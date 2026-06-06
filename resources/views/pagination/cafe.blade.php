@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex items-center justify-end gap-2">
        {{-- Botón anterior --}}
        @if ($paginator->onFirstPage())
            <span class="px-3 py-1.5 rounded-lg bg-[#f9f5ee] text-[#b8a895] text-sm border border-[#e5d8c7] cursor-not-allowed">
                ‹
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               class="px-3 py-1.5 rounded-lg bg-white text-[#5b3a1e] text-sm border border-[#e5d8c7] hover:bg-[#f3efe7] transition">
                ‹
            </a>
        @endif

        {{-- Números --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-1.5 rounded-lg bg-white text-gray-400 text-sm border border-[#e5d8c7]">
                    {{ $element }}
                </span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3 py-1.5 rounded-lg bg-[#5b3a1e] text-white text-sm border border-[#5b3a1e] font-semibold">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="px-3 py-1.5 rounded-lg bg-white text-[#5b3a1e] text-sm border border-[#e5d8c7] hover:bg-[#f3efe7] transition">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Botón siguiente --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               class="px-3 py-1.5 rounded-lg bg-white text-[#5b3a1e] text-sm border border-[#e5d8c7] hover:bg-[#f3efe7] transition">
                ›
            </a>
        @else
            <span class="px-3 py-1.5 rounded-lg bg-[#f9f5ee] text-[#b8a895] text-sm border border-[#e5d8c7] cursor-not-allowed">
                ›
            </span>
        @endif
    </nav>
@endif