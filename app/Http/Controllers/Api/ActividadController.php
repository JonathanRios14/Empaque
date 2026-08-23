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
            'producto_nombre' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'in:general'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:80'],
        ]);

        $term = trim((string) ($data['q'] ?? ''));
        $capa = mb_strtolower(trim((string) ($data['capa'] ?? '')));
        $vitola = mb_strtolower(trim((string) ($data['vitola'] ?? '')));
        $tipoEmpaque = mb_strtolower(trim((string) ($data['tipo_empaque'] ?? '')));
        $productoNombre = mb_strtolower(trim((string) ($data['producto_nombre'] ?? '')));
        $hasCaracteristicas = $capa !== '' && $vitola !== '' && $tipoEmpaque !== '';
        $limit = (int) ($data['limit'] ?? 30);
        $searchTerms = $this->searchTerms($term);

        if (($data['scope'] ?? null) === 'general') {
            return response()->json([
                'message' => 'Actividades generales encontradas.',
                'activities' => $this->generalActivities($term, $limit),
            ]);
        }

        if ($term !== '' && $productoNombre !== '') {
            $contextActivities = $this->contextActivities(
                $searchTerms,
                $productoNombre,
                $tipoEmpaque,
                $limit
            );

            if ($contextActivities->isNotEmpty()) {
                return response()->json([
                    'message' => 'Actividades encontradas por producto similar.',
                    'activities' => $contextActivities,
                ]);
            }
        }

        $query = $this->baseQuery();

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
            $query->where(function ($q) use ($term, $searchTerms, $hasCaracteristicas, $capa, $vitola, $tipoEmpaque) {
                $hasTextFilter = false;

                if ($term !== '') {
                    $this->applySearchTerms($q, $searchTerms);

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

        $activities = $this->activitiesFromQuery($query, $limit);

        return response()->json([
            'message' => 'Actividades encontradas.',
            'activities' => $activities,
        ]);
    }

    private function generalActivities(string $term, int $limit)
    {
        $query = DB::table('actividades')
            ->select([
                'id',
                'api_id_actividad',
                'codigo_actividad',
                'nombre',
            ]);

        if ($term !== '') {
            $query->where(function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where('nombre', 'like', $like)
                    ->orWhere('codigo_actividad', 'like', $like);

                if (ctype_digit($term)) {
                    $query->orWhere('api_id_actividad', (int) $term);
                }
            });
        }

        return $query
            ->orderBy('nombre')
            ->orderBy('codigo_actividad')
            ->limit($limit)
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'api_id_actividad' => $activity->api_id_actividad,
                'codigo_actividad' => $activity->codigo_actividad,
                'nombre' => $activity->nombre,
                'tipo_empaque' => null,
                'precio_mo' => null,
                'producto_id' => null,
                'codigo_producto' => null,
                'item' => null,
                'producto_nombre' => null,
            ]);
    }

    private function baseQuery()
    {
        return DB::table('actividad_producto')
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
    }

    private function contextActivities(array $searchTerms, string $productoNombre, string $tipoEmpaque, int $limit)
    {
        if ($searchTerms === []) {
            return collect();
        }

        $tipoFamilia = $this->tipoEmpaqueFamily($tipoEmpaque);
        $attempts = [
            ['producto' => 'exact', 'empaque' => 'exact'],
            ['producto' => 'like', 'empaque' => 'exact'],
            ['producto' => 'exact', 'empaque' => 'familia'],
            ['producto' => 'like', 'empaque' => 'familia'],
            ['producto' => 'exact', 'empaque' => null],
            ['producto' => 'like', 'empaque' => null],
        ];

        foreach ($attempts as $attempt) {
            if ($attempt['empaque'] === 'exact' && $tipoEmpaque === '') {
                continue;
            }

            if ($attempt['empaque'] === 'familia' && $tipoFamilia === '') {
                continue;
            }

            $query = $this->baseQuery();
            $this->applySearchTerms($query, $searchTerms);

            if ($attempt['producto'] === 'exact') {
                $query->whereRaw('LOWER(TRIM(productos.nombre)) = ?', [$productoNombre]);
            } else {
                $query->whereRaw('LOWER(productos.nombre) like ?', ['%'.$productoNombre.'%']);
            }

            if ($attempt['empaque'] === 'exact') {
                $query->whereRaw('LOWER(TRIM(tipo_empaques.nombre)) = ?', [$tipoEmpaque]);
            } elseif ($attempt['empaque'] === 'familia') {
                $query->whereRaw('LOWER(TRIM(tipo_empaques.nombre)) like ?', [$tipoFamilia.'%']);
            }

            $activities = $this->activitiesFromQuery($query, $limit);

            if ($activities->isNotEmpty()) {
                return $activities;
            }
        }

        return collect();
    }

    private function applySearchTerms($query, array $searchTerms): void
    {
        $query->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $index => $searchTerm) {
                $like = '%'.$searchTerm.'%';
                $method = $index === 0 ? 'where' : 'orWhere';

                $q->{$method}(function ($q) use ($like) {
                    $q->where('actividades.nombre', 'like', $like)
                        ->orWhere('actividades.codigo_actividad', 'like', $like)
                        ->orWhere('tipo_empaques.nombre', 'like', $like)
                        ->orWhere('productos.nombre', 'like', $like)
                        ->orWhere('productos.codigo_producto', 'like', $like)
                        ->orWhere('productos.item', 'like', $like);
                });
            }
        });
    }

    private function activitiesFromQuery($query, int $limit)
    {
        return $query
            ->orderBy('actividades.nombre')
            ->orderBy('productos.nombre')
            ->orderBy('tipo_empaques.nombre')
            ->orderBy('productos.codigo_producto')
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
    }

    private function tipoEmpaqueFamily(string $tipoEmpaque): string
    {
        $parts = preg_split('/\s+/', $tipoEmpaque);

        return $parts[0] ?? '';
    }

    private function searchTerms(string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $terms = [$term];
        $normalized = mb_strtolower($term);

        if (str_contains($normalized, 'rezag') || str_contains($normalized, 'resag') || str_contains($normalized, 'rezad')) {
            $terms[] = 'rezag';
        }

        return array_values(array_unique($terms));
    }
}
