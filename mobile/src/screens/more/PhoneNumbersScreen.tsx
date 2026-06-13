import React, {useCallback, useEffect, useRef, useState} from 'react';
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
import {numbersApi, RegisterNumberPayload} from '../../api/numbers';
import {AgentNumber} from '../../types';
import {useTheme} from '../../theme/ThemeProvider';

// Countries where the platform can provision Twilio numbers or verify BYON.
// twilioProvisionable = Twilio sells local numbers there without extra regulatory bundles.
const COUNTRIES = [
  {name: 'Nigeria',       code: 'NG', dialCode: '+234', flag: '🇳🇬', twilioProvisionable: false},
  {name: 'South Africa',  code: 'ZA', dialCode: '+27',  flag: '🇿🇦', twilioProvisionable: true},
  {name: 'Ghana',         code: 'GH', dialCode: '+233', flag: '🇬🇭', twilioProvisionable: true},
  {name: 'Kenya',         code: 'KE', dialCode: '+254', flag: '🇰🇪', twilioProvisionable: true},
  {name: 'United Kingdom',code: 'GB', dialCode: '+44',  flag: '🇬🇧', twilioProvisionable: true},
  {name: 'United States', code: 'US', dialCode: '+1',   flag: '🇺🇸', twilioProvisionable: true},
  {name: 'Canada',        code: 'CA', dialCode: '+1',   flag: '🇨🇦', twilioProvisionable: true},
];

type Step = 'type' | 'country' | 'details' | 'verifying';

interface PendingVerification {
  agentNumberId: number;
  displayNumber: string;
  validationCode: string;
}

