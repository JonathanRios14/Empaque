<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actividades | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800">

<div x-data="{ sidebarOpen: true, catalogos: true, seguridad: false, produccion: false }" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">
        @include('layouts.topbar', [
            'title' => 'Actividades',
            'description' => 'Catálogo de actividades sincronizadas desde la API.'
        ])

        <section class="p-6">
            <div class="max-w-6xl mx-auto">
                <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6 border-b border-[#e5d8c7]">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-[#3b2818]">Listado de actividades</h2>
                                <p class="text-sm text-gray-500">Aquí puedes ver las actividades relacionadas a productos.</p>
                            </div>

                            <form method="GET" action="{{ route('catalogos.actividades.index') }}" class="flex gap-2">
                                <div class="relative">
                                    <input type="text"
                                           name="buscar"
                                           value="{{ request('buscar') }}"
                                           placeholder="Buscar actividad o código"
                                           class="w-72 rounded-xl border-gray-300 text-sm pr-10 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                                    @if(request('buscar'))
                                        <a href="{{ route('catalogos.actividades.index') }}"
                                           class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:text-[#5b3a1e] hover:bg-[#f3efe7] transition">
                                            ×
                                        </a>
                                    @endif
                                </div>

                                <button type="submit"
                                        class="px-4 py-2 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                    Buscar
                                </button>
                            </form>
                        </div>
                    </div>

                    @php
    $sortLink = function ($campo) {
        $ordenActual = request('orden', 'nombre');
        $direccionActual = request('direccion', 'asc');

        $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

        return url()->current() . '?' . http_build_query(array_merge(request()->query(), [
            'orden' => $campo,
            'direccion' => $nuevaDireccion,
            'page' => null,
        ]));
    };

    $sortIcon = function ($campo) {
        $ordenActual = request('orden', 'nombre');
        $direccionActual = request('direccion', 'asc');

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
            <a href="{{ $sortLink('id') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                ID
                <span class="text-xs">{{ $sortIcon('id') }}</span>
            </a>
        </th>

        <th class="px-6 py-4 text-left font-semibold">
            <a href="{{ $sortLink('codigo_actividad') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                Código
                <span class="text-xs">{{ $sortIcon('codigo_actividad') }}</span>
            </a>
        </th>

        <th class="px-6 py-4 text-left font-semibold">
            <a href="{{ $sortLink('nombre') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                Actividad
                <span class="text-xs">{{ $sortIcon('nombre') }}</span>
            </a>
        </th>

        <th class="px-6 py-4 text-left font-semibold">
            <a href="{{ $sortLink('productos_count') }}" class="inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                Productos
                <span class="text-xs">{{ $sortIcon('productos_count') }}</span>
            </a>
        </th>
    </tr>
</thead>

                            <tbody>
                                @forelse ($actividades as $actividad)
                                    <tr class="border-b border-gray-100 hover:bg-[#faf7f2] transition">
                                        <td class="px-6 py-4 text-gray-500">#{{ $actividad->id }}</td>

                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $actividad->codigo_actividad ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <p class="font-semibold text-[#3b2818]">
                                                {{ $actividad->nombre }}
                                            </p>

                                            <p class="text-[11px] text-gray-400 mt-0.5">
                                                API ID: {{ $actividad->api_id_actividad }}
                                            </p>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                {{ $actividad->productos_count }} producto(s)
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                            No hay actividades registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-3 border-t border-[#e5d8c7] bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p class="text-sm text-gray-500">
                            Mostrando
                            <span class="font-semibold text-[#3b2818]">{{ $actividades->firstItem() ?? 0 }}</span>
                            a
                            <span class="font-semibold text-[#3b2818]">{{ $actividades->lastItem() ?? 0 }}</span>
                            de
                            <span class="font-semibold text-[#3b2818]">{{ $actividades->total() }}</span>
                            actividad(es)
                        </p>

                        <div>
                            {{ $actividades->onEachSide(1)->links('pagination.cafe') }}
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