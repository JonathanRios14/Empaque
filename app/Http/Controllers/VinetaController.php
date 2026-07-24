<?php

namespace App\Http\Controllers;

use App\Models\Vineta;
use App\Services\VinetaApiService;
use Illuminate\Http\Request;

class VinetaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->get('buscar', ''));
        $codigoProducto = trim((string) $request->get('codigo_producto', ''));
        $item = trim((string) $request->get('item', ''));
        $ordenDelSistema = trim((string) $request->get('orden_del_sistema', ''));
        $orden = $request->get('orden', 'api_id');
        $direccion = $request->get('direccion', 'desc');

        $ordenesPermitidos = [
            'api_id',
            'fecha',
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
            'impreso',
        ];

        if (! in_array($orden, $ordenesPermitidos, true)) {
            $orden = 'api_id';
        }

        if (! in_array($direccion, ['asc', 'desc'], true)) {
            $direccion = 'desc';
        }

        $query = Vineta::query()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where('api_id', ltrim($buscar, '#'));
            })
            ->when($codigoProducto !== '', function ($query) use ($codigoProducto) {
                $query->where('codigo_producto', 'like', "%{$codigoProducto}%");
            })
            ->when($item !== '', function ($query) use ($item) {
                $query->where('item', 'like', "%{$item}%");
            })
            ->when($ordenDelSistema !== '', function ($query) use ($ordenDelSistema) {
                $query->where('orden_del_sistema', 'like', "%{$ordenDelSistema}%");
            })
            ->orderBy($orden, $direccion);

        $perPageInput = $request->get('per_page', 10);

        if ($perPageInput === 'all') {
            $perPage = max((clone $query)->count(), 1);
        } else {
            $perPage = (int) $perPageInput;

            if (! in_array($perPage, [10, 25, 50, 100], true)) {
                $perPage = 10;
            }
        }

        $vinetas = $query
            ->paginate($perPage)
            ->appends($request->query());

        if ($request->ajax()) {
            return view('vinetas.partials.tabla', compact(
                'vinetas',
                'orden',
                'direccion'
            ))->render();
        }

        return view('vinetas.index', compact(
            'vinetas',
            'orden',
            'direccion'
        ));
    }

    public function sincronizar(VinetaApiService $vinetaApiService)
    {
        $resultado = $vinetaApiService->sincronizar();

        if (! $resultado['ok']) {
            return redirect()
                ->back()
                ->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->back()
            ->with('success', $resultado['mensaje'] . " Procesadas: {$resultado['total']}. Omitidas: {$resultado['omitidos']}.");
    }

    public function notificaciones(Request $request, VinetaApiService $vinetaApiService)
    {
        return response()->json(
            $vinetaApiService->resumenImpresasNuevas($request->boolean('force'))
        );
    }
}
