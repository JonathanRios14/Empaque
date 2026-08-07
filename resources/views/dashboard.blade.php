<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .metric-card {
            transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease;
        }

        .metric-card:hover {
            transform: translateY(-3px);
            border-color: rgba(11, 18, 32, .18);
            box-shadow: 0 18px 38px rgba(15, 23, 42, .08);
        }

        html.dark-navy .metric-card:hover {
            border-color: rgba(56, 189, 248, .26);
            box-shadow: none;
        }

        .production-hero {
            background: #ffffff;
        }

        html.dark-navy .production-hero {
            background: #0f172a;
        }

        .dashboard-primary-action {
            background: #0b1220;
            color: #ffffff !important;
        }

        .dashboard-primary-action:hover {
            background: #111c33;
            color: #ffffff !important;
        }

        html.dark-navy .dashboard-primary-action {
            background: #38bdf8;
            color: #0b1220 !important;
        }

        html.dark-navy .dashboard-primary-action:hover {
            background: #7dd3fc;
            color: #0b1220 !important;
        }

        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .55s ease, transform .55s ease;
        }

        .reveal-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 { transition-delay: .06s; }
        .reveal-delay-2 { transition-delay: .12s; }
        .reveal-delay-3 { transition-delay: .18s; }
    </style>
</head>

<body class="bg-[#f8fafc] text-gray-800 transition-colors duration-300">

@php
    $hayProduccion = ($produccionTotal['registros'] ?? 0) > 0 || ($produccionTotal['minutos_ordinarios'] ?? 0) > 0;
    $hayTendenciaDiaria = array_sum($tendenciaDiaria['actividades'] ?? []) > 0 || array_sum($tendenciaDiaria['horas'] ?? []) > 0;
    $hayTendenciaMensual = array_sum($tendenciaMensual['actividades'] ?? []) > 0 || array_sum($tendenciaMensual['horas'] ?? []) > 0;
    $hayProcesos = array_sum($distribucionProcesos['data'] ?? []) > 0;
