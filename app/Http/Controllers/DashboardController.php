<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Capa;
use App\Models\Empresa;
use App\Models\Marca;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\TipoEmpaque;
use App\Models\User;
use App\Models\Vitola;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $totales = [
            'productos' => Producto::count(),
            'empresas' => Empresa::count(),
            'marcas' => Marca::count(),
            'vitolas' => Vitola::count(),
            'capas' => Capa::count(),
            'presentaciones' => Presentacion::count(),
            'tipo_empaques' => TipoEmpaque::count(),
            'actividades' => Actividad::count(),

            'usuarios' => User::count(),
            'usuarios_activos' => User::where('is_active', true)->count(),
            'usuarios_inactivos' => User::where('is_active', false)->count(),
            'roles' => Role::count(),
        ];

        $ultimosProductos = Producto::with(['marca', 'vitola', 'capa', 'tipoEmpaque'])
            ->latest()
            ->take(5)
            ->get();

        $ultimosUsuarios = User::with('roles')
            ->latest()
            ->take(5)
            ->get();

        $topMarcas = Marca::withCount('productos')
            ->orderByDesc('productos_count')
            ->take(5)
            ->get();

        $topTiposEmpaque = TipoEmpaque::withCount('productos')
            ->orderByDesc('productos_count')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totales',
            'ultimosProductos',
            'ultimosUsuarios',
            'topMarcas',
            'topTiposEmpaque'
        ));
    }
}