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

<div id="productosTableInner" class="productos-table-inner relative">
<div class="productos-table-scroll catalogo-table-scroll vinetas-table-scroll">
    <table class="vinetas-table w-full text-sm">
        <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
            <tr>
                <th class="px-4 py-3 text-left font-semibold min-w-[220px]">
                    <a href="{{ $sortLink('nombre') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Producto
                        <span class="text-xs">{{ $sortIcon('nombre') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('item') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Item
                        <span class="text-xs">{{ $sortIcon('item') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('codigo_producto') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Código
                        <span class="text-xs">{{ $sortIcon('codigo_producto') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('presentacion') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Presentación
                        <span class="text-xs">{{ $sortIcon('presentacion') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('marca') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Marca
                        <span class="text-xs">{{ $sortIcon('marca') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('vitola') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Vitola
                        <span class="text-xs">{{ $sortIcon('vitola') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('capa') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Capa
                        <span class="text-xs">{{ $sortIcon('capa') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('tipo_empaque') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Tipo empaque
                        <span class="text-xs">{{ $sortIcon('tipo_empaque') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('precio') }}"
                       class="ajax-table-link inline-flex items-center gap-2 hover:text-[#5b3a1e]">
                        Precio
                        <span class="text-xs">{{ $sortIcon('precio') }}</span>
                    </a>
                </th>

                <th class="px-4 py-3 text-right font-semibold whitespace-nowrap">
                    <a href="{{ $sortLink('actividades_count') }}"
                       class="ajax-table-link inline-flex items-center justify-end gap-2 hover:text-[#5b3a1e]">
                        Acciones
                        <span class="text-xs">{{ $sortIcon('actividades_count') }}</span>
                    </a>
                </th>
            </tr>
        </thead>

        <tbody>
            @forelse ($productos as $producto)
                @php
                    $imagenPrincipalUrl = $producto->imagenPrincipalUrl();
                    $cargarImagenDirecta = $loop->iteration <= 8;
                    $actividadesCount = (int) ($producto->actividades_count ?? $producto->actividades->count());
                @endphp

                <tr class="theme-row border-b border-gray-100 theme-border hover:bg-[#faf7f2] transition">
                    {{-- Producto: imagen + nombre + API ID --}}
                    <td class="px-4 py-3 min-w-[220px]">
                        <div class="flex items-center gap-3">
                            @if ($imagenPrincipalUrl)
                                @if ($cargarImagenDirecta)
                                    <img src="{{ $imagenPrincipalUrl }}"
                                         class="product-thumb h-14 w-14 rounded-xl object-cover border theme-border shrink-0"
                                         alt="Imagen de {{ $producto->nombre ?? 'producto' }}"
                                         loading="eager"
                                         decoding="async"
                                         fetchpriority="{{ $loop->iteration <= 3 ? 'high' : 'auto' }}"
                                         width="56"
                                         height="56">
                                @else
                                    <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="
                                         data-product-image-src="{{ $imagenPrincipalUrl }}"
                                         class="product-thumb product-lazy-image h-14 w-14 rounded-xl object-cover border theme-border shrink-0"
                                         alt="Imagen de {{ $producto->nombre ?? 'producto' }}"
                                         loading="lazy"
                                         decoding="async"
                                         width="56"
                                         height="56">
                                @endif
                            @else
                                <div class="product-thumb-placeholder h-14 w-14 rounded-xl border theme-border flex items-center justify-center text-xs font-bold shrink-0">
                                    IMG
                                </div>
                            @endif

                            <div class="min-w-0">
                                <a href="{{ route('catalogos.productos.show', array_merge(['producto' => $producto->id], request()->query())) }}"
                                   class="theme-title font-semibold text-[#3b2818] hover:text-[#5b3a1e] hover:underline transition">
                                    {{ $producto->nombre ?? 'Sin nombre' }}
                                </a>

                                <p class="theme-text text-[11px] text-gray-400 mt-0.5">
                                    API ID: {{ $producto->api_id_producto }}
                                </p>
                            </div>
                        </div>
                    </td>

                    {{-- Item --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-text text-xs font-medium text-gray-700">
                            {{ $producto->item ?? '—' }}
                        </span>
                    </td>

                    {{-- Código de producto --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-text text-xs text-gray-700">
                            {{ $producto->codigo_producto ?? '—' }}
                        </span>
                    </td>

                    {{-- Presentación --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-text text-xs text-gray-700">
                            {{ $producto->presentacion?->nombre ?? '—' }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-badge inline-flex items-center px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                            {{ $producto->marca?->nombre ?? 'N/A' }}
                        </span>
                        <p class="theme-text text-[11px] text-gray-400 mt-1">
                            {{ $producto->empresa?->nombre ?? '' }}
                        </p>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-text text-gray-700">
                            {{ $producto->vitola?->nombre ?? 'N/A' }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-text text-gray-700">
                            {{ $producto->capa?->nombre ?? 'N/A' }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-text text-gray-700">
                            {{ $producto->tipoEmpaque?->nombre ?? 'N/A' }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="theme-title font-semibold text-[#3b2818]">
                            {{ number_format($producto->precio ?? 0, 2) }}
                        </span>
                    </td>

                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('catalogos.productos.show', array_merge(['producto' => $producto->id], request()->query())) }}"
                           title="{{ $actividadesCount === 0 ? 'Este producto no tiene actividades asociadas.' : 'Ver producto' }}"
                           class="theme-button-secondary relative overflow-hidden inline-flex items-center gap-1 px-3 py-1.5 {{ $actividadesCount === 0 ? 'pb-2.5' : '' }} rounded-lg bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7] hover:bg-[#e5d8c7] transition">
                            Ver
                            <span>→</span>
                            @if($actividadesCount === 0)
                                <span class="absolute inset-x-0 bottom-0 h-1 bg-red-500"></span>
                            @endif
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="theme-text px-4 py-10 text-center text-gray-500">
                        No hay productos registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="productosFloatingScroll"
     class="vinetas-floating-scrollbar"
     aria-hidden="true">
    <div class="vinetas-floating-scrollbar-inner"></div>
</div>

<div class="theme-soft px-6 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <p class="theme-text text-sm text-gray-500">
            Mostrando
            <span class="theme-title font-semibold text-[#3b2818]">{{ $productos->firstItem() ?? 0 }}</span>
            a
            <span class="theme-title font-semibold text-[#3b2818]">{{ $productos->lastItem() ?? 0 }}</span>
            de
            <span class="theme-title font-semibold text-[#3b2818]">{{ $productos->total() }}</span>
            producto(s)
        </p>

        <form method="GET"
              action="{{ route('catalogos.productos.index') }}"
              class="per-page-control ajax-per-page-form">
            @foreach(request()->except('per_page', 'page') as $key => $value)
                @if(is_array($value))
                    @foreach($value as $item)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <label class="per-page-label">
                Mostrar:
            </label>

            <select name="per_page"
                    onchange="this.form.requestSubmit()"
                    class="per-page-select">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($perPageSelected === $option)>{{ $option }}</option>
                @endforeach
                <option value="all" @selected($perPageSelected === 'all')>Todos</option>
            </select>
        </form>
    </div>

    <div class="pagination-cafe ajax-pagination">
        {{ $productos->onEachSide(1)->links('pagination.cafe') }}
    </div>
</div>
</div>
