const {getDefaultConfig, mergeConfig} = require('@react-native/metro-config');
const {withNativeWind} = require('nativewind/metro');

const config = mergeConfig(getDefaultConfig(__dirname), {
  resolver: {
    // Allow .cjs files (some dependencies ship CommonJS only)
    sourceExts: ['js', 'jsx', 'ts', 'tsx', 'cjs', 'json'],
  },
});

// NativeWind post-processes CSS — must be the outermost wrapper
module.exports = withNativeWind(config, {input: './global.css'});
