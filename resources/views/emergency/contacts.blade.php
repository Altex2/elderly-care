<x-elderly-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                Contacte de Urgență
            </h1>
            <p class="text-xl text-gray-600">
                Gestionați contactele dvs. de urgență și inițiați apeluri rapide.
            </p>
        </div>

        <!-- Emergency Contacts List -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="space-y-6">
                @if($contacts->count() > 0)
                    @foreach($contacts as $contact)
                        <div class="flex items-center justify-between p-6 bg-gray-50 rounded-lg">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ $contact->name }}</h3>
                                <p class="text-xl text-gray-600">{{ $contact->phone_number }}</p>
                                @if($contact->relationship)
                                    <p class="text-lg text-gray-500">{{ $contact->relationship }}</p>
                                @endif
                            </div>
                            <div class="flex space-x-4">
                                <form action="{{ route('emergency.call') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="contact_name" value="{{ $contact->name }}">
                                    <button type="submit" 
                                            class="px-6 py-3 bg-red-600 text-white text-xl font-bold rounded-lg hover:bg-red-700 flex items-center space-x-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span>Sună Acum</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-12">
                        <p class="text-xl text-gray-600">Nu aveți contacte de urgență configurate.</p>
                        <p class="text-lg text-gray-500 mt-2">Vă rugăm să contactați îngrijitorul dvs. pentru a configura contactele.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Emergency Instructions -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Instrucțiuni de Urgență</h2>
            <div class="space-y-4 text-lg text-gray-600">
                <p>1. Pentru a face un apel de urgență, apăsați butonul mare roșu din colțul din dreapta jos.</p>
                <p>2. Sistemul va apela automat primul contact de urgență din listă.</p>
                <p>3. Dacă primul contact nu răspunde, sistemul va încerca automat următorul contact.</p>
                <p>4. În caz de urgență, puteți apăsa butonul de urgență de mai multe ori pentru a insista.</p>
            </div>
        </div>
    </div>
</x-elderly-layout> 