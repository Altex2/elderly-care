import 'react-native-gesture-handler';
import React from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import AppNavigator from './src/navigation/AppNavigator';
import { library } from '@fortawesome/fontawesome-svg-core';
import { 
  faUserPlus, 
  faPlus, 
  faBell, 
  faUsers, 
  faCalendarDay,
  faHome,
  faUser
} from '@fortawesome/free-solid-svg-icons';

library.add(
  faUserPlus, 
  faPlus, 
  faBell, 
  faUsers, 
  faCalendarDay,
  faHome,
  faUser
);

const App = () => {
  return (
    <SafeAreaProvider>
      <AppNavigator />
    </SafeAreaProvider>
  );
};

export default App; 