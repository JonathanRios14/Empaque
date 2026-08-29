<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas por orden | Sistema de Empaque</title>

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

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="section-title-icon vinetas-header-icon w-9 h-9 rounded-xl flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Viñetas por orden
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Gestión de viñetas asignadas por orden y códigos QR únicos.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Registros: <strong class="theme-title">{{ $vinetasPorOrden->total() }}</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <form method="GET"
                                  action="{{ route('vinetas-por-orden.index') }}"
                                  class="vinetas-ajax-filter-form">
                                <div class="vinetas-filter-row grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-2 items-end">
                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Código QR / ID</label>
                                        <input type="text"
                                               name="buscar"
                                               value="{{ request('buscar') }}"
                                               placeholder="QR o ID..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Marca</label>
                                        <input type="text"
                                               name="marca"
                                               value="{{ request('marca') }}"
                                               placeholder="Marca..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Nombre</label>
                                        <input type="text"
                                               name="nombre"
                                               value="{{ request('nombre') }}"
                                               placeholder="Nombre..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Código producto</label>
                                        <input type="text"
                                               name="codigo_producto"
                                               value="{{ request('codigo_producto') }}"
                                               placeholder="Código..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Item</label>
                                        <input type="text"
                                               name="item"
                                               value="{{ request('item') }}"
                                               placeholder="Item..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Orden sistema</label>
                                        <input type="text"
                                               name="orden_del_sistema"
                                               value="{{ request('orden_del_sistema') }}"
                                               placeholder="Orden sistema..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>

                                    <div>
                                        <label class="theme-text block text-xs font-semibold mb-1 whitespace-nowrap">Orden</label>
                                        <input type="text"
                                               name="orden_cliente"
                                               value="{{ request('orden_cliente') }}"
                                               placeholder="Orden..."
                                               class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                    </div>
                                </div>

                                <div class="vinetas-filter-actions mt-3"
                                     style="display: flex !important; grid-column: auto !important; justify-content: flex-end; gap: 0.5rem; padding-top: 0 !important;">
                                    <a href="{{ route('vinetas-por-orden.index') }}"
                                       class="vinetas-ajax-clear-filters gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-2 rounded-xl bg-white text-[#0b1220] text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition"
                                       style="width: auto !important; white-space: nowrap;">
                                        Limpiar
                                    </a>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-3 py-2 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition"
                                            style="width: auto !important; white-space: nowrap;">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible">
                            <div id="vinetasPorOrdenTableContainer">
                                @include('vinetas-por-orden.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="vinetasPorOrdenTableLoader"
         class="productos-table-loader hidden"
         role="status"
         aria-live="polite">

        <div class="productos-table-loader-card theme-card theme-shadow">
            <div class="productos-table-loader-icon">
                <span></span>
            </div>

            <div class="text-left">
                <p class="theme-title text-sm font-bold leading-tight">
                    Actualizando tabla
                </p>

                <p class="theme-text text-xs leading-tight mt-0.5">
                    Cargando viñetas por orden...
                </p>
            </div>
        </div>
    </div>

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getTableContainer = () => document.getElementById('vinetasPorOrdenTableContainer');
            const getFilterForm = () => document.querySelector('.vinetas-ajax-filter-form');
            const getTopbarBottom = () => document.querySelector('.app-topbar')?.getBoundingClientRect().bottom || 0;

            let stickyHeaderClone = null;
            let stickyHeaderEventsBound = false;
            let floatingScroll = document.getElementById('vinetasPorOrdenFloatingScroll');
            let floatingScrollEventsBound = false;
            let boundFloatingScroll = null;
            let syncingFloatingScroll = false;

            const getTableScroll = () => document.querySelector('#vinetasPorOrdenTableContainer .vinetas-table-scroll');

            const restoreTableScroll = (scrollLeft) => {
                const scroll = getTableScroll();

                if (!scroll) {
                    return;
                }

                const maxScrollLeft = Math.max(scroll.scrollWidth - scroll.clientWidth, 0);

                scroll.scrollLeft = Math.min(scrollLeft, maxScrollLeft);

                floatingScroll = document.getElementById('vinetasPorOrdenFloatingScroll');

                if (floatingScroll) {
                    floatingScroll.scrollLeft = scroll.scrollLeft;
                }

                syncStickyHeaderClone();
                updateFloatingScroll();
            };

            const removeStickyHeaderClone = () => {
                stickyHeaderClone?.remove();
                stickyHeaderClone = null;
            };

            const syncStickyHeaderClone = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');

                if (!scroll || !table || !header || !stickyHeaderClone) {
                    return;
                }

                const scrollRect = scroll.getBoundingClientRect();
                const tableRect = table.getBoundingClientRect();
                const headerRect = header.getBoundingClientRect();
                const headerHeight = headerRect.height;
                const topbarBottom = getTopbarBottom();
                const shouldShow = headerRect.top < topbarBottom
                    && tableRect.bottom > topbarBottom + headerHeight;

                stickyHeaderClone.classList.toggle('is-visible', shouldShow);

                if (!shouldShow) {
                    return;
                }

                stickyHeaderClone.style.left = `${scrollRect.left}px`;
                stickyHeaderClone.style.width = `${scrollRect.width}px`;
                stickyHeaderClone.style.top = `${topbarBottom}px`;

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

                floatingScroll = document.getElementById('vinetasPorOrdenFloatingScroll');

                if (!scroll || !table || !floatingScroll) {
                    return;
                }

                const rect = scroll.getBoundingClientRect();
                const hasHorizontalOverflow = table.scrollWidth > Math.ceil(rect.width);
                const isTableVisible = rect.top < window.innerHeight - 80 && rect.bottom > 80;

                floatingScroll.classList.toggle('is-visible', hasHorizontalOverflow && isTableVisible);

                if (!hasHorizontalOverflow || !isTableVisible) {
                    return;
                }

                floatingScroll.style.width = '100%';

                const inner = floatingScroll.querySelector('.vinetas-floating-scrollbar-inner');

                if (inner) {
                    inner.style.width = `${table.scrollWidth}px`;
                }

                syncFloatingScrollFromTable();
            };

            const initFloatingScroll = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                floatingScroll = document.getElementById('vinetasPorOrdenFloatingScroll');

                if (!scroll || !table || !floatingScroll) {
                    return;
                }

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

                if (!floatingScrollEventsBound) {
                    window.addEventListener('scroll', updateFloatingScroll, { passive: true });
                    window.addEventListener('resize', () => requestAnimationFrame(updateFloatingScroll));
                    floatingScrollEventsBound = true;
                }

                requestAnimationFrame(updateFloatingScroll);
            };

            const initStickyHeaderClone = () => {
                removeStickyHeaderClone();

                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');

                if (!scroll || !table || !header) {
                    return;
                }

                stickyHeaderClone = document.createElement('div');
                stickyHeaderClone.className = 'vinetas-sticky-header-clone';

                const cloneTable = document.createElement('table');
                cloneTable.className = table.className;
                cloneTable.appendChild(header.cloneNode(true));
                stickyHeaderClone.appendChild(cloneTable);

                document.body.appendChild(stickyHeaderClone);

                if (!stickyHeaderEventsBound) {
                    window.addEventListener('scroll', syncStickyHeaderClone, { passive: true });
                    window.addEventListener('resize', syncStickyHeaderClone);

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
                    stickyHeaderEventsBound = true;
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

                syncStickyHeaderClone();
            };

            const initTableFeatures = () => {
                initFloatingScroll();
                initStickyHeaderClone();
            };

            const showTableLoader = () => {
                document.getElementById('vinetasPorOrdenTableLoader')?.classList.remove('hidden');
            };

            const hideTableLoader = () => {
                document.getElementById('vinetasPorOrdenTableLoader')?.classList.add('hidden');
            };

            const loadTableHtml = async (targetUrl, { pushState = true, previousScrollLeft = null } = {}) => {
                const container = getTableContainer();

                if (!container) {
                    return;
                }

                const currentScrollLeft = previousScrollLeft ?? (getTableScroll()?.scrollLeft || 0);

                showTableLoader();

                try {
                    const response = await fetch(targetUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Error al cargar la tabla de viñetas por orden.');
                    }

                    const html = await response.text();
                    container.innerHTML = html;

                    if (pushState) {
                        window.history.pushState({}, '', targetUrl);
                    }

                    initTableFeatures();
                    restoreTableScroll(currentScrollLeft);
                } catch (error) {
                    window.location.href = targetUrl;
                } finally {
                    hideTableLoader();
                }
            };

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('.vinetas-ajax-filter-form, .vinetas-ajax-per-page-form');

                if (!form) {
                    return;
                }

                event.preventDefault();

                const formData = new FormData(form);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (typeof value === 'string' && value.trim() !== '') {
                        params.append(key, value.trim());
                    }
                }

                const targetUrl = `${form.action.split('?')[0]}?${params.toString()}`;
                loadTableHtml(targetUrl, { previousScrollLeft: getTableScroll()?.scrollLeft || 0 });
            });

            document.addEventListener('click', (event) => {
                const link = event.target.closest('.vinetas-ajax-pagination a, .vinetas-ajax-table-link, .vinetas-ajax-clear-filters');

                if (!link || !link.href) {
                    return;
                }

                event.preventDefault();

                if (link.classList.contains('vinetas-ajax-clear-filters')) {
                    const form = getFilterForm();
                    if (form) {
                        form.querySelectorAll('input[type="text"]').forEach((input) => {
                            input.value = '';
                        });
                    }
                }

                loadTableHtml(link.href, { previousScrollLeft: getTableScroll()?.scrollLeft || 0 });
            });

            window.addEventListener('popstate', () => {
                loadTableHtml(window.location.href, { pushState: false });
            });

            initTableFeatures();
        });
    </script>
</body>
</html>
