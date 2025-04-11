<nav class="bg-white shadow" x-data="{ open: false }">
    <div class="container">
        <div class="flex justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center justify-center">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="Reminder Buddy Logo" class="h-8 w-8">
                    <span class="text-xl md:text-2xl font-bold text-primary whitespace-nowrap">Reminder Buddy</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden sm:flex h-full">
                <div class="flex items-center h-full space-x-2 md:space-x-6 lg:space-x-12">
                    <a href="{{ route('dashboard') }}" class="nav-link flex items-center justify-center h-full px-3 relative {{ request()->routeIs('dashboard') || request()->routeIs('caregiver.dashboard') ? 'text-primary font-bold active-nav' : 'text-gray-600 hover:text-gray-900' }}">
                        <div class="flex items-center space-x-1">
                            <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="hidden md:inline lg:inline">Tablou</span>
                            <span class="hidden lg:inline">de bord</span>
                            <span class="inline md:hidden">Tablou</span>
                        </div>
                    </a>

                    @if(auth()->user()->role === 'caregiver')
                        <a href="{{ route('caregiver.reminders') }}" class="nav-link flex items-center justify-center h-full px-3 relative {{ request()->routeIs('caregiver.reminders') ? 'text-primary font-bold active-nav' : 'text-gray-600 hover:text-gray-900' }}">
                            <div class="flex items-center space-x-1">
                                <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="hidden md:inline lg:inline">Memento-uri</span>
                                <span class="inline md:hidden">Memento</span>
                            </div>
                        </a>
                    @else
                        <a href="{{ route('voice.interface') }}" class="nav-link flex items-center justify-center h-full px-3 relative {{ request()->routeIs('voice.interface') ? 'text-primary font-bold active-nav' : 'text-gray-600 hover:text-gray-900' }}">
                            <div class="flex items-center space-x-1">
                                <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                                <span class="hidden md:inline lg:inline">Asistent</span>
                                <span class="inline md:hidden">Asistent</span>
                            </div>
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="nav-link flex items-center justify-center h-full px-3 relative {{ request()->routeIs('profile.edit') ? 'text-primary font-bold active-nav' : 'text-gray-600 hover:text-gray-900' }}">
                        <div class="flex items-center space-x-1">
                            <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="hidden md:inline">Profil</span>
                            <span class="inline md:hidden">Profil</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center sm:hidden">
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('caregiver.dashboard')" :active="request()->routeIs('caregiver.dashboard')">
                {{ __('Panou de control') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('caregiver.reminders')" :active="request()->routeIs('caregiver.reminders')">
                {{ __('Memento-uri') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Deconectare') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<style>
    .active-nav::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 3px;
        background-color: var(--color-primary, #0056b3);
    }
    
    .nav-link:hover::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #6b7280;
    }
    
    .active-nav:hover::after {
        background-color: var(--color-primary, #0056b3);
        height: 3px;
    }
</style>
