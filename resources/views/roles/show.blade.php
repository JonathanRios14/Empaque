<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Permisos del Rol | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800 transition-colors duration-300">

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: false
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Permisos del rol',
            'description' => 'Detalle de permisos asignados al rol ' . $role->name,
        ])

        <section class="app-content-compact">
            <div class="w-full max-w-[1600px] mx-auto space-y-6">

                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-[#5b3a1e] text-white flex items-center justify-center text-xl font-bold">
                                {{ strtoupper(substr($role->name, 0, 1)) }}
                            </div>

                            <div>
                                <h2 class="theme-title text-xl font-bold text-[#3b2818]">
                                    {{ $role->name }}
                                </h2>

                                <p class="theme-text text-sm text-gray-500">
                                    {{ $role->permissions->count() }} permiso(s) asignado(s)
                                </p>
                            </div>
                        </div>

                        @if ($role->name === 'SuperAdmin')
                            <span class="theme-button-secondary px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-semibold border border-gray-200 w-fit">
                                Rol protegido
                            </span>
                        @else
                            <a href="{{ route('roles.permissions.edit', $role) }}"
                               class="gooey-action px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm hover:bg-[#3b2818] transition w-fit">
                                Editar permisos
                            </a>
                        @endif
                    </div>
                </div>

                <div x-data="{ openGrupo: null }" class="space-y-4">
                    @foreach ($grupos as $nombreGrupo => $permisosGrupo)
                        @if ($permisosGrupo->count())
                            <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                                <button type="button"
                                        @click="openGrupo === '{{ $nombreGrupo }}' ? openGrupo = null : openGrupo = '{{ $nombreGrupo }}'"
                                        class="w-full flex items-center justify-between px-6 py-4 hover:bg-[#faf7f2] transition dark-hover-safe">

                                    <div class="text-left">
                                        <h3 class="theme-title font-bold text-[#3b2818]">
                                            {{ $nombreGrupo }}
                                        </h3>

                                        <p class="theme-text text-sm text-gray-500">
                                            {{ $permisosGrupo->count() }} permiso(s)
                                        </p>
                                    </div>

                                    <span class="text-[#5b3a1e] font-bold text-xl"
                                          x-text="openGrupo === '{{ $nombreGrupo }}' ? '−' : '+'"></span>
                                </button>

                                <div x-show="openGrupo === '{{ $nombreGrupo }}'"
                                     x-transition
                                     class="px-6 pb-5 border-t border-[#eee3d5] theme-border"
                                     style="display: none;">

                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach ($permisosGrupo as $permission)
                                            <div class="theme-soft flex items-center justify-between rounded-xl bg-[#f9f5ee] border border-[#eee3d5] theme-border px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="w-2 h-2 rounded-full bg-[#5b3a1e]"></span>

                                                    <span class="theme-title text-sm font-medium text-gray-700">
                                                        {{ $permission->name }}
                                                    </span>
                                                </div>

                                                <span class="theme-text text-xs text-gray-400">
                                                    ID {{ $permission->id }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        @endif
                    @endforeach
                </div>

                @if ($role->permissions->count() === 0)
                    <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm p-8 text-center">
                        <p class="theme-text text-gray-500">
                            Este rol no tiene permisos asignados.
                        </p>
                    </div>
                @endif

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

</body>
</html>
