<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\VinetaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    
// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('permission:dashboard.ver')
    ->name('dashboard');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // Usuarios
    Route::get('/usuarios', [UserController::class, 'index'])
        ->middleware('permission:usuarios.ver')
        ->name('usuarios.index');

    Route::get('/usuarios/crear', [UserController::class, 'create'])
        ->middleware('permission:usuarios.crear')
        ->name('usuarios.create');

    Route::post('/usuarios', [UserController::class, 'store'])
        ->middleware('permission:usuarios.crear')
        ->name('usuarios.store');

    Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])
        ->middleware('permission:usuarios.editar')
        ->name('usuarios.edit');

    Route::put('/usuarios/{user}', [UserController::class, 'update'])
        ->middleware('permission:usuarios.editar')
        ->name('usuarios.update');

    Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])
        ->middleware('permission:usuarios.editar')
        ->name('usuarios.toggle-status');

  // Roles
Route::get('/roles', [RoleController::class, 'index'])
    ->middleware('permission:roles.ver')
    ->name('roles.index');

Route::get('/roles/crear', [RoleController::class, 'create'])
    ->middleware('permission:roles.crear')
    ->name('roles.create');

Route::post('/roles', [RoleController::class, 'store'])
    ->middleware('permission:roles.crear')
    ->name('roles.store');

    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
    ->middleware('permission:roles.eliminar')
    ->name('roles.destroy');

Route::get('/roles/{role}/permisos', [RoleController::class, 'show'])
    ->middleware('permission:roles.ver')
    ->name('roles.show');

Route::get('/roles/{role}/permisos/editar', [RoleController::class, 'editPermissions'])
    ->middleware('permission:roles.editar')
    ->name('roles.permissions.edit');

Route::put('/roles/{role}/permisos', [RoleController::class, 'updatePermissions'])
    ->middleware('permission:roles.editar')
    ->name('roles.permissions.update');

    // Permisos
    Route::get('/permisos', [PermissionController::class, 'index'])
        ->middleware('permission:roles.ver')
        ->name('permisos.index');

        // Catalogos
        
        Route::get('/catalogos/productos', [CatalogoController::class, 'productos'])
    ->middleware('permission:productos.ver')
    ->name('catalogos.productos.index');

Route::post('/catalogos/productos/sincronizar', [CatalogoController::class, 'sincronizar'])
    ->middleware('permission:productos.sincronizar')
    ->name('catalogos.productos.sincronizar');

    Route::get('/catalogos/productos/{producto}', [CatalogoController::class, 'showProducto'])
    ->middleware('permission:productos.ver')
    ->name('catalogos.productos.show');

    Route::get('/catalogos/marcas', [CatalogoController::class, 'marcas'])
    ->middleware('permission:marcas.ver')
    ->name('catalogos.marcas.index');

Route::get('/catalogos/vitolas', [CatalogoController::class, 'vitolas'])
    ->middleware('permission:vitolas.ver')
    ->name('catalogos.vitolas.index');

Route::get('/catalogos/capas', [CatalogoController::class, 'capas'])
    ->middleware('permission:capas.ver')
    ->name('catalogos.capas.index');

Route::get('/catalogos/actividades', [CatalogoController::class, 'actividades'])
    ->middleware('permission:actividades.ver')
    ->name('catalogos.actividades.index');

    Route::get('/catalogos/empresas', [CatalogoController::class, 'empresas'])
    ->middleware('permission:catalogos.ver')
    ->name('catalogos.empresas.index');

Route::get('/catalogos/presentaciones', [CatalogoController::class, 'presentaciones'])
    ->middleware('permission:catalogos.ver')
    ->name('catalogos.presentaciones.index');

Route::get('/catalogos/tipos-empaque', [CatalogoController::class, 'tipoEmpaques'])
    ->middleware('permission:catalogos.ver')
    ->name('catalogos.tipo-empaques.index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/empleados', [EmpleadoController::class, 'index'])
        ->name('empleados.index');

    Route::post('/empleados/sincronizar', [EmpleadoController::class, 'sincronizar'])
        ->name('empleados.sincronizar');

    Route::get('/vinetas', [VinetaController::class, 'index'])
        ->name('vinetas.index');

    Route::post('/vinetas/sincronizar', [VinetaController::class, 'sincronizar'])
        ->name('vinetas.sincronizar');
});

Route::fallback(function () {
    if (auth()->check()) {
        return response()->view('errors.404', [
            'forceLayout' => true,
        ], 404);
    }

    return response()->view('errors.404', [
        'forceLayout' => false,
    ], 404);
});




require __DIR__.'/auth.php';
