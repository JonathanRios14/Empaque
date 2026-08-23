<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\CatalogoApiService;
use App\Support\PerPageOptions;
use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Capa;
use App\Models\Marca;
use App\Models\Vitola;
use App\Models\Empresa;
use App\Models\Presentacion;
use App\Models\TipoEmpaque;
use Illuminate\Support\Facades\DB;
class CatalogoController extends Controller
{
  public function productos(Request $request)
{
    $orden = $request->get('orden', 'created_at');
    $direccion = $request->get('direccion', 'desc');

    $ordenesPermitidos = [
        'nombre',
        'item',
        'codigo_producto',
        'precio',
        'created_at',
        'marca',
        'vitola',
        'capa',
        'tipo_empaque',
        'actividades_count',
    ];

    if (! in_array($orden, $ordenesPermitidos)) {
        $orden = 'created_at';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'desc';
    }

    $query = Producto::query()
        ->select('productos.*')
        ->with([
            'empresa',
            'marca',
            'vitola',
            'capa',
            'presentacion',
            'tipoEmpaque',
            'actividades',
        ])
        ->withCount('actividades');

    if ($request->filled('buscar')) {
        $buscar = $request->buscar;

        $query->where(function ($q) use ($buscar) {
            $q->where('productos.nombre', 'like', "%{$buscar}%")
                ->orWhere('productos.item', 'like', "%{$buscar}%")
                ->orWhere('productos.codigo_producto', 'like', "%{$buscar}%")
                ->orWhere('productos.codigo_caja', 'like', "%{$buscar}%")
                ->orWhere('productos.codigo_precio', 'like', "%{$buscar}%")
                ->orWhere('productos.api_id_producto', $buscar)
                ->orWhereHas('marca', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%");
                })
                ->orWhereHas('vitola', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%");
                })
                ->orWhereHas('capa', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%");
                })
                ->orWhereHas('tipoEmpaque', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%");
                })
                ->orWhereHas('actividades', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                      ->orWhere('codigo_actividad', 'like', "%{$buscar}%");
                });
        });
    }

    if ($request->filled('marca_id')) {
        $query->where('productos.marca_id', $request->marca_id);
    }
    if ($request->filled('empresa_id')) {
    $query->where('productos.empresa_id', $request->empresa_id);
}

    if ($request->filled('vitola_id')) {
        $query->where('productos.vitola_id', $request->vitola_id);
    }

    if ($request->filled('capa_id')) {
        $query->where('productos.capa_id', $request->capa_id);
    }

    if ($request->filled('tipo_empaque_id')) {
        $query->where('productos.tipo_empaque_id', $request->tipo_empaque_id);
    }

    if ($request->filled('presentacion_id')) {
        $query->where('productos.presentacion_id', $request->presentacion_id);
    }

    if ($request->filled('actividad_id')) {
        $query->whereHas('actividades', function ($q) use ($request) {
            $q->where('actividades.id', $request->actividad_id);
        });
    }

    if ($orden === 'marca') {
        $query->leftJoin('marcas', 'productos.marca_id', '=', 'marcas.id')
            ->orderBy('marcas.nombre', $direccion);
    } elseif ($orden === 'vitola') {
        $query->leftJoin('vitolas', 'productos.vitola_id', '=', 'vitolas.id')
            ->orderBy('vitolas.nombre', $direccion);
    } elseif ($orden === 'capa') {
        $query->leftJoin('capas', 'productos.capa_id', '=', 'capas.id')
            ->orderBy('capas.nombre', $direccion);
    } elseif ($orden === 'tipo_empaque') {
        $query->leftJoin('tipo_empaques', 'productos.tipo_empaque_id', '=', 'tipo_empaques.id')
            ->orderBy('tipo_empaques.nombre', $direccion);
    } elseif ($orden === 'actividades_count') {
        $query->orderBy('actividades_count', $direccion);
    } else {
        $query->orderBy('productos.' . $orden, $direccion);
    }

$perPageInput = $request->get('per_page', 10);
$perPageSelected = 10;

$productos = $query
    ->paginate(function (int $total) use ($perPageInput, &$perPageSelected) {
        $perPageSelected = PerPageOptions::resolve($perPageInput, $total, 10);

        return PerPageOptions::pageSize($perPageSelected, $total);
    })
    ->appends($request->query());
