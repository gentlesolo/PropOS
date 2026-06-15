import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useNavigation} from '@react-navigation/native';
import Icon from 'react-native-vector-icons/Feather';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {numbersApi, RegisterNumberPayload, RegisterNumberResponse} from '../../api/numbers';
import {AgentNumber} from '../../types';
import {useTheme} from '../../theme/ThemeProvider';

const COUNTRIES = [
  {name: 'Nigeria',        code: 'NG', dialCode: '+234', flag: '🇳🇬'},
  {name: 'South Africa',   code: 'ZA', dialCode: '+27',  flag: '🇿🇦'},
  {name: 'Ghana',          code: 'GH', dialCode: '+233', flag: '🇬🇭'},
  {name: 'Kenya',          code: 'KE', dialCode: '+254', flag: '🇰🇪'},
  {name: 'United Kingdom', code: 'GB', dialCode: '+44',  flag: '🇬🇧'},
  {name: 'United States',  code: 'US', dialCode: '+1',   flag: '🇺🇸'},
  {name: 'Canada',         code: 'CA', dialCode: '+1',   flag: '🇨🇦'},
];

type Step = 'country' | 'details' | 'verifying';

interface PendingVerification {
  agentNumberId: number;
  displayNumber: string;
}

export function PhoneNumbersScreen() {
  const {tokens} = useTheme();
  const navigation = useNavigation();
  const queryClient = useQueryClient();

  const [modalVisible, setModalVisible] = useState(false);
  const [step, setStep] = useState<Step>('country');
  const [selectedCountry, setSelectedCountry] = useState(COUNTRIES[0]);
  const [phoneInput, setPhoneInput] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [pendingVerification, setPendingVerification] = useState<PendingVerification | null>(null);

  const {data: numbers, isLoading} = useQuery({
    queryKey: ['agent-numbers'],
    queryFn: () => numbersApi.list().then(r => r.data),
  });

  const registerMutation = useMutation({
    mutationFn: (payload: RegisterNumberPayload) => numbersApi.register(payload),
    onSuccess: (response) => {
      queryClient.invalidateQueries({queryKey: ['agent-numbers']});
      const data: RegisterNumberResponse = response.data;
      setPendingVerification({agentNumberId: data.id, displayNumber: data.display_number});
      setStep('verifying');
      if (!data.sms_sent) {
        Alert.alert('SMS not delivered', data.message);
      }
    },
    onError: (error: any) => {
      const msg = error?.response?.data?.message ?? 'Something went wrong. Please try again.';
      Alert.alert('Registration failed', msg);
    },
  });

  const confirmMutation = useMutation({
    mutationFn: ({id, code}: {id: number; code: string}) => numbersApi.confirm(id, code),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['agent-numbers']});
      closeModal();
      Alert.alert('Number verified!', 'Your number is now active and ready for calls.');
    },
    onError: (error: any) => {
      const msg = error?.response?.data?.message ?? 'Invalid or expired code.';
      Alert.alert('Verification failed', msg);
    },
  });

  const resendMutation = useMutation({
    mutationFn: (id: number) => numbersApi.resendOtp(id),
    onSuccess: () => Alert.alert('Code resent', 'A new 6-digit code has been sent to your number.'),
    onError: () => Alert.alert('Error', 'Could not resend code. Please try again.'),
  });

  const activateMutation = useMutation({
    mutationFn: (id: number) => numbersApi.activate(id),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['agent-numbers']}),
    onError: () => Alert.alert('Error', 'Could not set active number.'),
  });

  const removeMutation = useMutation({
    mutationFn: (id: number) => numbersApi.remove(id),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['agent-numbers']}),
    onError: () => Alert.alert('Error', 'Could not remove number.'),
  });

  const closeModal = () => {
    setModalVisible(false);
    setStep('country');
    setSelectedCountry(COUNTRIES[0]);
    setPhoneInput('');
    setOtpCode('');
    setPendingVerification(null);
  };

  const handleRegister = () => {
    if (!phoneInput) return;
    const fullNumber = `${selectedCountry.dialCode}${phoneInput.replace(/^0+/, '')}`;
    registerMutation.mutate({type: 'verified_caller_id', country_code: selectedCountry.code, number: fullNumber});
  };

  const handleRemove = (number: AgentNumber) => {
    Alert.alert(
      'Remove number',
      `Remove ${number.display_number ?? number.twilio_number}? This cannot be undone.`,
      [
        {text: 'Cancel', style: 'cancel'},
        {text: 'Remove', style: 'destructive', onPress: () => removeMutation.mutate(number.id)},
      ],
    );
  };



  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View style={{
        flexDirection: 'row', alignItems: 'center',
        paddingHorizontal: 20, paddingVertical: 16,
        backgroundColor: tokens.surfaceCard,
        borderBottomWidth: 1, borderBottomColor: tokens.borderDefault,
      }}>
        <Pressable onPress={() => navigation.goBack()} style={{marginRight: 16}}>
          <Icon name="arrow-left" size={20} color={tokens.textPrimary} />
        </Pressable>
        <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '900', flex: 1}}>
          Phone Numbers
        </Text>
        <Pressable
          onPress={() => setModalVisible(true)}
          style={{
            backgroundColor: tokens.brandPrimary,
            paddingHorizontal: 14, paddingVertical: 8,
            borderRadius: 10, flexDirection: 'row', alignItems: 'center', gap: 6,
          }}>
          <Icon name="plus" size={14} color="#fff" />
          <Text style={{color: '#fff', fontWeight: '800', fontSize: 12}}>Add Number</Text>
        </Pressable>
      </View>

      <ScrollView style={{flex: 1, padding: 16}} showsVerticalScrollIndicator={false}>
        {/* Info banner */}
        <View style={{
          backgroundColor: `${tokens.brandPrimary}12`,
          borderWidth: 1, borderColor: `${tokens.brandPrimary}30`,
          borderRadius: 12, padding: 14, marginBottom: 20,
        }}>
          <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 13, marginBottom: 4}}>
            How phone numbers work
          </Text>
          <Text style={{color: tokens.textSecondary, fontSize: 12, lineHeight: 18}}>
            Register your existing number — we send a 6-digit SMS code to confirm you own it.
            Once verified, leads will see your real number as the caller ID when you call them.{'\n\n'}
            Works with any country including Nigeria (+234), South Africa, Ghana, Kenya, and more.
          </Text>
        </View>

        {/* Numbers list */}
        {isLoading ? (
          <ActivityIndicator color={tokens.brandPrimary} style={{marginTop: 40}} />
        ) : !numbers?.length ? (
          <View style={{alignItems: 'center', paddingVertical: 60}}>
            <View style={{
              width: 64, height: 64, borderRadius: 32,
              backgroundColor: `${tokens.brandPrimary}1A`,
              borderWidth: 1, borderColor: `${tokens.brandPrimary}33`,
              alignItems: 'center', justifyContent: 'center', marginBottom: 16,
            }}>
              <Icon name="phone" size={24} color={tokens.brandPrimary} />
            </View>
            <Text style={{color: tokens.textPrimary, fontWeight: '800', fontSize: 16, marginBottom: 6}}>
              No numbers yet
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 13, textAlign: 'center', lineHeight: 20}}>
              Add a number to start making calls from the app.
            </Text>
          </View>
        ) : (
          <View style={{gap: 12}}>
            {numbers.map(num => (
              <NumberCard
                key={num.id}
                number={num}
                tokens={tokens}
                onActivate={() => activateMutation.mutate(num.id)}
                onRemove={() => handleRemove(num)}
                activating={activateMutation.isPending}
                removing={removeMutation.isPending}
              />
            ))}
          </View>
        )}
      </ScrollView>

      {/* Add Number Modal */}
      <Modal
        visible={modalVisible}
        animationType="slide"
        transparent
        onRequestClose={closeModal}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={{flex: 1}}>
          <Pressable style={{flex: 1, backgroundColor: '#00000066'}} onPress={closeModal} />
          <View style={{
            backgroundColor: tokens.surfaceCard,
            borderTopLeftRadius: 24, borderTopRightRadius: 24,
            paddingBottom: 40, maxHeight: '85%',
          }}>
            {/* Modal header */}
            <View style={{
              flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
              padding: 20, borderBottomWidth: 1, borderBottomColor: tokens.borderDefault,
            }}>
              <Text style={{color: tokens.textPrimary, fontSize: 17, fontWeight: '900'}}>
                {step === 'country'   && 'Select Country'}
                {step === 'details'   && 'Enter Your Number'}
                {step === 'verifying' && 'Verify Your Number'}
              </Text>
              <Pressable onPress={closeModal}>
                <Icon name="x" size={20} color={tokens.textSecondary} />
              </Pressable>
            </View>

            <ScrollView style={{padding: 20}} keyboardShouldPersistTaps="handled">

              {/* Step 1 — country picker */}
              {step === 'country' && (
                <View style={{gap: 8}}>
                  {COUNTRIES.map(country => (
                    <Pressable
                      key={country.code}
                      onPress={() => { setSelectedCountry(country); setStep('details'); }}
                      style={{
                        flexDirection: 'row', alignItems: 'center',
                        padding: 16, borderRadius: 12, borderWidth: 1,
                        borderColor: selectedCountry.code === country.code
                          ? tokens.brandPrimary : tokens.borderDefault,
                        backgroundColor: selectedCountry.code === country.code
                          ? `${tokens.brandPrimary}12` : tokens.surfaceCard,
                      }}>
                      <Text style={{fontSize: 28, marginRight: 14}}>{country.flag}</Text>
                      <View style={{flex: 1}}>
                        <Text style={{color: tokens.textPrimary, fontWeight: '700', fontSize: 14}}>
                          {country.name}
                        </Text>
                        <Text style={{color: tokens.textSecondary, fontSize: 12}}>
                          {country.dialCode}
                        </Text>
                      </View>
                      {selectedCountry.code === country.code && (
                        <Icon name="check" size={16} color={tokens.brandPrimary} />
                      )}
                    </Pressable>
                  ))}
                </View>
              )}

              {/* Step 2 — enter number */}
              {step === 'details' && (
                <View style={{gap: 16}}>
                  <View style={{
                    flexDirection: 'row', alignItems: 'center', gap: 10,
                    padding: 14, borderRadius: 12,
                    backgroundColor: `${tokens.brandPrimary}12`,
                    borderWidth: 1, borderColor: `${tokens.brandPrimary}30`,
                  }}>
                    <Text style={{fontSize: 24}}>{selectedCountry.flag}</Text>
                    <Text style={{color: tokens.textPrimary, fontWeight: '700'}}>
                      {selectedCountry.name} ({selectedCountry.dialCode})
                    </Text>
                  </View>
                  <Text style={{color: tokens.textSecondary, fontSize: 13, lineHeight: 19}}>
                    Enter your number below. We'll send a 6-digit SMS code to confirm you own it.
                  </Text>
                  <View style={{
                    flexDirection: 'row', alignItems: 'center',
                    borderWidth: 1, borderColor: tokens.borderStrong,
                    borderRadius: 10, overflow: 'hidden',
                  }}>
                    <View style={{
                      paddingHorizontal: 14, paddingVertical: 14,
                      backgroundColor: tokens.surfaceSunken,
                      borderRightWidth: 1, borderRightColor: tokens.borderDefault,
                    }}>
                      <Text style={{color: tokens.textPrimary, fontWeight: '700'}}>
                        {selectedCountry.dialCode}
                      </Text>
                    </View>
                    <TextInput
                      value={phoneInput}
                      onChangeText={setPhoneInput}
                      placeholder="8012345678"
                      placeholderTextColor={tokens.textDisabled}
                      keyboardType="phone-pad"
                      style={{
                        flex: 1, paddingHorizontal: 14, paddingVertical: 14,
                        color: tokens.textPrimary, fontSize: 15,
                      }}
                    />
                  </View>
                  <Pressable
                    onPress={handleRegister}
                    disabled={registerMutation.isPending || !phoneInput}
                    style={{
                      backgroundColor: tokens.brandPrimary,
                      padding: 16, borderRadius: 12, alignItems: 'center',
                      opacity: registerMutation.isPending ? 0.7 : 1,
                    }}>
                    {registerMutation.isPending ? (
                      <ActivityIndicator color="#fff" />
                    ) : (
                      <Text style={{color: '#fff', fontWeight: '800', fontSize: 15}}>Send Verification Code</Text>
                    )}
                  </Pressable>
                  <Pressable onPress={() => setStep('country')} style={{alignItems: 'center', paddingVertical: 8}}>
                    <Text style={{color: tokens.textSecondary, fontSize: 13}}>← Back</Text>
                  </Pressable>
                </View>
              )}

              {/* Step 3 — enter OTP */}
              {step === 'verifying' && pendingVerification && (
                <View style={{gap: 20, paddingVertical: 8}}>
                  <View style={{alignItems: 'center', gap: 12}}>
                    <View style={{
                      width: 64, height: 64, borderRadius: 32,
                      backgroundColor: `${tokens.brandPrimary}1A`,
                      borderWidth: 1, borderColor: `${tokens.brandPrimary}33`,
                      alignItems: 'center', justifyContent: 'center',
                    }}>
                      <Icon name="message-square" size={26} color={tokens.brandPrimary} />
                    </View>
                    <Text style={{color: tokens.textPrimary, fontWeight: '900', fontSize: 18, textAlign: 'center'}}>
                      Check your SMS
                    </Text>
                    <Text style={{color: tokens.textSecondary, fontSize: 13, textAlign: 'center', lineHeight: 20}}>
                      We sent a 6-digit code to{'\n'}
                      <Text style={{color: tokens.textPrimary, fontWeight: '700'}}>{pendingVerification.displayNumber}</Text>
                    </Text>
                  </View>
                  <TextInput
                    value={otpCode}
                    onChangeText={setOtpCode}
                    placeholder="000000"
                    placeholderTextColor={tokens.textDisabled}
                    keyboardType="number-pad"
                    maxLength={6}
                    style={{
                      borderWidth: 2,
                      borderColor: otpCode.length === 6 ? tokens.brandPrimary : tokens.borderStrong,
                      borderRadius: 12, padding: 16,
                      color: tokens.textPrimary, fontSize: 28,
                      fontWeight: '900', letterSpacing: 10,
                      textAlign: 'center',
                      backgroundColor: tokens.surfaceInput,
                    }}
                  />
                  <Pressable
                    onPress={() => confirmMutation.mutate({id: pendingVerification.agentNumberId, code: otpCode})}
                    disabled={confirmMutation.isPending || otpCode.length !== 6}
                    style={{
                      backgroundColor: tokens.brandPrimary,
                      padding: 16, borderRadius: 12, alignItems: 'center',
                      opacity: (confirmMutation.isPending || otpCode.length !== 6) ? 0.5 : 1,
                    }}>
                    {confirmMutation.isPending ? (
                      <ActivityIndicator color="#fff" />
                    ) : (
                      <Text style={{color: '#fff', fontWeight: '800', fontSize: 15}}>Confirm</Text>
                    )}
                  </Pressable>
                  <Pressable
                    onPress={() => resendMutation.mutate(pendingVerification.agentNumberId)}
                    disabled={resendMutation.isPending}
                    style={{alignItems: 'center', paddingVertical: 4}}>
                    <Text style={{color: tokens.brandPrimary, fontSize: 13, fontWeight: '700'}}>
                      {resendMutation.isPending ? 'Sending…' : 'Resend code'}
                    </Text>
                  </Pressable>
                  <Pressable onPress={closeModal} style={{alignItems: 'center', paddingVertical: 4}}>
                    <Text style={{color: tokens.textSecondary, fontSize: 13}}>Cancel</Text>
                  </Pressable>
                </View>
              )}
            </ScrollView>
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </SafeAreaView>
  );
}

