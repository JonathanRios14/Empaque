<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
          @include('layouts.theme-script')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800 transition-colors duration-300">

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: true,
    seguridad: false,
    produccion: false
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Productos',
            'description' => 'Catálogo de productos sincronizado desde la API.'
        ])

        <section class="p-4 lg:p-6">
            <div class="w-full max-w-[1600px] mx-auto">

                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                @php
    $hayFiltros =
        request()->filled('buscar') ||
        request()->filled('marca_id') ||
        request()->filled('actividad_id') ||
        request()->filled('vitola_id') ||
        request()->filled('capa_id') ||
        request()->filled('tipo_empaque_id') ||
        request()->filled('presentacion_id');
@endphp

<div class="p-6 border-b border-[#e5d8c7] theme-border">
    <div class="grid grid-cols-1 xl:grid-cols-[260px_1fr] gap-6">

        {{-- BLOQUE IZQUIERDO --}}
        <div class="theme-soft rounded-2xl border border-[#e5d8c7] theme-border bg-[#f8fafc] p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="theme-title text-2xl font-extrabold text-[#0b1220] leading-tight">
                        Listado de productos
                    </h2>

                    <p class="theme-text text-sm text-gray-500 mt-2 leading-relaxed">
                        Aquí puedes ver todos los productos sincronizados y aplicar filtros avanzados.
                    </p>
                </div>

                
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <span class="theme-badge px-3 py-1 rounded-full text-xs font-semibold border border-[#dbe3f0] bg-white theme-border theme-title">
                    {{ $productos->total() }} producto(s)
                </span>

     @if($hayFiltros)
    <span class="active-filters-badge px-3 py-1 rounded-full text-xs font-semibold border">
        filtrado
    </span>
@endif
            </div>

            @can('productos.sincronizar')
                <form method="POST"
                      action="{{ route('catalogos.productos.sincronizar') }}"
                      class="form-sincronizar mt-5">
                    @csrf

                    <button type="submit"
                                   class="sync-main-btn btn-sincronizar inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-2xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition disabled:opacity-70 disabled:cursor-not-allowed">
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

        {{-- BLOQUE DERECHO --}}
        <div class="space-y-4">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="theme-title text-base font-bold text-[#0b1220]">
                        Filtros de búsqueda
                    </h3>
                    <p class="theme-text text-sm text-gray-500 mt-1">
                        Filtra por marca, actividad, vitola, capa, presentación o tipo de empaque.
                    </p>
                </div>

             
            </div>

            <form method="GET"
                  action="{{ route('catalogos.productos.index') }}"
                  class="space-y-4">

                {{-- BUSCADOR --}}
                <div>
                    <label class="theme-title block text-xs font-bold mb-2 uppercase tracking-wide text-gray-500">
                        Buscar
                    </label>

                    <div class="relative">
                        <input type="text"
                               name="buscar"
                               value="{{ request('buscar') }}"
                               placeholder="Producto, marca, vitola, código o actividad"
                               class="theme-input w-full h-12 rounded-2xl border border-gray-300 text-sm pl-12 pr-10 focus:border-[#0f172a] focus:ring-[#0f172a]">

                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                            </svg>
                        </span>

                        @if(request('buscar'))
                            <a href="{{ route('catalogos.productos.index', request()->except('buscar', 'page')) }}"
                               class="absolute right-3 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 transition"
                               title="Quitar búsqueda">
                                ×
                            </a>
                        @endif
                    </div>
                </div>

                {{-- FILTROS --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 gap-4">

                    <div>
                        <label class="theme-title block text-xs font-bold mb-2 uppercase tracking-wide text-gray-500">
                            Marca
                        </label>
                        <select name="marca_id"
                                class="theme-input w-full h-12 rounded-2xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                            <option value="">Todas</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca->id }}" @selected(request('marca_id') == $marca->id)>
                                    {{ $marca->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="theme-title block text-xs font-bold mb-2 uppercase tracking-wide text-gray-500">
                            Actividad
                        </label>
                        <select name="actividad_id"
                                class="theme-input w-full h-12 rounded-2xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                            <option value="">Todas</option>
                            @foreach ($actividades as $actividad)
                                <option value="{{ $actividad->id }}" @selected(request('actividad_id') == $actividad->id)>
                                    {{ $actividad->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="theme-title block text-xs font-bold mb-2 uppercase tracking-wide text-gray-500">
                            Vitola
                        </label>
                        <select name="vitola_id"
                                class="theme-input w-full h-12 rounded-2xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                            <option value="">Todas</option>
                            @foreach ($vitolas as $vitola)
                                <option value="{{ $vitola->id }}" @selected(request('vitola_id') == $vitola->id)>
                                    {{ $vitola->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="theme-title block text-xs font-bold mb-2 uppercase tracking-wide text-gray-500">
                            Capa
                        </label>
                        <select name="capa_id"
                                class="theme-input w-full h-12 rounded-2xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                            <option value="">Todas</option>
                            @foreach ($capas as $capa)
                                <option value="{{ $capa->id }}" @selected(request('capa_id') == $capa->id)>
                                    {{ $capa->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="theme-title block text-xs font-bold mb-2 uppercase tracking-wide text-gray-500">
                            Tipo empaque
                        </label>
                        <select name="tipo_empaque_id"
                                class="theme-input w-full h-12 rounded-2xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                            <option value="">Todos</option>
                            @foreach ($tipoEmpaques as $tipoEmpaque)
                                <option value="{{ $tipoEmpaque->id }}" @selected(request('tipo_empaque_id') == $tipoEmpaque->id)>
                                    {{ $tipoEmpaque->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="theme-title block text-xs font-bold mb-2 uppercase tracking-wide text-gray-500">
                            Presentación
                        </label>
                        <select name="presentacion_id"
                                class="theme-input w-full h-12 rounded-2xl border border-gray-300 text-sm focus:border-[#0f172a] focus:ring-[#0f172a]">
                            <option value="">Todas</option>
                            @foreach ($presentaciones as $presentacion)
                                <option value="{{ $presentacion->id }}" @selected(request('presentacion_id') == $presentacion->id)>
                                    {{ $presentacion->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

              {{-- ACCIONES --}}
<div class="flex flex-col sm:flex-row justify-end gap-3 pt-1">

    @if($hayFiltros)
        <a href="{{ route('catalogos.productos.index') }}"
           title="Quitar filtros"
           class="filter-clear-btn inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl border border-[#d9e1ec] bg-white text-[#0f172a] text-sm font-semibold hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition">
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
            class="filter-submit-btn inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition shadow-sm">
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
        Filtrar
    </button>
</div>
            </form>
        </div>
    </div>
</div>

@php
    $sortLink = function ($campo) {
        $ordenActual = request('orden', 'created_at');
        $direccionActual = request('direccion', 'desc');

        $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

        return url()->current() . '?' . http_build_query(array_merge(request()->query(), [
            'orden' => $campo,
            'direccion' => $nuevaDireccion,
            'page' => null,
        ]));
    };

    $sortIcon = function ($campo) {
        $ordenActual = request('orden', 'created_at');
        $direccionActual = request('direccion', 'desc');

        if ($ordenActual !== $campo) {
            return '↕';
        }

        return $direccionActual === 'asc' ? '↑' : '↓';
    };
@endphp
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="theme-table-head bg-[#f3efe7] text-[#3b2818]">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('nombre') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            Producto
                                            <span class="text-xs">{{ $sortIcon('nombre') }}</span>
                                        </a>
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('marca') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            Marca
                                            <span class="text-xs">{{ $sortIcon('marca') }}</span>
                                        </a>
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('vitola') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            Vitola
                                            <span class="text-xs">{{ $sortIcon('vitola') }}</span>
                                        </a>
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('capa') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            Capa
                                            <span class="text-xs">{{ $sortIcon('capa') }}</span>
                                        </a>
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('tipo_empaque') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            Tipo empaque
                                            <span class="text-xs">{{ $sortIcon('tipo_empaque') }}</span>
                                        </a>
                                    </th>

                                    <th class="px-6 py-4 text-left font-semibold">
                                        <a href="{{ $sortLink('precio') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                                            Precio
                                            <span class="text-xs">{{ $sortIcon('precio') }}</span>
                                        </a>
                                    </th>

                                    <th class="px-6 py-4 text-right font-semibold">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($productos as $producto)
                                    <tr class="theme-row border-b border-gray-100 theme-border hover:bg-[#faf7f2] transition">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('catalogos.productos.show', array_merge(['producto' => $producto->id], request()->query())) }}"
                                               class="theme-title font-semibold text-[#3b2818] hover:text-[#5b3a1e] hover:underline transition">
                                                {{ $producto->nombre ?? 'Sin nombre' }}
                                            </a>

                                            <p class="theme-text text-xs text-gray-500">
                                                Item: {{ $producto->item ?? 'N/A' }}
                                            </p>

                                            <p class="theme-text text-[11px] text-gray-400">
                                                Código: {{ $producto->codigo_producto ?? 'N/A' }}
                                            </p>

                                            <p class="theme-text text-[11px] text-gray-400">
                                                API ID: {{ $producto->api_id_producto }}
                                            </p>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-badge inline-flex items-center px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $producto->marca?->nombre ?? 'N/A' }}
                                            </span>

                                            <p class="theme-text text-[11px] text-gray-400 mt-1">
                                                {{ $producto->empresa?->nombre ?? '' }}
                                            </p>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-text text-gray-700">
                                                {{ $producto->vitola?->nombre ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-text text-gray-700">
                                                {{ $producto->capa?->nombre ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-text text-gray-700">
                                                {{ $producto->tipoEmpaque?->nombre ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-title font-semibold text-[#3b2818]">
                                                {{ number_format($producto->precio, 2) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('catalogos.productos.show', array_merge(['producto' => $producto->id], request()->query())) }}"
                                               class="theme-button-secondary inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] hover:bg-[#e5d8c7] transition">
                                                Ver
                                                <span>→</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="theme-text px-6 py-10 text-center text-gray-500">
                                            No hay productos registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="theme-soft px-6 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p class="theme-text text-sm text-gray-500">
                            Mostrando
                            <span class="theme-title font-semibold text-[#3b2818]">{{ $productos->firstItem() ?? 0 }}</span>
                            a
                            <span class="theme-title font-semibold text-[#3b2818]">{{ $productos->lastItem() ?? 0 }}</span>
                            de
                            <span class="theme-title font-semibold text-[#3b2818]">{{ $productos->total() }}</span>
                            producto(s)
                        </p>

                        <div class="pagination-cafe">
                            {{ $productos->onEachSide(1)->links('pagination.cafe') }}
                        </div>
                    </div>

                </div>

            </div>
        </section>
    </main>
</div>
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

@include('layouts.flash')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const forms = document.querySelectorAll('.form-sincronizar');

        forms.forEach(form => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('.btn-sincronizar');
                const spinner = form.querySelector('.spinner-sincronizar');
                const text = form.querySelector('.texto-sincronizar');

                if (button) {
                    button.disabled = true;
                }

                if (spinner) {
                    spinner.classList.remove('hidden');
                }

                if (text) {
                    text.textContent = 'Sincronizando...';
                }
            });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const forms = document.querySelectorAll('.form-sincronizar');

        forms.forEach(form => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('.btn-sincronizar');
                const spinner = form.querySelector('.spinner-sincronizar');
                const text = form.querySelector('.texto-sincronizar');
                const overlay = document.getElementById('syncOverlay');

                if (button) {
                    button.disabled = true;
                }

                if (spinner) {
                    spinner.classList.remove('hidden');
                }

                if (text) {
                    text.textContent = 'Sincronizando...';
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