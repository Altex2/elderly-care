<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Mulțumim pentru înregistrare! Înainte de a începe, vă rugăm să vă verificați adresa de email făcând clic pe link-ul pe care vi l-am trimis prin email? Dacă nu ați primit email-ul, vă vom trimite cu plăcere altul.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('Un nou link de verificare a fost trimis la adresa de email pe care ați furnizat-o în timpul înregistrării.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Retrimite email de verificare') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Deconectare') }}
            </button>
        </form>
    </div>
</x-guest-layout>
