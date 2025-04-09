<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-primary">
            {{ __('Gestionare memento-uri') }}
        </h1>
    </x-slot>

    <div class="space-y-6">
        @if($patients->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Pentru a vedea memento-uri, adăugați mai întâi un pacient în îngrijirea dumneavoastră.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @foreach($patients as $patient)
                <div class="card" x-data="{ open: false }">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-4">
                        <div>
                            <h2 class="text-xl font-semibold text-primary">{{ $patient->name }}</h2>
                            <p class="text-gray-600">{{ $patient->email }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="confirmPatientDelete({{ $patient->id }}, '{{ $patient->name }}')"
                                    class="btn flex items-center justify-center bg-danger text-white bg-red-600 hover:bg-red-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Elimină pacient
                            </button>
                            <button @click="open = !open" class="btn flex items-center justify-center bg-primary text-white bg-gray-600 hover:bg-gray-700">
                                <svg class="w-5 h-5 mr-2 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                                <span x-text="open ? 'Ascunde memento-uri' : 'Afișează memento-uri'"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="open" x-cloak class="space-y-4">
                        @forelse($patient->assignedReminders as $reminder)
                            <div class="bg-white border rounded-lg p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $reminder->title }}</h3>
                                        <span class="px-3 py-1 text-sm rounded-full
                                            {{ $reminder->priority >= 4 ? 'bg-red-100 text-red-800' :
                                               ($reminder->priority >= 2 ? 'bg-yellow-100 text-yellow-800' :
                                               'bg-green-100 text-green-800') }}">
                                            Prioritate {{ $reminder->priority }}
                                        </span>
                                        <span class="px-3 py-1 text-sm rounded-full
                                            {{ $reminder->status === 'active' ? 'bg-green-100 text-green-800' :
                                               'bg-gray-100 text-gray-800' }}">
                                            {{ $reminder->getStatusText() }}
                                        </span>
                                    </div>
                                    @if($reminder->description)
                                        <p class="text-md text-gray-600 mb-2">{{ $reminder->description }}</p>
                                    @endif
                                    <div class="text-md mb-1">
                                        <span class="text-gray-600">Program: </span>
                                        <span class="text-gray-900">{{ $reminder->schedule }}</span>
                                    </div>
                                    <div class="text-md">
                                        <span class="text-gray-600">Următoarea apariție: </span>
                                        <span class="text-gray-900">
                                            {{ $reminder->next_occurrence ? $reminder->next_occurrence->locale('ro')->isoFormat('D MMMM YYYY, HH:mm') : 'Nu este programat' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="editReminder({{ $reminder->id }})"
                                            class="btn bg-primary text-white hover:bg-primary-hover bg-gray-600 hover:bg-gray-700">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Editează
                                    </button>
                                    <button onclick="deleteReminder({{ $reminder->id }})"
                                            class="btn bg-danger text-white bg-red-600 hover:bg-red-700">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Șterge
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-gray-600 text-lg">
                                Nu există memento-uri setate pentru acest pacient.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Edit Reminder Modal -->
    <div id="editReminderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-xl font-semibold text-primary mb-4">Editare memento</h3>
                <form id="editReminderForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-md font-medium text-gray-700 mb-2">Titlu</label>
                            <input type="text" name="title" id="editReminderTitle" required class="block w-full">
                        </div>
                        <div>
                            <label class="block text-md font-medium text-gray-700 mb-2">Descriere</label>
                            <textarea name="description" id="editReminderDescription" class="block w-full"></textarea>
                        </div>
                        <div>
                            <label class="block text-md font-medium text-gray-700 mb-2">Prioritate</label>
                            <select name="priority" id="editReminderPriority" required class="block w-full">
                                <option value="1">Scăzută</option>
                                <option value="2">Medie</option>
                                <option value="3">Ridicată</option>
                                <option value="4">Urgentă</option>
                                <option value="5">Critică</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-md font-medium text-gray-700 mb-2">Program</label>
                            <input type="text" name="schedule" id="editReminderSchedule" required class="block w-full">
                        </div>
                        <div>
                            <label class="block text-md font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" id="editReminderStatus" required class="block w-full">
                                <option value="active">Activ</option>
                                <option value="inactive">Inactiv</option>
                                <option value="completed">Completat</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:justify-end">
                        <button type="button" onclick="closeEditReminderModal()"
                                class="btn border border-gray-300 text-gray-700 hover:bg-gray-50">
                            Anulare
                        </button>
                        <button type="submit" class="btn bg-primary text-white">
                            Salvează modificările
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Patient Delete Confirmation Modal -->
    <div id="deletePatientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-xl font-semibold text-primary mb-4">Eliminare pacient</h3>
                <p class="text-gray-600 mb-4">
                    Sigur doriți să eliminați pacientul <span id="deletePatientName" class="font-medium"></span>?
                    Această acțiune nu poate fi anulată și va elimina toate memento-urile asociate.
                </p>
                <p class="text-gray-600 mb-4">
                    Scrieți "delete" pentru a confirma:
                </p>
                <input type="text"
                       id="deleteConfirmation"
                       class="mb-4 block w-full"
                       placeholder="Scrieți 'delete' pentru a confirma">
                <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                    <button onclick="closeDeleteModal()"
                            class="btn border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Anulare
                    </button>
                    <button id="confirmDeleteBtn"
                            disabled
                            onclick="executePatientDelete()"
                            class="btn bg-danger text-white hover:bg-red-700 opacity-50 cursor-not-allowed">
                        Elimină pacient
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let patientToDelete = null;
        let reminderToEdit = null;

        function confirmPatientDelete(patientId, patientName) {
            patientToDelete = patientId;
            document.getElementById('deletePatientName').textContent = patientName;
            document.getElementById('deletePatientModal').classList.remove('hidden');
            document.getElementById('deleteConfirmation').value = '';
            document.getElementById('confirmDeleteBtn').disabled = true;
            document.getElementById('confirmDeleteBtn').classList.add('opacity-50', 'cursor-not-allowed');
        }

        function closeDeleteModal() {
            document.getElementById('deletePatientModal').classList.add('hidden');
            patientToDelete = null;
        }

        document.getElementById('deleteConfirmation').addEventListener('input', function(e) {
            const isValid = e.target.value.toLowerCase() === 'delete';
            document.getElementById('confirmDeleteBtn').disabled = !isValid;
            
            if (isValid) {
                document.getElementById('confirmDeleteBtn').classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                document.getElementById('confirmDeleteBtn').classList.add('opacity-50', 'cursor-not-allowed');
            }
        });

        function executePatientDelete() {
            if (!patientToDelete) return;

            fetch(`/caregiver/patients/${patientToDelete}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Close modal when clicking outside
        document.getElementById('deletePatientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        function editReminder(reminderId) {
            reminderToEdit = reminderId;
            // Fetch reminder data
            fetch(`/caregiver/reminders/${reminderId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editReminderTitle').value = data.title;
                    document.getElementById('editReminderDescription').value = data.description || '';
                    document.getElementById('editReminderPriority').value = data.priority;
                    document.getElementById('editReminderSchedule').value = data.schedule;
                    document.getElementById('editReminderStatus').value = data.status;
                    document.getElementById('editReminderForm').action = `/caregiver/reminders/${reminderId}`;
                    document.getElementById('editReminderModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('A apărut o eroare la încărcarea datelor memento-ului.');
                });
        }

        function closeEditReminderModal() {
            document.getElementById('editReminderModal').classList.add('hidden');
            reminderToEdit = null;
        }

        // Close edit modal when clicking outside
        document.getElementById('editReminderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditReminderModal();
            }
        });

        // Handle edit form submission
        document.getElementById('editReminderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('A apărut o eroare la salvarea modificărilor.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A apărut o eroare la salvarea modificărilor.');
            });
        });

        function deleteReminder(reminderId) {
            if (confirm('Sigur doriți să eliminați acest memento?')) {
                fetch(`/caregiver/reminders/${reminderId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
    </script>
    @endpush
</x-app-layout>
