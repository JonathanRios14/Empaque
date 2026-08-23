<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>403 | Sin permiso</title>
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
                'title' => 'Sin permiso',
                'description' => 'No tienes autorización para acceder a esta sección.'
            ])

            @include('errors.partials.space-error', [
                'code' => '403',
                'title' => 'Sin permiso para acceder',
                'description' => 'No tienes autorización para entrar a esta sección del sistema. Si necesitas acceso, comunícate con un administrador.',
                'withLayout' => true
            ])
        </main>
    </div>
@else
    @include('errors.partials.space-error', [
        'code' => '403',
        'title' => 'Sin permiso para acceder',
        'description' => 'No tienes autorización para entrar a esta sección del sistema. Inicia sesión con una cuenta autorizada para continuar.',
        'withLayout' => false
    ])
@endif

</body>
</html>
