<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Tablou de bord pentru îngrijitori') }}
            </h2>
            <button onclick="openAddPatientModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Adaugă pacient nou
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">Total pacienți</div>
                        <div class="text-2xl font-bold">{{ $patients->count() }}</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">Memento-uri active</div>
                        <div class="text-2xl font-bold">{{ $reminders->where('status', 'active')->count() }}</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">Sarcini pentru astăzi</div>
                        <div class="text-2xl font-bold">
                            {{ $reminders->where('status', 'active')
                                ->filter(function($reminder) {
                                    return $reminder->next_occurrence?->isToday();
                                })->count() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patients List -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Pacienții dvs.</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($patients as $patient)
                            <div class="border dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-lg">{{ $patient->name }}</h4>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $patient->email }}</p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="openReminderModal({{ $patient->id }})"
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <h5 class="text-sm font-medium mb-2">Memento-uri pentru astăzi</h5>
                                    @php
                                        $patientReminders = $reminders->where('user_id', $patient->id)
                                            ->where('status', 'active')
                                            ->filter(function($reminder) {
                                                return $reminder->next_occurrence?->isToday();
                                            });
                                    @endphp
                                    @forelse($patientReminders as $reminder)
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            • {{ $reminder->title }}
                                        </div>
                                    @empty
                                        <div class="text-sm text-gray-500">Nu există memento-uri pentru astăzi</div>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-8 text-gray-500">
                                Nu există pacienți încă. Apăsați "Adaugă pacient nou" pentru a începe.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Adaugă pacient nou</h3>
                    <form id="addPatientForm" action="{{ route('caregiver.patients.create') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nume</label>
                                <input type="text" name="name" required
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email" name="email" required
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Parolă</label>
                                <input type="password" name="password" required
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeAddPatientModal()"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                Anulare
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Adaugă pacient
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Reminder Modal -->
    <div id="addReminderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Adaugă memento nou</h3>
                    <form id="addReminderForm" onsubmit="submitReminderForm(event)">
                        @csrf
                        <input type="hidden" name="user_id" id="reminder_user_id">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Titlu</label>
                                <input type="text" name="title" required
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descriere</label>
                                <textarea name="description"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Program</label>
                                <input type="text" name="schedule" required
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="ex., zilnic la ora 9">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prioritate (1-5)</label>
                                <input type="number" name="priority" required min="1" max="5"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeReminderModal()"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                Anulare
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Adaugă memento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Notification -->
    <div id="reminderSuccessNotification"
         class="fixed bottom-4 right-4 z-50 transform translate-y-full opacity-0 transition-all duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 flex items-center space-x-3 border-l-4 border-green-500">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100" id="reminderSuccessTitle"></p>
                <p class="text-sm text-gray-500 dark:text-gray-400" id="reminderSuccessSchedule"></p>
            </div>
            <button onclick="hideNotification()" class="flex-shrink-0 text-gray-400 hover:text-gray-500">
                <span class="sr-only">Închide</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    </div>

    @push('scripts')
    <script>
        function openAddPatientModal() {
            document.getElementById('addPatientModal').classList.remove('hidden');
        }

        function closeAddPatientModal() {
            document.getElementById('addPatientModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('addPatientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddPatientModal();
            }
        });

        function openReminderModal(patientId) {
            document.getElementById('reminder_user_id').value = patientId;
            document.getElementById('addReminderModal').classList.remove('hidden');
        }

        function closeReminderModal() {
            document.getElementById('addReminderModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('addReminderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReminderModal();
            }
        });

        function submitReminderForm(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            fetch('{{ route('caregiver.reminders.create') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(response => response.json())
            .then(data => {
                if (data.reminder) {
                    showNotification(data.reminder);
                    closeReminderModal();
                    form.reset();

                    // Optionally refresh the reminders list
                    // You might want to add the new reminder to the UI without a full page reload
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error notification if needed
            });
        }

        function showNotification(reminder) {
            const notification = document.getElementById('reminderSuccessNotification');
            const title = document.getElementById('reminderSuccessTitle');
            const schedule = document.getElementById('reminderSuccessSchedule');

            title.textContent = `Reminder created: ${reminder.title}`;
            schedule.textContent = `Scheduled: ${reminder.schedule}`;

            // Show notification with animation
            notification.classList.remove('translate-y-full', 'opacity-0');
            notification.classList.add('translate-y-0', 'opacity-100');

            // Auto-hide after 5 seconds
            setTimeout(hideNotification, 5000);
        }

        function hideNotification() {
            const notification = document.getElementById('reminderSuccessNotification');
            notification.classList.add('translate-y-full', 'opacity-0');
            notification.classList.remove('translate-y-0', 'opacity-100');
        }
    </script>
    @endpush
</x-app-layout>
