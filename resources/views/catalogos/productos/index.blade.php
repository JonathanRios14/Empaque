<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f8fafc] text-gray-800 transition-colors duration-300">

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: true,
    seguridad: false,
    produccion: false
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1 min-w-0">

        @include('layouts.topbar', [
            'title' => 'Productos',
            'description' => 'Catálogo de productos sincronizados desde la API.'
        ])

       <section class="app-content-compact">
            <div class="w-full max-w-[1600px] mx-auto">

<div class="productos-card theme-card bg-white rounded-2xl border border-[#e5d8c7] theme-border shadow-sm theme-shadow overflow-visible">
                    @php
                        $filtrosActivos = [];

                        if (request()->filled('buscar')) {
                            $filtrosActivos[] = [
                                'label' => 'Búsqueda',
                                'value' => request('buscar'),
                                'clear' => route('catalogos.productos.index', request()->except('buscar', 'page')),
                            ];
                        }
                        if (request()->filled('empresa_id')) {
    $empresaSeleccionada = \App\Models\Empresa::find(request('empresa_id'));

    $filtrosActivos[] = [
        'label' => 'Empresa',
        'value' => $empresaSeleccionada?->nombre ?? 'ID ' . request('empresa_id'),
        'clear' => route('catalogos.productos.index', request()->except('empresa_id', 'page')),
    ];
}

                        if (request()->filled('marca_id')) {
                            $marcaSeleccionada = $marcas->firstWhere('id', (int) request('marca_id'));

                            $filtrosActivos[] = [
                                'label' => 'Marca',
                                'value' => $marcaSeleccionada?->nombre ?? 'ID ' . request('marca_id'),
                                'clear' => route('catalogos.productos.index', request()->except('marca_id', 'page')),
                            ];
                        }

                        if (request()->filled('actividad_id')) {
                            $actividadSeleccionada = $actividades->firstWhere('id', (int) request('actividad_id'));

                            $filtrosActivos[] = [
                                'label' => 'Actividad',
                                'value' => $actividadSeleccionada?->nombre ?? 'ID ' . request('actividad_id'),
                                'clear' => route('catalogos.productos.index', request()->except('actividad_id', 'page')),
                            ];
                        }

                        if (request()->filled('vitola_id')) {
                            $vitolaSeleccionada = $vitolas->firstWhere('id', (int) request('vitola_id'));

                            $filtrosActivos[] = [
                                'label' => 'Vitola',
                                'value' => $vitolaSeleccionada?->nombre ?? 'ID ' . request('vitola_id'),
                                'clear' => route('catalogos.productos.index', request()->except('vitola_id', 'page')),
                            ];
                        }

                        if (request()->filled('capa_id')) {
                            $capaSeleccionada = $capas->firstWhere('id', (int) request('capa_id'));

                            $filtrosActivos[] = [
                                'label' => 'Capa',
                                'value' => $capaSeleccionada?->nombre ?? 'ID ' . request('capa_id'),
                                'clear' => route('catalogos.productos.index', request()->except('capa_id', 'page')),
                            ];
                        }

                        if (request()->filled('tipo_empaque_id')) {
                            $tipoEmpaqueSeleccionado = $tipoEmpaques->firstWhere('id', (int) request('tipo_empaque_id'));

                            $filtrosActivos[] = [
                                'label' => 'Tipo empaque',
                                'value' => $tipoEmpaqueSeleccionado?->nombre ?? 'ID ' . request('tipo_empaque_id'),
                                'clear' => route('catalogos.productos.index', request()->except('tipo_empaque_id', 'page')),
                            ];
                        }

                        if (request()->filled('presentacion_id')) {
                            $presentacionSeleccionada = $presentaciones->firstWhere('id', (int) request('presentacion_id'));

                            $filtrosActivos[] = [
                                'label' => 'Presentación',
                                'value' => $presentacionSeleccionada?->nombre ?? 'ID ' . request('presentacion_id'),
                                'clear' => route('catalogos.productos.index', request()->except('presentacion_id', 'page')),
                            ];
                        }

                        $hayFiltros = count($filtrosActivos) > 0;
                    @endphp

                    {{-- ENCABEZADO + FILTROS --}}
                 <div id="productosFiltersContainer"
     x-data="{
        filtersOpen: localStorage.getItem('productosFiltersOpen') === null
            ? false
            : localStorage.getItem('productosFiltersOpen') === 'true'
    }"
                        x-init="$watch('filtersOpen', value => localStorage.setItem('productosFiltersOpen', value))"
                        class="border-b border-[#e5d8c7] theme-border">

                        {{-- ENCABEZADO COMPACTO --}}
                        <div class="p-5 lg:p-6">
                            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-5">

                                {{-- INFO --}}
                                <div class="min-w-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                        <div class="flex items-start gap-3">
                                            <div class="section-title-icon w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 5.5 11 18 11.8c1.5.1 2.7 1.4 2.7 2.9 0 1.6-1.3 2.9-2.9 2.9H5.5L3 15.1v-1.6Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11.1v6.5M9.7 11.2v6.4" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.2 11.9c.5.7.5 1.9 0 2.7-.6.8-1.7 1.1-2.6.7" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 7.5c.8-.8.8-1.8 0-2.7M17 7.5c1.1-1.1 1.1-2.5 0-3.6" />
                                                </svg>
                                            </div>

                                            <div>
                                                <h2 class="theme-title text-2xl font-extrabold text-[#0b1220] leading-tight">
                                                    Listado de productos
                                                </h2>

                                                <p class="theme-text text-sm text-gray-500 mt-1">
                                                    Productos sincronizados desde la API.
                                                </p>
                                            </div>
                                        </div>

                                        <span class="theme-badge w-fit px-3 py-1 rounded-full text-xs font-semibold border border-[#dbe3f0] bg-white theme-border theme-title">
                                            {{ $productos->total() }} producto(s)
                                        </span>
                                    </div>

                                    {{-- CHIPS DE FILTROS ACTIVOS --}}
                                    @if($hayFiltros)
                                        <div class="mt-4 flex flex-wrap items-center gap-2">
                                            <span class="theme-text text-xs font-bold uppercase tracking-wide text-gray-500">
                                                Filtros activos:
                                            </span>

                                            @foreach ($filtrosActivos as $filtro)
                                                <span class="active-filter-chip inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold">
                                                    <span class="opacity-70">
                                                        {{ $filtro['label'] }}:
                                                    </span>

                                                    <span class="max-w-[180px] truncate">
                                                        {{ $filtro['value'] }}
                                                    </span>

                                                   <a href="{{ $filtro['clear'] }}"
   class="ajax-filter-link active-filter-chip-x inline-flex items-center justify-center w-5 h-5 rounded-full transition"
   title="Quitar {{ $filtro['label'] }}">
    ×
