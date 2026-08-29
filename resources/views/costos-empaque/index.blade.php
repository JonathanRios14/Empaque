<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Costos empaque | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="vinetas-page min-h-screen theme-bg antialiased">
    <div
        x-data="{
            sidebarOpen: false,
            catalogos: false,
            seguridad: false,
            produccion: true
        }"
        class="min-h-screen flex theme-bg">

        @include('layouts.sidebar')

        <div class="flex-1 min-w-0 flex flex-col">
            @include('layouts.topbar')

            <main class="flex-1 min-w-0">
                <section class="app-content-compact">
                    <div class="w-full max-w-none space-y-3">

                        {{-- Header & Stats Strip --}}
                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3.5 sm:p-4">
                            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                                {{-- Header: Icon & Title --}}
                                <div class="shrink-0">
                                    <div class="flex items-center gap-3">
                                        <div class="section-title-icon vinetas-header-icon w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>

                                        <div class="shrink-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-black tracking-tight leading-tight whitespace-nowrap">
                                                Costos empaque
                                            </h1>
                                        </div>
                                    </div>
                                </div>

                                {{-- Stats Strip (4 metrics in single row) --}}
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 lg:gap-8 pt-3 xl:pt-0 border-t xl:border-t-0 theme-border shrink-0">
                                    {{-- Cant. Trabajada --}}
                                    <div class="text-left sm:text-right min-w-[5.5rem] sm:min-w-[7rem]">
                                        <div class="flex items-center sm:justify-end gap-1.5 mb-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                            <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em] whitespace-nowrap">Cant. Trabajada</span>
                                        </div>
                                        <p class="theme-title text-base sm:text-lg lg:text-[1.35rem] font-black tracking-tight tabular-nums whitespace-nowrap leading-tight">{{ number_format($totales['cantidad_trabajada']) }}</p>
                                    </div>

                                    {{-- Cant. Pagada --}}
                                    <div class="text-left sm:text-right min-w-[5.5rem] sm:min-w-[7rem]">
                                        <div class="flex items-center sm:justify-end gap-1.5 mb-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                            <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em] whitespace-nowrap">Cant. Pagada</span>
                                        </div>
                                        <p class="theme-title text-base sm:text-lg lg:text-[1.35rem] font-black tracking-tight tabular-nums whitespace-nowrap leading-tight">{{ number_format($totales['cantidad_pagada']) }}</p>
                                    </div>

                                    {{-- Total MOD --}}
                                    <div class="text-left sm:text-right min-w-[6.5rem] sm:min-w-[8.5rem]">
                                        <div class="flex items-center sm:justify-end gap-1.5 mb-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                                            <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em] whitespace-nowrap">Total MOD</span>
                                        </div>
                                        <p class="text-base sm:text-lg lg:text-[1.35rem] font-black tracking-tight tabular-nums whitespace-nowrap leading-tight text-cyan-600 dark:text-cyan-400">{{ number_format($totales['total_mod'], 4) }}</p>
                                    </div>

                                    {{-- H Trabajada --}}
                                    <div class="text-left sm:text-right min-w-[5.5rem] sm:min-w-[7rem]">
                                        <div class="flex items-center sm:justify-end gap-1.5 mb-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                            <span class="theme-text text-[10px] sm:text-[11px] font-black uppercase tracking-[.14em] whitespace-nowrap">H Trabajada</span>
                                        </div>
                                        <p class="theme-title text-base sm:text-lg lg:text-[1.35rem] font-black tracking-tight tabular-nums whitespace-nowrap leading-tight">{{ number_format($totales['h_trabajada'], 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Filter Card (Only 3 Filters) --}}
                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                            <form id="costosEmpaqueFilterForm"
                                  method="GET"
                                  action="{{ route('costos-empaque.index') }}"
                                  class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 items-end">

                                {{-- Desde --}}
                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Desde</label>
                                    <input type="date"
                                           name="fecha_desde"
                                           value="{{ request('fecha_desde') }}"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                {{-- Hasta --}}
                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Hasta</label>
                                    <input type="date"
                                           name="fecha_hasta"
                                           value="{{ request('fecha_hasta') }}"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                {{-- Empleado --}}
                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Empleado</label>
                                    <input type="text"
                                           name="empleado"
                                           value="{{ request('empleado') }}"
                                           placeholder="Código o nombre"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center justify-end gap-2 pt-1">
                                    <a href="{{ route('costos-empaque.index') }}"
                                       class="costos-ajax-clear-filters gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-2.5 rounded-xl bg-white text-[#0b1220] text-sm font-bold border theme-border hover:bg-[#f1f5f9] transition">
                                        Limpiar
                                    </a>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-[#0f172a] text-white text-sm font-black hover:bg-[#1e293b] transition">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Main Table Container --}}
                        <div id="costosEmpaqueTableContainer" class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible relative">
                            @include('costos-empaque.partials.tabla')
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let stickyHeaderClone = null;
        let floatingScroll = null;
        let boundFloatingScroll = null;
        let syncingFloatingScroll = false;
        let windowEventsBound = false;

        const getTableContainer = () => document.getElementById('costosEmpaqueTableContainer');
        const getTableScroll = () => getTableContainer()?.querySelector('.productos-table-scroll');

        const getTopbarBottom = () => {
            const topbar = document.querySelector('header, .app-topbar, [data-topbar]');
            return topbar ? Math.max(0, topbar.getBoundingClientRect().bottom) : 0;
        };

        const removeStickyHeaderClone = () => {
            if (stickyHeaderClone && stickyHeaderClone.parentNode) {
                stickyHeaderClone.parentNode.removeChild(stickyHeaderClone);
            }
            stickyHeaderClone = null;
        };

        const syncStickyHeaderClone = () => {
            const scroll = getTableScroll();
            const table = scroll?.querySelector('.vinetas-table');
            const header = table?.querySelector('thead');

            if (!scroll || !table || !header || !stickyHeaderClone) {
                return;
            }

            const tableRect = table.getBoundingClientRect();
            const headerRect = header.getBoundingClientRect();
            const scrollRect = scroll.getBoundingClientRect();
            const topbarBottom = getTopbarBottom();

            const shouldShow = headerRect.top < topbarBottom
                && tableRect.bottom > topbarBottom + headerRect.height;

            stickyHeaderClone.classList.toggle('is-visible', shouldShow);

            if (!shouldShow) {
                return;
            }

            stickyHeaderClone.style.top = `${topbarBottom}px`;
            stickyHeaderClone.style.left = `${scrollRect.left}px`;
            stickyHeaderClone.style.width = `${scrollRect.width}px`;

            const cloneTable = stickyHeaderClone.querySelector('table');
            if (cloneTable) {
                cloneTable.style.width = `${table.scrollWidth}px`;
                cloneTable.style.minWidth = `${table.scrollWidth}px`;
                cloneTable.style.transform = `translateX(${-scroll.scrollLeft}px)`;
            }

            const originalHeaders = [...header.querySelectorAll('th')];
            const cloneHeaders = [...stickyHeaderClone.querySelectorAll('th')];
            cloneHeaders.forEach((th, index) => {
                const width = originalHeaders[index]?.getBoundingClientRect().width;
                if (width) {
                    th.style.width = `${width}px`;
                    th.style.minWidth = `${width}px`;
                    th.style.maxWidth = `${width}px`;
                }
            });
        };

        const syncFloatingScrollFromTable = () => {
            const scroll = getTableScroll();
            if (!scroll || !floatingScroll || syncingFloatingScroll) {
                return;
            }
            syncingFloatingScroll = true;
            floatingScroll.scrollLeft = scroll.scrollLeft;
            syncingFloatingScroll = false;
        };

        const updateFloatingScroll = () => {
            const scroll = getTableScroll();
            const table = scroll?.querySelector('.vinetas-table');
            floatingScroll = document.getElementById('costosEmpaqueFloatingScroll');

            if (!scroll || !table || !floatingScroll) {
                return;
            }

            const rect = scroll.getBoundingClientRect();
            const hasHorizontalOverflow = table.scrollWidth > Math.ceil(rect.width);
            const isTableVisible = rect.top < window.innerHeight - 80 && rect.bottom > getTopbarBottom();

            floatingScroll.classList.toggle('is-visible', hasHorizontalOverflow && isTableVisible);

            if (!hasHorizontalOverflow || !isTableVisible) {
                return;
            }

            const inner = floatingScroll.querySelector('.vinetas-floating-scrollbar-inner');
            if (inner) {
                inner.style.width = `${table.scrollWidth}px`;
            }

            syncFloatingScrollFromTable();
        };

        const initTableFeatures = () => {
            removeStickyHeaderClone();

            const scroll = getTableScroll();
            const table = scroll?.querySelector('.vinetas-table');
            const header = table?.querySelector('thead');
            floatingScroll = document.getElementById('costosEmpaqueFloatingScroll');

            if (!scroll || !table || !header || !floatingScroll) {
                return;
            }

            stickyHeaderClone = document.createElement('div');
            stickyHeaderClone.className = 'vinetas-sticky-header-clone';
            stickyHeaderClone.innerHTML = `
                <div class="vinetas-sticky-header-inner">
                    <table class="w-full text-sm">${header.outerHTML}</table>
                </div>
            `;

            const originalHeaders = [...header.querySelectorAll('th')];
            const cloneHeaders = [...stickyHeaderClone.querySelectorAll('th')];

            cloneHeaders.forEach((th, index) => {
                const width = originalHeaders[index]?.getBoundingClientRect().width;
                if (width) {
                    th.style.width = `${width}px`;
                    th.style.minWidth = `${width}px`;
                    th.style.maxWidth = `${width}px`;
                }
            });

            document.body.appendChild(stickyHeaderClone);

            scroll.addEventListener('scroll', () => {
                syncStickyHeaderClone();
                syncFloatingScrollFromTable();
            }, { passive: true });

            if (boundFloatingScroll !== floatingScroll) {
                floatingScroll.addEventListener('scroll', () => {
                    const currentScroll = getTableScroll();
                    if (!currentScroll || syncingFloatingScroll) {
                        return;
                    }
                    syncingFloatingScroll = true;
                    currentScroll.scrollLeft = floatingScroll.scrollLeft;
                    syncStickyHeaderClone();
                    syncingFloatingScroll = false;
                }, { passive: true });
                boundFloatingScroll = floatingScroll;
            }

            if (!windowEventsBound) {
                window.addEventListener('scroll', () => {
                    syncStickyHeaderClone();
                    updateFloatingScroll();
                }, { passive: true });

                window.addEventListener('resize', () => {
                    syncStickyHeaderClone();
                    updateFloatingScroll();
                });

                let sidebarAnimFrame = null;
                const animateStickyHeader = () => {
                    const startTime = performance.now();
                    const duration = 350;
                    const step = (now) => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                        if (now - startTime < duration) {
                            sidebarAnimFrame = requestAnimationFrame(step);
                        }
                    };
                    if (sidebarAnimFrame) cancelAnimationFrame(sidebarAnimFrame);
                    sidebarAnimFrame = requestAnimationFrame(step);
                };

                window.addEventListener('sidebar-toggled', animateStickyHeader);
                window.addEventListener('open-mobile-sidebar', animateStickyHeader);
                window.addEventListener('close-mobile-sidebar', animateStickyHeader);
                windowEventsBound = true;
            }

            if (window.ResizeObserver) {
                const ro = new ResizeObserver(() => {
                    requestAnimationFrame(() => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                    });
                });
                const containerEl = getTableContainer();
                if (containerEl) ro.observe(containerEl);
                const mainEl = document.querySelector('main');
                if (mainEl) ro.observe(mainEl);
            }

            requestAnimationFrame(() => {
                syncStickyHeaderClone();
                updateFloatingScroll();
            });
        };

        async function loadUrl(url) {
            const container = getTableContainer();
            if (!container) return;

            const currentScrollLeft = getTableScroll()?.scrollLeft || 0;
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });
                if (!response.ok) throw new Error('Error en la carga');
                const html = await response.text();
                container.innerHTML = html;
                window.history.pushState({}, '', url);

                initTableFeatures();

                const newScroll = getTableScroll();
                if (newScroll) {
                    newScroll.scrollLeft = currentScrollLeft;
                }
            } catch (err) {
                window.location.href = url;
            } finally {
                container.style.opacity = '1';
                container.style.pointerEvents = 'auto';
            }
        }

        document.addEventListener('click', (e) => {
            const link = e.target.closest('.costos-ajax-table-link, .costos-ajax-pagination a, .costos-ajax-clear-filters');
            if (link && link.href) {
                e.preventDefault();
                loadUrl(link.href);
            }
        });

        document.addEventListener('submit', (e) => {
            const form = e.target.closest('#costosEmpaqueFilterForm, .costos-ajax-per-page-form');
            if (form) {
                e.preventDefault();
                const formData = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (typeof value === 'string' && value.trim() !== '') {
                        params.append(key, value.trim());
                    }
                }
                loadUrl(`${form.action.split('?')[0]}?${params.toString()}`);
            }
        });

        initTableFeatures();
    });
</script>
</body>
</html>
