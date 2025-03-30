<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Eliminare cont') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Odată ce contul dvs. este eliminat, toate resursele și datele sale vor fi șterse permanent. Înainte de a elimina contul dvs., vă rugăm să descărcați orice date sau informații pe care doriți să le păstrați.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Eliminare cont') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Sigur doriți să eliminați contul dvs.?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Odată ce contul dvs. este eliminat, toate resursele și datele sale vor fi șterse permanent. Vă rugăm să introduceți parola pentru a confirma că doriți să eliminați permanent contul dvs.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Parolă') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Parolă') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Anulare') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Eliminare cont') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
