module.exports = {
  presets: ['module:@react-native/babel-preset'],
  plugins: [
    // NativeWind requires this Babel transform
    'nativewind/babel',
  ],
};
