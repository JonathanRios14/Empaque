<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema de Empaque</title>
<link rel="stylesheet" href="{{ asset('build/assets/app.css') }}">
    {{-- Cargar tema antes del CSS para evitar parpadeo --}}
    <script>
        (function () {
            const theme = localStorage.getItem('systemTheme');

            if (theme === 'dark-navy') {
                document.documentElement.classList.add('login-dark', 'dark-navy');
            }
        })();
    </script>

    <!-- @vite(['resources/css/app.css', 'resources/js/app.js']) -->

    <style>
        /* ================================
           Login tema claro navy elegante
        ================================ */

        .login-bg {
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.10), transparent 30%),
                radial-gradient(circle at bottom right, rgba(15, 23, 42, 0.24), transparent 36%),
                linear-gradient(135deg, #0b1220 0%, #0f172a 52%, #111c33 100%);
        }

        .login-wrapper {
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(226, 232, 240, 0.85);
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.26);
        }

        .login-form-panel {
            background: rgba(255, 255, 255, 0.98);
        }

        .login-logo-box {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .login-title {
            color: #0b1220;
        }

        .login-label {
            color: #0b1220;
        }

        .login-subtitle,
        .login-footer,
        .login-remember {
            color: #64748b;
        }

        .login-input {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }

        .login-input::placeholder {
            color: #94a3b8;
        }

        .login-input:focus {
            border-color: #0b1220;
            box-shadow: 0 0 0 1px #0b1220;
        }

        .login-icon {
            color: #0b1220;
        }

        .login-eye {
            color: #0b1220;
        }

        .login-eye:hover {
            color: #111c33;
        }

        .login-button {
            background: #0b1220;
        }

        .login-button:hover {
            background: #111c33;
        }

        .login-image-panel {
            background: #0b1220;
        }

        .login-image-bg {
            background: linear-gradient(to bottom right, #0b1220, #0f172a, #111c33);
        }

        .login-logo-light {
            display: block;
        }

        .login-logo-dark {
            display: none;
        }

        /* ================================
           Login tema oscuro azul marino
        ================================ */

        html.login-dark .login-bg {
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.14), transparent 32%),
                radial-gradient(circle at bottom right, rgba(37, 99, 235, 0.22), transparent 35%),
                linear-gradient(135deg, #0b1220 0%, #0f172a 48%, #111827 100%);
        }

        html.login-dark .login-wrapper {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(148, 163, 184, 0.18);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
        }

        html.login-dark .login-form-panel {
            background: #0f172a;
        }

        html.login-dark .login-logo-box {
            background: #111827;
            border-color: rgba(255, 255, 255, 0.10);
        }

        html.login-dark .login-title {
            color: #ffffff;
        }

        html.login-dark .login-label {
            color: #e5e7eb;
        }

        html.login-dark .login-subtitle,
        html.login-dark .login-footer,
        html.login-dark .login-remember {
            color: #94a3b8;
        }

        html.login-dark .login-input {
            background: #0b1220;
            border-color: #334155;
            color: #e5e7eb;
        }

        html.login-dark .login-input::placeholder {
            color: #64748b;
        }

        html.login-dark .login-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 1px #2563eb;
        }

        html.login-dark .login-icon {
            color: #38bdf8;
        }

        html.login-dark .login-eye {
            color: #38bdf8;
        }

        html.login-dark .login-eye:hover {
            color: #7dd3fc;
        }

        html.login-dark .login-button {
            background: #38bdf8;
               color: #0b1220 !important;
        }

        html.login-dark .login-button:hover {
            background: #7dd3fc;
                color: #0b1220 !important;
        }

        html.login-dark .login-image-panel {
            background: #111827;
        }

        html.login-dark .login-image-bg {
            background: linear-gradient(to bottom right, #111827, #0f172a, #020617);
        }

        html.login-dark .login-logo-light {
            display: none !important;
        }

        html.login-dark .login-logo-dark {
            display: block !important;
        }

        /* ================================
           Animación login claro / oscuro
        ================================ */

        .intro-overlay {
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.10), transparent 30%),
                radial-gradient(circle at bottom right, rgba(15, 23, 42, 0.24), transparent 36%),
                linear-gradient(135deg, #0b1220 0%, #0f172a 52%, #111c33 100%);
        }

        .intro-logo-box {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.90);
        }

        .intro-title {
            color: #ffffff;
        }

        .intro-subtitle {
            color: rgba(255, 255, 255, 0.70);
        }

        .intro-logo-light {
            display: block;
        }

        .intro-logo-dark {
            display: none;
        }

        html.login-dark .intro-overlay {
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.14), transparent 32%),
                radial-gradient(circle at bottom right, rgba(37, 99, 235, 0.22), transparent 35%),
                linear-gradient(135deg, #0b1220 0%, #0f172a 48%, #111827 100%);
        }

        html.login-dark .intro-logo-box {
            background: #111827;
            border-color: rgba(255, 255, 255, 0.10);
        }

        html.login-dark .intro-logo-light {
            display: none !important;
        }

        html.login-dark .intro-logo-dark {
            display: block !important;
        }

        html.login-dark .intro-title {
            color: #ffffff;
        }

        html.login-dark .intro-subtitle {
            color: #94a3b8;
        }

        /* Animación de humo */
        .smoke {
            position: absolute;
            bottom: -130px;
            width: 240px;
            height: 240px;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(255,255,255,0.20), rgba(255,255,255,0.05), transparent 70%);
            filter: blur(18px);
            opacity: 0;
            animation: smokeMove 4s ease-in-out infinite;
        }

        .smoke-1 { left: 18%; animation-delay: 0s; }
        .smoke-2 { left: 38%; width: 310px; height: 310px; animation-delay: 0.6s; }
        .smoke-3 { left: 56%; width: 270px; height: 270px; animation-delay: 1.1s; }
        .smoke-4 { left: 70%; width: 230px; height: 230px; animation-delay: 1.7s; }

        @keyframes smokeMove {
            0% {
                opacity: 0;
                transform: translateY(0) scale(0.75);
            }

            25% {
                opacity: 0.45;
            }

            65% {
                opacity: 0.22;
            }

            100% {
                opacity: 0;
                transform: translateY(-520px) scale(1.75);
            }
        }

        .intro-logo {
            animation: introLogo 1.1s ease-out both;
        }

        @keyframes introLogo {
            0% {
                opacity: 0;
                transform: scale(0.86) translateY(10px);
            }

            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</head>

<body class="min-h-screen login-bg flex items-center justify-center px-4 py-3 overflow-x-hidden">

    {{-- Animación al iniciar sesión --}}
    <div id="introSistema"
         class="intro-overlay fixed inset-0 z-[9999] hidden items-center justify-center transition-opacity duration-700 overflow-hidden">

        <div class="smoke smoke-1"></div>
        <div class="smoke smoke-2"></div>
        <div class="smoke smoke-3"></div>
        <div class="smoke smoke-4"></div>

        <div class="relative z-10 text-center">
            <div class="intro-logo-box mx-auto w-32 h-32 rounded-3xl flex items-center justify-center shadow-2xl mb-5 intro-logo">
                <img src="{{ asset('img/plasencia-logocafe.png') }}"
                     alt="Plasencia Logo"
                     class="intro-logo-light h-24 w-auto object-contain">

                <img src="{{ asset('img/plasencia-logo.png') }}"
                     alt="Plasencia Logo"
                     class="intro-logo-dark h-24 w-auto object-contain">
            </div>

            <h1 class="intro-title text-2xl font-bold tracking-wide">
                Sistema de Empaque
            </h1>

            <p class="intro-subtitle text-sm mt-1">
                Verificando acceso...
            </p>
        </div>
    </div>

    {{-- Decoración fondo --}}
    <div class="absolute top-10 left-10 w-40 h-40 rounded-full bg-white/5 blur-2xl"></div>
    <div class="absolute bottom-10 right-10 w-56 h-56 rounded-full bg-sky-400/10 blur-3xl"></div>

    <div class="login-wrapper relative w-full max-w-4xl grid grid-cols-1 lg:grid-cols-2 gap-0 items-stretch rounded-[1.75rem] overflow-hidden">

        {{-- FORMULARIO --}}
        <div class="login-form-panel w-full p-5 lg:p-6 flex flex-col justify-center min-h-[440px]">

            <div class="text-center mb-5">
                <div class="login-logo-box mx-auto mb-4 w-20 h-20 rounded-3xl border flex items-center justify-center shadow-sm">
                    <img src="{{ asset('img/plasencia-logocafe.png') }}"
                         alt="Plasencia Logo"
                         class="login-logo-light h-14 w-auto object-contain">

                    <img src="{{ asset('img/plasencia-logo.png') }}"
                         alt="Plasencia Logo"
                         class="login-logo-dark h-14 w-auto object-contain">
                </div>

                <h1 class="login-title text-2xl font-extrabold">
                    Bienvenido
                </h1>

                <p class="login-subtitle text-sm mt-1">
                    Inicia sesión para continuar
                </p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <div class="mb-4">
                    <label for="email" class="login-label block text-sm font-bold mb-2">
                        Correo electrónico
                    </label>

                    <div class="relative">
                        <span class="login-icon absolute left-4 top-1/2 -translate-y-1/2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16 12H8m8 0a4 4 0 1 1-8 0 4 4 0 0 1 8 0zm4 0a8 8 0 1 1-2.343-5.657" />
                            </svg>
                        </span>

                        <input id="email"
                               type="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               autofocus
                               placeholder="correo@ejemplo.com"
                               class="login-input w-full rounded-2xl pl-12 pr-4 py-2.5 text-sm outline-none transition">
                    </div>
                </div>

                <div class="mb-4" x-data="{ showPassword: false }">
                    <label for="password" class="login-label block text-sm font-bold mb-2">
                        Contraseña
                    </label>

                    <div class="relative">
                        <span class="login-icon absolute left-4 top-1/2 -translate-y-1/2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 11c1.105 0 2-.895 2-2V7a2 2 0 1 0-4 0v2c0 1.105.895 2 2 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M6 11h12v9H6z" />
                            </svg>
                        </span>

                        <input id="password"
                               :type="showPassword ? 'text' : 'password'"
                               name="password"
                               required
                               placeholder="Ingresa tu contraseña"
                               class="login-input w-full rounded-2xl pl-12 pr-12 py-2.5 text-sm outline-none transition">

                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="login-eye absolute right-4 top-1/2 -translate-y-1/2 transition">
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-4.477 0-8.268-2.943-9.542-7a9.977 9.977 0 0 1 2.293-3.95M6.873 6.873A9.956 9.956 0 0 1 12 5c4.477 0 8.268 2.943 9.542 7a9.973 9.973 0 0 1-4.043 5.197M15 12a3 3 0 0 0-3-3m0 0a3 3 0 0 0-3 3m3-3l9 9M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-4">
                  
                </div>

                <button type="submit"
                        class="login-button w-full text-white font-bold py-2.5 rounded-2xl transition shadow-lg shadow-slate-900/20">
                    Iniciar sesión
                </button>
            </form>

            <p class="login-footer text-center text-xs mt-5">
                Plasencia · Área de Empaque
            </p>
        </div>

        {{-- IMAGEN DERECHA --}}
        <div class="login-image-panel hidden lg:flex min-h-[440px] items-center justify-center relative overflow-hidden">
            <div class="login-image-bg absolute inset-0"></div>

            <img src="{{ asset('img/login-tabaco.webp') }}"
                 alt="Imagen de tabaco"
                 class="relative z-10 w-full h-full object-cover object-center opacity-95">

            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-[#0f172a]/5 to-[#0f172a]/30"></div>
        </div>

    </div>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const isDark = localStorage.getItem('systemTheme') === 'dark-navy';

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo iniciar sesión',
                    text: '{{ $errors->first() }}',
                    confirmButtonText: 'Intentar de nuevo',
                    confirmButtonColor: isDark ? '#2563eb' : '#0b1220',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#e5e7eb' : '#0b1220'
                });
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('loginForm');
            const intro = document.getElementById('introSistema');
            const submitButton = form.querySelector('button[type="submit"]');

            let enviando = false;

            form.addEventListener('submit', (e) => {
                if (enviando) {
                    return;
                }

                e.preventDefault();

                intro.classList.remove('hidden');
                intro.classList.add('flex');

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerText = 'Ingresando...';
                }

                setTimeout(() => {
                    enviando = true;
                    form.submit();
                }, 900);
            });
        });
    </script>

</body>
</html>