$perPageOptions = PerPageOptions::forTotal($productos->total());

    $marcas = Marca::orderBy('nombre')->get();
    $vitolas = Vitola::orderBy('nombre')->get();
    $capas = Capa::orderBy('nombre')->get();
    $tipoEmpaques = TipoEmpaque::orderBy('nombre')->get();
    $presentaciones = Presentacion::orderBy('nombre')->get();
    $actividades = Actividad::orderBy('nombre')->get();

    if ($request->ajax()) {
    return view('catalogos.productos.partials.tabla', compact(
        'productos',
        'orden',
        'direccion',
        'marcas',
        'vitolas',
        'capas',
        'tipoEmpaques',
        'presentaciones',
        'actividades',
        'perPageOptions',
        'perPageSelected'
    ))->render();
}

return view('catalogos.productos.index', compact(
    'productos',
    'orden',
    'direccion',
    'marcas',
    'vitolas',
    'capas',
    'tipoEmpaques',
    'presentaciones',
    'actividades',
    'perPageOptions',
    'perPageSelected'
));
}

public function showProducto(Producto $producto)
{
    $producto->load([
        'empresa',
        'marca',
        'vitola',
        'capa',
        'presentacion',
        'tipoEmpaque',
        'actividades',
    ]);

    return view('catalogos.productos.show', compact('producto'));
}

public function marcas(Request $request)
{
    $orden = $request->get('orden', 'nombre');
    $direccion = $request->get('direccion', 'asc');

    $permitidos = ['id', 'nombre', 'productos_count'];

    if (! in_array($orden, $permitidos)) {
        $orden = 'nombre';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'asc';
    }

    $query = Marca::with('empresa')
        ->withCount('productos');

    if ($request->filled('buscar')) {
        $buscar = $request->buscar;

        $query->where(function ($q) use ($buscar) {
            $q->where('nombre', 'like', "%{$buscar}%")
              ->orWhereHas('empresa', function ($q) use ($buscar) {
                  $q->where('nombre', 'like', "%{$buscar}%");
              });
        });
    }

    $marcas = $query
        ->orderBy($orden, $direccion)
        ->paginate(10)
        ->appends($request->query());

    return view('catalogos.marcas.index', compact('marcas', 'orden', 'direccion'));
}

public function vitolas(Request $request)
{
    $orden = $request->get('orden', 'nombre');
    $direccion = $request->get('direccion', 'asc');

    $permitidos = ['id', 'nombre', 'productos_count'];

    if (! in_array($orden, $permitidos)) {
        $orden = 'nombre';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'asc';
    }

    $query = Vitola::withCount('productos');

    if ($request->filled('buscar')) {
        $query->where('nombre', 'like', "%{$request->buscar}%");
    }

    $vitolas = $query
        ->orderBy($orden, $direccion)
        ->paginate(10)
        ->appends($request->query());

    return view('catalogos.vitolas.index', compact('vitolas', 'orden', 'direccion'));
}

public function capas(Request $request)
{
    $orden = $request->get('orden', 'nombre');
    $direccion = $request->get('direccion', 'asc');

    $permitidos = ['id', 'nombre', 'productos_count'];

    if (! in_array($orden, $permitidos)) {
        $orden = 'nombre';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'asc';
    }

    $query = Capa::withCount('productos');

    if ($request->filled('buscar')) {
        $query->where('nombre', 'like', "%{$request->buscar}%");
    }

    $capas = $query
        ->orderBy($orden, $direccion)
        ->paginate(10)
        ->appends($request->query());

    return view('catalogos.capas.index', compact('capas', 'orden', 'direccion'));
}

