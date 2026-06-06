@if (session('success'))
    <script>
        window.flashMessage = {
            type: 'success',
            message: @json(session('success'))
        };
    </script>
@endif

@if (session('error'))
    <script>
        window.flashMessage = {
            type: 'error',
            message: @json(session('error'))
        };
    </script>
@endif

@if ($errors->any())
    <script>
        window.flashMessage = {
            type: 'error',
            message: 'Revisa los campos del formulario.'
        };
    </script>
@endif