<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asistent Vocal') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Recording Section -->
        <div class="card text-center">
            <button id="recordButton" 
                    class="w-32 h-32 bg-primary rounded-full flex items-center justify-center mx-auto mb-4 hover:bg-primary-hover transition-colors duration-200 bg-gray-600 hover:bg-gray-700">
                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                </svg>
                        </button>
            <p id="recordingStatus" class="text-xl text-gray-600 mb-4">
                Apăsați butonul pentru a începe înregistrarea
                        </p>
                    </div>

        <!-- Common Commands -->
        <div class="card">
            <h2 class="text-xl font-semibold text-primary mb-4">Comenzi Disponibile</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Memento-uri</h3>
                    <ul class="space-y-3 text-md text-gray-600">
                        <li class="flex items-center">
                            <span class="text-primary mr-2">•</span> "Ce am de făcut?"
                        </li>
                        <li class="flex items-center">
                            <span class="text-primary mr-2">•</span> "Ce am de făcut azi / mâine / săptămâna acesta / săptămâna următoare / luna aceasta ?"
                        </li>
                        <li class="flex items-center">
                            <span class="text-primary mr-2">•</span> "Memento nou la ora [ora] [acțiune/medicament]"
                        </li>
                        <li class="flex items-center">
                            <span class="text-primary mr-2">•</span> "Am făcut / am luat [acțiune/medicament]"
                        </li>
                    </ul>
                    </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Urgență</h3>
                    <ul class="space-y-3 text-md text-gray-600">
                        <li class="flex items-center">
                            <span class="text-danger mr-2">•</span> "SOS"
                        </li>
                        <li class="flex items-center">
                            <span class="text-danger mr-2">•</span> "Urgență"
                        </li>
                        <li class="flex items-center">
                            <span class="text-danger mr-2">•</span> "Am nevoie de ajutor"
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Response Section -->
        <div class="card">
            <h2 class="text-xl font-semibold text-primary mb-4">Răspuns Sistem</h2>
            <div id="responseText" class="text-lg text-gray-600 p-4 bg-gray-50 rounded-lg min-h-[100px] mb-4"></div>
            <audio id="responseAudio" controls class="w-full"></audio>
        </div>
    </div>

    @push('scripts')
    <script>
        let mediaRecorder;
        let audioChunks = [];
        let mediaStream = null;
        const recordButton = document.getElementById('recordButton');
        const recordingStatus = document.getElementById('recordingStatus');
        const responseText = document.getElementById('responseText');
        const responseAudio = document.getElementById('responseAudio');

        async function setupMediaRecorder() {
            try {
                mediaStream = await navigator.mediaDevices.getUserMedia({ 
                    audio: { 
                        echoCancellation: true,
                        noiseSuppression: true
                    } 
                });
                
                const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') 
                    ? 'audio/webm;codecs=opus' 
                    : 'audio/webm';
                
                mediaRecorder = new MediaRecorder(mediaStream, { mimeType });
                
                mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                };
                
                mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(audioChunks, { type: mimeType });
                    audioChunks = [];
                    await sendAudioToServer(audioBlob);
                    stopRecording();
                };
                
                mediaRecorder.start(100); // Collect data every 100ms
                updateRecordingStatus(true);
            } catch (error) {
                console.error('Error accessing microphone:', error);
                alert('Nu am putut accesa microfonul. Vă rugăm să verificați permisiunile.');
            }
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
                updateRecordingStatus(false);
                
                // Stop all tracks in the media stream
                if (mediaStream) {
                    mediaStream.getTracks().forEach(track => track.stop());
                    mediaStream = null;
                }
            }
        }

        function updateRecordingStatus(isRecording) {
            const button = document.getElementById('recordButton');
            const status = document.getElementById('recordingStatus');
            
            if (isRecording) {
                button.classList.add('bg-danger');
                button.classList.add('hover:bg-red-700');
                button.classList.remove('bg-primary');
                button.classList.remove('hover:bg-primary-hover');
                
                // Make sure the microphone icon stays visible
                const microphoneIcon = button.querySelector('svg');
                if (microphoneIcon) {
                    microphoneIcon.classList.add('text-white');
                }
                
                status.textContent = 'Înregistrare în curs...';
                status.classList.add('text-danger');
                status.classList.remove('text-gray-600');
            } else {
                button.classList.remove('bg-danger');
                button.classList.remove('hover:bg-red-700');
                button.classList.add('bg-primary');
                button.classList.add('hover:bg-primary-hover');
                
                // Make sure the microphone icon stays visible
                const microphoneIcon = button.querySelector('svg');
                if (microphoneIcon) {
                    microphoneIcon.classList.add('text-white');
                }
                
                status.textContent = 'Apăsați butonul pentru a începe înregistrarea';
                status.classList.remove('text-danger');
                status.classList.add('text-gray-600');
            }
        }

        async function sendAudioToServer(audioBlob) {
            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.webm');

            try {
                const response = await fetch('{{ route("voice.process") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server response:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (data.success) {
                    responseText.textContent = data.text;
                    if (data.audio) {
                        responseAudio.src = data.audio;
                        responseAudio.play().catch(error => {
                            console.error('Error playing audio:', error);
                        });
                    }
                    
                    // Refresh the UI if a command was executed successfully
                    if (data.refresh) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    }
                } else {
                    responseText.textContent = data.error || 'A apărut o eroare la procesarea comenzii.';
                }
            } catch (error) {
                console.error('Error sending audio:', error);
                responseText.textContent = 'A apărut o eroare la trimiterea comenzii.';
            } finally {
                recordingStatus.textContent = 'Apăsați butonul pentru a începe înregistrarea';
            }
        }

        // Clean up when leaving the page
        window.addEventListener('beforeunload', () => {
            stopRecording();
        });

        // Initialize recording setup on page load
        document.addEventListener('DOMContentLoaded', () => {
            const recordButton = document.getElementById('recordButton');
            
            // Ensure the microphone icon is visible
            const microphoneIcon = recordButton.querySelector('svg');
            if (microphoneIcon) {
                microphoneIcon.classList.add('text-white');
                microphoneIcon.style.visibility = 'visible';
            }
            
            recordButton.addEventListener('click', async () => {
                if (mediaRecorder && mediaRecorder.state === 'recording') {
                    stopRecording();
                } else {
                    await setupMediaRecorder();
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
