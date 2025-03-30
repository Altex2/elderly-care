<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestionare pacienți') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Add Patient Form -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Adaugă pacient nou</h3>
                    <form method="POST" action="{{ route('caregiver.patients.add') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="email" :value="__('Email pacient')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required />
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Introduceți emailul unui pacient înregistrat pentru a-l adăuga în lista dvs. de îngrijire.
                            </p>
                        </div>
                        <x-primary-button>Adaugă pacient</x-primary-button>
                    </form>
                </div>
            </div>

            <!-- Current Patients -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pacienții dvs.</h3>
                <div class="space-y-4">
                    @foreach($patients as $patient)
                    <div class="border dark:border-gray-700 rounded p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-medium">{{ $patient->name }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $patient->email }}</p>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('caregiver.reminders', ['patient' => $patient->id]) }}"
                                   class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                    Gestionează memento-uri
                                </a>
                                <form method="POST" action="{{ route('caregiver.patients.remove', $patient) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600"
                                            onclick="return confirm('Sigur doriți să eliminați acest pacient?')">
                                        Elimină
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
