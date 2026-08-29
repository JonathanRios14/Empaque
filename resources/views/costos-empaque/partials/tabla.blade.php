@php
    $queryParams = request()->except(['orden', 'direccion', 'page']);
    $sortLink = function (string $columna) use ($queryParams, $orden, $direccion) {
        $nuevaDireccion = ($orden === $columna && $direccion === 'asc') ? 'desc' : 'asc';
        return route('costos-empaque.index', array_merge($queryParams, [
            'orden' => $columna,
            'direccion' => $nuevaDireccion,
            'page' => 1,
        ]));
    };
    $sortIcon = function (string $columna) use ($orden, $direccion) {
        if ($orden !== $columna) {
            return '↕';
        }
        return $direccion === 'asc' ? '↑' : '↓';
    };
@endphp

<div id="costosEmpaqueTableInner" class="productos-table-inner relative">
    <div class="productos-table-scroll catalogo-table-scroll vinetas-table-scroll">
        <table class="vinetas-table w-full text-sm">
            <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
                <tr>
                    {{-- 1. Fecha del registro --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('fecha_registro') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Fecha
                            <span class="text-xs">{{ $sortIcon('fecha_registro') }}</span>
                        </a>
                    </th>

                    {{-- 2. Empleado --}}
                    <th class="px-4 py-3 text-left font-bold min-w-[200px]">
                        <a href="{{ $sortLink('empleado') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Empleado
                            <span class="text-xs">{{ $sortIcon('empleado') }}</span>
                        </a>
                    </th>

                    {{-- 3. Item --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('producto_item') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Item
                            <span class="text-xs">{{ $sortIcon('producto_item') }}</span>
                        </a>
                    </th>

                    {{-- 4. Presentación --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('presentacion') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Presentación
                            <span class="text-xs">{{ $sortIcon('presentacion') }}</span>
                        </a>
                    </th>

                    {{-- 5. Código del producto --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('producto_codigo') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Código del producto
                            <span class="text-xs">{{ $sortIcon('producto_codigo') }}</span>
                        </a>
                    </th>

                    {{-- 6. Marca --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('marca') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Marca
                            <span class="text-xs">{{ $sortIcon('marca') }}</span>
                        </a>
                    </th>

                    {{-- 7. Nombre --}}
                    <th class="px-4 py-3 text-left font-bold min-w-[200px]">
                        <a href="{{ $sortLink('producto_nombre') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Nombre
                            <span class="text-xs">{{ $sortIcon('producto_nombre') }}</span>
                        </a>
                    </th>

                    {{-- 8. Vitola --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('vitola') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Vitola
                            <span class="text-xs">{{ $sortIcon('vitola') }}</span>
                        </a>
                    </th>

                    {{-- 9. Capa --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('capa') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Capa
                            <span class="text-xs">{{ $sortIcon('capa') }}</span>
                        </a>
                    </th>

                    {{-- 10. Tipo de empaque --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('tipo_empaque') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Tipo de empaque
                            <span class="text-xs">{{ $sortIcon('tipo_empaque') }}</span>
                        </a>
                    </th>

                    {{-- 11. Orden del sistema --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('orden_del_sistema') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Orden del sistema
                            <span class="text-xs">{{ $sortIcon('orden_del_sistema') }}</span>
                        </a>
                    </th>

                    {{-- 12. Orden del cliente --}}
                    <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('orden_cliente') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Orden del cliente
                            <span class="text-xs">{{ $sortIcon('orden_cliente') }}</span>
                        </a>
                    </th>

                    {{-- 13. Actividad --}}
                    <th class="px-4 py-3 text-left font-bold min-w-[180px]">
                        <a href="{{ $sortLink('actividad') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Actividad
                            <span class="text-xs">{{ $sortIcon('actividad') }}</span>
                        </a>
                    </th>

                    {{-- 14. Cantidad trabajada --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('cantidad_trabajada') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Cantidad trabajada
                            <span class="text-xs">{{ $sortIcon('cantidad_trabajada') }}</span>
                        </a>
                    </th>

                    {{-- 15. Cantidad defectuosa --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        Cantidad defectuosa
                    </th>

                    {{-- 16. Cantidad pagada --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('cantidad_pagada') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Cantidad pagada
                            <span class="text-xs">{{ $sortIcon('cantidad_pagada') }}</span>
                        </a>
                    </th>

                    {{-- 17. Precio unitario --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('precio_unitario') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Precio unitario
                            <span class="text-xs">{{ $sortIcon('precio_unitario') }}</span>
                        </a>
                    </th>

                    {{-- 18. Total MOD --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('total_mod') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Total MOD
                            <span class="text-xs">{{ $sortIcon('total_mod') }}</span>
                        </a>
                    </th>

                    {{-- 19. Cantidad ctrl calidad --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('cantidad_ctrl_calidad') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            Cantidad ctrl calidad
                            <span class="text-xs">{{ $sortIcon('cantidad_ctrl_calidad') }}</span>
                        </a>
                    </th>

                    {{-- 20. H trabajada --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        <a href="{{ $sortLink('h_trabajada') }}"
                           class="costos-ajax-table-link inline-flex items-center gap-2 hover:text-[#2563eb]">
                            H trabajada
                            <span class="text-xs">{{ $sortIcon('h_trabajada') }}</span>
                        </a>
                    </th>

                    {{-- 21. Horas extras --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        Horas extras
                    </th>

                    {{-- 22. Costos indirectos varios --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        Costos indirectos varios
                    </th>

                    {{-- 23. Complemento --}}
                    <th class="px-4 py-3 text-center font-bold whitespace-nowrap">
                        Complemento
                    </th>

                    {{-- 24. Costos suministros --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        Costos suministros
                    </th>

                    {{-- 25. Mano de obra indirecta --}}
                    <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                        Mano de obra indirecta
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y theme-divide">
                @forelse ($filas as $fila)
                    <tr class="vinetas-table-row transition theme-row">
                        {{-- 1. Fecha del registro --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-text font-medium">
                            {{ $fila->fecha_registro ? \Carbon\Carbon::parse($fila->fecha_registro)->format('d/m/Y') : 'N/A' }}
                        </td>

                        {{-- 2. Empleado --}}
                        <td class="px-4 py-3 min-w-[200px]">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-[#0f172a] text-white flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ strtoupper(substr($fila->empleado_nombre ?? 'E', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="theme-title font-semibold leading-tight truncate">
                                        {{ $fila->empleado_nombre ?: 'Sin nombre' }}
                                    </p>
                                    <p class="theme-text text-xs mt-0.5">
                                        {{ $fila->empleado_codigo ?: 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- 3. Item --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $fila->producto_item ?: 'N/A' }}
                        </td>

                        {{-- 4. Presentación --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $fila->presentacion ?: 'N/A' }}
                        </td>

                        {{-- 5. Código del producto --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $fila->producto_codigo ?: 'N/A' }}
                        </td>

                        {{-- 6. Marca --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $fila->marca ?: 'N/A' }}
                        </td>

                        {{-- 7. Nombre --}}
                        <td class="px-4 py-3 min-w-[200px] theme-text">
                            <p class="leading-tight">{{ $fila->producto_nombre ?: 'N/A' }}</p>
                        </td>

                        {{-- 8. Vitola --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $fila->vitola ?: 'N/A' }}
                        </td>

                        {{-- 9. Capa --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $fila->capa ?: 'N/A' }}
                        </td>

                        {{-- 10. Tipo de empaque --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-text">
                            {{ $fila->tipo_empaque ?: 'N/A' }}
                        </td>

                        {{-- 11. Orden del sistema --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $fila->orden_del_sistema ?: 'N/A' }}
                        </td>

                        {{-- 12. Orden del cliente --}}
                        <td class="px-4 py-3 whitespace-nowrap theme-title font-semibold">
                            {{ $fila->orden_cliente ?: 'N/A' }}
                        </td>

                        {{-- 13. Actividad --}}
                        <td class="px-4 py-3 min-w-[180px]">
                            <p class="theme-title font-semibold leading-tight">{{ $fila->actividad_nombre ?: 'N/A' }}</p>
                            @if (!empty($fila->actividad_codigo))
                                <p class="theme-text text-xs mt-0.5">{{ $fila->actividad_codigo }}</p>
                            @endif
                        </td>

                        {{-- 14. Cantidad trabajada --}}
                        <td class="px-4 py-3 text-right font-bold whitespace-nowrap theme-title">
                            {{ number_format((float) $fila->cantidad_trabajada) }}
                        </td>

                        {{-- 15. Cantidad defectuosa --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap theme-text">
                            0
                        </td>

                        {{-- 16. Cantidad pagada --}}
                        <td class="px-4 py-3 text-right font-bold whitespace-nowrap theme-title">
                            {{ number_format((float) $fila->cantidad_pagada) }}
                        </td>

                        {{-- 17. Precio unitario --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap theme-text">
                            {{ number_format((float) ($fila->precio_unitario ?? 0), 4) }}
                        </td>

                        {{-- 18. Total MOD --}}
                        <td class="px-4 py-3 text-right font-bold whitespace-nowrap text-cyan-600 dark:text-cyan-400">
                            {{ number_format((float) $fila->total_mod, 4) }}
                        </td>

                        {{-- 19. Cantidad ctrl calidad --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap theme-text">
                            {{ number_format((float) $fila->cantidad_ctrl_calidad) }}
                        </td>

                        {{-- 20. H trabajada --}}
                        <td class="px-4 py-3 text-right font-semibold whitespace-nowrap theme-title">
                            {{ number_format((float) $fila->h_trabajada, 2) }}
                        </td>

                        {{-- 21. Horas extras --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap theme-text">
                            0
                        </td>

                        {{-- 22. Costos indirectos varios --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap theme-text">
                            0.00
                        </td>

                        {{-- 23. Complemento --}}
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <input type="checkbox"
                                   class="rounded border-gray-300 text-[#2563eb] shadow-sm focus:ring-[#2563eb] h-4 w-4 cursor-pointer"
                                   aria-label="Complemento">
                        </td>

                        {{-- 24. Costos suministros --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap theme-text">
                            0.00
                        </td>

                        {{-- 25. Mano de obra indirecta --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap theme-text">
                            0.00
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="25" class="px-4 py-12 text-center theme-text">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-base font-semibold theme-title">No hay registros de costos de empaque</p>
                                <p class="text-xs text-gray-400">Intenta ajustar los filtros de búsqueda o el rango de fechas.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="costosEmpaqueFloatingScroll"
         class="vinetas-floating-scrollbar"
         aria-hidden="true">
        <div class="vinetas-floating-scrollbar-inner"></div>
    </div>

    <div class="vineta-registros-table-footer theme-soft px-4 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="theme-text text-sm text-gray-500">
                Mostrando
                <span class="theme-title font-semibold text-[#3b2818]">{{ $filas->firstItem() ?? 0 }}</span>
                a
                <span class="theme-title font-semibold text-[#3b2818]">{{ $filas->lastItem() ?? 0 }}</span>
                de
                <span class="theme-title font-semibold text-[#3b2818]">{{ $filas->total() }}</span>
                registro(s)
            </p>

            <form method="GET"
                  action="{{ route('costos-empaque.index') }}"
                  class="per-page-control costos-ajax-per-page-form">
                @foreach (request()->except(['per_page', 'page']) as $key => $val)
                    @if (is_array($val))
                        @foreach ($val as $subVal)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}">
                        @endforeach
                    @elseif ($val !== null && $val !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach

                <label class="per-page-label">Mostrar:</label>
                <select name="per_page"
                        onchange="this.form.requestSubmit()"
                        class="per-page-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($perPageSelected === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <span class="per-page-label">por pág.</span>
            </form>
        </div>

        <div class="costos-ajax-pagination">
            {{ $filas->onEachSide(1)->links('pagination.cafe') }}
        </div>
    </div>
</div>
