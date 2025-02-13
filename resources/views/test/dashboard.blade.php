<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Test Dashboard') }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Voice Recognition Test -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Voice Recognition Test</h3>

                    <div class="mt-4">
                        <div class="mb-4">
                            <h4 class="text-md font-medium text-gray-700 dark:text-gray-300">Available Voice Commands:</h4>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Basic Commands -->
                                <div class="space-y-2">
                                    <h5 class="font-medium text-gray-600 dark:text-gray-400">Basic Commands</h5>
                                    <button onclick="processVoiceCommand('list my reminders')"
                                            class="w-full text-left px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        "List my reminders"
                                    </button>
                                    <button onclick="processVoiceCommand('what\'s next')"
                                            class="w-full text-left px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        "What's next?"
                                    </button>
                                </div>

                                <!-- Time-based Commands -->
                                <div class="space-y-2">
                                    <h5 class="font-medium text-gray-600 dark:text-gray-400">Time-based Commands</h5>
                                    <button onclick="processVoiceCommand('show reminders for today')"
                                            class="w-full text-left px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        "Show reminders for today"
                                    </button>
                                    <button onclick="processVoiceCommand('show reminders for this week')"
                                            class="w-full text-left px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        "Show reminders for this week"
                                    </button>
                                    <button onclick="processVoiceCommand('show reminders for next week')"
                                            class="w-full text-left px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        "Show reminders for next week"
                                    </button>
                                    <button onclick="processVoiceCommand('show reminders for this month')"
                                            class="w-full text-left px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        "Show reminders for this month"
                                    </button>
                                </div>

                                <!-- Status Commands -->
                                <div class="space-y-2">
                                    <h5 class="font-medium text-gray-600 dark:text-gray-400">Status Commands</h5>
                                    <button onclick="processVoiceCommand('list priority reminders')"
                                            class="w-full text-left px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        "List priority reminders"
                                    </button>
                                    <button onclick="processVoiceCommand('show overdue reminders')"
                                            class="w-full text-left px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        "Show overdue reminders"
                                    </button>
                                </div>

                                <!-- Complete Reminder Section -->
                                <div class="space-y-2">
                                    <h5 class="font-medium text-gray-600 dark:text-gray-400">Complete a Reminder</h5>
                                    <div class="flex space-x-2">
                                        <input type="text"
                                               id="reminderNameInput"
                                               placeholder="Enter reminder name"
                                               class="flex-1 px-4 py-2 bg-white dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600 text-sm">
                                        <button onclick="completeSpecificReminder()"
                                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                                            Complete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Voice Recording Section -->
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-4">Voice Recognition</h5>
                            <button id="recordButton"
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Start Recording
                            </button>
                            <div id="recordingStatus" class="mt-2 text-gray-600 dark:text-gray-400"></div>
                            <div id="transcriptionResult" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Reminders Test -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Reminders</h3>
                    <div class="mt-4 space-y-4">
                        @foreach($reminders as $reminder)
                        <div class="p-4 border dark:border-gray-700 rounded">
                            <p class="font-medium">{{ $reminder->title }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">For: {{ $reminder->user->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Schedule: {{ $reminder->schedule }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Quick Actions</h3>
                    <div class="mt-4 space-x-4">
                        <button onclick="processReminders()" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                            Process Reminders Now
                        </button>
                        <button onclick="testNotification()" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                            Test Push Notification
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>

    <script>
        let mediaRecorder;
        let audioChunks = [];

        document.getElementById('recordButton').addEventListener('click', async () => {
            const button = document.getElementById('recordButton');
            const status = document.getElementById('recordingStatus');

            if (button.textContent === 'Start Recording') {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);

                    mediaRecorder.ondataavailable = (event) => {
                        audioChunks.push(event.data);
                    };

                    mediaRecorder.onstop = async () => {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                        const formData = new FormData();
                        formData.append('audio', audioBlob);

                        try {
                            const response = await fetch('/test/voice', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            });

                            const data = await response.json();
                            document.getElementById('transcriptionResult').innerHTML = `
                                <p class="font-medium">Transcription:</p>
                                <p class="mb-2">${data.transcription}</p>
                                <p class="font-medium">Intent:</p>
                                <pre class="text-sm">${JSON.stringify(data.intent, null, 2)}</pre>
                            `;
                        } catch (error) {
                            console.error('Error:', error);
                            status.textContent = 'Error processing audio';
                        }

                        audioChunks = [];
                        button.textContent = 'Start Recording';
                        status.textContent = '';
                    };

                    mediaRecorder.start();
                    button.textContent = 'Stop Recording';
                    status.textContent = 'Recording...';
                } catch (error) {
                    console.error('Error:', error);
                    status.textContent = 'Error accessing microphone';
                }
            } else {
                mediaRecorder.stop();
                button.textContent = 'Processing...';
            }
        });

        async function processReminders() {
            try {
                const response = await fetch('/artisan/reminders:process', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                alert('Reminders processed successfully');
            } catch (error) {
                console.error('Error:', error);
                alert('Error processing reminders');
            }
        }

        async function testNotification() {
            try {
                const response = await fetch('/test/notification', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                alert('Test notification sent');
            } catch (error) {
                console.error('Error:', error);
                alert('Error sending notification');
            }
        }

        function processVoiceCommand(command) {
            fetch('/voice/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ command: command })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('transcriptionResult').innerHTML = `
                    <p class="text-gray-800 dark:text-gray-200">Command: "${command}"</p>
                    <p class="text-gray-800 dark:text-gray-200 mt-2">${data.message}</p>
                `;
                if (data.refresh) {
                    setTimeout(() => window.location.reload(), 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('transcriptionResult').innerHTML = `
                    <p class="text-red-600 dark:text-red-400">Error processing command</p>
                `;
            });
        }

        function completeSpecificReminder() {
            const reminderName = document.getElementById('reminderNameInput').value.trim();
            if (reminderName) {
                processVoiceCommand(`complete ${reminderName}`);
                document.getElementById('reminderNameInput').value = '';
            } else {
                document.getElementById('transcriptionResult').innerHTML = `
                    <p class="text-yellow-600 dark:text-yellow-400">Please enter a reminder name</p>
                `;
            }
        }
    </script>
</body>
</html>
