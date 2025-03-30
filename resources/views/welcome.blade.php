<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Helper Buddy') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Helper Buddy</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    @if (Route::has('login'))
                        <div class="space-x-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Tablou de bord</a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Autentificare</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Înregistrare</a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-600 to-indigo-900 pt-32 pb-40 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                <div class="sm:text-center md:max-w-2xl md:mx-auto lg:col-span-6 lg:text-left">
                    <h1 class="text-4xl tracking-tight font-extrabold sm:text-5xl xl:text-6xl">
                        Îngrijire inteligentă pentru cei dragi
                    </h1>
                    <p class="mt-3 text-lg sm:mt-5 sm:text-xl lg:text-lg xl:text-xl">
                        Un sistem complet de gestionare a îngrijirii care ajută îngrijitorii și vârstnicii să rămână conectați prin memento-uri inteligente și asistență vocală.
                    </p>
                    <div class="mt-8 sm:mt-12">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10">
                            Începe acum
                        </a>
                    </div>
                </div>
                <div class="mt-12 lg:mt-0 lg:col-span-6">
                    <div class="relative">
                        <div class="aspect-w-5 aspect-h-3 rounded-lg shadow-xl overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?ixlib=rb-4.0.3"
                                 alt="Ilustrație Helper Buddy"
                                 class="object-cover">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white sm:text-4xl">
                    Funcții care fac îngrijirea mai ușoară
                </h2>
                <p class="mt-4 text-xl text-gray-600 dark:text-gray-300">
                    Tot ce aveți nevoie pentru a oferi cea mai bună îngrijire pentru cei dragi
                </p>
            </div>

            <div class="mt-20">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Voice Commands -->
                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg px-6 py-8">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            <h3 class="mt-6 text-lg font-medium text-gray-900 dark:text-white">Comenzi vocale</h3>
                            <p class="mt-2 text-base text-gray-600 dark:text-gray-300">
                                Interfață vocală naturală pentru interacțiune ușoară cu memento-uri și setări
                            </p>
                        </div>
                    </div>

                    <!-- Smart Reminders -->
                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg px-6 py-8">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-6 text-lg font-medium text-gray-900 dark:text-white">Memento-uri inteligente</h3>
                            <p class="mt-2 text-base text-gray-600 dark:text-gray-300">
                                Memento-uri bazate pe inteligență artificială care se adaptează la programele și preferințele individuale
                            </p>
                        </div>
                    </div>

                    <!-- Caregiver Dashboard -->
                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg px-6 py-8">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="mt-6 text-lg font-medium text-gray-900 dark:text-white">Tablou de bord pentru îngrijitori</h3>
                            <p class="mt-2 text-base text-gray-600 dark:text-gray-300">
                                Tablou de bord complet pentru gestionarea mai multor pacienți și programele lor de îngrijire
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-blue-700">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Sunteți gata să începeți?</span>
                <span class="block text-blue-200">Alăturați-vă nouă astăzi și îmbunătățiți Helper Buddy.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50">
                        Începe acum
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-gray-400">
                <p>&copy; {{ date('Y') }} Helper Buddy. Toate drepturile rezervate.</p>
            </div>
        </div>
    </footer>
</body>
</html>
