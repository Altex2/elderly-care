<div class="voice-command-section">
    <div class="voice-command-container">
        <div class="voice-command-header">
            <h2>Comandă Vocală</h2>
            <div class="voice-command-status">
                <span id="voiceStatus">Gata</span>
                <div id="voiceLoading" class="loading-indicator" style="display: none;">
                    <div class="loading-spinner"></div>
                    <span>Se procesează comanda...</span>
                </div>
            </div>
        </div>
        <div class="voice-command-controls">
            <button id="startRecording" class="voice-button">
                <i class="fas fa-microphone"></i>
                <span>Începe înregistrarea</span>
            </button>
            <button id="stopRecording" class="voice-button" style="display: none;">
                <i class="fas fa-stop"></i>
                <span>Oprește înregistrarea</span>
            </button>
        </div>
        <div class="voice-command-response">
            <div class="response-header">
                <h3>Răspuns Sistem</h3>
            </div>
            <div id="systemResponse" class="response-content">
                <!-- Response will be displayed here -->
            </div>
        </div>
    </div>
</div>

<style>
.voice-command-status {
    display: flex;
    align-items: center;
    gap: 10px;
}

.loading-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
}

.loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.voice-command-response {
    margin-top: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    min-height: 100px;
}

.response-header {
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid #dee2e6;
}

.response-content {
    min-height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
async function processVoiceCommand(audioBlob) {
    try {
        // Show loading indicator
        document.getElementById('voiceLoading').style.display = 'flex';
        document.getElementById('voiceStatus').textContent = 'Se procesează...';
        document.getElementById('systemResponse').innerHTML = '';

        const formData = new FormData();
        formData.append('audio', audioBlob);

        const response = await fetch('/voice-command', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        
        // Update response display
        document.getElementById('systemResponse').innerHTML = `
            <div class="response-text">${data.text}</div>
            ${data.audio ? `<audio src="${data.audio}" controls></audio>` : ''}
        `;
        
        // Play audio response if available
        if (data.audio) {
            const audio = new Audio(data.audio);
            audio.play();
        }
    } catch (error) {
        console.error('Error processing voice command:', error);
        document.getElementById('systemResponse').innerHTML = `
            <div class="error-message">Eroare la procesarea comenzii vocale. Vă rugăm să încercați din nou.</div>
        `;
    } finally {
        // Hide loading indicator
        document.getElementById('voiceLoading').style.display = 'none';
        document.getElementById('voiceStatus').textContent = 'Gata';
    }
}
</script> 