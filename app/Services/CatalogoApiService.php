<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Capa;
use App\Models\Empresa;
use App\Models\Marca;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\TipoEmpaque;
use App\Models\Vitola;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CatalogoApiService
{
    private string $url = 'http://192.168.2.7:8080/api/clase_producto/empaque';

    private array $nombreCache = [];

    private array $marcaCache = [];

    private array $actividadCache = [];

    public function __construct(private readonly ExternalEmpaqueImageService $externalEmpaqueImageService)
    {
    }

    public function sincronizar(): array
    {
        set_time_limit(600);
        ini_set('max_execution_time', 600);

        $response = Http::timeout(180)
            ->connectTimeout(15)
            ->get($this->url);

        if ($response->failed()) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo conectar con la API del catálogo.',
                'total' => 0,
                'nuevos' => 0,
                'actualizados' => 0,
                'sin_cambios' => 0,
                'omitidos' => 0,
            ];
        }

        $json = $response->json();
        $data = $json['data'] ?? $json;

        if (! is_array($data)) {
            return [
                'ok' => false,
                'mensaje' => 'La respuesta de la API del catálogo no tiene un formato válido.',
                'total' => 0,
                'nuevos' => 0,
                'actualizados' => 0,
                'sin_cambios' => 0,
                'omitidos' => 0,
            ];
        }

        $items = [];
        $hashes = [];
        $omitidos = 0;

        foreach ($data as $item) {
            if (! is_array($item) || empty($item['id_producto'])) {
                $omitidos++;
                continue;
            }

            $item = $this->agregarImagenesExternas($item);

            $apiId = (int) $item['id_producto'];
            $items[$apiId] = $item;
            $hashes[$apiId] = $this->syncHash($item);
        }

        $existentes = $this->productosExistentes(array_keys($items));
        $pendientes = [];
        $nuevos = 0;
        $actualizados = 0;
        $sinCambios = 0;

        foreach ($items as $apiId => $item) {
            $producto = $existentes[$apiId] ?? null;
            $syncHash = $hashes[$apiId];

            if ($producto !== null && hash_equals((string) ($producto->sync_hash ?? ''), $syncHash)) {
                $sinCambios++;
                continue;
            }

            if ($producto === null) {
                $nuevos++;
            } else {
                $actualizados++;
            }

            $pendientes[] = [
                'item' => $item,
                'sync_hash' => $syncHash,
            ];
        }

        foreach (array_chunk($pendientes, 200) as $lote) {
            DB::transaction(function () use ($lote) {
                foreach ($lote as $pendiente) {
                    $this->guardarProducto($pendiente['item'], $pendiente['sync_hash']);
                }
            });
        }

        return [
            'ok' => true,
            'mensaje' => 'Catálogo sincronizado correctamente.',
            'total' => count($items),
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
            'sin_cambios' => $sinCambios,
            'omitidos' => $omitidos,
        ];
    }

    private function agregarImagenesExternas(array $item): array
    {
        $imagenesExternas = $this->externalEmpaqueImageService->imagenesParaProducto($item);

        if ($imagenesExternas['imagen_caja'] !== []) {
            $item['imagen_caja'] = $this->mergeImagenes(
                $this->itemValue($item, [
                    'imagen_caja',
                    'imagen_caja_url',
                    'imagen_caja_path',
                    'ruta_imagen_caja',
                    'imagen_empaque',
                    'imagen_empaque_url',
                    'imagen_empaque_path',
                    'ruta_imagen_empaque',
                ]),
                $imagenesExternas['imagen_caja']
            );
        }

        if ($imagenesExternas['imagen_anillado'] !== []) {
            $item['imagen_anillado'] = $this->mergeImagenes(
                $this->itemValue($item, [
                    'imagen_anillado',
                    'imagen_anillado_url',
                    'imagen_anillado_path',
                    'ruta_imagen_anillado',
                    'imagen_anillo',
                    'imagen_anillo_url',
                    'imagen_anillo_path',
                    'ruta_imagen_anillo',
                ]),
                $imagenesExternas['imagen_anillado']
            );
        }

        return $item;
    }

    private function mergeImagenes($actuales, array $externas): array
    {
        return collect(array_merge($this->imagenesArray($actuales), $externas))
            ->filter(fn ($imagen) => is_scalar($imagen))
            ->map(fn ($imagen) => trim((string) $imagen))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function guardarProducto(array $item, string $syncHash): void
    {
        $empresa = $this->buscarOCrear(Empresa::class, $item['empresa'] ?? null);

        $marca = $this->buscarOCrearMarca($empresa?->id, $item['marca'] ?? null);

        $vitola = $this->buscarOCrear(Vitola::class, $item['vitola'] ?? null);
        $capa = $this->buscarOCrear(Capa::class, $item['capa'] ?? null);
        $presentacion = $this->buscarOCrear(Presentacion::class, $item['presentacion'] ?? null);
        $tipoEmpaque = $this->buscarOCrear(TipoEmpaque::class, $item['tipo_empaque'] ?? null);

        $producto = Producto::firstOrNew([
            'api_id_producto' => (int) $item['id_producto'],
        ]);

        $producto->fill([
            'item' => $this->nullableString($item['item'] ?? null),
            'codigo_producto' => $this->nullableString($item['codigo_producto'] ?? null),
            'codigo_caja' => $this->nullableString($item['codigo_caja'] ?? null),
            'codigo_precio' => $this->nullableString($item['codigo_precio'] ?? null),
            'nombre' => $this->nullableString($item['nombre'] ?? null),
            'descripcion' => $this->nullableString($item['des'] ?? null),
            'imagen_caja' => $this->imagenesJson($this->itemValue($item, [
                'imagen_caja',
                'imagen_caja_url',
                'imagen_caja_path',
                'ruta_imagen_caja',
                'imagen_empaque',
                'imagen_empaque_url',
                'imagen_empaque_path',
                'ruta_imagen_empaque',
            ])),
            'imagen_anillado' => $this->imagenesJson($this->itemValue($item, [
                'imagen_anillado',
                'imagen_anillado_url',
                'imagen_anillado_path',
                'ruta_imagen_anillado',
                'imagen_anillo',
                'imagen_anillo_url',
                'imagen_anillo_path',
                'ruta_imagen_anillo',
            ])),
            'precio' => $item['precio'] ?? 0,
            'cantidad_bulto' => $item['cantidad_bulto'] ?? 0,
            'anillo' => $this->siNoABoolean($item['anillo'] ?? null),
            'cello' => $this->siNoABoolean($item['cello'] ?? null),
            'upc' => $this->siNoABoolean($item['upc'] ?? null),
            'sampler' => $this->siNoABoolean($item['sampler'] ?? null),
            'caja_local' => $this->siNoABoolean($item['caja_local'] ?? null),
            'empresa_id' => $empresa?->id,
            'marca_id' => $marca?->id,
            'vitola_id' => $vitola?->id,
            'capa_id' => $capa?->id,
            'presentacion_id' => $presentacion?->id,
            'tipo_empaque_id' => $tipoEmpaque?->id,
            'sync_hash' => $syncHash,
        ]);

        if (! $producto->exists || $producto->isDirty()) {
            $producto->save();
        }

        $this->sincronizarActividades($producto, $item['actividades'] ?? []);
    }

    private function sincronizarActividades(Producto $producto, array $actividades): void
    {
        $now = now();

        foreach ($actividades as $actividadItem) {
            if (! is_array($actividadItem) || empty($actividadItem['id_actividad'])) {
                continue;
            }

            $actividad = $this->guardarActividad($actividadItem);

            $tipoEmpaqueActividad = $this->buscarOCrear(
                TipoEmpaque::class,
                $actividadItem['tipo_empaque'] ?? null
            );

            $tipoEmpaqueId = $tipoEmpaqueActividad?->id;
            $precioMo = $actividadItem['precio_mo'] ?? 0;

            $existente = DB::table('actividad_producto')
                ->where('producto_id', $producto->id)
                ->where('actividad_id', $actividad->id)
                ->when($tipoEmpaqueId !== null, fn ($q) => $q->where('tipo_empaque_id', $tipoEmpaqueId))
                ->first();

            if ($existente) {
                DB::table('actividad_producto')
                    ->where('id', $existente->id)
                    ->update([
                        'precio_mo' => $precioMo,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('actividad_producto')->insertOrIgnore([
                    'producto_id' => $producto->id,
                    'actividad_id' => $actividad->id,
                    'tipo_empaque_id' => $tipoEmpaqueId,
                    'precio_mo' => $precioMo,
                    'activo' => true,
                    'origen' => 'api',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }


    private function guardarActividad(array $actividadItem): Actividad
    {
        $apiId = (int) $actividadItem['id_actividad'];
        $atributos = [
            'codigo_actividad' => $this->nullableString($actividadItem['codigo_actividad'] ?? null),
            'nombre' => $this->nullableString($actividadItem['nombre_actividad'] ?? null),
        ];

        if (isset($this->actividadCache[$apiId])) {
            $actividad = $this->actividadCache[$apiId];
            $actividad->fill($atributos);

            if ($actividad->isDirty()) {
                $actividad->save();
            }

            return $actividad;
        }

        $actividad = Actividad::firstOrNew([
            'api_id_actividad' => $apiId,
        ]);

        $actividad->fill($atributos);

        if (! $actividad->exists || $actividad->isDirty()) {
            $actividad->save();
        }

        return $this->actividadCache[$apiId] = $actividad;
    }

    private function buscarOCrear(string $modelo, ?string $nombre)
    {
        $nombre = $this->nullableString($nombre);

        if ($nombre === null) {
            return null;
        }

        $cacheKey = $modelo . '|' . strtolower($nombre);

        if (! array_key_exists($cacheKey, $this->nombreCache)) {
            $this->nombreCache[$cacheKey] = $modelo::firstOrCreate([
                'nombre' => $nombre,
            ]);
        }

        return $this->nombreCache[$cacheKey];
    }

    private function buscarOCrearMarca(?int $empresaId, ?string $nombre): ?Marca
    {
        $nombre = $this->nullableString($nombre);

        if ($nombre === null) {
            return null;
        }

        $cacheKey = ($empresaId ?? 'null') . '|' . strtolower($nombre);

        if (! array_key_exists($cacheKey, $this->marcaCache)) {
            $this->marcaCache[$cacheKey] = Marca::firstOrCreate([
                'empresa_id' => $empresaId,
                'nombre' => $nombre,
            ]);
        }

        return $this->marcaCache[$cacheKey];
    }

    private function siNoABoolean($valor): bool
    {
        return in_array(strtolower(trim((string) $valor)), ['1', 'true', 'yes', 'y', 'si', 's'], true);
    }

    private function productosExistentes(array $apiIds): array
    {
        $productos = [];

        foreach (array_chunk($apiIds, 1000) as $lote) {
            Producto::query()
                ->whereIn('api_id_producto', $lote)
                ->get(['id', 'api_id_producto', 'sync_hash'])
                ->each(function (Producto $producto) use (&$productos) {
                    $productos[(int) $producto->api_id_producto] = $producto;
                });
        }

        return $productos;
    }

    private function syncHash(array $item): string
    {
        return hash('sha256', json_encode(
            $this->normalizarProductoParaHash($item),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    private function normalizarProductoParaHash(array $item): array
    {
        $actividades = collect($item['actividades'] ?? [])
            ->filter(fn ($actividad) => is_array($actividad))
            ->map(fn (array $actividad) => [
                'id_actividad' => $this->normalizarValor($actividad['id_actividad'] ?? null),
                'codigo_actividad' => $this->normalizarValor($actividad['codigo_actividad'] ?? null),
                'nombre_actividad' => $this->normalizarValor($actividad['nombre_actividad'] ?? null),
                'tipo_empaque' => $this->normalizarValor($actividad['tipo_empaque'] ?? null),
                'precio_mo' => $this->normalizarValor($actividad['precio_mo'] ?? null),
            ])
            ->sortBy(fn (array $actividad) => implode('|', $actividad))
            ->values()
            ->all();

        return [
            'id_producto' => $this->normalizarValor($item['id_producto'] ?? null),
            'empresa' => $this->normalizarValor($item['empresa'] ?? null),
            'marca' => $this->normalizarValor($item['marca'] ?? null),
            'vitola' => $this->normalizarValor($item['vitola'] ?? null),
            'capa' => $this->normalizarValor($item['capa'] ?? null),
            'presentacion' => $this->normalizarValor($item['presentacion'] ?? null),
            'tipo_empaque' => $this->normalizarValor($item['tipo_empaque'] ?? null),
            'item' => $this->normalizarValor($item['item'] ?? null),
            'codigo_producto' => $this->normalizarValor($item['codigo_producto'] ?? null),
            'codigo_caja' => $this->normalizarValor($item['codigo_caja'] ?? null),
            'codigo_precio' => $this->normalizarValor($item['codigo_precio'] ?? null),
            'nombre' => $this->normalizarValor($item['nombre'] ?? null),
            'des' => $this->normalizarValor($item['des'] ?? null),
            'imagen_empaque' => $this->imagenesArray($this->itemValue($item, [
                'imagen_caja',
                'imagen_caja_url',
                'imagen_caja_path',
                'ruta_imagen_caja',
                'imagen_empaque',
                'imagen_empaque_url',
                'imagen_empaque_path',
                'ruta_imagen_empaque',
            ])),
            'imagen_anillado' => $this->imagenesArray($this->itemValue($item, [
                'imagen_anillado',
                'imagen_anillado_url',
                'imagen_anillado_path',
                'ruta_imagen_anillado',
                'imagen_anillo',
                'imagen_anillo_url',
                'imagen_anillo_path',
                'ruta_imagen_anillo',
            ])),
            'precio' => $this->normalizarValor($item['precio'] ?? null),
            'cantidad_bulto' => $this->normalizarValor($item['cantidad_bulto'] ?? null),
            'anillo' => $this->siNoABoolean($item['anillo'] ?? null),
            'cello' => $this->siNoABoolean($item['cello'] ?? null),
            'upc' => $this->siNoABoolean($item['upc'] ?? null),
            'sampler' => $this->siNoABoolean($item['sampler'] ?? null),
            'caja_local' => $this->siNoABoolean($item['caja_local'] ?? null),
            'actividades' => $actividades,
        ];
    }

    private function normalizarValor($valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return trim((string) $valor);
    }

    private function itemValue(array $item, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item)) {
                return $item[$key];
            }
        }

        return null;
    }

    private function nullableString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function imagenesJson($value): ?string
    {
        $imagenes = $this->imagenesArray($value);

        if ($imagenes === []) {
            return null;
        }

        return json_encode($imagenes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function imagenesArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return collect($value)
                ->flatten()
                ->filter(fn ($imagen) => is_scalar($imagen))
                ->map(fn ($imagen) => trim((string) $imagen))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->imagenesArray($decoded);
        }

        return [$value];
    }
}
