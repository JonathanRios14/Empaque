<script>
    document.addEventListener('DOMContentLoaded', () => {
        @if (session('success'))
            mostrarToast('success', @json(session('success')));
        @endif

        @if (session('error'))
            appSwal({
                icon: 'error',
                title: 'Error',
                text: @json(session('error')),
            });
        @endif

        @if (session('warning'))
            appSwal({
                icon: 'warning',
                title: 'Atención',
                text: @json(session('warning')),
            });
        @endif
    });
</script>