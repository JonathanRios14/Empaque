<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas | Sistema de Empaque</title>

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
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h2m4 0h-2m-4 4h6" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Viñetas
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Información de QR sincronizada desde la API de empaque.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Registros: <strong class="theme-title">{{ $vinetas->total() }}</strong>
                                        </span>

                                        <span class="theme-badge inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                                            Estados: <strong class="theme-title">{{ $estados->count() }}</strong>
                                        </span>
                                    </div>
                                </div>

                                <form method="POST"
                                      action="{{ route('vinetas.sincronizar') }}"
                                      class="form-sincronizar-vinetas w-full sm:w-auto">
                                    @csrf

                                    <button type="submit"
                                            class="gooey-action btn-sincronizar-vinetas inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition disabled:opacity-70 disabled:cursor-not-allowed">
                                        <div class="loader-sincronizar hidden"></div>

                                        <span class="texto-sincronizar-vinetas">
                                            Sincronizar viñetas
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3">
                            <form method="GET"
                                  action="{{ route('vinetas.index') }}"
                                  class="vinetas-ajax-filter-form grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-2 items-end">

                                <div class="xl:col-span-3">
                                    <label class="theme-text block text-xs font-semibold mb-1">
                                        Buscar
                                    </label>

                                    <input type="text"
                                           name="buscar"
                                           value="{{ request('buscar') }}"
                                           placeholder="ID, item, orden, marca, producto o código..."
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">
                                        Estado
                                    </label>

                                    <select name="estado"
                                            class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                        <option value="">Todos</option>
                                        @foreach ($estados as $estado)
                                            <option value="{{ $estado }}" @selected(request('estado') === $estado)>
                                                {{ $estado }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">
                                        Impreso
                                    </label>

                                    <select name="impreso"
                                            class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                        <option value="">Todos</option>
                                        <option value="1" @selected(request('impreso') === '1')>Sí</option>
                                        <option value="0" @selected(request('impreso') === '0')>No</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-2 xl:col-span-1 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 pt-1">
                                    <a href="{{ route('vinetas.index') }}"
                                       class="vinetas-ajax-clear-filters gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-2 rounded-xl bg-white text-[#0b1220] text-sm font-semibold border theme-border hover:bg-[#f1f5f9] transition">
                                        Limpiar
                                    </a>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-3 py-2 rounded-xl bg-[#0f172a] text-white text-sm font-semibold hover:bg-[#1e293b] transition">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible">
                            <div id="vinetasTableContainer">
                                @include('vinetas.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="vinetasSyncOverlay"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/45 backdrop-blur-sm px-4">
        <div class="theme-card w-full max-w-md rounded-3xl bg-white border theme-border p-6 shadow-2xl text-center">
            <div class="mx-auto mb-5 flex justify-center">
                <div class="sync-loader"></div>
            </div>

            <h3 class="theme-title text-lg font-bold">
                Sincronizando viñetas
            </h3>

            <p class="theme-text text-sm mt-2">
                Actualizando información de QR desde la API de empaque.
            </p>

            <p class="theme-text text-xs mt-4">
                No cierres esta ventana mientras termina la sincronización.
            </p>
        </div>
    </div>

    <div id="vinetasTableLoader"
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
                    Cargando viñetas...
                </p>
            </div>
        </div>
    </div>

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getTableContainer = () => document.getElementById('vinetasTableContainer');
            const getFilterForm = () => document.querySelector('.vinetas-ajax-filter-form');

            let stickyHeaderClone = null;
            let stickyHeaderEventsBound = false;
            let floatingScroll = document.getElementById('vinetasFloatingScroll');
            let floatingScrollEventsBound = false;
            let boundFloatingScroll = null;
            let syncingFloatingScroll = false;

            const getTableScroll = () => document.querySelector('#vinetasTableContainer .vinetas-table-scroll');

            const restoreTableScroll = (scrollLeft) => {
                const scroll = getTableScroll();

                if (!scroll) {
                    return;
                }

                const maxScrollLeft = Math.max(scroll.scrollWidth - scroll.clientWidth, 0);

                scroll.scrollLeft = Math.min(scrollLeft, maxScrollLeft);

                floatingScroll = document.getElementById('vinetasFloatingScroll');

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
                const footer = document.querySelector('#vinetasTableContainer .vinetas-table-footer');
                const header = table?.querySelector('thead');

                if (!scroll || !table || !header || !stickyHeaderClone) {
                    return;
                }

                const rect = scroll.getBoundingClientRect();
                const headerHeight = header.getBoundingClientRect().height;
                const shouldShow = rect.top <= 0 && rect.bottom > headerHeight;

                stickyHeaderClone.classList.toggle('is-visible', shouldShow);

                if (!shouldShow) {
                    return;
                }

                stickyHeaderClone.style.left = `${rect.left}px`;
                stickyHeaderClone.style.width = `${rect.width}px`;

                const cloneTable = stickyHeaderClone.querySelector('table');

                if (cloneTable) {
                    cloneTable.style.width = `${table.scrollWidth}px`;
                    cloneTable.style.transform = `translateX(${-scroll.scrollLeft}px)`;
                }
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

                floatingScroll = document.getElementById('vinetasFloatingScroll');

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
                floatingScroll = document.getElementById('vinetasFloatingScroll');

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
                stickyHeaderClone.innerHTML = `
                    <div class="vinetas-sticky-header-inner">
                        <table class="w-full text-sm">
                            ${header.outerHTML}
                        </table>
                    </div>
                `;

                const originalHeaders = [...header.querySelectorAll('th')];
                const cloneHeaders = [...stickyHeaderClone.querySelectorAll('th')];

                cloneHeaders.forEach((th, index) => {
                    const width = originalHeaders[index]?.getBoundingClientRect().width;

                    if (width) {
                        th.style.width = `${width}px`;
                        th.style.minWidth = `${width}px`;
                    }
                });

                document.body.appendChild(stickyHeaderClone);

                if (!stickyHeaderEventsBound) {
                    document.addEventListener('scroll', syncStickyHeaderClone, { passive: true, capture: true });
                    window.addEventListener('resize', () => requestAnimationFrame(initStickyHeaderClone));
                    stickyHeaderEventsBound = true;
                }

                syncStickyHeaderClone();
            };

            const showTableLoader = () => {
                const container = getTableContainer();
                const loader = document.getElementById('vinetasTableLoader');

                if (loader) {
                    const header = document.querySelector('#vinetasTableContainer .productos-sticky-head');

                    if (header) {
                        const rect = header.getBoundingClientRect();
                        const top = Math.max(rect.bottom + 12, 78);

                        loader.style.top = `${top}px`;
                    }

                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }

                const inner = container?.querySelector('#vinetasTableInner');

                if (inner) {
                    inner.classList.add('productos-table-loading');
                }
            };

            const hideTableLoader = () => {
                const loader = document.getElementById('vinetasTableLoader');
                const inner = document.getElementById('vinetasTableInner');

                if (inner) {
                    inner.classList.remove('productos-table-loading');
                }

                if (loader) {
                    setTimeout(() => {
                        loader.classList.add('hidden');
                        loader.classList.remove('flex');
                    }, 160);
                }
            };

            const loadVinetasTable = async (url, options = {}) => {
                const container = getTableContainer();

                if (!container) {
                    return;
                }

                const scrollLeft = options.preserveHorizontalScroll
                    ? (getTableScroll()?.scrollLeft || floatingScroll?.scrollLeft || 0)
                    : 0;

                showTableLoader();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo actualizar la tabla de viñetas');
                    }

                    container.innerHTML = await response.text();
                    initStickyHeaderClone();
                    initFloatingScroll();

                    if (options.preserveHorizontalScroll) {
                        requestAnimationFrame(() => {
                            restoreTableScroll(scrollLeft);
                            requestAnimationFrame(() => restoreTableScroll(scrollLeft));
                        });
                    }

                    window.history.pushState({}, '', url);
                } catch (error) {
                    console.error(error);
                    window.location.href = url;
                } finally {
                    hideTableLoader();
                }
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a');

                if (!link) {
                    return;
                }

                const isSortLink = link.classList.contains('vinetas-ajax-table-link');
                const isPaginationLink = link.closest('#vinetasTableContainer .vinetas-ajax-pagination');
                const isClearLink = link.classList.contains('vinetas-ajax-clear-filters');

                if (!isSortLink && !isPaginationLink && !isClearLink) {
                    return;
                }

                event.preventDefault();

                if (isClearLink) {
                    const form = getFilterForm();

                    form?.querySelectorAll('input, select').forEach((field) => {
                        field.value = '';
                    });
                }

                loadVinetasTable(link.href, {
                    preserveHorizontalScroll: isSortLink,
                });
            });

            document.addEventListener('submit', (event) => {
                const filterForm = event.target.closest('.vinetas-ajax-filter-form');
                const perPageForm = event.target.closest('.vinetas-ajax-per-page-form');

                if (!filterForm && !perPageForm) {
                    return;
                }

                event.preventDefault();

                const form = filterForm || perPageForm;
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                params.delete('page');

                loadVinetasTable(`${form.action}?${params.toString()}`);
            });

            window.addEventListener('popstate', () => {
                loadVinetasTable(window.location.href);
            });

            initStickyHeaderClone();
            initFloatingScroll();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.form-sincronizar-vinetas').forEach((form) => {
                form.addEventListener('submit', function () {
                    const button = form.querySelector('.btn-sincronizar-vinetas');
                    const text = form.querySelector('.texto-sincronizar-vinetas');
                    const loader = form.querySelector('.loader-sincronizar');
                    const overlay = document.getElementById('vinetasSyncOverlay');

                    if (button) {
                        button.disabled = true;
                        button.classList.add('pointer-events-none', 'opacity-80');
                    }

                    if (text) {
                        text.textContent = 'Sincronizando...';
                    }

                    if (loader) {
                        loader.classList.remove('hidden');
                    }

                    if (overlay) {
                        overlay.classList.remove('hidden');
                        overlay.classList.add('flex');
                    }
                });
            });
        });
    </script>
</body>
</html>
