import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Modal,
  TextInput,
  Alert,
} from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { patientService } from '../../services/api';

const PatientsScreen = () => {
  const [patients, setPatients] = useState([]);
  const [modalVisible, setModalVisible] = useState(false);
  const [editingPatient, setEditingPatient] = useState(null);
  const [newPatient, setNewPatient] = useState({
    name: '',
    email: '',
    phone: '',
    address: '',
  });

  useEffect(() => {
    loadPatients();
  }, []);

  const loadPatients = async () => {
    try {
      const response = await patientService.getPatients();
      setPatients(response.data);
    } catch (error) {
      Alert.alert('Error', 'Failed to load patients');
    }
  };

  const handleAddPatient = async () => {
    try {
      await patientService.addPatient(newPatient);
      setModalVisible(false);
      setNewPatient({ name: '', email: '', phone: '', address: '' });
      loadPatients();
      Alert.alert('Success', 'Patient added successfully');
    } catch (error) {
      Alert.alert('Error', 'Failed to add patient');
    }
  };

  const handleUpdatePatient = async () => {
    try {
      await patientService.updatePatient(editingPatient.id, newPatient);
      setModalVisible(false);
      setEditingPatient(null);
      setNewPatient({ name: '', email: '', phone: '', address: '' });
      loadPatients();
      Alert.alert('Success', 'Patient updated successfully');
    } catch (error) {
      Alert.alert('Error', 'Failed to update patient');
    }
  };

  const handleDeletePatient = async (patientId) => {
    Alert.alert(
      'Confirm Delete',
      'Are you sure you want to remove this patient?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            try {
              await patientService.removePatient(patientId);
              loadPatients();
              Alert.alert('Success', 'Patient removed successfully');
            } catch (error) {
              Alert.alert('Error', 'Failed to remove patient');
            }
          },
        },
      ]
    );
  };

  const openEditModal = (patient) => {
    setEditingPatient(patient);
    setNewPatient({
      name: patient.name,
      email: patient.email,
      phone: patient.phone,
      address: patient.address,
    });
    setModalVisible(true);
  };

  const renderPatientItem = ({ item }) => (
    <View style={styles.patientCard}>
      <View style={styles.patientInfo}>
        <Text style={styles.patientName}>{item.name}</Text>
        <Text style={styles.patientDetail}>{item.email}</Text>
        <Text style={styles.patientDetail}>{item.phone}</Text>
        <Text style={styles.patientDetail}>{item.address}</Text>
      </View>
      <View style={styles.actionButtons}>
        <TouchableOpacity 
          style={styles.editButton}
          onPress={() => openEditModal(item)}
        >
          <Icon name="edit" size={24} color="#007AFF" />
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.deleteButton}
          onPress={() => handleDeletePatient(item.id)}
        >
          <Icon name="delete" size={24} color="#ff3b30" />
        </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={patients}
        renderItem={renderPatientItem}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.list}
      />

      <TouchableOpacity 
        style={styles.fab}
        onPress={() => {
          setEditingPatient(null);
          setNewPatient({ name: '', email: '', phone: '', address: '' });
          setModalVisible(true);
        }}
      >
        <Icon name="add" size={24} color="#fff" />
      </TouchableOpacity>

      <Modal
        animationType="slide"
        transparent={true}
        visible={modalVisible}
        onRequestClose={() => setModalVisible(false)}
      >
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>
              {editingPatient ? 'Edit Patient' : 'Add New Patient'}
            </Text>
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
              placeholder="Phone"
              value={newPatient.phone}
              onChangeText={(text) => setNewPatient({...newPatient, phone: text})}
              keyboardType="phone-pad"
            />
            <TextInput
              style={styles.input}
              placeholder="Address"
              value={newPatient.address}
              onChangeText={(text) => setNewPatient({...newPatient, address: text})}
              multiline
            />
            <View style={styles.modalButtons}>
              <TouchableOpacity 
                style={[styles.button, styles.cancelButton]}
                onPress={() => setModalVisible(false)}
              >
                <Text style={styles.buttonText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.button, styles.saveButton]}
                onPress={editingPatient ? handleUpdatePatient : handleAddPatient}
              >
                <Text style={styles.buttonText}>
                  {editingPatient ? 'Update' : 'Save'}
                </Text>
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
  list: {
    padding: 16,
  },
  patientCard: {
    backgroundColor: '#fff',
    borderRadius: 10,
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
    marginBottom: 4,
  },
  patientDetail: {
    fontSize: 14,
    color: '#666',
    marginBottom: 2,
  },
  actionButtons: {
    justifyContent: 'space-around',
    padding: 8,
  },
  editButton: {
    marginBottom: 8,
  },
  deleteButton: {
    marginTop: 8,
  },
  fab: {
    position: 'absolute',
    right: 16,
    bottom: 16,
    backgroundColor: '#007AFF',
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    elevation: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 4,
  },
  modalContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
  },
  modalContent: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 20,
    width: '90%',
    maxHeight: '80%',
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 16,
  },
  input: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 5,
    padding: 10,
    marginBottom: 12,
  },
  modalButtons: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    marginTop: 16,
  },
  button: {
    padding: 10,
    borderRadius: 5,
    marginLeft: 10,
  },
  cancelButton: {
    backgroundColor: '#ff3b30',
  },
  saveButton: {
    backgroundColor: '#007AFF',
  },
  buttonText: {
    color: '#fff',
    fontWeight: 'bold',
  },
});

export default PatientsScreen; 