<?php

namespace App\Http\Controllers;

use App\Models\VinetaPorOrden;
use App\Support\PerPageOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VinetaPorOrdenController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $marca = trim((string) $request->get('marca', ''));
        $nombre = trim((string) $request->get('nombre', ''));
        $codigoProducto = trim((string) $request->get('codigo_producto', ''));
        $item = trim((string) $request->get('item', ''));
        $ordenDelSistema = trim((string) $request->get('orden_del_sistema', ''));
        $ordenCliente = trim((string) $request->get('orden_cliente', ''));
        $orden = $request->get('orden', 'id');
        $direccion = $request->get('direccion', 'desc');

        $ordenesPermitidos = [
            'id',
            'codigo_qr',
            'api_id',
            'fecha',
            'presentacion',
            'item',
            'orden_del_sistema',
            'marca',
            'nombre',
            'capa',
            'vitola',
            'tipo_empaque',
            'codigo_producto',
            'mes',
            'orden',
            'cantidad_puros',
            'estado',
        ];

        if (! in_array($orden, $ordenesPermitidos, true)) {
            $orden = 'id';
        }

        if (! in_array($direccion, ['asc', 'desc'], true)) {
            $direccion = 'desc';
        }

        $presentacionPorCodigo = DB::table('productos')
            ->join('presentaciones', 'presentaciones.id', '=', 'productos.presentacion_id')
            ->selectRaw('productos.codigo_producto, MIN(presentaciones.nombre) as presentacion')
            ->whereNotNull('productos.codigo_producto')
            ->groupBy('productos.codigo_producto');

        $columnaOrden = $orden === 'presentacion'
            ? 'presentaciones_producto.presentacion'
            : 'vinetas_por_orden.'.$orden;

        $query = VinetaPorOrden::query()
            ->leftJoinSub($presentacionPorCodigo, 'presentaciones_producto', function ($join) {
                $join->on('presentaciones_producto.codigo_producto', '=', 'vinetas_por_orden.codigo_producto');
            })
            ->select([
                'vinetas_por_orden.id',
                'vinetas_por_orden.codigo_qr',
                'vinetas_por_orden.api_id',
                'vinetas_por_orden.fecha',
                'presentaciones_producto.presentacion',
                'vinetas_por_orden.marca',
                'vinetas_por_orden.nombre',
                'vinetas_por_orden.capa',
                'vinetas_por_orden.vitola',
                'vinetas_por_orden.tipo_empaque',
                'vinetas_por_orden.codigo_producto',
                'vinetas_por_orden.item',
                'vinetas_por_orden.orden_del_sistema',
                'vinetas_por_orden.mes',
                'vinetas_por_orden.orden',
                'vinetas_por_orden.cantidad_puros',
                'vinetas_por_orden.estado',
            ])
            ->when($buscar !== '', function ($query) use ($buscar) {
                $cleanBuscar = ltrim($buscar, '#');
                $query->where(function ($query) use ($buscar, $cleanBuscar) {
                    $query->where('vinetas_por_orden.codigo_qr', 'like', "%{$buscar}%")
                        ->orWhere('vinetas_por_orden.orden', 'like', "%{$buscar}%")
                        ->orWhere('vinetas_por_orden.orden_del_sistema', 'like', "%{$buscar}%");

                    if (ctype_digit($cleanBuscar)) {
                        $query->orWhere('vinetas_por_orden.id', (int) $cleanBuscar)
                            ->orWhere('vinetas_por_orden.api_id', (int) $cleanBuscar);
                    }
                });
            })
            ->when($marca !== '', function ($query) use ($marca) {
                $query->where('vinetas_por_orden.marca', 'like', "%{$marca}%");
            })
            ->when($nombre !== '', function ($query) use ($nombre) {
                $query->where('vinetas_por_orden.nombre', 'like', "%{$nombre}%");
            })
            ->when($codigoProducto !== '', function ($query) use ($codigoProducto) {
                $query->where('vinetas_por_orden.codigo_producto', 'like', "%{$codigoProducto}%");
            })
            ->when($item !== '', function ($query) use ($item) {
                $query->where('vinetas_por_orden.item', 'like', "%{$item}%");
            })
            ->when($ordenDelSistema !== '', function ($query) use ($ordenDelSistema) {
                $query->where('vinetas_por_orden.orden_del_sistema', 'like', "%{$ordenDelSistema}%");
            })
            ->when($ordenCliente !== '', function ($query) use ($ordenCliente) {
                $query->where('vinetas_por_orden.orden', 'like', "%{$ordenCliente}%");
            })
            ->orderBy($columnaOrden, $direccion);

        $perPageInput = $request->get('per_page', 10);
        $perPageSelected = 10;

        $vinetasPorOrden = $query
            ->toBase()
            ->paginate(function (int $total) use ($perPageInput, &$perPageSelected) {
                $perPageSelected = PerPageOptions::resolve($perPageInput, $total, 10);

                return PerPageOptions::pageSize($perPageSelected, $total);
            })
            ->appends($request->query());

        $perPageOptions = PerPageOptions::forTotal($vinetasPorOrden->total());

        if ($request->ajax()) {
            return view('vinetas-por-orden.partials.tabla', compact(
                'vinetasPorOrden',
                'orden',
                'direccion',
                'perPageOptions',
                'perPageSelected'
            ))->render();
        }

        return view('vinetas-por-orden.index', compact(
            'vinetasPorOrden',
            'orden',
            'direccion',
            'perPageOptions',
            'perPageSelected'
        ));
    }
}
