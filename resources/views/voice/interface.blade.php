<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Voice Assistant') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <!-- Browser Compatibility Alert -->
                    <div id="browserAlert" class="hidden mb-4 p-4 rounded-lg"></div>

                    <!-- Microphone Test Section -->
                    <div class="mb-8 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Microphone Test</h3>
                        <div class="flex items-center space-x-4">
                            <button id="testMicButton"
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Test Microphone
                            </button>
                            <div id="micVolume" class="flex-1 h-4 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                <div id="volumeBar" class="h-full w-0 bg-green-500 transition-all duration-75"></div>
                            </div>
                            <span id="micStatus" class="text-sm text-gray-600 dark:text-gray-400">Not tested</span>
                        </div>
                        <div id="microphoneList" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Microphone:
                            </label>
                            <select id="micSelect"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Loading microphones...</option>
                            </select>
                        </div>
                    </div>

                    <div class="text-center mb-8">
                        <button id="startVoice"
                                class="px-6 py-3 bg-blue-600 text-white rounded-full hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                            <div class="flex items-center space-x-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                                <span id="buttonText">Start Voice Assistant</span>
                            </div>
                        </button>
                    </div>

                    <!-- Live Transcription -->
                    <div id="liveTranscription"
                         class="mb-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg text-center hidden">
                        <div class="flex items-center justify-center space-x-2">
                            <div class="animate-pulse">
                                <div class="w-3 h-3 bg-blue-600 dark:bg-blue-400 rounded-full"></div>
                            </div>
                            <p class="text-blue-800 dark:text-blue-200">
                                Listening... <span id="transcriptText" class="italic"></span>
                            </p>
                        </div>
                    </div>

                    <div id="voiceOutput" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg min-h-[100px]">
                        <p class="text-gray-600 dark:text-gray-300">
                            Try saying:
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>"List my reminders"</li>
                                <li>"What's next?"</li>
                                <li>"Complete [reminder name]"</li>
                            </ul>
                        </p>
                    </div>

                    <!-- Add this right after the voiceOutput div -->
                    <div class="flex items-center justify-end mt-2">
                        <button id="toggleSpeech"
                                class="flex items-center space-x-2 px-3 py-1 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                            <svg id="speechIcon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 3.5a.5.5 0 0 0-.5-.5c-1.93 0-3.5 1.57-3.5 3.5v4a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5v-4c0-1.93-1.57-3.5-3.5-3.5a.5.5 0 0 0-.5.5z"/>
                                <path d="M10 12.5a.5.5 0 0 1 .5.5v1.5a.5.5 0 1 1-1 0v-1.5a.5.5 0 0 1 .5-.5z"/>
                            </svg>
                            <span id="speechStatus">Voice Feedback: On</span>
                        </button>
                    </div>

                    <!-- Current Reminders -->
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Today's Reminders</h3>
                        <div class="space-y-4">
                            @forelse($reminders as $reminder)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $reminder->title }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $reminder->next_occurrence->format('g:i A') }}
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 text-sm rounded-full {{ $reminder->completed
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' }}">
                                        {{ $reminder->completed ? 'Completed' : 'Pending' }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-gray-600 dark:text-gray-400">No reminders for today.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let recognition;
        const startBtn = document.getElementById('startVoice');
        const buttonText = document.getElementById('buttonText');
        const output = document.getElementById('voiceOutput');
        const liveTranscription = document.getElementById('liveTranscription');
        const transcriptText = document.getElementById('transcriptText');
        let isListening = false;
        let speechEnabled = true;
        const synth = window.speechSynthesis;
        let currentUtterance = null;

        // Browser Compatibility Check
        function checkBrowserCompatibility() {
            const browserAlert = document.getElementById('browserAlert');
            const issues = [];

            // Check for basic requirements
            if (!window.webkitSpeechRecognition && !window.SpeechRecognition) {
                issues.push('Speech recognition is not supported');
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                issues.push('Microphone access is not supported');
            }

            // Check browser
            const userAgent = navigator.userAgent;
            const isChrome = /Chrome/.test(userAgent) && /Google Inc/.test(navigator.vendor);
            const isEdge = /Edg/.test(userAgent);

            if (!isChrome && !isEdge) {
                issues.push('For best results, use Chrome or Edge browser');
            }

            // Display alerts if there are issues
            if (issues.length > 0) {
                browserAlert.classList.remove('hidden', 'bg-green-50', 'text-green-800');
                browserAlert.classList.add('bg-yellow-50', 'text-yellow-800', 'dark:bg-yellow-900', 'dark:text-yellow-100');
                browserAlert.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium">Compatibility Issues:</h3>
                            <ul class="mt-2 text-sm list-disc list-inside">
                                ${issues.map(issue => `<li>${issue}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                `;
            } else {
                browserAlert.classList.remove('hidden', 'bg-yellow-50', 'text-yellow-800');
                browserAlert.classList.add('bg-green-50', 'text-green-800', 'dark:bg-green-900', 'dark:text-green-100');
                browserAlert.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">Your browser is fully compatible with voice recognition.</p>
                        </div>
                    </div>
                `;
            }
        }

        // Microphone Testing
        let audioContext;
        let microphone;
        let volumeProcessor;
        let isTesting = false;
        const testMicButton = document.getElementById('testMicButton');
        const volumeBar = document.getElementById('volumeBar');
        const micStatus = document.getElementById('micStatus');
        const micSelect = document.getElementById('micSelect');

        async function loadMicrophones() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                const microphones = devices.filter(device => device.kind === 'audioinput');

                micSelect.innerHTML = microphones.map(mic =>
                    `<option value="${mic.deviceId}">${mic.label || `Microphone ${mic.deviceId.slice(0, 5)}`}</option>`
                ).join('');
            } catch (error) {
                console.error('Error loading microphones:', error);
                micSelect.innerHTML = '<option value="">No microphones found</option>';
            }
        }

        async function testMicrophone() {
            try {
                if (isTesting) {
                    stopMicrophoneTest();
                    return;
                }

                testMicButton.textContent = 'Stop Test';
                testMicButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                testMicButton.classList.add('bg-red-600', 'hover:bg-red-700');
                isTesting = true;

                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: { deviceId: micSelect.value ? { exact: micSelect.value } : undefined }
                });

                microphone = audioContext.createMediaStreamSource(stream);
                volumeProcessor = audioContext.createScriptProcessor(2048, 1, 1);

                microphone.connect(volumeProcessor);
                volumeProcessor.connect(audioContext.destination);

                volumeProcessor.onaudioprocess = function(event) {
                    const input = event.inputBuffer.getChannelData(0);
                    let sum = 0;
                    for (let i = 0; i < input.length; i++) {
                        sum += input[i] * input[i];
                    }
                    const volume = Math.sqrt(sum / input.length) * 100;
                    updateVolumeBar(volume * 500); // Amplify for better visibility
                };

                micStatus.textContent = 'Microphone active';
            } catch (error) {
                console.error('Microphone test error:', error);
                micStatus.textContent = 'Error: ' + error.message;
                stopMicrophoneTest();
            }
        }

        function stopMicrophoneTest() {
            if (volumeProcessor) {
                volumeProcessor.disconnect();
                microphone.disconnect();
                volumeProcessor = null;
                microphone = null;
            }
            if (audioContext) {
                audioContext.close();
                audioContext = null;
            }

            testMicButton.textContent = 'Test Microphone';
            testMicButton.classList.remove('bg-red-600', 'hover:bg-red-700');
            testMicButton.classList.add('bg-green-600', 'hover:bg-green-700');
            isTesting = false;
            volumeBar.style.width = '0%';
            micStatus.textContent = 'Test stopped';
        }

        function updateVolumeBar(volume) {
            const clampedVolume = Math.min(Math.max(volume, 0), 100);
            volumeBar.style.width = clampedVolume + '%';

            // Update color based on volume
            if (clampedVolume > 75) {
                volumeBar.classList.remove('bg-green-500', 'bg-yellow-500');
                volumeBar.classList.add('bg-red-500');
            } else if (clampedVolume > 30) {
                volumeBar.classList.remove('bg-green-500', 'bg-red-500');
                volumeBar.classList.add('bg-yellow-500');
            } else {
                volumeBar.classList.remove('bg-yellow-500', 'bg-red-500');
                volumeBar.classList.add('bg-green-500');
            }
        }

        // Event Listeners
        testMicButton.addEventListener('click', testMicrophone);
        micSelect.addEventListener('change', () => {
            if (isTesting) {
                stopMicrophoneTest();
                testMicrophone();
            }
        });

        // Initialize
        checkBrowserCompatibility();
        loadMicrophones();

        async function initializeSpeechRecognition() {
            try {
                if ('webkitSpeechRecognition' in window) {
                    recognition = new webkitSpeechRecognition();
                    recognition.continuous = false; // Changed to false for more reliable local testing
                    recognition.interimResults = true;
                    recognition.lang = 'en-US';

                    // Add these settings for better local testing
                    recognition.maxAlternatives = 1;
                    recognition.timeout = 5000;

                    recognition.onstart = function() {
                        console.log('Recognition started');
                        isListening = true;
                        startBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                        startBtn.classList.add('bg-red-600', 'hover:bg-red-700');
                        buttonText.textContent = 'Stop Listening';
                        liveTranscription.classList.remove('hidden');
                        transcriptText.textContent = '';
                        updateOutput('Listening... Speak your command');
                    };

                    recognition.onend = function() {
                        console.log('Recognition ended');
                        isListening = false;
                        startBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
                        startBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                        buttonText.textContent = 'Start Voice Assistant';
                        liveTranscription.classList.add('hidden');
                    };

                    recognition.onresult = function(event) {
                        console.log('Got result:', event);
                        let interimTranscript = '';
                        let finalTranscript = '';

                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const transcript = event.results[i][0].transcript;
                            if (event.results[i].isFinal) {
                                finalTranscript += transcript;
                                processVoiceCommand(transcript);
                            } else {
                                interimTranscript += transcript;
                            }
                        }

                        // Show live transcription
                        transcriptText.textContent = interimTranscript || finalTranscript;
                    };

                    recognition.onerror = function(event) {
                        console.error('Speech recognition error:', event.error);
                        if (event.error === 'network' || event.error === 'not-allowed') {
                            showTestingMode();
                        } else {
                            updateOutput(`Error: ${event.error}. Please make sure your microphone is connected and you've granted permission.`);
                        }
                        stopListening();
                    };

                    startBtn.disabled = false;
                    updateOutput('Voice recognition ready. Click the button to start.');
                } else {
                    throw new Error('Speech recognition not supported');
                }
            } catch (error) {
                console.error('Initialization error:', error);
                showTestingMode();
            }
        }

        function showTestingMode() {
            const testCommands = {
                'Basic Commands': [
                    'List my reminders',
                    'What\'s next?',
                    'Show my reminders'
                ],
                'Time-based Commands': [
                    'Show reminders for today',
                    'Show reminders for tomorrow',
                    'Show reminders for this week',
                    'Show reminders for next week',
                    'Show reminders for this month'
                ],
                'Status Commands': [
                    'List priority reminders',
                    'Show overdue reminders',
                    'Show missed reminders'
                ]
            };

            let testingDiv = document.createElement('div');
            testingDiv.className = 'mt-4 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg';
            testingDiv.innerHTML = `
                <h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-4">Testing Mode (Voice Recognition Unavailable)</h4>
            `;

            // Add command categories
            for (const [category, commands] of Object.entries(testCommands)) {
                testingDiv.innerHTML += `
                    <div class="mb-4">
                        <h5 class="text-sm font-medium text-yellow-700 dark:text-yellow-300 mb-2">${category}</h5>
                        <div class="space-y-2">
                            ${commands.map(cmd => `
                                <button
                                    onclick="processVoiceCommand('${cmd.replace(/'/g, "\\'")}')"
                                    class="block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded border border-yellow-300 dark:border-yellow-700 text-sm">
                                    Test: "${cmd}"
                                </button>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            // Add complete reminder input
            testingDiv.innerHTML += `
                <div class="mt-6">
                    <h5 class="text-sm font-medium text-yellow-700 dark:text-yellow-300 mb-2">Complete a Specific Reminder:</h5>
                    <div class="flex space-x-2">
                        <input type="text"
                               id="customReminderInput"
                               placeholder="Enter reminder name"
                               class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 rounded border border-yellow-300 dark:border-yellow-700 text-sm">
                        <button onclick="completeCustomReminder()"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                            Complete
                        </button>
                    </div>
                </div>
            `;

            // Insert testing div after voice output
            document.getElementById('voiceOutput').after(testingDiv);

            // Update the status with speech
            updateOutput('Voice recognition is not available. Running in test mode instead.');
            speak('Voice recognition is not available. Running in test mode instead.');

            // Disable the main voice button
            startBtn.disabled = true;
            startBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }

        // Add this function for custom reminder completion
        function completeCustomReminder() {
            const reminderInput = document.getElementById('customReminderInput');
            const reminderName = reminderInput.value.trim();

            if (reminderName) {
                processVoiceCommand(`complete ${reminderName}`);
                reminderInput.value = ''; // Clear the input
            } else {
                document.getElementById('voiceOutput').innerHTML = `
                    <p class="text-gray-600 dark:text-gray-400">Please enter a reminder name first.</p>
                `;
            }
        }

        // Initialize on page load
        initializeSpeechRecognition();

        startBtn.addEventListener('click', async function() {
            if (!recognition) {
                await initializeSpeechRecognition();
                if (!recognition) return;
            }

            if (isListening) {
                stopListening();
            } else {
                startListening();
            }
        });

        function startListening() {
            try {
                console.log('Starting recognition...');
                recognition.start();
            } catch (error) {
                console.error('Error starting recognition:', error);
                updateOutput(`Error starting voice recognition: ${error.message}. Try refreshing the page.`);
            }
        }

        function stopListening() {
            try {
                console.log('Stopping recognition...');
                recognition.stop();
                updateOutput('Voice recognition stopped');
            } catch (error) {
                console.error('Error stopping recognition:', error);
            }
        }

        // Add this function to get timezone offset
        function getTimezoneOffset() {
            // Get minutes between UTC and local time, multiply by -1 because
            // JS returns opposite of what we need
            return new Date().getTimezoneOffset() * -1;
        }

        function processVoiceCommand(command) {
            console.log('Processing command:', command);
            updateOutput('Processing: "' + command + '"');

            fetch('{{ route("voice.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    command: command,
                    timezone_offset: getTimezoneOffset() // Add timezone offset
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Command response:', data);
                updateOutput(data.message);
                if (data.success && data.refresh) {
                    speak("Updating your reminders...");
                    setTimeout(() => window.location.reload(), 2500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                updateOutput('Sorry, there was an error processing your command.');
            });
        }

        function updateOutput(message) {
            console.log('Output:', message);
            output.innerHTML = `<p class="text-gray-800 dark:text-gray-200">${message}</p>`;
            speak(message); // Add speech synthesis
        }

        // Speech synthesis configuration
        function speak(text) {
            if (!speechEnabled) return;

            // Stop any ongoing speech
            if (currentUtterance) {
                synth.cancel();
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 0.9; // Slightly slower
            utterance.pitch = 1;
            utterance.volume = 1;

            // Get available voices and select an English one
            let voices = synth.getVoices();
            const englishVoices = voices.filter(voice => voice.lang.startsWith('en-'));
            if (englishVoices.length > 0) {
                utterance.voice = englishVoices[0];
            }

            currentUtterance = utterance;
            synth.speak(utterance);
        }

        // Toggle speech feedback
        const toggleSpeech = document.getElementById('toggleSpeech');
        const speechStatus = document.getElementById('speechStatus');

        toggleSpeech.addEventListener('click', () => {
            speechEnabled = !speechEnabled;
            speechStatus.textContent = `Voice Feedback: ${speechEnabled ? 'On' : 'Off'}`;
            if (!speechEnabled && currentUtterance) {
                synth.cancel();
            }
        });

        // Handle voice selection when voices are loaded
        synth.onvoiceschanged = () => {
            const voices = synth.getVoices();
            console.log('Available voices:', voices);
        };
    </script>
    @endpush
</x-app-layout>
