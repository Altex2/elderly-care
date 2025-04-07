<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-primary">
            Bine ați venit înapoi
        </h2>
        <p class="mt-2 text-md text-gray-600">
            Conectați-vă la contul dvs.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-md font-medium text-gray-700 mb-2">Email</label>
            <input id="email"
                type="email"
                name="email" 
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username" 
            />
            @error('email')
                <p class="mt-2 text-danger">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="block text-md font-medium text-gray-700 mb-2">Parolă</label>
            <input id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password" 
            />
            @error('password')
                <p class="mt-2 text-danger">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="mt-4  flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-primary focus:ring-primary mr-2 w-10"
                    name="remember">
                <span class="text-md text-gray-600">Ține-mă minte</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-md text-primary hover:underline" href="{{ route('password.request') }}">
                    Ați uitat parola?
                </a>
            @endif
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full btn btn-primary">
                Conectare
            </button>
        </div>

        <div class="mt-6 text-center">
            <span class="text-md text-gray-600">Nu aveți un cont?</span>
            <a href="{{ route('register') }}" class="ml-1 text-md text-primary hover:underline">
                Înregistrați-vă ca îngrijitor
            </a>
        </div>
    </form>
</x-guest-layout>
