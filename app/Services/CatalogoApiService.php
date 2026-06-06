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

  public function sincronizar(): array
{
    set_time_limit(300);
    ini_set('max_execution_time', 300);

    $response = Http::timeout(120)->get($this->url);

        if ($response->failed()) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo conectar con la API del catálogo.',
                'total' => 0,
            ];
        }

        $data = $response->json('data') ?? [];

     foreach ($data as $item) {
    DB::transaction(function () use ($item) {
        $this->guardarProducto($item);
    });
}

        return [
            'ok' => true,
            'mensaje' => 'Catálogo sincronizado correctamente.',
            'total' => count($data),
        ];
    }

    private function guardarProducto(array $item): void
    {
        $empresa = $this->buscarOCrear(Empresa::class, $item['empresa'] ?? null);

        $marca = null;

        if (! empty($item['marca'])) {
            $marca = Marca::firstOrCreate(
                [
                    'empresa_id' => $empresa?->id,
                    'nombre' => trim($item['marca']),
                ],
                [
                    'empresa_id' => $empresa?->id,
                    'nombre' => trim($item['marca']),
                ]
            );
        }

        $vitola = $this->buscarOCrear(Vitola::class, $item['vitola'] ?? null);
        $capa = $this->buscarOCrear(Capa::class, $item['capa'] ?? null);
        $presentacion = $this->buscarOCrear(Presentacion::class, $item['presentacion'] ?? null);
        $tipoEmpaque = $this->buscarOCrear(TipoEmpaque::class, $item['tipo_empaque'] ?? null);

        $producto = Producto::updateOrCreate(
            [
                'api_id_producto' => $item['id_producto'],
            ],
            [
                'item' => $item['item'] ?? null,
                'codigo_producto' => $item['codigo_producto'] ?? null,
                'codigo_caja' => $item['codigo_caja'] ?? null,
                'codigo_precio' => $item['codigo_precio'] ?? null,
                'nombre' => $item['nombre'] ?? null,
                'descripcion' => $item['des'] ?? null,
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
            ]
        );

        $producto->actividades()->detach();

        foreach (($item['actividades'] ?? []) as $actividadItem) {
            $actividad = Actividad::updateOrCreate(
                [
                    'api_id_actividad' => $actividadItem['id_actividad'],
                ],
                [
                    'codigo_actividad' => $actividadItem['codigo_actividad'] ?? null,
                    'nombre' => $actividadItem['nombre_actividad'] ?? null,
                ]
            );

            $tipoEmpaqueActividad = $this->buscarOCrear(
                TipoEmpaque::class,
                $actividadItem['tipo_empaque'] ?? null
            );

            $producto->actividades()->attach($actividad->id, [
                'tipo_empaque_id' => $tipoEmpaqueActividad?->id,
                'precio_mo' => $actividadItem['precio_mo'] ?? 0,
            ]);
        }
    }

    private function buscarOCrear(string $modelo, ?string $nombre)
    {
        if (empty($nombre)) {
            return null;
        }

        return $modelo::firstOrCreate([
            'nombre' => trim($nombre),
        ]);
    }

    private function siNoABoolean(?string $valor): bool
    {
        return strtolower((string) $valor) === 'si' || strtolower((string) $valor) === 's';
    }
}