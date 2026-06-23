@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex items-center justify-between gap-3">
        {{-- Mobile --}}
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl border theme-border theme-button-secondary opacity-50 cursor-not-allowed">
                    Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="relative inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl border theme-border theme-button-secondary transition">
                    Anterior
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="relative inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl border theme-border theme-button-secondary transition">
                    Siguiente
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold rounded-xl border theme-border theme-button-secondary opacity-50 cursor-not-allowed">
                    Siguiente
                </span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-end">
            <div>
                <span class="relative z-0 inline-flex items-center gap-1 rounded-2xl">

                    {{-- Previous --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true"
                              aria-label="Anterior"
                              class="pagination-item opacity-40 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}"
                           rel="prev"
                           aria-label="Anterior"
                           class="pagination-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    @endif

                    {{-- Elements --}}
                    @foreach ($elements as $element)
                        {{-- Dots --}}
                        @if (is_string($element))
                            <span aria-disabled="true" class="pagination-dots">
                                {{ $element }}
                            </span>
                        @endif

                        {{-- Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="pagination-item pagination-active">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="pagination-item">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}"
                           rel="next"
                           aria-label="Siguiente"
                           class="pagination-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true"
                              aria-label="Siguiente"
                              class="pagination-item opacity-40 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    @endif

                </span>
            </div>
        </div>
    </nav>
@endif