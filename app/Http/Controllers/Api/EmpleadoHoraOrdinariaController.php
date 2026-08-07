<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use App\Models\VinetaRegistro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EmpleadoHoraOrdinariaController extends Controller
{
    private int $metaDiariaMinutos = 570;

    public function index(Request $request, Empleado $empleado): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $fecha = $data['fecha'];
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
            $cajones = VinetaRegistro::query()
                ->where('empleado_codigo', $empleado->codigo)
                ->whereDate('fecha_registro', $fecha)
                ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
                ->orderBy('fecha_registro')
                ->orderBy('hora_registro')
                ->orderBy('id')
                ->get();
        }

        $minutosCajones = (int) $cajones->sum(fn (VinetaRegistro $registro) => (int) ($registro->minutos_trabajados ?? 0));
        $minutosOrdinarios = (int) $ordinarias->sum('minutos');

        return response()->json([
            'message' => 'Horas ordinarias encontradas.',
            'tabla_disponible' => $tablaDisponible,
            'empleado' => $this->empleadoPayload($empleado),
            'fecha' => $fecha,
            'resumen' => $this->resumenPayload($minutosCajones, $minutosOrdinarios),
            'cajones' => $cajones->map(fn (VinetaRegistro $registro) => $this->cajonPayload($registro))->values(),
            'horas_ordinarias' => $ordinarias->map(fn (EmpleadoHoraOrdinaria $hora) => $this->horaPayload($hora))->values(),
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
        $totalMinutes = $data['minutos_total'];
        $registros = VinetaRegistro::query()
            ->where('empleado_codigo', $empleado->codigo)
            ->whereDate('fecha_registro', $fecha)
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get()
            ->filter(fn (VinetaRegistro $registro) => ! $registro->esPorHoraOrdinario())
            ->values();

        if ($registros->isEmpty()) {
            throw ValidationException::withMessages([
                'fecha' => 'Este empleado no tiene viñetas activas para distribuir jornada en la fecha seleccionada.',
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

    private function resumenPayload(int $minutosCajones, int $minutosOrdinarios): array
    {
        $total = $minutosCajones + $minutosOrdinarios;
        $faltante = max($this->metaDiariaMinutos - $total, 0);

        return [
            'meta_minutos' => $this->metaDiariaMinutos,
            'meta_texto' => VinetaRegistro::minutosATiempoTexto($this->metaDiariaMinutos),
            'minutos_cajones' => $minutosCajones,
            'tiempo_cajones_texto' => VinetaRegistro::minutosATiempoTexto($minutosCajones),
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
            'vineta' => $registro->vineta_api_id ? 'ID ' . $registro->vineta_api_id : $registro->codigo_vineta,
            'actividad' => $registro->actividad_nombre,
            'producto' => $registro->producto_nombre,
            'cantidad_puros' => $registro->cantidad_puros,
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