export function PhoneNumbersScreen() {
  const {tokens} = useTheme();
  const navigation = useNavigation();
  const queryClient = useQueryClient();

  const [modalVisible, setModalVisible] = useState(false);
  const [step, setStep] = useState<Step>('type');
  const [selectedType, setSelectedType] = useState<'twilio_provisioned' | 'verified_caller_id' | null>(null);
  const [selectedCountry, setSelectedCountry] = useState(COUNTRIES[0]);
  const [phoneInput, setPhoneInput] = useState('');
  const [areaCode, setAreaCode] = useState('');
  const [pendingVerification, setPendingVerification] = useState<PendingVerification | null>(null);
  const pollIntervalRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const pollCountRef = useRef(0);

  const {data: numbers, isLoading} = useQuery({
    queryKey: ['agent-numbers'],
    queryFn: () => numbersApi.list().then(r => r.data),
  });

  const registerMutation = useMutation({
    mutationFn: (payload: RegisterNumberPayload) => numbersApi.register(payload),
    onSuccess: (response) => {
      queryClient.invalidateQueries({queryKey: ['agent-numbers']});
      const data = response.data;
      if (data.number_type === 'verified_caller_id' && data.validation_code) {
        setPendingVerification({
          agentNumberId: data.id,
          displayNumber: data.display_number ?? '',
          validationCode: data.validation_code,
        });
        setStep('verifying');
      } else {
        closeModal();
      }
    },
    onError: (error: any) => {
      const msg = error?.response?.data?.message ?? 'Something went wrong. Please try again.';
      Alert.alert('Registration failed', msg);
    },
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

  // Poll Twilio every 5 s to check if BYON verification completed (max 2 min)
  const startPolling = useCallback((agentNumberId: number) => {
    pollCountRef.current = 0;
    pollIntervalRef.current = setInterval(async () => {
      pollCountRef.current += 1;
      if (pollCountRef.current > 24) {
        clearInterval(pollIntervalRef.current!);
        return;
      }
      try {
        const res = await numbersApi.checkVerification(agentNumberId);
        if (res.data.verified) {
          clearInterval(pollIntervalRef.current!);
          queryClient.invalidateQueries({queryKey: ['agent-numbers']});
          closeModal();
          Alert.alert('Number verified!', 'Your number is now active and ready for calls.');
        }
      } catch (_) {}
    }, 5000);
  }, [queryClient]);

  useEffect(() => {
    if (step === 'verifying' && pendingVerification) {
      startPolling(pendingVerification.agentNumberId);
    }
    return () => {
      if (pollIntervalRef.current) clearInterval(pollIntervalRef.current);
    };
  }, [step, pendingVerification, startPolling]);

  const closeModal = () => {
    if (pollIntervalRef.current) clearInterval(pollIntervalRef.current);
    setModalVisible(false);
    setStep('type');
    setSelectedType(null);
    setSelectedCountry(COUNTRIES[0]);
    setPhoneInput('');
    setAreaCode('');
    setPendingVerification(null);
  };

  const handleRegister = () => {
    if (!selectedType || !selectedCountry) return;

    const payload: RegisterNumberPayload = {
      type: selectedType,
      country_code: selectedCountry.code,
    };

    if (selectedType === 'verified_caller_id') {
      const fullNumber = `${selectedCountry.dialCode}${phoneInput.replace(/^0+/, '')}`;
      payload.number = fullNumber;
    } else {
      if (areaCode) payload.area_code = areaCode;
    }

    registerMutation.mutate(payload);
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

  const canProceedToDetails = selectedType !== null && (
    selectedType === 'twilio_provisioned' ? selectedCountry.twilioProvisionable : true
  );

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
            <Text style={{fontWeight: '700'}}>Use your own number</Text> — we verify your existing number and use it as your caller ID.
            Leads will see your real number when you call them.{'\n\n'}
            <Text style={{fontWeight: '700'}}>Get a platform number</Text> — we provision a Twilio number in your country.
            Best for teams who want a dedicated calling line.{'\n\n'}
            For Nigerian numbers, use <Text style={{fontWeight: '700'}}>"Use your own number"</Text> — Twilio's direct provisioning for NG requires additional business registration.
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
                {step === 'type'      && 'Add a Phone Number'}
                {step === 'country'   && 'Select Country'}
                {step === 'details'   && (selectedType === 'verified_caller_id' ? 'Enter Your Number' : 'Configure Number')}
                {step === 'verifying' && 'Verify Your Number'}
              </Text>
              <Pressable onPress={closeModal}>
                <Icon name="x" size={20} color={tokens.textSecondary} />
              </Pressable>
            </View>

            <ScrollView style={{padding: 20}} keyboardShouldPersistTaps="handled">

              {/* Step 1 — choose type */}
              {step === 'type' && (
                <View style={{gap: 12}}>
                  <TypeOption
                    title="Use my existing number"
                    subtitle="Verify a number you already own. Works with any country including Nigeria."
                    icon="smartphone"
                    selected={selectedType === 'verified_caller_id'}
                    onPress={() => setSelectedType('verified_caller_id')}
                    tokens={tokens}
                  />
                  <TypeOption
                    title="Get a new platform number"
                    subtitle="We purchase a Twilio number in your country. Available in ZA, GH, KE, GB, US, CA."
                    icon="phone-call"
                    selected={selectedType === 'twilio_provisioned'}
                    onPress={() => setSelectedType('twilio_provisioned')}
                    tokens={tokens}
                  />
                  <Pressable
                    onPress={() => selectedType && setStep('country')}
                    style={{
                      backgroundColor: selectedType ? tokens.brandPrimary : tokens.surfaceSunken,
                      padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 8,
                    }}>
                    <Text style={{
                      color: selectedType ? '#fff' : tokens.textSecondary,
                      fontWeight: '800', fontSize: 15,
                    }}>Continue</Text>
                  </Pressable>
                </View>
              )}

              {/* Step 2 — country picker */}
              {step === 'country' && (
                <View style={{gap: 8}}>
                  {COUNTRIES.filter(c =>
                    selectedType === 'twilio_provisioned' ? c.twilioProvisionable : true
                  ).map(country => (
                    <Pressable
                      key={country.code}
                      onPress={() => { setSelectedCountry(country); setStep('details'); }}
                      style={{
                        flexDirection: 'row', alignItems: 'center',
                        padding: 16, borderRadius: 12,
                        borderWidth: 1,
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
                  <Pressable onPress={() => setStep('type')} style={{alignItems: 'center', paddingVertical: 12}}>
                    <Text style={{color: tokens.textSecondary, fontSize: 13}}>← Back</Text>
                  </Pressable>
                </View>
              )}

              {/* Step 3 — details */}
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

                  {selectedType === 'verified_caller_id' ? (
                    <>
                      <Text style={{color: tokens.textSecondary, fontSize: 13, lineHeight: 19}}>
                        Enter your number below. We'll call it and ask you to enter a short code on your keypad to confirm you own it.
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
                    </>
                  ) : (
                    <>
                      <Text style={{color: tokens.textSecondary, fontSize: 13, lineHeight: 19}}>
                        We'll find an available local number in {selectedCountry.name} and assign it to you.
                        Optionally enter an area code to request a specific region.
                      </Text>
                      <TextInput
                        value={areaCode}
                        onChangeText={setAreaCode}
                        placeholder="Area code (optional)"
                        placeholderTextColor={tokens.textDisabled}
                        keyboardType="number-pad"
                        style={{
                          borderWidth: 1, borderColor: tokens.borderStrong,
                          borderRadius: 10, padding: 14,
                          color: tokens.textPrimary, fontSize: 15,
                        }}
                      />
                    </>
                  )}

                  <Pressable
                    onPress={handleRegister}
                    disabled={registerMutation.isPending || (selectedType === 'verified_caller_id' && !phoneInput)}
                    style={{
                      backgroundColor: tokens.brandPrimary,
                      padding: 16, borderRadius: 12, alignItems: 'center',
                      opacity: registerMutation.isPending ? 0.7 : 1,
                    }}>
                    {registerMutation.isPending ? (
                      <ActivityIndicator color="#fff" />
                    ) : (
                      <Text style={{color: '#fff', fontWeight: '800', fontSize: 15}}>
                        {selectedType === 'verified_caller_id' ? 'Verify My Number' : 'Get a Number'}
                      </Text>
                    )}
                  </Pressable>
                  <Pressable onPress={() => setStep('country')} style={{alignItems: 'center', paddingVertical: 8}}>
                    <Text style={{color: tokens.textSecondary, fontSize: 13}}>← Back</Text>
                  </Pressable>
                </View>
              )}

              {/* Step 4 — verification pending */}
              {step === 'verifying' && pendingVerification && (
                <View style={{alignItems: 'center', gap: 20, paddingVertical: 8}}>
                  <View style={{
                    width: 72, height: 72, borderRadius: 36,
                    backgroundColor: `${tokens.brandPrimary}1A`,
                    borderWidth: 1, borderColor: `${tokens.brandPrimary}33`,
                    alignItems: 'center', justifyContent: 'center',
                  }}>
                    <Icon name="phone-incoming" size={28} color={tokens.brandPrimary} />
                  </View>
                  <Text style={{color: tokens.textPrimary, fontWeight: '900', fontSize: 18, textAlign: 'center'}}>
                    We're calling you now
                  </Text>
                  <Text style={{color: tokens.textSecondary, fontSize: 13, textAlign: 'center', lineHeight: 20}}>
                    Answer the call to {pendingVerification.displayNumber}.{'\n'}
                    When prompted, enter this code on your keypad:
                  </Text>
                  <View style={{
                    backgroundColor: tokens.surfaceSunken,
                    borderWidth: 2, borderColor: tokens.brandPrimary,
                    borderRadius: 16, paddingHorizontal: 32, paddingVertical: 20,
                  }}>
                    <Text style={{
                      color: tokens.brandPrimary, fontSize: 40,
                      fontWeight: '900', letterSpacing: 8, textAlign: 'center',
                    }}>
                      {pendingVerification.validationCode}
                    </Text>
                  </View>
                  <View style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
                    <ActivityIndicator size="small" color={tokens.brandPrimary} />
                    <Text style={{color: tokens.textSecondary, fontSize: 12}}>
                      Waiting for confirmation…
                    </Text>
                  </View>
                  <Text style={{color: tokens.textDisabled, fontSize: 11, textAlign: 'center'}}>
                    This window will close automatically once your number is verified.
                    The call may take up to 30 seconds to arrive.
                  </Text>
                  <Pressable onPress={closeModal} style={{paddingVertical: 8}}>
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
  const isProvisioned = number.number_type === 'twilio_provisioned';

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
            {country?.name ?? number.country_code} · {isProvisioned ? 'Platform number' : 'Your number'}
          </Text>

          {/* Status badges */}
          <View style={{flexDirection: 'row', gap: 6, marginTop: 8, flexWrap: 'wrap'}}>
            {number.active && (
              <Badge label="Active" bg="#10B98120" border="#10B98140" text="#059669" />
            )}
            {number.verified ? (
              <Badge label="Verified" bg="#10B98120" border="#10B98140" text="#059669" />
            ) : (
              <Badge label="Pending verification" bg="#F59E0B20" border="#F59E0B40" text="#D97706" />
            )}
            {isProvisioned && (
              <Badge label="Twilio" bg={`${tokens.brandPrimary}15`} border={`${tokens.brandPrimary}30`} text={tokens.brandPrimary} />
            )}
          </View>
        </View>
      </View>

      {/* Actions */}
      <View style={{flexDirection: 'row', gap: 8, marginTop: 14}}>
        {!number.active && number.verified && (
          <Pressable
            onPress={onActivate}
            disabled={activating}
            style={{
              flex: 1, padding: 10, borderRadius: 8,
              backgroundColor: tokens.brandPrimary,
              alignItems: 'center',
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

function TypeOption({title, subtitle, icon, selected, onPress, tokens}: {
  title: string;
  subtitle: string;
  icon: string;
  selected: boolean;
  onPress: () => void;
  tokens: any;
}) {
  return (
    <Pressable
      onPress={onPress}
      style={{
        borderWidth: 2,
        borderColor: selected ? tokens.brandPrimary : tokens.borderDefault,
        backgroundColor: selected ? `${tokens.brandPrimary}08` : tokens.surfaceCard,
        borderRadius: 14, padding: 16,
        flexDirection: 'row', alignItems: 'flex-start', gap: 14,
      }}>
      <View style={{
        width: 40, height: 40, borderRadius: 20,
        backgroundColor: selected ? `${tokens.brandPrimary}1A` : tokens.surfaceSunken,
        borderWidth: 1,
        borderColor: selected ? `${tokens.brandPrimary}33` : tokens.borderDefault,
        alignItems: 'center', justifyContent: 'center', flexShrink: 0,
      }}>
        <Icon name={icon} size={18} color={selected ? tokens.brandPrimary : tokens.textSecondary} />
      </View>
      <View style={{flex: 1}}>
        <Text style={{color: tokens.textPrimary, fontWeight: '800', fontSize: 14, marginBottom: 4}}>
          {title}
        </Text>
        <Text style={{color: tokens.textSecondary, fontSize: 12, lineHeight: 17}}>
          {subtitle}
        </Text>
      </View>
      {selected && <Icon name="check-circle" size={18} color={tokens.brandPrimary} style={{flexShrink: 0}} />}
    </Pressable>
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
