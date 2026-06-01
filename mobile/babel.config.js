module.exports = {
  presets: ['module:@react-native/babel-preset'],
  plugins: [
    // NativeWind requires this Babel transform
    'nativewind/babel',
    // react-native-reanimated must be listed last
    'react-native-reanimated/plugin',
  ],
};
