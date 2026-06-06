<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roles | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800">

<div x-data="{ sidebarOpen: true, catalogos: false }" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

    @include('layouts.topbar', [
    'title' => 'Roles',
    'description' => 'Gestión de roles y permisos asignados.'
])

        <section class="p-6">
            <div class="max-w-6xl mx-auto space-y-6">

                <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-[#e5d8c7] flex items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-[#3b2818]">
            Listado de roles
        </h2>

        <p class="text-sm text-gray-500">
            Roles ordenados del que tiene más permisos al que tiene menos.
        </p>
    </div>

    @can('roles.crear')
        <a href="{{ route('roles.create') }}"
           class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
            Nuevo rol
        </a>
    @endcan
</div>


                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#f3efe7] text-[#3b2818]">
                                <tr>
                                    <th class="px-6 py-4 text-left">Rol</th>
                                    <th class="px-6 py-4 text-left">Usuarios</th>
                                    <th class="px-6 py-4 text-left">Permisos</th>
                                    <th class="px-6 py-4 text-right">Estado</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($roles as $role)
                                    <tr class="border-b border-gray-100 hover:bg-[#faf7f2] transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-[#5b3a1e] text-white flex items-center justify-center font-bold">
                                                    {{ strtoupper(substr($role->name, 0, 1)) }}
                                                </div>

                                                <div>
                                                    <p class="font-semibold text-[#3b2818]">
                                                        {{ $role->name }}
                                                    </p>

                                                    <p class="text-xs text-gray-500">
                                                        @php
    $rolesProtegidos = ['SuperAdmin', 'Admin', 'Supervisor', 'Digitalizador', 'Operador'];
@endphp

{{ in_array($role->name, $rolesProtegidos) ? 'Rol base del sistema' : 'Rol personalizado' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $role->users_count }} usuario(s)
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $role->permissions_count }} permiso(s)
                                            </span>
                                        </td>

                                      <td class="px-6 py-4 text-right">
    <div class="flex justify-end gap-2">
    <a href="{{ route('roles.show', $role) }}"
       class="px-3 py-1.5 rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold hover:bg-[#e5d8c7] transition">
        Ver permisos
    </a>

  @php
    $rolesProtegidos = ['SuperAdmin', 'Admin', 'Supervisor', 'Digitalizador', 'Operador'];
@endphp

@if (in_array($role->name, $rolesProtegidos))
    <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-semibold">
        Protegido
    </span>
@else
    @can('roles.eliminar')
        <form method="POST"
              action="{{ route('roles.destroy', $role) }}"
              class="form-eliminar-rol">
            @csrf
            @method('DELETE')

            <button type="submit"
                    class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-xs font-semibold hover:bg-red-100 transition">
                Eliminar
            </button>
        </form>
    @endcan
@endif
</div>
</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

               

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

</body>
</html>