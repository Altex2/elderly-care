import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { FontAwesomeIcon } from '@fortawesome/react-native-fontawesome';
import { faHome, faBell, faUsers, faUser } from '@fortawesome/free-solid-svg-icons';

// Auth Screens
import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';

// Caregiver Screens
import CaregiverDashboard from '../screens/caregiver/DashboardScreen';
import RemindersScreen from '../screens/caregiver/RemindersScreen';
import PatientsScreen from '../screens/caregiver/PatientsScreen';

// Patient Screens
import UserDashboard from '../screens/user/DashboardScreen';
import VoiceInterface from '../screens/user/VoiceInterface';

// Common Screens
import ProfileScreen from '../screens/common/ProfileScreen';

const Stack = createStackNavigator();
const Tab = createBottomTabNavigator();

const CaregiverTabs = () => (
  <Tab.Navigator
    screenOptions={{
      headerShown: false,
      tabBarActiveTintColor: '#007AFF',
      tabBarInactiveTintColor: '#999',
      tabBarStyle: {
        paddingBottom: 5,
        paddingTop: 5,
      }
    }}
  >
    <Tab.Screen 
      name="Dashboard" 
      component={CaregiverDashboard}
      options={{
        tabBarIcon: ({ color, size }) => (
          <FontAwesomeIcon icon={faHome} size={size} color={color} />
        ),
      }}
    />
    <Tab.Screen 
      name="Reminders" 
      component={RemindersScreen}
      options={{
        tabBarIcon: ({ color, size }) => (
          <FontAwesomeIcon icon={faBell} size={size} color={color} />
        ),
      }}
    />
    <Tab.Screen 
      name="Patients" 
      component={PatientsScreen}
      options={{
        tabBarIcon: ({ color, size }) => (
          <FontAwesomeIcon icon={faUsers} size={size} color={color} />
        ),
      }}
    />
    <Tab.Screen 
      name="Profile" 
      component={ProfileScreen}
      options={{
        tabBarIcon: ({ color, size }) => (
          <FontAwesomeIcon icon={faUser} size={size} color={color} />
        ),
      }}
    />
  </Tab.Navigator>
);

const UserTabs = () => (
  <Tab.Navigator
    screenOptions={{
      headerShown: false,
      tabBarActiveTintColor: '#007AFF',
    }}
  >
    <Tab.Screen 
      name="Dashboard" 
      component={UserDashboard}
      options={{
        tabBarIcon: ({ color, size }) => (
          <FontAwesomeIcon icon={faHome} size={size} color={color} />
        ),
      }}
    />
    <Tab.Screen 
      name="Voice" 
      component={VoiceInterface}
      options={{
        tabBarIcon: ({ color, size }) => (
          <FontAwesomeIcon icon={faBell} size={size} color={color} />
        ),
      }}
    />
    <Tab.Screen 
      name="Profile" 
      component={ProfileScreen}
      options={{
        tabBarIcon: ({ color, size }) => (
          <FontAwesomeIcon icon={faUser} size={size} color={color} />
        ),
      }}
    />
  </Tab.Navigator>
);

const AppNavigator = () => {
  return (
    <NavigationContainer>
      <Stack.Navigator 
        screenOptions={{
          headerShown: false,
        }}
      >
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="Register" component={RegisterScreen} />
        <Stack.Screen name="CaregiverHome" component={CaregiverTabs} />
        <Stack.Screen name="UserHome" component={UserTabs} />
      </Stack.Navigator>
    </NavigationContainer>
  );
};

export default AppNavigator; 