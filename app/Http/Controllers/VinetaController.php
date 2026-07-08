<?php

namespace App\Http\Controllers;

use App\Models\Vineta;
use App\Services\VinetaApiService;
use Illuminate\Http\Request;

class VinetaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->get('buscar');
        $estado = $request->get('estado');
        $impreso = $request->get('impreso');
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
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('api_id', $buscar)
                        ->orWhere('id_pendiente_empaque', 'like', "%{$buscar}%")
                        ->orWhere('item', 'like', "%{$buscar}%")
                        ->orWhere('orden_del_sistema', 'like', "%{$buscar}%")
                        ->orWhere('mes', 'like', "%{$buscar}%")
                        ->orWhere('orden', 'like', "%{$buscar}%")
                        ->orWhere('marca', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('capa', 'like', "%{$buscar}%")
                        ->orWhere('vitola', 'like', "%{$buscar}%")
                        ->orWhere('tipo_empaque', 'like', "%{$buscar}%")
                        ->orWhere('codigo_producto', 'like', "%{$buscar}%");
                });
            })
            ->when($estado, function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->when($impreso !== null && $impreso !== '', function ($query) use ($impreso) {
                $query->where('impreso', (bool) $impreso);
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

        $estados = Vineta::query()
            ->whereNotNull('estado')
            ->select('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado');

        if ($request->ajax()) {
            return view('vinetas.partials.tabla', compact(
                'vinetas',
                'orden',
                'direccion'
            ))->render();
        }

        return view('vinetas.index', compact(
            'vinetas',
            'estados',
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
}
