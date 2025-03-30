<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-blue-600 to-indigo-900">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Bine ați venit înapoi
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Conectați-vă la contul dvs.
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('Email')" class="text-gray-700 dark:text-gray-300" />
                    <x-text-input id="email"
                                 class="block mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                 type="email"
                                 name="email"
                                 :value="old('email')"
                                 required
                                 autofocus
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
                                 autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="mt-4 flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me"
                               type="checkbox"
                               class="rounded dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500"
                               name="remember">
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Ține-mă minte') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm text-blue-600 dark:text-blue-400 hover:underline" href="{{ route('password.request') }}">
                            {{ __('Ați uitat parola?') }}
                        </a>
                    @endif
                </div>

                <div class="mt-6">
                    <button type="submit"
                            class="w-full py-3 px-4 border border-transparent rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm font-medium transition-colors duration-200">
                        {{ __('Conectare') }}
                    </button>
                </div>

                <div class="mt-6 text-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Nu aveți un cont?</span>
                    <a href="{{ route('register') }}"
                       class="ml-1 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        Înregistrați-vă ca îngrijitor
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
