<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Dashboard | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .dashboard-shell {
            background:
                radial-gradient(circle at 8% 0%, rgba(37, 99, 235, .08), transparent 28rem),
                linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }

        html.dark-navy .dashboard-shell {
            background:
                radial-gradient(circle at 8% 0%, rgba(56, 189, 248, .08), transparent 28rem),
                linear-gradient(180deg, #111c33 0%, #0b1220 100%);
        }

        .dashboard-panel {
            background: rgba(255, 255, 255, .96);
            border: 1px solid #e2e8f0;
            box-shadow: 0 14px 38px rgba(15, 23, 42, .055);
        }

        html.dark-navy .dashboard-panel {
            background: #111c33;
            border-color: #263650;
            box-shadow: none;
        }

        .dashboard-welcome {
            background:
                radial-gradient(circle at 92% 15%, rgba(37, 99, 235, .12), transparent 21rem),
                linear-gradient(125deg, #ffffff 0%, #f8fbff 55%, #eff6ff 100%);
            border: 1px solid #dbeafe;
            box-shadow: 0 18px 44px rgba(15, 23, 42, .07);
        }

        html.dark-navy .dashboard-welcome {
            background:
                radial-gradient(circle at 92% 15%, rgba(56, 189, 248, .10), transparent 21rem),
                linear-gradient(125deg, #111c33 0%, #16233d 100%);
            border-color: #263650;
            box-shadow: none;
        }

        .dashboard-kicker {
            color: #2563eb;
        }

        html.dark-navy .dashboard-kicker {
            color: #38bdf8;
        }

        .hero-stat {
            background: rgba(255, 255, 255, .82);
            border: 1px solid #dbeafe;
            min-width: 0;
            position: relative;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .hero-stat::before {
            background: var(--stat-color);
            border-radius: 999px;
            content: '';
            height: 3px;
            inset: 0.65rem 0.8rem auto;
            position: absolute;
        }

        .hero-stat:hover {
            border-color: #93c5fd;
            box-shadow: 0 12px 25px rgba(37, 99, 235, .09);
            transform: translateY(-2px);
        }

        html.dark-navy .hero-stat {
            background: #16233d;
            border-color: #263650;
        }

        html.dark-navy .hero-stat:hover {
            border-color: #38bdf8;
            box-shadow: none;
        }

        .hero-stat:nth-child(1) { --stat-color: #60a5fa; }
        .hero-stat:nth-child(2) { --stat-color: #2563eb; }
        .hero-stat:nth-child(3) { --stat-color: #0f172a; }

        html.dark-navy .hero-stat:nth-child(1) { --stat-color: #7dd3fc; }
        html.dark-navy .hero-stat:nth-child(2) { --stat-color: #38bdf8; }
        html.dark-navy .hero-stat:nth-child(3) { --stat-color: #0ea5e9; }

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

        .process-date-input {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #0f172a;
            color-scheme: light;
            cursor: pointer;
            font-size: .75rem;
            font-weight: 800;
            min-height: 2.5rem;
            outline: none;
            padding: .55rem .75rem;
            transition: border-color .18s ease, box-shadow .18s ease, opacity .18s ease;
        }

        .process-date-input:hover,
        .process-date-input:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .09);
        }

        .process-date-input:disabled {
            cursor: wait;
            opacity: .62;
        }

        .process-date-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        html.dark-navy .process-date-input {
            background: #16233d;
            border-color: #263650;
            color: #f8fafc;
            color-scheme: dark;
        }

        html.dark-navy .process-date-input:hover,
        html.dark-navy .process-date-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .08);
        }

        .process-chart-wrap {
            min-height: 240px;
            position: relative;
        }

        .process-chart-wrap #processChart {
            transition: opacity .18s ease;
        }

        .process-chart-wrap::after {
            animation: process-spinner .7s linear infinite;
            border: 3px solid #dbeafe;
            border-radius: 999px;
            border-top-color: #2563eb;
            content: '';
            height: 2rem;
            left: calc(50% - 1rem);
            opacity: 0;
            pointer-events: none;
            position: absolute;
            top: calc(50% - 1rem);
            transition: opacity .18s ease;
            width: 2rem;
        }

        .process-chart-wrap.is-loading #processChart {
            opacity: .28;
        }

        .process-chart-wrap.is-loading::after {
            opacity: 1;
        }

        html.dark-navy .process-chart-wrap::after {
            border-color: #263650;
            border-top-color: #38bdf8;
        }

        @keyframes process-spinner {
            to { transform: rotate(360deg); }
        }

        .area-card-rezago {
            --area-color: #2563eb;
            --area-soft: rgba(37, 99, 235, .12);
            --area-border: rgba(37, 99, 235, .38);
        }

        .area-card-anillado {
            --area-color: #0891b2;
            --area-soft: rgba(8, 145, 178, .12);
            --area-border: rgba(8, 145, 178, .38);
        }

        .area-card-llenado {
            --area-color: #16a34a;
            --area-soft: rgba(22, 163, 74, .12);
            --area-border: rgba(22, 163, 74, .38);
        }

        html.dark-navy .area-card-rezago {
            --area-color: #38bdf8;
            --area-soft: rgba(56, 189, 248, .13);
            --area-border: rgba(56, 189, 248, .38);
        }

        html.dark-navy .area-card-anillado {
            --area-color: #22d3ee;
            --area-soft: rgba(34, 211, 238, .12);
            --area-border: rgba(34, 211, 238, .38);
        }

        html.dark-navy .area-card-llenado {
            --area-color: #4ade80;
            --area-soft: rgba(74, 222, 128, .12);
            --area-border: rgba(74, 222, 128, .38);
        }

        .area-card {
            overflow: hidden;
            position: relative;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .area-card::before {
            background: var(--area-color);
            content: '';
            height: 4px;
            inset: 0 0 auto;
            position: absolute;
        }

        .area-card::after {
            background: radial-gradient(circle, var(--area-soft), transparent 68%);
            content: '';
            height: 13rem;
            position: absolute;
            right: -5rem;
            top: -6rem;
            width: 13rem;
        }

        .area-card:hover {
            border-color: var(--area-border);
            box-shadow: 0 22px 42px rgba(15, 23, 42, .08);
            transform: translateY(-3px);
        }

        .area-card .area-icon {
            animation: area-float 4.2s ease-in-out infinite;
            transition: transform .25s ease;
        }

        .area-card:hover .area-icon {
            animation-play-state: paused;
            transform: rotate(-7deg) scale(1.08);
        }

        .area-card .area-progress {
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 1s cubic-bezier(.22, 1, .36, 1);
            transition-delay: calc(var(--item-delay, 80ms) + 260ms);
        }

        .reveal-on-scroll.is-visible .area-card .area-progress {
            transform: scaleX(1);
        }

        @keyframes area-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        html.dark-navy .area-card:hover {
            box-shadow: none;
        }

        .dashboard-tabs {
            background: #eff6ff;
            border: 1px solid #dbeafe;
        }

        .dashboard-tab {
            color: #64748b;
            transition: background-color .18s ease, color .18s ease, box-shadow .18s ease;
        }

        .dashboard-tab:hover {
            color: #0f172a;
        }

        .dashboard-tab.is-active {
            background: #0f172a;
            color: #fff;
            box-shadow: 0 5px 16px rgba(15, 23, 42, .16);
        }

        html.dark-navy .dashboard-tabs {
            background: #16233d;
            border-color: #263650;
        }

        html.dark-navy .dashboard-tab {
            color: #94a3b8;
        }

        html.dark-navy .dashboard-tab:hover {
            color: #e2e8f0;
        }

        html.dark-navy .dashboard-tab.is-active {
            background: #38bdf8;
            color: #0b1220;
            box-shadow: none;
        }

        .ranking-row {
            transition: background-color .18s ease, transform .18s ease;
        }

        .ranking-row:hover {
            background: #eff6ff;
            transform: translateX(3px);
        }

        html.dark-navy .ranking-row:hover {
            background: #172845;
        }

        html.dark-navy .ranking-list,
        html.dark-navy .ranking-list > *,
        html.dark-navy .recent-table-body,
        html.dark-navy .recent-table-body > * {
            border-color: transparent !important;
        }

        html.dark-navy .ranking-row {
            border: 0 !important;
        }

        html.dark-navy .ranking-position {
            color: #020617 !important;
        }

        .ranking-top-1 {
            background: linear-gradient(90deg, rgba(29, 78, 216, .12), rgba(14, 165, 233, .055) 72%, transparent);
            box-shadow: inset 3px 0 0 #1d4ed8;
            position: relative;
        }

        .ranking-top-1:hover {
            background: linear-gradient(90deg, rgba(29, 78, 216, .17), rgba(14, 165, 233, .085) 72%, transparent);
        }

        .ranking-top-1 .ranking-position {
            background: #1d4ed8 !important;
            border: 1px solid #1e40af;
            box-shadow: inset 0 -3px 0 #38bdf8;
            color: #ffffff !important;
        }

        html.dark-navy .ranking-top-1 {
            background: linear-gradient(90deg, rgba(56, 189, 248, .11), rgba(37, 99, 235, .07) 72%, transparent);
            box-shadow: inset 3px 0 0 #38bdf8;
        }

        html.dark-navy .ranking-top-1:hover {
            background: linear-gradient(90deg, rgba(56, 189, 248, .16), rgba(37, 99, 235, .10) 72%, transparent);
        }

        html.dark-navy .ranking-top-1 .ranking-position {
            background: #38bdf8 !important;
            border-color: #0ea5e9;
            box-shadow: inset 0 -3px 0 #2563eb;
            color: #0b1220 !important;
        }

        html.dark-navy .dark-clean-panel,
        html.dark-navy .dark-clean-panel > header,
        html.dark-navy .dark-clean-panel thead {
            border-color: transparent !important;
        }

        html.dark-navy .area-progress-track {
            background: #263650 !important;
        }

        html.dark-navy .area-metrics > * {
            border-color: rgba(56, 189, 248, .08) !important;
        }

        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(24px) scale(.988);
            transition:
                opacity .62s cubic-bezier(.22, 1, .36, 1),
                transform .62s cubic-bezier(.22, 1, .36, 1);
            transition-delay: var(--reveal-delay, 0ms);
            will-change: opacity, transform;
        }

        .reveal-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .reveal-on-scroll .reveal-item {
            opacity: 0;
            transform: translateY(12px);
            transition:
                opacity .5s cubic-bezier(.22, 1, .36, 1),
                transform .5s cubic-bezier(.22, 1, .36, 1);
            transition-delay: calc(var(--reveal-delay, 0ms) + var(--item-delay, 80ms));
        }

        .reveal-on-scroll.is-visible .reveal-item {
            opacity: 1;
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: reduce) {
            .reveal-on-scroll,
            .reveal-on-scroll .reveal-item {
                opacity: 1;
                transform: none;
                transition: none;
            }
            .area-card, .ranking-row { transition: none; }
            .area-card .area-icon { animation: none; }
            .area-card .area-progress { transform: scaleX(1); transition: none; }
            .process-chart-wrap::after { animation: none; }
        }
    </style>
</head>

<body class="dashboard-shell text-gray-800 transition-colors duration-300">

@php
    $areasMes = $resumenAreas['mes'] ?? [];
    $totalMesAreas = max((int) collect($areasMes)->sum('actividades'), 1);
    $hayProduccion = ($produccionTotal['registros'] ?? 0) > 0;
    $periodos = ['dia' => 'Día', 'mes' => 'Mes', 'anio' => 'Año'];
    $periodLabels = [
        'dia' => ucfirst($selectedMonth->locale('es')->translatedFormat('F Y')),
        'mes' => (string) $selectedYear,
        'anio' => max($selectedYear - 4, 2000).' - '.$selectedYear,
    ];
    $defaultRankingArea = collect($rankingEmpleados)
        ->first(fn ($group) => count($group['rows'] ?? []) > 0)['key'] ?? 'rezago';
@endphp

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: false,
    seguridad: false,
    produccion: true,
    rankingArea: '{{ $defaultRankingArea }}'
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1 min-w-0">
        @include('layouts.topbar', [
            'title' => 'Dashboard',
            'description' => 'Resumen operativo por área'
        ])

        <section class="app-content-compact">
            <div class="w-full max-w-[1600px] mx-auto space-y-5 lg:space-y-6">

                <section class="dashboard-welcome reveal-on-scroll rounded-[1.75rem] p-5 lg:p-6 overflow-hidden relative">
                    <div class="absolute -right-16 -top-24 h-64 w-64 rounded-full border border-[#dbeafe] theme-border"></div>
                    <div class="absolute -right-6 -top-10 h-40 w-40 rounded-full border border-[#dbeafe] theme-border"></div>

                    <div class="relative grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_560px] xl:items-center gap-5 lg:gap-8">
                        <div class="reveal-item min-w-0" style="--item-delay: 80ms">
                            <h1 class="theme-title mt-1 text-2xl lg:text-3xl font-black tracking-tight truncate">
                                Buenos días, {{ Auth::user()->name }}
                            </h1>
                            <div class="theme-text mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-bold">
                                <span>{{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}</span>
                                <span class="hidden sm:inline h-1 w-1 rounded-full bg-[#93c5fd]"></span>
                                <span>{{ $today->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</span>
                                <span class="hidden sm:inline h-1 w-1 rounded-full bg-[#93c5fd]"></span>
                                <span>{{ number_format($produccionMes['empleados'] ?? 0) }} empleados este mes</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a href="{{ route('vineta-registros.index') }}" class="dashboard-primary-action gooey-action inline-flex items-center justify-center px-5 py-3 rounded-2xl text-sm font-semibold transition">
                                    Ver registros
                                </a>
                                <a href="{{ route('vineta-registros.reporte-semanal') }}" class="theme-button-secondary gooey-action inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-white text-[#0b1220] text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition">
                                    Reporte semanal
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
                            <div class="hero-stat reveal-item rounded-2xl px-3 pb-3 pt-5 sm:px-4" style="--item-delay: 130ms">
                                <p class="theme-text text-[10px] font-black uppercase tracking-[.14em]">Hoy</p>
                                <p class="theme-title mt-1 truncate text-xl sm:text-2xl font-black counter" data-count="{{ $produccionHoy['actividades'] ?? 0 }}">0</p>
                                <p class="theme-text mt-0.5 text-[10px] font-bold">actividades</p>
                            </div>
                            <div class="hero-stat reveal-item rounded-2xl px-3 pb-3 pt-5 sm:px-4" style="--item-delay: 180ms">
                                <p class="theme-text text-[10px] font-black uppercase tracking-[.14em]">Mes</p>
                                <p class="theme-title mt-1 truncate text-xl sm:text-2xl font-black counter" data-count="{{ $produccionMes['actividades'] ?? 0 }}">0</p>
                                <p class="theme-text mt-0.5 text-[10px] font-bold">actividades</p>
                            </div>
                            <div class="hero-stat reveal-item rounded-2xl px-3 pb-3 pt-5 sm:px-4" style="--item-delay: 230ms">
                                <p class="theme-text text-[10px] font-black uppercase tracking-[.14em]">Año</p>
                                <p class="theme-title mt-1 truncate text-xl sm:text-2xl font-black counter" data-count="{{ $produccionAnio['actividades'] ?? 0 }}">0</p>
                                <p class="theme-text mt-0.5 text-[10px] font-bold">actividades</p>
                            </div>
                        </div>
                    </div>
                </section>

                @unless($hayProduccion)
                    <div class="dashboard-panel reveal-on-scroll rounded-3xl p-8 text-center">
                        <p class="theme-title font-black">Sin producción registrada</p>
                    </div>
                @endunless

                <section class="reveal-on-scroll space-y-3" style="--reveal-delay: 40ms">
                    <div class="flex items-center justify-between gap-4 px-1">
                        <h2 class="theme-title text-lg font-black">Resumen mensual</h2>
                        <span class="theme-text text-xs font-black uppercase tracking-[.12em]">{{ $periodLabels['dia'] }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-5">
                        @foreach($areasMes as $area)
                            @php($share = round(($area['actividades'] / $totalMesAreas) * 100))
                            <article class="area-card area-card-{{ $area['key'] }} reveal-item dashboard-panel rounded-[1.6rem] p-5 lg:p-6" style="--item-delay: {{ 80 + ($loop->index * 70) }}ms">
                                <div class="relative z-10">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-3">
                                            <span class="h-3 w-3 rounded-full shadow-sm" style="background: var(--area-color)"></span>
                                            <h3 class="theme-title text-base font-black">{{ $area['label'] }}</h3>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex items-end justify-between gap-4">
                                        <div>
                                            <p class="theme-title text-4xl xl:text-[2.7rem] leading-none font-black counter" data-count="{{ $area['actividades'] }}">0</p>
                                            <p class="theme-text mt-2 text-xs font-bold">Actividades</p>
                                        </div>
                                        <div class="area-icon h-11 w-11 rounded-2xl flex items-center justify-center" style="background: var(--area-soft); color: var(--area-color)">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M4 19V9M10 19V5M16 19v-7M22 19H2"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="area-progress-track mt-5 h-1.5 overflow-hidden rounded-full bg-slate-200/70" role="progressbar" aria-label="Participación de {{ $area['label'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $share }}">
                                        <div class="area-progress h-full rounded-full" style="width: {{ $share }}%; background: var(--area-color)"></div>
                                    </div>

                                    <dl class="area-metrics mt-5 grid grid-cols-3 divide-x theme-border">
                                        <div class="pr-3">
                                            <dt class="theme-text text-[10px] font-black uppercase tracking-wider">Empleados</dt>
                                            <dd class="theme-title mt-1 text-base font-black">{{ number_format($area['empleados']) }}</dd>
                                        </div>
                                        <div class="px-3">
                                            <dt class="theme-text text-[10px] font-black uppercase tracking-wider">Registros</dt>
                                            <dd class="theme-title mt-1 text-base font-black">{{ number_format($area['registros']) }}</dd>
                                        </div>
                                        <div class="pl-3">
                                            <dt class="theme-text text-[10px] font-black uppercase tracking-wider">Puros</dt>
                                            <dd class="theme-title mt-1 text-base font-black">{{ number_format($area['puros']) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="dashboard-panel reveal-on-scroll rounded-[1.75rem] overflow-hidden" style="--reveal-delay: 70ms">
                    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-5 py-4 lg:px-6 border-b theme-border">
                        <div class="flex items-center gap-3">
                            <h2 class="theme-title text-lg font-black">Producción por área</h2>
                            <span id="trendPeriodLabel" class="theme-badge hidden sm:inline-flex rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-wider">{{ $periodLabels['dia'] }}</span>
                        </div>
                        <div class="dashboard-tabs inline-flex self-start rounded-xl p-1">
                            @foreach($periodos as $key => $label)
                                <button type="button" data-trend-period="{{ $key }}" class="dashboard-tab {{ $key === 'dia' ? 'is-active' : '' }} rounded-lg px-4 py-2 text-xs font-black">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </header>
                    <div class="px-2 pb-2 pt-3 sm:px-4 lg:px-5">
                        <div id="areaTrendChart" class="w-full h-[390px]"></div>
                    </div>
                </section>

                <div class="grid grid-cols-1 xl:grid-cols-[minmax(300px,.72fr)_minmax(0,1.28fr)] items-start gap-5 lg:gap-6">
                    <section class="dashboard-panel reveal-on-scroll rounded-[1.75rem] overflow-hidden" style="--reveal-delay: 90ms">
                        <header class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b theme-border">
                            <h2 class="theme-title text-lg font-black">Producción diaria por área</h2>
                            <label for="processDate" class="sr-only">Seleccionar fecha</label>
                            <input
                                id="processDate"
                                type="date"
                                value="{{ $selectedDay->format('Y-m-d') }}"
                                max="{{ $today->format('Y-m-d') }}"
                                data-current-date="{{ $selectedDay->format('Y-m-d') }}"
                                class="process-date-input rounded-xl"
                            >
                        </header>

                        <div class="p-4 lg:p-5">
                            <div id="processChartWrap" class="process-chart-wrap">
                                <div id="processChart" class="w-full h-[240px]"></div>
                            </div>
                            <div id="processLegend" class="mt-1"></div>
                            <p id="processDateError" class="mt-3 hidden text-center text-xs font-bold text-red-600" role="alert">
                                No se pudo actualizar la fecha.
                            </p>
                        </div>
                    </section>

                    <section class="dark-clean-panel dashboard-panel reveal-on-scroll rounded-[1.75rem] overflow-hidden" style="--reveal-delay: 110ms">
                        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 border-b theme-border">
                            <h2 class="theme-title text-lg font-black">Ranking de empleados</h2>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="dashboard-tabs inline-flex self-start rounded-xl p-1">
                                    @foreach($rankingEmpleados as $key => $grupo)
                                        <button type="button" @click="rankingArea = '{{ $key }}'" :class="rankingArea === '{{ $key }}' ? 'is-active' : ''" class="dashboard-tab rounded-lg px-3 py-2 text-xs font-black">
                                            {{ $grupo['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                                <span class="theme-text text-xs font-black uppercase tracking-[.12em]">{{ $periodLabels['dia'] }}</span>
                            </div>
                        </header>

                        <div class="p-3 lg:p-4">
                            @foreach($rankingEmpleados as $key => $grupo)
                                <div x-show="rankingArea === '{{ $key }}'" x-cloak class="ranking-list space-y-0.5">
                                    @forelse($grupo['rows'] as $index => $empleado)
                                        @php($position = $index + 1)
                                        <div class="ranking-row {{ $position === 1 ? 'ranking-top-1 py-2' : 'py-1.5' }} flex items-center gap-3 rounded-xl px-3">
                                            <div class="ranking-position area-card-{{ $key }} {{ $position === 1 ? 'h-9 w-9 rounded-xl text-sm' : 'h-7 w-7 rounded-lg text-[11px]' }} shrink-0 flex items-center justify-center font-black text-white" style="background: var(--area-color)">
                                                {{ $position }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="theme-title {{ $position === 1 ? 'text-sm' : 'text-[13px]' }} truncate font-black">{{ $empleado['nombre'] }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="theme-title {{ $position === 1 ? 'text-base' : 'text-sm' }} font-black">{{ number_format($empleado['actividades']) }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="py-16 text-center">
                                            <p class="theme-text text-sm font-bold">Sin producción en {{ strtolower($grupo['label']) }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <section class="dark-clean-panel dashboard-panel reveal-on-scroll rounded-[1.75rem] overflow-hidden" style="--reveal-delay: 110ms">
                    <header class="px-5 py-4 lg:px-6 border-b theme-border">
                        <h2 class="theme-title text-lg font-black">Actividad reciente</h2>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="theme-soft border-b theme-border">
                                <tr>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Viñeta</th>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Empleado</th>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Actividad</th>
                                    <th class="px-6 py-3 text-left theme-text text-[11px] font-black uppercase tracking-wider">Marca</th>
                                    <th class="px-6 py-3 text-right theme-text text-[11px] font-black uppercase tracking-wider">Puros</th>
                                </tr>
                            </thead>
                            <tbody class="recent-table-body">
                                @forelse($ultimosRegistros as $registro)
                                    <tr class="theme-row transition hover:bg-[#eff6ff]">
                                        <td class="px-6 py-4 theme-title font-black whitespace-nowrap">{{ $registro['vineta'] }}</td>
                                        <td class="px-6 py-4 theme-text whitespace-nowrap">{{ $registro['fecha'] }} {{ $registro['hora'] }}</td>
                                        <td class="px-6 py-4 theme-title font-bold whitespace-nowrap">{{ $registro['empleado'] }}</td>
                                        <td class="px-6 py-4 theme-text min-w-[220px]">{{ $registro['actividad'] }}</td>
                                        <td class="px-6 py-4 theme-text whitespace-nowrap">{{ $registro['marca'] }}</td>
                                        <td class="px-6 py-4 theme-title font-black text-right">{{ number_format($registro['puros']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center theme-text">Sin movimientos</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const trends = @json($tendenciasAreas);
        const summaries = @json($resumenAreas);
        const periodLabels = @json($periodLabels);
        const dashboardDataUrl = @json(route('dashboard'));
        let trendChart = null;
        let processChart = null;
        let currentTrendPeriod = 'dia';
        const currentProcessPeriod = 'dia';

        function chartTheme() {
            const isDark = document.documentElement.classList.contains('dark-navy');

            return {
                text: isDark ? '#e2e8f0' : '#0f172a',
                muted: isDark ? '#94a3b8' : '#64748b',
                grid: isDark ? 'rgba(148, 163, 184, .13)' : '#e2e8f0',
                tooltip: isDark ? 'dark' : 'light',
                empty: isDark ? '#263650' : '#dbeafe',
                areas: isDark
                    ? { rezago: '#38bdf8', anillado: '#22d3ee', llenado: '#4ade80' }
                    : { rezago: '#2563eb', anillado: '#0891b2', llenado: '#16a34a' }
            };
        }

        function baseChartOptions(theme) {
            return {
                fontFamily: 'inherit',
                foreColor: theme.text,
                parentHeightOffset: 0,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 520,
                    dynamicAnimation: { enabled: true, speed: 280 }
                }
            };
        }

        function trendAreas(period) {
            return Object.entries(trends[period]?.areas || {}).map(([key, area]) => ({ ...area, key }));
        }

        function areaColor(key) {
            return chartTheme().areas[key] || '#2563eb';
        }

        function trendSeries(period) {
            return trendAreas(period).map(area => ({ name: area.label, data: area.data }));
        }

        function processRows(period) {
            return Object.values(summaries[period] || {});
        }

        function processState(period) {
            const rows = processRows(period);
            const total = rows.reduce((sum, row) => sum + Number(row.actividades || 0), 0);

            return {
                rows,
                total,
                series: total > 0 ? rows.map(row => Number(row.actividades || 0)) : [1],
                labels: total > 0 ? rows.map(row => row.label) : ['Sin producción'],
                colors: total > 0 ? rows.map(row => areaColor(row.key)) : [chartTheme().empty]
            };
        }

        function setActiveTab(selector, period, datasetKey) {
            document.querySelectorAll(selector).forEach(button => {
                button.classList.toggle('is-active', button.dataset[datasetKey] === period);
            });
        }

        function renderProcessLegend(period) {
            const container = document.querySelector('#processLegend');
            const state = processState(period);

            if (! container) {
                return;
            }

            container.innerHTML = state.rows.map(row => {
                const percentage = state.total > 0 ? Math.round((Number(row.actividades || 0) / state.total) * 100) : 0;

                return `
                    <div class="flex items-center gap-3 px-2 py-3">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background:${areaColor(row.key)}"></span>
                        <span class="theme-title flex-1 text-sm font-bold">${row.label}</span>
                        <span class="theme-text text-xs font-black">${percentage}%</span>
                        <span class="theme-title min-w-[84px] text-right text-sm font-black">${Number(row.actividades || 0).toLocaleString('es-HN')}</span>
                    </div>
                `;
            }).join('');
        }

        function renderCharts() {
            const theme = chartTheme();
            const trendElement = document.querySelector('#areaTrendChart');
            const processElement = document.querySelector('#processChart');

            if (trendElement) {
                const areas = trendAreas(currentTrendPeriod);
                trendChart = new ApexCharts(trendElement, {
                    chart: { ...baseChartOptions(theme), height: 390, type: 'area' },
                    series: trendSeries(currentTrendPeriod),
                    colors: areas.map(area => areaColor(area.key)),
                    stroke: { curve: 'smooth', width: 3, lineCap: 'round' },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: .2, opacityFrom: .30, opacityTo: .025, stops: [0, 82, 100] }
                    },
                    markers: { size: 0, hover: { size: 5 } },
                    dataLabels: { enabled: false },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        fontSize: '12px',
                        fontWeight: 700,
                        labels: { colors: theme.muted },
                        markers: { width: 9, height: 9, radius: 9 },
                        itemMargin: { horizontal: 12, vertical: 4 }
                    },
                    xaxis: {
                        categories: trends[currentTrendPeriod].labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { style: { colors: theme.muted, fontSize: '11px', fontWeight: 600 } },
                        tooltip: { enabled: false }
                    },
                    yaxis: {
                        min: 0,
                        labels: {
                            style: { colors: theme.muted, fontSize: '11px', fontWeight: 600 },
                            formatter: value => Math.round(value).toLocaleString('es-HN')
                        }
                    },
                    grid: { borderColor: theme.grid, strokeDashArray: 5, padding: { left: 8, right: 16 } },
                    tooltip: {
                        theme: theme.tooltip,
                        shared: true,
                        intersect: false,
                        y: { formatter: value => Number(value).toLocaleString('es-HN') + ' actividades' }
                    }
                });

                trendChart.render();
            }

            if (processElement) {
                const state = processState(currentProcessPeriod);
                processChart = new ApexCharts(processElement, {
                    chart: { ...baseChartOptions(theme), height: 240, type: 'donut' },
                    series: state.series,
                    labels: state.labels,
                    colors: state.colors,
                    stroke: { width: 0 },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    plotOptions: {
                        pie: {
                            expandOnClick: false,
                            donut: {
                                size: '76%',
                                labels: {
                                    show: true,
                                    name: { show: true, color: theme.muted, fontSize: '12px', fontWeight: 700, offsetY: -5 },
                                    value: {
                                        show: true,
                                        color: theme.text,
                                        fontSize: '24px',
                                        fontWeight: 900,
                                        offsetY: 3,
                                        formatter: value => state.total > 0 ? Number(value).toLocaleString('es-HN') : '0'
                                    },
                                    total: {
                                        show: true,
                                        showAlways: true,
                                        label: 'Total',
                                        color: theme.muted,
                                        fontSize: '12px',
                                        fontWeight: 700,
                                        formatter: () => state.total.toLocaleString('es-HN')
                                    }
                                }
                            }
                        }
                    },
                    tooltip: {
                        enabled: state.total > 0,
                        theme: theme.tooltip,
                        y: { formatter: value => Number(value).toLocaleString('es-HN') + ' actividades' }
                    }
                });

                processChart.render();
                renderProcessLegend(currentProcessPeriod);
            }
        }

        function updateProcessChart() {
            const state = processState(currentProcessPeriod);
            const theme = chartTheme();

            processChart?.updateOptions({
                labels: state.labels,
                colors: state.colors,
                tooltip: {
                    enabled: state.total > 0,
                    theme: theme.tooltip,
                    y: { formatter: value => Number(value).toLocaleString('es-HN') + ' actividades' }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                name: { color: theme.muted },
                                value: {
                                    color: theme.text,
                                    formatter: value => state.total > 0 ? Number(value).toLocaleString('es-HN') : '0'
                                },
                                total: {
                                    color: theme.muted,
                                    formatter: () => state.total.toLocaleString('es-HN')
                                }
                            }
                        }
                    }
                }
            }, false, true);
            processChart?.updateSeries(state.series, true);
            renderProcessLegend(currentProcessPeriod);
        }

        async function loadProcessDate(date) {
            const input = document.querySelector('#processDate');
            const chartWrap = document.querySelector('#processChartWrap');
            const error = document.querySelector('#processDateError');

            if (! input || ! chartWrap || ! date) {
                return;
            }

            const previousDate = input.dataset.currentDate;
            input.disabled = true;
            chartWrap.classList.add('is-loading');
            chartWrap.setAttribute('aria-busy', 'true');
            error?.classList.add('hidden');

            try {
                const endpoint = new URL(dashboardDataUrl, window.location.origin);
                endpoint.searchParams.set('fecha', date);
                endpoint.searchParams.set('distribucion_diaria', '1');

                const response = await fetch(endpoint.toString(), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (! response.ok) {
                    throw new Error(`Dashboard request failed with status ${response.status}`);
                }

                const payload = await response.json();

                if (! Array.isArray(payload.areas) || ! payload.fecha) {
                    throw new Error('Dashboard response is invalid');
                }

                summaries.dia = payload.areas;
                input.value = payload.fecha;
                input.dataset.currentDate = payload.fecha;
                updateProcessChart();

                const pageUrl = new URL(window.location.href);
                pageUrl.searchParams.set('fecha', payload.fecha);
                pageUrl.searchParams.delete('distribucion_diaria');
                window.history.replaceState({}, '', pageUrl);
            } catch {
                input.value = previousDate;
                error?.classList.remove('hidden');
            } finally {
                input.disabled = false;
                chartWrap.classList.remove('is-loading');
                chartWrap.removeAttribute('aria-busy');
            }
        }

        function updateTrend(period) {
            currentTrendPeriod = period;
            const areas = trendAreas(period);
            setActiveTab('[data-trend-period]', period, 'trendPeriod');
            document.querySelector('#trendPeriodLabel').textContent = periodLabels[period];

            trendChart?.updateOptions({
                colors: areas.map(area => areaColor(area.key)),
                xaxis: { categories: trends[period].labels }
            }, false, true);
            trendChart?.updateSeries(trendSeries(period), true);
        }

        function animateCounters(root = document) {
            root.querySelectorAll('.counter').forEach(counter => {
                if (counter.dataset.animated === 'true') {
                    return;
                }

                counter.dataset.animated = 'true';
                const target = Number(counter.dataset.count || 0);
                const startTime = performance.now();

                const tick = currentTime => {
                    const progress = Math.min((currentTime - startTime) / 720, 1);
                    counter.textContent = Math.floor(target * (1 - Math.pow(1 - progress, 3))).toLocaleString('es-HN');

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            });
        }

        function updateChartsTheme() {
            const theme = chartTheme();
            const trendColors = trendAreas(currentTrendPeriod).map(area => areaColor(area.key));
            const process = processState(currentProcessPeriod);

            trendChart?.updateOptions({
                colors: trendColors,
                chart: { foreColor: theme.text },
                grid: { borderColor: theme.grid },
                xaxis: { labels: { style: { colors: theme.muted } } },
                yaxis: { labels: { style: { colors: theme.muted } } },
                legend: { labels: { colors: theme.muted } },
                tooltip: { theme: theme.tooltip }
            }, false, true);

            processChart?.updateOptions({
                colors: process.colors,
                chart: { foreColor: theme.text },
                tooltip: { theme: theme.tooltip },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                name: { color: theme.muted },
                                value: { color: theme.text },
                                total: { color: theme.muted }
                            }
                        }
                    }
                }
            }, false, true);

            renderProcessLegend(currentProcessPeriod);
        }

        function revealDashboard() {
            const elements = document.querySelectorAll('.reveal-on-scroll');

            if (! ('IntersectionObserver' in window)) {
                elements.forEach(element => {
                    element.classList.add('is-visible');
                    animateCounters(element);
                });

                return;
            }

            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (! entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    animateCounters(entry.target);

                    if (entry.target.querySelector('#areaTrendChart')) {
                        trendChart?.updateSeries(trendSeries(currentTrendPeriod), true);
                    }

                    if (entry.target.querySelector('#processChart')) {
                        processChart?.updateSeries(processState(currentProcessPeriod).series, true);
                    }

                    observer.unobserve(entry.target);
                });
            }, {
                threshold: .12,
                rootMargin: '0px 0px -48px 0px'
            });

            elements.forEach(element => observer.observe(element));
        }

        document.querySelectorAll('[data-trend-period]').forEach(button => {
            button.addEventListener('click', () => updateTrend(button.dataset.trendPeriod));
        });

        document.querySelector('#processDate')?.addEventListener('change', event => {
            const date = event.currentTarget.value;

            if (! date) {
                event.currentTarget.value = event.currentTarget.dataset.currentDate;

                return;
            }

            loadProcessDate(date);
        });

        revealDashboard();

        if (typeof ApexCharts !== 'undefined') {
            renderCharts();
        }

        new MutationObserver(updateChartsTheme).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    });
</script>

</body>
</html>
