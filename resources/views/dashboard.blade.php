<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-primary">
            {{ __('Bun venit, :name!', ['name' => auth()->user()->name]) }}
        </h1>
    </x-slot>

    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="card">
            <h2 class="text-xl font-semibold text-primary mb-4">{{ __('Acțiuni Rapide') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @if(auth()->user()->role === 'user')
                    <a href="{{ route('voice.interface') }}" class="btn btn-primary flex items-center justify-center space-x-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                        <span>{{ __('Asistent Vocal') }}</span>
                    </a>
                @endif

                <a href="{{ route('reminders.create') }}" class="btn btn-primary flex items-center justify-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>{{ __('Adaugă Memento') }}</span>
                </a>

                <a href="{{ route('emergency.create') }}" class="btn btn-primary flex items-center justify-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ __('Solicită Ajutor') }}</span>
                </a>
            </div>
        </div>

        <!-- Upcoming Reminders -->
        <div class="card">
            <h2 class="text-xl font-semibold text-primary mb-4">{{ __('Memento-uri Viitoare') }}</h2>
            @if($upcomingReminders->isEmpty())
                <p class="text-gray-600">{{ __('Nu ai memento-uri programate pentru astăzi.') }}</p>
            @else
                <div class="space-y-4">
                    @foreach($upcomingReminders as $reminder)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ $reminder->title }}</h3>
                                    <p class="text-gray-600">{{ $reminder->scheduled_at->format('H:i') }}</p>
                                </div>
                            </div>
                            <form action="{{ route('reminders.complete', $reminder) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Completează') }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Completed Reminders -->
        <div class="card">
            <h2 class="text-xl font-semibold text-primary mb-4">{{ __('Memento-uri Completate') }}</h2>
            @if($completedReminders->isEmpty())
                <p class="text-gray-600">{{ __('Nu ai completat niciun memento astăzi.') }}</p>
            @else
                <div class="space-y-4">
                    @foreach($completedReminders as $reminder)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ $reminder->title }}</h3>
                                    <p class="text-gray-600">{{ $reminder->completed_at->format('H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout> 