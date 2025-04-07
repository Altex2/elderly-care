<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-primary">
            {{ __('Gestionare pacienți') }}
        </h1>
    </x-slot>

    <div class="space-y-6">
        <!-- Add Patient Form -->
        <div class="card">
            <h2 class="text-xl font-semibold text-primary mb-4">Adaugă pacient nou</h2>
            <form method="POST" action="{{ route('caregiver.patients.add') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-md font-medium text-gray-700 mb-2">Email pacient</label>
                    <input id="email" name="email" type="email" required>
                    <p class="mt-2 text-md text-gray-600">
                        Introduceți emailul unui pacient înregistrat pentru a-l adăuga în lista dvs. de îngrijire.
                    </p>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Adaugă pacient</button>
            </form>
        </div>

        <!-- Current Patients -->
        <div class="card">
            <h2 class="text-xl font-semibold text-primary mb-4">Pacienții dvs.</h2>
            <div class="space-y-4">
                @foreach($patients as $patient)
                <div class="border rounded-lg p-4 bg-white">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $patient->name }}</h3>
                            <p class="text-md text-gray-600">{{ $patient->email }}</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="{{ route('caregiver.reminders', ['patient' => $patient->id]) }}"
                               class="btn btn-primary flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Gestionează memento-uri
                            </a>
                            <form method="POST" action="{{ route('caregiver.patients.remove', $patient) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn bg-danger text-white hover:bg-red-700 flex items-center justify-center"
                                        onclick="return confirm('Sigur doriți să eliminați acest pacient?')">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
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
</x-app-layout>
