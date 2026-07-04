<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Producto | Sistema de Empaque</title>
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

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

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
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $producto->anillo ? 'bg-green-50 text-green-700 border-green-200' : 'theme-button-secondary bg-gray-100 text-gray-500 border-gray-200' }}">
                            Anillo: {{ $producto->anillo ? 'Sí' : 'No' }}
                        </span>

                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $producto->cello ? 'bg-green-50 text-green-700 border-green-200' : 'theme-button-secondary bg-gray-100 text-gray-500 border-gray-200' }}">
                            Cello: {{ $producto->cello ? 'Sí' : 'No' }}
                        </span>

                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $producto->upc ? 'bg-green-50 text-green-700 border-green-200' : 'theme-button-secondary bg-gray-100 text-gray-500 border-gray-200' }}">
                            UPC: {{ $producto->upc ? 'Sí' : 'No' }}
                        </span>

                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $producto->sampler ? 'bg-green-50 text-green-700 border-green-200' : 'theme-button-secondary bg-gray-100 text-gray-500 border-gray-200' }}">
                            Sampler: {{ $producto->sampler ? 'Sí' : 'No' }}
                        </span>

                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $producto->caja_local ? 'bg-green-50 text-green-700 border-green-200' : 'theme-button-secondary bg-gray-100 text-gray-500 border-gray-200' }}">
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
