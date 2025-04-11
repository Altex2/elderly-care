<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Reminder Buddy') }}</title>
    <link rel="preload" href="{{ asset('images/logo.png') }}" as="image">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex flex-row justify-center items-center">
                    <a href="{{ url('/') }}" class="flex items-center space-x-2">
                        <img src="{{ asset('images/logo.png') }}" alt="Reminder Buddy Logo" class="h-8 w-8">
                        <span class="text-2xl font-bold text-primary">Reminder Buddy</span>
                    </a>
                </div>
                
                <!-- Desktop navigation links -->
                <div class="hidden md:flex items-center">
                    @if (Route::has('login'))
                        <div class="space-x-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-gray-600 hover:text-primary">Tablou de bord</a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-600 hover:text-primary">Autentificare</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="ml-4 btn btn-primary">Înregistrare</a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
                
                <!-- Mobile menu button -->
                <div class="flex items-center md:hidden">
                    <button @click="open = !open" class="p-2 rounded-md text-gray-600 hover:text-gray-900 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': !open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !open, 'inline-flex': open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div class="md:hidden" x-show="open" x-cloak @click.away="open = false">
            <div class="pt-2 pb-3 px-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="block py-3 px-4 text-lg text-center rounded-md mb-2 bg-gray-100 text-gray-800">Tablou de bord</a>
                    @else
                        <a href="{{ route('login') }}" class="block py-3 px-4 text-lg text-center rounded-md mb-2 bg-gray-100 text-gray-800">Autentificare</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="block py-3 px-4 text-lg text-center rounded-md mb-2 bg-gray-100 text-gray-800">Înregistrare</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-600 to-indigo-900 pt-32 pb-20 text-white">
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
                        <a href="{{ route('register') }}" class="btn btn-light btn-lg">
                            Începe acum
                        </a>
                    </div>
                </div>
                <div class="mt-12 lg:mt-0 lg:col-span-6">
                    <div class="relative">
                        <div class="aspect-w-5 aspect-h-3 rounded-lg shadow-xl overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=60"
                                 alt="Ilustrație Reminder Buddy"
                                 class="object-cover"
                                 loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Funcții care fac îngrijirea mai ușoară
                </h2>
                <p class="mt-4 text-xl text-gray-600">
                    Tot ce aveți nevoie pentru a oferi cea mai bună îngrijire pentru cei dragi
                </p>
            </div>

            <div class="mt-20">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Voice Commands -->
                    <div class="bg-white shadow-lg rounded-lg px-6 py-8">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            <h3 class="mt-6 text-lg font-medium text-gray-900">Comenzi vocale</h3>
                            <p class="mt-2 text-base text-gray-600">
                                Interfață vocală naturală pentru interacțiune ușoară cu memento-uri și setări
                            </p>
                        </div>
                    </div>

                    <!-- Smart Reminders -->
                    <div class="bg-white shadow-lg rounded-lg px-6 py-8">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-6 text-lg font-medium text-gray-900">Memento-uri inteligente</h3>
                            <p class="mt-2 text-base text-gray-600">
                                Memento-uri bazate pe inteligență artificială care se adaptează la programele și preferințele individuale
                            </p>
                        </div>
                    </div>

                    <!-- Caregiver Dashboard -->
                    <div class="bg-white shadow-lg rounded-lg px-6 py-8">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="mt-6 text-lg font-medium text-gray-900">Tablou de bord pentru îngrijitori</h3>
                            <p class="mt-2 text-base text-gray-600">
                                Tablou de bord complet pentru gestionarea mai multor pacienți și programele lor de îngrijire
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-12 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block text-black">Sunteți gata să începeți?</span>
                <span class="block text-blue-500">Alăturați-vă nouă astăzi și îmbunătățiți Reminder Buddy.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="{{ route('register') }}" class="btn btn-light btn-lg">
                        Începe acum
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-100 text-gray-700">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p>&copy; {{ date('Y') }} Reminder Buddy. Toate drepturile rezervate.</p>
            </div>
        </div>
    </footer>
</body>
</html>
