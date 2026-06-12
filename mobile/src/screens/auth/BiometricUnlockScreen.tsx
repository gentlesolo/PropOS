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

const rnb = new ReactNativeBiometrics();

export function BiometricUnlockScreen() {
  const {user, setLocked, clearAuth} = useAuthStore();
  const [showPasscode, setShowPasscode] = useState(false);
  const [passwordInput, setPasswordInput] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [status, setStatus] = useState<'idle' | 'success' | 'failed'>('idle');

  // Animation values
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const shakeAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(1)).current;
  const fadeAnim = useRef(new Animated.Value(1)).current;
  const slideAnim = useRef(new Animated.Value(100)).current; // For passcode slide-in

  // Loop pulsing animation for the fingerprint scanner
  useEffect(() => {
    if (status === 'idle') {
      const animation = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.08,
            duration: 1500,
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 0.98,
            duration: 1500,
            useNativeDriver: true,
          }),
        ])
      );
      animation.start();
      return () => animation.stop();
    }
  }, [status]);

  // Attempt biometric authentication on mount
  useEffect(() => {
    const triggerBiometrics = async () => {
      // Small timeout to allow transition stability
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
        Animated.spring(slideAnim, {
          toValue: 0,
          useNativeDriver: true,
        }).start();
        return;
      }

      const {success} = await rnb.simplePrompt({
        promptMessage: 'Authenticate to unlock PropOS',
      });

      if (success) {
        handleSuccess();
      } else {
        handleFailure('Authentication cancelled or failed.');
      }
    } catch (err) {
      handleFailure('Biometrics failed. Please use passcode.');
      setShowPasscode(true);
      Animated.spring(slideAnim, {
        toValue: 0,
        useNativeDriver: true,
      }).start();
    }
  };

  const handleSuccess = () => {
    setStatus('success');
    
    // Checkmark scale spring morph animation
    Animated.sequence([
      Animated.spring(scaleAnim, {
        toValue: 1.3,
        friction: 4,
        tension: 40,
        useNativeDriver: true,
      }),
      Animated.spring(scaleAnim, {
        toValue: 1.1,
        friction: 5,
        useNativeDriver: true,
      }),
    ]).start();

    // Blur fade out in 250ms, then remove overlay
    setTimeout(() => {
      Animated.timing(fadeAnim, {
        toValue: 0,
        duration: 250,
        useNativeDriver: true,
      }).start(() => {
        setLocked(false);
      });
    }, 400);
  };

  const handleFailure = (msg = 'Invalid credentials') => {
    setStatus('failed');
    setErrorMsg(msg);

    // Shake animation: horizontal, 3x, 80ms each
    Animated.sequence([
      Animated.timing(shakeAnim, { toValue: -15, duration: 40, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: 15, duration: 80, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: -12, duration: 80, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: 12, duration: 80, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: -8, duration: 80, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: 8, duration: 80, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: 0, duration: 40, useNativeDriver: true }),
    ]).start();

    // Reset status back to idle after 1.5 seconds so they can retry
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
    Animated.spring(slideAnim, {
      toValue: 0,
      useNativeDriver: true,
    }).start();
  };

  const handleLogout = () => {
    // If lock state is broken/stuck, allow logging out to force a clean credential refresh
    clearAuth();
  };

  // Build initials for the fallback avatar
  const initials = user
    ? `${user.first_name?.[0] || ''}${user.last_name?.[0] || ''}`
    : 'P';

  return (
    <Animated.View 
      style={{opacity: fadeAnim}} 
      className="absolute inset-0 bg-[#020617]/95 z-50 justify-between py-12 px-6"
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1 justify-between"
      >
        {/* Top Spacer / Status Header */}
        <View className="items-center mt-6">
          <View className="flex-row items-center gap-2">
            <Icon name="shield" size={16} color="#10B981" />
            <Text className="text-brand-500 font-mono text-xs uppercase tracking-widest font-bold">Secure Session</Text>
          </View>
        </View>

        {/* Center Section: Biometrics Indicator */}
        <View className="items-center justify-center flex-1 py-8">
          <Animated.View
            style={[
              {
                transform: [
                  {scale: status === 'success' ? scaleAnim : pulseAnim},
                  {translateX: shakeAnim},
                ],
              },
            ]}
            className="items-center"
          >
            <Pressable
              onPress={status === 'idle' ? handleBiometricPrompt : undefined}
              className={`w-24 h-24 rounded-full items-center justify-center border-2 shadow-2xl ${
                status === 'success'
                  ? 'bg-success border-success'
                  : status === 'failed'
                  ? 'bg-danger/20 border-danger'
                  : 'bg-surface-raised border-brand-500/30'
              }`}
            >
              {status === 'success' ? (
                <Icon name="check" size={44} color="#FAFAFA" />
              ) : (
                <Icon
                  name={showPasscode ? 'key' : 'shield'}
                  size={40}
                  color={status === 'failed' ? '#F43F5E' : '#10B981'}
                />
              )}
            </Pressable>
          </Animated.View>

          <View className="items-center mt-6 gap-2">
            <Text className="text-text-primary text-xl font-extrabold tracking-tight">
              {status === 'success' ? 'Unlocked' : 'PropOS is Locked'}
            </Text>
            
            {/* User profile detail */}
            <View className="flex-row items-center bg-surface-card border border-zinc-800 rounded-full pl-2 pr-4 py-1.5 gap-2.5 mt-2">
              {user?.avatar_path ? (
                <Image
                  source={{uri: user.avatar_path}}
                  className="w-7 h-7 rounded-full"
                />
              ) : (
                <View className="w-7 h-7 bg-brand-500 rounded-full items-center justify-center">
                  <Text className="text-text-primary text-[10px] font-bold">{initials}</Text>
                </View>
              )}
              <Text className="text-text-secondary text-sm font-semibold">
                {user ? `${user.first_name} ${user.last_name}` : 'Field Agent'}
              </Text>
            </View>
          </View>

          {/* Inline Error display */}
          {errorMsg ? (
            <Text className="text-danger text-sm font-bold text-center mt-4 px-6 animate-pulse">
              {errorMsg}
            </Text>
          ) : null}
        </View>

        {/* Bottom Section: Fallback options */}
        <View className="items-center gap-6 pb-6">
          {!showPasscode ? (
            <Pressable 
              onPress={handleUsePasscodeClick}
              className="active:opacity-80 py-2"
            >
              <Text className="text-brand-500 text-base font-bold tracking-wide">
                Use Passcode
              </Text>
            </Pressable>
          ) : (
            <Animated.View
              style={{
                transform: [{translateY: slideAnim}],
                width: '100%',
              }}
              className="gap-4 items-center"
            >
              <View className="w-full relative justify-center">
                <TextInput
                  className="w-full bg-surface-input text-text-primary px-5 py-4 rounded-[10px] border border-zinc-800 focus:border-brand-500 text-center text-lg"
                  placeholder="Enter Password"
                  placeholderTextColor="#71717A"
                  secureTextEntry
                  value={passwordInput}
                  onChangeText={setPasswordInput}
                  onSubmitEditing={verifyPasscode}
                  autoFocus
                />
                {isVerifying && (
                  <View className="absolute right-4">
                    <ActivityIndicator size="small" color="#10B981" />
                  </View>
                )}
              </View>

              <View className="flex-row gap-4 w-full">
                <Pressable
                  onPress={() => {
                    setShowPasscode(false);
                    setPasswordInput('');
                  }}
                  className="flex-1 border border-zinc-800 rounded-[10px] h-[52px] items-center justify-center active:bg-zinc-900"
                >
                  <Text className="text-text-secondary font-bold text-base">Cancel</Text>
                </Pressable>

                <Pressable
                  onPress={verifyPasscode}
                  disabled={isVerifying}
                  className="flex-1 bg-brand-500 rounded-[10px] h-[52px] items-center justify-center active:bg-brand-600 shadow-md shadow-brand-500/25"
                >
                  <Text className="text-text-primary font-bold text-base">Verify</Text>
                </Pressable>
              </View>
            </Animated.View>
          )}

          <Pressable 
            onPress={handleLogout}
            className="active:opacity-75 py-2"
          >
            <Text className="text-text-tertiary text-xs font-semibold tracking-wider uppercase">
              Sign Out & Switch Account
            </Text>
          </Pressable>
        </View>
      </KeyboardAvoidingView>
    </Animated.View>
  );
}
