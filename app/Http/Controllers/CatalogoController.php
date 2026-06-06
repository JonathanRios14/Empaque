<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\CatalogoApiService;
use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Capa;
use App\Models\Marca;
use App\Models\Vitola;
use App\Models\Empresa;
use App\Models\Presentacion;
use App\Models\TipoEmpaque;
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
        ]);

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
                });
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
    } else {
        $query->orderBy('productos.' . $orden, $direccion);
    }

    $productos = $query
        ->paginate(10)
        ->appends($request->query());

    return view('catalogos.productos.index', compact('productos', 'orden', 'direccion'));
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

    $permitidos = ['id', 'codigo_actividad', 'nombre', 'productos_count'];

    if (! in_array($orden, $permitidos)) {
        $orden = 'nombre';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'asc';
    }

    $query = Actividad::withCount('productos');

    if ($request->filled('buscar')) {
        $buscar = $request->buscar;

        $query->where(function ($q) use ($buscar) {
            $q->where('nombre', 'like', "%{$buscar}%")
              ->orWhere('codigo_actividad', 'like', "%{$buscar}%")
              ->orWhere('api_id_actividad', $buscar);
        });
    }

    $actividades = $query
        ->orderBy($orden, $direccion)
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
            ->with('success', $resultado['mensaje'] . ' Total: ' . $resultado['total']);
    }

    
}