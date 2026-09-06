<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use App\Models\VinetaRegistro;
use App\Support\EmployeeProductionGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmpleadoHoraOrdinariaController extends Controller
{
    private int $metaDiariaMinutos = 570;

    public function resumenEmpleados(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $fecha = $data['fecha'];
        $tablaDisponible = Schema::hasTable('empleado_horas_ordinarias');
        $registros = collect();

        if (Schema::hasTable('vineta_registros')) {
            $registros = VinetaRegistro::query()
                ->whereDate('fecha_registro', $fecha)
                ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
                ->orderBy('hora_registro')
                ->orderBy('id')
                ->get()
                ->filter(fn (VinetaRegistro $registro) => ! $registro->esPorHoraOrdinario())
                ->values();
        }

        $codigos = $registros
            ->map(fn (VinetaRegistro $registro) => trim((string) $registro->empleado_codigo))
            ->filter()
            ->unique()
            ->values();
        $empleados = Empleado::query()
            ->whereIn('codigo', $codigos)
            ->get()
            ->keyBy(fn (Empleado $empleado) => trim((string) $empleado->codigo));
        $gruposEmpleados = $this->gruposProduccionEmpleados($registros, $empleados);

        $minutosOrdinarios = collect();

        if ($tablaDisponible && $codigos->isNotEmpty()) {
            $minutosOrdinarios = EmpleadoHoraOrdinaria::query()
                ->whereIn('empleado_codigo', $codigos)
                ->whereDate('fecha', $fecha)
                ->selectRaw('empleado_codigo, COALESCE(SUM(minutos), 0) as minutos')
                ->groupBy('empleado_codigo')
                ->pluck('minutos', 'empleado_codigo');
        }

        $items = collect(['rezago', 'anillado', 'llenado', 'limpieza'])
            ->flatMap(function (string $grupo) use ($registros, $empleados, $gruposEmpleados, $minutosOrdinarios) {
                return $registros
                    ->filter(
                        fn (VinetaRegistro $registro) => $gruposEmpleados->get(
                            trim((string) $registro->empleado_codigo)
                        ) === $grupo
                    )
                    ->groupBy(fn (VinetaRegistro $registro) => trim((string) $registro->empleado_codigo))
                    ->map(function (Collection $registrosEmpleado, $codigo) use ($grupo, $empleados, $minutosOrdinarios) {
                        /** @var Empleado|null $empleado */
                        $empleado = $empleados->get($codigo);

                        if (! $empleado) {
                            return null;
                        }

                        return [
                            'grupo' => $grupo,
                            'empleado' => $this->empleadoPayload($empleado),
                            'resumen' => $this->resumenRegistrosPayload(
                                $registrosEmpleado,
                                (int) ($minutosOrdinarios[$empleado->codigo] ?? 0)
                            ),
                        ];
                    })
                    ->filter()
                    ->sortBy(fn (array $item) => Str::lower($item['empleado']['nombre']))
                    ->values();
            })
            ->values();

        return response()->json([
            'message' => 'Resumen de horas ordinarias encontrado.',
            'fecha' => $fecha,
            'tabla_disponible' => $tablaDisponible,
            'grupos' => [
                'rezago' => $items->where('grupo', 'rezago')->count(),
                'anillado' => $items->where('grupo', 'anillado')->count(),
                'llenado' => $items->where('grupo', 'llenado')->count(),
                'limpieza' => $items->where('grupo', 'limpieza')->count(),
            ],
            'empleados' => $items,
        ]);
    }

    public function index(Request $request, Empleado $empleado): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'grupo' => ['nullable', 'string', 'in:rezago,anillado,llenado,limpieza'],
        ]);

        $fecha = $data['fecha'];
        $grupo = $data['grupo'] ?? null;
        $tablaDisponible = Schema::hasTable('empleado_horas_ordinarias');
        $ordinarias = collect();

        if ($tablaDisponible) {
            $ordinarias = EmpleadoHoraOrdinaria::query()
                ->where('empleado_codigo', $empleado->codigo)
                ->whereDate('fecha', $fecha)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
        }

        $cajones = collect();

        if (Schema::hasTable('vineta_registros') && Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            $cajones = $this->registrosTareaEmpleado($empleado, $fecha);
            $grupoEmpleado = $this->grupoProduccionEmpleado($empleado, $cajones);

            if ($grupo !== null && $grupoEmpleado !== $grupo) {
                $cajones = collect();
            }
        }

        $minutosOrdinarios = (int) $ordinarias->sum('minutos');

        return response()->json([
            'message' => 'Horas ordinarias encontradas.',
            'tabla_disponible' => $tablaDisponible,
            'empleado' => $this->empleadoPayload($empleado),
            'fecha' => $fecha,
            'grupo' => $grupo,
            'resumen' => $this->resumenRegistrosPayload($cajones, $minutosOrdinarios),
            'cajones' => $cajones->map(fn (VinetaRegistro $registro) => $this->cajonPayload($registro))->values(),
            'horas_ordinarias' => $ordinarias->map(fn (EmpleadoHoraOrdinaria $hora) => $this->horaPayload($hora))->values(),
            'jornada_laboral' => $this->jornadaLaboralPayload($cajones),
        ]);
    }

    public function store(Request $request, Empleado $empleado): JsonResponse
    {
        if (! Schema::hasTable('empleado_horas_ordinarias')) {
            return response()->json([
                'message' => 'La tabla de horas ordinarias no existe. Ejecuta la migracion pendiente.',
            ], 409);
        }

        $data = $this->validatedHoraData($request);

        $hora = EmpleadoHoraOrdinaria::create([
            'empleado_id' => $empleado->id,
            'registrado_por_user_id' => $request->user()?->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'fecha' => $data['fecha'],
            'minutos' => $data['minutos_total'],
            'observacion' => $data['observacion'],
            'registrado_por_nombre' => $request->user()?->name,
        ]);

        return response()->json([
            'message' => 'Hora ordinaria agregada correctamente.',
            'hora_ordinaria' => $this->horaPayload($hora),
        ], 201);
    }

    public function update(Request $request, Empleado $empleado, EmpleadoHoraOrdinaria $horaOrdinaria): JsonResponse
    {
        if ($horaOrdinaria->empleado_codigo !== $empleado->codigo) {
            abort(404);
        }

        $data = $this->validatedHoraData($request);

        $horaOrdinaria->update([
            'fecha' => $data['fecha'],
            'minutos' => $data['minutos_total'],
            'observacion' => $data['observacion'],
        ]);

        return response()->json([
            'message' => 'Hora ordinaria actualizada correctamente.',
            'hora_ordinaria' => $this->horaPayload($horaOrdinaria->refresh()),
        ]);
    }

    public function destroy(Empleado $empleado, EmpleadoHoraOrdinaria $horaOrdinaria): JsonResponse
    {
        if ($horaOrdinaria->empleado_codigo !== $empleado->codigo) {
            abort(404);
        }

        $horaOrdinaria->delete();

        return response()->json([
            'message' => 'Hora ordinaria eliminada correctamente.',
        ]);
    }

    public function distributeJornada(Request $request, Empleado $empleado): JsonResponse
    {
        if (! Schema::hasTable('vineta_registros') || ! Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            return response()->json([
                'message' => 'La tabla de registros no permite distribuir minutos trabajados.',
            ], 409);
        }

        $data = $this->validatedJornadaData($request);
        $fecha = $data['fecha'];
        $grupo = $data['grupo'] ?? null;
        $totalMinutes = $data['minutos_total'];
        $registros = $this->registrosTareaEmpleado($empleado, $fecha);
        $grupoEmpleado = $this->grupoProduccionEmpleado($empleado, $registros);

        if ($grupo !== null && $grupoEmpleado !== $grupo) {
            $registros = collect();
        }

        if ($registros->isEmpty()) {
            throw ValidationException::withMessages([
                'fecha' => $grupo === null
                    ? 'Este empleado no tiene viñetas activas para distribuir jornada en la fecha seleccionada.'
                    : 'Este empleado no tiene viñetas activas del grupo seleccionado para distribuir jornada en la fecha seleccionada.',
            ]);
        }

        $baseMinutes = intdiv($totalMinutes, $registros->count());
        $extraMinutes = $totalMinutes % $registros->count();

        DB::transaction(function () use ($registros, $baseMinutes, $extraMinutes) {
            foreach ($registros as $index => $registro) {
                $registro->update([
                    'minutos_trabajados' => $baseMinutes + ($index < $extraMinutes ? 1 : 0),
                ]);
            }
        });

        return response()->json([
            'message' => 'Jornada laboral distribuida correctamente.',
            'registros_actualizados' => $registros->count(),
            'minutos_distribuidos' => $totalMinutes,
            'tiempo_distribuido_texto' => VinetaRegistro::minutosATiempoTexto($totalMinutes),
        ]);
    }

    public function destroyJornada(Request $request, Empleado $empleado): JsonResponse
    {
        if (! Schema::hasTable('vineta_registros') || ! Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            return response()->json([
                'message' => 'La tabla de registros no permite eliminar la jornada laboral.',
            ], 409);
        }

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'grupo' => ['nullable', 'string', 'in:rezago,anillado,llenado,limpieza'],
        ]);
        $registros = $this->registrosTareaEmpleado($empleado, $data['fecha']);
        $grupo = $data['grupo'] ?? null;
        $grupoEmpleado = $this->grupoProduccionEmpleado($empleado, $registros);

        if ($grupo !== null && $grupoEmpleado !== $grupo) {
            $registros = collect();
        }

        if ($registros->isEmpty()) {
            throw ValidationException::withMessages([
                'fecha' => 'Este empleado no tiene una jornada distribuida en la fecha seleccionada.',
            ]);
        }

        DB::transaction(function () use ($registros) {
            foreach ($registros as $registro) {
                $registro->update(['minutos_trabajados' => null]);
            }
        });

        return response()->json([
            'message' => 'Distribucion de jornada laboral eliminada correctamente.',
            'registros_actualizados' => $registros->count(),
        ]);
    }

    public function distributeGlobal(Request $request): JsonResponse
    {
        if (! Schema::hasTable('vineta_registros') || ! Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            return response()->json([
                'message' => 'La tabla de registros no permite distribuir minutos trabajados.',
            ], 409);
        }

        $data = $this->validatedJornadaData($request);
        $fecha = $data['fecha'];
        $grupo = $data['grupo'] ?? null;
        $totalMinutes = $data['minutos_total'];

        $registros = VinetaRegistro::query()
            ->whereDate('fecha_registro', $fecha)
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get()
            ->filter(fn (VinetaRegistro $registro) => ! $registro->esPorHoraOrdinario())
            ->values();

        $codigos = $registros->pluck('empleado_codigo')->filter()->unique()->values();
        $empleados = Empleado::query()->whereIn('codigo', $codigos)->get()->keyBy('codigo');
        $gruposEmpleados = $this->gruposProduccionEmpleados($registros, $empleados);

        $empleadosActualizados = 0;
        $registrosActualizados = 0;

        DB::transaction(function () use ($registros, $gruposEmpleados, $grupo, $totalMinutes, &$empleadosActualizados, &$registrosActualizados) {
            $porEmpleado = $registros->groupBy('empleado_codigo');

            foreach ($porEmpleado as $codigo => $registrosEmpleado) {
                if ($grupo !== null && $gruposEmpleados->get((string) $codigo) !== $grupo) {
                    continue;
                }

                if ($registrosEmpleado->isEmpty()) {
                    continue;
                }

                $count = $registrosEmpleado->count();
                $baseMinutes = intdiv($totalMinutes, $count);
                $extraMinutes = $totalMinutes % $count;

                foreach ($registrosEmpleado->values() as $index => $registro) {
                    $registro->update([
                        'minutos_trabajados' => $baseMinutes + ($index < $extraMinutes ? 1 : 0),
                    ]);
                    $registrosActualizados++;
                }

                $empleadosActualizados++;
            }
        });

        if ($empleadosActualizados === 0) {
            throw ValidationException::withMessages([
                'fecha' => 'No se encontraron registros activos para distribuir jornada en la fecha y grupo seleccionados.',
            ]);
        }

        return response()->json([
            'message' => "Jornada distribuida correctamente a {$empleadosActualizados} empleado(s).",
            'empleados_actualizados' => $empleadosActualizados,
            'registros_actualizados' => $registrosActualizados,
            'minutos_distribuidos' => $totalMinutes,
            'tiempo_distribuido_texto' => VinetaRegistro::minutosATiempoTexto($totalMinutes),
        ]);
    }

    public function destroyGlobal(Request $request): JsonResponse
    {
        if (! Schema::hasTable('vineta_registros') || ! Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            return response()->json([
                'message' => 'La tabla de registros no permite eliminar la jornada laboral.',
            ], 409);
        }

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'grupo' => ['nullable', 'string', 'in:rezago,anillado,llenado,limpieza'],
        ]);
        $fecha = $data['fecha'];
        $grupo = $data['grupo'] ?? null;

        $registros = VinetaRegistro::query()
            ->whereDate('fecha_registro', $fecha)
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->whereNotNull('minutos_trabajados')
            ->get();

        $codigos = $registros->pluck('empleado_codigo')->filter()->unique()->values();
        $empleados = Empleado::query()->whereIn('codigo', $codigos)->get()->keyBy('codigo');
        $gruposEmpleados = $this->gruposProduccionEmpleados($registros, $empleados);

        $eliminados = 0;

        DB::transaction(function () use ($registros, $gruposEmpleados, $grupo, &$eliminados) {
            foreach ($registros as $registro) {
                if ($grupo !== null && $gruposEmpleados->get((string) $registro->empleado_codigo) !== $grupo) {
                    continue;
                }

                $registro->update(['minutos_trabajados' => null]);
                $eliminados++;
            }
        });

        return response()->json([
            'message' => "Distribución de jornada eliminada correctamente ({$eliminados} registros actualizados).",
            'registros_actualizados' => $eliminados,
        ]);
    }


    /** @return Collection<int, VinetaRegistro> */
    private function registrosTareaEmpleado(Empleado $empleado, string $fecha): Collection
    {
        return VinetaRegistro::query()
            ->where('empleado_codigo', $empleado->codigo)
            ->whereDate('fecha_registro', $fecha)
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get()
            ->filter(fn (VinetaRegistro $registro) => ! $registro->esPorHoraOrdinario())
            ->values();
    }

    private function grupoProduccionEmpleado(Empleado $empleado, Collection $registros): ?string
    {
        $codigoTrim = trim((string) $empleado->codigo);
        if ($codigoTrim === '8219' || $codigoTrim === '8217') {
            return 'rezago';
        }

        $grupoPuesto = EmployeeProductionGroup::fromCargo($empleado->cargo, $codigoTrim);
        if ($grupoPuesto !== null && in_array($grupoPuesto, ['rezago', 'anillado', 'llenado', 'limpieza'], true)) {
            return $grupoPuesto;
        }

        return $registros
            ->map(fn (VinetaRegistro $registro) => $this->grupoActividadRegistro($registro))
            ->first(fn (?string $grupo) => $grupo !== null && in_array($grupo, ['rezago', 'anillado', 'llenado', 'limpieza'], true));
    }

    /**
     * @param  Collection<int, VinetaRegistro>  $registros
     * @param  Collection<string, Empleado>  $empleados
     * @return Collection<string, string>
     */
    private function gruposProduccionEmpleados(Collection $registros, Collection $empleados): Collection
    {
        return $registros
            ->groupBy(fn (VinetaRegistro $registro) => trim((string) $registro->empleado_codigo))
            ->map(function (Collection $items, string $codigo) use ($empleados) {
                $empleado = $empleados->get($codigo);

                return $empleado instanceof Empleado
                    ? $this->grupoProduccionEmpleado($empleado, $items)
                    : null;
            })
            ->filter();
    }

    /** @param Collection<int, VinetaRegistro> $registros */
    private function jornadaLaboralPayload(Collection $registros): ?array
    {
        $minutos = (int) $registros->sum(fn (VinetaRegistro $registro) => (int) ($registro->minutos_trabajados ?? 0));

        if ($minutos <= 0) {
            return null;
        }

        return [
            'message' => 'Jornada laboral distribuida.',
            'registros_actualizados' => $registros->count(),
            'minutos_distribuidos' => $minutos,
            'tiempo_distribuido_texto' => VinetaRegistro::minutosATiempoTexto($minutos),
        ];
    }

    /** @param Collection<int, VinetaRegistro> $registros */
    private function resumenRegistrosPayload(Collection $registros, int $minutosOrdinarios): array
    {
        $minutosVinetas = (int) $registros->sum(
            fn (VinetaRegistro $registro) => $registro->esPorHoraOrdinario()
                ? 0
                : (int) ($registro->minutos_trabajados ?? 0)
        );

        return $this->resumenPayload(
            $minutosVinetas,
            $minutosOrdinarios,
            $registros->count(),
            (int) $registros->sum('cantidad_puros'),
            (int) $registros->sum(fn (VinetaRegistro $registro) => $registro->total_actividades)
        );
    }

    private function resumenPayload(
        int $minutosVinetas,
        int $minutosOrdinarios,
        int $totalVinetas,
        int $totalPuros,
        int $totalActividades
    ): array {
        $total = $minutosVinetas + $minutosOrdinarios;
        $faltante = max($this->metaDiariaMinutos - $total, 0);

        return [
            'meta_minutos' => $this->metaDiariaMinutos,
            'meta_texto' => VinetaRegistro::minutosATiempoTexto($this->metaDiariaMinutos),
            'total_vinetas' => $totalVinetas,
            'total_puros' => $totalPuros,
            'total_actividades' => $totalActividades,
            'minutos_vinetas' => $minutosVinetas,
            'tiempo_vinetas_texto' => VinetaRegistro::minutosATiempoTexto($minutosVinetas),
            'minutos_cajones' => $minutosVinetas,
            'tiempo_cajones_texto' => VinetaRegistro::minutosATiempoTexto($minutosVinetas),
            'minutos_ordinarios' => $minutosOrdinarios,
            'tiempo_ordinario_texto' => VinetaRegistro::minutosATiempoTexto($minutosOrdinarios),
            'total_minutos' => $total,
            'total_texto' => VinetaRegistro::minutosATiempoTexto($total),
            'faltante_minutos' => $faltante,
            'faltante_texto' => VinetaRegistro::minutosATiempoTexto($faltante),
            'completado' => $total >= $this->metaDiariaMinutos,
            'porcentaje' => $this->metaDiariaMinutos > 0
                ? min(round(($total / $this->metaDiariaMinutos) * 100, 1), 100)
                : 0,
        ];
    }

    private function grupoActividadRegistro(VinetaRegistro $registro): ?string
    {
        $texto = implode(' ', array_filter([
            $registro->actividad_nombre,
            $registro->actividad_tipo_empaque,
            $registro->actividad_codigo,
        ], fn ($valor) => trim((string) $valor) !== ''));
        $texto = Str::ascii(Str::lower(trim($texto)));
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto) ?? $texto;

        if (str_contains($texto, 'rezag') || str_contains($texto, 'rezad') || str_contains($texto, 'resag')) {
            return 'rezago';
        }

        if (
            str_contains($texto, 'anill')
            || str_contains($texto, 'anil')
            || str_contains($texto, 'celof')
            || str_contains($texto, 'sello')
            || str_contains($texto, 'sell')
            || str_contains($texto, 'esponj')
            || str_contains($texto, 'lamina')
        ) {
            return 'anillado';
        }

        if (
            str_contains($texto, 'llenad')
            || str_contains($texto, 'petaca')
            || str_contains($texto, 'sampler')
            || (str_contains($texto, 'paquete') && str_contains($texto, 'tubo'))
        ) {
            return 'llenado';
        }

        if (
            str_contains($texto, 'limpia')
            || str_contains($texto, 'limpiad')
            || str_contains($texto, 'limpi')
        ) {
            return 'limpieza';
        }

        return null;
    }

    private function validatedHoraData(Request $request): array
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'horas' => ['nullable', 'integer', 'min:0', 'max:9'],
            'minutos' => ['nullable', 'integer', 'min:0', 'max:570'],
            'observacion' => ['required', 'string', 'max:1000'],
        ]);

        $hasHoras = $request->has('horas') && $request->input('horas') !== null && $request->input('horas') !== '';
        $horas = (int) ($data['horas'] ?? 0);
        $minutosInput = (int) ($data['minutos'] ?? 0);

        if ($hasHoras && $minutosInput > 59 && $horas > 0) {
            throw ValidationException::withMessages([
                'minutos' => 'Cuando ingresas horas, los minutos deben estar entre 0 y 59.',
            ]);
        }

        $minutosTotal = $hasHoras && $minutosInput <= 59
            ? ($horas * 60) + $minutosInput
            : $minutosInput;

        if ($minutosTotal <= 0 || $minutosTotal > $this->metaDiariaMinutos) {
            throw ValidationException::withMessages([
                'minutos' => 'Ingresa un tiempo entre 1 minuto y 9 h 30 min.',
            ]);
        }

        $data['minutos_total'] = $minutosTotal;

        return $data;
    }

    private function validatedJornadaData(Request $request): array
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'grupo' => ['nullable', 'string', 'in:rezago,anillado,llenado,limpieza'],
            'horas' => ['nullable', 'integer', 'min:0', 'max:9'],
            'minutos' => ['nullable', 'integer', 'min:0', 'max:570'],
        ]);

        $hasHoras = $request->has('horas') && $request->input('horas') !== null && $request->input('horas') !== '';
        $horas = (int) ($data['horas'] ?? 0);
        $minutosInput = (int) ($data['minutos'] ?? 0);

        if ($hasHoras && $minutosInput > 59 && $horas > 0) {
            throw ValidationException::withMessages([
                'minutos' => 'Cuando ingresas horas, los minutos deben estar entre 0 y 59.',
            ]);
        }

        $minutosTotal = $hasHoras && $minutosInput <= 59
            ? ($horas * 60) + $minutosInput
            : $minutosInput;

        if ($minutosTotal <= 0 || $minutosTotal > $this->metaDiariaMinutos) {
            throw ValidationException::withMessages([
                'minutos' => 'Ingresa una jornada entre 1 minuto y 9 h 30 min.',
            ]);
        }

        $data['minutos_total'] = $minutosTotal;

        return $data;
    }

    private function cajonPayload(VinetaRegistro $registro): array
    {
        $porHora = $registro->esPorHoraOrdinario();

        return [
            'id' => $registro->id,
            'vineta' => $registro->vineta_api_id ? 'ID '.$registro->vineta_api_id : $registro->codigo_vineta,
            'actividad' => $registro->actividad_nombre,
            'grupo' => $this->grupoActividadRegistro($registro),
            'producto' => $registro->producto_nombre,
            'cantidad_puros' => $registro->cantidad_puros,
            'cantidad_actividades' => $registro->cantidadActividadesValor(),
            'total_actividades' => $registro->total_actividades,
            'modo_registro' => $registro->modoRegistro(),
            'por_hora' => $porHora,
            'minutos' => $porHora ? null : (int) ($registro->minutos_trabajados ?? 0),
            'tiempo_texto' => $registro->tiempoTrabajadoReporteTexto(),
            'registrado_en_texto' => $registro->fechaHoraRegistroTexto(),
        ];
    }

    private function horaPayload(EmpleadoHoraOrdinaria $hora): array
    {
        $minutos = (int) ($hora->minutos ?? 0);

        return [
            'id' => $hora->id,
            'fecha' => $hora->fecha?->format('Y-m-d'),
            'minutos' => $minutos,
            'horas' => intdiv($minutos, 60),
            'minutos_resto' => $minutos % 60,
            'tiempo_texto' => VinetaRegistro::minutosATiempoTexto($minutos),
            'observacion' => $hora->observacion,
            'registrado_por' => $hora->registrado_por_nombre,
            'created_at_texto' => $hora->created_at?->timezone('America/Tegucigalpa')->format('d/m/Y h:i A'),
        ];
    }

    private function empleadoPayload(Empleado $empleado): array
    {
        return [
            'id' => $empleado->id,
            'codigo' => $empleado->codigo,
            'nombre' => $empleado->nombre,
            'cargo' => $empleado->cargo,
            'area' => $empleado->area,
            'activo' => (bool) $empleado->activo,
        ];
    }
}
