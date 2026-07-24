<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800 transition-colors duration-300">

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: false,
    seguridad: false,
    produccion: false
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Mi perfil',
            'description' => 'Actualiza tu información personal, foto de perfil y contraseña.'
        ])

       <section class="app-content-compact">
            <div class="profile-compact-page w-full max-w-[1600px] mx-auto">
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        {{-- TARJETA PRINCIPAL DEL PERFIL --}}
                        <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] theme-border shadow-sm overflow-hidden">

                    <div class="p-4">
                        <div class="flex items-center gap-4">

                            {{-- Preview de imagen --}}
                            <div class="flex justify-start">
                                <div class="relative">
                                    @if (Auth::user()->photo)
                                        <img id="photoPreview"
                                             src="{{ asset('storage/' . Auth::user()->photo) }}"
                                             class="w-20 h-20 rounded-2xl object-cover border-4 border-[#e5d8c7] theme-border shadow-sm"
                                             alt="Foto de perfil">

                                        <div id="initialPreview"
                                             class="hidden w-20 h-20 rounded-2xl bg-[#5b3a1e] text-white items-center justify-center font-bold text-2xl border-4 border-[#e5d8c7] theme-border shadow-sm">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @else
                                        <img id="photoPreview"
                                             src=""
                                             class="hidden w-20 h-20 rounded-2xl object-cover border-4 border-[#e5d8c7] theme-border shadow-sm"
                                             alt="Foto de perfil">

                                        <div id="initialPreview"
                                             class="w-20 h-20 rounded-2xl bg-[#5b3a1e] text-white flex items-center justify-center font-bold text-2xl border-4 border-[#e5d8c7] theme-border shadow-sm">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="absolute -bottom-1 -right-1 bg-[#5b3a1e] text-white w-7 h-7 rounded-lg flex items-center justify-center shadow">
                                        <span class="text-xs">📷</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Datos del usuario --}}
                            <div class="min-w-0 flex-1 text-left">
                                <h3 class="theme-title text-xl font-bold text-[#3b2818] truncate">
                                    {{ Auth::user()->name }}
                                </h3>

                                <p class="theme-text text-sm text-gray-500 mt-1 truncate">
                                    {{ Auth::user()->email }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                        {{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}
                                    </span>

                                    <span class="estado-badge is-active px-3 py-1 rounded-full text-xs font-semibold border">
                                        Activo
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                        {{-- INFORMACIÓN DEL PERFIL --}}
                        <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] theme-border shadow-sm p-4">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>

                    {{-- ACTUALIZAR CONTRASEÑA --}}
                    <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] theme-border shadow-sm p-4">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputPhoto = document.getElementById('photo');
        const photoPreview = document.getElementById('photoPreview');
        const initialPreview = document.getElementById('initialPreview');

        if (inputPhoto) {
            inputPhoto.addEventListener('change', function (event) {
                const file = event.target.files[0];

                if (file) {
                    const fileNameText = document.getElementById('photoFileName');

                    if (fileNameText) {
                        fileNameText.textContent = file.name;
                    }

                    const reader = new FileReader();

                    reader.onload = function (e) {
                        photoPreview.src = e.target.result;
                        photoPreview.classList.remove('hidden');

                        if (initialPreview) {
                            initialPreview.classList.add('hidden');
                            initialPreview.classList.remove('flex');
                        }
                    };

                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>

</body>
</html>
