<x-elderly-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">
                        Memento-uri
                    </h1>
                    <p class="text-xl text-gray-600">
                        Gestionați toate memento-urile dvs.
                    </p>
                </div>
                <a href="{{ route('reminders.create') }}" 
                   class="inline-flex items-center px-6 py-3 text-lg font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Memento Nou
                </a>
            </div>
        </div>

        <!-- Reminders List -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="space-y-4">
                @forelse($reminders as $reminder)
                    <div class="p-6 bg-gray-50 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-2xl font-medium text-gray-900 mb-2">
                                    {{ $reminder->title }}
                                </h3>
                                @if($reminder->description)
                                    <p class="text-xl text-gray-600 mb-4">
                                        {{ $reminder->description }}
                                    </p>
                                @endif
                                <div class="flex items-center space-x-4 text-lg text-gray-600">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $reminder->next_occurrence->format('d.m.Y H:i') }}
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        {{ ucfirst($reminder->frequency) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4 ml-6">
                                @if(!$reminder->completed)
                                    <button onclick="completeReminder('{{ $reminder->id }}')" 
                                            class="px-6 py-3 text-lg font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                        Am Făcut
                                    </button>
                                @endif
                                <a href="{{ route('reminders.edit', $reminder) }}" 
                                   class="px-6 py-3 text-lg font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                    Editează
                                </a>
                                <form action="{{ route('reminders.destroy', $reminder) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="px-6 py-3 text-lg font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                                            onclick="return confirm('Sigur doriți să ștergeți acest memento?')">
                                        Șterge
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <h3 class="mt-2 text-2xl font-medium text-gray-900">Nu există memento-uri</h3>
                        <p class="mt-1 text-xl text-gray-500">Creați un memento nou pentru a începe.</p>
                        <div class="mt-6">
                            <a href="{{ route('reminders.create') }}" 
                               class="inline-flex items-center px-6 py-3 text-lg font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Memento Nou
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function completeReminder(reminderId) {
            fetch(`/reminders/${reminderId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('A apărut o eroare la completarea memento-ului.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('A apărut o eroare la completarea memento-ului.');
            });
        }
    </script>
    @endpush
</x-elderly-layout> 