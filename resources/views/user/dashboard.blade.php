<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tablou de bord') }}
        </h2>
    </x-slot>

    @push('scripts')
    <script>
        // Set timezone offset in cookie when page loads
        document.cookie = `timezone_offset=${new Date().getTimezoneOffset() * -1};path=/`;

        // Function to format times in user's timezone
        function formatTimeInUserTimezone(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        // Function to calculate time difference
        function getTimeDifference(dateString) {
            const now = new Date();
            const date = new Date(dateString);
            const diffMs = Math.abs(now - date);
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            const remainingMins = diffMins % 60;

            if (diffHours === 0) {
                return `${diffMins} minute`;
            } else {
                return `${diffHours} ore și ${remainingMins} minute`;
            }
        }

        // Update all time displays on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-timestamp]').forEach(element => {
                const timestamp = element.dataset.timestamp;
                if (element.dataset.type === 'time') {
                    element.textContent = formatTimeInUserTimezone(timestamp);
                } else if (element.dataset.type === 'diff') {
                    element.textContent = getTimeDifference(timestamp);
                }
            });
        });
    </script>
    @endpush

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <!-- Voice Command Component -->
            <div id="app">
                <voice-command></voice-command>
            </div>

            <!-- Voice Assistant Card -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 overflow-hidden shadow-xl rounded-lg">
                <a href="{{ route('voice.interface') }}"
                   class="block p-6 hover:from-blue-600 hover:to-blue-700 transition-all duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-white/10 rounded-full">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-white">Asistent Vocal</h3>
                                <p class="text-blue-100">Controlați memento-urile dvs. cu comenzi vocale</p>
                            </div>
                        </div>
                        <div class="text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Overdue Reminders -->
            @if($overdueReminders->count() > 0)
                <div class="mb-8">
                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">Restante</h4>
                    <div class="space-y-4">
                        @foreach($overdueReminders as $reminder)
                            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-red-900 dark:text-red-200">{{ $reminder->title }}</h3>
                                        <p class="text-sm text-red-700 dark:text-red-300">
                                            Programat pentru <span data-timestamp="{{ $reminder->next_occurrence }}" data-type="time"></span>
                                            (<span data-timestamp="{{ $reminder->next_occurrence }}" data-type="diff"></span> restant)
                                        </p>
                                    </div>
                                    <a href="{{ route('voice.interface') }}"
                                       class="ml-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                                        Completează
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Today's Reminders -->
            @php
                $todayReminders = $activeReminders->filter(function($reminder) {
                    return $reminder->next_occurrence->isToday() && !$reminder->next_occurrence->isPast();
                });
            @endphp

            @if($todayReminders->count() > 0)
                <div class="mb-8">
                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">Pentru astăzi</h4>
                    <div class="space-y-4">
                        @foreach($todayReminders as $reminder)
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-blue-900 dark:text-blue-200">{{ $reminder->title }}</h3>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            Programat pentru <span data-timestamp="{{ $reminder->next_occurrence }}" data-type="time"></span>
                                        </p>
                                    </div>
                                    <a href="{{ route('voice.interface') }}"
                                       class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                        Completează
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Upcoming Reminders -->
            @php
                $upcomingReminders = $activeReminders->filter(function($reminder) {
                    return !$reminder->next_occurrence->isToday() && !$reminder->next_occurrence->isPast();
                });
            @endphp

            @if($upcomingReminders->count() > 0)
                <div class="mb-8">
                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">Viitoare</h4>
                    <div class="space-y-4">
                        @foreach($upcomingReminders as $reminder)
                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-yellow-900 dark:text-yellow-200">{{ $reminder->title }}</h3>
                                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                            Programat pentru <span data-timestamp="{{ $reminder->next_occurrence }}" data-type="time"></span>
                                        </p>
                                    </div>
                                    <a href="{{ route('voice.interface') }}"
                                       class="ml-4 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 text-sm">
                                        Completează
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($overdueReminders->count() === 0 && $todayReminders->count() === 0 && $upcomingReminders->count() === 0)
                <div class="text-center py-8">
                    <p class="text-gray-600 dark:text-gray-400">Nu există memento-uri active</p>
                </div>
            @endif

            <!-- Completed Reminders -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Memento-uri completate</h3>
                    @forelse($completedReminders as $reminder)
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 mb-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-green-900 dark:text-green-200">{{ $reminder->title }}</h3>
                                    @if($reminder->priority)
                                        <span class="ml-2 px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                                            Prioritate {{ $reminder->priority }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                                    Completat <span data-timestamp="{{ $reminder->completed_at }}" data-type="diff"></span> în urmă
                                </p>
                                @if($reminder->description)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $reminder->description }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <p class="text-gray-600 dark:text-gray-400">Nu există memento-uri completate</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
