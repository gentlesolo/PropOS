import {AppRegistry} from 'react-native';
import './global.css'; // Enable NativeWind styling
import './src/i18n';   // initialise i18next before any component renders
import App from './App';
import {name as appName} from './app.json';

AppRegistry.registerComponent(appName, () => App);
