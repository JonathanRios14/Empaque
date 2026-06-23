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

        <section class="p-4 lg:p-6">
            <div class="w-full max-w-[1600px] mx-auto space-y-6">

                {{-- TARJETA PRINCIPAL DEL PERFIL --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">

                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-center gap-6">

                            {{-- Preview de imagen --}}
                            <div class="flex justify-center md:justify-start">
                                <div class="relative">
                                    @if (Auth::user()->photo)
                                        <img id="photoPreview"
                                             src="{{ asset('storage/' . Auth::user()->photo) }}"
                                             class="w-28 h-28 rounded-2xl object-cover border-4 border-[#e5d8c7] theme-border shadow-sm"
                                             alt="Foto de perfil">

                                        <div id="initialPreview"
                                             class="hidden w-28 h-28 rounded-2xl bg-[#5b3a1e] text-white items-center justify-center font-bold text-3xl border-4 border-[#e5d8c7] theme-border shadow-sm">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @else
                                        <img id="photoPreview"
                                             src=""
                                             class="hidden w-28 h-28 rounded-2xl object-cover border-4 border-[#e5d8c7] theme-border shadow-sm"
                                             alt="Foto de perfil">

                                        <div id="initialPreview"
                                             class="w-28 h-28 rounded-2xl bg-[#5b3a1e] text-white flex items-center justify-center font-bold text-3xl border-4 border-[#e5d8c7] theme-border shadow-sm">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="absolute -bottom-2 -right-2 bg-[#5b3a1e] text-white w-9 h-9 rounded-xl flex items-center justify-center shadow">
                                        <span class="text-sm">📷</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Datos del usuario --}}
                            <div class="flex-1 text-center md:text-left">
                                <h3 class="theme-title text-2xl font-bold text-[#3b2818]">
                                    {{ Auth::user()->name }}
                                </h3>

                                <p class="theme-text text-sm text-gray-500 mt-1">
                                    {{ Auth::user()->email }}
                                </p>

                                <div class="mt-4 flex flex-wrap justify-center md:justify-start gap-2">
                                    <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                        {{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}
                                    </span>

                                    <span class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold border border-green-200">
                                        Activo
                                    </span>
                                </div>

                                <p class="theme-text text-sm text-gray-500 mt-4 max-w-xl">
                                    Puedes cambiar tu nombre, correo electrónico y foto de perfil.
                                </p>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- INFORMACIÓN DEL PERFIL --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] theme-border shadow-sm p-6">
                    @include('profile.partials.update-profile-information-form')
                </div>

                {{-- ACTUALIZAR CONTRASEÑA --}}
                <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] theme-border shadow-sm p-6">
                    @include('profile.partials.update-password-form')
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