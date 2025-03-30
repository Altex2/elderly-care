import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
    setup() {
        const isListening = ref(false);
        const transcript = ref('');
        const response = ref('');
        const error = ref('');
        const recognition = ref(null);

        onMounted(() => {
            if ('webkitSpeechRecognition' in window) {
                recognition.value = new webkitSpeechRecognition();
                recognition.value.continuous = false;
                recognition.value.interimResults = false;
                recognition.value.lang = 'ro-RO';

                recognition.value.onresult = (event) => {
                    transcript.value = event.results[0][0].transcript;
                    processCommand(transcript.value);
                };

                recognition.value.onerror = (event) => {
                    error.value = 'Eroare la recunoașterea vocală: ' + event.error;
                };

                recognition.value.onend = () => {
                    isListening.value = false;
                };
            } else {
                error.value = 'Recunoașterea vocală nu este suportată în acest browser.';
            }
        });

        const startListening = () => {
            if (recognition.value) {
                isListening.value = true;
                transcript.value = '';
                response.value = '';
                error.value = '';
                recognition.value.start();
            }
        };

        const stopListening = () => {
            if (recognition.value) {
                recognition.value.stop();
                isListening.value = false;
            }
        };

        const processCommand = async (text) => {
            try {
                const response = await axios.post('/voice/process', { text });
                handleResponse(response.data);
            } catch (error) {
                handleError(error);
            }
        };

        const handleResponse = (data) => {
            response.value = data.message;
            // You can add additional handling here, such as playing a sound or showing notifications
        };

        const handleError = (error) => {
            response.value = 'A apărut o eroare la procesarea comenzii. Vă rugăm să încercați din nou.';
            console.error('Error processing voice command:', error);
        };

        return {
            isListening,
            transcript,
            response,
            error,
            startListening,
            stopListening
        };
    },
    template: `
        <div class="voice-command-container p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Comandă vocală
                </h3>
                <button
                    @click="isListening ? stopListening() : startListening()"
                    :class="[
                        'px-4 py-2 rounded-md text-white transition-colors',
                        isListening ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'
                    ]"
                >
                    {{ isListening ? 'Oprește' : 'Începe' }}
                </button>
            </div>

            <div v-if="transcript" class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Ați spus:
                </p>
                <p class="text-gray-900 dark:text-gray-100">
                    {{ transcript }}
                </p>
            </div>

            <div v-if="response" class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Răspuns:
                </p>
                <p class="text-gray-900 dark:text-gray-100">
                    {{ response }}
                </p>
            </div>

            <div v-if="error" class="text-red-600 dark:text-red-400 text-sm">
                {{ error }}
            </div>

            <div v-if="isListening" class="mt-4">
                <div class="flex items-center justify-center">
                    <div class="animate-pulse text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    `
}; 