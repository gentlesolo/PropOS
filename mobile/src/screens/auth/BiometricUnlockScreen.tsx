import React, {useState, useEffect, useRef} from 'react';
import {
  View,
  Text,
  Pressable,
  Animated,
  TextInput,
  KeyboardAvoidingView,
  Platform,
  Image,
  ActivityIndicator,
} from 'react-native';
import Icon from 'react-native-vector-icons/Feather';
import ReactNativeBiometrics from 'react-native-biometrics';
import * as Keychain from 'react-native-keychain';
import {useAuthStore} from '../../store/authStore';
import {useTheme} from '../../theme/ThemeProvider';

const rnb = new ReactNativeBiometrics();

export function BiometricUnlockScreen() {
  const {tokens, resolvedTheme} = useTheme();
  const {user, setLocked, clearAuth} = useAuthStore();
  const [showPasscode, setShowPasscode] = useState(false);
  const [passwordInput, setPasswordInput] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [status, setStatus] = useState<'idle' | 'success' | 'failed'>('idle');

  const pulseAnim = useRef(new Animated.Value(1)).current;
  const shakeAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const fadeAnim = useRef(new Animated.Value(1)).current;
  const slideAnim = useRef(new Animated.Value(100)).current;

  useEffect(() => {
    if (status === 'idle') {
      const animation = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {toValue: 1.08, duration: 1500, useNativeDriver: true}),
          Animated.timing(pulseAnim, {toValue: 0.98, duration: 1500, useNativeDriver: true}),
        ])
      );
      animation.start();
      return () => animation.stop();
    }
  }, [status]);

  useEffect(() => {
    const triggerBiometrics = async () => {
      setTimeout(async () => {
        await handleBiometricPrompt();
      }, 500);
    };
    triggerBiometrics();
  }, []);

  const handleBiometricPrompt = async () => {
    try {
      const {available} = await rnb.isSensorAvailable();
      if (!available) {
        setShowPasscode(true);
        Animated.spring(slideAnim, {toValue: 0, useNativeDriver: true}).start();
        return;
      }
      const {success} = await rnb.simplePrompt({promptMessage: 'Authenticate to unlock VillaCRM'});
      if (success) {
        handleSuccess();
      } else {
        handleFailure('Authentication cancelled or failed.');
      }
    } catch (err) {
      handleFailure('Biometrics failed. Please use passcode.');
      setShowPasscode(true);
      Animated.spring(slideAnim, {toValue: 0, useNativeDriver: true}).start();
    }
  };

  const handleSuccess = () => {
    setStatus('success');
    Animated.sequence([
      Animated.spring(scaleAnim, {toValue: 1.3, friction: 4, tension: 40, useNativeDriver: true}),
      Animated.spring(scaleAnim, {toValue: 1.1, friction: 5, useNativeDriver: true}),
    ]).start();
    setTimeout(() => {
      Animated.timing(fadeAnim, {toValue: 0, duration: 250, useNativeDriver: true}).start(() => {
        setLocked(false);
      });
    }, 400);
  };

  const handleFailure = (msg = 'Invalid credentials') => {
    setStatus('failed');
    setErrorMsg(msg);
    Animated.sequence([
      Animated.timing(shakeAnim, {toValue: -15, duration: 40, useNativeDriver: true}),
      Animated.timing(shakeAnim, {toValue: 15, duration: 80, useNativeDriver: true}),
      Animated.timing(shakeAnim, {toValue: -12, duration: 80, useNativeDriver: true}),
      Animated.timing(shakeAnim, {toValue: 12, duration: 80, useNativeDriver: true}),
      Animated.timing(shakeAnim, {toValue: -8, duration: 80, useNativeDriver: true}),
      Animated.timing(shakeAnim, {toValue: 8, duration: 80, useNativeDriver: true}),
      Animated.timing(shakeAnim, {toValue: 0, duration: 40, useNativeDriver: true}),
    ]).start();
    setTimeout(() => {
      setStatus('idle');
      setErrorMsg('');
    }, 1500);
  };

  const verifyPasscode = async () => {
    if (!passwordInput) return;
    setIsVerifying(true);
    setErrorMsg('');
    try {
      const credentials = await Keychain.getGenericPassword({service: 'villacrm_credentials'});
      if (credentials && credentials.password === passwordInput) {
        setIsVerifying(false);
        handleSuccess();
      } else {
        setIsVerifying(false);
        handleFailure('Incorrect passcode or password');
      }
    } catch (err) {
      setIsVerifying(false);
      handleFailure('Verification error. Please retry.');
    }
  };

  const handleUsePasscodeClick = () => {
    setShowPasscode(true);
    Animated.spring(slideAnim, {toValue: 0, useNativeDriver: true}).start();
  };

  const handleLogout = () => {
    clearAuth();
  };

  const initials = user
    ? `${user.first_name?.[0] || ''}${user.last_name?.[0] || ''}`
    : 'P';

  // In light mode the overlay is a frosted-glass effect; dark mode is near-opaque dark
  const overlayBg =
    resolvedTheme === 'light'
      ? 'rgba(248,250,252,0.92)'
      : 'rgba(2,6,23,0.95)';

  return (
    <Animated.View
      style={{
        opacity: fadeAnim,
        position: 'absolute',
        top: 0, right: 0, bottom: 0, left: 0,
        backgroundColor: overlayBg,
        zIndex: 50,
        justifyContent: 'space-between',
        paddingVertical: 48,
        paddingHorizontal: 24,
      }}
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1 justify-between"
      >
        {/* Status Header */}
        <View className="items-center mt-6">
          <View className="flex-row items-center gap-2">
            <Icon name="shield" size={16} color={tokens.brandPrimary} />
            <Text style={{color: tokens.brandPrimary}} className="font-mono text-xs uppercase tracking-widest font-bold">
              Secure Session
            </Text>
          </View>
        </View>

        {/* Biometrics Indicator */}
        <View className="items-center justify-center flex-1 py-8">
          <Animated.View
            style={{
              transform: [
                {scale: status === 'success' ? scaleAnim : pulseAnim},
                {translateX: shakeAnim},
              ],
            }}
            className="items-center"
          >
            <Pressable
              onPress={status === 'idle' ? handleBiometricPrompt : undefined}
              style={{
                width: 96,
                height: 96,
                borderRadius: 48,
                alignItems: 'center',
                justifyContent: 'center',
                borderWidth: 2,
                backgroundColor:
                  status === 'success'
                    ? '#22C55E'
                    : status === 'failed'
                    ? tokens.dangerBg
                    : tokens.surfaceRaised,
                borderColor:
                  status === 'success'
                    ? '#22C55E'
                    : status === 'failed'
                    ? tokens.dangerText
                    : `${tokens.brandPrimary}4D`,
              }}
            >
              {status === 'success' ? (
                <Icon name="check" size={44} color={tokens.textInverse} />
              ) : (
                <Icon
                  name={showPasscode ? 'key' : 'shield'}
                  size={40}
                  color={status === 'failed' ? tokens.dangerText : tokens.brandPrimary}
                />
              )}
            </Pressable>
          </Animated.View>

          <View className="items-center mt-6 gap-2">
            <Text style={{color: tokens.textPrimary}} className="text-xl font-extrabold tracking-tight">
              {status === 'success' ? 'Unlocked' : 'VillaCRM is Locked'}
            </Text>

            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 999,
                paddingLeft: 8,
                paddingRight: 16,
                paddingVertical: 6,
                gap: 10,
                marginTop: 8,
              }}
            >
              {user?.avatar_path ? (
                <Image source={{uri: user.avatar_path}} style={{width: 28, height: 28, borderRadius: 14}} />
              ) : (
                <View
                  style={{
                    width: 28,
                    height: 28,
                    borderRadius: 14,
                    backgroundColor: tokens.brandPrimary,
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  <Text style={{color: tokens.textInverse}} className="text-[10px] font-bold">
                    {initials}
                  </Text>
                </View>
              )}
              <Text style={{color: tokens.textSecondary}} className="text-sm font-semibold">
                {user ? `${user.first_name} ${user.last_name}` : 'Field Agent'}
              </Text>
            </View>
          </View>

          {errorMsg ? (
            <Text style={{color: tokens.dangerText}} className="text-sm font-bold text-center mt-4 px-6">
              {errorMsg}
            </Text>
          ) : null}
        </View>

        {/* Fallback options */}
        <View className="items-center gap-6 pb-6">
          {!showPasscode ? (
            <Pressable onPress={handleUsePasscodeClick} className="active:opacity-80 py-2">
              <Text style={{color: tokens.brandPrimary}} className="text-base font-bold tracking-wide">
                Use Passcode
              </Text>
            </Pressable>
          ) : (
            <Animated.View
              style={{transform: [{translateY: slideAnim}], width: '100%'}}
              className="gap-4 items-center"
            >
              <View className="w-full relative justify-center">
                <TextInput
                  style={{
                    backgroundColor: tokens.surfaceInput,
                    color: tokens.textPrimary,
                    borderWidth: 1,
                    borderColor: tokens.borderStrong,
                    borderRadius: 10,
                    paddingHorizontal: 20,
                    paddingVertical: 16,
                    textAlign: 'center',
                    fontSize: 18,
                    width: '100%',
                  }}
                  placeholder="Enter Password"
                  placeholderTextColor={tokens.textTertiary}
                  secureTextEntry
                  value={passwordInput}
                  onChangeText={setPasswordInput}
                  onSubmitEditing={verifyPasscode}
                  autoFocus
                />
                {isVerifying && (
                  <View className="absolute right-4">
                    <ActivityIndicator size="small" color={tokens.brandPrimary} />
                  </View>
                )}
              </View>

              <View className="flex-row gap-4 w-full">
                <Pressable
                  onPress={() => {
                    setShowPasscode(false);
                    setPasswordInput('');
                  }}
                  style={{
                    flex: 1,
                    borderWidth: 1,
                    borderColor: tokens.borderStrong,
                    borderRadius: 10,
                    height: 52,
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  <Text style={{color: tokens.textSecondary}} className="font-bold text-base">
                    Cancel
                  </Text>
                </Pressable>

                <Pressable
                  onPress={verifyPasscode}
                  disabled={isVerifying}
                  className="flex-1 bg-brand-500 rounded-[10px] h-[52px] items-center justify-center active:bg-brand-600"
                >
                  <Text style={{color: tokens.textInverse}} className="font-bold text-base">
                    Verify
                  </Text>
                </Pressable>
              </View>
            </Animated.View>
          )}

          <Pressable onPress={handleLogout} className="active:opacity-75 py-2">
            <Text style={{color: tokens.textTertiary}} className="text-xs font-semibold tracking-wider uppercase">
              Sign Out &amp; Switch Account
            </Text>
          </Pressable>
        </View>
      </KeyboardAvoidingView>
    </Animated.View>
  );
}