public function actividades(Request $request)
{
    $orden = $request->get('orden', 'nombre');
    $direccion = $request->get('direccion', 'asc');

    $permitidos = ['id', 'codigo_actividad', 'nombre', 'productos_count', 'precio_mo'];

    if (! in_array($orden, $permitidos)) {
        $orden = 'nombre';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'asc';
    }

    $preciosSubquery = DB::table('actividad_producto')
        ->select([
            'actividad_id',
            DB::raw('MIN(precio_mo) as precio_min'),
            DB::raw('MAX(precio_mo) as precio_max'),
            DB::raw('COUNT(DISTINCT precio_mo) as precios_count'),
        ])
        ->groupBy('actividad_id');

    $query = Actividad::query()
        ->select([
            'actividades.*',
            'precios_actividades.precio_min',
            'precios_actividades.precio_max',
            'precios_actividades.precios_count',
        ])
        ->leftJoinSub($preciosSubquery, 'precios_actividades', function ($join) {
            $join->on('actividades.id', '=', 'precios_actividades.actividad_id');
        })
        ->withCount([
            'productos as productos_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT productos.id)'));
            }
        ]);

    if ($request->filled('buscar')) {
        $buscar = $request->buscar;

        $query->where(function ($q) use ($buscar) {
            $q->where('nombre', 'like', "%{$buscar}%")
              ->orWhere('codigo_actividad', 'like', "%{$buscar}%")
              ->orWhere('api_id_actividad', $buscar);
        });
    }

    $ordenQuery = $orden === 'precio_mo' ? 'precio_min' : $orden;

    $actividades = $query
        ->orderBy($ordenQuery, $direccion)
        ->paginate(10)
        ->appends($request->query());

    return view('catalogos.actividades.index', compact('actividades', 'orden', 'direccion'));
}

public function empresas(Request $request)
{
    $orden = $request->get('orden', 'nombre');
    $direccion = $request->get('direccion', 'asc');

    $permitidos = ['id', 'nombre', 'productos_count', 'marcas_count'];

    if (! in_array($orden, $permitidos)) {
        $orden = 'nombre';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'asc';
    }

    $query = Empresa::withCount(['productos', 'marcas']);

    if ($request->filled('buscar')) {
        $query->where('nombre', 'like', "%{$request->buscar}%");
    }

    $empresas = $query
        ->orderBy($orden, $direccion)
        ->paginate(10)
        ->appends($request->query());

    return view('catalogos.empresas.index', compact('empresas', 'orden', 'direccion'));
}

public function presentaciones(Request $request)
{
    $orden = $request->get('orden', 'nombre');
    $direccion = $request->get('direccion', 'asc');

    $permitidos = ['id', 'nombre', 'productos_count'];

    if (! in_array($orden, $permitidos)) {
        $orden = 'nombre';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'asc';
    }

    $query = Presentacion::withCount('productos');

    if ($request->filled('buscar')) {
        $query->where('nombre', 'like', "%{$request->buscar}%");
    }

    $presentaciones = $query
        ->orderBy($orden, $direccion)
        ->paginate(10)
        ->appends($request->query());

    return view('catalogos.presentaciones.index', compact('presentaciones', 'orden', 'direccion'));
}

public function tipoEmpaques(Request $request)
{
    $orden = $request->get('orden', 'nombre');
    $direccion = $request->get('direccion', 'asc');

    $permitidos = ['id', 'nombre', 'productos_count'];

    if (! in_array($orden, $permitidos)) {
        $orden = 'nombre';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'asc';
    }

    $query = TipoEmpaque::withCount('productos');

    if ($request->filled('buscar')) {
        $query->where('nombre', 'like', "%{$request->buscar}%");
    }

    $tipoEmpaques = $query
        ->orderBy($orden, $direccion)
        ->paginate(10)
        ->appends($request->query());

    return view('catalogos.tipo-empaques.index', compact('tipoEmpaques', 'orden', 'direccion'));
}

    public function sincronizar(CatalogoApiService $catalogoApiService)
    {
        $resultado = $catalogoApiService->sincronizar();

        if (! $resultado['ok']) {
            return redirect()
                ->route('catalogos.productos.index')
                ->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->route('catalogos.productos.index')
            ->with('success', sprintf(
                '%s Total: %s. Nuevos: %s. Actualizados: %s. Sin cambios: %s. Omitidos: %s.',
                $resultado['mensaje'],
                $resultado['total'] ?? 0,
                $resultado['nuevos'] ?? 0,
                $resultado['actualizados'] ?? 0,
                $resultado['sin_cambios'] ?? 0,
                $resultado['omitidos'] ?? 0,
            ));
    }

    
}
