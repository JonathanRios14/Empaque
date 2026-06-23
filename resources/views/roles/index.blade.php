<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Roles | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800 transition-colors duration-300">

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: false,
    seguridad: true,
    produccion: false
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Roles',
            'description' => 'Gestión de roles y permisos asignados.'
        ])

        <section class="p-4 lg:p-6">
            <div class="w-full max-w-[1600px] mx-auto">

                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#e5d8c7] theme-border flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h2 class="theme-title text-lg font-bold text-[#3b2818]">
                                Listado de roles
                            </h2>

                            <p class="theme-text text-sm text-gray-500">
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

                            <thead class="theme-table-head bg-[#f3efe7] text-[#3b2818]">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">
                                        Rol
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        Usuarios
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        Permisos
                                    </th>

                                    <th class="px-6 py-4 text-right font-semibold">
                                        Estado
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @php
                                    $rolesProtegidos = ['SuperAdmin', 'Admin', 'Supervisor', 'Digitalizador', 'Operador'];
                                @endphp

                                @forelse ($roles as $role)
                                    <tr class="theme-row border-b border-gray-100 theme-border hover:bg-[#faf7f2] transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-[#5b3a1e] text-white flex items-center justify-center font-bold shadow-sm">
                                                    {{ strtoupper(substr($role->name, 0, 1)) }}
                                                </div>

                                                <div>
                                                    <p class="theme-title font-semibold text-[#3b2818]">
                                                        {{ $role->name }}
                                                    </p>

                                                    <p class="theme-text text-xs text-gray-500">
                                                        {{ in_array($role->name, $rolesProtegidos) ? 'Rol base del sistema' : 'Rol personalizado' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $role->users_count }} usuario(s)
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $role->permissions_count }} permiso(s)
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('roles.show', $role) }}"
                                                   class="theme-button-secondary px-3 py-1.5 rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] hover:bg-[#e5d8c7] transition">
                                                    Ver permisos
                                                </a>

                                                @if (in_array($role->name, $rolesProtegidos))
                                                    <span class="theme-button-secondary px-3 py-1.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-semibold border border-gray-200">
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
                                @empty
                                    <tr>
                                        <td colspan="4" class="theme-text px-6 py-10 text-center text-gray-500">
                                            No hay roles registrados.
                                        </td>
                                    </tr>
                                @endforelse
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