<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActividadController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'capa' => ['nullable', 'string', 'max:120'],
            'vitola' => ['nullable', 'string', 'max:120'],
            'tipo_empaque' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:80'],
        ]);

        $term = trim((string) ($data['q'] ?? ''));
        $capa = mb_strtolower(trim((string) ($data['capa'] ?? '')));
        $vitola = mb_strtolower(trim((string) ($data['vitola'] ?? '')));
        $tipoEmpaque = mb_strtolower(trim((string) ($data['tipo_empaque'] ?? '')));
        $hasCaracteristicas = $capa !== '' && $vitola !== '' && $tipoEmpaque !== '';
        $limit = (int) ($data['limit'] ?? 30);

        $query = DB::table('actividad_producto')
            ->join('actividades', 'actividades.id', '=', 'actividad_producto.actividad_id')
            ->leftJoin('tipo_empaques', 'tipo_empaques.id', '=', 'actividad_producto.tipo_empaque_id')
            ->leftJoin('productos', 'productos.id', '=', 'actividad_producto.producto_id')
            ->leftJoin('tipo_empaques as producto_tipo_empaques', 'producto_tipo_empaques.id', '=', 'productos.tipo_empaque_id')
            ->leftJoin('capas', 'capas.id', '=', 'productos.capa_id')
            ->leftJoin('vitolas', 'vitolas.id', '=', 'productos.vitola_id')
            ->select([
                'actividades.id',
                'actividades.api_id_actividad',
                'actividades.codigo_actividad',
                'actividades.nombre',
                'actividad_producto.precio_mo',
                'tipo_empaques.nombre as tipo_empaque',
                'productos.id as producto_id',
                'productos.codigo_producto',
                'productos.item',
                'productos.nombre as producto_nombre',
            ]);

        if ($tipoEmpaque !== '') {
            $query->whereRaw('LOWER(TRIM(tipo_empaques.nombre)) = ?', [$tipoEmpaque]);
        }

        $restrictedToExactProduct = false;

        if ($term !== '') {
            $termLower = mb_strtolower($term);
            $exactProductIds = DB::table('productos')
                ->whereRaw('LOWER(TRIM(codigo_producto)) = ?', [$termLower])
                ->orWhereRaw('LOWER(TRIM(item)) = ?', [$termLower])
                ->pluck('id');

            if ($exactProductIds->isNotEmpty()) {
                $exactActivitiesCount = (clone $query)
                    ->whereIn('productos.id', $exactProductIds)
                    ->count();

                if ($exactActivitiesCount > 0) {
                    $query->whereIn('productos.id', $exactProductIds);
                    $restrictedToExactProduct = true;
                }
            }
        }

        if (! $restrictedToExactProduct && ($term !== '' || $hasCaracteristicas)) {
            $like = '%' . $term . '%';

            $query->where(function ($q) use ($term, $like, $hasCaracteristicas, $capa, $vitola, $tipoEmpaque) {
                $hasTextFilter = false;

                if ($term !== '') {
                    $q->where(function ($q) use ($like) {
                        $q->where('actividades.nombre', 'like', $like)
                            ->orWhere('actividades.codigo_actividad', 'like', $like)
                            ->orWhere('tipo_empaques.nombre', 'like', $like)
                            ->orWhere('productos.nombre', 'like', $like)
                            ->orWhere('productos.codigo_producto', 'like', $like)
                            ->orWhere('productos.item', 'like', $like);
                    });

                    $hasTextFilter = true;
                }

                if ($hasCaracteristicas) {
                    $caracteristicasFilter = function ($q) use ($capa, $vitola, $tipoEmpaque) {
                        $q->whereRaw('LOWER(TRIM(capas.nombre)) = ?', [$capa])
                            ->whereRaw('LOWER(TRIM(vitolas.nombre)) = ?', [$vitola])
                            ->whereRaw('LOWER(TRIM(producto_tipo_empaques.nombre)) = ?', [$tipoEmpaque]);
                    };

                    if ($hasTextFilter) {
                        $q->orWhere($caracteristicasFilter);
                    } else {
                        $q->where($caracteristicasFilter);
                    }
                }
            });
        }

        $activities = $query
            ->orderBy('actividades.nombre')
            ->orderBy('productos.nombre')
            ->limit($limit)
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'api_id_actividad' => $activity->api_id_actividad,
                'codigo_actividad' => $activity->codigo_actividad,
                'nombre' => $activity->nombre,
                'tipo_empaque' => $activity->tipo_empaque,
                'precio_mo' => (string) $activity->precio_mo,
                'producto_id' => $activity->producto_id,
                'codigo_producto' => $activity->codigo_producto,
                'item' => $activity->item,
                'producto_nombre' => $activity->producto_nombre,
            ]);

        return response()->json([
            'message' => 'Actividades encontradas.',
            'activities' => $activities,
        ]);
    }
}
