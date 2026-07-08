import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

window.appSwalTheme = function () {
    const isDark = document.documentElement.classList.contains('dark-navy');

    return {
        background: isDark ? '#0f172a' : '#ffffff',
        color: isDark ? '#e5e7eb' : '#0b1220',
        confirmButtonColor: isDark ? '#38bdf8' : '#0b1220',
        cancelButtonColor: isDark ? '#334155' : '#64748b',
    };
};

window.appSwal = function (options = {}) {
    return Swal.fire({
        ...window.appSwalTheme(),
        confirmButtonText: 'Aceptar',
        customClass: {
            popup: 'app-swal-popup',
            title: 'app-swal-title',
            htmlContainer: 'app-swal-text',
            confirmButton: 'app-swal-confirm',
            cancelButton: 'app-swal-cancel',
        },
        ...options,
    });
};

window.mostrarToast = function (tipo, mensaje) {
    const isDark = document.documentElement.classList.contains('dark-navy');

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo,
        title: mensaje,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        background: isDark ? '#0f172a' : '#ffffff',
        color: isDark ? '#e5e7eb' : '#0b1220',
        customClass: {
            popup: 'app-swal-popup',
            title: 'app-swal-title',
        },
    });
};

document.addEventListener('DOMContentLoaded', () => {
    if (window.flashMessage) {
        window.mostrarToast(window.flashMessage.type, window.flashMessage.message);
    }

    document.querySelectorAll('.form-cambiar-estado-usuario').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            appSwal({
                title: '¿Cambiar estado del usuario?',
                text: 'El usuario será activado o desactivado según su estado actual.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('.form-eliminar-rol').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            appSwal({
                title: '¿Eliminar rol?',
                text: 'Solo se eliminará si no tiene usuarios asignados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('themeToggle');
    let themeTransitionTimer;

    const refreshAutofillFields = () => {
        const refresh = () => {
            document.querySelectorAll('input, textarea').forEach((field) => {
                if (! field.value) {
                    return;
                }

                field.classList.add('autofill-repaint');

                requestAnimationFrame(() => {
                    field.classList.remove('autofill-repaint');
                });
            });
        };

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(refresh, { timeout: 300 });
            return;
        }

        setTimeout(refresh, 80);
    };

    const startThemeTransition = () => {
        document.documentElement.classList.add('theme-transitioning');
        window.clearTimeout(themeTransitionTimer);

        themeTransitionTimer = window.setTimeout(() => {
            document.documentElement.classList.remove('theme-transitioning');
        }, 320);
    };

    const applyTheme = (theme, animate = false) => {
        if (animate) {
            startThemeTransition();
        }

        if (theme === 'dark-navy') {
            document.documentElement.classList.add('dark-navy');
        } else {
            document.documentElement.classList.remove('dark-navy');
        }

        if (animate) {
            refreshAutofillFields();
        }

        if (themeToggle) {
            themeToggle.checked = theme === 'dark-navy';
        }
    };

    const savedTheme = localStorage.getItem('systemTheme') || 'light';

    applyTheme(savedTheme);

    themeToggle?.addEventListener('change', () => {
        const newTheme = themeToggle.checked ? 'dark-navy' : 'light';

        localStorage.setItem('systemTheme', newTheme);
        applyTheme(newTheme, true);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const getContainer = () => document.getElementById('catalogoTableContainer');
    const getFilterForm = () => document.querySelector('.catalogo-ajax-filter-form');

    if (! getContainer() || ! getFilterForm()) {
        return;
    }

    const getTableLoader = () => {
        let loader = document.getElementById('catalogoTableLoader');

        if (loader) {
            return loader;
        }

        loader = document.createElement('div');
        loader.id = 'catalogoTableLoader';
        loader.className = 'productos-table-loader hidden';
        loader.setAttribute('role', 'status');
        loader.setAttribute('aria-live', 'polite');
        loader.innerHTML = `
            <div class="productos-table-loader-card theme-card theme-shadow">
                <div class="productos-table-loader-icon">
                    <span></span>
                </div>

                <div class="text-left">
                    <p class="theme-title text-sm font-bold leading-tight">
                        Actualizando tabla
                    </p>

                    <p class="theme-text text-xs leading-tight mt-0.5">
                        Cargando catálogo...
                    </p>
                </div>
            </div>
        `;

        document.body.appendChild(loader);

        return loader;
    };

    const showTableLoader = () => {
        const loader = getTableLoader();
        const header = document.querySelector('#catalogoTableContainer .productos-sticky-head');

        if (header) {
            const rect = header.getBoundingClientRect();
            const top = Math.max(rect.bottom + 12, 78);

            loader.style.top = `${top}px`;
        }

        loader.classList.remove('hidden');
        loader.classList.add('flex');
    };

    const hideTableLoader = () => {
        const loader = document.getElementById('catalogoTableLoader');

        if (! loader) {
            return;
        }

        setTimeout(() => {
            loader.classList.add('hidden');
            loader.classList.remove('flex');
        }, 160);
    };

    const loadCatalogoTable = async (url) => {
        const container = getContainer();

        if (! container) {
            return;
        }

        showTableLoader();
        container.classList.add('productos-table-loading');

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (! response.ok) {
                throw new Error('No se pudo actualizar el catálogo');
            }

            const html = await response.text();
            const documentFragment = new DOMParser().parseFromString(html, 'text/html');
            const nextContainer = documentFragment.getElementById('catalogoTableContainer');

            if (! nextContainer) {
                throw new Error('No se encontró la tabla del catálogo');
            }

            container.innerHTML = nextContainer.innerHTML;
            window.history.pushState({}, '', url);
        } catch (error) {
            console.error(error);
            window.location.href = url;
        } finally {
            getContainer()?.classList.remove('productos-table-loading');
            hideTableLoader();
        }
    };

    const toggleClearLink = () => {
        const clearLink = document.querySelector('.catalogo-ajax-clear');
        const buscar = getFilterForm()?.querySelector('[name="buscar"]');

        clearLink?.classList.toggle('hidden', ! buscar?.value);
    };

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('.catalogo-ajax-filter-form');

        if (! form) {
            return;
        }

        event.preventDefault();

        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        params.delete('page');
        toggleClearLink();

        loadCatalogoTable(`${form.action}?${params.toString()}`);
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');

        if (! link) {
            return;
        }

        const clearLink = link.classList.contains('catalogo-ajax-clear');
        const tableLink = link.closest('#catalogoTableContainer');

        if (! clearLink && ! tableLink) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) {
            return;
        }

        if (! clearLink && ! (url.searchParams.has('page') || url.searchParams.has('orden') || url.searchParams.has('direccion'))) {
            return;
        }

        event.preventDefault();

        if (clearLink) {
            getFilterForm()?.querySelectorAll('input, select').forEach((field) => {
                field.value = '';
            });

            toggleClearLink();
        }

        loadCatalogoTable(url.toString());
    });

    window.addEventListener('popstate', () => {
        loadCatalogoTable(window.location.href);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.app-sidebar');

    if (! sidebar) {
        return;
    }

    const tooltip = document.createElement('div');
    tooltip.className = 'sidebar-floating-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    document.body.appendChild(tooltip);

    let activeTarget = null;

    const isCollapsed = () => sidebar.classList.contains('sidebar-collapsed');

    const hideTooltip = () => {
        activeTarget = null;
        tooltip.classList.remove('is-visible');
    };

    const showTooltip = (target) => {
        if (! isCollapsed()) {
            hideTooltip();
            return;
        }

        const text = target.getAttribute('data-sidebar-tooltip');

        if (! text) {
            return;
        }

        activeTarget = target;

        const rect = target.getBoundingClientRect();
        const top = Math.min(Math.max(rect.top + rect.height / 2, 24), window.innerHeight - 24);

        tooltip.textContent = text;
        tooltip.style.left = `${rect.right + 14}px`;
        tooltip.style.top = `${top}px`;
        tooltip.classList.add('is-visible');
    };

    document.addEventListener('mouseover', (event) => {
        const target = event.target.closest('[data-sidebar-tooltip]');

        if (! target || ! sidebar.contains(target) || target === activeTarget) {
            return;
        }

        showTooltip(target);
    });

    document.addEventListener('mouseout', (event) => {
        const target = event.target.closest('[data-sidebar-tooltip]');

        if (! target || ! sidebar.contains(target)) {
            return;
        }

        if (event.relatedTarget && target.contains(event.relatedTarget)) {
            return;
        }

        hideTooltip();
    });

    document.addEventListener('focusin', (event) => {
        const target = event.target.closest('[data-sidebar-tooltip]');

        if (target && sidebar.contains(target)) {
            showTooltip(target);
        }
    });

    document.addEventListener('focusout', hideTooltip);
    window.addEventListener('resize', hideTooltip);
    window.addEventListener('scroll', hideTooltip, true);

    new MutationObserver(() => {
        if (! isCollapsed()) {
            hideTooltip();
        }
    }).observe(sidebar, { attributes: true, attributeFilter: ['class'] });
});

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.app-sidebar');
    const nav = sidebar?.querySelector('nav');
    const highlight = sidebar?.querySelector('.sidebar-hover-highlight');

    if (! sidebar || ! nav || ! highlight) {
        return;
    }

    const getTarget = (event) => event.target.closest('a.sidebar-link, button.sidebar-link, a.sidebar-active, button.sidebar-active');

    let highlightedTarget = null;

    const hideHighlight = () => {
        highlightedTarget?.classList.remove('sidebar-hover-target');
        highlightedTarget = null;
        highlight.classList.remove('is-visible');
    };

    const showHighlight = (target) => {
        if (! target || target.classList.contains('sidebar-active')) {
            hideHighlight();
            return;
        }

        if (highlightedTarget !== target) {
            highlightedTarget?.classList.remove('sidebar-hover-target');
            highlightedTarget = target;
            highlightedTarget.classList.add('sidebar-hover-target');
        }

        highlight.style.setProperty('--sidebar-hover-top', `${target.offsetTop}px`);
        highlight.style.setProperty('--sidebar-hover-height', `${target.offsetHeight}px`);
        highlight.classList.add('is-visible');
    };

    nav.addEventListener('mouseover', (event) => {
        const target = getTarget(event);

        if (! target || ! nav.contains(target)) {
            return;
        }

        showHighlight(target);
    });

    nav.addEventListener('mouseout', (event) => {
        const target = getTarget(event);

        if (! target || target !== highlightedTarget) {
            return;
        }

        if (event.relatedTarget && target.contains(event.relatedTarget)) {
            return;
        }

        const nextTarget = event.relatedTarget?.closest?.('a.sidebar-link, button.sidebar-link, a.sidebar-active, button.sidebar-active');

        if (nextTarget && nav.contains(nextTarget)) {
            return;
        }

        hideHighlight();
    });

    nav.addEventListener('mouseleave', hideHighlight);

    nav.addEventListener('focusin', (event) => {
        const target = getTarget(event);

        if (target && nav.contains(target)) {
            showHighlight(target);
        }
    });

    nav.addEventListener('focusout', (event) => {
        if (event.relatedTarget && nav.contains(event.relatedTarget)) {
            return;
        }

        hideHighlight();
    });
    nav.addEventListener('scroll', hideHighlight);
});

document.addEventListener('pointermove', (event) => {
    const target = event.target.closest('.gooey-action');

    if (! target) {
        return;
    }

    const rect = target.getBoundingClientRect();

    target.style.setProperty('--x', ((event.clientX - rect.left) / rect.width) * 100);
    target.style.setProperty('--y', ((event.clientY - rect.top) / rect.height) * 100);
});
