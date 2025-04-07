<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-primary">
            {{ __('Gestionare memento-uri') }}
        </h1>
    </x-slot>

    <div class="space-y-6">
        @foreach($patients as $patient)
            <div class="card">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-primary">{{ $patient->name }}</h2>
                        <p class="text-gray-600">{{ $patient->email }}</p>
                    </div>
                    <button onclick="confirmPatientDelete({{ $patient->id }}, '{{ $patient->name }}')"
                            class="btn flex items-center justify-center bg-danger text-white bg-red-600 hover:bg-red-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Elimină pacient
                    </button>
                </div>

                <div class="space-y-4">
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
                                        {{ ucfirst($reminder->status) }}
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
                                        {{ $reminder->next_occurrence ? $reminder->next_occurrence->format('j F Y, H:i') : 'Nu este programat' }}
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
            // To be implemented
            alert('Funcționalitatea de editare va fi disponibilă în curând.');
        }

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
