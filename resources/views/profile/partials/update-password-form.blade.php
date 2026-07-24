<section>
    <header>
        <h2 class="theme-title text-lg font-bold text-[#3b2818]">
            Actualizar contraseña
        </h2>

        <p class="theme-text mt-1 text-xs text-gray-600">
            Usa una contraseña segura para proteger tu cuenta.
        </p>
    </header>

    <form method="post"
          action="{{ route('password.update') }}"
          class="mt-4 space-y-4"
          x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
        @csrf
        @method('put')

        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-1 2xl:grid-cols-3 gap-4">
            <div>
                <label for="current_password" class="theme-title block text-sm font-semibold text-[#3b2818] mb-1.5">
                    Contraseña actual
                </label>

                <div class="relative">
                    <input id="current_password"
                           name="current_password"
                           :type="showCurrent ? 'text' : 'password'"
                           autocomplete="current-password"
                           class="theme-input w-full rounded-xl border-gray-300 text-sm pr-20 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                    <button type="button"
                            @click="showCurrent = !showCurrent"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#5b3a1e]">
                        <span x-text="showCurrent ? 'Ocultar' : 'Ver'"></span>
                    </button>
                </div>

                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="theme-title block text-sm font-semibold text-[#3b2818] mb-1.5">
                    Nueva contraseña
                </label>

                <div class="relative">
                    <input id="password"
                           name="password"
                           :type="showNew ? 'text' : 'password'"
                           autocomplete="new-password"
                           class="theme-input w-full rounded-xl border-gray-300 text-sm pr-20 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                    <button type="button"
                            @click="showNew = !showNew"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#5b3a1e]">
                        <span x-text="showNew ? 'Ocultar' : 'Ver'"></span>
                    </button>
                </div>

                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="theme-title block text-sm font-semibold text-[#3b2818] mb-1.5">
                    Confirmar contraseña
                </label>

                <div class="relative">
                    <input id="password_confirmation"
                           name="password_confirmation"
                           :type="showConfirm ? 'text' : 'password'"
                           autocomplete="new-password"
                           class="theme-input w-full rounded-xl border-gray-300 text-sm pr-20 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                    <button type="button"
                            @click="showConfirm = !showConfirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#5b3a1e]">
                        <span x-text="showConfirm ? 'Ocultar' : 'Ver'"></span>
                    </button>
                </div>

                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-4 pt-3 border-t border-[#eee3d5] theme-border">
            <button type="submit"
                    class="gooey-action px-5 py-2.5 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                Guardar contraseña
            </button>

         @if (session('status') === 'password-updated')
<script>
document.addEventListener('DOMContentLoaded', () => {
    mostrarToast('success', 'Contraseña actualizada correctamente.');
});
</script>
@endif
        </div>
    </form>
</section>
