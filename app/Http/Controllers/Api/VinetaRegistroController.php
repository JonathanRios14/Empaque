<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Empleado;
use App\Models\Producto;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class VinetaRegistroController extends Controller
{
    private string $timezone = 'America/Tegucigalpa';
    private int $metaDiariaMinutos = 570;

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

        $registros = $query->get();
        $activos = $registros->where('estado', VinetaRegistro::ESTADO_ACTIVO);
        $minutos = (int) $activos->sum(fn (VinetaRegistro $registro) => (int) ($registro->minutos_trabajados ?? 0));

        return response()->json([
            'message' => 'Registros encontrados.',
            'fecha' => $data['fecha'],
            'resumen' => [
                'registros' => $registros->count(),
                'activos' => $activos->count(),
                'por_hora' => $activos->filter(fn (VinetaRegistro $registro) => $registro->esPorHoraOrdinario())->count(),
                'puros' => (int) $activos->sum('cantidad_puros'),
                'cajones' => (int) $activos->sum('cantidad_cajones'),
                'actividades' => (int) $activos->sum(fn (VinetaRegistro $registro) => $registro->total_actividades),
                'minutos' => $minutos,
                'tiempo' => VinetaRegistro::minutosATiempoTexto($minutos),
                'monto' => (float) $activos->sum(fn (VinetaRegistro $registro) => $registro->total_mo),
            ],
            'registros' => $registros->map(fn (VinetaRegistro $registro) => $this->registroPayload($registro))->values(),
        ]);
    }

    public function seguimiento(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vineta_api_id' => ['required', 'integer', 'min:1'],
            'vineta_fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $apiId = (int) $data['vineta_api_id'];
        $fecha = $data['vineta_fecha'];
        $vinetas = Vineta::query()
            ->where('api_id', $apiId)
            ->whereDate('fecha', $fecha)
            ->orderByDesc('id')
            ->get();
        $vinetaIds = $vinetas->pluck('id');

        $registros = VinetaRegistro::query()
            ->where(function ($query) use ($apiId, $fecha, $vinetaIds) {
                $query->where(function ($query) use ($apiId, $fecha) {
                    $query->where('vineta_api_id', $apiId)
                        ->whereDate('vineta_fecha', $fecha);
                });

                if ($vinetaIds->isNotEmpty()) {
                    $query->orWhereIn('vineta_id', $vinetaIds);
                }
            })
            ->orderBy('fecha_registro')
            ->orderBy('hora_registro')
            ->orderBy('id')
            ->get();
        $vineta = $vinetas->first();
        $referencia = $registros->last();

        if (! $vineta && ! $referencia) {
            return response()->json([
                'message' => 'No se encontró una viñeta con ese ID y fecha de viñeta.',
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
        $data = $request->validate($this->storeRules($vineta !== null));

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
        $precioMo = $porTarea ? $this->resolvePrecioMo($data, $producto, $actividad) : 0;

        if (! $actividadNombre) {
            throw ValidationException::withMessages([
                'actividad_nombre' => 'Selecciona una actividad válida para guardar el registro.',
            ]);
        }

        $cantidadActividades = $this->resolveCantidadActividades($data, $actividadNombre);
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
            $cantidadActividades
        ) {
            $payload = [
                'vineta_id' => $vineta->id,
                'producto_id' => $producto?->id,
                'actividad_id' => $actividad?->id,
                'empleado_id' => $empleado->id,
                'registrado_por_user_id' => $request->user()?->id,
                'codigo_vineta' => $this->codigoVineta($vineta),
                'vineta_api_id' => $vineta->api_id,
                'id_pendiente_empaque' => $vineta->id_pendiente_empaque,
                'id_detalle_programacion' => $vineta->id_detalle_programacion,
                'vineta_fecha' => $vineta->fecha,
                'producto_codigo' => $producto?->codigo_producto ?? $vineta->codigo_producto,
                'producto_item' => $producto?->item ?? $vineta->item,
                'producto_nombre' => $producto?->nombre ?? $vineta->nombre,
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
                    : $this->inputInt($data, 'minutos_trabajados');
            }

            return VinetaRegistro::create($payload);
        });

        return response()->json([
            'message' => 'Registro de viñeta guardado correctamente.',
            'registro' => $this->registroPayload($registro),
            'resumen_diario' => $this->resumenDiarioPayload($registro),
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

    public function update(Request $request, VinetaRegistro $vinetaRegistro): JsonResponse
    {
        $rules = [
            'fecha_registro' => ['required', 'date_format:Y-m-d'],
            'hora_registro' => ['required', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'cantidad_puros' => ['required', 'integer', 'min:1', 'max:1000000'],
            'empleado_codigo' => ['required', 'string', 'max:120'],
            'modo_registro' => ['nullable', 'in:por_tarea,por_hora'],
        ];

        $modoRegistro = $request->input('modo_registro', $vinetaRegistro->modoRegistro());
        $porHora = $modoRegistro === 'por_hora';

        if (Schema::hasColumn('vineta_registros', 'minutos_trabajados') && ! $porHora) {
            $rules['minutos_trabajados'] = ['required', 'integer', 'min:1', 'max:' . $this->metaDiariaMinutos];
        }

        $data = $request->validate($rules);
        $modoRegistro = $data['modo_registro'] ?? $vinetaRegistro->modoRegistro();
        $porHora = $modoRegistro === 'por_hora';
        $empleado = Empleado::where('codigo', trim($data['empleado_codigo']))->first();

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

        $hora = $this->normalizeTime($data['hora_registro']);
        $registradoEn = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $data['fecha_registro'] . ' ' . $hora,
            $this->timezone
        );
        $duplicado = $this->registroActivoExistente(
            $vinetaRegistro->vineta,
            $registradoEn,
            $vinetaRegistro->actividad,
            $vinetaRegistro->actividad_api_id,
            $vinetaRegistro->actividad_codigo,
            $vinetaRegistro->actividad_nombre,
            $vinetaRegistro->id
        );

        if ($duplicado) {
            return response()->json([
                'message' => 'Ya existe otro registro activo para esta viñeta, actividad y fecha.',
                'registro' => $this->registroPayload($duplicado),
            ], 409);
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

        $rawPayload = is_array($vinetaRegistro->raw_payload) ? $vinetaRegistro->raw_payload : [];
        $rawPayload['modo_registro'] = $modoRegistro;
        $payload['raw_payload'] = $rawPayload;

        if ($porHora) {
            $payload['precio_mo'] = 0;
        } elseif ((float) ($vinetaRegistro->precio_mo ?? 0) <= 0) {
            $payload['precio_mo'] = $this->precioMoRegistro($vinetaRegistro) ?? 0;
        }

        if (Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            $payload['minutos_trabajados'] = $porHora
                ? null
                : $this->inputInt($data, 'minutos_trabajados');
        }

        $vinetaRegistro->update($payload);

        return response()->json([
            'message' => 'Registro actualizado correctamente.',
            'registro' => $this->registroPayload($vinetaRegistro),
            'resumen_diario' => $this->resumenDiarioPayload($vinetaRegistro),
        ]);
    }

    private function storeRules(bool $hasRouteVineta): array
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
            'minutos_trabajados' => ['nullable', 'integer', 'min:1', 'max:570'],
            'fecha_registro' => ['nullable', 'date_format:Y-m-d'],
            'hora_registro' => ['nullable', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/'],
            'registrado_en' => ['nullable', 'date'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
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

    private function resolvePrecioMo(array $data, ?Producto $producto, ?Actividad $actividad): ?float
    {
        if (array_key_exists('precio_mo', $data) && $data['precio_mo'] !== null && $data['precio_mo'] !== '') {
            return (float) $data['precio_mo'];
        }

        if (! $producto || ! $actividad) {
            return null;
        }

        $precio = DB::table('actividad_producto')
            ->where('producto_id', $producto->id)
            ->where('actividad_id', $actividad->id)
            ->value('precio_mo');

        return $precio === null ? null : (float) $precio;
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

        return Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time, $this->timezone);
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
            ->where('fecha_registro', $registradoEn->toDateString())
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

    private function normalizeTime(string $time): string
    {
        return substr_count($time, ':') === 1 ? $time . ':00' : $time;
    }

    private function precioMoRegistro(VinetaRegistro $registro): ?float
    {
        if (! $registro->producto_id || ! $registro->actividad_id) {
            return null;
        }

        $precio = DB::table('actividad_producto')
            ->where('producto_id', $registro->producto_id)
            ->where('actividad_id', $registro->actividad_id)
            ->value('precio_mo');

        return $precio === null ? null : (float) $precio;
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

    private function registroPayload(VinetaRegistro $registro): array
    {
        $registro->refresh();

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
                'nombre' => $registro->producto_nombre,
                'marca' => $registro->marca,
                'capa' => $registro->capa,
                'vitola' => $registro->vitola,
                'tipo_empaque' => $registro->tipo_empaque,
            ],
            'actividad' => [
                'id' => $registro->actividad_id,
                'api_id_actividad' => $registro->actividad_api_id,
                'codigo_actividad' => $registro->actividad_codigo,
                'nombre' => $registro->actividad_nombre,
                'tipo_empaque' => $registro->actividad_tipo_empaque,
                'precio_mo' => $registro->precio_mo,
            ],
            'empleado' => [
                'id' => $registro->empleado_id,
                'codigo' => $registro->empleado_codigo,
                'nombre' => $registro->empleado_nombre,
            ],
            'cantidad_puros' => $registro->cantidad_puros,
            'cantidad_cajones' => $registro->cantidad_cajones,
            'cantidad_actividades' => $registro->cantidadActividadesValor(),
            'modo_registro' => $registro->modoRegistro(),
            'por_hora' => $registro->esPorHoraOrdinario(),
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

        if ($empleadoCodigo && $fecha && Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            $minutosCajones = (int) VinetaRegistro::query()
                ->where('empleado_codigo', $empleadoCodigo)
                ->whereDate('fecha_registro', $fecha)
                ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
                ->sum('minutos_trabajados');
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

    private function seguimientoVinetaPayload(?Vineta $vineta, ?VinetaRegistro $registro, int $apiId, string $fecha): array
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
}
