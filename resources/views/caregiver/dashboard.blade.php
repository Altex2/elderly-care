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

        <!-- Notifications -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Notificări</h2>
            
            @if($notifications->isEmpty() && $patients->isEmpty())
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Pentru a primi notificări, adăugați mai întâi un pacient în îngrijirea dumneavoastră.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($notifications->isEmpty())
                <div class="text-gray-500 text-sm">
                    Nu există notificări noi.
                </div>
            @else
                <div class="space-y-4">
                    @foreach($notifications->groupBy('data.patient_id') as $patientId => $patientNotifications)
                        @php
                            $patient = $patients->firstWhere('id', $patientId);
                        @endphp
                        <div x-data="{ open: false }" class="border rounded-lg overflow-hidden">
                            <button @click="open = !open" class="w-full flex justify-between items-center p-4 bg-gray-50 hover:bg-gray-100">
                                <div class="flex items-center space-x-3">
                                    <svg class="h-5 w-5 text-gray-500" :class="{ 'transform rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-medium text-gray-900">{{ $patient->name }}</span>
                                </div>
                                <span class="text-sm text-gray-500">{{ $patientNotifications->count() }} notificări</span>
                            </button>
                            <div x-show="open" x-cloak class="border-t divide-y">
                                @foreach($patientNotifications as $notification)
                                    <div class="p-4 {{ $notification->type === 'App\\Notifications\\MissedReminderNotification' ? 'bg-red-50' : 'bg-blue-50' }}">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-gray-900 font-medium">{{ $notification->data['message'] }}</p>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    Programat pentru: <span class="font-medium">{{ $notification->data['scheduled_time'] }}</span>
                                                </p>
                                            </div>
                                            @if($notification->type === 'App\\Notifications\\MissedReminderNotification')
                                                <div x-data="notifications" class="flex flex-col space-y-3">
                                                    <div class="flex space-x-2">
                                                        <button @click="showAcceptConfirm = true" type="button" class="px-5 py-3 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                            Acceptă
                                                        </button>
                                                        <button @click="showDenyConfirm = true" type="button" class="px-5 py-3 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                            Respinge
                                                        </button>
                                                    </div>

                                                    <!-- Accept Confirmation Modal -->
                                                    <div x-show="showAcceptConfirm" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                                                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                                    <div class="sm:flex sm:items-start">
                                                                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                                                            <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                            </svg>
                                                                        </div>
                                                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                                            <h3 class="text-lg leading-6 font-medium text-gray-900">Confirmă acceptarea</h3>
                                                                            <div class="mt-2">
                                                                                <p class="text-sm text-gray-500">Sunteți sigur că doriți să marcați acest memento ca fiind completat cu întârziere?</p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                                    <form action="{{ route('caregiver.reminders.accept-missed', $notification->data['reminder_id']) }}" method="POST" class="inline" @submit.prevent="handleReminderAction($event, 'accept')">
                                                                        @csrf
                                                                        <input type="hidden" name="patient_id" value="{{ $notification->data['patient_id'] }}">
                                                                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                                            Confirmă
                                                                        </button>
                                                                    </form>
                                                                    <button type="button" @click="showAcceptConfirm = false" class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                                        Anulare
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Deny Confirmation Modal -->
                                                    <div x-show="showDenyConfirm" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                                                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                                    <div class="sm:flex sm:items-start">
                                                                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                            </svg>
                                                                        </div>
                                                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                                            <h3 class="text-lg leading-6 font-medium text-gray-900">Confirmă respingerea</h3>
                                                                            <div class="mt-2">
                                                                                <p class="text-sm text-gray-500">Sunteți sigur că doriți să marcați acest memento ca necompletat și să-l reprogramați?</p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                                    <form action="{{ route('caregiver.reminders.deny-missed', $notification->data['reminder_id']) }}" method="POST" class="inline" @submit.prevent="handleReminderAction($event, 'deny')">
                                                                        @csrf
                                                                        <input type="hidden" name="patient_id" value="{{ $notification->data['patient_id'] }}">
                                                                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                                            Confirmă
                                                                        </button>
                                                                    </form>
                                                                    <button type="button" @click="showDenyConfirm = false" class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                                        Anulare
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md mt-5">
                                            <p class="mb-1"><span class="font-medium text-green-600">Acceptă</span> - Marchează memento-ul ca fiind completat cu întârziere și păstrează programarea.</p>
                                            <p><span class="font-medium text-red-600">Respinge</span> - Marchează memento-ul ca necompletat și reprogramează pentru următoarea ocurență.</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
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
                                        class="flex text-sm md:text-base items-center space-x-2 px-2 py-1 bg-primary text-white rounded-md hover:bg-primary-hover transition-colors bg-blue-500 hover:bg-blue-600">
                                    Adaugă memento
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
    <div id="addReminderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full relative">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-primary mb-4">Adaugă memento nou</h3>
                    <form id="addReminderForm" onsubmit="submitReminderForm(event)">
                        @csrf
                        <input type="hidden" name="user_id" id="reminder_user_id">
                        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Titlu</label>
                                <input type="text" name="title" required class="w-full">
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Descriere</label>
                                <textarea name="description" rows="3" class="w-full"></textarea>
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Frecvență</label>
                                <select name="frequency" required class="w-full">
                                    <option value="daily">Zilnic</option>
                                    <option value="weekly">Săptămânal</option>
                                    <option value="monthly">Lunar</option>
                                    <option value="yearly">Anual</option>
                                    <option value="once">O singură dată</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Data de început</label>
                                <input type="datetime-local" name="start_date" required class="w-full" 
                                       placeholder="Selectează data și ora"
                                       onfocus="this.showPicker()">
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Data de sfârșit (opțional)</label>
                                <input type="datetime-local" name="end_date" class="w-full"
                                       placeholder="Selectează data și ora"
                                       onfocus="this.showPicker()">
                            </div>
                            <div>
                                <label class="block text-md font-medium text-gray-700 mb-2">Prioritate (1-5)</label>
                                <input type="number" name="priority" required min="1" max="5" value="3" class="w-full">
                                <p class="text-sm text-gray-500">1 - Prioritate scăzută, 5 - Prioritate ridicată</p>
                            </div>
                            
                        </div>
                        <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:justify-end sticky bottom-0 bg-white pt-4 border-t">
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
        // Enable browser notifications
        document.addEventListener('DOMContentLoaded', function() {
            // Request permission for browser notifications
            if ('Notification' in window) {
                if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === 'granted') {
                            console.log('Notification permission granted');
                        }
                    });
                }
            }
            
            // Store the user ID for Echo channel authentication
            window.userId = "{{ auth()->id() }}";
        });
    
        function openReminderModal(patientId) {
            document.getElementById('reminder_user_id').value = patientId;
            document.getElementById('addReminderModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeReminderModal() {
            document.getElementById('addReminderModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function openAddPatientModal() {
            document.getElementById('addPatientModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeAddPatientModal() {
            document.getElementById('addPatientModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Close modal when clicking outside
        document.getElementById('addReminderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReminderModal();
            }
        });

        document.getElementById('addPatientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddPatientModal();
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
                    showNotification(`Memento-ul "${data.reminder.title}" a fost creat cu succes!`, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('A apărut o eroare. Vă rugăm să încercați din nou.', 'error');
            });
        }

        function showNotification(title, schedule) {
            const notification = document.getElementById('reminderSuccessNotification');
            const titleElement = document.getElementById('reminderSuccessTitle');
            const scheduleElement = document.getElementById('reminderSuccessSchedule');
            const notificationBorder = notification.querySelector('div');
            const notificationIcon = notification.querySelector('svg');
            
            // Update notification content
            titleElement.textContent = `Memento-ul "${title}" a fost creat cu succes!`;
            scheduleElement.textContent = schedule;
            
            // Ensure green color
            notificationBorder.classList.remove('border-red-500');
            notificationBorder.classList.add('border-green-500');
            notificationIcon.classList.remove('text-red-500');
            notificationIcon.classList.add('text-green-500');
            
            // Show notification
            notification.classList.remove('translate-y-full', 'opacity-0');
            notification.classList.add('translate-y-0', 'opacity-100');
            
            // Show browser notification if permission is granted
            if ('Notification' in window && Notification.permission === 'granted') {
                const browserNotification = new Notification('Reminder Buddy', {
                    body: `Memento-ul "${title}" a fost creat cu succes!`,
                    icon: '/images/logo.png'
                });
                
                setTimeout(() => {
                    browserNotification.close();
                }, 5000);
            }
            
            setTimeout(hideNotification, 5000);
        }

        function hideNotification() {
            const notification = document.getElementById('reminderSuccessNotification');
            notification.classList.add('translate-y-full', 'opacity-0');
            notification.classList.remove('translate-y-0', 'opacity-100');
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('notifications', () => ({
                showAcceptConfirm: false,
                showDenyConfirm: false,
                handleReminderAction(event, action) {
                    event.preventDefault();
                    const form = event.target;
                    const formData = new FormData(form);
                    
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Close the modal
                            if (action === 'accept') {
                                this.showAcceptConfirm = false;
                            } else {
                                this.showDenyConfirm = false;
                            }
                            
                            // Remove the notification from the UI
                            const notificationContainer = form.closest('.p-4');
                            if (notificationContainer) {
                                notificationContainer.remove();
                            }
                            
                            // Update notification count
                            const patientContainer = form.closest('[x-data]');
                            const countElement = patientContainer.querySelector('.text-sm.text-gray-500');
                            if (countElement) {
                                const currentCount = parseInt(countElement.textContent);
                                if (!isNaN(currentCount) && currentCount > 0) {
                                    const newCount = currentCount - 1;
                                    countElement.textContent = `${newCount} notificări`;
                                    
                                    // If this was the last notification, hide the entire patient section
                                    if (newCount === 0) {
                                        patientContainer.remove();
                                    }
                                }
                            }
                            
                            // Show success notification
                            showNotification('Acțiunea a fost efectuată cu succes!', 'success');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('A apărut o eroare. Vă rugăm să încercați din nou.', 'error');
                    });
                }
            }));
        });

        function showNotification(message, type) {
            const notification = document.getElementById('reminderSuccessNotification');
            const title = document.getElementById('reminderSuccessTitle');
            const schedule = document.getElementById('reminderSuccessSchedule');
            const notificationBorder = notification.querySelector('div');
            const notificationIcon = notification.querySelector('svg');
            
            // Update notification content
            title.textContent = message;
            schedule.textContent = '';
            
            // Update notification color based on type
            notificationBorder.classList.remove('border-red-500', 'border-green-500');
            notificationIcon.classList.remove('text-red-500', 'text-green-500');
            
            if (type === 'success') {
                notificationBorder.classList.add('border-green-500');
                notificationIcon.classList.add('text-green-500');
            } else {
                notificationBorder.classList.add('border-red-500');
                notificationIcon.classList.add('text-red-500');
            }
            
            // Show notification
            notification.classList.remove('translate-y-full', 'opacity-0');
            notification.classList.add('translate-y-0', 'opacity-100');
            
            // Hide notification after 5 seconds
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
