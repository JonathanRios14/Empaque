<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .stat-card {
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 35px rgba(15, 23, 42, .10);
            border-color: #cbd5e1;
        }

        .soft-panel {
            background:
                radial-gradient(circle at top right, rgba(15, 23, 42, .08), transparent 35%),
                linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }

        html.dark-navy .soft-panel {
            background:
                radial-gradient(circle at top right, rgba(56, 189, 248, .10), transparent 35%),
                linear-gradient(135deg, #0f172a 0%, #111c33 100%);
        }

        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(28px);
            transition:
                opacity .7s ease,
                transform .7s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }

        .reveal-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 {
            transition-delay: .08s;
        }

        .reveal-delay-2 {
            transition-delay: .16s;
        }

        .reveal-delay-3 {
            transition-delay: .24s;
        }

        .reveal-delay-4 {
            transition-delay: .32s;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-gray-800 transition-colors duration-300">

@php
    $topMarcaLabels = $topMarcas->pluck('nombre')->values();
    $topMarcaData = $topMarcas->pluck('productos_count')->values();

    $hayTopMarcas = $topMarcas->count() > 0;
@endphp

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: false,
    seguridad: false,
    produccion: false
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Dashboard',
            'description' => 'Panel general del sistema de empaque.'
        ])

        <section class="p-4 lg:p-6">
            <div class="w-full max-w-[1600px] mx-auto space-y-6">

                {{-- HERO --}}
                <div class="soft-panel reveal-on-scroll rounded-3xl border theme-border shadow-sm p-7 overflow-hidden">
                    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white text-[#0b1220] text-xs font-semibold border theme-border mb-4">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                Sistema activo
                            </div>

                            <h1 class="theme-title text-3xl font-extrabold">
                                Buenos días, {{ Auth::user()->name }}
                            </h1>

                            <p class="theme-text text-sm mt-2 max-w-2xl">
                                Panel general para supervisar catálogos sincronizados, usuarios activos y datos principales del sistema.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border">
                                    Rol: {{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}
                                </span>

                                <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border">
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
                                   class="theme-button-secondary px-5 py-3 rounded-2xl bg-white text-[#0b1220] text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition">
                                    Ver usuarios
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- CONTADORES --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 reveal-on-scroll reveal-delay-1">

                    <a href="{{ route('catalogos.productos.index') }}"
                      class="kpi-card stat-card bg-white rounded-3xl border border-[#e5d8c7] shadow-sm theme-card theme-shadow p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="theme-title text-sm font-bold">Productos</p>
                                <h2 class="theme-title text-4xl font-extrabold mt-1 counter"
                                    data-count="{{ $totales['productos'] ?? 0 }}">0</h2>
                            </div>

                            <div class="w-14 h-14 rounded-2xl bg-[#5b3a1e] text-white flex items-center justify-center shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>

                        <p class="theme-text text-xs mt-4">
                            Registros sincronizados desde la API.
                        </p>
                    </a>

                    <a href="{{ route('catalogos.marcas.index') }}"
                      class="kpi-card stat-card bg-white rounded-3xl border border-[#e5d8c7] shadow-sm theme-card theme-shadow p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="theme-title text-sm font-bold">Marcas</p>
                                <h2 class="theme-title text-4xl font-extrabold mt-1 counter"
                                    data-count="{{ $totales['marcas'] ?? 0 }}">0</h2>
                            </div>

                            <div class="w-14 h-14 rounded-2xl bg-[#8a5a2b] text-white flex items-center justify-center shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M7 7h.01M3 11l8.5-8.5a2.121 2.121 0 013 0L21 9l-9 9-9-7z" />
                                </svg>
                            </div>
                        </div>

                        <p class="theme-text text-xs mt-4">
                            Marcas normalizadas del catálogo.
                        </p>
                    </a>

                    @can('usuarios.ver')
                        <a href="{{ route('usuarios.index') }}"
                          class="stat-card user-card bg-white rounded-3xl border border-[#e5d8c7] shadow-sm theme-card theme-shadow p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="theme-title text-sm font-bold">Usuarios</p>
                                    <h2 class="theme-title text-4xl font-extrabold mt-1 counter"
                                        data-count="{{ $totales['usuarios'] ?? 0 }}">0</h2>
                                </div>

                                <div class="w-14 h-14 rounded-2xl bg-[#24160d] text-white flex items-center justify-center shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m8-5a4 4 0 1 0-8 0 4 4 0 0 0 8 0zm-8 0a4 4 0 1 0-8 0 4 4 0 0 0 8 0z" />
                                    </svg>
                                </div>
                            </div>

                            <p class="theme-text text-xs mt-4">
                                Usuarios registrados en el sistema.
                            </p>
                        </a>
                    @endcan

                    @can('roles.ver')
                        <a href="{{ route('roles.index') }}"
                           class="kpi-card stat-card bg-white rounded-3xl border border-[#e5d8c7] shadow-sm theme-card theme-shadow p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="theme-title text-sm font-bold">Roles</p>
                                    <h2 class="theme-title text-4xl font-extrabold mt-1 counter"
                                        data-count="{{ $totales['roles'] ?? 0 }}">0</h2>
                                </div>

                                <div class="w-14 h-14 rounded-2xl bg-[#c9a66b] text-white flex items-center justify-center shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M6 21v-2a6 6 0 0 1 12 0v2M19 8l1.5 1.5L23 7" />
                                    </svg>
                                </div>
                            </div>

                            <p class="theme-text text-xs mt-4">
                                Roles y permisos configurados.
                            </p>
                        </a>
                    @endcan

                </div>

                {{-- GRÁFICOS PRINCIPALES --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 reveal-on-scroll reveal-delay-2">

                    {{-- RANKING MARCAS --}}
                    <div class="xl:col-span-2 bg-white rounded-3xl border border-[#e5d8c7] shadow-sm theme-card theme-shadow overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7] theme-border flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h2 class="theme-title text-lg font-bold">
                                    Ranking de marcas
                                </h2>

                                <p class="theme-text text-sm">
                                    Top 5 marcas según la cantidad de productos relacionados.
                                </p>
                            </div>

                            <a href="{{ route('catalogos.marcas.index') }}"
                               class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                Ver marcas
                            </a>
                        </div>

                        <div class="p-6">
                            @if ($hayTopMarcas)
                                <div id="rankingMarcasChart" class="w-full h-[380px]"></div>
                            @else
                                <div class="theme-soft rounded-2xl border theme-border p-10 text-center">
                                    <p class="theme-title font-bold">No hay marcas registradas.</p>
                                    <p class="theme-text text-sm mt-1">
                                        Cuando existan productos asociados, aquí aparecerá el ranking.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- GRÁFICO USUARIOS --}}
                    <div class="bg-white rounded-3xl border border-[#e5d8c7] shadow-sm theme-card theme-shadow overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7] theme-border">
                            <h2 class="theme-title text-lg font-bold">
                                Estado de usuarios
                            </h2>

                            <p class="theme-text text-sm">
                                Usuarios activos e inactivos.
                            </p>
                        </div>

                        <div class="p-6">
                            <div id="usuariosDonutChart" class="w-full h-[260px]"></div>

                            <div class="mt-4 space-y-3">
                                <div class="theme-soft flex items-center justify-between rounded-2xl border theme-border p-4">
                                    <span class="theme-text text-sm">Activos</span>
                                    <span class="theme-title font-bold counter"
                                          data-count="{{ $totales['usuarios_activos'] ?? 0 }}">0</span>
                                </div>

                                <div class="theme-soft flex items-center justify-between rounded-2xl border theme-border p-4">
                                    <span class="theme-text text-sm">Inactivos</span>
                                    <span class="theme-title font-bold counter"
                                          data-count="{{ $totales['usuarios_inactivos'] ?? 0 }}">0</span>
                                </div>

                                <div class="theme-soft flex items-center justify-between rounded-2xl border theme-border p-4">
                                    <span class="theme-text text-sm">Total</span>
                                    <span class="theme-title font-bold counter"
                                          data-count="{{ $totales['usuarios'] ?? 0 }}">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- RESUMEN DE CATÁLOGOS Y USUARIOS --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 reveal-on-scroll reveal-delay-3">

                    <div class="xl:col-span-2 bg-white rounded-3xl border border-[#e5d8c7] shadow-sm theme-card theme-shadow overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7] theme-border">
                            <h2 class="theme-title text-lg font-bold">
                                Resumen de catálogos
                            </h2>

                            <p class="theme-text text-sm">
                                Accesos rápidos a los catálogos principales.
                            </p>
                        </div>

                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                            <a href="{{ route('catalogos.empresas.index') }}"
                               class="theme-soft rounded-2xl border theme-border p-4 hover:bg-[#f3efe7] transition">
                                <p class="theme-text text-sm">Empresas</p>
                                <p class="theme-title text-2xl font-bold counter"
                                   data-count="{{ $totales['empresas'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.vitolas.index') }}"
                               class="theme-soft rounded-2xl border theme-border p-4 hover:bg-[#f3efe7] transition">
                                <p class="theme-text text-sm">Vitolas</p>
                                <p class="theme-title text-2xl font-bold counter"
                                   data-count="{{ $totales['vitolas'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.capas.index') }}"
                               class="theme-soft rounded-2xl border theme-border p-4 hover:bg-[#f3efe7] transition">
                                <p class="theme-text text-sm">Capas</p>
                                <p class="theme-title text-2xl font-bold counter"
                                   data-count="{{ $totales['capas'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.presentaciones.index') }}"
                               class="theme-soft rounded-2xl border theme-border p-4 hover:bg-[#f3efe7] transition">
                                <p class="theme-text text-sm">Presentaciones</p>
                                <p class="theme-title text-2xl font-bold counter"
                                   data-count="{{ $totales['presentaciones'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.tipo-empaques.index') }}"
                               class="theme-soft rounded-2xl border theme-border p-4 hover:bg-[#f3efe7] transition">
                                <p class="theme-text text-sm">Tipos de empaque</p>
                                <p class="theme-title text-2xl font-bold counter"
                                   data-count="{{ $totales['tipo_empaques'] ?? 0 }}">0</p>
                            </a>

                            <a href="{{ route('catalogos.actividades.index') }}"
                               class="theme-soft rounded-2xl border theme-border p-4 hover:bg-[#f3efe7] transition">
                                <p class="theme-text text-sm">Actividades</p>
                                <p class="theme-title text-2xl font-bold counter"
                                   data-count="{{ $totales['actividades'] ?? 0 }}">0</p>
                            </a>

                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-[#e5d8c7] shadow-sm theme-card theme-shadow overflow-hidden">
                        <div class="p-6 border-b border-[#e5d8c7] theme-border">
                            <h2 class="theme-title text-lg font-bold">
                                Usuarios del sistema
                            </h2>

                            <p class="theme-text text-sm">
                                Resumen de acceso y seguridad.
                            </p>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="theme-text text-sm">Activos</span>
                                <span class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-200 counter"
                                      data-count="{{ $totales['usuarios_activos'] ?? 0 }}">0</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="theme-text text-sm">Inactivos</span>
                                <span class="px-3 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold border border-red-200 counter"
                                      data-count="{{ $totales['usuarios_inactivos'] ?? 0 }}">0</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="theme-text text-sm">Roles</span>
                                <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border counter"
                                      data-count="{{ $totales['roles'] ?? 0 }}">0</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let countersStarted = false;
        let rankingChart = null;
        let usuariosChart = null;
        let chartsRendered = false;

        function animateCounters() {
            if (countersStarted) {
                return;
            }

            countersStarted = true;

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
        }

        function chartTheme() {
            const isDark = document.documentElement.classList.contains('dark-navy');

            return {
                isDark,
                text: isDark ? '#cbd5e1' : '#475569',
                muted: isDark ? '#94a3b8' : '#64748b',
                grid: isDark ? 'rgba(148, 163, 184, 0.14)' : '#e2e8f0',
                main: isDark ? '#38bdf8' : '#0b1220',
                soft: isDark ? '#334155' : '#cbd5e1',
                tooltip: isDark ? 'dark' : 'light',
                dataLabel: isDark ? '#0f172a' : '#ffffff'
            };
        }

        function renderCharts() {
            if (chartsRendered) {
                return;
            }

            chartsRendered = true;

            const t = chartTheme();

            const rankingElement = document.querySelector('#rankingMarcasChart');

            if (rankingElement) {
                rankingChart = new ApexCharts(rankingElement, {
                    chart: {
                        type: 'bar',
                        height: 380,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        foreColor: t.text,
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 900,
                            animateGradually: {
                                enabled: true,
                                delay: 160
                            },
                            dynamicAnimation: {
                                enabled: true,
                                speed: 450
                            }
                        }
                    },

                    series: [{
                        name: 'Productos',
                        data: @json($topMarcaData)
                    }],

                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 8,
                            barHeight: '58%',
                            distributed: false
                        }
                    },

                    xaxis: {
                        categories: @json($topMarcaLabels),
                        labels: {
                            style: {
                                colors: t.muted,
                                fontSize: '12px'
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },

                    yaxis: {
                        labels: {
                            style: {
                                colors: t.muted,
                                fontSize: '13px',
                                fontWeight: 600
                            }
                        }
                    },

                    colors: [t.main],

                    grid: {
                        borderColor: t.grid,
                        strokeDashArray: 4,
                        xaxis: {
                            lines: { show: true }
                        },
                        yaxis: {
                            lines: { show: false }
                        }
                    },

                    dataLabels: {
                        enabled: true,
                        formatter: function (value) {
                            return value + ' producto(s)';
                        },
                        style: {
                            colors: [t.dataLabel],
                            fontWeight: 700
                        }
                    },

                    tooltip: {
                        theme: t.tooltip,
                        y: {
                            formatter: function (value) {
                                return value.toLocaleString('es-HN') + ' producto(s)';
                            }
                        }
                    },

                    legend: {
                        show: false
                    }
                });

                rankingChart.render();
            }

            const usuariosElement = document.querySelector('#usuariosDonutChart');

            if (usuariosElement) {
                usuariosChart = new ApexCharts(usuariosElement, {
                    chart: {
                        type: 'donut',
                        height: 260,
                        fontFamily: 'inherit',
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 900
                        }
                    },

                    series: [
                        {{ $totales['usuarios_activos'] ?? 0 }},
                        {{ $totales['usuarios_inactivos'] ?? 0 }}
                    ],

                    labels: ['Activos', 'Inactivos'],

                    colors: [
                        t.main,
                        t.soft
                    ],

                    stroke: {
                        width: 0
                    },

                    plotOptions: {
                        pie: {
                            donut: {
                                size: '72%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true,
                                        color: t.muted,
                                        fontSize: '13px',
                                        fontWeight: 600
                                    },
                                    value: {
                                        show: true,
                                        color: t.text,
                                        fontSize: '24px',
                                        fontWeight: 800,
                                        formatter: function (value) {
                                            return parseInt(value).toLocaleString('es-HN');
                                        }
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        color: t.muted,
                                        fontSize: '13px',
                                        formatter: function () {
                                            return {{ $totales['usuarios'] ?? 0 }};
                                        }
                                    }
                                }
                            }
                        }
                    },

                    legend: {
                        show: true,
                        position: 'bottom',
                        labels: {
                            colors: t.muted
                        }
                    },

                    tooltip: {
                        theme: t.tooltip
                    },

                    dataLabels: {
                        enabled: false
                    }
                });

                usuariosChart.render();
            }
        }

        function updateChartsTheme() {
            const t = chartTheme();

            if (rankingChart) {
                rankingChart.updateOptions({
                    chart: {
                        foreColor: t.text
                    },
                    colors: [t.main],
                    xaxis: {
                        labels: {
                            style: {
                                colors: t.muted
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: t.muted
                            }
                        }
                    },
                    grid: {
                        borderColor: t.grid
                    },
                    dataLabels: {
                        style: {
                            colors: [t.dataLabel]
                        }
                    },
                    tooltip: {
                        theme: t.tooltip
                    }
                }, false, true);
            }

            if (usuariosChart) {
                usuariosChart.updateOptions({
                    colors: [
                        t.main,
                        t.soft
                    ],
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    name: {
                                        color: t.muted
                                    },
                                    value: {
                                        color: t.text
                                    },
                                    total: {
                                        color: t.muted
                                    }
                                }
                            }
                        }
                    },
                    legend: {
                        labels: {
                            colors: t.muted
                        }
                    },
                    tooltip: {
                        theme: t.tooltip
                    }
                }, false, true);
            }
        }

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');

                    if (entry.target.querySelector('.counter')) {
                        animateCounters();
                    }

                    if (
                        entry.target.querySelector('#rankingMarcasChart') ||
                        entry.target.querySelector('#usuariosDonutChart')
                    ) {
                        renderCharts();
                    }

                    revealObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.18,
            rootMargin: '0px 0px -60px 0px'
        });

        document.querySelectorAll('.reveal-on-scroll').forEach(element => {
            revealObserver.observe(element);
        });

        const firstVisible = document.querySelector('.reveal-on-scroll');

        if (firstVisible) {
            setTimeout(() => {
                firstVisible.classList.add('is-visible');
            }, 120);
        }

        const themeObserver = new MutationObserver(() => {
            updateChartsTheme();
        });

        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    });
</script>

</body>
</html>