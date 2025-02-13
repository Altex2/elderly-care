<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Manage Reminders') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @foreach($patients as $patient)
                <div class="mb-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $patient->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $patient->email }}</p>
                            </div>
                            <button onclick="confirmPatientDelete({{ $patient->id }}, '{{ $patient->name }}')"
                                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                Remove Patient
                            </button>
                        </div>

                        <div class="space-y-4">
                            @forelse($patient->assignedReminders as $reminder)
                                <div class="border dark:border-gray-700 rounded-lg p-4 flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $reminder->title }}</h4>
                                            <span class="px-2 py-1 text-xs rounded-full
                                                {{ $reminder->priority >= 4 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                                   ($reminder->priority >= 2 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                                   'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200') }}">
                                                Priority {{ $reminder->priority }}
                                            </span>
                                            <span class="px-2 py-1 text-xs rounded-full
                                                {{ $reminder->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                                   'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                                                {{ ucfirst($reminder->status) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $reminder->description }}</p>
                                        <div class="mt-2 text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Schedule: </span>
                                            <span class="text-gray-900 dark:text-gray-100">{{ $reminder->schedule }}</span>
                                        </div>
                                        <div class="text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Next occurrence: </span>
                                            <span class="text-gray-900 dark:text-gray-100">
                                                {{ $reminder->next_occurrence ? $reminder->next_occurrence->format('F j, Y g:i A') : 'Not scheduled' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="editReminder({{ $reminder->id }})"
                                                class="text-blue-600 hover:text-blue-800 dark:hover:text-blue-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button onclick="deleteReminder({{ $reminder->id }})"
                                                class="text-red-600 hover:text-red-800 dark:hover:text-red-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-gray-600 dark:text-gray-400">
                                    No reminders set for this patient.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Patient Delete Confirmation Modal -->
    <div id="deletePatientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Delete Patient</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Are you sure you want to delete <span id="deletePatientName" class="font-medium"></span>?
                    This action cannot be undone and will remove all associated reminders.
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Type "delete" to confirm:
                </p>
                <input type="text"
                       id="deleteConfirmation"
                       class="mb-4 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-red-500 focus:ring-red-500"
                       placeholder="Type 'delete' to confirm">
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button id="confirmDeleteBtn"
                            disabled
                            onclick="executePatientDelete()"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Delete Patient
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
        }

        function closeDeleteModal() {
            document.getElementById('deletePatientModal').classList.add('hidden');
            patientToDelete = null;
        }

        document.getElementById('deleteConfirmation').addEventListener('input', function(e) {
            document.getElementById('confirmDeleteBtn').disabled = e.target.value.toLowerCase() !== 'delete';
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
            // Implement reminder editing logic
        }

        function deleteReminder(reminderId) {
            if (confirm('Are you sure you want to delete this reminder?')) {
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
