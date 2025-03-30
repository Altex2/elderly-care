<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-blue-600 to-indigo-900">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Creați cont
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Înregistrați-vă ca îngrijitor pentru a începe să gestionați îngrijirea
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <input type="hidden" name="role" value="caregiver">

                <!-- Name -->
                <div>
                    <x-input-label for="name" :value="__('Nume complet')" class="text-gray-700 dark:text-gray-300" />
                    <x-text-input id="name"
                                 class="block mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                 type="text"
                                 name="name"
                                 :value="old('name')"
                                 required
                                 autofocus
                                 autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Email Address -->
                <div class="mt-4">
                    <x-input-label for="email" :value="__('Email')" class="text-gray-700 dark:text-gray-300" />
                    <x-text-input id="email"
                                 class="block mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                 type="email"
                                 name="email"
                                 :value="old('email')"
                                 required
                                 autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label for="password" :value="__('Parolă')" class="text-gray-700 dark:text-gray-300" />
                    <x-text-input id="password"
                                 class="block mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                 type="password"
                                 name="password"
                                 required
                                 autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div class="mt-4">
                    <x-input-label for="password_confirmation" :value="__('Confirmare parolă')" class="text-gray-700 dark:text-gray-300" />
                    <x-text-input id="password_confirmation"
                                 class="block mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                 type="password"
                                 name="password_confirmation"
                                 required
                                 autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                    Prin înregistrare, veți crea un cont de îngrijitor. Puteți adăuga pacienți din tabloul de bord după autentificare.
                </div>

                <div class="mt-6">
                    <button type="submit"
                            class="w-full py-3 px-4 border border-transparent rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm font-medium transition-colors duration-200">
                        {{ __('Înregistrare') }}
                    </button>
                </div>

                <div class="mt-6 text-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Aveți deja un cont?</span>
                    <a href="{{ route('login') }}"
                       class="ml-1 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        Conectare
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
