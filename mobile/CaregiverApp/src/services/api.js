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

// Add response interceptor for better error handling
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    console.log('API Error:', {
      status: error.response?.status,
      data: error.response?.data,
      config: error.config
    });

    if (error.response?.status === 401) {
      await AsyncStorage.removeItem('token');
      // You might want to add navigation to login here
    }
    return Promise.reject(error);
  }
);

export const authService = {
  login: async (email, password) => {
    const response = await api.post('/login', { email, password });
    const token = response.data.token;
    if (token) {
      await AsyncStorage.setItem('token', token);
      console.log('Stored token:', token);
    }
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

export const patientService = {
  getPatients: () => api.get('/caregiver/patients'),
  addPatient: (patientData) => api.post('/caregiver/patients/create', patientData),
  removePatient: (patientId) => api.delete(`/caregiver/patients/${patientId}`),
  updatePatient: (patientId, patientData) => api.put(`/caregiver/patients/${patientId}`, patientData),
};

export const reminderService = {
  getReminders: () => api.get('/caregiver/reminders'),
  completeReminder: (id) => api.post(`/reminders/${id}/complete`),
  createReminder: (reminderData) => api.post('/caregiver/reminders', reminderData),
  updateReminder: (reminderId, reminderData) => api.put(`/caregiver/reminders/${reminderId}`, reminderData),
  deleteReminder: (reminderId) => api.delete(`/caregiver/reminders/${reminderId}`),
};

export const voiceService = {
  processCommand: (audioData) => api.post('/voice/process', audioData),
  textToSpeech: (text) => api.post('/voice/speak', { text }),
};

export default api; 