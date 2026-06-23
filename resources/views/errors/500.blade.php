<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>500 | Error del sistema</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f8fafc] text-gray-800 transition-colors duration-300">

@if (auth()->check())
    <div x-data="{
        sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
        catalogos: false,
        seguridad: false,
        produccion: false
    }" class="flex min-h-screen">

        @include('layouts.sidebar')

        <main class="flex-1 min-w-0">
            @include('layouts.topbar', [
                'title' => 'Error del sistema',
                'description' => 'Ocurrió un problema interno al procesar la solicitud.'
            ])

            @include('errors.partials.space-error', [
                'code' => '500',
                'title' => 'Error del sistema',
                'description' => 'Ocurrió un problema interno al procesar la solicitud. Intenta nuevamente o comunícate con soporte si el problema continúa.',
                'withLayout' => true
            ])
        </main>
    </div>
@else
    @include('errors.partials.space-error', [
        'code' => '500',
        'title' => 'Error del sistema',
        'description' => 'Ocurrió un problema interno al procesar la solicitud. Intenta nuevamente o vuelve al inicio de sesión.',
        'withLayout' => false
    ])
@endif

</body>
</html>