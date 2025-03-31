<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Asistent Vocal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                            Asistent Vocal
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Puteți să-mi spuneți următoarele:
                        </p>
                        <ul class="mt-4 text-left text-gray-600 dark:text-gray-400 space-y-2">
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                "Ce am de făcut" - pentru a vedea sarcinile dvs.
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                "Am făcut" urmat de numele sarcinii - pentru a o marca ca fiind completată
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                "Ajutor" - pentru a vedea această listă din nou
                            </li>
                        </ul>
                    </div>

                    <!-- Voice Command Interface -->
                    <div class="max-w-md mx-auto">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border border-blue-200 dark:border-blue-800">
                            <button id="voiceButton" 
                                    class="w-full px-8 py-4 rounded-full focus:outline-none focus:ring-4 focus:ring-offset-2 transition-all duration-200 text-lg font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-lg hover:shadow-xl">
                                <div class="flex items-center justify-center space-x-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                    </svg>
                                    <span>Începe să vorbești</span>
                                </div>
                            </button>

                            <!-- Transcription Display -->
                            <div id="transcriptionBox" class="mt-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg text-center hidden">
                                <div class="flex items-center justify-center space-x-2">
                                    <div class="animate-pulse">
                                        <div class="w-3 h-3 bg-blue-600 dark:bg-blue-400 rounded-full"></div>
                                    </div>
                                    <p class="text-blue-800 dark:text-blue-200">
                                        Ascult... <span id="transcript" class="italic"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Response Display -->
                            <div id="responseBox" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hidden">
                                <p id="response" class="text-gray-800 dark:text-gray-200"></p>
                            </div>

                            <!-- Error Display -->
                            <div id="errorBox" class="mt-4 p-4 bg-red-50 dark:bg-red-900 rounded-lg hidden">
                                <p id="error" class="text-red-800 dark:text-red-200"></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 text-center">
                        <a href="{{ route('user.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Înapoi la tabloul de bord
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const voiceButton = document.getElementById('voiceButton');
            const transcriptionBox = document.getElementById('transcriptionBox');
            const responseBox = document.getElementById('responseBox');
            const errorBox = document.getElementById('errorBox');
            const transcriptText = document.getElementById('transcript');
            const responseText = document.getElementById('response');
            const errorText = document.getElementById('error');
            
            let mediaRecorder;
            let audioChunks = [];
            let isRecording = false;
            let supportedFormats = ['audio/webm', 'audio/mp4', 'audio/ogg'];
            let selectedFormat = supportedFormats.find(format => MediaRecorder.isTypeSupported(format)) || 'audio/webm';
            console.log('Using audio format:', selectedFormat);

            // Function to convert audio to MP3
            async function convertToMp3(audioBlob) {
                try {
                    // Create an audio context
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    
                    // Convert blob to array buffer
                    const arrayBuffer = await audioBlob.arrayBuffer();
                    
                    // Decode the audio data
                    const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
                    
                    // Create an offline audio context
                    const offlineContext = new OfflineAudioContext(
                        audioBuffer.numberOfChannels,
                        audioBuffer.length,
                        audioBuffer.sampleRate
                    );
                    
                    // Create a buffer source
                    const source = offlineContext.createBufferSource();
                    source.buffer = audioBuffer;
                    
                    // Connect the source to the destination
                    source.connect(offlineContext.destination);
                    
                    // Start the source
                    source.start();
                    
                    // Render the audio
                    const renderedBuffer = await offlineContext.startRendering();
                    
                    // Convert to WAV format
                    const wavBlob = await audioBufferToWav(renderedBuffer);
                    
                    return wavBlob;
                } catch (error) {
                    console.error('Error converting audio:', error);
                    throw error;
                }
            }

            // Function to convert AudioBuffer to WAV
            function audioBufferToWav(buffer) {
                const numChannels = buffer.numberOfChannels;
                const sampleRate = buffer.sampleRate;
                const format = 1; // PCM
                const bitDepth = 16;
                
                const bytesPerSample = bitDepth / 8;
                const blockAlign = numChannels * bytesPerSample;
                
                const wav = new ArrayBuffer(44 + buffer.length * blockAlign);
                const view = new DataView(wav);
                
                // Write WAV header
                writeString(view, 0, 'RIFF');
                view.setUint32(4, 36 + buffer.length * blockAlign, true);
                writeString(view, 8, 'WAVE');
                writeString(view, 12, 'fmt ');
                view.setUint32(16, 16, true);
                view.setUint16(20, format, true);
                view.setUint16(22, numChannels, true);
                view.setUint32(24, sampleRate, true);
                view.setUint32(28, sampleRate * blockAlign, true);
                view.setUint16(32, blockAlign, true);
                view.setUint16(34, bitDepth, true);
                writeString(view, 36, 'data');
                view.setUint32(40, buffer.length * blockAlign, true);
                
                // Write audio data
                const data = new Float32Array(buffer.length);
                const channelData = buffer.getChannelData(0);
                for (let i = 0; i < buffer.length; i++) {
                    data[i] = channelData[i];
                }
                
                let offset = 44;
                for (let i = 0; i < data.length; i++) {
                    const sample = Math.max(-1, Math.min(1, data[i]));
                    view.setInt16(offset, sample < 0 ? sample * 0x8000 : sample * 0x7FFF, true);
                    offset += 2;
                }
                
                return new Blob([wav], { type: 'audio/wav' });
            }

            function writeString(view, offset, string) {
                for (let i = 0; i < string.length; i++) {
                    view.setUint8(offset + i, string.charCodeAt(i));
                }
            }

            // Start recording
            voiceButton.addEventListener('click', async () => {
                if (isRecording) {
                    cleanup();
                    return;
                }

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream, {
                        mimeType: selectedFormat
                    });
                    
                    mediaRecorder.ondataavailable = (event) => {
                        audioChunks.push(event.data);
                    };
                    
                    mediaRecorder.onstop = async () => {
                        const audioBlob = new Blob(audioChunks, { type: selectedFormat });
                        console.log('Audio blob created:', { type: selectedFormat, size: audioBlob.size });
                        
                        try {
                            // Convert to WAV format
                            const wavBlob = await convertToMp3(audioBlob);
                            console.log('Converted to WAV:', { type: wavBlob.type, size: wavBlob.size });
                            
                            // Create form data
                            const formData = new FormData();
                            formData.append('audio', wavBlob, 'audio.wav');
                            
                            // Add timezone offset
                            const timezoneOffset = new Date().getTimezoneOffset();
                            formData.append('timezone_offset', timezoneOffset);
                            
                            // Show loading state
                            transcriptionBox.classList.add('hidden');
                            responseBox.classList.add('hidden');
                            errorBox.classList.add('hidden');
                            
                            // Send to server
                            const response = await fetch('/voice/process-audio', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                responseText.textContent = data.message;
                                responseBox.classList.remove('hidden');
                                errorBox.classList.add('hidden');
                            } else {
                                errorText.textContent = data.message;
                                errorBox.classList.remove('hidden');
                                responseBox.classList.add('hidden');
                            }
                        } catch (error) {
                            console.error('Error processing audio:', error);
                            errorText.textContent = 'Eroare la procesarea audio: ' + error.message;
                            errorBox.classList.remove('hidden');
                            responseBox.classList.add('hidden');
                        }
                        
                        audioChunks = [];
                    };
                    
                    mediaRecorder.start();
                    isRecording = true;
                    voiceButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    voiceButton.classList.add('bg-red-600', 'hover:bg-red-700');
                    voiceButton.querySelector('span').textContent = 'Oprește înregistrarea';
                    transcriptionBox.classList.remove('hidden');
                    responseBox.classList.add('hidden');
                    errorBox.classList.add('hidden');
                    transcriptText.textContent = 'Înregistrare...';
                } catch (error) {
                    console.error('Error accessing microphone:', error);
                    errorText.textContent = 'Eroare la accesarea microfonului: ' + error.message;
                    errorBox.classList.remove('hidden');
                    responseBox.classList.add('hidden');
                }
            });

            // Cleanup function to ensure microphone is stopped
            function cleanup() {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    isRecording = false;
                    voiceButton.classList.remove('bg-red-600', 'hover:bg-red-700');
                    voiceButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    voiceButton.querySelector('span').textContent = 'Începe să vorbești';
                    mediaRecorder.stop();
                    
                    // Stop all tracks in the stream
                    if (mediaRecorder.stream) {
                        mediaRecorder.stream.getTracks().forEach(track => track.stop());
                    }
                }
            }

            // Add cleanup on page unload
            window.addEventListener('beforeunload', cleanup);

            // Add cleanup when clicking outside the button
            document.addEventListener('click', (event) => {
                if (isRecording && !voiceButton.contains(event.target)) {
                    cleanup();
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
