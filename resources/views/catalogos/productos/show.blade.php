<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Detalle Producto | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="{{ rtrim(config('services.product_images.base_url'), '/') }}" crossorigin>
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
            'title' => 'Detalle del producto',
            'description' => 'Información completa del producto y actividades relacionadas.'
        ])

        <section class="app-content-compact">
            <div class="w-full max-w-[1600px] mx-auto space-y-6">

                {{-- RESUMEN PRINCIPAL --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#e5d8c7] theme-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="theme-title text-xl font-bold text-[#3b2818]">
                                {{ $producto->nombre ?? 'Sin nombre' }}
                            </h2>

                            <p class="theme-text text-sm text-gray-500 mt-1">
                                Item: {{ $producto->item ?? 'N/A' }} · Código: {{ $producto->codigo_producto ?? 'N/A' }} · API ID: {{ $producto->api_id_producto }}
                            </p>
                        </div>

                        <a href="{{ route('catalogos.productos.index', request()->query()) }}"
                           class="gooey-action px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition text-center">
                            Volver
                        </a>
                    </div>

                    @php
                        $imagenesProductoCarrusel = collect($producto->imagenesEmpaqueUrls())
                            ->map(fn ($url, $index) => [
                                'url' => $url,
                                'tipo' => 'Empaque / caja',
                            ])
                            ->merge(
                                collect($producto->imagenesAnilladoUrls())
                                    ->map(fn ($url, $index) => [
                                        'url' => $url,
                                        'tipo' => 'Anillado',
                                    ])
                            )
                            ->unique('url')
                            ->values()
                            ->all();
                    @endphp

                    <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-5">
                        <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Marca</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->marca?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Empresa</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->empresa?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Vitola</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->vitola?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Capa</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->capa?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Presentación</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->presentacion?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Tipo empaque</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->tipoEmpaque?->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Precio</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ number_format($producto->precio, 2) }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Cantidad bulto</p>
                            <p class="theme-title font-bold text-[#3b2818] mt-1">
                                {{ $producto->cantidad_bulto }}
                            </p>
                        </div>

                        </div>

                        <div class="product-image-card theme-soft rounded-2xl border theme-border overflow-hidden min-h-[320px]"
                             x-data="productImageCarousel(@js($imagenesProductoCarrusel))">
                            <div class="p-4 border-b theme-border flex items-center justify-between gap-3">
                                <div>
                                    <p class="theme-title text-sm font-bold">
                                        Imagenes del producto
                                    </p>
                                    <p class="theme-text mt-1 text-xs">
                                        Empaque y anillado
                                    </p>
                                </div>

                                <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] whitespace-nowrap">
                                    {{ count($imagenesProductoCarrusel) ?: 'Sin' }} {{ count($imagenesProductoCarrusel) === 1 ? 'imagen' : 'imagenes' }}
                                </span>
                            </div>

                            @if ($imagenesProductoCarrusel)
                                <div class="relative p-3">
                                    <a :href="images[index].url" target="_blank" rel="noopener noreferrer" class="block rounded-xl overflow-hidden border theme-border bg-white">
                                        <img :src="currentSrc"
                                             src="{{ $imagenesProductoCarrusel[0]['url'] }}"
                                             class="product-image-preview h-72 w-full object-contain transition-opacity duration-150"
                                             :class="loading ? 'opacity-70' : 'opacity-100'"
                                             alt="Imagen de {{ $producto->nombre ?? 'producto' }}"
                                             loading="eager"
                                             decoding="async"
                                             fetchpriority="high">
                                    </a>

                                    <div x-show="loading"
                                         class="absolute inset-3 rounded-xl bg-white/70 backdrop-blur-[1px] flex items-center justify-center text-xs font-bold text-[#0f172a]"
                                         style="display: none;">
                                        Cargando imagen...
                                    </div>

                                    @if (count($imagenesProductoCarrusel) > 1)
                                        <button type="button"
                                                class="absolute left-5 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-[#0f172a]/80 text-white text-2xl leading-none flex items-center justify-center hover:bg-[#0f172a] transition"
                                                @click="previous()"
                                                aria-label="Imagen anterior">
                                            ‹
                                        </button>

                                        <button type="button"
                                                class="absolute right-5 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-[#0f172a]/80 text-white text-2xl leading-none flex items-center justify-center hover:bg-[#0f172a] transition"
                                                @click="next()"
                                                aria-label="Imagen siguiente">
                                            ›
                                        </button>

                                        <span class="absolute right-5 bottom-5 rounded-full bg-[#0f172a]/80 px-3 py-1 text-xs font-semibold text-white">
                                            <span x-text="index + 1">1</span>/{{ count($imagenesProductoCarrusel) }}
                                        </span>
                                    @endif

                                    <span class="absolute left-5 bottom-5 rounded-full bg-[#0f172a]/80 px-3 py-1 text-xs font-semibold text-white"
                                          x-text="images[index].tipo">
                                        {{ $imagenesProductoCarrusel[0]['tipo'] }}
                                    </span>
                                </div>
                            @else
                                <div class="product-image-empty h-72 flex items-center justify-center text-sm font-semibold">
                                    Sin imagen
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- DATOS ADICIONALES --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#e5d8c7] theme-border">
                        <h3 class="theme-title text-lg font-bold text-[#3b2818]">
                            Datos adicionales
                        </h3>

                        <p class="theme-text text-sm text-gray-500">
                            Códigos, descripción e indicadores del producto.
                        </p>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Código caja</p>
                            <p class="theme-title font-semibold text-gray-700 mt-1">
                                {{ $producto->codigo_caja ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Código precio</p>
                            <p class="theme-title font-semibold text-gray-700 mt-1">
                                {{ $producto->codigo_precio ?? 'N/A' }}
                            </p>
                        </div>

                        <div class="theme-soft rounded-2xl bg-[#fbf8f3] border border-[#e5d8c7] theme-border p-4">
                            <p class="theme-text text-xs text-gray-500">Descripción</p>
                            <p class="theme-title font-semibold text-gray-700 mt-1">
                                {{ $producto->descripcion ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6 flex flex-wrap gap-2">
                        <span class="product-flag-badge {{ $producto->anillo ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            Anillo: {{ $producto->anillo ? 'Sí' : 'No' }}
                        </span>

                        <span class="product-flag-badge {{ $producto->cello ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            Cello: {{ $producto->cello ? 'Sí' : 'No' }}
                        </span>

                        <span class="product-flag-badge {{ $producto->upc ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            UPC: {{ $producto->upc ? 'Sí' : 'No' }}
                        </span>

                        <span class="product-flag-badge {{ $producto->sampler ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            Sampler: {{ $producto->sampler ? 'Sí' : 'No' }}
                        </span>

                        <span class="product-flag-badge {{ $producto->caja_local ? 'is-yes' : 'is-no' }} px-3 py-1 rounded-full text-xs font-semibold border">
                            Caja local: {{ $producto->caja_local ? 'Sí' : 'No' }}
                        </span>
                    </div>
                </div>

                {{-- ACTIVIDADES --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#e5d8c7] theme-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="theme-title text-lg font-bold text-[#3b2818]">
                                Actividades del producto
                            </h3>

                            <p class="theme-text text-sm text-gray-500">
                                Actividades asociadas desde la API.
                            </p>
                        </div>

                        <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] w-fit">
                            {{ $producto->actividades->count() }} actividad(es)
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="theme-table-head bg-[#f3efe7] text-[#3b2818]">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold">Código</th>
                                    <th class="px-6 py-4 text-left font-semibold">Actividad</th>
                                    <th class="px-6 py-4 text-left font-semibold">Tipo empaque</th>
                                    <th class="px-6 py-4 text-left font-semibold">Precio MO</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($producto->actividades as $actividad)
                                    <tr class="theme-row border-b border-gray-100 theme-border hover:bg-[#faf7f2] transition">
                                        <td class="px-6 py-4">
                                            <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $actividad->codigo_actividad ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <p class="theme-title font-semibold text-[#3b2818]">
                                                {{ $actividad->nombre }}
                                            </p>

                                            <p class="theme-text text-[11px] text-gray-400">
                                                API ID: {{ $actividad->api_id_actividad }}
                                            </p>
                                        </td>

                                        <td class="px-6 py-4">
                                            @php
                                                $tipo = \App\Models\TipoEmpaque::find($actividad->pivot->tipo_empaque_id);
                                            @endphp

                                            <span class="theme-text text-gray-700">
                                                {{ $tipo?->nombre ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="theme-title font-semibold text-[#3b2818]">
                                                {{ number_format($actividad->pivot->precio_mo, 7) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="theme-text px-6 py-10 text-center text-gray-500">
                                            Este producto no tiene actividades registradas.
                                        </td>
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

</body>
</html>
