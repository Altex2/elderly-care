<x-elderly-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                Memento Nou
            </h1>
            <p class="text-xl text-gray-600">
                Completați formularul pentru a crea un nou memento.
            </p>
        </div>

        <!-- Form -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <form action="{{ route('reminders.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Title -->
                <div>
                    <label for="title" class="block text-xl font-medium text-gray-700 mb-2">
                        Titlu
                    </label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           value="{{ old('title') }}"
                           class="w-full px-4 py-3 text-lg border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           required>
                    @error('title')
                        <p class="mt-1 text-lg text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-xl font-medium text-gray-700 mb-2">
                        Descriere
                    </label>
                    <textarea name="description" 
                              id="description" 
                              rows="3"
                              class="w-full px-4 py-3 text-lg border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-lg text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-xl font-medium text-gray-700 mb-2">
                        Data de început
                    </label>
                    <input type="datetime-local" 
                           name="start_date" 
                           id="start_date" 
                           value="{{ old('start_date') }}"
                           class="w-full px-4 py-3 text-lg border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                           required>
                    @error('start_date')
                        <p class="mt-1 text-lg text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-xl font-medium text-gray-700 mb-2">
                        Data de sfârșit (opțional)
                    </label>
                    <input type="datetime-local" 
                           name="end_date" 
                           id="end_date" 
                           value="{{ old('end_date') }}"
                           class="w-full px-4 py-3 text-lg border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    @error('end_date')
                        <p class="mt-1 text-lg text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Frequency -->
                <div>
                    <label for="frequency" class="block text-xl font-medium text-gray-700 mb-2">
                        Frecvență
                    </label>
                    <select name="frequency" 
                            id="frequency" 
                            class="w-full px-4 py-3 text-lg border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Selectați frecvența</option>
                        <option value="once" {{ old('frequency') == 'once' ? 'selected' : '' }}>O singură dată</option>
                        <option value="daily" {{ old('frequency') == 'daily' ? 'selected' : '' }}>Zilnic</option>
                        <option value="weekly" {{ old('frequency') == 'weekly' ? 'selected' : '' }}>Săptămânal</option>
                        <option value="monthly" {{ old('frequency') == 'monthly' ? 'selected' : '' }}>Lunar</option>
                    </select>
                    @error('frequency')
                        <p class="mt-1 text-lg text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Priority -->
                <div>
                    <label for="priority" class="block text-xl font-medium text-gray-700 mb-2">
                        Prioritate
                    </label>
                    <select name="priority" 
                            id="priority" 
                            class="w-full px-4 py-3 text-lg border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="0" {{ old('priority') == '0' ? 'selected' : '' }}>Normală</option>
                        <option value="1" {{ old('priority') == '1' ? 'selected' : '' }}>Importantă</option>
                        <option value="2" {{ old('priority') == '2' ? 'selected' : '' }}>Foarte importantă</option>
                        <option value="3" {{ old('priority') == '3' ? 'selected' : '' }}>Urgentă</option>
                    </select>
                    @error('priority')
                        <p class="mt-1 text-lg text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('reminders.index') }}" 
                       class="px-6 py-3 text-lg font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700">
                        Anulează
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 text-lg font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Creează Memento
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-elderly-layout> 