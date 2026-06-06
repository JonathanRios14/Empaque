import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

window.mostrarToast = function (tipo, mensaje) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo,
        title: mensaje,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
    });
};

document.addEventListener('DOMContentLoaded', () => {
    if (window.flashMessage) {
        window.mostrarToast(window.flashMessage.type, window.flashMessage.message);
    }

    // Confirmación para activar/desactivar usuario
    document.querySelectorAll('.form-cambiar-estado-usuario').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: '¿Cambiar estado del usuario?',
                text: 'El usuario será activado o desactivado según su estado actual.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#5b3a1e',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // Confirmación para eliminar rol
    document.querySelectorAll('.form-eliminar-rol').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: '¿Eliminar rol?',
                text: 'Solo se eliminará si no tiene usuarios asignados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#5b3a1e',
                cancelButtonColor: '#6b7280',
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