<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Permisos | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800">

<div x-data="{ sidebarOpen: true, catalogos: false }" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Permisos',
            'description' => 'Permisos del sistema agrupados por módulo.',
        ])

        <section class="p-6">
            <div class="max-w-6xl mx-auto">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                    <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm p-5">
                        <p class="text-sm text-gray-500">Total de permisos</p>
                        <h2 class="text-3xl font-bold text-[#5b3a1e] mt-1">
                            {{ $permissions->count() }}
                        </h2>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm p-5">
                        <p class="text-sm text-gray-500">Módulos</p>
                        <h2 class="text-3xl font-bold text-[#5b3a1e] mt-1">
                            {{ collect($grupos)->filter(fn($grupo) => $grupo->count())->count() }}
                        </h2>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm p-5">
                        <p class="text-sm text-gray-500">Administración</p>
                        <h2 class="text-lg font-bold text-[#5b3a1e] mt-2">
                            Roles y accesos
                        </h2>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-[#e5d8c7]">
                        <h2 class="text-lg font-bold text-[#3b2818]">
                            Listado de permisos
                        </h2>
                        <p class="text-sm text-gray-500">
                            Estos permisos se asignan a los roles para controlar el acceso del sistema.
                        </p>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            @foreach ($grupos as $nombreGrupo => $permisosGrupo)
                                @if ($permisosGrupo->count())
                                    <div class="rounded-2xl bg-[#f9f5ee] border border-[#e5d8c7] p-5">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="font-bold text-[#3b2818]">
                                                {{ $nombreGrupo }}
                                            </h3>

                                            <span class="px-3 py-1 rounded-full bg-white text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $permisosGrupo->count() }} permiso(s)
                                            </span>
                                        </div>

                                        <div class="space-y-2">
                                            @foreach ($permisosGrupo as $permission)
                                                <div class="flex items-center justify-between bg-white rounded-xl border border-[#eee3d5] px-4 py-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-2 h-2 rounded-full bg-[#5b3a1e]"></span>
                                                        <span class="text-sm text-gray-700">
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
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

</body>
</html>