<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .stat-card {
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 35px rgba(59, 40, 24, .10);
            border-color: #d8c6a3;
        }

        .chart-bar {
            width: 0%;
            transition: width 1.1s ease;
        }

        .dashboard-ring {
            background:
                conic-gradient(#5b3a1e var(--porcentaje), #f3efe7 0);
        }

        .soft-panel {
            background:
                radial-gradient(circle at top right, rgba(91, 58, 30, .10), transparent 35%),
                linear-gradient(135deg, #ffffff 0%, #fbf8f3 100%);
        }
    </style>
</head>

<body class="bg-[#f5f2ec] text-gray-800">

@php
    $totalUsuarios = max($totales['usuarios'] ?? 0, 1);
    $usuariosActivosPorcentaje = round((($totales['usuarios_activos'] ?? 0) / $totalUsuarios) * 100);
    $usuariosInactivosPorcentaje = round((($totales['usuarios_inactivos'] ?? 0) / $totalUsuarios) * 100);

    $catalogoValores = [
        'Productos' => $totales['productos'] ?? 0,
        'Marcas' => $totales['marcas'] ?? 0,
        'Empresas' => $totales['empresas'] ?? 0,
        'Vitolas' => $totales['vitolas'] ?? 0,
        'Capas' => $totales['capas'] ?? 0,
        'Actividades' => $totales['actividades'] ?? 0,
    ];

    $maxCatalogo = max(max($catalogoValores), 1);
@endphp

<div x-data="{ sidebarOpen: true, catalogos: false, seguridad: false, produccion: false }" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Dashboard',
            'description' => 'Panel general del sistema de empaque.'
        ])

        <section class="p-6">
            <div class="max-w-7xl mx-auto space-y-6">

                {{-- HERO --}}
                <div class="soft-panel rounded-3xl border border-[#e5d8c7] shadow-sm p-7 overflow-hidden">
                    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] mb-4">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                Sistema activo
                            </div>

                            <h1 class="text-3xl font-extrabold text-[#3b2818]">
                                Buenos días, {{ Auth::user()->name }}
                            </h1>

                            <p class="text-sm text-gray-500 mt-2 max-w-2xl">
                                Panel general para supervisar catálogos sincronizados, usuarios activos y datos principales del sistema.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                    Rol: {{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}
                                </span>

                                <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-gray-600 text-xs font-semibold border border-[#e5d8c7]">
                                    {{ now()->format('d/m/Y') }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('catalogos.productos.index') }}"
                               class="px-5 py-3 rounded-2xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                Ver catálogo
                            </a>

                            @can('usuarios.ver')
                                <a href="{{ route('usuarios.index') }}"
                                   class="px-5 py-3 rounded-2xl bg-white text-[#5b3a1e] text-sm font-semibold border border-[#e5d8c7] hover:bg-[#f3efe7] transition">
                                    Ver usuarios
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>

               {{-- CONTADORES --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

    <a href="{{ route('catalogos.productos.index') }}"
       class="stat-card bg-white rounded-3xl border border-[#e5d8c7] shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-[#24160d]">Productos</p>
                <h2 class="text-4xl font-extrabold text-[#24160d] mt-1 counter"
                    data-count="{{ $totales['productos'] ?? 0 }}">0</h2>
            </div>

            <div class="w-14 h-14 rounded-2xl bg-[#5b3a1e] text-white flex items-center justify-center shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        <p class="text-xs text-gray-400 mt-4">
            Registros sincronizados desde la API.
        </p>
    </a>

    <a href="{{ route('catalogos.marcas.index') }}"
       class="stat-card bg-white rounded-3xl border border-[#e5d8c7] shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-[#24160d]">Marcas</p>
                <h2 class="text-4xl font-extrabold text-[#24160d] mt-1 counter"
                    data-count="{{ $totales['marcas'] ?? 0 }}">0</h2>
            </div>

            <div class="w-14 h-14 rounded-2xl bg-[#8a5a2b] text-white flex items-center justify-center shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M7 7h.01M3 11l8.5-8.5a2.121 2.121 0 013 0L21 9l-9 9-9-7z" />
                </svg>
            </div>
        </div>

        <p class="text-xs text-gray-400 mt-4">
            Marcas normalizadas del catálogo.
        </p>
    </a>

    @can('usuarios.ver')
        <a href="{{ route('usuarios.index') }}"
           class="stat-card bg-white rounded-3xl border border-[#e5d8c7] shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-bold text-[#24160d]">Usuarios</p>
                    <h2 class="text-4xl font-extrabold text-[#24160d] mt-1 counter"
                        data-count="{{ $totales['usuarios'] ?? 0 }}">0</h2>
                </div>

                <div class="w-14 h-14 rounded-2xl bg-[#24160d] text-white flex items-center justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m8-5a4 4 0 1 0-8 0 4 4 0 0 0 8 0zm-8 0a4 4 0 1 0-8 0 4 4 0 0 0 8 0z" />
                    </svg>
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-4">
                Usuarios registrados en el sistema.
            </p>
        </a>
    @endcan

    @can('roles.ver')
        <a href="{{ route('roles.index') }}"
           class="stat-card bg-white rounded-3xl border border-[#e5d8c7] shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-bold text-[#24160d]">Roles</p>
                    <h2 class="text-4xl font-extrabold text-[#24160d] mt-1 counter"
                        data-count="{{ $totales['roles'] ?? 0 }}">0</h2>
                </div>

                <div class="w-14 h-14 rounded-2xl bg-[#c9a66b] text-white flex items-center justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6 21v-2a6 6 0 0 1 12 0v2M19 8l1.5 1.5L23 7" />
                    </svg>
                </div>
            </div>

            <p class="text-xs text-gray-400 mt-4">
                Roles y permisos configurados.
            </p>
        </a>
    @endcan

</div>

                {{-- GRÁFICOS PRINCIPALES --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                    {{-- GRÁFICO CATÁLOGOS --}}
                    <div class="xl:col-span-2 bg-white rounded-3xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-[#3b2818]">
                                    Distribución de catálogos
                                </h2>
                                <p class="text-sm text-gray-500">
                                    Comparación visual de los datos principales normalizados.
                                </p>
                            </div>

                            <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                {{ $totales['productos'] ?? 0 }} productos
                            </span>
                        </div>

                        <div class="p-6 space-y-5">
                            @foreach ($catalogoValores as $label => $valor)
                                @php
                                    $porcentaje = round(($valor / $maxCatalogo) * 100);
                                @endphp

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-semibold text-[#3b2818]">
                                            {{ $label }}
                                        </span>

                                        <span class="text-sm text-gray-500 counter"
                                              data-count="{{ $valor }}">0</span>
                                    </div>

                                    <div class="h-3 rounded-full bg-[#f3efe7] overflow-hidden">
                                        <div class="chart-bar h-full rounded-full bg-[#5b3a1e]"
                                             data-width="{{ $porcentaje }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- GRÁFICO USUARIOS --}}
                    <div class="bg-white rounded-3xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7]">
                            <h2 class="text-lg font-bold text-[#3b2818]">
                                Estado de usuarios
                            </h2>
                            <p class="text-sm text-gray-500">
                                Usuarios activos e inactivos.
                            </p>
                        </div>

                        <div class="p-6">
                            <div class="mx-auto w-44 h-44 rounded-full dashboard-ring flex items-center justify-center"
                                 style="--porcentaje: {{ $usuariosActivosPorcentaje }}%;">
                                <div class="w-28 h-28 rounded-full bg-white flex flex-col items-center justify-center border border-[#e5d8c7]">
                                    <span class="text-3xl font-extrabold text-[#3b2818] counter"
                                          data-count="{{ $usuariosActivosPorcentaje }}">0</span>
                                    <span class="text-xs text-gray-500">% activos</span>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-[#5b3a1e]"></span>
                                        <span class="text-sm text-gray-600">Activos</span>
                                    </div>

                                    <span class="font-bold text-[#3b2818] counter"
                                          data-count="{{ $totales['usuarios_activos'] ?? 0 }}">0</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-[#f3efe7] border border-[#d8c6a3]"></span>
                                        <span class="text-sm text-gray-600">Inactivos</span>
                                    </div>

                                    <span class="font-bold text-[#3b2818] counter"
                                          data-count="{{ $totales['usuarios_inactivos'] ?? 0 }}">0</span>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-[#e5d8c7]">
                                    <span class="text-sm text-gray-600">Total</span>
                                    <span class="font-bold text-[#3b2818] counter"
                                          data-count="{{ $totales['usuarios'] ?? 0 }}">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- RESUMEN DE CATÁLOGOS Y USUARIOS --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                    <div class="xl:col-span-2 bg-white rounded-3xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7]">
                            <h2 class="text-lg font-bold text-[#3b2818]">
                                Resumen de catálogos
                            </h2>

                            <p class="text-sm text-gray-500">
                                Accesos rápidos a los catálogos principales.
                            </p>
                        </div>

                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                            <a href="{{ route('catalogos.empresas.index') }}"
                               class="rounded-2xl border border-[#e5d8c7] bg-[#fbf8f3] p-4 hover:bg-[#f3efe7] transition">
                                <p class="text-sm text-gray-500">Empresas</p>
                                <p class="text-2xl font-bold text-[#3b2818] counter"
                                   data-count="{{ $totales['empresas'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.vitolas.index') }}"
                               class="rounded-2xl border border-[#e5d8c7] bg-[#fbf8f3] p-4 hover:bg-[#f3efe7] transition">
                                <p class="text-sm text-gray-500">Vitolas</p>
                                <p class="text-2xl font-bold text-[#3b2818] counter"
                                   data-count="{{ $totales['vitolas'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.capas.index') }}"
                               class="rounded-2xl border border-[#e5d8c7] bg-[#fbf8f3] p-4 hover:bg-[#f3efe7] transition">
                                <p class="text-sm text-gray-500">Capas</p>
                                <p class="text-2xl font-bold text-[#3b2818] counter"
                                   data-count="{{ $totales['capas'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.presentaciones.index') }}"
                               class="rounded-2xl border border-[#e5d8c7] bg-[#fbf8f3] p-4 hover:bg-[#f3efe7] transition">
                                <p class="text-sm text-gray-500">Presentaciones</p>
                                <p class="text-2xl font-bold text-[#3b2818] counter"
                                   data-count="{{ $totales['presentaciones'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.tipo-empaques.index') }}"
                               class="rounded-2xl border border-[#e5d8c7] bg-[#fbf8f3] p-4 hover:bg-[#f3efe7] transition">
                                <p class="text-sm text-gray-500">Tipos de empaque</p>
                                <p class="text-2xl font-bold text-[#3b2818] counter"
                                   data-count="{{ $totales['tipo_empaques'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.actividades.index') }}"
                               class="rounded-2xl border border-[#e5d8c7] bg-[#fbf8f3] p-4 hover:bg-[#f3efe7] transition">
                                <p class="text-sm text-gray-500">Actividades</p>
                                <p class="text-2xl font-bold text-[#3b2818] counter"
                                   data-count="{{ $totales['actividades'] ?? 0 }}">0</p>
                            </a>

                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7]">
                            <h2 class="text-lg font-bold text-[#3b2818]">
                                Usuarios del sistema
                            </h2>
                            <p class="text-sm text-gray-500">
                                Resumen de acceso y seguridad.
                            </p>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Activos</span>
                                <span class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-200 counter"
                                      data-count="{{ $totales['usuarios_activos'] ?? 0 }}">0</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Inactivos</span>
                                <span class="px-3 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold border border-red-200 counter"
                                      data-count="{{ $totales['usuarios_inactivos'] ?? 0 }}">0</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Roles</span>
                                <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] counter"
                                      data-count="{{ $totales['roles'] ?? 0 }}">0</span>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- TOPS --}}
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                    <div class="bg-white rounded-3xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7]">
                            <h2 class="text-lg font-bold text-[#3b2818]">
                                Marcas con más productos
                            </h2>
                            <p class="text-sm text-gray-500">
                                Top 5 marcas según cantidad de productos.
                            </p>
                        </div>

                        <div class="p-6 space-y-4">
                            @forelse ($topMarcas as $marca)
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-[#3b2818]">
                                            {{ $marca->nombre }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $marca->empresa?->nombre ?? 'Sin empresa' }}
                                        </p>
                                    </div>

                                    <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                        {{ $marca->productos_count }} producto(s)
                                    </span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">
                                    No hay marcas registradas.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7]">
                            <h2 class="text-lg font-bold text-[#3b2818]">
                                Tipos de empaque más usados
                            </h2>
                            <p class="text-sm text-gray-500">
                                Top 5 tipos de empaque según productos relacionados.
                            </p>
                        </div>

                        <div class="p-6 space-y-4">
                            @forelse ($topTiposEmpaque as $tipo)
                                <div class="flex items-center justify-between gap-4">
                                    <p class="font-semibold text-[#3b2818]">
                                        {{ $tipo->nombre }}
                                    </p>

                                    <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                        {{ $tipo->productos_count }} producto(s)
                                    </span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">
                                    No hay tipos de empaque registrados.
                                </p>
                            @endforelse
                        </div>
                    </div>

                </div>

                {{-- RECIENTES --}}
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                    <div class="bg-white rounded-3xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7] flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-[#3b2818]">
                                    Últimos productos
                                </h2>
                                <p class="text-sm text-gray-500">
                                    Productos sincronizados recientemente.
                                </p>
                            </div>

                            <a href="{{ route('catalogos.productos.index') }}"
                               class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                Ver
                            </a>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @forelse ($ultimosProductos as $producto)
                                <a href="{{ route('catalogos.productos.show', $producto) }}"
                                   class="block p-4 hover:bg-[#faf7f2] transition">
                                    <p class="font-semibold text-[#3b2818]">
                                        {{ $producto->nombre ?? 'Sin nombre' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $producto->marca?->nombre ?? 'N/A' }}
                                        · {{ $producto->vitola?->nombre ?? 'N/A' }}
                                        · {{ $producto->tipoEmpaque?->nombre ?? 'N/A' }}
                                    </p>
                                </a>
                            @empty
                                <p class="p-6 text-sm text-gray-500">
                                    No hay productos recientes.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7] flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-[#3b2818]">
                                    Últimos usuarios
                                </h2>
                                <p class="text-sm text-gray-500">
                                    Usuarios registrados recientemente.
                                </p>
                            </div>

                            @can('usuarios.ver')
                                <a href="{{ route('usuarios.index') }}"
                                   class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                    Ver
                                </a>
                            @endcan
                        </div>

                        <div class="divide-y divide-gray-100">
                            @forelse ($ultimosUsuarios as $usuario)
                                <div class="p-4 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-[#3b2818]">
                                            {{ $usuario->name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $usuario->email }}
                                            · {{ $usuario->roles->first()?->name ?? 'Sin rol' }}
                                        </p>
                                    </div>

                                    @if ($usuario->is_active)
                                        <span class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-200">
                                            Activo
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold border border-red-200">
                                            Inactivo
                                        </span>
                                    @endif
                                </div>
                            @empty
                                <p class="p-6 text-sm text-gray-500">
                                    No hay usuarios recientes.
                                </p>
                            @endforelse
                        </div>
                    </div>

                </div>

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const counters = document.querySelectorAll('.counter');

        counters.forEach(counter => {
            const target = parseInt(counter.dataset.count || '0', 10);
            const duration = 900;
            const startTime = performance.now();

            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const value = Math.floor(progress * target);

                counter.textContent = value.toLocaleString('es-HN');

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    counter.textContent = target.toLocaleString('es-HN');
                }
            };

            requestAnimationFrame(animate);
        });

        const bars = document.querySelectorAll('.chart-bar');

        setTimeout(() => {
            bars.forEach(bar => {
                const width = bar.dataset.width || 0;
                bar.style.width = width + '%';
            });
        }, 150);
    });
</script>

</body>
</html>