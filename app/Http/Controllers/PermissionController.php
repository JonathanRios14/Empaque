<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->get();

        $grupos = [
            'Dashboard' => $permissions->filter(fn($p) => str_starts_with($p->name, 'dashboard.')),

            'Usuarios' => $permissions->filter(fn($p) => str_starts_with($p->name, 'usuarios.')),

            'Roles' => $permissions->filter(fn($p) => str_starts_with($p->name, 'roles.')),

            'Catálogos' => $permissions->filter(fn($p) =>
                str_starts_with($p->name, 'catalogos.') ||
                str_starts_with($p->name, 'productos.') ||
                str_starts_with($p->name, 'marcas.') ||
                str_starts_with($p->name, 'vitolas.') ||
                str_starts_with($p->name, 'capas.') ||
                str_starts_with($p->name, 'actividades.')
            ),

            'Cajones' => $permissions->filter(fn($p) => str_starts_with($p->name, 'cajones.')),

            'Anillado' => $permissions->filter(fn($p) => str_starts_with($p->name, 'anillado.')),

            'Llenado' => $permissions->filter(fn($p) => str_starts_with($p->name, 'llenado.')),

            'Reportes' => $permissions->filter(fn($p) => str_starts_with($p->name, 'reportes.')),

            'Configuración' => $permissions->filter(fn($p) => str_starts_with($p->name, 'configuracion.')),
        ];

        return view('permisos.index', compact('grupos', 'permissions'));
    }
}