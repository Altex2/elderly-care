<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Asistent Vocal') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Asistent Vocal</h1>
                <p class="text-gray-600">Controlați memento-urile dvs. cu comenzi vocale sau folosind butoanele de mai jos</p>
                
                @if(auth()->user()->is_caregiver)
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-yellow-800">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <strong>Notă pentru îngrijitori:</strong> Comenzile vocale sunt disponibile momentan doar pentru pacienți. Suportul pentru comenzi vocale pentru îngrijitori va fi adăugat în curând.
                        </p>
                    </div>
                @endif
            </div>

            <!-- Voice Command Section -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="text-center">
                    <button id="startButton" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 px-8 rounded-full text-xl flex items-center justify-center mx-auto transition-colors duration-200">
                        <svg class="w-8 h-8 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        <span>Începe să vorbești</span>
                    </button>
                    <p id="status" class="mt-4 text-gray-600"></p>
                    <p id="loadingMessage" class="mt-4 text-blue-600 hidden">Vă rog să așteptați, procesez comanda dvs...</p>
                </div>

                <!-- Quick Action Buttons -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button onclick="processVoiceCommand('ce am de facut')" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg flex items-center justify-center transition-colors duration-200">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Ce am de făcut?
                    </button>
                    <button onclick="processVoiceCommand('ajutor')" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-3 px-6 rounded-lg flex items-center justify-center transition-colors duration-200">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Ajutor
                    </button>
                </div>

                <!-- Response Section -->
                <div class="mt-8">
                    <div id="transcription" class="text-gray-700 mb-4"></div>
                    <div id="response" class="text-gray-800"></div>
                    <div id="error" class="text-red-600 mt-4"></div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="text-center">
                <a href="{{ route('user.dashboard') }}" class="inline-block bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-200">
                    Înapoi la tabloul de bord
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const startButton = document.getElementById('startButton');
        const status = document.getElementById('status');
        const transcription = document.getElementById('transcription');
        const response = document.getElementById('response');
        const error = document.getElementById('error');
        const loadingMessage = document.getElementById('loadingMessage');
        
        let mediaRecorder;
        let audioChunks = [];
        let isRecording = false;
        let supportedFormats = ['audio/webm', 'audio/mp4', 'audio/ogg'];
        let selectedFormat = supportedFormats.find(format => MediaRecorder.isTypeSupported(format)) || 'audio/webm';

        // Function to process voice commands
        window.processVoiceCommand = async function(command) {
            console.log('Processing command:', command);
            try {
                loadingMessage.classList.remove('hidden');
                response.textContent = '';
                error.textContent = '';

                console.log('Sending request to /voice/process-command');
                const res = await fetch('/voice/process-command', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ text: command })
                });

                console.log('Response status:', res.status);
                console.log('Response headers:', Object.fromEntries(res.headers.entries()));

                const responseText = await res.text();
                console.log('Raw response:', responseText);

                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.log('Non-JSON response received:', responseText);
                    throw new Error('Server returned invalid JSON');
                }

                loadingMessage.classList.add('hidden');

                if (data.success) {
                    console.log('Command processed successfully:', data);
                    response.textContent = data.message;
                } else {
                    console.error('Command processing failed:', data);
                    error.textContent = data.message || 'A apărut o eroare la procesarea comenzii.';
                }
            } catch (err) {
                console.error('Error processing command:', err);
                loadingMessage.classList.add('hidden');
                error.textContent = 'Eroare la procesarea comenzii: ' + err.message;
            }
        };

        // Start recording
        startButton.addEventListener('click', async () => {
            if (isRecording) {
                stopRecording();
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream, { mimeType: selectedFormat });
                
                mediaRecorder.ondataavailable = (event) => {
                    audioChunks.push(event.data);
                };
                
                mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(audioChunks, { type: selectedFormat });
                    
                    try {
                        loadingMessage.classList.remove('hidden');
                        const formData = new FormData();
                        formData.append('audio', audioBlob);
                        
                        const res = await fetch('/voice/process-audio', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        
                        const data = await res.json();
                        loadingMessage.classList.add('hidden');
                        
                        if (data.success) {
                            response.textContent = data.message;
                            error.textContent = '';
                        } else {
                            error.textContent = data.message || 'A apărut o eroare la procesarea comenzii vocale.';
                            response.textContent = '';
                        }
                    } catch (err) {
                        loadingMessage.classList.add('hidden');
                        error.textContent = 'Eroare la procesarea audio: ' + err.message;
                        response.textContent = '';
                    }
                    
                    audioChunks = [];
                };
                
                mediaRecorder.start();
                isRecording = true;
                startButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                startButton.classList.add('bg-red-500', 'hover:bg-red-600');
                startButton.querySelector('span').textContent = 'Oprește înregistrarea';
                status.textContent = 'Înregistrare...';
                response.textContent = '';
                error.textContent = '';
            } catch (err) {
                error.textContent = 'Eroare la accesarea microfonului: ' + err.message;
            }
        });

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
                
                isRecording = false;
                startButton.classList.remove('bg-red-500', 'hover:bg-red-600');
                startButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
                startButton.querySelector('span').textContent = 'Începe să vorbești';
                status.textContent = '';
            }
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', stopRecording);
    });
    </script>
    @endpush
</x-app-layout>
