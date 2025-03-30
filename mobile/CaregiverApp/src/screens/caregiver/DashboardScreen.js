import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Modal, TextInput, Alert } from 'react-native';
import { FontAwesomeIcon } from '@fortawesome/react-native-fontawesome';
import { faUserPlus, faPlus, faBell, faUsers, faCalendarDay } from '@fortawesome/free-solid-svg-icons';
import { reminderService, patientService } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation } from '@react-navigation/native';

const DashboardScreen = () => {
  const navigation = useNavigation();
  const [reminders, setReminders] = useState([]);
  const [patients, setPatients] = useState([]);
  const [loading, setLoading] = useState(true);
  const [addPatientModalVisible, setAddPatientModalVisible] = useState(false);
  const [addReminderModalVisible, setAddReminderModalVisible] = useState(false);
  const [selectedPatientId, setSelectedPatientId] = useState(null);
  const [newPatient, setNewPatient] = useState({
    name: '',
    email: '',
    password: '',
  });
  const [newReminder, setNewReminder] = useState({
    title: '',
    description: '',
    schedule: '',
    priority: '1',
  });

  useEffect(() => {
    checkAuth();
    loadData();
  }, []);

  const checkAuth = async () => {
    const token = await AsyncStorage.getItem('token');
    if (!token) {
      navigation.replace('Login');
    }
  };

  const loadData = async () => {
    try {
      setLoading(true);
      const [remindersResponse, patientsResponse] = await Promise.all([
        reminderService.getReminders(),
        patientService.getPatients()
      ]);
      console.log('API Responses:', { reminders: remindersResponse.data, patients: patientsResponse.data });
      setReminders(remindersResponse.data?.reminders || []);
      setPatients(patientsResponse.data?.patients || []);
    } catch (error) {
      console.error('Error loading data:', error.response?.data || error.message);
      if (error.response?.status === 401) {
        await AsyncStorage.removeItem('token');
        navigation.replace('Login');
      }
      Alert.alert('Error', 'Failed to load data');
    } finally {
      setLoading(false);
    }
  };

  const handleAddPatient = async () => {
    try {
      await patientService.addPatient(newPatient);
      setAddPatientModalVisible(false);
      setNewPatient({ name: '', email: '', password: '' });
      loadData();
      Alert.alert('Success', 'Patient added successfully');
    } catch (error) {
      Alert.alert('Error', 'Failed to add patient');
    }
  };

  const handleAddReminder = async () => {
    try {
      await reminderService.createReminder({
        ...newReminder,
        user_id: selectedPatientId,
      });
      setAddReminderModalVisible(false);
      setNewReminder({ title: '', description: '', schedule: '', priority: '1' });
      loadData();
      Alert.alert('Success', 'Reminder created successfully');
    } catch (error) {
      Alert.alert('Error', 'Failed to create reminder');
    }
  };

  const getTodayReminders = (patientId) => {
    return reminders.filter(reminder => 
      reminder.user_id === patientId && 
      reminder.status === 'active' &&
      new Date(reminder.next_occurrence).toDateString() === new Date().toDateString()
    );
  };

  return (
    <View style={styles.container}>
      <ScrollView style={styles.scrollView}>
        <View style={styles.header}>
          <View style={styles.headerContent}>
            <Text style={styles.headerText}>Dashboard</Text>
            <TouchableOpacity 
              style={styles.addButton}
              onPress={() => setAddPatientModalVisible(true)}
            >
              <FontAwesomeIcon icon={faUserPlus} size={20} color="#007AFF" />
              <Text style={styles.addButtonText}>Add Patient</Text>
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.statsContainer}>
          <View style={styles.statBox}>
            <FontAwesomeIcon icon={faUsers} size={24} color="#007AFF" style={styles.statIcon} />
            <Text style={styles.statNumber}>{patients.length}</Text>
            <Text style={styles.statLabel}>Patients</Text>
          </View>
          <View style={styles.statBox}>
            <FontAwesomeIcon icon={faBell} size={24} color="#007AFF" style={styles.statIcon} />
            <Text style={styles.statNumber}>
              {reminders.filter(r => r.status === 'active').length}
            </Text>
            <Text style={styles.statLabel}>Active Reminders</Text>
          </View>
          <View style={styles.statBox}>
            <FontAwesomeIcon icon={faCalendarDay} size={24} color="#007AFF" style={styles.statIcon} />
            <Text style={styles.statNumber}>
              {reminders.filter(r => 
                r.status === 'active' && 
                new Date(r.next_occurrence).toDateString() === new Date().toDateString()
              ).length}
            </Text>
            <Text style={styles.statLabel}>Today's Tasks</Text>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Your Patients</Text>
          {loading ? (
            <View style={styles.loadingContainer}>
              <Text style={styles.loadingText}>Loading...</Text>
            </View>
          ) : patients.length > 0 ? (
            patients.map((patient) => (
              <View key={patient.id} style={styles.patientCard}>
                <View style={styles.patientInfo}>
                  <Text style={styles.patientName}>{patient.name}</Text>
                  <Text style={styles.patientEmail}>{patient.email}</Text>
                  
                  <View style={styles.todayReminders}>
                    <Text style={styles.remindersSectionTitle}>Today's Reminders</Text>
                    {getTodayReminders(patient.id).length > 0 ? (
                      getTodayReminders(patient.id).map((reminder) => (
                        <View key={reminder.id} style={styles.reminderItem}>
                          <FontAwesomeIcon icon={faBell} size={12} color="#666" />
                          <Text style={styles.reminderText}>{reminder.title}</Text>
                        </View>
                      ))
                    ) : (
                      <Text style={styles.noRemindersText}>No reminders for today</Text>
                    )}
                  </View>
                </View>
                <TouchableOpacity 
                  style={styles.addReminderButton}
                  onPress={() => {
                    setSelectedPatientId(patient.id);
                    setAddReminderModalVisible(true);
                  }}
                >
                  <FontAwesomeIcon icon={faPlus} size={20} color="#007AFF" />
                </TouchableOpacity>
              </View>
            ))
          ) : (
            <View style={styles.emptyStateContainer}>
              <FontAwesomeIcon icon={faUsers} size={48} color="#ccc" />
              <Text style={styles.noDataText}>
                No patients yet. Click "Add Patient" to get started.
              </Text>
            </View>
          )}
        </View>
      </ScrollView>

      {/* Add Patient Modal */}
      <Modal
        visible={addPatientModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setAddPatientModalVisible(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Add New Patient</Text>
            <TextInput
              style={styles.input}
              placeholder="Name"
              value={newPatient.name}
              onChangeText={(text) => setNewPatient({...newPatient, name: text})}
            />
            <TextInput
              style={styles.input}
              placeholder="Email"
              value={newPatient.email}
              onChangeText={(text) => setNewPatient({...newPatient, email: text})}
              keyboardType="email-address"
              autoCapitalize="none"
            />
            <TextInput
              style={styles.input}
              placeholder="Password"
              value={newPatient.password}
              onChangeText={(text) => setNewPatient({...newPatient, password: text})}
              secureTextEntry
            />
            <View style={styles.modalButtons}>
              <TouchableOpacity 
                style={[styles.button, styles.cancelButton]}
                onPress={() => setAddPatientModalVisible(false)}
              >
                <Text style={styles.buttonText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.button, styles.saveButton]}
                onPress={handleAddPatient}
              >
                <Text style={styles.buttonText}>Add Patient</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* Add Reminder Modal */}
      <Modal
        visible={addReminderModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setAddReminderModalVisible(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Add New Reminder</Text>
            <TextInput
              style={styles.input}
              placeholder="Title"
              value={newReminder.title}
              onChangeText={(text) => setNewReminder({...newReminder, title: text})}
            />
            <TextInput
              style={[styles.input, styles.textArea]}
              placeholder="Description"
              value={newReminder.description}
              onChangeText={(text) => setNewReminder({...newReminder, description: text})}
              multiline
              numberOfLines={3}
            />
            <TextInput
              style={styles.input}
              placeholder="Schedule (e.g., daily at 9am)"
              value={newReminder.schedule}
              onChangeText={(text) => setNewReminder({...newReminder, schedule: text})}
            />
            <TextInput
              style={styles.input}
              placeholder="Priority (1-5)"
              value={newReminder.priority}
              onChangeText={(text) => setNewReminder({...newReminder, priority: text})}
              keyboardType="numeric"
            />
            <View style={styles.modalButtons}>
              <TouchableOpacity 
                style={[styles.button, styles.cancelButton]}
                onPress={() => setAddReminderModalVisible(false)}
              >
                <Text style={styles.buttonText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.button, styles.saveButton]}
                onPress={handleAddReminder}
              >
                <Text style={styles.buttonText}>Add Reminder</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  scrollView: {
    flex: 1,
  },
  header: {
    backgroundColor: '#007AFF',
    paddingTop: 0, // Remove extra padding
    padding: 16,
  },
  headerContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerText: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#fff',
  },
  addButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    gap: 8,
  },
  addButtonText: {
    color: '#007AFF',
    fontWeight: '600',
  },
  statsContainer: {
    flexDirection: 'row',
    padding: 16,
    gap: 12,
  },
  statBox: {
    flex: 1,
    backgroundColor: '#fff',
    padding: 16,
    borderRadius: 12,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  statIcon: {
    marginBottom: 8,
  },
  statNumber: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#007AFF',
  },
  statLabel: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
  },
  section: {
    padding: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 16,
    color: '#333',
  },
  patientCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  patientInfo: {
    flex: 1,
  },
  patientName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  patientEmail: {
    fontSize: 14,
    color: '#666',
    marginTop: 4,
  },
  todayReminders: {
    marginTop: 12,
  },
  remindersSectionTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    marginBottom: 8,
  },
  reminderItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 4,
  },
  reminderText: {
    fontSize: 14,
    color: '#666',
  },
  noRemindersText: {
    fontSize: 14,
    color: '#999',
    fontStyle: 'italic',
  },
  addReminderButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#f0f0f0',
    justifyContent: 'center',
    alignItems: 'center',
  },
  emptyStateContainer: {
    alignItems: 'center',
    padding: 32,
  },
  noDataText: {
    textAlign: 'center',
    color: '#666',
    marginTop: 16,
    fontSize: 16,
  },
  loadingContainer: {
    padding: 32,
    alignItems: 'center',
  },
  loadingText: {
    color: '#666',
    fontSize: 16,
  },
  modalContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
  },
  modalContent: {
    backgroundColor: '#fff',
    padding: 20,
    borderRadius: 10,
    width: '80%',
    maxWidth: '90%',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 15,
  },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    padding: 10,
    marginBottom: 10,
    borderRadius: 5,
  },
  modalButtons: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 20,
  },
  button: {
    backgroundColor: '#007AFF',
    padding: 12,
    borderRadius: 6,
  },
  buttonText: {
    color: '#fff',
    fontWeight: 'bold',
  },
  cancelButton: {
    backgroundColor: '#ccc',
  },
  saveButton: {
    backgroundColor: '#007AFF',
  },
  textArea: {
    height: 80,
    textAlignVertical: 'top',
  },
});

export default DashboardScreen; 