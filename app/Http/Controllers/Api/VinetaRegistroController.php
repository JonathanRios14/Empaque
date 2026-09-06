<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Empleado;
use App\Models\Producto;
use App\Models\TipoEmpaque;
use App\Models\Vineta;
use App\Models\VinetaPorOrden;
use App\Models\VinetaRegistro;
use App\Support\EmployeeProductionGroup;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VinetaRegistroController extends Controller
{
    private string $timezone = 'America/Tegucigalpa';

    private int $metaDiariaMinutos = 570;

    public function feed(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'fecha' => ['required', 'date_format:Y-m-d'],
            'todo' => ['nullable', 'in:0,1'],
            'grupo' => ['nullable', 'in:rezago,anillado,llenado,limpieza'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'La fecha es obligatoria y debe tener el formato AAAA-MM-DD.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $todo = ($data['todo'] ?? '1') === '1';
        $grupo = $data['grupo'] ?? null;
        $registros = VinetaRegistro::query()
            ->select([
                'id',
                'vineta_id',
                'vineta_api_id',
                'producto_item',
                'producto_codigo',
                'orden_del_sistema',
                'orden',
                'actividad_codigo',
                'actividad_nombre',
                'actividad_tipo_empaque',
                'empleado_codigo',
                'empleado_nombre',
                'cantidad_puros',
                'minutos_trabajados',
                'fecha_registro',
                'hora_registro',
            ])
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->when(
                $todo,
                fn ($query) => $query->where('fecha_registro', '>=', $data['fecha']),
                fn ($query) => $query->whereDate('fecha_registro', $data['fecha'])
            )
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get();

        if ($grupo !== null) {
            $registros = $registros
                ->filter(fn (VinetaRegistro $registro) => $this->grupoActividadRegistro($registro) === $grupo)
                ->values();
        }

        return response()->json([
            'message' => 'Viñetas registradas encontradas.',
            'fecha_desde' => $data['fecha'],
            'todo' => $todo ? 1 : 0,
            'grupo' => $grupo,
            'total' => $registros->count(),
            'registros' => $registros->map(fn (VinetaRegistro $registro) => [
                'id_vineta' => (int) ($registro->vineta_api_id ?? $registro->vineta_id),
                'item' => $registro->producto_item,
                'codigo_producto' => $registro->producto_codigo,
                'orden_del_sistema' => $registro->orden_del_sistema,
                'orden_del_cliente' => $registro->orden,
                'codigo_actividad' => $registro->actividad_codigo,
                'actividad' => $registro->actividad_nombre,
                'grupo' => $this->grupoActividadRegistro($registro),
                'empleado_codigo' => $registro->empleado_codigo,
                'empleado_nombre' => $registro->empleado_nombre,
                'cantidad_puros' => (int) $registro->cantidad_puros,
                'minutos_por_vineta' => $registro->minutos_trabajados === null
                    ? null
                    : round((int) $registro->minutos_trabajados / 60, 2),
                'fecha_ingreso' => $registro->fecha_registro?->format('Y-m-d'),
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'estado' => ['nullable', 'in:activo,anulado,todos'],
        ]);

        $estado = $data['estado'] ?? 'activo';
        $query = VinetaRegistro::query()
            ->whereDate('fecha_registro', $data['fecha'])
            ->when($estado !== 'todos', fn ($query) => $query->where('estado', $estado))
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id');

        $registros = $query->with('empleado')->get();
        $gruposEmpleados = $this->gruposProduccionEmpleados($registros);
        $activos = $registros->where('estado', VinetaRegistro::ESTADO_ACTIVO);
        $minutosCajones = (int) $activos->sum(fn (VinetaRegistro $registro) => (int) ($registro->minutos_trabajados ?? 0));
        $incluyeOrdinarias = $estado !== 'anulado' && Schema::hasTable('empleado_horas_ordinarias');
        $minutosOrdinarios = $incluyeOrdinarias
            ? (int) DB::table('empleado_horas_ordinarias')->whereDate('fecha', $data['fecha'])->sum('minutos')
            : 0;
        $horasOrdinariasCount = $incluyeOrdinarias
            ? (int) DB::table('empleado_horas_ordinarias')->whereDate('fecha', $data['fecha'])->count()
            : 0;
        $totalMinutos = $minutosCajones + $minutosOrdinarios;

        return response()->json([
            'message' => 'Registros encontrados.',
            'fecha' => $data['fecha'],
            'resumen' => [
                'registros' => $registros->count(),
                'registros_total' => $registros->count() + $horasOrdinariasCount,
                'activos' => $activos->count(),
                'horas_ordinarias' => $horasOrdinariasCount,
                'por_hora' => $activos->filter(fn (VinetaRegistro $registro) => $registro->esPorHoraOrdinario())->count(),
                'puros' => (int) $activos->sum('cantidad_puros'),
                'cajones' => (int) $activos->sum('cantidad_cajones'),
                'actividades' => (int) $activos->sum(fn (VinetaRegistro $registro) => $registro->total_actividades),
                'minutos' => $totalMinutos,
                'minutos_cajones' => $minutosCajones,
                'minutos_ordinarios' => $minutosOrdinarios,
                'tiempo' => VinetaRegistro::minutosATiempoTexto($totalMinutos),
                'tiempo_cajones' => VinetaRegistro::minutosATiempoTexto($minutosCajones),
                'tiempo_ordinario' => VinetaRegistro::minutosATiempoTexto($minutosOrdinarios),
                'monto' => (float) $activos->sum(fn (VinetaRegistro $registro) => $registro->total_mo),
            ],
            'registros' => $registros->map(
                fn (VinetaRegistro $registro) => $this->registroPayload(
                    $registro,
                    $gruposEmpleados->get($this->claveEmpleadoRegistro($registro))
                )
            )->values(),
        ]);
    }

    public function seguimiento(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vineta_id' => ['nullable', 'integer', 'min:1'],
            'vineta_api_id' => ['nullable', 'integer', 'min:1'],
            'vineta_fecha' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if (empty($data['vineta_id']) && empty($data['vineta_api_id'])) {
            throw ValidationException::withMessages([
                'vineta_id' => 'Ingresa o escanea una viñeta para consultar el seguimiento.',
            ]);
        }

        $vinetas = ! empty($data['vineta_id'])
            ? Vineta::query()->whereKey((int) $data['vineta_id'])->get()
            : Vineta::query()
                ->where('api_id', (int) $data['vineta_api_id'])
                ->when(! empty($data['vineta_fecha']), fn ($query) => $query->whereDate('fecha', $data['vineta_fecha']))
                ->orderByDesc('id')
                ->get();
        $vineta = $vinetas->first();
        $apiId = $vineta?->api_id ?? (isset($data['vineta_api_id']) ? (int) $data['vineta_api_id'] : null);
        $fecha = $vineta?->fecha?->format('Y-m-d') ?? ($data['vineta_fecha'] ?? null);
        $vinetaIds = $vinetas->pluck('id');

        $registros = VinetaRegistro::query()
            ->where(function ($query) use ($apiId, $fecha, $vinetaIds) {
                $hasCondition = false;

                if ($vinetaIds->isNotEmpty()) {
                    $query->whereIn('vineta_id', $vinetaIds);
                    $hasCondition = true;
                }

                if ($apiId) {
                    $method = $hasCondition ? 'orWhere' : 'where';

                    $query->{$method}(function ($query) use ($apiId, $fecha) {
                        $query->where('vineta_api_id', $apiId);

                        if ($fecha) {
                            $query->whereDate('vineta_fecha', $fecha);
                        }
                    });

                    $hasCondition = true;
                }

                if (! $hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get();
        $referencia = $registros->last();

        if (! $vineta && ! $referencia) {
            return response()->json([
                'message' => 'No se encontró una viñeta con ese código.',
                'vineta' => null,
                'movimientos' => [],
            ], 404);
        }

        return response()->json([
            'message' => $registros->isEmpty()
                ? 'Viñeta encontrada sin movimientos registrados.'
                : 'Seguimiento encontrado.',
            'vineta' => $this->seguimientoVinetaPayload($vineta, $referencia, $apiId, $fecha),
            'resumen' => [
                'movimientos' => $registros->count(),
                'activos' => $registros->where('estado', VinetaRegistro::ESTADO_ACTIVO)->count(),
                'ultimo_movimiento' => $referencia?->actividad_nombre,
                'ultimo_empleado' => $referencia?->empleado_nombre,
                'ultima_fecha' => $referencia?->fechaHoraRegistroTexto(),
            ],
            'movimientos' => $registros
                ->map(fn (VinetaRegistro $registro) => $this->seguimientoMovimientoPayload($registro))
                ->values(),
        ]);
    }

    public function store(Request $request, ?Vineta $vineta = null): JsonResponse
    {
        $this->mergeMinutosTrabajadosAlias($request);

        $data = $request->validate($this->storeRules(
            $vineta !== null,
            false
        ));

        $vineta ??= Vineta::findOrFail($data['vineta_id']);
        $producto = $this->resolveProducto($data, $vineta);
        $actividad = $this->resolveActividad($data);
        $empleado = $this->resolveEmpleado($data);
        $registradoEn = $this->resolveRegistradoEn($data);
        $porTarea = ($data['modo_registro'] ?? 'por_tarea') !== 'por_hora';

        if (! $empleado) {
            throw ValidationException::withMessages([
                'empleado_codigo' => 'No se encontró el empleado indicado.',
            ]);
        }

        if (! $empleado->activo) {
            throw ValidationException::withMessages([
                'empleado_codigo' => 'El empleado indicado no está activo.',
            ]);
        }

        $actividadApiId = $this->inputInt($data, 'api_id_actividad', 'actividad_api_id')
            ?? $actividad?->api_id_actividad;
        $actividadCodigo = $actividad?->codigo_actividad
            ?? $this->inputString($data, 'codigo_actividad', 'actividad_codigo');
        $actividadNombre = $actividad?->nombre
            ?? $this->inputString($data, 'actividad_nombre', 'nombre_actividad', 'nombre');
        $actividadTipoEmpaque = $this->inputString($data, 'actividad_tipo_empaque', 'tipo_empaque');
        $precioMo = $porTarea ? $this->resolvePrecioMo($data, $actividad) : 0;

        if (! $actividadNombre) {
            throw ValidationException::withMessages([
                'actividad_nombre' => 'Selecciona una actividad válida para guardar el registro.',
            ]);
        }

        $isVinetaPorOrden = $this->isVinetaPorOrden($vineta);

        if (! $isVinetaPorOrden) {
            $grupoActividad = $this->grupoActividadProceso($actividadNombre, $actividadTipoEmpaque, $actividadCodigo);

            if ($grupoActividad && $grupoActividad !== 'llenado' && $this->vinetaTieneGrupoProceso($vineta, $grupoActividad)) {
                throw ValidationException::withMessages([
                    'actividad_nombre' => 'Esta viñeta ya tiene '.$this->grupoActividadProcesoLabel($grupoActividad).' registrado.',
                ]);
            }

            $duplicado = $this->registroActivoExistente(
                $vineta,
                $registradoEn,
                $actividad,
                $actividadApiId,
                $actividadCodigo,
                $actividadNombre
            );

            if ($duplicado) {
                return response()->json([
                    'message' => 'Ya existe un registro activo para esta viñeta, actividad y fecha.',
                    'registro' => $this->registroPayload($duplicado),
                    'resumen_diario' => $this->resumenDiarioPayload($duplicado),
                ], 409);
            }

            if ($this->vinetaTieneActividadProceso($vineta, $actividad, $actividadApiId, $actividadCodigo, $actividadNombre)) {
                throw ValidationException::withMessages([
                    'actividad_nombre' => 'Esta viñeta ya tiene la actividad '.$actividadNombre.' registrada.',
                ]);
            }
        }

        $cantidadActividades = $this->resolveCantidadActividades($data, $actividadNombre);

        $codigoVinetaGenerado = $isVinetaPorOrden
            ? $this->generarSiguienteIdOrden()
            : $this->codigoVineta($vineta);

        $registro = DB::transaction(function () use (
            $request,
            $data,
            $vineta,
            $producto,
            $actividad,
            $empleado,
            $registradoEn,
            $actividadApiId,
            $actividadCodigo,
            $actividadNombre,
            $actividadTipoEmpaque,
            $precioMo,
            $cantidadActividades,
            $isVinetaPorOrden,
            $codigoVinetaGenerado
        ) {
            $payload = [
                'vineta_id' => $vineta->id,
                'producto_id' => $producto?->id,
                'actividad_id' => $actividad?->id,
                'empleado_id' => $empleado->id,
                'registrado_por_user_id' => $request->user()?->id,
                'codigo_vineta' => $codigoVinetaGenerado,
                'vineta_api_id' => $isVinetaPorOrden ? null : $vineta->api_id,
                'id_pendiente_empaque' => $vineta->id_pendiente_empaque,
                'id_detalle_programacion' => $vineta->id_detalle_programacion,
                'vineta_fecha' => $vineta->fecha,
                'producto_codigo' => $producto?->codigo_producto ?? $vineta->codigo_producto,
                'producto_item' => $producto?->item ?? $vineta->item,
                'producto_nombre' => $this->textoPreferidoVineta($vineta->nombre, $producto?->nombre),
                'marca' => $vineta->marca,
                'capa' => $vineta->capa,
                'vitola' => $vineta->vitola,
                'tipo_empaque' => $vineta->tipo_empaque,
                'orden' => $vineta->orden,
                'orden_del_sistema' => $vineta->orden_del_sistema,
                'actividad_api_id' => $actividadApiId,
                'actividad_codigo' => $actividadCodigo,
                'actividad_nombre' => $actividadNombre,
                'actividad_tipo_empaque' => $actividadTipoEmpaque,
                'precio_mo' => $precioMo,
                'empleado_codigo' => $empleado->codigo,
                'empleado_nombre' => $empleado->nombre,
                'cantidad_puros' => (int) $data['cantidad_puros'],
                'cantidad_cajones' => (int) ($data['cantidad_cajones'] ?? 1),
                'cantidad_actividades' => $cantidadActividades,
                'fecha_registro' => $registradoEn->toDateString(),
                'hora_registro' => $registradoEn->format('H:i:s'),
                'registrado_en' => $registradoEn,
                'registrado_por_nombre' => $request->user()?->name,
                'estado' => VinetaRegistro::ESTADO_ACTIVO,
                'observacion' => $this->inputString($data, 'observacion'),
                'raw_payload' => $request->all(),
            ];

            if (Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
                $payload['minutos_trabajados'] = ($data['modo_registro'] ?? 'por_tarea') === 'por_hora'
                    ? null
                    : ($this->inputInt($data, 'minutos_trabajados') ?? 0);
            }

            $registro = VinetaRegistro::create($payload);

            if ($producto && $actividad) {
                $tipoEmpaqueModel = null;
                if ($actividadTipoEmpaque) {
                    $tipoEmpaqueModel = TipoEmpaque::whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($actividadTipoEmpaque))])->first();
                }

                app(\App\Services\ActividadApiService::class)->asociarActividadDeEscaneo(
                    $producto,
                    $actividad,
                    $tipoEmpaqueModel?->id ?? $producto->tipo_empaque_id,
                    (float) $precioMo,
                    $registradoEn
                );
            }

            return $registro;
        });

        return response()->json([

            'message' => 'Registro de viñeta guardado correctamente.',
            'registro' => $this->registroPayload($registro),
            'resumen_diario' => $this->resumenDiarioPayload($registro),
            'proceso' => $this->procesoVinetaPayload($vineta),
        ], 201);
    }

    public function resumenDiarioEmpleado(Request $request, Empleado $empleado): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json([
            'message' => 'Resumen diario encontrado.',
            'resumen_diario' => $this->resumenDiarioEmpleadoPayload(
                $empleado->codigo,
                $empleado->nombre,
                $data['fecha']
            ),
        ]);
    }

    public function seguimientoEmpleado(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['nullable', 'in:global,rezago,anillado,llenado'],
            'period' => ['nullable', 'in:day,month,year'],
            'date' => ['required', 'date_format:Y-m-d'],
            'empleado_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'empleado_codigo' => ['nullable', 'string', 'max:120'],
        ]);

        $scope = $data['scope'] ?? 'global';
        $period = $data['period'] ?? 'day';
        $date = Carbon::createFromFormat('Y-m-d', $data['date'], $this->timezone);
        [$from, $to, $label] = $this->seguimientoPeriodoRango($period, $date);
        $empleado = $this->resolveSeguimientoEmpleado($data);
        $empleadoCodigo = $empleado?->codigo;
        $empleadoBusqueda = $empleadoCodigo ? null : $this->inputString($data, 'empleado_codigo');

        $query = VinetaRegistro::query()
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->whereDate('fecha_registro', '>=', $from->toDateString())
            ->whereDate('fecha_registro', '<=', $to->toDateString())
            ->when($empleadoCodigo, fn ($query) => $query->where('empleado_codigo', $empleadoCodigo))
            ->when($empleadoBusqueda, fn ($query) => $query->where('empleado_codigo', 'like', '%'.$empleadoBusqueda.'%'))
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id');
        $periodRecords = $query->with('empleado')->get();
        $codigos = $periodRecords->pluck('empleado_codigo')->filter()->unique()->values();
        $empleadosMap = Empleado::query()->whereIn('codigo', $codigos)->get()->keyBy('codigo');

        $scopedRecords = $scope === 'global'
            ? $periodRecords
            : $periodRecords
                ->filter(function (VinetaRegistro $registro) use ($empleadosMap, $scope) {
                    $emp = $registro->empleado ?? $empleadosMap->get((string) $registro->empleado_codigo);
                    return EmployeeProductionGroup::fromCargo($emp?->cargo, $registro->empleado_codigo) === $scope;
                })
                ->values();

        return response()->json([
            'message' => 'Seguimiento de empleado encontrado.',
            'scope' => $scope,
            'period' => $period,
            'date' => $date->toDateString(),
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'label' => $label,
            ],
            'employee' => $this->seguimientoEmpleadoPayload($empleado),
            'summary' => $this->seguimientoResumenPayload($scopedRecords),
            'group_counts' => $this->seguimientoGrupoCountsPayload($periodRecords, $empleadosMap),
            'employee_summaries' => $this->seguimientoEmpleadoSummariesPayload($scopedRecords, $empleadosMap),
            'activity_summaries' => $this->seguimientoActividadSummariesPayload($scopedRecords),
            'records' => $empleado
                ? $scopedRecords->map(fn (VinetaRegistro $registro) => $this->registroPayload($registro))->values()
                : [],
        ]);
    }

    public function update(Request $request, VinetaRegistro $vinetaRegistro): JsonResponse
    {
        $this->mergeMinutosTrabajadosAlias($request);
        $actualizaActividad = $request->anyFilled([
            'actividad_id',
            'api_id_actividad',
            'actividad_api_id',
            'codigo_actividad',
            'actividad_codigo',
            'actividad_nombre',
            'nombre_actividad',
            'nombre',
        ]);

        $rules = [
            'fecha_registro' => ['required', 'date_format:Y-m-d'],
            'hora_registro' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'cantidad_puros' => ['required', 'integer', 'min:1', 'max:1000000'],
            'empleado_codigo' => ['required', 'string', 'max:120'],
            'modo_registro' => ['nullable', 'in:por_tarea,por_hora'],
            'actividad_id' => ['nullable', 'integer', 'exists:actividades,id'],
            'api_id_actividad' => ['nullable', 'integer'],
            'actividad_api_id' => ['nullable', 'integer'],
            'codigo_actividad' => ['nullable', 'string', 'max:120'],
            'actividad_codigo' => ['nullable', 'string', 'max:120'],
            'actividad_nombre' => ['nullable', 'string', 'max:255'],
            'nombre_actividad' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'actividad_tipo_empaque' => ['nullable', 'string', 'max:255'],
            'tipo_empaque' => ['nullable', 'string', 'max:255'],
            'precio_mo' => ['nullable', 'numeric', 'min:0'],
            'cantidad_actividades' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];

        $modoRegistro = $request->input('modo_registro', $vinetaRegistro->modoRegistro());
        $porHora = $modoRegistro === 'por_hora';

        if (Schema::hasColumn('vineta_registros', 'minutos_trabajados') && ! $porHora) {
            $rules['minutos_trabajados'] = ['nullable', 'integer', 'min:0', 'max:'.$this->metaDiariaMinutos];
        }

        $data = $request->validate($rules);
        $modoRegistro = $data['modo_registro'] ?? $vinetaRegistro->modoRegistro();
        $porHora = $modoRegistro === 'por_hora';
        $empleado = Empleado::where('codigo', trim($data['empleado_codigo']))->first();
        $actividad = $actualizaActividad
            ? $this->resolveActividad($data)
            : $vinetaRegistro->actividad;
        $actividadApiId = $actualizaActividad
            ? ($actividad?->api_id_actividad ?? $this->inputInt($data, 'api_id_actividad', 'actividad_api_id'))
            : $vinetaRegistro->actividad_api_id;
        $actividadCodigo = $actualizaActividad
            ? ($actividad?->codigo_actividad ?? $this->inputString($data, 'codigo_actividad', 'actividad_codigo'))
            : $vinetaRegistro->actividad_codigo;
        $actividadNombre = $actualizaActividad
            ? ($actividad?->nombre ?? $this->inputString($data, 'actividad_nombre', 'nombre_actividad', 'nombre'))
            : $vinetaRegistro->actividad_nombre;
        $actividadTipoEmpaque = $actualizaActividad
            ? $this->inputString($data, 'actividad_tipo_empaque', 'tipo_empaque')
            : $vinetaRegistro->actividad_tipo_empaque;

        if (! $empleado) {
            throw ValidationException::withMessages([
                'empleado_codigo' => 'No se encontró el empleado indicado.',
            ]);
        }

        if (! $empleado->activo) {
            throw ValidationException::withMessages([
                'empleado_codigo' => 'El empleado indicado no está activo.',
            ]);
        }

        if (! $actividadNombre) {
            throw ValidationException::withMessages([
                'actividad_nombre' => 'Selecciona una actividad válida para actualizar el registro.',
            ]);
        }

        $hora = $this->normalizeTime($data['hora_registro']);
        $registradoEn = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $data['fecha_registro'].' '.$hora,
            $this->timezone
        );
        $isVinetaPorOrden = $vinetaRegistro->vineta ? $this->isVinetaPorOrden($vinetaRegistro->vineta) : false;

        if (! $isVinetaPorOrden) {
            $duplicado = $this->registroActivoExistente(
                $vinetaRegistro->vineta,
                $registradoEn,
                $actividad,
                $actividadApiId,
                $actividadCodigo,
                $actividadNombre,
                $vinetaRegistro->id
            );

            if ($duplicado) {
                return response()->json([
                    'message' => 'Ya existe otro registro activo para esta viñeta, actividad y fecha.',
                    'registro' => $this->registroPayload($duplicado),
                    'resumen_diario' => $this->resumenDiarioPayload($duplicado),
                ], 409);
            }

            if ($actualizaActividad) {
                $grupoActividad = $this->grupoActividadProceso(
                    $actividadNombre,
                    $actividadTipoEmpaque,
                    $actividadCodigo
                );

                if (
                    $grupoActividad
                    && $grupoActividad !== 'llenado'
                    && $this->vinetaTieneGrupoProceso($vinetaRegistro->vineta, $grupoActividad, $vinetaRegistro->id)
                ) {
                    throw ValidationException::withMessages([
                        'actividad_nombre' => 'Esta viñeta ya tiene '.$this->grupoActividadProcesoLabel($grupoActividad).' registrado.',
                    ]);
                }

                if ($this->vinetaTieneActividadProceso(
                    $vinetaRegistro->vineta,
                    $actividad,
                    $actividadApiId,
                    $actividadCodigo,
                    $actividadNombre,
                    $vinetaRegistro->id
                )) {
                    throw ValidationException::withMessages([
                        'actividad_nombre' => 'Esta viñeta ya tiene la actividad '.$actividadNombre.' registrada.',
                    ]);
                }
            }
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

        if ($actualizaActividad) {
            $payload = array_merge($payload, [
                'actividad_id' => $actividad?->id,
                'actividad_api_id' => $actividadApiId,
                'actividad_codigo' => $actividadCodigo,
                'actividad_nombre' => $actividadNombre,
                'actividad_tipo_empaque' => $actividadTipoEmpaque,
                'cantidad_actividades' => VinetaRegistro::cantidadActividadesDesdeNombre($actividadNombre),
            ]);
        }

        $rawPayload = is_array($vinetaRegistro->raw_payload) ? $vinetaRegistro->raw_payload : [];
        $rawPayload['modo_registro'] = $modoRegistro;

        if ($actualizaActividad) {
            $rawPayload = array_merge($rawPayload, [
                'actividad_id' => $actividad?->id,
                'api_id_actividad' => $actividadApiId,
                'codigo_actividad' => $actividadCodigo,
                'actividad_nombre' => $actividadNombre,
                'actividad_tipo_empaque' => $actividadTipoEmpaque,
            ]);
        }

        $payload['raw_payload'] = $rawPayload;

        if ($porHora) {
            $payload['precio_mo'] = 0;
        } elseif ($actualizaActividad) {
            $payload['precio_mo'] = $this->resolvePrecioMo($data, $actividad) ?? 0;
        } elseif ((float) ($vinetaRegistro->precio_mo ?? 0) <= 0) {
            $payload['precio_mo'] = $this->precioMoRegistro($vinetaRegistro) ?? 0;
        }

        if (Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            $payload['minutos_trabajados'] = $porHora
                ? null
                : ($this->inputInt($data, 'minutos_trabajados') ?? 0);
        }

        $vinetaRegistro->update($payload);

        return response()->json([
            'message' => 'Registro actualizado correctamente.',
            'registro' => $this->registroPayload($vinetaRegistro),
            'resumen_diario' => $this->resumenDiarioPayload($vinetaRegistro),
        ]);
    }

    private function storeRules(bool $hasRouteVineta, bool $requireMinutosTrabajados): array
    {
        return [
            'vineta_id' => [$hasRouteVineta ? 'nullable' : 'required', 'integer', 'exists:vinetas,id'],
            'producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'modo_registro' => ['nullable', 'in:por_tarea,por_hora'],
            'actividad_id' => ['nullable', 'integer', 'exists:actividades,id'],
            'api_id_actividad' => ['nullable', 'integer'],
            'actividad_api_id' => ['nullable', 'integer'],
            'codigo_actividad' => ['nullable', 'string', 'max:120'],
            'actividad_codigo' => ['nullable', 'string', 'max:120'],
            'actividad_nombre' => ['nullable', 'string', 'max:255'],
            'nombre_actividad' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'actividad_tipo_empaque' => ['nullable', 'string', 'max:255'],
            'tipo_empaque' => ['nullable', 'string', 'max:255'],
            'precio_mo' => ['nullable', 'numeric', 'min:0'],
            'empleado_id' => ['nullable', 'integer', 'exists:empleados,id', 'required_without:empleado_codigo'],
            'empleado_codigo' => ['nullable', 'string', 'max:120', 'required_without:empleado_id'],
            'cantidad_puros' => ['required', 'integer', 'min:1', 'max:1000000'],
            'cantidad_cajones' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'cantidad_actividades' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'minutos_trabajados' => [$requireMinutosTrabajados ? 'required' : 'nullable', 'integer', 'min:0', 'max:570'],
            'fecha_registro' => ['nullable', 'date_format:Y-m-d'],
            'hora_registro' => ['nullable', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'registrado_en' => ['nullable', 'date'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function mergeMinutosTrabajadosAlias(Request $request): void
    {
        if (! $request->has('minutos_trabajados') && $request->has('minutos')) {
            $request->merge([
                'minutos_trabajados' => $request->input('minutos'),
            ]);
        }
    }

    private function resolveProducto(array $data, Vineta $vineta): ?Producto
    {
        if (! empty($data['producto_id'])) {
            $producto = Producto::find($data['producto_id']);

            if ($producto && $this->productoCoincideConVineta($producto, $vineta)) {
                return $producto;
            }
        }

        foreach (['item' => $vineta->item, 'codigo_producto' => $vineta->codigo_producto] as $column => $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $producto = Producto::where($column, $value)->first();

            if ($producto) {
                return $producto;
            }
        }

        return null;
    }

    private function textoPreferidoVineta(?string $vinetaValue, ?string $fallbackValue): ?string
    {
        foreach ([$vinetaValue, $fallbackValue] as $value) {
            $text = trim((string) $value);

            if ($text !== '' && ! in_array(strtolower($text), ['ninguna', 'ninguno', 'n/a', 'na', 'null'], true)) {
                return $text;
            }
        }

        return null;
    }

    private function productoCoincideConVineta(Producto $producto, Vineta $vineta): bool
    {
        $codigo = trim((string) $vineta->codigo_producto);
        $item = trim((string) $vineta->item);

        if ($item !== '') {
            return trim((string) $producto->item) === $item;
        }

        return $codigo !== '' && trim((string) $producto->codigo_producto) === $codigo;
    }

    private function resolveActividad(array $data): ?Actividad
    {
        if (! empty($data['actividad_id'])) {
            return Actividad::find($data['actividad_id']);
        }

        $apiId = $this->inputInt($data, 'api_id_actividad', 'actividad_api_id');

        if ($apiId) {
            $actividad = Actividad::where('api_id_actividad', $apiId)->first();

            if ($actividad) {
                return $actividad;
            }
        }

        $codigo = $this->inputString($data, 'codigo_actividad', 'actividad_codigo');

        if ($codigo) {
            $actividad = Actividad::where('codigo_actividad', $codigo)->first();

            if ($actividad) {
                return $actividad;
            }
        }

        $nombre = $this->inputString($data, 'actividad_nombre', 'nombre_actividad', 'nombre');

        if ($nombre) {
            return Actividad::where('nombre', $nombre)->first();
        }

        return null;
    }

    private function resolveCantidadActividades(array $data, string $actividadNombre): int
    {
        if (! empty($data['cantidad_actividades'])) {
            return max((int) $data['cantidad_actividades'], 1);
        }

        return VinetaRegistro::cantidadActividadesDesdeNombre($actividadNombre);
    }

    private function resolvePrecioMo(array $data, ?Actividad $actividad): ?float
    {
        $precioEnviado = null;

        if (array_key_exists('precio_mo', $data) && $data['precio_mo'] !== null && $data['precio_mo'] !== '') {
            $precioEnviado = (float) $data['precio_mo'];
        }

        if (! $actividad) {
            return $precioEnviado;
        }

        return VinetaRegistro::precioMoActividadCatalogo($actividad->id) ?? $precioEnviado;
    }

    private function resolveEmpleado(array $data): ?Empleado
    {
        if (! empty($data['empleado_id'])) {
            return Empleado::find($data['empleado_id']);
        }

        $codigo = $this->inputString($data, 'empleado_codigo');

        if (! $codigo) {
            return null;
        }

        return Empleado::where('codigo', $codigo)->first();
    }

    private function resolveRegistradoEn(array $data): Carbon
    {
        if (! empty($data['registrado_en'])) {
            return Carbon::parse($data['registrado_en'])->setTimezone($this->timezone);
        }

        $now = now($this->timezone);
        $date = $data['fecha_registro'] ?? $now->toDateString();
        $time = $data['hora_registro'] ?? $now->format('H:i:s');

        if (substr_count($time, ':') === 1) {
            $time .= ':00';
        }

        return Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$time, $this->timezone);
    }

    private function registroActivoExistente(
        Vineta $vineta,
        Carbon $registradoEn,
        ?Actividad $actividad,
        ?int $actividadApiId,
        ?string $actividadCodigo,
        string $actividadNombre,
        ?int $exceptRegistroId = null
    ): ?VinetaRegistro {
        return VinetaRegistro::query()
            ->where('vineta_id', $vineta->id)
            ->whereDate('fecha_registro', $registradoEn->toDateString())
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->when($exceptRegistroId, fn ($query) => $query->whereKeyNot($exceptRegistroId))
            ->where(function ($query) use ($actividad, $actividadApiId, $actividadCodigo, $actividadNombre) {
                if ($actividad) {
                    $query->orWhere('actividad_id', $actividad->id);
                }

                if ($actividadApiId) {
                    $query->orWhere('actividad_api_id', $actividadApiId);
                }

                if ($actividadCodigo) {
                    $query->orWhere('actividad_codigo', $actividadCodigo);
                }

                $query->orWhereRaw('LOWER(actividad_nombre) = ?', [strtolower($actividadNombre)]);
            })
            ->first();
    }

    private function procesoVinetaPayload(Vineta $vineta): array
    {
        $registros = $this->registrosProcesoVineta($vineta);
        $grupos = [
            'rezago' => null,
            'anillado' => null,
            'llenado' => null,
        ];
        $actividadesPorGrupo = [
            'rezago' => [],
            'anillado' => [],
            'llenado' => [],
        ];
        $registrosPayload = [];

        foreach ($registros as $registro) {
            $grupo = $this->grupoActividadProceso(
                $registro->actividad_nombre,
                $registro->actividad_tipo_empaque,
                $registro->actividad_codigo
            );

            $payload = [
                'id' => $registro->id,
                'actividad_id' => $registro->actividad_id,
                'actividad_api_id' => $registro->actividad_api_id,
                'actividad_codigo' => $registro->actividad_codigo,
                'actividad_nombre' => $registro->actividad_nombre,
                'grupo' => $grupo,
                'empleado' => $registro->empleado_nombre,
                'empleado_codigo' => $registro->empleado_codigo,
                'fecha' => $registro->fechaHoraRegistroTexto(),
            ];

            if ($grupo && array_key_exists($grupo, $grupos)) {
                $grupos[$grupo] = $registro;
                $actividadesPorGrupo[$grupo][] = $payload;
            }

            $registrosPayload[] = $payload;
        }

        return [
            'puede_llenar' => true,
            'mensaje_bloqueo_llenado' => null,
            'pasos' => [
                $this->pasoProcesoPayload('rezago', 'Rezago', $grupos['rezago'], true, $actividadesPorGrupo['rezago']),
                $this->pasoProcesoPayload('anillado', 'Anillado', $grupos['anillado'], false, $actividadesPorGrupo['anillado']),
                $this->pasoProcesoPayload('llenado', 'Llenado', $grupos['llenado'], false, $actividadesPorGrupo['llenado']),
            ],
            'registros' => $registrosPayload,
        ];
    }

    private function pasoProcesoPayload(string $key, string $label, ?VinetaRegistro $registro, bool $opcional, array $actividades = []): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'completado' => $registro !== null,
            'opcional' => $opcional,
            'actividad' => $registro?->actividad_nombre,
            'empleado' => $registro?->empleado_nombre,
            'fecha' => $registro?->fechaHoraRegistroTexto(),
            'actividades' => $actividades,
        ];
    }

    private function vinetaTieneActividadProceso(
        Vineta $vineta,
        ?Actividad $actividad,
        ?int $actividadApiId,
        ?string $actividadCodigo,
        ?string $actividadNombre,
        ?int $exceptRegistroId = null
    ): ?VinetaRegistro {
        return $this->registrosProcesoVineta($vineta)
            ->filter(fn (VinetaRegistro $r) => ! $exceptRegistroId || $r->id !== $exceptRegistroId)
            ->first(function (VinetaRegistro $r) use ($actividad, $actividadApiId, $actividadCodigo, $actividadNombre) {
                if ($actividad?->id && $r->actividad_id === $actividad->id) {
                    return true;
                }

                if ($actividadApiId && $r->actividad_api_id === $actividadApiId) {
                    return true;
                }

                if (
                    $actividadCodigo
                    && $r->actividad_codigo
                    && strtolower(trim((string) $r->actividad_codigo)) === strtolower(trim((string) $actividadCodigo))
                ) {
                    return true;
                }

                if (
                    $actividadNombre
                    && $r->actividad_nombre
                    && strtolower(trim((string) $r->actividad_nombre)) === strtolower(trim((string) $actividadNombre))
                ) {
                    return true;
                }

                return false;
            });
    }

    private function vinetaTieneGrupoProceso(
        Vineta $vineta,
        string $grupoBuscado,
        ?int $exceptRegistroId = null
    ): bool {
        foreach ($this->registrosProcesoVineta($vineta) as $registro) {
            if ($exceptRegistroId && $registro->id === $exceptRegistroId) {
                continue;
            }

            $grupo = $this->grupoActividadProceso(
                $registro->actividad_nombre,
                $registro->actividad_tipo_empaque,
                $registro->actividad_codigo
            );

            if ($grupo === $grupoBuscado) {
                return true;
            }
        }

        return false;
    }

    private function grupoActividadProcesoLabel(string $grupo): string
    {
        return match ($grupo) {
            'rezago' => 'rezago',
            'anillado' => 'anillado',
            'llenado' => 'llenado',
            default => 'este proceso',
        };
    }


    private function registrosProcesoVineta(Vineta $vineta)
    {
        return VinetaRegistro::query()
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->where(function ($query) use ($vineta) {
                $query->where('vineta_id', $vineta->id);

                if ($vineta->api_id && $vineta->fecha) {
                    $query->orWhere(function ($query) use ($vineta) {
                        $query->where('vineta_api_id', $vineta->api_id)
                            ->whereDate('vineta_fecha', $vineta->fecha->format('Y-m-d'));
                    });
                }
            })
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get();
    }

    private function seguimientoPeriodoRango(string $period, Carbon $date): array
    {
        return match ($period) {
            'month' => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
                $date->format('m/Y'),
            ],
            'year' => [
                $date->copy()->startOfYear(),
                $date->copy()->endOfYear(),
                $date->format('Y'),
            ],
            default => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
                $date->format('d/m/Y'),
            ],
        };
    }

    private function resolveSeguimientoEmpleado(array $data): ?Empleado
    {
        if (! empty($data['empleado_id'])) {
            return Empleado::find((int) $data['empleado_id']);
        }

        $codigo = $this->inputString($data, 'empleado_codigo');

        if (! $codigo) {
            return null;
        }

        return Empleado::where('codigo', $codigo)->first();
    }

    private function seguimientoEmpleadoPayload(?Empleado $empleado): ?array
    {
        if (! $empleado) {
            return null;
        }

        return [
            'id' => $empleado->id,
            'codigo' => $empleado->codigo,
            'nombre' => $empleado->nombre,
            'activo' => (bool) $empleado->activo,
            'cargo' => $empleado->cargo,
            'area' => $empleado->area,
        ];
    }

    private function seguimientoResumenPayload($registros): array
    {
        $minutos = (int) $registros->sum(
            fn (VinetaRegistro $registro) => $registro->esPorHoraOrdinario()
                ? 0
                : (int) ($registro->minutos_trabajados ?? 0)
        );

        return [
            'registros' => $registros->count(),
            'empleados' => $registros
                ->map(fn (VinetaRegistro $registro) => trim((string) $registro->empleado_codigo))
                ->filter()
                ->unique()
                ->count(),
            'puros' => (int) $registros->sum('cantidad_puros'),
            'cajones' => (int) $registros->sum('cantidad_cajones'),
            'actividades' => (int) $registros->sum(fn (VinetaRegistro $registro) => $registro->total_actividades),
            'minutos' => $minutos,
            'tiempo' => VinetaRegistro::minutosATiempoTexto($minutos),
            'monto' => (float) $registros->sum(fn (VinetaRegistro $registro) => $registro->total_mo),
        ];
    }

    private function seguimientoGrupoCountsPayload($registros, $empleadosMap): array
    {
        $employees = [
            'global' => [],
            'anillado' => [],
            'rezago' => [],
            'llenado' => [],
        ];

        foreach ($registros as $registro) {
            $employeeKey = trim((string) $registro->empleado_codigo)
                ?: trim((string) $registro->empleado_nombre);

            if ($employeeKey === '') {
                continue;
            }

            $employees['global'][$employeeKey] = true;
            $emp = $registro->empleado ?? $empleadosMap->get((string) $registro->empleado_codigo);
            $grupo = EmployeeProductionGroup::fromCargo($emp?->cargo, $registro->empleado_codigo);

            if ($grupo && array_key_exists($grupo, $employees)) {
                $employees[$grupo][$employeeKey] = true;
            }
        }

        return [
            'global' => count($employees['global']),
            'anillado' => count($employees['anillado']),
            'rezago' => count($employees['rezago']),
            'llenado' => count($employees['llenado']),
        ];
    }

    private function seguimientoEmpleadoSummariesPayload($registros, $empleadosMap): array
    {
        return $registros
            ->groupBy(fn (VinetaRegistro $registro) => trim((string) $registro->empleado_codigo).'|'.trim((string) $registro->empleado_nombre))
            ->map(function ($items) use ($empleadosMap) {
                /** @var VinetaRegistro $first */
                $first = $items->first();
                $emp = $first?->empleado ?? $empleadosMap->get((string) $first?->empleado_codigo);
                $summary = $this->seguimientoResumenPayload($items);

                return [
                    'codigo' => $first?->empleado_codigo,
                    'nombre' => $first?->empleado_nombre,
                    'cargo' => $emp?->cargo,
                    'area' => $emp?->area,
                    'registros' => $summary['registros'],
                    'puros' => $summary['puros'],
                    'cajones' => $summary['cajones'],
                    'actividades' => $summary['actividades'],
                    'minutos' => $summary['minutos'],
                    'tiempo' => $summary['tiempo'],
                    'monto' => $summary['monto'],
                ];
            })
            ->sortByDesc('actividades')
            ->values()
            ->all();
    }

    private function seguimientoActividadSummariesPayload($registros): array
    {
        return $registros
            ->groupBy(fn (VinetaRegistro $registro) => trim((string) $registro->actividad_nombre) ?: 'Actividad')
            ->map(function ($items, string $actividad) {
                /** @var VinetaRegistro $first */
                $first = $items->first();
                $summary = $this->seguimientoResumenPayload($items);

                return [
                    'actividad' => $actividad,
                    'grupo' => $first ? $this->grupoActividadRegistro($first) : null,
                    'registros' => $summary['registros'],
                    'puros' => $summary['puros'],
                    'cajones' => $summary['cajones'],
                    'actividades' => $summary['actividades'],
                    'minutos' => $summary['minutos'],
                    'tiempo' => $summary['tiempo'],
                    'monto' => $summary['monto'],
                ];
            })
            ->sortByDesc('actividades')
            ->values()
            ->all();
    }

    private function grupoActividadRegistro(VinetaRegistro $registro): ?string
    {
        return $this->grupoActividadProceso(
            $registro->actividad_nombre,
            $registro->actividad_tipo_empaque,
            $registro->actividad_codigo
        );
    }

    private function claveEmpleadoRegistro(VinetaRegistro $registro): string
    {
        return trim((string) $registro->empleado_codigo)
            ?: 'id:'.((string) ($registro->empleado_id ?? $registro->id));
    }

    /**
     * @param  Collection<int, VinetaRegistro>  $registros
     * @return Collection<string, string>
     */
    private function gruposProduccionEmpleados(Collection $registros): Collection
    {
        return $registros
            ->filter(fn (VinetaRegistro $registro) => ! $registro->esPorHoraOrdinario())
            ->groupBy(fn (VinetaRegistro $registro) => $this->claveEmpleadoRegistro($registro))
            ->map(function (Collection $items) {
                /** @var VinetaRegistro $first */
                $first = $items->first();

                return EmployeeProductionGroup::fromCargo($first->empleado?->cargo, $first->empleado_codigo)
                    ?? $items
                        ->map(fn (VinetaRegistro $registro) => $this->grupoActividadRegistro($registro))
                        ->first(fn (?string $grupo) => $grupo !== null);
            })
            ->filter();
    }

    private function grupoActividadProceso(?string $nombre, ?string $tipoEmpaque = null, ?string $codigo = null): ?string
    {
        $texto = $this->normalizarTextoProceso(implode(' ', array_filter([$nombre, $tipoEmpaque, $codigo])));

        if ($texto === '') {
            return null;
        }

        // 1. Rezago
        if (
            str_contains($texto, 'rezag')
            || str_contains($texto, 'rezad')
            || str_contains($texto, 'resag')
            || str_contains($texto, 'rezurado')
            || str_contains($texto, 'rasurado')
        ) {
            return 'rezago';
        }

        // 2. Anillado: Anillo, celofan, cello, sello, lamina, esponja, tapon, banda, cinta, rolado
        if (
            str_contains($texto, 'anill')
            || str_contains($texto, 'anil')
            || str_contains($texto, 'celof')
            || str_contains($texto, 'cello')
            || str_contains($texto, 'sello')
            || str_contains($texto, 'lamina')
            || str_contains($texto, 'esponj')
            || str_contains($texto, 'tapon')
            || str_contains($texto, 'banda')
            || str_contains($texto, 'cinta')
            || str_contains($texto, 'rolado')
        ) {
            return 'anillado';
        }

        // 3. Llenado: Llenado, petaca, sampler, display, bolsa, caja, paquete, sellado, jarra, tubo, costura
        if (
            str_contains($texto, 'llenad')
            || str_contains($texto, 'petaca')
            || str_contains($texto, 'sampler')
            || str_contains($texto, 'display')
            || str_contains($texto, 'bolsa')
            || str_contains($texto, 'caja')
            || str_contains($texto, 'paquet')
            || str_contains($texto, 'tubo')
            || str_contains($texto, 'costura')
            || str_contains($texto, 'sellado')
            || str_contains($texto, 'jarra')
            || str_contains($texto, 'kretek')
            || str_contains($texto, 'swisher')
        ) {
            return 'llenado';
        }

        // 4. Limpieza: Limpieza de puros, limpiado de brocha
        if (
            str_contains($texto, 'limpieza')
            || str_contains($texto, 'limpiad')
            || str_contains($texto, 'limpia')
        ) {
            return 'limpieza';
        }

        return null;
    }



    private function normalizarTextoProceso(string $value): string
    {
        $value = Str::ascii(Str::lower(trim($value)));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function normalizeTime(string $time): string
    {
        return substr_count($time, ':') === 1 ? $time.':00' : $time;
    }

    private function precioMoRegistro(VinetaRegistro $registro): ?float
    {
        if (! $registro->actividad_id) {
            return null;
        }

        return VinetaRegistro::precioMoActividadCatalogo($registro->actividad_id);
    }

    private function codigoVineta(Vineta $vineta): ?string
    {
        foreach ([$vineta->id_pendiente_empaque, $vineta->orden_del_sistema, $vineta->orden, $vineta->api_id] as $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function registroPayload(VinetaRegistro $registro, ?string $grupoEmpleado = null): array
    {
        $registro->refresh();
        $registro->loadMissing(['vineta', 'empleado']);
        $grupoEmpleado = $registro->esPorHoraOrdinario()
            ? 'por_hora'
            : $grupoEmpleado
                ?? EmployeeProductionGroup::fromCargo($registro->empleado?->cargo, $registro->empleado_codigo)
                ?? $this->grupoActividadRegistro($registro);

        return [
            'id' => $registro->id,
            'vineta_id' => $registro->vineta_id,
            'codigo_vineta' => $registro->codigo_vineta,
            'vineta_api_id' => $registro->vineta_api_id,
            'id_pendiente_empaque' => $registro->id_pendiente_empaque,
            'id_detalle_programacion' => $registro->id_detalle_programacion,
            'vineta_fecha' => $registro->vineta_fecha?->format('Y-m-d'),
            'orden' => $registro->orden,
            'orden_del_sistema' => $registro->orden_del_sistema,
            'producto' => [
                'id' => $registro->producto_id,
                'codigo_producto' => $registro->producto_codigo,
                'item' => $registro->producto_item,
                'nombre' => $registro->productoNombreReporte(),
                'marca' => $registro->marca,
                'capa' => $registro->capa,
                'vitola' => $registro->vitola,
                'tipo_empaque' => $registro->tipoEmpaqueReporte(),
            ],
            'actividad' => [
                'id' => $registro->actividad_id,
                'api_id_actividad' => $registro->actividad_api_id,
                'codigo_actividad' => $registro->actividad_codigo,
                'nombre' => $registro->actividad_nombre,
                'tipo_empaque' => $registro->actividad_tipo_empaque,
                'precio_mo' => $registro->precioMoEfectivo(),
            ],
            'grupo_actividad' => $this->grupoActividadRegistro($registro),
            'grupo_empleado' => $grupoEmpleado,
            'empleado' => [
                'id' => $registro->empleado_id,
                'codigo' => $registro->empleado_codigo,
                'nombre' => $registro->empleado_nombre,
                'cargo' => $registro->empleado?->cargo,
                'area' => $registro->empleado?->area,
            ],
            'cantidad_puros' => $registro->cantidad_puros,
            'cantidad_cajones' => $registro->cantidad_cajones,
            'cantidad_actividades' => $registro->cantidadActividadesValor(),
            'modo_registro' => $registro->modoRegistro(),
            'por_hora' => $registro->esPorHoraOrdinario(),
            'minutos' => $registro->esPorHoraOrdinario() ? null : $registro->minutos_trabajados,
            'minutos_trabajados' => $registro->minutos_trabajados,
            'tiempo_trabajado_texto' => $registro->tiempoTrabajadoReporteTexto(),
            'total_actividades' => $registro->total_actividades,
            'total_mo' => $registro->total_mo,
            'fecha_registro' => $registro->fecha_registro?->format('Y-m-d'),
            'hora_registro' => $registro->hora_registro,
            'registrado_en' => $registro->registrado_en?->toIso8601String(),
            'registrado_en_texto' => $registro->fechaHoraRegistroTexto(),
            'registrado_por' => [
                'id' => $registro->registrado_por_user_id,
                'nombre' => $registro->registrado_por_nombre,
            ],
            'estado' => $registro->estado,
            'observacion' => $registro->observacion,
        ];
    }

    private function resumenDiarioPayload(VinetaRegistro $registro): array
    {
        return $this->resumenDiarioEmpleadoPayload(
            $registro->empleado_codigo,
            $registro->empleado_nombre,
            $registro->fecha_registro?->format('Y-m-d')
        );
    }

    private function resumenDiarioEmpleadoPayload(?string $empleadoCodigo, ?string $empleadoNombre, ?string $fecha): array
    {
        $minutosCajones = 0;
        $minutosOrdinarios = 0;
        $totalActividades = 0;

        if ($empleadoCodigo && $fecha && Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            $minutosCajones = (int) VinetaRegistro::query()
                ->where('empleado_codigo', $empleadoCodigo)
                ->whereDate('fecha_registro', $fecha)
                ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
                ->sum('minutos_trabajados');
        }

        if ($empleadoCodigo && $fecha) {
            $columns = ['cantidad_puros', 'actividad_nombre'];

            if (Schema::hasColumn('vineta_registros', 'cantidad_actividades')) {
                $columns[] = 'cantidad_actividades';
            }

            $totalActividades = (int) VinetaRegistro::query()
                ->where('empleado_codigo', $empleadoCodigo)
                ->whereDate('fecha_registro', $fecha)
                ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
                ->get($columns)
                ->sum(fn (VinetaRegistro $registro) => $registro->total_actividades);
        }

        if ($empleadoCodigo && $fecha && Schema::hasTable('empleado_horas_ordinarias')) {
            $minutosOrdinarios = (int) DB::table('empleado_horas_ordinarias')
                ->where('empleado_codigo', $empleadoCodigo)
                ->whereDate('fecha', $fecha)
                ->sum('minutos');
        }

        $totalMinutos = $minutosCajones + $minutosOrdinarios;

        $faltante = max($this->metaDiariaMinutos - $totalMinutos, 0);

        return [
            'empleado_codigo' => $empleadoCodigo,
            'empleado_nombre' => $empleadoNombre,
            'fecha' => $fecha,
            'meta_minutos' => $this->metaDiariaMinutos,
            'meta_texto' => VinetaRegistro::minutosATiempoTexto($this->metaDiariaMinutos),
            'minutos_cajones' => $minutosCajones,
            'tiempo_cajones_texto' => VinetaRegistro::minutosATiempoTexto($minutosCajones),
            'minutos_ordinarios' => $minutosOrdinarios,
            'tiempo_ordinario_texto' => VinetaRegistro::minutosATiempoTexto($minutosOrdinarios),
            'total_actividades' => $totalActividades,
            'total_minutos' => $totalMinutos,
            'total_texto' => VinetaRegistro::minutosATiempoTexto($totalMinutos),
            'faltante_minutos' => $faltante,
            'faltante_texto' => VinetaRegistro::minutosATiempoTexto($faltante),
            'completado' => $totalMinutos >= $this->metaDiariaMinutos,
            'porcentaje' => $this->metaDiariaMinutos > 0
                ? min(round(($totalMinutos / $this->metaDiariaMinutos) * 100, 1), 100)
                : 0,
        ];
    }

    private function seguimientoVinetaPayload(?Vineta $vineta, ?VinetaRegistro $registro, ?int $apiId, ?string $fecha): array
    {
        return [
            'id' => $vineta?->id ?? $registro?->vineta_id,
            'api_id' => $vineta?->api_id ?? $registro?->vineta_api_id ?? $apiId,
            'fecha' => $vineta?->fecha?->format('Y-m-d') ?? $registro?->vineta_fecha?->format('Y-m-d') ?? $fecha,
            'marca' => $vineta?->marca ?? $registro?->marca,
            'nombre' => $vineta?->nombre ?? $registro?->producto_nombre,
            'capa' => $vineta?->capa ?? $registro?->capa,
            'vitola' => $vineta?->vitola ?? $registro?->vitola,
            'tipo_empaque' => $vineta?->tipo_empaque ?? $registro?->tipo_empaque,
            'codigo_producto' => $vineta?->codigo_producto ?? $registro?->producto_codigo,
            'item' => $vineta?->item ?? $registro?->producto_item,
            'orden_del_sistema' => $vineta?->orden_del_sistema ?? $registro?->orden_del_sistema,
            'orden' => $vineta?->orden ?? $registro?->orden,
            'cantidad_puros' => $vineta?->cantidad_puros ?? $registro?->cantidad_puros,
            'estado' => $vineta?->estado,
            'impreso' => $vineta ? (bool) $vineta->impreso : null,
        ];
    }

    private function seguimientoMovimientoPayload(VinetaRegistro $registro): array
    {
        return [
            'id' => $registro->id,
            'actividad' => [
                'id' => $registro->actividad_id,
                'api_id_actividad' => $registro->actividad_api_id,
                'codigo_actividad' => $registro->actividad_codigo,
                'nombre' => $registro->actividad_nombre,
                'tipo_empaque' => $registro->actividad_tipo_empaque,
            ],
            'empleado' => [
                'id' => $registro->empleado_id,
                'codigo' => $registro->empleado_codigo,
                'nombre' => $registro->empleado_nombre,
            ],
            'fecha_registro' => $registro->fecha_registro?->format('Y-m-d'),
            'hora_registro' => $registro->hora_registro,
            'modo_registro' => $registro->modoRegistro(),
            'por_hora' => $registro->esPorHoraOrdinario(),
            'registrado_en_texto' => $registro->fechaHoraRegistroTexto(),
            'minutos' => $registro->esPorHoraOrdinario() ? null : $registro->minutos_trabajados,
            'minutos_trabajados' => $registro->minutos_trabajados,
            'tiempo_trabajado_texto' => $registro->tiempoTrabajadoReporteTexto(),
            'estado' => $registro->estado,
            'motivo_anulacion' => $registro->motivo_anulacion,
        ];
    }

    private function inputString(array $data, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = trim((string) $data[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function inputInt(array $data, string ...$keys): ?int
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                continue;
            }

            return (int) $data[$key];
        }

        return null;
    }

    private function isVinetaPorOrden(Vineta $vineta): bool
    {
        $idPendiente = strtolower(trim((string) $vineta->id_pendiente_empaque));
        if (str_starts_with($idPendiente, 'or-') || str_starts_with($idPendiente, 'o-')) {
            return true;
        }

        return VinetaPorOrden::query()
            ->whereRaw('LOWER(codigo_qr) = ?', [$idPendiente])
            ->exists();
    }

    private function generarSiguienteIdOrden(): string
    {
        $codigos = DB::table('vineta_registros')
            ->where(function ($q) {
                $q->where('codigo_vineta', 'like', 'o-%')
                  ->orWhere('codigo_vineta', 'like', 'O-%')
                  ->orWhere('codigo_vineta', 'like', 'or-%')
                  ->orWhere('codigo_vineta', 'like', 'OR-%');
            })
            ->pluck('codigo_vineta');

        $maxNum = 0;
        foreach ($codigos as $cod) {
            if (preg_match('/^o(?:r)?-(\d+)$/i', trim((string) $cod), $m)) {
                $num = (int) $m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        return 'o-' . ($maxNum + 1);
    }
}

