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
            <h2 class="font-semibold text-xl text-primary leading-tight">
                {{ __('Test Dashboard') }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Voice Recognition Test -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Voice Recognition Test</h3>

                    <div class="mt-4">
                        <div class="mb-4">
                            <h4 class="text-md font-medium text-gray-700">Available Voice Commands:</h4>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Basic Commands -->
                                <div class="space-y-2">
                                    <h5 class="font-medium text-gray-600">Basic Commands</h5>
                                    <button onclick="processVoiceCommand('list my reminders')"
                                            class="w-full text-left px-4 py-2 bg-white hover:bg-gray-50 rounded border border-gray-300 text-sm">
                                        "List my reminders"
                                    </button>
                                    <button onclick="processVoiceCommand('what\'s next')"
                                            class="w-full text-left px-4 py-2 bg-white hover:bg-gray-50 rounded border border-gray-300 text-sm">
                                        "What's next?"
                                    </button>
                                </div>

                                <!-- Time-based Commands -->
                                <div class="space-y-2">
                                    <h5 class="font-medium text-gray-600">Time-based Commands</h5>
                                    <button onclick="processVoiceCommand('show reminders for today')"
                                            class="w-full text-left px-4 py-2 bg-white hover:bg-gray-50 rounded border border-gray-300 text-sm">
                                        "Show reminders for today"
                                    </button>
                                    <button onclick="processVoiceCommand('show reminders for this week')"
                                            class="w-full text-left px-4 py-2 bg-white hover:bg-gray-50 rounded border border-gray-300 text-sm">
                                        "Show reminders for this week"
                                    </button>
                                    <button onclick="processVoiceCommand('show reminders for next week')"
                                            class="w-full text-left px-4 py-2 bg-white hover:bg-gray-50 rounded border border-gray-300 text-sm">
                                        "Show reminders for next week"
                                    </button>
                                    <button onclick="processVoiceCommand('show reminders for this month')"
                                            class="w-full text-left px-4 py-2 bg-white hover:bg-gray-50 rounded border border-gray-300 text-sm">
                                        "Show reminders for this month"
                                    </button>
                                </div>

                                <!-- Status Commands -->
                                <div class="space-y-2">
                                    <h5 class="font-medium text-gray-600">Status Commands</h5>
                                    <button onclick="processVoiceCommand('list priority reminders')"
                                            class="w-full text-left px-4 py-2 bg-white hover:bg-gray-50 rounded border border-gray-300 text-sm">
                                        "List priority reminders"
                                    </button>
                                    <button onclick="processVoiceCommand('show overdue reminders')"
                                            class="w-full text-left px-4 py-2 bg-white hover:bg-gray-50 rounded border border-gray-300 text-sm">
                                        "Show overdue reminders"
                                    </button>
                                </div>

                                <!-- Complete Reminder Section -->
                                <div class="space-y-2">
                                    <h5 class="font-medium text-gray-600">Complete a Reminder</h5>
                                    <div class="flex space-x-2">
                                        <input type="text"
                                               id="reminderNameInput"
                                               placeholder="Enter reminder name"
                                               class="flex-1 px-4 py-2 bg-white rounded border border-gray-300 text-sm">
                                        <button onclick="completeSpecificReminder()"
                                                class="btn btn-success">
                                            Complete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Voice Recording Section -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h5 class="font-medium text-gray-700 mb-4">Voice Recognition</h5>
                            <button id="recordButton"
                                    class="btn btn-primary">
                                Start Recording
                            </button>
                            <div id="recordingStatus" class="mt-2 text-gray-600"></div>
                            <div id="transcriptionResult" class="mt-4 p-4 bg-gray-50 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Reminders Test -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Reminders</h3>
                    <div class="mt-4 space-y-4">
                        @foreach($reminders as $reminder)
                        <div class="p-4 border rounded">
                            <p class="font-medium">{{ $reminder->title }}</p>
                            <p class="text-sm text-gray-600">For: {{ $reminder->user->name }}</p>
                            <p class="text-sm text-gray-600">Schedule: {{ $reminder->schedule }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                    <div class="mt-4 space-x-4">
                        <button onclick="processReminders()" class="btn btn-success">
                            Process Reminders Now
                        </button>
                        <button onclick="testNotification()" class="btn btn-primary">
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
                    <p class="text-gray-800">Command: "${command}"</p>
                    <p class="text-gray-800 mt-2">${data.message}</p>
                `;
                if (data.refresh) {
                    setTimeout(() => window.location.reload(), 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('transcriptionResult').innerHTML = `
                    <p class="text-red-600">Error processing command</p>
                `;
            });
        }

        function completeSpecificReminder() {
            const reminderName = document.getElementById('reminderNameInput').value;
            if (!reminderName) {
                alert('Please enter a reminder name');
                return;
            }

            processVoiceCommand(`complete ${reminderName}`);
        }
    </script>
</body>
</html>
