<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->with('permissions')
            ->orderByDesc('permissions_count')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function show(Role $role)
    {
        $role->load('permissions');

        $permissions = $role->permissions;

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

        return view('roles.show', compact('role', 'grupos'));
    }

    public function create()
{
    $permissions = Permission::orderBy('name')->get();

    $grupos = $this->agruparPermisos($permissions);

    return view('roles.create', compact('grupos'));
}

public function store(Request $request)
{
    $request->validate([
        'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
        'permissions' => ['nullable', 'array'],
        'permissions.*' => ['exists:permissions,name'],
    ]);

    if ($request->name === 'SuperAdmin') {
        return redirect()
            ->route('roles.create')
            ->with('error', 'No puedes crear otro rol SuperAdmin.');
    }

    $role = Role::create([
        'name' => $request->name,
        'guard_name' => 'web',
    ]);

    $role->syncPermissions($request->permissions ?? []);

    return redirect()
        ->route('roles.index')
        ->with('success', 'Rol creado correctamente.');
}
    public function editPermissions(Role $role)
{
    if ($role->name === 'SuperAdmin') {
        return redirect()
            ->route('roles.index')
            ->with('error', 'No puedes editar los permisos del SuperAdmin.');
    }

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

    $permisosAsignados = $role->permissions->pluck('name')->toArray();

    return view('roles.edit-permissions', compact('role', 'grupos', 'permisosAsignados'));
}

private function agruparPermisos($permissions)
{
    return [
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
}
public function updatePermissions(Request $request, Role $role)
{
    if ($role->name === 'SuperAdmin') {
        return redirect()
            ->route('roles.index')
            ->with('error', 'No puedes modificar los permisos del SuperAdmin.');
    }

    $request->validate([
        'permissions' => ['nullable', 'array'],
        'permissions.*' => ['exists:permissions,name'],
    ]);

    $role->syncPermissions($request->permissions ?? []);

    return redirect()
        ->route('roles.show', $role)
        ->with('success', 'Permisos actualizados correctamente.');
}
public function destroy(Role $role)
{
    $rolesProtegidos = [
        'SuperAdmin',
        'Admin',
        'Supervisor',
        'Digitalizador',
        'Operador',
    ];

    if (in_array($role->name, $rolesProtegidos)) {
        return redirect()
            ->route('roles.index')
            ->with('error', 'No puedes eliminar un rol base del sistema.');
    }

    if ($role->users()->count() > 0) {
        return redirect()
            ->route('roles.index')
            ->with('error', 'No puedes eliminar este rol porque tiene usuarios asignados.');
    }

    $role->delete();

    return redirect()
        ->route('roles.index')
        ->with('success', 'Rol eliminado correctamente.');
}

}