<?php

namespace App\Http\Controllers;

use App\Models\VinetaRegistro;
use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class VinetaRegistroController extends Controller
{
    private int $metaDiariaMinutos = 570;

    public function index(Request $request)
    {
        $perPage = $this->perPage($request->get('per_page', 25));
        $migrationPending = ! Schema::hasTable('vineta_registros');
        $hasHorasOrdinarias = Schema::hasTable('empleado_horas_ordinarias');

        if ($migrationPending) {
            return view('vineta-registros.index', [
                'registros' => new LengthAwarePaginator([], 0, $perPage, 1, [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]),
                'totales' => [
                    'registros' => 0,
                    'ordinarias' => 0,
                    'puros' => 0,
                    'cajones' => 0,
                    'actividades' => 0,
                    'minutos' => 0,
                    'minutos_cajones' => 0,
                    'minutos_ordinarios' => 0,
                    'tiempo' => '0 min',
                    'tiempo_cajones' => '0 min',
                    'tiempo_ordinario' => '0 min',
                    'monto' => 0,
                ],
                'orden' => 'fecha_registro',
                'direccion' => 'desc',
                'estado' => 'activo',
                'actividadGrupo' => '',
                'migrationPending' => true,
                'hasCantidadActividades' => false,
                'hasMinutosTrabajados' => false,
                'seguimientoTimelineMap' => [],
                'seguimientoResumenMap' => [],
                'hasHorasOrdinarias' => $hasHorasOrdinarias,
            ]);
        }

        $hasCantidadActividades = Schema::hasColumn('vineta_registros', 'cantidad_actividades');
        $hasMinutosTrabajados = Schema::hasColumn('vineta_registros', 'minutos_trabajados');

        $buscar = trim((string) $request->get('buscar', ''));
        $empleado = trim((string) $request->get('empleado', ''));
        $actividadGrupo = $this->actividadGrupo($request->get('actividad_grupo'));
        $fechaDesde = $this->dateInput($request->get('fecha_desde'));
        $fechaHasta = $this->dateInput($request->get('fecha_hasta'));
        $estado = $request->get('estado', 'activo');
        $orden = $request->get('orden', 'fecha_registro');
        $direccion = $request->get('direccion', 'desc');

        $ordenesPermitidos = [
            'fecha_registro',
            'codigo_vineta',
            'vineta_api_id',
            'empleado_nombre',
            'actividad_nombre',
            'precio_mo',
            'cantidad_puros',
            'cantidad_cajones',
        ];

        if ($hasCantidadActividades) {
            $ordenesPermitidos[] = 'cantidad_actividades';
        }

        if ($hasMinutosTrabajados) {
            $ordenesPermitidos[] = 'minutos_trabajados';
        }

        if (! in_array($orden, $ordenesPermitidos, true)) {
            $orden = 'fecha_registro';
        }

        if (! in_array($direccion, ['asc', 'desc'], true)) {
            $direccion = 'desc';
        }

        if (! in_array($estado, ['activo', 'anulado', 'todos'], true)) {
            $estado = 'activo';
        }

        $query = VinetaRegistro::query()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $like = '%' . $buscar . '%';
                $apiId = ltrim($buscar, '#');

                $query->where(function ($query) use ($like, $apiId) {
                    $query->where('codigo_vineta', 'like', $like)
                        ->orWhere('id_pendiente_empaque', 'like', $like)
                        ->orWhere('producto_codigo', 'like', $like)
                        ->orWhere('producto_item', 'like', $like)
                        ->orWhere('producto_nombre', 'like', $like)
                        ->orWhere('empleado_codigo', 'like', $like)
                        ->orWhere('empleado_nombre', 'like', $like)
                        ->orWhere('actividad_nombre', 'like', $like);

                    if (ctype_digit($apiId)) {
                        $query->orWhere('vineta_api_id', (int) $apiId);
                    }
                });
            })
            ->when($empleado !== '', function ($query) use ($empleado) {
                $like = '%' . $empleado . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('empleado_codigo', 'like', $like)
                        ->orWhere('empleado_nombre', 'like', $like);
                });
            })
            ->when($actividadGrupo !== '', fn ($query) => $this->applyActividadGrupo($query, $actividadGrupo))
            ->when($fechaDesde, fn ($query) => $query->whereDate('fecha_registro', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('fecha_registro', '<=', $fechaHasta))
            ->when($estado !== 'todos', fn ($query) => $query->where('estado', $estado));

        $horasOrdinariasQuery = $hasHorasOrdinarias
            ? $this->filteredHorasOrdinariasQuery($request)
            : null;
        $totalesQuery = clone $query;
        $totalMinutosCajones = $this->totalMinutos($totalesQuery, $hasMinutosTrabajados);
        $totalMinutosOrdinarios = $horasOrdinariasQuery
            ? (int) (clone $horasOrdinariasQuery)->sum('minutos')
            : 0;
        $totalMinutos = $totalMinutosCajones + $totalMinutosOrdinarios;
        $totales = [
            'registros' => (clone $totalesQuery)->count() + ($horasOrdinariasQuery ? (clone $horasOrdinariasQuery)->count() : 0),
            'ordinarias' => $horasOrdinariasQuery ? (clone $horasOrdinariasQuery)->count() : 0,
            'puros' => (clone $totalesQuery)->sum('cantidad_puros'),
            'cajones' => (clone $totalesQuery)->sum('cantidad_cajones'),
            'actividades' => $this->totalActividades($totalesQuery, $hasCantidadActividades),
            'minutos' => $totalMinutos,
            'minutos_cajones' => $totalMinutosCajones,
            'minutos_ordinarios' => $totalMinutosOrdinarios,
            'tiempo' => VinetaRegistro::minutosATiempoTexto($totalMinutos),
            'tiempo_cajones' => VinetaRegistro::minutosATiempoTexto($totalMinutosCajones),
            'tiempo_ordinario' => VinetaRegistro::minutosATiempoTexto($totalMinutosOrdinarios),
            'monto' => $this->totalMonto($totalesQuery),
        ];

        $vinetaRows = $query->get();
        $horaRows = $horasOrdinariasQuery ? $horasOrdinariasQuery->get() : collect();
        $combinedRows = $this->ordenarRegistrosCombinados(
            $vinetaRows->concat($horaRows),
            $orden,
            $direccion
        );
        $page = max((int) $request->get('page', 1), 1);
        $registros = new LengthAwarePaginator(
            $combinedRows->forPage($page, $perPage)->values(),
            $combinedRows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        $seguimiento = $this->seguimientoPorVineta(
            $registros->getCollection()
                ->filter(fn ($registro) => $registro instanceof VinetaRegistro)
                ->pluck('vineta_id')
        );

        return view('vineta-registros.index', compact(
            'registros',
            'totales',
            'orden',
            'direccion',
            'estado',
            'actividadGrupo'
        ) + [
            'migrationPending' => false,
            'hasCantidadActividades' => $hasCantidadActividades,
            'hasMinutosTrabajados' => $hasMinutosTrabajados,
            'seguimientoTimelineMap' => $seguimiento['timelines'],
            'seguimientoResumenMap' => $seguimiento['resumenes'],
            'hasHorasOrdinarias' => $hasHorasOrdinarias,
        ]);
    }

    public function destroy(VinetaRegistro $vinetaRegistro)
    {
        $vinetaRegistro->delete();

        return back()->with('success', 'Registro eliminado correctamente.');
    }

    public function storeHoraOrdinaria(Request $request)
    {
        if (! Schema::hasTable('empleado_horas_ordinarias')) {
            return back()->withErrors([
                'hora_ordinaria' => 'La tabla de horas ordinarias no existe. Ejecuta la migracion pendiente.',
            ]);
        }

        $data = $request->validate([
            'empleado_codigo' => ['required', 'string', 'max:120'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'horas' => ['nullable', 'integer', 'min:0', 'max:9'],
            'minutos' => ['nullable', 'integer', 'min:0', 'max:59'],
            'observacion' => ['required', 'string', 'max:1000'],
        ]);

        $minutos = ((int) ($data['horas'] ?? 0) * 60) + (int) ($data['minutos'] ?? 0);

        if ($minutos <= 0 || $minutos > $this->metaDiariaMinutos) {
            return back()
                ->withInput()
                ->withErrors(['minutos' => 'Ingresa un tiempo entre 1 minuto y 9 h 30 min.']);
        }

        $empleado = Empleado::where('codigo', trim($data['empleado_codigo']))->first();

        if (! $empleado) {
            return back()
                ->withInput()
                ->withErrors(['empleado_codigo' => 'No se encontró el empleado indicado.']);
        }

        if (! $empleado->activo) {
            return back()
                ->withInput()
                ->withErrors(['empleado_codigo' => 'El empleado indicado no está activo.']);
        }

        EmpleadoHoraOrdinaria::create([
            'empleado_id' => $empleado->id,
            'registrado_por_user_id' => $request->user()?->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'fecha' => $data['fecha'],
            'minutos' => $minutos,
            'observacion' => trim($data['observacion']),
            'registrado_por_nombre' => $request->user()?->name,
        ]);

        return back()->with('success', 'Hora ordinaria agregada correctamente.');
    }

    public function updateHoraOrdinaria(Request $request, EmpleadoHoraOrdinaria $horaOrdinaria)
    {
        $data = $request->validate([
            'empleado_codigo' => ['required', 'string', 'max:120'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'horas' => ['nullable', 'integer', 'min:0', 'max:9'],
            'minutos' => ['nullable', 'integer', 'min:0', 'max:59'],
            'observacion' => ['required', 'string', 'max:1000'],
        ]);

        $minutos = ((int) ($data['horas'] ?? 0) * 60) + (int) ($data['minutos'] ?? 0);

        if ($minutos <= 0 || $minutos > $this->metaDiariaMinutos) {
            return back()
                ->withInput()
                ->withErrors(['minutos' => 'Ingresa un tiempo entre 1 minuto y 9 h 30 min.']);
        }

        $empleado = Empleado::where('codigo', trim($data['empleado_codigo']))->first();

        if (! $empleado) {
            return back()
                ->withInput()
                ->withErrors(['empleado_codigo' => 'No se encontró el empleado indicado.']);
        }

        if (! $empleado->activo) {
            return back()
                ->withInput()
                ->withErrors(['empleado_codigo' => 'El empleado indicado no está activo.']);
        }

        $horaOrdinaria->update([
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'fecha' => $data['fecha'],
            'minutos' => $minutos,
            'observacion' => trim($data['observacion']),
        ]);

        return back()->with('success', 'Hora ordinaria actualizada correctamente.');
    }

    public function destroyHoraOrdinaria(EmpleadoHoraOrdinaria $horaOrdinaria)
    {
        $horaOrdinaria->delete();

        return back()->with('success', 'Hora ordinaria eliminada correctamente.');
    }

    public function update(Request $request, VinetaRegistro $vinetaRegistro)
    {
        $hasMinutosTrabajados = Schema::hasColumn('vineta_registros', 'minutos_trabajados');
        $porHora = $vinetaRegistro->esPorHoraOrdinario();
        $rules = [
            'fecha_registro' => ['required', 'date_format:Y-m-d'],
            'hora_registro' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'cantidad_puros' => ['required', 'integer', 'min:1', 'max:1000000'],
            'empleado_codigo' => ['required', 'string', 'max:120'],
        ];

        if ($hasMinutosTrabajados && ! $porHora) {
            $rules['minutos_trabajados'] = ['required', 'integer', 'min:1', 'max:' . $this->metaDiariaMinutos];
        }

        $data = $request->validate($rules);

        $empleado = Empleado::where('codigo', trim($data['empleado_codigo']))->first();

        if (! $empleado) {
            return back()
                ->withInput()
                ->withErrors(['empleado_codigo' => 'No se encontró el empleado indicado.']);
        }

        if (! $empleado->activo) {
            return back()
                ->withInput()
                ->withErrors(['empleado_codigo' => 'El empleado indicado no está activo.']);
        }

        $hora = $this->normalizeTime($data['hora_registro']);
        $registradoEn = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $data['fecha_registro'] . ' ' . $hora,
            'America/Tegucigalpa'
        );

        $duplicado = VinetaRegistro::query()
            ->where('id', '!=', $vinetaRegistro->id)
            ->where('vineta_id', $vinetaRegistro->vineta_id)
            ->where('fecha_registro', $registradoEn->toDateString())
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->where(function ($query) use ($vinetaRegistro) {
                if ($vinetaRegistro->actividad_id) {
                    $query->orWhere('actividad_id', $vinetaRegistro->actividad_id);
                }

                if ($vinetaRegistro->actividad_api_id) {
                    $query->orWhere('actividad_api_id', $vinetaRegistro->actividad_api_id);
                }

                if ($vinetaRegistro->actividad_codigo) {
                    $query->orWhere('actividad_codigo', $vinetaRegistro->actividad_codigo);
                }

                $query->orWhereRaw('LOWER(actividad_nombre) = ?', [strtolower($vinetaRegistro->actividad_nombre)]);
            })
            ->exists();

        if ($duplicado) {
            return back()
                ->withInput()
                ->withErrors(['fecha_registro' => 'Ya existe otro registro activo para esta viñeta, actividad y fecha.']);
        }

        $payload = [
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'cantidad_puros' => (int) $data['cantidad_puros'],
            'fecha_registro' => $registradoEn->toDateString(),
            'hora_registro' => $registradoEn->format('H:i:s'),
            'registrado_en' => $registradoEn,
        ];

        if ($hasMinutosTrabajados) {
            $payload['minutos_trabajados'] = $porHora
                ? null
                : (int) $data['minutos_trabajados'];
        }

        $vinetaRegistro->update($payload);

        return back()->with('success', 'Registro actualizado correctamente.');
    }

    public function empleado(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:120'],
        ]);

        $empleado = Empleado::where('codigo', trim($data['codigo']))->first();

        if (! $empleado) {
            return response()->json([
                'message' => 'No se encontró el empleado.',
                'employee' => null,
            ], 404);
        }

        return response()->json([
            'message' => 'Empleado encontrado.',
            'employee' => [
                'id' => $empleado->id,
                'codigo' => $empleado->codigo,
                'nombre' => $empleado->nombre,
                'activo' => (bool) $empleado->activo,
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $fileName = 'vinetas_procesadas_' . now('America/Tegucigalpa')->format('Ymd_His') . '.xlsx';
        $hasMinutosTrabajados = Schema::hasColumn('vineta_registros', 'minutos_trabajados');
        $hasHorasOrdinarias = Schema::hasTable('empleado_horas_ordinarias');
        $registros = $this->filteredQuery($request)
            ->orderBy('empleado_nombre')
            ->orderBy('empleado_codigo')
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->cursor();
        $horasOrdinarias = $hasHorasOrdinarias
            ? $this->filteredHorasOrdinariasQuery($request)
                ->orderBy('empleado_nombre')
                ->orderBy('empleado_codigo')
                ->orderBy('fecha')
                ->orderBy('created_at')
                ->orderBy('id')
                ->cursor()
            : collect();

        $detalle = [[
            'Fecha',
            'Empleado',
            'Codigo empleado',
            'Tipo',
            'Viñeta',
            'Codigo producto',
            'Marca',
            'Item',
            'Orden sistema',
            'Orden cliente',
            'Actividad',
            'Cantidad procesada',
            'Estadistico',
            'Minutos',
            'Tiempo',
            'Observacion',
        ]];
        $resumen = [];
        $tiempoDiario = [];

        foreach ($registros as $registro) {
            $porHora = $registro->esPorHoraOrdinario();
            $estadistico = $registro->total_actividades;
            $minutos = $hasMinutosTrabajados && ! $porHora ? (int) ($registro->minutos_trabajados ?? 0) : 0;

            $detalle[] = [
                $registro->fecha_registro?->format('Y-m-d') ?? 'N/A',
                $registro->empleado_nombre,
                $registro->empleado_codigo,
                $porHora ? 'Viñeta por hora' : 'Viñeta',
                $registro->vineta_api_id ? 'ID ' . $registro->vineta_api_id : $registro->codigo_vineta,
                $this->valorExport($registro->producto_codigo),
                $this->valorExport($registro->marca),
                $this->valorExport($registro->producto_item),
                $this->valorExport($registro->orden_del_sistema),
                $this->valorExport($registro->orden),
                $registro->actividad_nombre,
                $registro->cantidad_puros,
                $estadistico,
                $porHora ? '' : $minutos,
                $porHora ? 'Por hora ordinario' : VinetaRegistro::minutosATiempoTexto($minutos),
                '',
            ];

            $key = $registro->empleado_codigo . '|' . $registro->empleado_nombre;

            if (! isset($resumen[$key])) {
                $resumen[$key] = [
                    'empleado' => $registro->empleado_nombre,
                    'codigo' => $registro->empleado_codigo,
                    'estadistico' => 0,
                ];
            }

            $resumen[$key]['estadistico'] += $estadistico;

            if ($hasMinutosTrabajados) {
                $fecha = $registro->fecha_registro?->format('Y-m-d') ?? 'N/A';
                $tiempoKey = $fecha . '|' . $registro->empleado_codigo . '|' . $registro->empleado_nombre;

                if (! isset($tiempoDiario[$tiempoKey])) {
                    $tiempoDiario[$tiempoKey] = [
                        'fecha' => $fecha,
                        'empleado' => $registro->empleado_nombre,
                        'codigo' => $registro->empleado_codigo,
                        'minutos_cajones' => 0,
                        'minutos_ordinarios' => 0,
                    ];
                }

                $tiempoDiario[$tiempoKey]['minutos_cajones'] += $minutos;
            }
        }

        foreach ($horasOrdinarias as $hora) {
            $minutos = (int) ($hora->minutos ?? 0);
            $fecha = $hora->fecha?->format('Y-m-d') ?? 'N/A';

            $detalle[] = [
                $fecha,
                $hora->empleado_nombre,
                $hora->empleado_codigo,
                'Hora ordinaria',
                'N/A',
                'N/A',
                'N/A',
                'N/A',
                'N/A',
                'N/A',
                'Hora ordinaria',
                0,
                0,
                $minutos,
                VinetaRegistro::minutosATiempoTexto($minutos),
                $hora->observacion,
            ];

            $key = $hora->empleado_codigo . '|' . $hora->empleado_nombre;

            if (! isset($resumen[$key])) {
                $resumen[$key] = [
                    'empleado' => $hora->empleado_nombre,
                    'codigo' => $hora->empleado_codigo,
                    'estadistico' => 0,
                ];
            }

            $tiempoKey = $fecha . '|' . $hora->empleado_codigo . '|' . $hora->empleado_nombre;

            if (! isset($tiempoDiario[$tiempoKey])) {
                $tiempoDiario[$tiempoKey] = [
                    'fecha' => $fecha,
                    'empleado' => $hora->empleado_nombre,
                    'codigo' => $hora->empleado_codigo,
                    'minutos_cajones' => 0,
                    'minutos_ordinarios' => 0,
                ];
            }

            $tiempoDiario[$tiempoKey]['minutos_ordinarios'] += $minutos;
        }

        uasort($resumen, function (array $a, array $b) {
            return [$a['empleado'], $a['codigo']] <=> [$b['empleado'], $b['codigo']];
        });

        $resumenRows = [[
            'Empleado',
            'Codigo empleado',
            'Estadistico',
        ]];

        foreach ($resumen as $row) {
            $resumenRows[] = [
                $row['empleado'],
                $row['codigo'],
                $row['estadistico'],
            ];
        }

        $sheets = [
            ['name' => 'Detalle', 'rows' => $detalle],
            ['name' => 'Resumen empleados', 'rows' => $resumenRows],
        ];

        if ($hasMinutosTrabajados || $hasHorasOrdinarias) {
            uasort($tiempoDiario, function (array $a, array $b) {
                return [$a['fecha'], $a['empleado'], $a['codigo']] <=> [$b['fecha'], $b['empleado'], $b['codigo']];
            });

            $tiempoRows = [[
                'Fecha',
                'Empleado',
                'Codigo empleado',
                'Minutos cajones',
                'Tiempo cajones',
                'Minutos ordinarios',
                'Tiempo ordinario',
                'Minutos total',
                'Tiempo total',
                'Meta diaria',
                'Faltante',
                'Estado',
            ]];

            foreach ($tiempoDiario as $row) {
                $minutosCajones = (int) ($row['minutos_cajones'] ?? 0);
                $minutosOrdinarios = (int) ($row['minutos_ordinarios'] ?? 0);
                $totalMinutos = $minutosCajones + $minutosOrdinarios;
                $faltante = max($this->metaDiariaMinutos - $totalMinutos, 0);

                $tiempoRows[] = [
                    $row['fecha'],
                    $row['empleado'],
                    $row['codigo'],
                    $minutosCajones,
                    VinetaRegistro::minutosATiempoTexto($minutosCajones),
                    $minutosOrdinarios,
                    VinetaRegistro::minutosATiempoTexto($minutosOrdinarios),
                    $totalMinutos,
                    VinetaRegistro::minutosATiempoTexto($totalMinutos),
                    VinetaRegistro::minutosATiempoTexto($this->metaDiariaMinutos),
                    VinetaRegistro::minutosATiempoTexto($faltante),
                    $totalMinutos >= $this->metaDiariaMinutos ? 'Completo' : 'Pendiente',
                ];
            }

            $sheets[] = ['name' => 'Resumen tiempo diario', 'rows' => $tiempoRows];
        }

        $path = $this->buildXlsx($sheets);

        return response()->download($path, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function valorExport(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'N/A';
    }

    public function exportReporteSemanal(Request $request): BinaryFileResponse
    {
        $inicio = Carbon::parse(
            $this->dateInput($request->get('fecha_desde'))
                ?? $this->dateInput($request->get('fecha'))
                ?? now('America/Tegucigalpa')->toDateString(),
            'America/Tegucigalpa'
        )->startOfWeek(1);
        $fin = $inicio->copy()->addDays(6);
        $areas = $this->reporteSemanalAreas();
        $empleados = Empleado::query()
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereRaw('LOWER(area) LIKE ?', ['%empaque%tarea%permanente%'])
                    ->orWhereRaw('LOWER(area) LIKE ?', ['%empaque%brocha%permanente%']);
            })
            ->orderBy('nombre')
            ->orderBy('codigo')
            ->get();
        $codigos = $empleados->pluck('codigo')->filter()->values();
        $registros = $codigos->isEmpty()
            ? collect()
            : VinetaRegistro::query()
                ->whereIn('empleado_codigo', $codigos)
                ->whereBetween('fecha_registro', [$inicio->toDateString(), $fin->toDateString()])
                ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
                ->orderBy('empleado_nombre')
                ->orderBy('fecha_registro')
                ->get();
        $horasOrdinarias = Schema::hasTable('empleado_horas_ordinarias') && $codigos->isNotEmpty()
            ? EmpleadoHoraOrdinaria::query()
                ->whereIn('empleado_codigo', $codigos)
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->get()
            : collect();
        $registrosPorEmpleado = $registros->groupBy('empleado_codigo');
        $horasPorEmpleado = $horasOrdinarias->groupBy('empleado_codigo');
        $empleadosLlenado = $empleados
            ->filter(fn (Empleado $empleado) => $this->empleadoEsLlenadoReporte($empleado))
            ->sortBy(fn (Empleado $empleado) => mb_strtolower((string) $empleado->nombre) . '|' . $empleado->codigo)
            ->values();
        $sheets = [];

        foreach ($areas as $areaKey => $areaTitulo) {
            $empleadosArea = $empleados
                ->filter(fn (Empleado $empleado) => $this->empleadoPerteneceAreaReporte($empleado, $areaKey)
                    && ! $this->empleadoEsLlenadoReporte($empleado))
                ->sortBy(fn (Empleado $empleado) => mb_strtolower((string) $empleado->nombre) . '|' . $empleado->codigo)
                ->values();
            $rows = $this->reporteSemanalAreaRows(
                $areaTitulo,
                $inicio,
                $fin,
                $empleadosArea,
                $registrosPorEmpleado,
                $horasPorEmpleado
            );

            $sheets[] = [
                'name' => $areaKey === 'tarea' ? 'Empaque tarea' : 'Empaque brocha',
                'rows' => $rows,
                'format' => 'weekly_report',
                'paper_size' => 'letter',
                'employees_per_page' => 3,
            ];
        }

        if ($empleadosLlenado->isNotEmpty()) {
            $sheets[] = [
                'name' => 'Llenado',
                'rows' => $this->reporteSemanalLlenadoRows(
                    $inicio,
                    $fin,
                    $empleadosLlenado,
                    $registrosPorEmpleado,
                    $horasPorEmpleado
                ),
                'format' => 'weekly_report',
                'paper_size' => 'legal',
                'employees_per_page' => 1,
            ];
        }

        $path = $this->buildXlsx($sheets);
        $fileName = 'reporte_semanal_vinetas_'
            . $inicio->format('Ymd')
            . '_'
            . $fin->format('Ymd')
            . '_'
            . now('America/Tegucigalpa')->format('His')
            . '.xlsx';

        return response()->download($path, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function reporteSemanalAreas(): array
    {
        return [
            'tarea' => 'Empaque a la Tarea Permanente',
            'brocha' => 'Empaque de Brocha Permanente',
        ];
    }

    private function reporteSemanalAreaRows(
        string $areaTitulo,
        Carbon $inicio,
        Carbon $fin,
        $empleados,
        $registrosPorEmpleado,
        $horasPorEmpleado
    ): array {
        $rows = [
            ['Tabacos de Oriente "El Paraiso"'],
            ['Periodo del ' . $this->fechaLarga($inicio) . ' al ' . $this->fechaLarga($fin)],
            [$areaTitulo],
            [],
        ];

        if ($empleados->isEmpty()) {
            $rows[] = ['No hay empleados activos en esta area.'];

            return $rows;
        }

        foreach ($empleados as $empleado) {
            $registrosEmpleado = $registrosPorEmpleado->get($empleado->codigo, collect());
            $horasEmpleado = $horasPorEmpleado->get($empleado->codigo, collect());
            $tipo = $this->tipoReporteEmpleado($empleado, $registrosEmpleado);
            $columnasActividad = $this->columnasActividadReporte($tipo);
            $header = array_merge([
                'Dia',
                'Incap.',
                'S.S.',
                'Lact.',
                'P',
                'HD',
                'HN',
                'HO',
            ], $columnasActividad, [
                'Total',
                'Otros Ingr.',
            ]);

            $rows[] = [
                'COD: ' . $empleado->codigo,
                $empleado->nombre,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Puesto:',
                $this->puestoReporteEmpleado($empleado, $tipo),
            ];
            $rows[] = $header;

            $totalesActividad = array_fill_keys($columnasActividad, 0);
            $totalGeneral = 0;
            $totalHo = 0.0;

            for ($date = $inicio->copy(); $date->lte($fin); $date->addDay()) {
                $fecha = $date->toDateString();
                $actividadDia = array_fill_keys($columnasActividad, 0);
                $registrosDia = $registrosEmpleado->filter(
                    fn (VinetaRegistro $registro) => $registro->fecha_registro?->format('Y-m-d') === $fecha
                );

                foreach ($registrosDia as $registro) {
                    $columna = $this->columnaActividadRegistroReporte($registro, $tipo);

                    if ($columna !== null) {
                        $actividadDia[$columna] += (int) $registro->total_actividades;
                    }
                }

                $totalDia = (int) array_sum($actividadDia);
                $ho = $this->horasOrdinariasDia($horasEmpleado, $fecha);
                $totalHo += $ho;
                $totalGeneral += $totalDia;

                foreach ($columnasActividad as $columna) {
                    $totalesActividad[$columna] += $actividadDia[$columna];
                }

                $actividadValores = [];

                foreach ($columnasActividad as $columna) {
                    $actividadValores[] = $this->numeroReporte($actividadDia[$columna] ?? 0);
                }

                $rows[] = array_merge([
                    $this->diaSemana($date),
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $this->numeroReporte($ho),
                ], $actividadValores, [
                    $this->totalDiaReporte($totalDia, $ho),
                    '',
                ]);
            }

            $totalActividadValores = [];

            foreach ($columnasActividad as $columna) {
                $totalActividadValores[] = $this->numeroReporte($totalesActividad[$columna] ?? 0);
            }

            $rows[] = array_merge([
                'Total Semanal',
                '',
                '',
                '',
                '',
                '',
                '',
                $this->numeroReporte($totalHo),
            ], $totalActividadValores, [
                $this->numeroReporte($totalGeneral),
                '',
            ]);
            $rows[] = [];
        }

        return $rows;
    }

    private function reporteSemanalLlenadoRows(
        Carbon $inicio,
        Carbon $fin,
        $empleados,
        $registrosPorEmpleado,
        $horasPorEmpleado
    ): array {
        $columnasActividad = $this->columnasActividadLlenadoReporte();
        $precios = $this->preciosActividadLlenadoReporte($columnasActividad);
        $header = array_merge([
            'Dia',
            'Inc.',
            'Per',
            'S.S.',
            'Lact.',
            'HD',
            'HO',
        ], array_values($columnasActividad), [
            'Total',
            'Precios',
            'Actividades',
            'Otros Ing.',
        ]);
        $totalIndex = 7 + count($columnasActividad);
        $precioIndex = $totalIndex + 1;
        $actividadPrecioIndex = $totalIndex + 2;
        $rows = [
            ['Tabacos de Oriente "El Paraiso"'],
            ['Periodo del ' . $this->fechaLarga($inicio) . ' al ' . $this->fechaLarga($fin)],
            ['Llenado de Cajas y Paquetes / Sellado de Bolsas'],
            [],
        ];

        foreach ($empleados as $empleado) {
            $registrosEmpleado = $registrosPorEmpleado->get($empleado->codigo, collect());
            $horasEmpleado = $horasPorEmpleado->get($empleado->codigo, collect());
            $empleadoRow = array_fill(0, count($header), '');
            $empleadoRow[0] = 'COD: ' . $empleado->codigo;
            $empleadoRow[1] = $empleado->nombre;
            $empleadoRow[max(0, $totalIndex - 4)] = 'Puesto:';
            $empleadoRow[max(0, $totalIndex - 3)] = $this->puestoReporteEmpleado($empleado, 'llenado');
            $empleadoRow[$totalIndex] = $empleado->area ?: 'Empaque a la Tarea Permanente 2';

            $rows[] = $empleadoRow;
            $rows[] = $header;

            $totalesActividad = array_fill_keys(array_keys($columnasActividad), 0);
            $totalGeneral = 0;
            $totalHo = 0.0;
            $dias = [];

            for ($date = $inicio->copy(); $date->lte($fin) && count($dias) < 6; $date->addDay()) {
                $dias[] = [$this->diaSemanaCorto($date), $date->toDateString()];
            }

            $lineas = max(count($dias), count($precios));

            for ($index = 0; $index < $lineas; $index++) {
                $row = array_fill(0, count($header), '');

                if (isset($dias[$index])) {
                    [$dia, $fecha] = $dias[$index];
                    $actividadDia = array_fill_keys(array_keys($columnasActividad), 0);
                    $registrosDia = $registrosEmpleado->filter(
                        fn (VinetaRegistro $registro) => $registro->fecha_registro?->format('Y-m-d') === $fecha
                    );

                    foreach ($registrosDia as $registro) {
                        $columna = $this->columnaActividadLlenadoRegistroReporte($registro);

                        if ($columna !== null && array_key_exists($columna, $actividadDia)) {
                            $actividadDia[$columna] += (int) $registro->total_actividades;
                        }
                    }

                    $ho = $this->horasOrdinariasDia($horasEmpleado, $fecha);
                    $totalDia = (int) array_sum($actividadDia);
                    $row[0] = $dia;
                    $row[6] = $this->numeroReporte($ho);
                    $totalHo += $ho;
                    $totalGeneral += $totalDia;

                    $columnIndex = 7;

                    foreach ($columnasActividad as $key => $label) {
                        $valor = $actividadDia[$key] ?? 0;
                        $row[$columnIndex++] = $this->numeroReporte($valor);
                        $totalesActividad[$key] += $valor;
                    }

                    $row[$totalIndex] = $this->totalDiaReporte($totalDia, $ho);
                }

                if (isset($precios[$index])) {
                    $row[$precioIndex] = $precios[$index]['precio'];
                    $row[$actividadPrecioIndex] = $precios[$index]['actividad'];
                }

                $rows[] = $row;
            }

            $totalRow = array_fill(0, count($header), '');
            $totalRow[0] = 'Total Semanal';
            $totalRow[6] = $this->numeroReporte($totalHo);
            $columnIndex = 7;

            foreach ($columnasActividad as $key => $label) {
                $totalRow[$columnIndex++] = $this->numeroReporte($totalesActividad[$key] ?? 0);
            }

            $totalRow[$totalIndex] = $this->numeroReporte($totalGeneral);
            $totalRow[$actividadPrecioIndex] = 'Totales';
            $rows[] = $totalRow;
            $rows[] = [];
        }

        return $rows;
    }

    private function empleadoPerteneceAreaReporte(Empleado $empleado, string $areaKey): bool
    {
        $area = $this->normalizarTextoReporte($empleado->area);

        return match ($areaKey) {
            'tarea' => str_contains($area, 'empaque')
                && str_contains($area, 'tarea')
                && str_contains($area, 'permanente'),
            'brocha' => str_contains($area, 'empaque')
                && str_contains($area, 'brocha')
                && str_contains($area, 'permanente'),
            default => false,
        };
    }

    private function empleadoEsLlenadoReporte(Empleado $empleado): bool
    {
        $texto = $this->normalizarTextoReporte(($empleado->cargo ?? '') . ' ' . ($empleado->area ?? ''));

        return str_contains($texto, 'llenad')
            || (str_contains($texto, 'sell') && str_contains($texto, 'bolsa'));
    }

    private function tipoReporteEmpleado(Empleado $empleado, $registros): string
    {
        $texto = $this->normalizarTextoReporte(($empleado->cargo ?? '') . ' ' . ($empleado->area ?? ''));

        if (str_contains($texto, 'rezag') || str_contains($texto, 'rezad')) {
            return 'rezago';
        }

        if (
            str_contains($texto, 'anill')
            || str_contains($texto, 'celof')
            || str_contains($texto, 'sell')
            || (str_contains($texto, 'brocha') && str_contains($texto, 'limp'))
        ) {
            return 'anillado';
        }

        if (str_contains($texto, 'limp')) {
            return 'limpia';
        }

        $actividadTexto = $this->normalizarTextoReporte(
            $registros->pluck('actividad_nombre')->filter()->implode(' ')
        );

        if (str_contains($actividadTexto, 'rezag') || str_contains($actividadTexto, 'rezad')) {
            return 'rezago';
        }

        if (
            str_contains($actividadTexto, 'anill')
            || str_contains($actividadTexto, 'celof')
            || str_contains($actividadTexto, 'sell')
            || (str_contains($actividadTexto, 'brocha') && str_contains($actividadTexto, 'limp'))
        ) {
            return 'anillado';
        }

        if (str_contains($actividadTexto, 'limp')) {
            return 'limpia';
        }

        return str_contains($texto, 'brocha') ? 'anillado' : 'limpia';
    }

    private function puestoReporteEmpleado(Empleado $empleado, string $tipo): string
    {
        if ($empleado->cargo) {
            return $empleado->cargo;
        }

        return match ($tipo) {
            'rezago' => 'Rezago Puros',
            'anillado' => 'Anilladora / Celofanadora',
            'limpia' => 'Limpia Puros',
            'llenado' => 'Llenado de Cajas y Paquetes',
            default => 'Empaque',
        };
    }

    private function columnasActividadReporte(string $tipo): array
    {
        return match ($tipo) {
            'rezago' => ['Bols/1/Pac', 'Llen/Disp/Br', '6 y 7/PH', 'Rez/Puros'],
            'anillado' => ['Cel/Brocha', 'Rasurado', 'Limp/Brocha', 'Ani/Cel/Sel'],
            'limpia' => ['2/PHM', 'Sampler de 5', '6/7 PPHM', 'Limp/Puros'],
            default => ['2/PHM', 'Sampler de 5', '6/7 PPHM', 'Limp/Puros'],
        };
    }

    private function columnasActividadLlenadoReporte(): array
    {
        return [
            '1_phm' => '1/PHM',
            '2_phm' => '2/PHM',
            '3_phm' => '3/PHM',
            '3_pp' => '3/PP',
            '4_shm' => '4/SHM',
            '5_shm' => '5/SHM',
            '7_pphm' => '6,7/PH',
            'ani_cel_sel' => 'Ani/Cel/Sel',
            'bols_1_pac' => 'Bols/1/Pac',
            'llen_100' => 'Llen/100',
            'llen_10_40' => 'Llen/10-40',
            'llen_50' => 'Llen/50',
            'petaca_4' => 'Petaca 4',
            'sampler_5' => 'Sampler de 5',
            'sampler_10' => 'Samp/Cost/10',
            'llen_bolsa_3p' => 'Llen/Bols/3p',
            'llen_bolsa_5p' => 'Llen/Bols/5p',
            'sell_bolsa_3p' => 'Sell/Bols/3p',
            'sell_bolsa_5p' => 'Sell/Bols/5p',
            'llen_disp_24p' => 'Llen/Disp/24p',
            'llen_disp_30p' => 'Llen/Disp/30p',
        ];
    }

    private function preciosActividadLlenadoReporte(array $columnasActividad): array
    {
        $precios = array_fill_keys(array_keys($columnasActividad), null);

        DB::table('actividad_producto')
            ->join('actividades', 'actividades.id', '=', 'actividad_producto.actividad_id')
            ->where(function ($query) {
                $query->whereRaw('LOWER(actividades.nombre) LIKE ?', ['%llenad%'])
                    ->orWhereRaw('LOWER(actividades.nombre) LIKE ?', ['%sell%'])
                    ->orWhereRaw('LOWER(actividades.nombre) LIKE ?', ['%paquete%'])
                    ->orWhereRaw('LOWER(actividades.nombre) LIKE ?', ['%sampler%'])
                    ->orWhereRaw('LOWER(actividades.nombre) LIKE ?', ['%petaca%']);
            })
            ->whereNotNull('actividad_producto.precio_mo')
            ->orderBy('actividades.nombre')
            ->get(['actividades.nombre', 'actividad_producto.precio_mo'])
            ->each(function ($actividad) use (&$precios) {
                $key = $this->columnaActividadLlenadoTextoReporte((string) $actividad->nombre);
                $precio = (float) $actividad->precio_mo;

                if ($key !== null && array_key_exists($key, $precios) && $precio > 0 && $precios[$key] === null) {
                    $precios[$key] = $precio;
                }
            });

        foreach ($this->fuentesPrecioLlenadoReporte() as $key => $nombres) {
            if (! array_key_exists($key, $precios)) {
                continue;
            }

            $precio = $this->precioActividadPorNombres($nombres);

            if ($precio !== null) {
                $precios[$key] = $precio;
            }
        }

        $precios['sell_bolsa_3p'] = 0.0179583;
        $precios['sell_bolsa_5p'] = 0.0107750;

        $rows = [
            ['precio' => 'L53.88', 'actividad' => 'H.O'],
            ['precio' => 'L431.00', 'actividad' => 'Al Dia'],
        ];

        foreach ($columnasActividad as $key => $label) {
            $rows[] = [
                'precio' => $precios[$key] === null ? '' : $this->monedaReporte($precios[$key]),
                'actividad' => $label,
            ];
        }

        return $rows;
    }

    private function fuentesPrecioLlenadoReporte(): array
    {
        return [
            'ani_cel_sel' => ['Anillado', 'Celofanado', 'Pegado de Sellos en Celofan'],
            'petaca_4' => ['Petaca 4 Puros'],
            'sampler_5' => ['Sampler de 5'],
            'sampler_10' => ['Sampler COTSCO 10 Puros'],
            'llen_bolsa_3p' => ['Llenado de Bolsas 3 Puros (Kretek)'],
            'llen_bolsa_5p' => ['Llenado de Bolsas 5 Puros (Kretek)'],
            'sell_bolsa_3p' => ['Sellado de bolsas (Altadis)'],
            'sell_bolsa_5p' => ['Sellado de bolsas (Altadis)'],
            'llen_disp_24p' => ['Llenado de Display 24 Puros(Kretek)'],
            'llen_disp_30p' => ['Llenado de Display 30 Puros(Kretek)'],
        ];
    }

    private function precioActividadPorNombres(array $nombres): ?float
    {
        $precio = DB::table('actividad_producto')
            ->join('actividades', 'actividades.id', '=', 'actividad_producto.actividad_id')
            ->whereIn('actividades.nombre', $nombres)
            ->whereNotNull('actividad_producto.precio_mo')
            ->orderBy('actividades.nombre')
            ->value('actividad_producto.precio_mo');

        return $precio === null ? null : (float) $precio;
    }

    private function monedaReporte(float $value): string
    {
        return 'L' . number_format($value, 6, '.', '');
    }

    private function columnaActividadLlenadoRegistroReporte(VinetaRegistro $registro): ?string
    {
        return $this->columnaActividadLlenadoTextoReporte($this->textoActividadReporte($registro));
    }

    private function columnaActividadLlenadoTextoReporte(string $texto): ?string
    {
        $texto = $this->normalizarTextoReporte($texto);

        if ($texto === '') {
            return null;
        }

        if (str_contains($texto, '1/phm') || str_contains($texto, '1 phm')) {
            return '1_phm';
        }

        if (str_contains($texto, '2/phm') || str_contains($texto, '2 phm')) {
            return '2_phm';
        }

        if (str_contains($texto, '3/phm') || str_contains($texto, '3 phm')) {
            return '3_phm';
        }

        if (str_contains($texto, '3/pp') || str_contains($texto, '3 pp')) {
            return '3_pp';
        }

        if (str_contains($texto, '4/shm') || str_contains($texto, '4 shm')) {
            return '4_shm';
        }

        if (str_contains($texto, '5/shm') || str_contains($texto, '5 shm')) {
            return '5_shm';
        }

        if ($this->esActividadSeisSieteReporte($texto)) {
            return '7_pphm';
        }

        if (
            str_contains($texto, 'anill')
            || str_contains($texto, 'anil')
            || str_contains($texto, 'celof')
            || str_contains($texto, 'sello')
        ) {
            return 'ani_cel_sel';
        }

        if (str_contains($texto, 'bolsa') && (str_contains($texto, '1 pack') || str_contains($texto, '1/p') || str_contains($texto, '1 pac'))) {
            return 'bols_1_pac';
        }

        if (str_contains($texto, 'caja') && str_contains($texto, '100')) {
            return 'llen_100';
        }

        if (str_contains($texto, '10-40') || str_contains($texto, '10/40')) {
            return 'llen_10_40';
        }

        if (str_contains($texto, 'caja') && str_contains($texto, '50')) {
            return 'llen_50';
        }

        if (str_contains($texto, 'petaca')) {
            return 'petaca_4';
        }

        if (str_contains($texto, 'sampler') && str_contains($texto, '5')) {
            return 'sampler_5';
        }

        if (str_contains($texto, 'sampler') && str_contains($texto, '10')) {
            return 'sampler_10';
        }

        if (str_contains($texto, 'sell') && str_contains($texto, 'bolsa') && str_contains($texto, '5')) {
            return 'sell_bolsa_5p';
        }

        if (str_contains($texto, 'sell') && str_contains($texto, 'bolsa')) {
            return 'sell_bolsa_3p';
        }

        if (str_contains($texto, 'bolsa') && (str_contains($texto, '3 puro') || str_contains($texto, '3 pack') || str_contains($texto, '3 p'))) {
            return 'llen_bolsa_3p';
        }

        if (str_contains($texto, 'bolsa') && (str_contains($texto, '5 puro') || str_contains($texto, '5 pack') || str_contains($texto, '5 p') || str_contains($texto, 'bolsa/5'))) {
            return 'llen_bolsa_5p';
        }

        if (str_contains($texto, 'display') && str_contains($texto, '24')) {
            return 'llen_disp_24p';
        }

        if (str_contains($texto, 'display') && str_contains($texto, '30')) {
            return 'llen_disp_30p';
        }

        return null;
    }

    private function columnaActividadReporte(?string $actividad, string $tipo): ?string
    {
        $texto = $this->normalizarTextoReporte($actividad);

        if ($texto === '') {
            return null;
        }

        return match ($tipo) {
            'rezago' => $this->columnaActividadRezagoReporte($texto),
            'anillado' => $this->columnaActividadAnilladoReporte($texto),
            'limpia' => $this->columnaActividadLimpiaReporte($texto),
            default => $this->columnaActividadLimpiaReporte($texto),
        };
    }

    private function columnaActividadRegistroReporte(VinetaRegistro $registro, string $tipo): ?string
    {
        return $this->columnaActividadReporte($this->textoActividadReporte($registro), $tipo);
    }

    private function columnaActividadRezagoReporte(string $texto): ?string
    {
        if (str_contains($texto, 'rezag') || str_contains($texto, 'rezad') || str_contains($texto, 'resag')) {
            return 'Rez/Puros';
        }

        if (str_contains($texto, 'bolsa') && (str_contains($texto, '1 pack') || str_contains($texto, '1/p') || str_contains($texto, '1 pac'))) {
            return 'Bols/1/Pac';
        }

        if (
            str_contains($texto, 'display')
            || (str_contains($texto, 'brocha') && (
                str_contains($texto, 'llenad')
                || str_contains($texto, 'bolsa')
                || str_contains($texto, 'paquete')
                || str_contains($texto, 'empaque')
            ))
        ) {
            return 'Llen/Disp/Br';
        }

        if ($this->esActividadSeisSieteReporte($texto)) {
            return '6 y 7/PH';
        }

        return null;
    }

    private function columnaActividadAnilladoReporte(string $texto): ?string
    {
        if (str_contains($texto, 'celof') && str_contains($texto, 'brocha')) {
            return 'Cel/Brocha';
        }

        if (str_contains($texto, 'rasur')) {
            return 'Rasurado';
        }

        if ((str_contains($texto, 'limp') || str_contains($texto, 'gomad')) && str_contains($texto, 'brocha')) {
            return 'Limp/Brocha';
        }

        if (
            str_contains($texto, 'anill')
            || str_contains($texto, 'anil')
            || str_contains($texto, 'celof')
            || str_contains($texto, 'sello')
            || str_contains($texto, 'sell')
        ) {
            return 'Ani/Cel/Sel';
        }

        return null;
    }

    private function columnaActividadLimpiaReporte(string $texto): ?string
    {
        if (str_contains($texto, 'sampler') && str_contains($texto, '5')) {
            return 'Sampler de 5';
        }

        if (str_contains($texto, '2/phm') || str_contains($texto, '2 phm')) {
            return '2/PHM';
        }

        if ($this->esActividadSeisSieteReporte($texto)) {
            return '6/7 PPHM';
        }

        if (str_contains($texto, 'limp') && str_contains($texto, 'puro')) {
            return 'Limp/Puros';
        }

        return null;
    }

    private function esActividadSeisSieteReporte(string $texto): bool
    {
        return str_contains($texto, '6/7')
            || str_contains($texto, '6 y 7')
            || str_contains($texto, '6-7')
            || str_contains($texto, '7/ph')
            || str_contains($texto, '7/pph')
            || str_contains($texto, '7 pph')
            || preg_match('/(^|\D)6(\D|$)/', $texto) === 1;
    }

    private function textoActividadReporte(VinetaRegistro $registro): string
    {
        return implode(' ', array_filter([
            $registro->actividad_nombre,
            $registro->actividad_codigo,
            $registro->actividad_tipo_empaque,
        ]));
    }

    private function horasOrdinariasDia($horasEmpleado, string $fecha): float
    {
        $minutos = (int) $horasEmpleado
            ->filter(fn (EmpleadoHoraOrdinaria $hora) => $hora->fecha?->format('Y-m-d') === $fecha)
            ->sum('minutos');

        return round($minutos / 60, 2);
    }

    private function diaSemana(Carbon $date): string
    {
        return match ((int) $date->dayOfWeekIso) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            default => 'Domingo',
        };
    }

    private function diaSemanaCorto(Carbon $date): string
    {
        return match ((int) $date->dayOfWeekIso) {
            1 => 'LU',
            2 => 'MA',
            3 => 'MI',
            4 => 'JU',
            5 => 'VI',
            default => 'SA',
        };
    }

    private function fechaLarga(Carbon $date): string
    {
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return $date->day . ' de ' . $meses[(int) $date->month] . ' de ' . $date->year;
    }

    private function numeroReporte(float|int $value): string|int
    {
        if ((float) $value === 0.0) {
            return '';
        }

        if (floor((float) $value) === (float) $value) {
            return (int) $value;
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private function totalDiaReporte(int $totalDia, float $ho): string|int
    {
        if ($totalDia === 0 && abs($ho - 8.0) < 0.01) {
            return 'Al dia';
        }

        return $this->numeroReporte($totalDia);
    }

    private function normalizarTextoReporte(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $buscar = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'];
        $reemplazar = ['a', 'e', 'i', 'o', 'u', 'u', 'n'];

        return str_replace($buscar, $reemplazar, $value);
    }

    private function buildXlsx(array $sheets): string
    {
        $directory = storage_path('framework/cache');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = tempnam($directory, 'vinetas_export_');

        if ($path === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal de exportacion.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());

        foreach ($sheets as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet' . ($index + 1) . '.xml',
                $this->xlsxWorksheetXml(
                    $sheet['rows'],
                    $sheet['format'] ?? null,
                    $sheet['paper_size'] ?? null,
                    $sheet['employees_per_page'] ?? null
                )
            );
        }

        $zip->close();

        return $path;
    }

    private function xlsxWorksheetXml(array $rows, ?string $format = null, ?string $paperSize = null, ?int $employeesPerPage = null): string
    {
        $columnCount = max(1, collect($rows)->map(fn ($row) => count($row))->max() ?? 1);
        $rowCount = max(1, count($rows));
        $lastCell = $this->xlsxColumnName($columnCount) . $rowCount;
        $widths = $this->xlsxColumnWidths($rows, $columnCount, $format, $paperSize);
        $colsXml = '';
        $sheetPrXml = $format === 'weekly_report'
            ? '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            : '';
        $printXml = $format === 'weekly_report'
            ? $this->xlsxPrintSettingsXml($paperSize)
            : '';
        $rowBreaksXml = $format === 'weekly_report'
            ? $this->xlsxRowBreaksXml($rows, $employeesPerPage ?? 1)
            : '';

        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $colsXml .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
        }

        $rowsXml = '';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $rowHeight = $format === 'weekly_report'
                && $paperSize === 'legal'
                && (string) ($row[0] ?? '') === 'Dia'
                    ? ' ht="24" customHeight="1"'
                    : '';
            $rowsXml .= '<row r="' . $rowNumber . '"' . $rowHeight . '>';
            $rowStyle = $this->xlsxRowStyle($row, $rowIndex, $format, $paperSize);

            for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
                $value = $row[$columnIndex] ?? '';
                $cell = $this->xlsxColumnName($columnIndex + 1) . $rowNumber;
                $styleId = $rowStyle;

                if ($format === 'weekly_report' && $this->xlsxEsFilaEmpleadoReporte($row) && $columnIndex >= 11) {
                    $styleId = 5;
                }

                if ($format === 'weekly_report' && (string) $value === 'Al dia') {
                    $styleId = $paperSize === 'legal' ? 10 : 7;
                }

                $style = $styleId === null ? '' : ' s="' . $styleId . '"';

                if (is_int($value) || is_float($value)) {
                    $rowsXml .= '<c r="' . $cell . '"' . $style . '><v>' . $value . '</v></c>';
                    continue;
                }

                $rowsXml .= '<c r="' . $cell . '" t="inlineStr"' . $style . '><is><t>'
                    . $this->xml((string) $value)
                    . '</t></is></c>';
            }

            $rowsXml .= '</row>';
        }

        $mergeXml = '';

        if ($format === 'weekly_report') {
            $merges = [
                'A1:' . $this->xlsxColumnName($columnCount) . '1',
                'A2:' . $this->xlsxColumnName($columnCount) . '2',
                'A3:' . $this->xlsxColumnName($columnCount) . '3',
            ];

            foreach ($rows as $rowIndex => $row) {
                if ($this->xlsxEsFilaEmpleadoReporte($row)) {
                    $rowNumber = $rowIndex + 1;
                    $merges[] = 'B' . $rowNumber . ':K' . $rowNumber;

                    $puestoIndex = array_search('Puesto:', $row, true);

                    if ($puestoIndex !== false && isset($row[$puestoIndex + 1])) {
                        $cargoIndex = $puestoIndex + 1;
                        $cargoEndIndex = min($cargoIndex + 2, $columnCount - 1);

                        if ($cargoEndIndex > $cargoIndex) {
                            $merges[] = $this->xlsxColumnName($cargoIndex + 1) . $rowNumber
                                . ':'
                                . $this->xlsxColumnName($cargoEndIndex + 1) . $rowNumber;
                        }
                    }
                }
            }

            $mergeXml = '<mergeCells count="' . count($merges) . '">';

            foreach ($merges as $merge) {
                $mergeXml .= '<mergeCell ref="' . $merge . '"/>';
            }

            $mergeXml .= '</mergeCells>';
        }

        $autoFilter = $format === 'weekly_report' ? '' : '<autoFilter ref="A1:' . $lastCell . '"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . $sheetPrXml
            . '<dimension ref="A1:' . $lastCell . '"/>'
            . '<cols>' . $colsXml . '</cols>'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . $mergeXml
            . $autoFilter
            . $printXml
            . $rowBreaksXml
            . '</worksheet>';
    }

    private function xlsxPrintSettingsXml(?string $paperSize): string
    {
        $paperSizeId = $paperSize === 'legal' ? 5 : 1;
        $margins = $paperSize === 'legal'
            ? '<pageMargins left="0.1" right="0.1" top="0.25" bottom="0.25" header="0.1" footer="0.1"/>'
            : '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>';

        return '<printOptions horizontalCentered="1"/>'
            . $margins
            . '<pageSetup paperSize="' . $paperSizeId . '" orientation="landscape" fitToWidth="1" fitToHeight="0"/>';
    }

    private function xlsxRowBreaksXml(array $rows, int $employeesPerPage): string
    {
        $breaks = [];
        $employeeRows = [];

        foreach ($rows as $rowIndex => $row) {
            if ($this->xlsxEsFilaEmpleadoReporte($row)) {
                $employeeRows[] = $rowIndex + 1;
            }
        }

        if ($employeesPerPage <= 0 || count($employeeRows) <= $employeesPerPage) {
            return '';
        }

        foreach ($employeeRows as $index => $employeeRow) {
            $position = $index + 1;

            if ($position % $employeesPerPage !== 0 || ! isset($employeeRows[$index + 1])) {
                continue;
            }

            $breakRow = max($employeeRows[$index + 1] - 1, $employeeRow);
            $breaks[$breakRow] = true;
        }

        if ($breaks === []) {
            return '';
        }

        $breaksXml = '';

        foreach (array_keys($breaks) as $breakRow) {
            $breaksXml .= '<brk id="' . $breakRow . '" max="16383" man="1"/>';
        }

        return '<rowBreaks count="' . count($breaks) . '" manualBreakCount="' . count($breaks) . '">' . $breaksXml . '</rowBreaks>';
    }

    private function xlsxRowStyle(array $row, int $rowIndex, ?string $format, ?string $paperSize = null): ?int
    {
        if ($format !== 'weekly_report') {
            return $rowIndex === 0 ? 1 : null;
        }

        $first = (string) ($row[0] ?? '');

        if ($rowIndex <= 2) {
            return $rowIndex === 0 ? 2 : 6;
        }

        if ($first === 'Dia' || $first === 'Total Semanal') {
            return $paperSize === 'legal' ? 9 : 4;
        }

        if (in_array($first, ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo', 'LU', 'MA', 'MI', 'JU', 'VI', 'SA'], true)) {
            return $paperSize === 'legal' ? 8 : 3;
        }

        if ($first === '' && count($row) > 20) {
            $precio = (string) ($row[count($row) - 3] ?? '');
            $actividad = (string) ($row[count($row) - 2] ?? '');

            if ($precio !== '' || $actividad !== '') {
                return $paperSize === 'legal' ? 8 : 3;
            }
        }

        if ($this->xlsxEsFilaEmpleadoReporte($row)) {
            return 1;
        }

        return null;
    }

    private function xlsxEsFilaEmpleadoReporte(array $row): bool
    {
        return str_starts_with((string) ($row[0] ?? ''), 'COD: ');
    }

    private function xlsxColumnWidths(array $rows, int $columnCount, ?string $format = null, ?string $paperSize = null): array
    {
        if ($format === 'weekly_report') {
            if ($paperSize === 'legal') {
                $base = [9, 5.4, 5.4, 5.4, 5.4, 5.4, 5.4];

                for ($index = 7; $index < $columnCount; $index++) {
                    $base[$index] = match (true) {
                        $index >= $columnCount - 4 => 8.8,
                        default => 7.2,
                    };
                }

                return $base;
            }

            $base = [13, 9, 8, 8, 7, 7, 7, 7, 12, 13, 12, 12, 11, 13];

            return array_pad(array_slice($base, 0, $columnCount), $columnCount, 10);
        }

        $widths = array_fill(0, $columnCount, 10);

        foreach ($rows as $row) {
            for ($index = 0; $index < $columnCount; $index++) {
                $length = mb_strlen((string) ($row[$index] ?? '')) + 2;
                $widths[$index] = max($widths[$index], min($length, 60));
            }
        }

        return $widths;
    }

    private function xlsxWorkbookXml(array $sheets): string
    {
        $sheetsXml = '';
        $definedNamesXml = '';

        foreach ($sheets as $index => $sheet) {
            $sheetId = $index + 1;
            $name = $this->xml($this->xlsxSheetName($sheet['name']));
            $sheetsXml .= '<sheet name="' . $name . '" sheetId="' . $sheetId . '" r:id="rId' . $sheetId . '"/>';

            if (($sheet['format'] ?? null) === 'weekly_report') {
                $definedNamesXml .= '<definedName name="_xlnm.Print_Titles" localSheetId="' . $index . '">'
                    . $this->xml($this->xlsxQuotedSheetName($sheet['name']) . '!$1:$3')
                    . '</definedName>';
            }
        }

        $definedNamesXml = $definedNamesXml === '' ? '' : '<definedNames>' . $definedNamesXml . '</definedNames>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsXml . '</sheets>'
            . $definedNamesXml
            . '</workbook>';
    }

    private function xlsxQuotedSheetName(string $name): string
    {
        return "'" . str_replace("'", "''", $this->xlsxSheetName($name)) . "'";
    }

    private function xlsxWorkbookRelsXml(int $sheetCount): string
    {
        $rels = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $rels .= '<Relationship Id="rId' . $index . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $index . '.xml"/>';
        }

        $rels .= '<Relationship Id="rId' . ($sheetCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    private function xlsxContentTypesXml(int $sheetCount): string
    {
        $overrides = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $index . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function xlsxRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="6"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><name val="Georgia"/></font><font><b/><sz val="12"/><name val="Calibri"/></font><font><sz val="9"/><name val="Calibri"/></font><font><b/><sz val="9"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFF59D"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="11">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function xlsxSheetName(string $name): string
    {
        $name = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name) ?: 'Hoja');

        return mb_substr($name, 0, 31) ?: 'Hoja';
    }

    private function xlsxColumnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function perPage($value): int
    {
        $perPage = (int) $value;

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }

    private function filteredQuery(Request $request)
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $empleado = trim((string) $request->get('empleado', ''));
        $actividadGrupo = $this->actividadGrupo($request->get('actividad_grupo'));
        $fechaDesde = $this->dateInput($request->get('fecha_desde'));
        $fechaHasta = $this->dateInput($request->get('fecha_hasta'));
        $estado = $request->get('estado', 'activo');

        if (! in_array($estado, ['activo', 'anulado', 'todos'], true)) {
            $estado = 'activo';
        }

        return VinetaRegistro::query()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $like = '%' . $buscar . '%';
                $apiId = ltrim($buscar, '#');

                $query->where(function ($query) use ($like, $apiId) {
                    $query->where('codigo_vineta', 'like', $like)
                        ->orWhere('id_pendiente_empaque', 'like', $like)
                        ->orWhere('producto_codigo', 'like', $like)
                        ->orWhere('producto_item', 'like', $like)
                        ->orWhere('producto_nombre', 'like', $like)
                        ->orWhere('empleado_codigo', 'like', $like)
                        ->orWhere('empleado_nombre', 'like', $like)
                        ->orWhere('actividad_nombre', 'like', $like);

                    if (ctype_digit($apiId)) {
                        $query->orWhere('vineta_api_id', (int) $apiId);
                    }
                });
            })
            ->when($empleado !== '', function ($query) use ($empleado) {
                $like = '%' . $empleado . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('empleado_codigo', 'like', $like)
                        ->orWhere('empleado_nombre', 'like', $like);
                });
            })
            ->when($actividadGrupo !== '', fn ($query) => $this->applyActividadGrupo($query, $actividadGrupo))
            ->when($fechaDesde, fn ($query) => $query->whereDate('fecha_registro', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('fecha_registro', '<=', $fechaHasta))
            ->when($estado !== 'todos', fn ($query) => $query->where('estado', $estado));
    }

    private function filteredHorasOrdinariasQuery(Request $request)
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $empleado = trim((string) $request->get('empleado', ''));
        $actividadGrupo = $this->actividadGrupo($request->get('actividad_grupo'));
        $fechaDesde = $this->dateInput($request->get('fecha_desde'));
        $fechaHasta = $this->dateInput($request->get('fecha_hasta'));
        $estado = $request->get('estado', 'activo');

        if (! in_array($estado, ['activo', 'anulado', 'todos'], true)) {
            $estado = 'activo';
        }

        return EmpleadoHoraOrdinaria::query()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $like = '%' . $buscar . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('empleado_codigo', 'like', $like)
                        ->orWhere('empleado_nombre', 'like', $like)
                        ->orWhere('observacion', 'like', $like);
                });
            })
            ->when($empleado !== '', function ($query) use ($empleado) {
                $like = '%' . $empleado . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('empleado_codigo', 'like', $like)
                        ->orWhere('empleado_nombre', 'like', $like);
                });
            })
            ->when($actividadGrupo !== '', fn ($query) => $query->whereRaw('0 = 1'))
            ->when($fechaDesde, fn ($query) => $query->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('fecha', '<=', $fechaHasta))
            ->when($estado === 'anulado', fn ($query) => $query->whereRaw('0 = 1'));
    }

    private function ordenarRegistrosCombinados($registros, string $orden, string $direccion)
    {
        return collect($registros)->sort(function ($a, $b) use ($orden, $direccion) {
            $resultado = $this->compararValoresOrden($this->valorOrden($a, $orden), $this->valorOrden($b, $orden));

            if ($resultado === 0) {
                $resultado = $this->compararValoresOrden(
                    $this->valorOrden($a, 'fecha_registro'),
                    $this->valorOrden($b, 'fecha_registro')
                );
            }

            if ($resultado === 0) {
                $resultado = $this->compararValoresOrden($this->valorOrden($a, 'id'), $this->valorOrden($b, 'id'));
            }

            return $direccion === 'desc' ? -$resultado : $resultado;
        })->values();
    }

    private function compararValoresOrden($a, $b): int
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a <=> (float) $b;
        }

        return strnatcasecmp((string) $a, (string) $b);
    }

    private function valorOrden($registro, string $orden)
    {
        if ($orden === 'fecha_registro') {
            $fecha = $registro instanceof EmpleadoHoraOrdinaria
                ? $registro->fecha?->format('Y-m-d')
                : $registro->fecha_registro?->format('Y-m-d');
            $hora = $registro instanceof EmpleadoHoraOrdinaria
                ? $registro->created_at?->format('H:i:s')
                : (string) ($registro->hora_registro ?? '00:00:00');

            return ($fecha ?: '0000-00-00') . ' ' . ($hora ?: '00:00:00');
        }

        return match ($orden) {
            'codigo_vineta' => $registro instanceof EmpleadoHoraOrdinaria ? 'Hora ordinaria' : (string) ($registro->codigo_vineta ?? ''),
            'vineta_api_id' => $registro instanceof EmpleadoHoraOrdinaria ? 0 : (int) ($registro->vineta_api_id ?? 0),
            'empleado_nombre' => (string) ($registro->empleado_nombre ?? ''),
            'actividad_nombre' => (string) ($registro->actividad_nombre ?? ''),
            'precio_mo' => (float) ($registro->precio_mo ?? 0),
            'cantidad_puros' => (int) ($registro->cantidad_puros ?? 0),
            'cantidad_cajones' => (int) ($registro->cantidad_cajones ?? 0),
            'cantidad_actividades' => method_exists($registro, 'cantidadActividadesValor') ? $registro->cantidadActividadesValor() : 0,
            'minutos_trabajados' => (int) ($registro->minutos_trabajados ?? 0),
            'id' => (int) ($registro->id ?? 0),
            default => '',
        };
    }

    private function actividadGrupo($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, ['anillado', 'rezago', 'llenado'], true) ? $value : '';
    }

    private function applyActividadGrupo($query, string $grupo): void
    {
        $query->where(function ($query) use ($grupo) {
            match ($grupo) {
                'anillado' => $query->whereRaw('LOWER(actividad_nombre) LIKE ?', ['%anil%']),
                'rezago' => $query->whereRaw('LOWER(actividad_nombre) LIKE ?', ['%rezag%']),
                'llenado' => $query->whereRaw('LOWER(actividad_nombre) LIKE ?', ['%llenad%']),
                default => null,
            };
        });
    }

    private function totalActividades($query, bool $hasCantidadActividades): int
    {
        $columns = ['cantidad_puros', 'actividad_nombre'];

        if ($hasCantidadActividades) {
            $columns[] = 'cantidad_actividades';
        }

        return (int) (clone $query)
            ->get($columns)
            ->sum(fn (VinetaRegistro $registro) => $registro->total_actividades);
    }

    private function totalMonto($query): float
    {
        return (float) (clone $query)
            ->selectRaw('COALESCE(SUM(cantidad_puros * COALESCE(precio_mo, 0)), 0) as suma_monto')
            ->value('suma_monto');
    }

    private function totalMinutos($query, bool $hasMinutosTrabajados): int
    {
        if (! $hasMinutosTrabajados) {
            return 0;
        }

        return (int) (clone $query)->sum('minutos_trabajados');
    }

    private function seguimientoPorVineta($vinetaIds): array
    {
        $vinetaIds = collect($vinetaIds)
            ->filter()
            ->unique()
            ->values();

        if ($vinetaIds->isEmpty()) {
            return [
                'timelines' => [],
                'resumenes' => [],
            ];
        }

        $registros = VinetaRegistro::query()
            ->whereIn('vineta_id', $vinetaIds)
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get()
            ->groupBy('vineta_id');

        $timelines = [];
        $resumenes = [];

        foreach ($registros as $vinetaId => $timeline) {
            $ultimo = $timeline->last();
            $activos = $timeline->where('estado', VinetaRegistro::ESTADO_ACTIVO);

            $timelines[(string) $vinetaId] = $timeline->map(fn (VinetaRegistro $registro) => [
                'fecha' => $registro->fechaHoraRegistroTexto(),
                'actividad' => $registro->actividad_nombre,
                'empleado' => $registro->empleado_nombre,
                'empleado_codigo' => $registro->empleado_codigo,
                'puros' => $registro->cantidad_puros,
                'cantidad_actividades' => $registro->cantidadActividadesValor(),
                'total_actividades' => $registro->total_actividades,
                'minutos_trabajados' => $registro->minutos_trabajados,
                'tiempo_trabajado_texto' => $registro->tiempoTrabajadoTexto(),
                'precio_mo' => (float) ($registro->precio_mo ?? 0),
                'total_mo' => $registro->total_mo,
                'estado' => $registro->estado,
                'motivo_anulacion' => $registro->motivo_anulacion,
            ])->values()->all();

            $resumenes[(string) $vinetaId] = [
                'vineta' => $ultimo?->vineta_api_id ? 'ID ' . $ultimo->vineta_api_id : 'ID ' . $vinetaId,
                'producto' => $ultimo?->producto_nombre ?? 'Sin producto',
                'producto_codigo' => $ultimo?->producto_codigo ?? 'N/A',
                'producto_item' => $ultimo?->producto_item ?? 'N/A',
                'marca' => $ultimo?->marca ?? 'N/A',
                'orden' => $ultimo?->orden ?? $ultimo?->orden_del_sistema ?? 'N/A',
                'vineta_fecha' => $ultimo?->vineta_fecha?->format('d/m/Y') ?? 'N/A',
                'ultimo_movimiento' => $ultimo?->actividad_nombre ?? 'Sin movimientos',
                'ultimo_empleado' => $ultimo?->empleado_nombre ?? 'N/A',
                'ultima_fecha' => $ultimo?->fechaHoraRegistroTexto() ?? 'N/A',
                'movimientos' => $timeline->count(),
                'activos' => $activos->count(),
                'puros' => (int) ($ultimo?->cantidad_puros ?? 0),
                'total_actividades' => (int) $activos->sum(fn (VinetaRegistro $registro) => $registro->total_actividades),
                'minutos_trabajados' => (int) $activos->sum(fn (VinetaRegistro $registro) => (int) ($registro->minutos_trabajados ?? 0)),
            ];
        }

        return [
            'timelines' => $timelines,
            'resumenes' => $resumenes,
        ];
    }

    private function dateInput($value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeTime(string $value): string
    {
        $value = trim($value);

        if (substr_count($value, ':') === 1) {
            return $value . ':00';
        }

        return $value;
    }
}
