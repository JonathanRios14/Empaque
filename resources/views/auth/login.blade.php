<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema de Empaque</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .login-bg {
            background:
                radial-gradient(circle at top left, rgba(201, 166, 107, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(91, 58, 30, 0.28), transparent 35%),
                linear-gradient(135deg, #24160d 0%, #3b2818 48%, #5b3a1e 100%);
        }

        .glass-card {
            backdrop-filter: blur(18px);
            background: rgba(255, 255, 255, 0.97);
        }

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
         class="fixed inset-0 z-[9999] bg-[#3b2818] hidden items-center justify-center transition-opacity duration-700 overflow-hidden">

        <div class="smoke smoke-1"></div>
        <div class="smoke smoke-2"></div>
        <div class="smoke smoke-3"></div>
        <div class="smoke smoke-4"></div>

        <div class="relative z-10 text-center">
            <div class="mx-auto w-32 h-32 rounded-3xl bg-white flex items-center justify-center shadow-2xl mb-5 intro-logo">
                <img src="{{ asset('img/plasencia-logocafe.png') }}"
                     alt="Plasencia Logo"
                     class="h-24 w-auto object-contain">
            </div>

            <h1 class="text-white text-2xl font-bold tracking-wide">
                Sistema de Empaque
            </h1>

            <p class="text-white/60 text-sm mt-1">
                Verificando acceso...
            </p>
        </div>
    </div>

    {{-- Decoración fondo --}}
    <div class="absolute top-10 left-10 w-40 h-40 rounded-full bg-white/5 blur-2xl"></div>
    <div class="absolute bottom-10 right-10 w-56 h-56 rounded-full bg-[#c9a66b]/20 blur-3xl"></div>

<div class="relative w-full max-w-4xl grid grid-cols-1 lg:grid-cols-2 gap-0 items-stretch rounded-[1.75rem] overflow-hidden shadow-2xl border border-white/20">
        {{-- FORMULARIO --}}
     <div class="w-full glass-card p-5 lg:p-6 flex flex-col justify-center min-h-[440px]">
            <div class="text-center mb-5">
                <div class="mx-auto mb-4 w-20 h-20 rounded-3xl bg-[#f3efe7] border border-[#e5d8c7] flex items-center justify-center shadow-sm">
                    <img src="{{ asset('img/plasencia-logocafe.png') }}"
                         alt="Plasencia Logo"
                         class="h-14 w-auto object-contain">
                </div>

                <h1 class="text-2xl font-extrabold text-[#24160d]">
                    Bienvenido
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Inicia sesión para continuar
                </p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-bold text-[#24160d] mb-2">
                        Correo electrónico
                    </label>

                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#8a5a2b]">
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
                               class="w-full rounded-2xl border-[#d8c6a3] pl-12 pr-4 py-2.5 text-sm focus:border-[#5b3a1e] focus:ring-[#5b3a1e] bg-white">
                    </div>
                </div>

                <div class="mb-4" x-data="{ showPassword: false }">
                    <label for="password" class="block text-sm font-bold text-[#24160d] mb-2">
                        Contraseña
                    </label>

                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#8a5a2b]">
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
                               class="w-full rounded-2xl border-[#d8c6a3] pl-12 pr-12 py-2.5 text-sm focus:border-[#5b3a1e] focus:ring-[#5b3a1e] bg-white">

                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-[#8a5a2b] hover:text-[#3b2818] transition">
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
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="checkbox"
                               name="remember"
                               class="rounded border-gray-300 text-[#5b3a1e] focus:ring-[#5b3a1e]">
                        
                    </label>

                
                </div>

                <button type="submit"
                        class="w-full bg-[#24160d] hover:bg-[#3b2818] text-white font-bold py-2.5 rounded-2xl transition shadow-sm">
                    Iniciar sesión
                </button>
            </form>

            <p class="text-center text-xs text-gray-400 mt-5">
                Plasencia · Área de Empaque
            </p>
        </div>

        {{-- IMAGEN DERECHA --}}
     <div class="hidden lg:flex bg-[#f7f4ef] min-h-[440px] items-center justify-center relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white via-[#f7f4ef] to-[#eadfd5]"></div>

            <img src="{{ asset('img/login-tabaco.webp') }}"
                 alt="Imagen de tabaco"
              class="relative z-10 w-full h-full object-cover object-center">

            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-white/10"></div>
        </div>
 
    </div>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo iniciar sesión',
                    text: '{{ $errors->first() }}',
                    confirmButtonText: 'Intentar de nuevo',
                    confirmButtonColor: '#5b3a1e',
                    background: '#ffffff',
                    color: '#3b2818'
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