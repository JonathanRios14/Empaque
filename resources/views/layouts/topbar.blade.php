<header class="h-20 bg-[#3b2818] border-b border-[#2b1b12] flex items-center justify-between px-6 shadow-sm">
    <div>
        <h1 class="text-2xl font-bold text-white">
            {{ $title ?? 'Dashboard' }}
        </h1>

        <p class="text-sm text-white/60">
            {{ $description ?? 'Sistema de empaque' }}
        </p>
    </div>

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
                     class="w-12 h-12 rounded-2xl object-cover "
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
             class="absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border border-[#e5d8c7] overflow-hidden z-50"
             style="display: none;">

            <div class="p-5 text-center bg-[#fbf8f3]">
                @if (Auth::user()->photo)
                    <img src="{{ asset('storage/' . Auth::user()->photo) }}"
                         class="w-16 h-16 rounded-2xl object-cover mx-auto border-2 border-[#d8c6a3]"
                         alt="Usuario">
                @else
                    <div class="w-16 h-16 rounded-2xl bg-[#5b3a1e] text-white flex items-center justify-center font-bold text-xl mx-auto border-2 border-[#d8c6a3]">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif

                <h3 class="mt-3 font-bold text-[#3b2818]">
                    {{ Auth::user()->name }}
                </h3>

                <p class="text-sm text-gray-500">
                    {{ Auth::user()->email }}
                </p>
            </div>

            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-2 px-4 py-3 border-t border-gray-100 hover:bg-[#f3efe7] text-[#3b2818] text-sm font-semibold">
                Mi perfil
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit"
                        class="w-full flex items-center gap-2 px-4 py-3 border-t border-gray-100 hover:bg-[#f3efe7] text-[#3b2818] text-sm font-semibold">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</header>