</a>
                                                </span>
                                            @endforeach

                                           <a href="{{ route('catalogos.productos.index') }}"
   class="ajax-filter-link clear-all-filters inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-bold transition">
    Limpiar todo
</a>
                                        </div>
                                    @else
                                        <div class="mt-4 flex flex-wrap items-center gap-2">
                                            <span class="inactive-filter-chip inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold">
                                                Sin filtros aplicados
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- ACCIONES --}}
                                <div class="flex flex-col sm:flex-row gap-3 xl:justify-end">

                                    <button type="button"
                                            @click="filtersOpen = !filtersOpen"
                                            class="gooey-action filter-toggle-btn inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl border text-sm font-semibold transition">

                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="w-4 h-4"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 .8 1.6L14 13.5V19a1 1 0 0 1-1.447.894l-2-1A1 1 0 0 1 10 18v-4.5L3.2 4.6A1 1 0 0 1 3 4z" />
                                        </svg>

                                        <span x-text="filtersOpen ? 'Ocultar filtros' : 'Mostrar filtros'"></span>

                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="w-4 h-4 transition-transform duration-300"
                                             :class="filtersOpen ? 'rotate-180' : ''"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    @can('productos.sincronizar')
                                        <form method="POST"
                                              action="{{ route('catalogos.productos.sincronizar') }}"
                                              class="form-sincronizar">
                                            @csrf

                                            <button type="submit"
                                                    class="gooey-action sync-main-btn btn-sincronizar inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-3 rounded-2xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition disabled:opacity-70 disabled:cursor-not-allowed">

                                                <div class="loader-sincronizar hidden"></div>

                                                <svg class="spinner-sincronizar hidden w-4 h-4 animate-spin"
                                                     xmlns="http://www.w3.org/2000/svg"
                                                     fill="none"
                                                     viewBox="0 0 24 24">
                                                    <circle class="opacity-25"
                                                            cx="12"
                                                            cy="12"
                                                            r="10"
                                                            stroke="currentColor"
                                                            stroke-width="4"></circle>
                                                    <path class="opacity-75"
                                                          fill="currentColor"
                                                          d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                                                </svg>

                                                <span class="texto-sincronizar">Sincronizar</span>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        {{-- PANEL COLAPSABLE DE FILTROS --}}
                        <div x-show="filtersOpen"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                             style="display: none;"
                             class="px-5 lg:px-6 pb-4">

                            <div class="filters-panel rounded-2xl border theme-border p-3 lg:p-4">
                                <form method="GET"
                                      action="{{ route('catalogos.productos.index') }}"
                                      class="ajax-filter-form">

                                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8 gap-2 items-end">
                                        {{-- BUSCADOR --}}
                                        <div class="sm:col-span-2 xl:col-span-2 2xl:col-span-2">
                                            <label class="theme-title block text-[11px] font-bold mb-1 uppercase tracking-wide text-gray-500">
                                                Buscar
                                            </label>

                                            <div class="relative">
                                                <input type="text"
                                                       name="buscar"
                                                       value="{{ request('buscar') }}"
                                                       placeholder="Producto, marca, vitola, código o actividad"
                                                       class="theme-input w-full h-10 rounded-xl border border-gray-300 text-sm pl-10 pr-9 focus:border-[#0f172a] focus:ring-[#0f172a]">

                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                         class="w-4 h-4"
                                                         fill="none"
                                                         viewBox="0 0 24 24"
                                                         stroke="currentColor"
                                                         stroke-width="2">
                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                                                    </svg>
                                                </span>

                                                @if(request('buscar'))
                                                    <a href="{{ route('catalogos.productos.index', request()->except('buscar', 'page')) }}"
                                                       class="ajax-filter-link absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 transition"
                                                       title="Quitar búsqueda">
                                                        ×
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- SELECTS --}}
                                        <div>
                                            <label class="theme-title block text-[11px] font-bold mb-1 uppercase tracking-wide text-gray-500">
                                                Marca
                                            </label>
                                            <select name="marca_id"
                                                    class="theme-input w-full h-10 rounded-xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                                                <option value="">Todas</option>
                                                @foreach ($marcas as $marca)
                                                    <option value="{{ $marca->id }}" @selected(request('marca_id') == $marca->id)>
                                                        {{ $marca->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="theme-title block text-[11px] font-bold mb-1 uppercase tracking-wide text-gray-500">
                                                Actividad
                                            </label>
                                            <select name="actividad_id"
                                                    class="theme-input w-full h-10 rounded-xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                                                <option value="">Todas</option>
                                                @foreach ($actividades as $actividad)
                                                    <option value="{{ $actividad->id }}" @selected(request('actividad_id') == $actividad->id)>
                                                        {{ $actividad->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="theme-title block text-[11px] font-bold mb-1 uppercase tracking-wide text-gray-500">
                                                Vitola
                                            </label>
                                            <select name="vitola_id"
                                                    class="theme-input w-full h-10 rounded-xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                                                <option value="">Todas</option>
                                                @foreach ($vitolas as $vitola)
                                                    <option value="{{ $vitola->id }}" @selected(request('vitola_id') == $vitola->id)>
                                                        {{ $vitola->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="theme-title block text-[11px] font-bold mb-1 uppercase tracking-wide text-gray-500">
                                                Capa
                                            </label>
                                            <select name="capa_id"
                                                    class="theme-input w-full h-10 rounded-xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                                                <option value="">Todas</option>
                                                @foreach ($capas as $capa)
                                                    <option value="{{ $capa->id }}" @selected(request('capa_id') == $capa->id)>
                                                        {{ $capa->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="theme-title block text-[11px] font-bold mb-1 uppercase tracking-wide text-gray-500">
                                                Tipo empaque
                                            </label>
                                            <select name="tipo_empaque_id"
                                                    class="theme-input w-full h-10 rounded-xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                                                <option value="">Todos</option>
                                                @foreach ($tipoEmpaques as $tipoEmpaque)
                                                    <option value="{{ $tipoEmpaque->id }}" @selected(request('tipo_empaque_id') == $tipoEmpaque->id)>
                                                        {{ $tipoEmpaque->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="theme-title block text-[11px] font-bold mb-1 uppercase tracking-wide text-gray-500">
                                                Presentación
                                            </label>
                                            <select name="presentacion_id"
                                                    class="theme-input w-full h-10 rounded-xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                                                <option value="">Todas</option>
                                                @foreach ($presentaciones as $presentacion)
                                                    <option value="{{ $presentacion->id }}" @selected(request('presentacion_id') == $presentacion->id)>
                                                        {{ $presentacion->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- ACCIONES --}}
                                        <div class="sm:col-span-2 xl:col-span-4 2xl:col-span-2 flex flex-col sm:flex-row justify-end gap-2">
                                            @if($hayFiltros)
                                                <a href="{{ route('catalogos.productos.index') }}"
                                                   class="ajax-filter-link filter-clear-btn inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-[#d9e1ec] bg-white text-[#0f172a] text-sm font-semibold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                         class="w-4 h-4"
                                                         fill="none"
                                                         viewBox="0 0 24 24"
                                                         stroke="currentColor"
                                                         stroke-width="2">
                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Quitar filtros
                                                </a>
                                            @endif

                                            <button type="submit"
                                                    class="gooey-action filter-submit-btn inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     class="w-4 h-4"
                                                     fill="none"
                                                     viewBox="0 0 24 24"
                                                     stroke="currentColor"
                                                     stroke-width="2">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 .8 1.6L14 13.5V19a1 1 0 0 1-1.447.894l-2-1A1 1 0 0 1 10 18v-4.5L3.2 4.6A1 1 0 0 1 3 4z" />
                                                </svg>
                                                Aplicar filtros
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- TABLA AJAX --}}
                    <div id="productosTableContainer">
                        @include('catalogos.productos.partials.tabla')
                    </div>

                </div>
            </div>
        </section>
    </main>
</div>

{{-- OVERLAY DE SINCRONIZACIÓN --}}
<div id="syncOverlay"
     class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/45 backdrop-blur-sm px-4">

    <div class="theme-card w-full max-w-md rounded-3xl bg-white border theme-border p-6 shadow-2xl text-center">
        <div class="mx-auto mb-5 flex justify-center">
            <div class="sync-loader"></div>
        </div>

        <h3 class="theme-title text-lg font-bold">
            Sincronizando catálogos
        </h3>

        <p class="theme-text text-sm mt-2">
            Actualizando productos y datos relacionados desde la API.
        </p>

        <p class="theme-text text-xs mt-4">
            No cierres esta ventana mientras termina la sincronización.
        </p>
    </div>
</div>
{{-- LOADER AJAX GLOBAL BAJO HEADER DE TABLA --}}
<div id="productosTableLoader"
     class="productos-table-loader hidden"
     role="status"
     aria-live="polite">

    <div class="productos-table-loader-card theme-card theme-shadow">
        <div class="productos-table-loader-icon">
            <span></span>
        </div>

        <div class="text-left">
            <p class="theme-title text-sm font-bold leading-tight">
                Actualizando tabla
            </p>

            <p class="theme-text text-xs leading-tight mt-0.5">
                Cargando resultados...
            </p>
        </div>
    </div>
</div>
@include('layouts.flash')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const getTableContainer = () => document.getElementById('productosTableContainer');
        const getFiltersContainer = () => document.getElementById('productosFiltersContainer');

      const showTableLoader = () => {
    const container = getTableContainer();
    const loader = document.getElementById('productosTableLoader');

    if (loader) {
        const header = document.querySelector('#productosTableContainer .productos-sticky-head');

        if (header) {
            const rect = header.getBoundingClientRect();
            const top = Math.max(rect.bottom + 12, 78);

            loader.style.top = `${top}px`;
        }

        loader.classList.remove('hidden');
        loader.classList.add('flex');
    }

    if (!container) return;

    const inner = container.querySelector('#productosTableInner');

    if (inner) {
        inner.classList.add('productos-table-loading');
    }
};
const hideTableLoader = () => {
    const loader = document.getElementById('productosTableLoader');

    if (loader) {
        setTimeout(() => {
            loader.classList.add('hidden');
            loader.classList.remove('flex');
        }, 160);
    }
};

        const refreshAlpine = (element) => {
            if (window.Alpine && element) {
                window.Alpine.initTree(element);
            }
        };

        const loadTable = async (url) => {
            const container = getTableContainer();

            if (!container) return;

            showTableLoader();

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    }
                });

                if (!response.ok) {
                    throw new Error('No se pudo actualizar la tabla');
                }

                const html = await response.text();

               container.innerHTML = html;
window.history.pushState({}, '', url);
hideTableLoader();

            } catch (error) {
                console.error(error);
                window.location.href = url;
            }
        };

        const loadFiltersAndTable = async (url) => {
            showTableLoader();

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'text/html',
                    }
                });

                if (!response.ok) {
                    throw new Error('No se pudieron actualizar los filtros');
                }

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newFilters = doc.getElementById('productosFiltersContainer');
                const newTable = doc.getElementById('productosTableContainer');

                const currentFilters = getFiltersContainer();
                const currentTable = getTableContainer();

                if (newFilters && currentFilters) {
                    currentFilters.replaceWith(newFilters);
                    refreshAlpine(newFilters);
                }

                if (newTable && currentTable) {
                    currentTable.replaceWith(newTable);
                }

                window.history.pushState({}, '', url);
                hideTableLoader();

            } catch (error) {
                console.error(error);
                window.location.href = url;
            }
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a');

            if (!link) return;

            const tableContainer = getTableContainer();

            const isSortLink = link.classList.contains('ajax-table-link');
            const isPaginationLink = tableContainer && link.closest('#productosTableContainer .ajax-pagination');
            const isFilterLink = link.classList.contains('ajax-filter-link');

            if (!isSortLink && !isPaginationLink && !isFilterLink) return;

            event.preventDefault();

            if (isFilterLink) {
                loadFiltersAndTable(link.href);
                return;
            }

            loadTable(link.href);
        });

        document.addEventListener('submit', (event) => {
            const perPageForm = event.target.closest('.ajax-per-page-form');
            const filterForm = event.target.closest('.ajax-filter-form');

            if (!perPageForm && !filterForm) return;

            event.preventDefault();

            const form = perPageForm || filterForm;
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);

            params.delete('page');

            const url = `${form.action}?${params.toString()}`;

            if (filterForm) {
                loadFiltersAndTable(url);
                return;
            }

            loadTable(url);
        });

        window.addEventListener('popstate', () => {
            loadFiltersAndTable(window.location.href);
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.form-sincronizar').forEach((form) => {
            form.addEventListener('submit', function () {
                const button = form.querySelector('.btn-sincronizar');
                const text = form.querySelector('.texto-sincronizar');
                const loader = form.querySelector('.loader-sincronizar');
                const spinner = form.querySelector('.spinner-sincronizar');
                const overlay = document.getElementById('syncOverlay');

                if (button) {
                    button.disabled = true;
                    button.classList.add('pointer-events-none', 'opacity-80');
                }

                if (text) {
                    text.textContent = 'Sincronizando...';
                }

                if (loader) {
                    loader.classList.remove('hidden');
                }

                if (spinner) {
                    spinner.classList.remove('hidden');
                }

                if (overlay) {
                    overlay.classList.remove('hidden');
                    overlay.classList.add('flex');
                }
            });
        });
    });
</script>
</body>
</html>
