<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Permisos del Rol | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800">

<div x-data="{ sidebarOpen: true, catalogos: false }" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Permisos del rol',
            'description' => 'Detalle de permisos asignados al rol ' . $role->name,
        
        ])

        <section class="p-6">
            <div class="max-w-6xl mx-auto space-y-6">

                <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-[#5b3a1e] text-white flex items-center justify-center text-xl font-bold">
                                {{ strtoupper(substr($role->name, 0, 1)) }}
                            </div>

                            <div>
                                <h2 class="text-xl font-bold text-[#3b2818]">
                                    {{ $role->name }}
                                </h2>

                                <p class="text-sm text-gray-500">
                                    {{ $role->permissions->count() }} permiso(s) asignado(s)
                                </p>
                            </div>
                        </div>

                       @if ($role->name === 'SuperAdmin')
    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-semibold">
        Rol protegido
    </span>
@else
    <a href="{{ route('roles.permissions.edit', $role) }}"
       class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm hover:bg-[#3b2818] transition">
        Editar permisos
    </a>
@endif
                    </div>
                </div>

               <div x-data="{ openGrupo: null }" class="space-y-4">
    @foreach ($grupos as $nombreGrupo => $permisosGrupo)
        @if ($permisosGrupo->count())
            <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                <button type="button"
                        @click="openGrupo === '{{ $nombreGrupo }}' ? openGrupo = null : openGrupo = '{{ $nombreGrupo }}'"
                        class="w-full flex items-center justify-between px-6 py-4 hover:bg-[#faf7f2] transition">

                  <div class="text-left">
    <h3 class="font-bold text-[#3b2818]">
        {{ $nombreGrupo }}
    </h3>

    <p class="text-sm text-gray-500">
        {{ $permisosGrupo->count() }} permiso(s)
    </p>
</div>
                    <span class="text-[#5b3a1e] font-bold text-xl"
                          x-text="openGrupo === '{{ $nombreGrupo }}' ? '−' : '+'"></span>
                </button>

                <div x-show="openGrupo === '{{ $nombreGrupo }}'"
                     x-transition
                     class="px-6 pb-5 border-t border-[#eee3d5]"
                     style="display: none;">

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($permisosGrupo as $permission)
                            <div class="flex items-center justify-between rounded-xl bg-[#f9f5ee] border border-[#eee3d5] px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-[#5b3a1e]"></span>

                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $permission->name }}
                                    </span>
                                </div>

                                <span class="text-xs text-gray-400">
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
                    <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm p-8 text-center text-gray-500">
                        Este rol no tiene permisos asignados.
                    </div>
                @endif

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

</body>
</html>