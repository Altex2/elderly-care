<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-2xl font-bold text-primary">
                {{ __('Tablou de bord pentru îngrijitori') }}
            </h1>
            <button onclick="openAddPatientModal()" class="btn btn-primary">
                Adaugă pacient nou
            </button>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card">
                <div class="text-gray-600 text-sm mb-2">Total pacienți</div>
                <div class="text-2xl font-bold text-primary">{{ $patients->count() }}</div>
            </div>
            <div class="card">
                <div class="text-gray-600 text-sm mb-2">Memento-uri active</div>
                <div class="text-2xl font-bold text-primary">
                    @php
                        $activeReminders = collect();
                        foreach($patients as $patient) {
                            $patientReminders = $patient->assignedReminders()
                                ->where('status', 'active')
                                ->get();
                            $activeReminders = $activeReminders->merge($patientReminders);
                        }
                    @endphp
                    {{ $activeReminders->count() }}
                </div>
            </div>
            <div class="card">
                <div class="text-gray-600 text-sm mb-2">Sarcini pentru astăzi</div>
                <div class="text-2xl font-bold text-primary">
                    @php
                        $todayReminders = $activeReminders->filter(function($reminder) {
                            return $reminder->next_occurrence && $reminder->next_occurrence->isToday();
                        });
                    @endphp
                    {{ $todayReminders->count() }}
                </div>
            </div>
        </div>

        <!-- Patients List -->
        <div class="card">
            <h2 class="text-xl font-semibold text-primary mb-4">Pacienții dvs.</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($patients as $patient)
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow bg-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-lg">{{ $patient->name }}</h3>
                                <p class="text-gray-600 text-sm">{{ $patient->email }}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="openReminderModal({{ $patient->id }})"
                                        class="text-primary hover:text-primary-hover">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h4 class="text-md font-medium mb-2">Memento-uri pentru astăzi:</h4>
                            @php
                                $patientReminders = $patient->assignedReminders()
                                    ->where('status', 'active')
                                    ->get()
                                    ->filter(function($reminder) {
                                        return $reminder->next_occurrence && $reminder->next_occurrence->isToday();
                                    });
                            @endphp
                            @forelse($patientReminders as $reminder)
                                <div class="text-md text-blue-500 mb-1">
                                    • {{ $reminder->title }}
                                </div>
                            @empty
                                <div class="text-md text-green-500">Nu există memento-uri pentru astăzi</div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-600 text-lg">
                        Nu există pacienți încă. Apăsați "Adaugă pacient nou" pentru a începe.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-primary mb-4">Adaugă pacient nou</h3>
                    <form id="addPatientForm" action="{{ route('caregiver.patients.create') }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Nume</label>
                                <input type="text" name="name" required>
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" required>
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Parolă</label>
                                <input type="password" name="password" required>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
                            <button type="button" onclick="closeAddPatientModal()"
                                    class="btn border border-gray-300 text-gray-700 hover:bg-gray-50">
                                Anulare
                            </button>
                            <button type="submit" class="btn btn-primary">
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
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-primary mb-4">Adaugă memento nou</h3>
                    <form id="addReminderForm" onsubmit="submitReminderForm(event)">
                        @csrf
                        <input type="hidden" name="user_id" id="reminder_user_id">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Titlu</label>
                                <input type="text" name="title" required>
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Descriere</label>
                                <textarea name="description" rows="3"></textarea>
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Frecvență</label>
                                <select name="frequency" required>
                                    <option value="daily">Zilnic</option>
                                    <option value="weekly">Săptămânal</option>
                                    <option value="monthly">Lunar</option>
                                    <option value="yearly">Anual</option>
                                    <option value="once">O singură dată</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Data de început</label>
                                <input type="datetime-local" name="start_date" required>
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Data de sfârșit (opțional)</label>
                                <input type="datetime-local" name="end_date">
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Prioritate (1-5)</label>
                                <input type="number" name="priority" required min="1" max="5" value="3">
                            </div>
                        </div>
                        <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
                            <button type="button" onclick="closeReminderModal()"
                                    class="btn border border-gray-300 text-gray-700 hover:bg-gray-50">
                                Anulare
                            </button>
                            <button type="submit" class="btn btn-primary">
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
        <div class="bg-white rounded-lg shadow-lg p-4 flex items-center space-x-3 border-l-4 border-green-500">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-md font-medium text-gray-900" id="reminderSuccessTitle"></p>
                <p class="text-md text-gray-600" id="reminderSuccessSchedule"></p>
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
            
            const form = document.getElementById('addReminderForm');
            const formData = new FormData(form);
            
            fetch('{{ route('caregiver.reminders.create') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeReminderModal();
                    showNotification(data.reminder.title, data.reminder.schedule);
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function showNotification(title, schedule) {
            const notification = document.getElementById('reminderSuccessNotification');
            document.getElementById('reminderSuccessTitle').textContent = title;
            document.getElementById('reminderSuccessSchedule').textContent = schedule;
            
            notification.classList.remove('translate-y-full', 'opacity-0');
            notification.classList.add('translate-y-0', 'opacity-100');
            
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
