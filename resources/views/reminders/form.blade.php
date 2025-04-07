<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-primary">
            {{ isset($reminder) ? 'Editează Memento' : 'Creează Memento Nou' }}
        </h1>
    </x-slot>

    <div class="card">
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 rounded-lg border border-red-200">
                <ul class="list-disc list-inside text-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ isset($reminder) ? route('reminders.update', $reminder) : route('reminders.store') }}" class="space-y-6">
            @csrf
            @if(isset($reminder))
                @method('PUT')
            @endif

            <!-- Title -->
            <div>
                <label for="title" class="block text-md font-medium text-gray-700 mb-2">Titlu</label>
                <input type="text" name="title" id="title" value="{{ old('title', $reminder->title ?? '') }}" required>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-md font-medium text-gray-700 mb-2">Descriere</label>
                <textarea name="description" id="description" rows="3">{{ old('description', $reminder->description ?? '') }}</textarea>
            </div>

            <!-- Frequency -->
            <div>
                <label for="frequency" class="block text-md font-medium text-gray-700 mb-2">Frecvență</label>
                <select name="frequency" id="frequency" required>
                    <option value="daily" {{ (old('frequency', $reminder->frequency ?? '') == 'daily') ? 'selected' : '' }}>Zilnic</option>
                    <option value="weekly" {{ (old('frequency', $reminder->frequency ?? '') == 'weekly') ? 'selected' : '' }}>Săptămânal</option>
                    <option value="monthly" {{ (old('frequency', $reminder->frequency ?? '') == 'monthly') ? 'selected' : '' }}>Lunar</option>
                    <option value="yearly" {{ (old('frequency', $reminder->frequency ?? '') == 'yearly') ? 'selected' : '' }}>Anual</option>
                    <option value="once" {{ (old('frequency', $reminder->frequency ?? '') == 'once') ? 'selected' : '' }}>O singură dată</option>
                </select>
            </div>

            <!-- Time -->
            <div>
                <label for="time" class="block text-md font-medium text-gray-700 mb-2">Ora</label>
                <input type="time" name="time" id="time" value="{{ old('time', isset($reminder) ? $reminder->time->format('H:i') : '') }}" required>
            </div>

            <!-- Start Date -->
            <div>
                <label for="start_date" class="block text-md font-medium text-gray-700 mb-2">Data de început</label>
                <input type="date" name="start_date" id="start_date" 
                       value="{{ old('start_date', isset($reminder) ? $reminder->start_date->format('Y-m-d') : '') }}" required>
            </div>

            <!-- Duration Type -->
            <div>
                <label class="block text-md font-medium text-gray-700 mb-2">Tip de durată</label>
                <div class="mt-2 space-y-2 md:space-y-0 md:space-x-6 flex flex-col md:flex-row">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="duration_type" value="forever" 
                               {{ (old('duration_type', isset($reminder) ? ($reminder->is_forever ? 'forever' : 'until') : '') == 'forever') ? 'checked' : '' }}
                               class="h-5 w-5 text-primary focus:ring-primary mr-2" required>
                        <span class="text-md">Permanent</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio" name="duration_type" value="until" 
                               {{ (old('duration_type', isset($reminder) ? ($reminder->is_forever ? 'forever' : 'until') : '') == 'until') ? 'checked' : '' }}
                               class="h-5 w-5 text-primary focus:ring-primary mr-2" required>
                        <span class="text-md">Până la o dată</span>
                    </label>
                </div>
            </div>

            <!-- End Date (shown only when "until" is selected) -->
            <div id="end_date_container" style="display: none;">
                <label for="end_date" class="block text-md font-medium text-gray-700 mb-2">Data de sfârșit</label>
                <input type="date" name="end_date" id="end_date" 
                       value="{{ old('end_date', isset($reminder) ? $reminder->end_date?->format('Y-m-d') : '') }}">
            </div>

            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end mt-8">
                <a href="{{ route('reminders.index') }}" class="btn border border-gray-300 text-gray-700 hover:bg-gray-50">
                    Anulează
                </a>
                <button type="submit" class="btn btn-primary">
                    {{ isset($reminder) ? 'Actualizează' : 'Creează' }}
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const durationTypeInputs = document.querySelectorAll('input[name="duration_type"]');
            const endDateContainer = document.getElementById('end_date_container');
            const endDateInput = document.getElementById('end_date');

            function toggleEndDate() {
                const selectedType = document.querySelector('input[name="duration_type"]:checked').value;
                endDateContainer.style.display = selectedType === 'until' ? 'block' : 'none';
                if (selectedType === 'forever') {
                    endDateInput.value = '';
                }
            }

            durationTypeInputs.forEach(input => {
                input.addEventListener('change', toggleEndDate);
            });

            // Initial state
            toggleEndDate();
        });
    </script>
    @endpush
</x-app-layout> 