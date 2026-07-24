<?php

namespace App\Http\Controllers;

use App\Models\VinetaRegistro;
use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
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
        $rules = [
            'fecha_registro' => ['required', 'date_format:Y-m-d'],
            'hora_registro' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'cantidad_puros' => ['required', 'integer', 'min:1', 'max:1000000'],
            'empleado_codigo' => ['required', 'string', 'max:120'],
        ];

        if ($hasMinutosTrabajados) {
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
            $payload['minutos_trabajados'] = (int) $data['minutos_trabajados'];
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
            'Producto',
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
                $registro->producto_nombre,
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
                'Registro manual',
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
                $this->xlsxWorksheetXml($sheet['rows'])
            );
        }

        $zip->close();

        return $path;
    }

    private function xlsxWorksheetXml(array $rows): string
    {
        $columnCount = max(1, collect($rows)->map(fn ($row) => count($row))->max() ?? 1);
        $rowCount = max(1, count($rows));
        $lastCell = $this->xlsxColumnName($columnCount) . $rowCount;
        $widths = $this->xlsxColumnWidths($rows, $columnCount);
        $colsXml = '';

        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $colsXml .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
        }

        $rowsXml = '';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $rowsXml .= '<row r="' . $rowNumber . '">';

            for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
                $value = $row[$columnIndex] ?? '';
                $cell = $this->xlsxColumnName($columnIndex + 1) . $rowNumber;
                $style = $rowIndex === 0 ? ' s="1"' : '';

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

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:' . $lastCell . '"/>'
            . '<cols>' . $colsXml . '</cols>'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '<autoFilter ref="A1:' . $lastCell . '"/>'
            . '</worksheet>';
    }

    private function xlsxColumnWidths(array $rows, int $columnCount): array
    {
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

        foreach ($sheets as $index => $sheet) {
            $sheetId = $index + 1;
            $name = $this->xml($this->xlsxSheetName($sheet['name']));
            $sheetsXml .= '<sheet name="' . $name . '" sheetId="' . $sheetId . '" r:id="rId' . $sheetId . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsXml . '</sheets>'
            . '</workbook>';
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
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
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
        if ($hasCantidadActividades) {
            return (int) (clone $query)
                ->selectRaw('COALESCE(SUM(cantidad_puros * COALESCE(cantidad_actividades, 1)), 0) as suma_actividades')
                ->value('suma_actividades');
        }

        return (int) (clone $query)->sum('cantidad_puros');
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
