import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
} from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { reminderService } from '../../services/api';

const RemindersScreen = () => {
  const [reminders, setReminders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadReminders();
  }, []);

  const loadReminders = async () => {
    try {
      const response = await reminderService.getReminders();
      setReminders(response.data?.reminders || []);
    } catch (error) {
      console.error('Error loading reminders:', error);
      Alert.alert('Error', 'Failed to load reminders');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteReminder = (reminderId) => {
    Alert.alert(
      'Delete Reminder',
      'Are you sure you want to delete this reminder?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            try {
              await reminderService.deleteReminder(reminderId);
              loadReminders();
            } catch (error) {
              Alert.alert('Error', 'Failed to delete reminder');
            }
          },
        },
      ]
    );
  };

  const handleCompleteReminder = async (reminderId) => {
    try {
      await reminderService.completeReminder(reminderId);
      loadReminders();
    } catch (error) {
      Alert.alert('Error', 'Failed to complete reminder');
    }
  };

  const getPriorityStyle = (priority) => {
    if (priority >= 4) return styles.highPriority;
    if (priority >= 2) return styles.mediumPriority;
    return styles.lowPriority;
  };

  const renderReminderCard = (reminder) => (
    <View key={reminder.id} style={styles.reminderCard}>
      <View style={styles.reminderHeader}>
        <View style={styles.titleContainer}>
          <Text style={styles.reminderTitle}>{reminder.title}</Text>
          <View style={[styles.priorityBadge, getPriorityStyle(reminder.priority)]}>
            <Text style={styles.priorityText}>Priority {reminder.priority}</Text>
          </View>
          <View style={[styles.statusBadge, reminder.status === 'active' ? styles.activeBadge : styles.completedBadge]}>
            <Text style={styles.statusText}>{reminder.status}</Text>
          </View>
        </View>
      </View>

      <Text style={styles.reminderDescription}>{reminder.description}</Text>
      
      <View style={styles.scheduleContainer}>
        <Text style={styles.scheduleLabel}>Schedule: </Text>
        <Text style={styles.scheduleText}>{reminder.schedule}</Text>
      </View>
      
      <View style={styles.scheduleContainer}>
        <Text style={styles.scheduleLabel}>Next occurrence: </Text>
        <Text style={styles.scheduleText}>
          {reminder.next_occurrence ? new Date(reminder.next_occurrence).toLocaleString() : 'Not scheduled'}
        </Text>
      </View>

      <View style={styles.actionButtons}>
        <TouchableOpacity 
          style={styles.actionButton}
          onPress={() => handleCompleteReminder(reminder.id)}
        >
          <Icon name="check-circle" size={24} color="#4CAF50" />
        </TouchableOpacity>
        <TouchableOpacity 
          style={styles.actionButton}
          onPress={() => handleDeleteReminder(reminder.id)}
        >
          <Icon name="delete" size={24} color="#f44336" />
        </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <ScrollView style={styles.container}>
      {loading ? (
        <Text style={styles.loadingText}>Loading reminders...</Text>
      ) : reminders.length > 0 ? (
        reminders.map(renderReminderCard)
      ) : (
        <Text style={styles.noDataText}>No reminders found</Text>
      )}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
    padding: 16,
  },
  reminderCard: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  reminderHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  titleContainer: {
    flex: 1,
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: 8,
  },
  reminderTitle: {
    fontSize: 18,
    fontWeight: 'bold',
  },
  priorityBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  highPriority: {
    backgroundColor: '#ffebee',
  },
  mediumPriority: {
    backgroundColor: '#fff3e0',
  },
  lowPriority: {
    backgroundColor: '#e8f5e9',
  },
  priorityText: {
    fontSize: 12,
    fontWeight: '600',
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  activeBadge: {
    backgroundColor: '#e8f5e9',
  },
  completedBadge: {
    backgroundColor: '#f5f5f5',
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
  },
  reminderDescription: {
    fontSize: 14,
    color: '#666',
    marginBottom: 8,
  },
  scheduleContainer: {
    flexDirection: 'row',
    marginBottom: 4,
  },
  scheduleLabel: {
    fontSize: 14,
    color: '#666',
    fontWeight: '500',
  },
  scheduleText: {
    fontSize: 14,
    color: '#333',
  },
  actionButtons: {
    flexDirection: 'row',
    justifyContent: 'flex-end',
    marginTop: 12,
    gap: 16,
  },
  actionButton: {
    padding: 4,
  },
  loadingText: {
    textAlign: 'center',
    marginTop: 20,
    color: '#666',
  },
  noDataText: {
    textAlign: 'center',
    marginTop: 20,
    color: '#666',
  },
});

export default RemindersScreen; 