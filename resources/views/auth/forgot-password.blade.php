<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Ați uitat parola? Nu este nicio problemă. Doar spuneți-ne adresa dvs. de email și vă vom trimite un link de resetare a parolei care vă va permite să alegeți una nouă.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Trimite link de resetare parolă') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
