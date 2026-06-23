<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>404 | Página no encontrada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f8fafc] text-gray-800 transition-colors duration-300">

@if (($forceLayout ?? false) && auth()->check())
    <div x-data="{
        sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
        catalogos: false,
        seguridad: false,
        produccion: false
    }" class="flex min-h-screen">

        @include('layouts.sidebar')

        <main class="flex-1 min-w-0">
            @include('layouts.topbar', [
                'title' => 'Página no encontrada',
                'description' => 'La ruta solicitada no existe o fue movida.'
            ])

            @include('errors.partials.space-error', [
                'code' => '404',
                'title' => 'Página no encontrada',
                'description' => 'La página que intentas abrir no existe, fue movida o la dirección escrita no es correcta.',
                'withLayout' => true
            ])
        </main>
    </div>
@else
    @include('errors.partials.space-error', [
        'code' => '404',
        'title' => 'Página no encontrada',
        'description' => 'La página que intentas abrir no existe, fue movida o la dirección escrita no es correcta.',
        'withLayout' => false
    ])
@endif

</body>
</html>