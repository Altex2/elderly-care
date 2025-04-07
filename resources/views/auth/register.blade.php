<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-primary">
            Creați cont
        </h2>
        <p class="mt-2 text-md text-gray-600">
            Înregistrați-vă ca îngrijitor pentru a începe să gestionați îngrijirea
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="role" value="caregiver">

        <!-- Name -->
        <div>
            <label for="name" class="block text-md font-medium text-gray-700 mb-2">Nume complet</label>
            <input id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name" 
            />
            @error('name')
                <p class="mt-2 text-danger">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <label for="email" class="block text-md font-medium text-gray-700 mb-2">Email</label>
            <input id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
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
                autocomplete="new-password" 
            />
            @error('password')
                <p class="mt-2 text-danger">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <label for="password_confirmation" class="block text-md font-medium text-gray-700 mb-2">Confirmare parolă</label>
            <input id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password" 
            />
            @error('password_confirmation')
                <p class="mt-2 text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-6 text-md text-gray-600">
            Prin înregistrare, veți crea un cont de îngrijitor. Puteți adăuga pacienți din tabloul de bord după autentificare.
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full btn btn-primary">
                Înregistrare
            </button>
        </div>

        <div class="mt-6 text-center">
            <span class="text-md text-gray-600">Aveți deja un cont?</span>
            <a href="{{ route('login') }}" class="ml-1 text-md text-primary hover:underline">
                Conectare
            </a>
        </div>
    </form>
</x-guest-layout>
