import './bootstrap';
import '../css/app.css';

import Alpine from 'alpinejs';
import { createApp } from 'vue';
import VoiceCommand from './components/VoiceCommand.vue';

window.Alpine = Alpine;
Alpine.start();

const app = createApp({});
app.component('voice-command', VoiceCommand);
app.mount('#app');
