@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <h2 class="text-2xl font-semibold mb-6">{{ isset($reminder) ? 'Editează Memento' : 'Creează Memento Nou' }}</h2>

            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 dark:bg-red-900 rounded-lg">
                    <ul class="list-disc list-inside text-red-700 dark:text-red-300">
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
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Titlu</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $reminder->title ?? '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" required>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descriere</label>
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm">{{ old('description', $reminder->description ?? '') }}</textarea>
                </div>

                <!-- Frequency -->
                <div>
                    <label for="frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frecvență</label>
                    <select name="frequency" id="frequency" 
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" required>
                        <option value="daily" {{ (old('frequency', $reminder->frequency ?? '') == 'daily') ? 'selected' : '' }}>Zilnic</option>
                        <option value="weekly" {{ (old('frequency', $reminder->frequency ?? '') == 'weekly') ? 'selected' : '' }}>Săptămânal</option>
                        <option value="monthly" {{ (old('frequency', $reminder->frequency ?? '') == 'monthly') ? 'selected' : '' }}>Lunar</option>
                        <option value="yearly" {{ (old('frequency', $reminder->frequency ?? '') == 'yearly') ? 'selected' : '' }}>Anual</option>
                        <option value="once" {{ (old('frequency', $reminder->frequency ?? '') == 'once') ? 'selected' : '' }}>O singură dată</option>
                    </select>
                </div>

                <!-- Time -->
                <div>
                    <label for="time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ora</label>
                    <input type="time" name="time" id="time" value="{{ old('time', isset($reminder) ? $reminder->time->format('H:i') : '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" required>
                </div>

                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de început</label>
                    <input type="date" name="start_date" id="start_date" 
                           value="{{ old('start_date', isset($reminder) ? $reminder->start_date->format('Y-m-d') : '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm" required>
                </div>

                <!-- Duration Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tip de durată</label>
                    <div class="mt-2 space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="duration_type" value="forever" 
                                   {{ (old('duration_type', isset($reminder) ? ($reminder->is_forever ? 'forever' : 'until') : '') == 'forever') ? 'checked' : '' }}
                                   class="form-radio text-indigo-600 dark:text-indigo-400" required>
                            <span class="ml-2">Permanent</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="duration_type" value="until" 
                                   {{ (old('duration_type', isset($reminder) ? ($reminder->is_forever ? 'forever' : 'until') : '') == 'until') ? 'checked' : '' }}
                                   class="form-radio text-indigo-600 dark:text-indigo-400" required>
                            <span class="ml-2">Până la o dată</span>
                        </label>
                    </div>
                </div>

                <!-- End Date (shown only when "until" is selected) -->
                <div id="end_date_container" style="display: none;">
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de sfârșit</label>
                    <input type="date" name="end_date" id="end_date" 
                           value="{{ old('end_date', isset($reminder) ? $reminder->end_date?->format('Y-m-d') : '') }}" 
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('reminders.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Anulează
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ isset($reminder) ? 'Actualizează' : 'Creează' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
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
@endsection 