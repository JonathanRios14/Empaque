<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Capa;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\TipoEmpaque;
use App\Models\VinetaRegistro;
use App\Models\Vitola;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ActividadApiService
{
    private string $url = 'http://192.168.2.7:8080/api/fichas/empaque/actividades';

    public function sincronizar(): array
    {
        set_time_limit(300);

        try {
            $response = Http::timeout(60)
                ->connectTimeout(10)
                ->get($this->url);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'mensaje' => 'Error de conexión con la API de actividades: ' . $e->getMessage(),
                'total' => 0,
                'nuevos' => 0,
                'actualizados' => 0,
                'sin_cambios' => 0,
            ];
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo conectar con la API de actividades de empaque.',
                'total' => 0,
                'nuevos' => 0,
                'actualizados' => 0,
                'sin_cambios' => 0,
            ];
        }

        $json = $response->json();
        $data = $json['data'] ?? $json;

        if (! is_array($data)) {
            return [
                'ok' => false,
                'mensaje' => 'La respuesta de la API de actividades no tiene un formato válido.',
                'total' => 0,
                'nuevos' => 0,
                'actualizados' => 0,
                'sin_cambios' => 0,
            ];
        }

        $nuevos = 0;
        $actualizados = 0;
        $sinCambios = 0;
        $total = 0;

        DB::transaction(function () use ($data, &$nuevos, &$actualizados, &$sinCambios, &$total) {
            foreach ($data as $item) {
                if (! is_array($item) || empty($item['codigo_actividad']) || empty($item['nombre_actividad'])) {
                    continue;
                }

                $codigo = trim((string) $item['codigo_actividad']);
                $nombre = trim((string) $item['nombre_actividad']);
                $precioMo = (float) ($item['precio_mo'] ?? 0);

                if ($codigo === '' || $nombre === '') {
                    continue;
                }

                $total++;

                $actividad = Actividad::query()
                    ->where('codigo_actividad', $codigo)
                    ->orWhereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($nombre))])
                    ->first();

                if ($actividad === null) {
                    $targetApiId = is_numeric($codigo) ? (int) $codigo : 0;
                    if ($targetApiId <= 0 || Actividad::where('api_id_actividad', $targetApiId)->exists()) {
                        $maxApiId = (int) Actividad::max('api_id_actividad');
                        $targetApiId = max($maxApiId + 1, 1000 + $total);
                    }

                    Actividad::create([
                        'api_id_actividad' => $targetApiId,
                        'codigo_actividad' => $codigo,
                        'nombre' => $nombre,
                        'precio_mo' => $precioMo,
                    ]);
                    $nuevos++;
                } else {
                    $dirty = false;

                    if ($actividad->nombre !== $nombre) {
                        $actividad->nombre = $nombre;
                        $dirty = true;
                    }

                    if ($actividad->codigo_actividad !== $codigo) {
                        $actividad->codigo_actividad = $codigo;
                        $dirty = true;
                    }

                    if (abs((float) $actividad->precio_mo - $precioMo) > 0.0000001 && $precioMo > 0) {
                        $actividad->precio_mo = $precioMo;
                        $dirty = true;
                    }

                    if ($dirty) {
                        $actividad->save();
                        $actualizados++;
                    } else {
                        $sinCambios++;
                    }
                }
            }
        });

        // Crear/completar productos desde la información de viñetas registradas
        $productosCreados = $this->crearProductosDesdeVinetas();

        // Estandarizar precios en actividad_producto usando el precio oficial de la actividad
        $this->estandarizarPreciosOficiales();

        // Sincronizar también las actividades que ya han sido registradas en viñetas
        $vinculados = $this->vincularRegistrosHistoricosAProductos();

        return [
            'ok' => true,
            'mensaje' => 'Catálogo de actividades sincronizado correctamente.',
            'total' => $total,
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
            'sin_cambios' => $sinCambios,
            'productos_creados' => $productosCreados,
            'vinculados_escaneos' => $vinculados,
        ];
    }


    /**
     * Estandariza los precios en actividad_producto asignándoles el precio_mo
     * oficial de la tabla actividades.
     */
    public function estandarizarPreciosOficiales(): void
    {
        $actividadesConPrecio = Actividad::query()
            ->where('precio_mo', '>', 0)
            ->pluck('precio_mo', 'id');

        foreach ($actividadesConPrecio as $actividadId => $precioMo) {
            DB::table('actividad_producto')
                ->where('actividad_id', $actividadId)
                ->update(['precio_mo' => $precioMo]);
        }
    }

    /**
     * Crea o completa productos en el catálogo a partir de la información
     * registrada en viñetas para que existan todas las variantes escaneadas.
     */
    public function crearProductosDesdeVinetas(): int

    {
        $creados = 0;
        $maxApiId = (int) Producto::max('api_id_producto');

        $vinetasCombos = DB::table('vinetas')
            ->select([
                'item',
                'codigo_producto',
                'nombre',
                'marca',
                'vitola',
                'capa',
                'tipo_empaque',
            ])
            ->where(function ($q) {
                $q->whereNotNull('item')->where('item', '!=', '')
                  ->orWhereNotNull('codigo_producto')->where('codigo_producto', '!=', '');
            })
            ->distinct()
            ->get();

        foreach ($vinetasCombos as $combo) {
            $item = trim((string) $combo->item);
            $codigo = trim((string) $combo->codigo_producto);
            $nombre = trim((string) $combo->nombre);
            $marcaNombre = trim((string) $combo->marca);
            $vitolaNombre = trim((string) $combo->vitola);
            $capaNombre = trim((string) $combo->capa);
            $tipoEmpaqueNombre = trim((string) $combo->tipo_empaque);

            if ($item === '' && $codigo === '') {
                continue;
            }

            $marca = $marcaNombre !== '' ? Marca::firstOrCreate(['nombre' => $marcaNombre]) : null;
            $vitola = $vitolaNombre !== '' ? Vitola::firstOrCreate(['nombre' => $vitolaNombre]) : null;
            $capa = $capaNombre !== '' ? Capa::firstOrCreate(['nombre' => $capaNombre]) : null;
            $tipoEmpaque = $tipoEmpaqueNombre !== '' ? TipoEmpaque::firstOrCreate(['nombre' => $tipoEmpaqueNombre]) : null;

            $productoQuery = Producto::query();

            if ($item !== '' && $codigo !== '') {
                $productoQuery->where('item', $item)->where('codigo_producto', $codigo);
            } elseif ($item !== '') {
                $productoQuery->where('item', $item);
            } else {
                $productoQuery->where('codigo_producto', $codigo);
            }

            if ($capa) {
                $productoQuery->where('capa_id', $capa->id);
            }
            if ($tipoEmpaque) {
                $productoQuery->where('tipo_empaque_id', $tipoEmpaque->id);
            }

            $productoExistente = $productoQuery->first();

            if (! $productoExistente) {
                $productoBase = Producto::query()
                    ->where(function ($q) use ($item, $codigo) {
                        if ($item !== '' && $codigo !== '') {
                            $q->where('item', $item)->where('codigo_producto', $codigo);
                        } elseif ($item !== '') {
                            $q->where('item', $item);
                        } else {
                            $q->where('codigo_producto', $codigo);
                        }
                    })
                    ->first();

                $maxApiId++;

                $nuevoProducto = Producto::create([
                    'api_id_producto' => $maxApiId,
                    'item' => $item !== '' ? $item : ($productoBase?->item ?? 'N/A'),
                    'codigo_producto' => $codigo !== '' ? $codigo : ($productoBase?->codigo_producto ?? 'N/A'),
                    'nombre' => $nombre !== '' ? $nombre : ($productoBase?->nombre ?? 'Producto Viñeta'),
                    'marca_id' => $marca?->id ?? $productoBase?->marca_id,
                    'vitola_id' => $vitola?->id ?? $productoBase?->vitola_id,
                    'capa_id' => $capa?->id ?? $productoBase?->capa_id,
                    'tipo_empaque_id' => $tipoEmpaque?->id ?? $productoBase?->tipo_empaque_id,
                    'empresa_id' => $productoBase?->empresa_id,
                    'presentacion_id' => $productoBase?->presentacion_id,
                ]);

                if ($productoBase) {
                    foreach ($productoBase->actividades as $act) {
                        DB::table('actividad_producto')->insertOrIgnore([
                            'producto_id' => $nuevoProducto->id,
                            'actividad_id' => $act->id,
                            'tipo_empaque_id' => $nuevoProducto->tipo_empaque_id ?? $act->pivot->tipo_empaque_id,
                            'precio_mo' => $act->pivot->precio_mo,
                            'activo' => (bool) $act->pivot->activo,
                            'origen' => $act->pivot->origen ?? 'api',
                            'ultimo_escaneo_en' => $act->pivot->ultimo_escaneo_en,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $creados++;
            }
        }

        return $creados;
    }


    /**
     * Asocia una actividad de un escaneo a un producto en actividad_producto,
     * marcándola como activa, con origen 'escaneo' y actualizando la fecha de último escaneo.
     */
    public function asociarActividadDeEscaneo(
        Producto $producto,
        Actividad $actividad,
        ?int $tipoEmpaqueId = null,
        ?float $precioMo = null,
        ?\DateTimeInterface $fechaEscaneo = null
    ): void {
        $fechaEscaneo = $fechaEscaneo ?? now();
        $precioMo = ($precioMo !== null && $precioMo > 0) ? $precioMo : (float) $actividad->precio_mo;

        $pivote = DB::table('actividad_producto')
            ->where('producto_id', $producto->id)
            ->where('actividad_id', $actividad->id)
            ->when($tipoEmpaqueId !== null, fn ($q) => $q->where('tipo_empaque_id', $tipoEmpaqueId))
            ->first();

        if ($pivote) {
            $updates = [
                'activo' => true,
                'ultimo_escaneo_en' => $fechaEscaneo,
                'updated_at' => now(),
            ];

            if ((float) $pivote->precio_mo <= 0 && $precioMo > 0) {
                $updates['precio_mo'] = $precioMo;
            }

            DB::table('actividad_producto')
                ->where('id', $pivote->id)
                ->update($updates);
        } else {
            DB::table('actividad_producto')->insertOrIgnore([
                'producto_id' => $producto->id,
                'actividad_id' => $actividad->id,
                'tipo_empaque_id' => $tipoEmpaqueId ?? $producto->tipo_empaque_id,
                'precio_mo' => $precioMo,
                'activo' => true,
                'origen' => 'escaneo',
                'ultimo_escaneo_en' => $fechaEscaneo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Vincula históricamente todos los registros de viñetas escaneadas con sus productos.
     */
    public function vincularRegistrosHistoricosAProductos(): int
    {
        $vinculados = 0;

        $productosPorItem = Producto::query()
            ->whereNotNull('item')
            ->where('item', '!=', '')
            ->get()
            ->keyBy(fn ($p) => strtolower(trim($p->item)));

        $productosPorCodigo = Producto::query()
            ->whereNotNull('codigo_producto')
            ->where('codigo_producto', '!=', '')
            ->get()
            ->keyBy(fn ($p) => strtolower(trim($p->codigo_producto)));

        VinetaRegistro::query()
            ->with(['vineta', 'actividad'])
            ->whereNotNull('actividad_id')
            ->orderBy('id', 'asc')
            ->chunk(200, function ($registros) use ($productosPorItem, $productosPorCodigo, &$vinculados) {
                foreach ($registros as $registro) {
                    $producto = null;

                    if ($registro->producto_id) {
                        $producto = Producto::find($registro->producto_id);
                    }

                    if (! $producto && $registro->vineta) {
                        $item = strtolower(trim((string) $registro->vineta->item));
                        $codigo = strtolower(trim((string) $registro->vineta->codigo_producto));

                        if ($item !== '' && isset($productosPorItem[$item])) {
                            $producto = $productosPorItem[$item];
                        } elseif ($codigo !== '' && isset($productosPorCodigo[$codigo])) {
                            $producto = $productosPorCodigo[$codigo];
                        }
                    }

                    if (! $producto && $registro->producto_item) {
                        $item = strtolower(trim((string) $registro->producto_item));
                        if ($item !== '' && isset($productosPorItem[$item])) {
                            $producto = $productosPorItem[$item];
                        }
                    }

                    if (! $producto && $registro->producto_codigo) {
                        $codigo = strtolower(trim((string) $registro->producto_codigo));
                        if ($codigo !== '' && isset($productosPorCodigo[$codigo])) {
                            $producto = $productosPorCodigo[$codigo];
                        }
                    }

                    if (! $producto || ! $registro->actividad_id) {
                        continue;
                    }

                    $actividad = $registro->actividad ?? Actividad::find($registro->actividad_id);

                    if (! $actividad) {
                        continue;
                    }

                    $tipoEmpaqueId = null;

                    if ($registro->actividad_tipo_empaque) {
                        $tipoEmpaque = TipoEmpaque::whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($registro->actividad_tipo_empaque))])->first();
                        $tipoEmpaqueId = $tipoEmpaque?->id;
                    }

                    if (! $tipoEmpaqueId && $registro->vineta?->tipo_empaque) {
                        $tipoEmpaque = TipoEmpaque::whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($registro->vineta->tipo_empaque))])->first();
                        $tipoEmpaqueId = $tipoEmpaque?->id;
                    }

                    $fechaRegistro = $registro->created_at ?? now();
                    $precioMo = (float) ($registro->precio_mo ?? 0);
                    if ($precioMo <= 0) {
                        $precioMo = (float) $actividad->precio_mo;
                    }

                    $this->asociarActividadDeEscaneo(
                        $producto,
                        $actividad,
                        $tipoEmpaqueId ?? $producto->tipo_empaque_id,
                        $precioMo,
                        $fechaRegistro
                    );

                    $vinculados++;
                }
            });

        return $vinculados;
    }
}
