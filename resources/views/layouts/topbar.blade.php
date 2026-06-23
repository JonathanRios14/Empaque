<header class="app-topbar h-20 bg-[#3b2818] border-b border-[#2b1b12] flex items-center justify-between px-6 shadow-sm transition-colors duration-300">
    <div>
        <h1 class="app-topbar-title text-2xl font-bold text-white">
            {{ $title ?? 'Dashboard' }}
        </h1>

        <p class="app-topbar-description text-sm text-white/60">
            {{ $description ?? 'Sistema de empaque' }}
        </p>
    </div>

    <div class="flex items-center gap-3">
        <button type="button"
                id="themeToggle"
                class="app-theme-button w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 hover:bg-white/20 text-white transition"
                title="Cambiar tema">

            <svg id="themeIconMoon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
            </svg>

            <svg id="themeIconSun" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 3v1m0 16v1m8.66-12.66-.7.7M4.04 19.96l-.7.7M21 12h-1M4 12H3m16.96 7.96-.7-.7M4.04 4.04l-.7-.7M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" />
            </svg>
        </button>

        <div x-data="{ profileOpen: false }" class="relative">

            <button @click="profileOpen = !profileOpen"
                    class="flex items-center gap-3 rounded-2xl hover:bg-white/10 transition px-3 py-2">

                <div class="hidden lg:block text-right">
                    <p class="text-sm font-semibold text-white">
                        {{ Auth::user()->name }}
                    </p>

                    <p class="text-xs text-white/60">
                        {{ Auth::user()->getRoleNames()->first() ?? 'Sin rol' }}
                    </p>
                </div>

                @if (Auth::user()->photo)
                    <img src="{{ asset('storage/' . Auth::user()->photo) }}"
                         class="w-12 h-12 rounded-2xl object-cover"
                         alt="Usuario">
                @else
                    <div class="w-11 h-11 rounded-2xl bg-white text-[#5b3a1e] flex items-center justify-center font-bold border-2 border-white/30">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif

                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-4 h-4 text-white/70"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="profileOpen"
                 x-transition
                 @click.outside="profileOpen = false"
                 class="app-profile-dropdown absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border border-[#e5d8c7] overflow-hidden z-50"
                 style="display: none;">

                <div class="app-profile-header p-5 text-center bg-[#fbf8f3]">
                    @if (Auth::user()->photo)
                        <img src="{{ asset('storage/' . Auth::user()->photo) }}"
                             class="w-16 h-16 rounded-2xl object-cover mx-auto border-2 border-[#d8c6a3]"
                             alt="Usuario">
                    @else
                        <div class="w-16 h-16 rounded-2xl bg-[#5b3a1e] text-white flex items-center justify-center font-bold text-xl mx-auto border-2 border-[#d8c6a3]">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif

                    <h3 class="app-profile-name mt-3 font-bold text-[#3b2818]">
                        {{ Auth::user()->name }}
                    </h3>

                    <p class="app-profile-email text-sm text-gray-500">
                        {{ Auth::user()->email }}
                    </p>
                </div>

                <a href="{{ route('profile.edit') }}"
                   class="app-profile-link flex items-center gap-2 px-4 py-3 border-t border-gray-100 hover:bg-[#f3efe7] text-[#3b2818] text-sm font-semibold">
                    Mi perfil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                            class="app-profile-link w-full flex items-center gap-2 px-4 py-3 border-t border-gray-100 hover:bg-[#f3efe7] text-[#3b2818] text-sm font-semibold">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>