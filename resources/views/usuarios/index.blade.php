<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios | Sistema de Empaque</title>
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
            'title' => 'Usuarios',
            'description' => 'Gestión de usuarios del sistema'
        ])

        <section class="p-4 lg:p-6">

            <div class="theme-card theme-shadow w-full max-w-[1600px] mx-auto bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                <div class="p-6 border-b border-[#e5d8c7] theme-border">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h2 class="theme-title text-lg font-bold text-[#3b2818]">
                                Listado de usuarios
                            </h2>

                            <p class="theme-text text-sm text-gray-500">
                                Aquí puedes ver los usuarios registrados.
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <form method="GET" action="{{ route('usuarios.index') }}" class="flex gap-2">
                                <div class="relative">
                                    <input type="text"
                                           name="buscar"
                                           value="{{ request('buscar') }}"
                                           placeholder="Buscar por nombre, correo o ID"
                                           class="theme-input w-72 rounded-xl border-gray-300 text-sm pr-10 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                                    @if(request('buscar'))
                                        <a href="{{ route('usuarios.index') }}"
                                           class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:text-[#5b3a1e] hover:bg-[#f3efe7] transition"
                                           title="Limpiar búsqueda">
                                            ×
                                        </a>
                                    @endif
                                </div>

                                <button type="submit"
                                        class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                    Buscar
                                </button>
                            </form>

                            @can('usuarios.crear')
                                <a href="{{ route('usuarios.create') }}"
                                   class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition text-center">
                                    Nuevo usuario
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>

                @php
                    function sortLinkUsuarios($campo) {
                        $ordenActual = request('orden', 'created_at');
                        $direccionActual = request('direccion', 'desc');

                        $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

                        return route('usuarios.index', array_merge(request()->query(), [
                            'orden' => $campo,
                            'direccion' => $nuevaDireccion,
                            'page' => null,
                        ]));
                    }

                    function sortIconUsuarios($campo) {
                        $ordenActual = request('orden', 'created_at');
                        $direccionActual = request('direccion', 'desc');

                        if ($ordenActual !== $campo) {
                            return '↕';
                        }

                        return $direccionActual === 'asc' ? '↑' : '↓';
                    }
                @endphp

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="theme-table-head bg-[#f3efe7] text-[#3b2818]">
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold">
                                    <a href="{{ sortLinkUsuarios('name') }}"
                                       class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                        Usuario
                                        <span class="text-xs">{{ sortIconUsuarios('name') }}</span>
                                    </a>
                                </th>

                                <th class="px-6 py-4 text-left font-semibold">
                                    Rol
                                </th>

                                <th class="px-6 py-4 text-left font-semibold">
                                    <a href="{{ sortLinkUsuarios('is_active') }}"
                                       class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                        Estado
                                        <span class="text-xs">{{ sortIconUsuarios('is_active') }}</span>
                                    </a>
                                </th>

                                <th class="px-6 py-4 text-left font-semibold">
                                    <a href="{{ sortLinkUsuarios('created_at') }}"
                                       class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                        Fecha
                                        <span class="text-xs">{{ sortIconUsuarios('created_at') }}</span>
                                    </a>
                                </th>

                                <th class="px-6 py-4 text-right font-semibold">
                                    Acciones
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($usuarios as $usuario)
                                <tr class="theme-row border-b border-gray-100 theme-border hover:bg-[#faf7f2] transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            @if ($usuario->photo)
                                                <img src="{{ asset('storage/' . $usuario->photo) }}"
                                                     class="w-11 h-11 rounded-full object-cover border-2 border-[#d8c6a3]"
                                                     alt="Foto">
                                            @else
                                                <div class="w-11 h-11 rounded-full bg-[#5b3a1e] text-white flex items-center justify-center font-bold border-2 border-[#d8c6a3]">
                                                    {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                                </div>
                                            @endif

                                            <div>
                                                <p class="theme-title font-semibold text-[#3b2818]">
                                                    {{ $usuario->name }}
                                                </p>

                                                <p class="theme-text text-xs text-gray-500">
                                                    {{ $usuario->email }}
                                                </p>

                                                <p class="theme-text text-[11px] text-gray-400 mt-0.5">
                                                    ID: {{ $usuario->id }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        @forelse ($usuario->roles as $role)
                                            <span class="theme-badge inline-flex items-center px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="theme-button-secondary inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-semibold border border-gray-200">
                                                Sin rol
                                            </span>
                                        @endforelse
                                    </td>

                                    <td class="px-6 py-4">
                                        @if ($usuario->is_active)
                                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-200">
                                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold border border-red-200">
                                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="theme-text text-gray-500">
                                            {{ $usuario->created_at->format('d/m/Y') }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            @can('usuarios.editar')
                                                @if ($usuario->hasRole('SuperAdmin'))
                                                    @if (auth()->id() === $usuario->id)
                                                        <a href="{{ route('usuarios.edit', $usuario) }}"
                                                           class="theme-button-secondary px-3 py-1.5 rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] hover:bg-[#e5d8c7] transition">
                                                            Editar
                                                        </a>
                                                    @else
                                                        <span class="theme-button-secondary px-3 py-1.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-semibold border border-gray-200">
                                                            No editable
                                                        </span>
                                                    @endif
                                                @else
                                                    <a href="{{ route('usuarios.edit', $usuario) }}"
                                                       class="theme-button-secondary px-3 py-1.5 rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] hover:bg-[#e5d8c7] transition">
                                                        Editar
                                                    </a>
                                                @endif
                                            @endcan

                                            @can('usuarios.editar')
                                                @if (! $usuario->hasRole('SuperAdmin') && auth()->id() !== $usuario->id)
                                                    <form method="POST"
                                                          action="{{ route('usuarios.toggle-status', $usuario) }}"
                                                          class="form-cambiar-estado-usuario">
                                                        @csrf
                                                        @method('PATCH')

                                                        <button type="submit"
                                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                                                                {{ $usuario->is_active
                                                                    ? 'bg-red-50 text-red-600 hover:bg-red-100'
                                                                    : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                                                            {{ $usuario->is_active ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="theme-button-secondary px-3 py-1.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-semibold border border-gray-200">
                                                        Protegido
                                                    </span>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="theme-text px-6 py-10 text-center text-gray-500">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="theme-soft px-6 py-4 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <p class="theme-text text-sm text-gray-500">
                        Mostrando
                        <span class="theme-title font-semibold text-[#3b2818]">{{ $usuarios->firstItem() ?? 0 }}</span>
                        a
                        <span class="theme-title font-semibold text-[#3b2818]">{{ $usuarios->lastItem() ?? 0 }}</span>
                        de
                        <span class="theme-title font-semibold text-[#3b2818]">{{ $usuarios->total() }}</span>
                        usuario(s)
                    </p>

                    <div class="pagination-cafe">
                        {{ $usuarios->onEachSide(1)->links('pagination.cafe') }}
                    </div>
                </div>

            </div>
        </section>

    </main>
</div>

@include('layouts.flash')

</body>
</html>