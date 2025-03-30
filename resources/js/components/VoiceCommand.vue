<template>
    <div class="voice-command-container">
        <div class="text-center mb-8">
            <button @click="toggleListening" 
                    :class="['px-6 py-3 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200',
                            isListening ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700']"
                    class="text-white">
                <div class="flex items-center space-x-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <span>{{ isListening ? 'Stop Listening' : 'Start Voice Assistant' }}</span>
                </div>
            </button>
        </div>

        <!-- Live Transcription -->
        <div v-if="isListening" class="mb-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg text-center">
            <div class="flex items-center justify-center space-x-2">
                <div class="animate-pulse">
                    <div class="w-3 h-3 bg-blue-600 dark:bg-blue-400 rounded-full"></div>
                </div>
                <p class="text-blue-800 dark:text-blue-200">
                    Listening... <span class="italic">{{ transcript }}</span>
                </p>
            </div>
        </div>

        <!-- Response -->
        <div v-if="response" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-gray-800 dark:text-gray-200">{{ response }}</p>
        </div>

        <!-- Error -->
        <div v-if="error" class="mt-6 p-4 bg-red-50 dark:bg-red-900 rounded-lg">
            <p class="text-red-800 dark:text-red-200">{{ error }}</p>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'VoiceCommand',
    setup() {
        const isListening = ref(false);
        const transcript = ref('');
        const response = ref('');
        const error = ref('');
        let recognition = null;

        onMounted(() => {
            if ('webkitSpeechRecognition' in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = true;
                recognition.lang = 'ro-RO';

                recognition.onresult = (event) => {
                    let interimTranscript = '';
                    let finalTranscript = '';

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            finalTranscript += transcript;
                            processCommand(transcript);
                        } else {
                            interimTranscript += transcript;
                        }
                    }

                    transcript.value = interimTranscript || finalTranscript;
                };

                recognition.onerror = (event) => {
                    error.value = `Error: ${event.error}`;
                    stopListening();
                };

                recognition.onend = () => {
                    stopListening();
                };
            } else {
                error.value = 'Speech recognition is not supported in your browser.';
            }
        });

        const toggleListening = () => {
            if (isListening.value) {
                stopListening();
            } else {
                startListening();
            }
        };

        const startListening = () => {
            if (recognition) {
                isListening.value = true;
                transcript.value = '';
                response.value = '';
                error.value = '';
                recognition.start();
            }
        };

        const stopListening = () => {
            if (recognition) {
                isListening.value = false;
                recognition.stop();
            }
        };

        const processCommand = async (command) => {
            try {
                const response = await axios.post('/voice/process', {
                    command: command,
                    timezone_offset: new Date().getTimezoneOffset() * -1
                });

                if (response.data.success) {
                    response.value = response.data.message;
                } else {
                    error.value = response.data.message;
                }
            } catch (error) {
                error.value = 'Error processing command: ' + error.message;
            }
        };

        return {
            isListening,
            transcript,
            response,
            error,
            toggleListening
        };
    }
};
</script> 