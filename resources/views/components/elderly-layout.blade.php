<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('dashboard') }}">
                                <img src="{{ asset('images/logo.png') }}" alt="Reminder Buddy Logo" class="h-8 w-8">
                            </a>
                        </div>
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-blue-600">
                                {{ config('app.name', 'Laravel') }}
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('dashboard') }}" 
                               class="inline-flex items-center px-4 py-2 text-lg font-medium text-gray-900 hover:text-blue-600">
                                Dashboard
                            </a>
                            <a href="{{ route('voice.interface') }}" 
                               class="inline-flex items-center px-4 py-2 text-lg font-medium text-gray-900 hover:text-blue-600">
                                Comenzi Vocale
                            </a>
                            <a href="{{ route('reminders.index') }}" 
                               class="inline-flex items-center px-4 py-2 text-lg font-medium text-gray-900 hover:text-blue-600">
                                Memento-uri
                            </a>
                            <a href="{{ route('emergency.contacts') }}" 
                               class="inline-flex items-center px-4 py-2 text-lg font-medium text-gray-900 hover:text-blue-600">
                                Contacte Urgență
                            </a>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center">
                        <div class="ml-3 relative">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-4 py-2 text-lg font-medium text-gray-900 hover:text-blue-600">
                                        {{ Auth::user()->name }}
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')" class="text-lg">
                                        {{ __('Profil') }}
                                    </x-dropdown-link>

                                    <!-- Authentication -->
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault();
                                                            this.closest('form').submit();"
                                                class="text-lg">
                                            {{ __('Deconectare') }}
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        <!-- Emergency Button -->
        <div class="fixed bottom-8 right-8">
            <a href="{{ route('emergency.call') }}" 
               class="flex items-center justify-center w-20 h-20 bg-red-600 rounded-full shadow-lg hover:bg-red-700 transition-colors duration-200">
                <span class="text-white text-2xl font-bold">SOS</span>
            </a>
        </div>
    </div>
</body>
</html> 