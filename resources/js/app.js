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

document.addEventListener('pointermove', (event) => {
    const target = event.target.closest('.gooey-action');

    if (! target) {
        return;
    }

    const rect = target.getBoundingClientRect();

    target.style.setProperty('--x', ((event.clientX - rect.left) / rect.width) * 100);
    target.style.setProperty('--y', ((event.clientY - rect.top) / rect.height) * 100);
});