@endphp

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: false,
    seguridad: false,
    produccion: true
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">
        @include('layouts.topbar', [
            'title' => 'Dashboard',
            'description' => 'Producción, rendimiento y seguimiento operativo.'
        ])

        <section class="app-content-compact">
            <div class="w-full max-w-[1600px] mx-auto space-y-6">

                <div class="production-hero reveal-on-scroll rounded-3xl border theme-border shadow-sm p-6 lg:p-7 overflow-hidden">
                    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
                        <div>
                            <div class="theme-badge inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border mb-4">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                Producción en vivo
                            </div>

                            <h1 class="theme-title text-3xl font-extrabold">
                                Buenos días, {{ Auth::user()->name }}
                            </h1>

                            <p class="theme-text text-sm mt-2 max-w-3xl">
                                Panel operativo para revisar producción diaria, avance mensual, rendimiento anual, empleados, actividades y procesos con datos reales de viñetas registradas.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border">
                                    Rol: {{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}
                                </span>

                                <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border">
                                    {{ $today->format('d/m/Y') }}
                                </span>

                                <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border">
                                    {{ number_format($produccionMes['empleados'] ?? 0) }} empleado(s) con producción este mes
                                </span>
                            </div>
                        </div>

                        <div class="min-w-full xl:min-w-[420px] space-y-3">
                            <div class="flex flex-wrap justify-end gap-3">
                                <a href="{{ route('vineta-registros.index') }}"
                                   class="dashboard-primary-action gooey-action inline-flex items-center justify-center px-5 py-3 rounded-2xl text-sm font-semibold transition">
                                    Ver registros
                                </a>

                                <a href="{{ route('vineta-registros.reporte-semanal') }}"
                                   class="theme-button-secondary gooey-action inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-white text-[#0b1220] text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition">
                                    Reporte semanal
                                </a>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div class="theme-soft rounded-2xl border theme-border p-4">
                                    <p class="theme-text text-xs font-semibold">Hoy</p>
                                    <p class="theme-title text-2xl font-extrabold counter" data-count="{{ $produccionHoy['actividades'] ?? 0 }}">0</p>
                                    <p class="theme-text text-xs">actividades</p>
                                </div>

                                <div class="theme-soft rounded-2xl border theme-border p-4">
                                    <p class="theme-text text-xs font-semibold">Mes</p>
                                    <p class="theme-title text-2xl font-extrabold counter" data-count="{{ $produccionMes['actividades'] ?? 0 }}">0</p>
                                    <p class="theme-text text-xs">actividades</p>
                                </div>

                                <div class="theme-soft rounded-2xl border theme-border p-4">
                                    <p class="theme-text text-xs font-semibold">Año</p>
                                    <p class="theme-title text-2xl font-extrabold counter" data-count="{{ $produccionAnio['actividades'] ?? 0 }}">0</p>
                                    <p class="theme-text text-xs">actividades</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @unless($hayProduccion)
                    <div class="reveal-on-scroll reveal-delay-1 theme-soft rounded-3xl border theme-border p-6 text-center">
                        <p class="theme-title text-lg font-bold">Aún no hay producción registrada.</p>
                        <p class="theme-text text-sm mt-1">Cuando se registren viñetas u horas ordinarias, este dashboard mostrará los indicadores de producción.</p>
                    </div>
                @endunless

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 reveal-on-scroll reveal-delay-1">
                    <div class="metric-card theme-card theme-shadow rounded-3xl border theme-border p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="theme-text text-sm font-bold">Actividades de hoy</p>
                                <h2 class="theme-title text-4xl font-extrabold mt-1 counter" data-count="{{ $produccionHoy['actividades'] ?? 0 }}">0</h2>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-[#0b1220] text-white flex items-center justify-center">
                                <span class="text-lg font-black">D</span>
                            </div>
                        </div>
                        <p class="theme-text text-xs mt-4">{{ number_format($produccionHoy['registros'] ?? 0) }} registros activos hoy.</p>
                    </div>

                    <div class="metric-card theme-card theme-shadow rounded-3xl border theme-border p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="theme-text text-sm font-bold">Puros de hoy</p>
                                <h2 class="theme-title text-4xl font-extrabold mt-1 counter" data-count="{{ $produccionHoy['puros'] ?? 0 }}">0</h2>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-[#2563eb] text-white flex items-center justify-center">
                                <span class="text-lg font-black">P</span>
                            </div>
                        </div>
                        <p class="theme-text text-xs mt-4">{{ number_format($produccionHoy['cajones'] ?? 0) }} cajones registrados.</p>
                    </div>

                    <div class="metric-card theme-card theme-shadow rounded-3xl border theme-border p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="theme-text text-sm font-bold">Cajones del mes</p>
                                <h2 class="theme-title text-4xl font-extrabold mt-1 counter" data-count="{{ $produccionMes['cajones'] ?? 0 }}">0</h2>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-[#0891b2] text-white flex items-center justify-center">
                                <span class="text-lg font-black">C</span>
                            </div>
                        </div>
                        <p class="theme-text text-xs mt-4">{{ number_format($produccionMes['puros'] ?? 0) }} puros producidos este mes.</p>
                    </div>

                    <div class="metric-card theme-card theme-shadow rounded-3xl border theme-border p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="theme-text text-sm font-bold">Empleados con producción</p>
                                <h2 class="theme-title text-4xl font-extrabold mt-1 counter" data-count="{{ $produccionMes['empleados'] ?? 0 }}">0</h2>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-[#16a34a] text-white flex items-center justify-center">
                                <span class="text-lg font-black">E</span>
                            </div>
                        </div>
                        <p class="theme-text text-xs mt-4">{{ number_format($produccionMes['registros'] ?? 0) }} registros activos este mes.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 reveal-on-scroll reveal-delay-2">
                    <div class="xl:col-span-2 theme-card theme-shadow rounded-3xl border theme-border overflow-hidden">
                        <div class="p-6 border-b theme-border flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h2 class="theme-title text-lg font-bold">Producción diaria del mes</h2>
                                <p class="theme-text text-sm">Actividades y horas trabajadas por día.</p>
                            </div>
                            <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border">{{ $today->format('F Y') }}</span>
                        </div>

                        <div class="p-6">
                            @if($hayTendenciaDiaria)
                                <div id="dailyProductionChart" class="w-full h-[360px]"></div>
                            @else
                                <div class="theme-soft rounded-2xl border theme-border p-10 text-center">
                                    <p class="theme-title font-bold">Sin datos diarios este mes.</p>
                                    <p class="theme-text text-sm mt-1">Aquí aparecerá el avance conforme se registren viñetas.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="theme-card theme-shadow rounded-3xl border theme-border overflow-hidden">
                        <div class="p-6 border-b theme-border">
                            <h2 class="theme-title text-lg font-bold">Procesos del mes</h2>
                            <p class="theme-text text-sm">Distribución por rezago, anillado y llenado.</p>
                        </div>

                        <div class="p-6">
                            @if($hayProcesos)
                                <div id="processChart" class="w-full h-[260px]"></div>
                                <div class="mt-4 space-y-3">
                                    @foreach($distribucionProcesos['rows'] as $proceso)
                                        <div class="theme-soft flex items-center justify-between rounded-2xl border theme-border p-3">
                                            <span class="theme-text text-sm font-semibold">{{ $proceso['grupo'] }}</span>
                                            <span class="theme-title text-sm font-extrabold">{{ number_format($proceso['actividades']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="theme-soft rounded-2xl border theme-border p-8 text-center">
                                    <p class="theme-title font-bold">Sin procesos este mes.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="theme-card theme-shadow rounded-3xl border theme-border overflow-hidden reveal-on-scroll reveal-delay-2">
                        <div class="p-6 border-b theme-border">
                            <h2 class="theme-title text-lg font-bold">Producción mensual del año</h2>
                            <p class="theme-text text-sm">Comparativo anual de actividades, puros y horas.</p>
                        </div>

                        <div class="p-6">
                            @if($hayTendenciaMensual)
                                <div id="monthlyProductionChart" class="w-full h-[360px]"></div>
                            @else
                                <div class="theme-soft rounded-2xl border theme-border p-10 text-center">
                                    <p class="theme-title font-bold">Sin datos anuales.</p>
                                    <p class="theme-text text-sm mt-1">La gráfica se llenará con la producción acumulada del año.</p>
                                </div>
                            @endif
                        </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 reveal-on-scroll reveal-delay-3">
                    <div class="theme-card theme-shadow rounded-3xl border theme-border overflow-hidden">
                        <div class="p-6 border-b theme-border flex items-center justify-between gap-3">
                            <div>
                                <h2 class="theme-title text-lg font-bold">Ranking de empleados</h2>
                                <p class="theme-text text-sm">Top mensual por actividades realizadas.</p>
                            </div>
                            <a href="{{ route('empleados.index') }}" class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border">Ver empleados</a>
                        </div>

                        <div class="p-6 space-y-3">
                            @forelse($rankingEmpleados as $index => $empleado)
                                <div class="theme-soft rounded-2xl border theme-border p-4 flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-[#0b1220] text-white flex items-center justify-center text-sm font-extrabold">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="theme-title font-bold truncate">{{ $empleado['nombre'] }}</p>
                                        <p class="theme-text text-xs">COD {{ $empleado['codigo'] }} · {{ $empleado['tiempo'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="theme-title font-extrabold">{{ number_format($empleado['actividades']) }}</p>
                                        <p class="theme-text text-xs">actividades</p>
                                    </div>
                                </div>
                            @empty
                                <div class="theme-soft rounded-2xl border theme-border p-8 text-center">
                                    <p class="theme-title font-bold">Sin empleados con producción este mes.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="theme-card theme-shadow rounded-3xl border theme-border overflow-hidden">
                        <div class="p-6 border-b theme-border">
                            <h2 class="theme-title text-lg font-bold">Actividades más registradas</h2>
                            <p class="theme-text text-sm">Prioriza lo que más mueve la producción del mes.</p>
                        </div>

                        <div class="p-6 space-y-3">
                            @forelse($rankingActividades as $actividad)
                                <div class="theme-soft rounded-2xl border theme-border p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="theme-title font-bold truncate">{{ $actividad['nombre'] }}</p>
                                        <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border">{{ number_format($actividad['actividades']) }}</span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs theme-text">
                                        <span>{{ number_format($actividad['registros']) }} registros</span>
                                        <span>·</span>
                                        <span>{{ number_format($actividad['puros']) }} puros</span>
                                    </div>
                                </div>
                            @empty
                                <div class="theme-soft rounded-2xl border theme-border p-8 text-center">
                                    <p class="theme-title font-bold">Sin actividades este mes.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="theme-card theme-shadow rounded-3xl border theme-border overflow-hidden reveal-on-scroll reveal-delay-3">
                    <div class="p-6 border-b theme-border flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h2 class="theme-title text-lg font-bold">Últimos movimientos de producción</h2>
                            <p class="theme-text text-sm">Registros activos más recientes.</p>
                        </div>
                        <a href="{{ route('vineta-registros.index') }}" class="gooey-action px-4 py-2 rounded-xl bg-[#0b1220] text-white text-sm font-semibold hover:bg-[#111c33] transition">Ver todos</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="theme-soft border-b theme-border">
                                <tr>
                                    <th class="text-left theme-text font-bold px-6 py-4">Viñeta</th>
                                    <th class="text-left theme-text font-bold px-6 py-4">Fecha</th>
                                    <th class="text-left theme-text font-bold px-6 py-4">Empleado</th>
                                    <th class="text-left theme-text font-bold px-6 py-4">Actividad</th>
                                    <th class="text-left theme-text font-bold px-6 py-4">Marca</th>
                                    <th class="text-right theme-text font-bold px-6 py-4">Puros</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y theme-border">
                                @forelse($ultimosRegistros as $registro)
                                    <tr>
                                        <td class="px-6 py-4 theme-title font-bold whitespace-nowrap">{{ $registro['vineta'] }}</td>
                                        <td class="px-6 py-4 theme-text whitespace-nowrap">{{ $registro['fecha'] }} {{ $registro['hora'] }}</td>
                                        <td class="px-6 py-4 theme-text whitespace-nowrap">{{ $registro['empleado'] }}</td>
                                        <td class="px-6 py-4 theme-text min-w-[220px]">{{ $registro['actividad'] }}</td>
                                        <td class="px-6 py-4 theme-text whitespace-nowrap">{{ $registro['marca'] }}</td>
                                        <td class="px-6 py-4 theme-title font-bold text-right">{{ number_format($registro['puros']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center theme-text">No hay movimientos registrados.</td>
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

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let countersStarted = false;
        let chartsRendered = false;
        const charts = [];

        const dailyTrend = @json($tendenciaDiaria);
        const monthlyTrend = @json($tendenciaMensual);
        const processBreakdown = @json($distribucionProcesos);
        function animateCounters() {
            if (countersStarted) {
                return;
            }

            countersStarted = true;

            document.querySelectorAll('.counter').forEach(counter => {
                const target = parseInt(counter.dataset.count || '0', 10);
                const duration = 850;
                const startTime = performance.now();

                const animate = currentTime => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = Math.floor(eased * target);

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
                text: isDark ? '#cbd5e1' : '#334155',
                muted: isDark ? '#94a3b8' : '#64748b',
                grid: isDark ? 'rgba(148, 163, 184, 0.14)' : '#e2e8f0',
                primary: isDark ? '#38bdf8' : '#0b1220',
                blue: '#2563eb',
                cyan: '#0891b2',
                green: '#16a34a',
                amber: '#d97706',
                tooltip: isDark ? 'dark' : 'light'
            };
        }

        function baseChartOptions(t) {
            return {
                fontFamily: 'inherit',
                foreColor: t.text,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 650,
                    dynamicAnimation: { enabled: true, speed: 320 }
                }
            };
        }

        function renderCharts() {
            if (chartsRendered) {
                return;
            }

            chartsRendered = true;
            const t = chartTheme();

            const dailyElement = document.querySelector('#dailyProductionChart');
            if (dailyElement) {
                const chart = new ApexCharts(dailyElement, {
                    chart: { ...baseChartOptions(t), height: 360, type: 'line' },
                    series: [
                        { name: 'Actividades', type: 'column', data: dailyTrend.actividades },
                        { name: 'Horas', type: 'line', data: dailyTrend.horas }
                    ],
                    colors: [t.primary, t.cyan],
                    stroke: { width: [0, 3], curve: 'smooth' },
                    plotOptions: { bar: { borderRadius: 7, columnWidth: '55%' } },
                    dataLabels: { enabled: false },
                    labels: dailyTrend.labels,
                    xaxis: { labels: { style: { colors: t.muted } }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: [
                        { title: { text: 'Actividades', style: { color: t.muted } }, labels: { style: { colors: t.muted }, formatter: value => Math.round(value).toLocaleString('es-HN') } },
                        { opposite: true, title: { text: 'Horas', style: { color: t.muted } }, labels: { style: { colors: t.muted }, formatter: value => value.toLocaleString('es-HN') + ' h' } }
                    ],
                    grid: { borderColor: t.grid, strokeDashArray: 4 },
                    tooltip: { theme: t.tooltip, shared: true }
                });

                charts.push(chart);
                chart.render();
            }

            const monthlyElement = document.querySelector('#monthlyProductionChart');
            if (monthlyElement) {
                const chart = new ApexCharts(monthlyElement, {
                    chart: { ...baseChartOptions(t), height: 360, type: 'line' },
                    series: [
                        { name: 'Actividades', type: 'column', data: monthlyTrend.actividades },
                        { name: 'Puros', type: 'line', data: monthlyTrend.puros },
                        { name: 'Horas', type: 'line', data: monthlyTrend.horas }
                    ],
                    colors: [t.primary, t.blue, t.green],
                    stroke: { width: [0, 3, 3], curve: 'smooth' },
                    plotOptions: { bar: { borderRadius: 7, columnWidth: '48%' } },
                    dataLabels: { enabled: false },
                    labels: monthlyTrend.labels,
                    xaxis: { labels: { style: { colors: t.muted } }, axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { labels: { style: { colors: t.muted }, formatter: value => Math.round(value).toLocaleString('es-HN') } },
                    grid: { borderColor: t.grid, strokeDashArray: 4 },
                    tooltip: { theme: t.tooltip, shared: true }
                });

                charts.push(chart);
                chart.render();
            }

            const processElement = document.querySelector('#processChart');
            if (processElement) {
                const chart = new ApexCharts(processElement, {
                    chart: { ...baseChartOptions(t), height: 260, type: 'donut' },
                    series: processBreakdown.data,
                    labels: processBreakdown.labels,
                    colors: [t.primary, t.blue, t.green, t.amber],
                    stroke: { width: 0 },
                    dataLabels: { enabled: false },
                    legend: { position: 'bottom', labels: { colors: t.muted } },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '72%',
                                labels: {
                                    show: true,
                                    total: { show: true, label: 'Total', color: t.muted },
                                    value: { color: t.text, fontSize: '22px', fontWeight: 800 }
                                }
                            }
                        }
                    },
                    tooltip: { theme: t.tooltip }
                });

                charts.push(chart);
                chart.render();
            }

        }

        function updateChartsTheme() {
            const t = chartTheme();

            charts.forEach(chart => {
                chart.updateOptions({
                    chart: { foreColor: t.text },
                    colors: [t.primary, t.blue, t.green, t.amber],
                    grid: { borderColor: t.grid },
                    xaxis: { labels: { style: { colors: t.muted } } },
                    yaxis: { labels: { style: { colors: t.muted } } },
                    legend: { labels: { colors: t.muted } },
                    tooltip: { theme: t.tooltip }
                }, false, true);
            });
        }

        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (! entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');

                if (entry.target.querySelector('.counter')) {
                    animateCounters();
                }

                if (
                    entry.target.querySelector('#dailyProductionChart') ||
                    entry.target.querySelector('#monthlyProductionChart') ||
                    entry.target.querySelector('#processChart')
                ) {
                    renderCharts();
                }

                revealObserver.unobserve(entry.target);
            });
        }, { threshold: 0.16, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('.reveal-on-scroll').forEach(element => revealObserver.observe(element));

        const firstVisible = document.querySelector('.reveal-on-scroll');
        if (firstVisible) {
            setTimeout(() => firstVisible.classList.add('is-visible'), 90);
        }

        new MutationObserver(updateChartsTheme).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    });
</script>

</body>
</html>