function NumberCard({number, tokens, onActivate, onRemove, activating, removing}: {
  number: AgentNumber;
  tokens: any;
  onActivate: () => void;
  onRemove: () => void;
  activating: boolean;
  removing: boolean;
}) {
  const displayNumber = number.display_number ?? number.twilio_number ?? '—';
  const country = COUNTRIES.find(c => c.code === number.country_code);

  return (
    <View style={{
      borderWidth: 1,
      borderColor: number.active ? tokens.brandPrimary : tokens.borderDefault,
      backgroundColor: number.active ? `${tokens.brandPrimary}08` : tokens.surfaceCard,
      borderRadius: 16, padding: 16,
    }}>
      <View style={{flexDirection: 'row', alignItems: 'flex-start'}}>
        <View style={{
          width: 44, height: 44, borderRadius: 22,
          backgroundColor: `${tokens.brandPrimary}1A`,
          borderWidth: 1, borderColor: `${tokens.brandPrimary}33`,
          alignItems: 'center', justifyContent: 'center', marginRight: 14,
        }}>
          <Text style={{fontSize: 22}}>{country?.flag ?? '🌍'}</Text>
        </View>

        <View style={{flex: 1}}>
          <Text style={{color: tokens.textPrimary, fontWeight: '900', fontSize: 16}}>
            {displayNumber}
          </Text>
          <Text style={{color: tokens.textSecondary, fontSize: 12, marginTop: 2}}>
            {country?.name ?? number.country_code} · Your number
          </Text>

          <View style={{flexDirection: 'row', gap: 6, marginTop: 8, flexWrap: 'wrap'}}>
            {number.active && (
              <Badge label="Active" bg="#10B98120" border="#10B98140" text="#059669" />
            )}
            {number.verified ? (
              <Badge label="Verified" bg="#10B98120" border="#10B98140" text="#059669" />
            ) : (
              <Badge label="Pending verification" bg="#F59E0B20" border="#F59E0B40" text="#D97706" />
            )}
          </View>
        </View>
      </View>

      <View style={{flexDirection: 'row', gap: 8, marginTop: 14}}>
        {!number.active && number.verified && (
          <Pressable
            onPress={onActivate}
            disabled={activating}
            style={{
              flex: 1, padding: 10, borderRadius: 8,
              backgroundColor: tokens.brandPrimary, alignItems: 'center',
            }}>
            {activating ? (
              <ActivityIndicator size="small" color="#fff" />
            ) : (
              <Text style={{color: '#fff', fontWeight: '800', fontSize: 12}}>Set as Active</Text>
            )}
          </Pressable>
        )}
        <Pressable
          onPress={onRemove}
          disabled={removing}
          style={{
            flex: number.active || !number.verified ? 1 : 0,
            paddingHorizontal: 14, paddingVertical: 10,
            borderRadius: 8, borderWidth: 1, borderColor: '#F43F5E33',
            backgroundColor: '#F43F5E0A', alignItems: 'center',
          }}>
          {removing ? (
            <ActivityIndicator size="small" color="#F43F5E" />
          ) : (
            <Text style={{color: '#F43F5E', fontWeight: '800', fontSize: 12}}>Remove</Text>
          )}
        </Pressable>
      </View>
    </View>
  );
}

function Badge({label, bg, border, text}: {label: string; bg: string; border: string; text: string}) {
  return (
    <View style={{
      paddingHorizontal: 8, paddingVertical: 3,
      borderRadius: 6, backgroundColor: bg, borderWidth: 1, borderColor: border,
    }}>
      <Text style={{color: text, fontSize: 10, fontWeight: '800'}}>{label}</Text>
    </View>
  );
}
