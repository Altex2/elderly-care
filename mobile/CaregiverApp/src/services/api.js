import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = 'http://10.0.2.2:8000/api/v1'; // Use 10.0.2.2 for Android emulator to connect to localhost

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add token to requests
api.interceptors.request.use(
  async (config) => {
    const token = await AsyncStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

export const authService = {
  login: async (email, password) => {
    const response = await api.post('/login', { email, password });
    await AsyncStorage.setItem('token', response.data.token);
    return response.data;
  },
  register: async (userData) => {
    const response = await api.post('/register', userData);
    return response.data;
  },
  logout: async () => {
    await api.post('/logout');
    await AsyncStorage.removeItem('token');
  },
};

export const reminderService = {
  getReminders: () => api.get('/reminders'),
  completeReminder: (id) => api.post(`/reminders/${id}/complete`),
};

export const voiceService = {
  processCommand: (audioData) => api.post('/voice/process', audioData),
  textToSpeech: (text) => api.post('/voice/speak', { text }),
};

export default api; 