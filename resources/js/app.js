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
    const iconMoon = document.getElementById('themeIconMoon');
    const iconSun = document.getElementById('themeIconSun');

    const applyTheme = (theme) => {
        if (theme === 'dark-navy') {
            document.documentElement.classList.add('dark-navy');
            iconMoon?.classList.add('hidden');
            iconSun?.classList.remove('hidden');
        } else {
            document.documentElement.classList.remove('dark-navy');
            iconMoon?.classList.remove('hidden');
            iconSun?.classList.add('hidden');
        }
    };

    const savedTheme = localStorage.getItem('systemTheme') || 'light';

    applyTheme(savedTheme);

    themeToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.contains('dark-navy');
        const newTheme = isDark ? 'light' : 'dark-navy';

        localStorage.setItem('systemTheme', newTheme);
        applyTheme(newTheme);
    });
});