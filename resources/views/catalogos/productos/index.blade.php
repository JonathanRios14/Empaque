<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800">

<div x-data="{ sidebarOpen: true, catalogos: true, seguridad: false, produccion: false }" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Productos',
            'description' => 'Catálogo de productos sincronizado desde la API.'
        ])

        <section class="p-6">
            <div class="max-w-7xl mx-auto">

                <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#e5d8c7]">
                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-[#3b2818]">
                                    Listado de productos
                                </h2>
                                                                <p class="text-sm text-gray-500">Aquí puedes ver todos los productos sincronizados.</p>

                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <form method="GET" action="{{ route('catalogos.productos.index') }}" class="flex gap-2">
                                    <div class="relative">
                                        <input type="text"
                                               name="buscar"
                                               value="{{ request('buscar') }}"
                                               placeholder="Buscar producto, marca, vitola o código"
                                               class="w-80 rounded-xl border-gray-300 text-sm pr-10 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                                        @if(request('buscar'))
                                            <a href="{{ route('catalogos.productos.index') }}"
                                               class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:text-[#5b3a1e] hover:bg-[#f3efe7] transition"
                                               title="Limpiar búsqueda">
                                                ×
                                            </a>
                                        @endif
                                    </div>

                                    <button type="submit"
                                            class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                        Buscar
                                    </button>
                                </form>

                                @can('productos.sincronizar')
                                    <form method="POST" action="{{ route('catalogos.productos.sincronizar') }}">
                                        @csrf

                                        <button type="submit"
                                                class="w-full sm:w-auto px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                            Sincronizar
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>

                    @php
                        $sortLink = function ($campo) {
                            $ordenActual = request('orden', 'created_at');
                            $direccionActual = request('direccion', 'desc');

                            $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

                            return route('catalogos.productos.index', array_merge(request()->query(), [
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
                            <thead class="bg-[#f3efe7] text-[#3b2818]">
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
                                    <tr class="border-b border-gray-100 hover:bg-[#faf7f2] transition">
                                        <td class="px-6 py-4">
                                           <a href="{{ route('catalogos.productos.show', array_merge(['producto' => $producto->id], request()->query())) }}"
   class="font-semibold text-[#3b2818] hover:text-[#5b3a1e] hover:underline transition">
    {{ $producto->nombre ?? 'Sin nombre' }}
</a>

                                            <p class="text-xs text-gray-500">
                                                Item: {{ $producto->item ?? 'N/A' }}
                                            </p>

                                            <p class="text-[11px] text-gray-400">
                                                Código: {{ $producto->codigo_producto ?? 'N/A' }}
                                            </p>

                                            <p class="text-[11px] text-gray-400">
                                                API ID: {{ $producto->api_id_producto }}
                                            </p>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $producto->marca?->nombre ?? 'N/A' }}
                                            </span>

                                            <p class="text-[11px] text-gray-400 mt-1">
                                                {{ $producto->empresa?->nombre ?? '' }}
                                            </p>
                                        </td>

                                        <td class="px-6 py-4">
                                            {{ $producto->vitola?->nombre ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4">
                                            {{ $producto->capa?->nombre ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4">
                                            {{ $producto->tipoEmpaque?->nombre ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="font-semibold text-[#3b2818]">
                                                {{ number_format($producto->precio, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
    <a href="{{ route('catalogos.productos.show', array_merge(['producto' => $producto->id], request()->query())) }}"
   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold hover:bg-[#e5d8c7] transition">
    Ver
    <span>→</span>
</a>
</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                            No hay productos registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-3 border-t border-[#e5d8c7] bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p class="text-sm text-gray-500">
                            Mostrando
                            <span class="font-semibold text-[#3b2818]">{{ $productos->firstItem() ?? 0 }}</span>
                            a
                            <span class="font-semibold text-[#3b2818]">{{ $productos->lastItem() ?? 0 }}</span>
                            de
                            <span class="font-semibold text-[#3b2818]">{{ $productos->total() }}</span>
                            producto(s)
                        </p>

                        <div>
                            {{ $productos->onEachSide(1)->links('pagination.cafe') }}
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