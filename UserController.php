<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
public function index(Request $request)
{
    $orden = $request->get('orden', 'created_at');
    $direccion = $request->get('direccion', 'desc');

    $columnasPermitidas = ['name', 'email', 'created_at', 'is_active'];

    if (! in_array($orden, $columnasPermitidas)) {
        $orden = 'created_at';
    }

    if (! in_array($direccion, ['asc', 'desc'])) {
        $direccion = 'desc';
    }

    $query = User::with('roles');

    if ($request->filled('buscar')) {
        $buscar = $request->buscar;

        $query->where(function ($q) use ($buscar) {
            $q->where('name', 'like', "%{$buscar}%")
              ->orWhere('email', 'like', "%{$buscar}%")
              ->orWhere('id', $buscar);
        });
    }

    // SuperAdmin siempre primero
    $query->orderByRaw("
        CASE 
            WHEN EXISTS (
                SELECT 1 
                FROM model_has_roles 
                INNER JOIN roles ON roles.id = model_has_roles.role_id
                WHERE model_has_roles.model_id = users.id
                AND model_has_roles.model_type = ?
                AND roles.name = 'SuperAdmin'
            ) THEN 0 
            ELSE 1 
        END
    ", [User::class]);

    $usuarios = $query
        ->orderBy($orden, $direccion)
        ->paginate(10)
        ->appends($request->query());

    return view('usuarios.index', compact('usuarios', 'orden', 'direccion'));
}

    public function create()
    {
$roles = Role::where('name', '!=', 'SuperAdmin')
    ->orderBy('name')
    ->get();
        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,name', 'not_in:SuperAdmin'],
        ]);

        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $usuario->assignRole($request->role);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        if ($user->hasRole('SuperAdmin') && auth()->id() !== $user->id) {
    return redirect()
        ->route('usuarios.index')
        ->with('error', 'No puedes editar el usuario SuperAdmin.');
}

$roles = Role::where('name', '!=', 'SuperAdmin')
    ->orderBy('name')
    ->get();
        return view('usuarios.edit', compact('user', 'roles'));
    }

   public function update(Request $request, User $user)
{
    if ($user->hasRole('SuperAdmin') && auth()->id() !== $user->id) {
        return redirect()
            ->route('usuarios.index')
            ->with('error', 'No puedes actualizar el usuario SuperAdmin.');
    }

    $rules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email,' . $user->id],
        'password' => ['nullable', 'string', 'min:8', 'confirmed'],
    ];

    if (! $user->hasRole('SuperAdmin')) {
        $rules['role'] = ['required', 'exists:roles,name', 'not_in:SuperAdmin'];
    }

    $request->validate($rules);

    $user->name = $request->name;
    $user->email = $request->email;

    if ($request->filled('password')) {
        $user->password = Hash::make($request->password);
    }

    $user->save();

    if (! $user->hasRole('SuperAdmin')) {
        $user->syncRoles([$request->role]);
    }

    return redirect()
        ->route('usuarios.index')
        ->with('success', 'Usuario actualizado correctamente.');
}

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

public function toggleStatus(User $user)
{
    if (auth()->id() === $user->id) {
        return redirect()
            ->route('usuarios.index')
            ->with('error', 'No puedes desactivar tu propio usuario.');
    }

    if ($user->hasRole('SuperAdmin')) {
        return redirect()
            ->route('usuarios.index')
            ->with('error', 'No puedes desactivar un usuario SuperAdmin.');
    }

    $user->is_active = ! $user->is_active;
    $user->save();

    $mensaje = $user->is_active
        ? 'Usuario activado correctamente.'
        : 'Usuario desactivado correctamente.';

    return redirect()
        ->route('usuarios.index')
        ->with('success', $mensaje);
}

}
