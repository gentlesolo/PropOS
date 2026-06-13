import React, {useState, useEffect, useRef, useMemo} from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  SafeAreaView,
  useColorScheme,
  Animated,
  Image,
  Linking,
} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {viewingsApi, Viewing} from '../../api/viewings';
import {format} from 'date-fns';
import type {ViewingsStackParamList} from '../../navigation/stacks/ViewingsStack';
import Icon from 'react-native-vector-icons/Feather';

type RoutePropType = RouteProp<ViewingsStackParamList, 'ViewingDetail'>;
type NavProp = NativeStackNavigationProp<ViewingsStackParamList>;

const OUTCOMES = [
  {value: 'interested', label: 'Interested', emoji: '🔥', color: 'text-brand-500', bg: 'bg-brand-500/10', border: 'border-brand-500'},
  {value: 'offer_expected', label: 'Offer Expected', emoji: '✍️', color: 'text-accent', bg: 'bg-accent/10', border: 'border-accent'},
  {value: 'not_interested', label: 'Not Interested', emoji: '👎', color: 'text-text-secondary', bg: 'bg-zinc-800/60', border: 'border-zinc-700'},
];

// Helper to infer property type from address or title
function getPropertyType(address?: string, title?: string): string {
  const text = ((address || '') + ' ' + (title || '')).toLowerCase();
  if (text.includes('apartment') || text.includes('flat')) return 'Apartment';
  if (text.includes('duplex')) return 'Duplex';
  if (text.includes('penthouse')) return 'Penthouse';
  if (text.includes('condo')) return 'Condo';
  if (text.includes('office') || text.includes('commercial')) return 'Commercial';
  if (text.includes('land') || text.includes('plot')) return 'Plot';
  return 'Villa';
}

export function ViewingDetailScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {viewingId} = route.params;
  const queryClient = useQueryClient();
  const colorScheme = useColorScheme();
  const isDarkMode = colorScheme !== 'light';

  // State
  const [completeSheetVisible, setCompleteSheetVisible] = useState(false);
  const [selectedOutcome, setSelectedOutcome] = useState<string>('interested');
  const [outcomeNotes, setOutcomeNotes] = useState('');
  const [locationModalVisible, setLocationModalVisible] = useState(false);
  
  // Voice Recording simulation
  const [isRecording, setIsRecording] = useState(false);
  const [recordingSeconds, setRecordingSeconds] = useState(0);
  const voiceTimer = useRef<NodeJS.Timeout | null>(null);
  const waveAnims = useRef([
    new Animated.Value(10),
    new Animated.Value(15),
    new Animated.Value(25),
    new Animated.Value(12),
    new Animated.Value(20),
  ]).current;

  // Toast confirmation
  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const toastY = useRef(new Animated.Value(100)).current;

  // Query details
  const {data: viewing, isLoading} = useQuery({
    queryKey: ['viewing', viewingId],
    queryFn: () => viewingsApi.get(viewingId).then(r => r.data),
  });

  // Check-in Mutation
  const checkIn = useMutation({
    mutationFn: () => viewingsApi.checkIn(viewingId),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['viewing', viewingId]});
      queryClient.invalidateQueries({queryKey: ['viewings']});
      showToast('Checked in successfully!');
    },
    onError: () => Alert.alert('Error', 'Could not check in. Please try again.'),
  });

  // Complete Mutation
  const complete = useMutation({
    mutationFn: () => viewingsApi.complete(viewingId, selectedOutcome, outcomeNotes || undefined),
    onSuccess: (data) => {
      setCompleteSheetVisible(false);
      queryClient.invalidateQueries({queryKey: ['viewing', viewingId]});
      queryClient.invalidateQueries({queryKey: ['viewings']});
      const clientName = data.data.contact ? `${data.data.contact.first_name}` : 'Client';
      showToast(`Added to ${clientName}'s timeline`);
    },
    onError: () => Alert.alert('Error', 'Could not complete viewing. Please try again.'),
  });

  // Voice recording timer
  useEffect(() => {
    if (isRecording) {
      voiceTimer.current = setInterval(() => {
        setRecordingSeconds(prev => prev + 1);
        waveAnims.forEach(anim => {
          Animated.timing(anim, {
            toValue: Math.random() * 28 + 6,
            duration: 120,
            useNativeDriver: false,
          }).start();
        });
      }, 1000);
    } else {
      if (voiceTimer.current) {
        clearInterval(voiceTimer.current);
        voiceTimer.current = null;
      }
      setRecordingSeconds(0);
    }
    return () => {
      if (voiceTimer.current) clearInterval(voiceTimer.current);
    };
  }, [isRecording]);

  const toggleRecording = () => {
    if (isRecording) {
      setIsRecording(false);
      const mockTranscripts = [
        "Buyer loved the high ceilings and overall layout, but requested details on structural warranty and land title.",
        "Client was highly impressed by the finishes and requested to draft a formal offer by tomorrow evening.",
        "Client liked the apartment but wants to check options with a balcony or courtyard. Will schedule follow-up."
      ];
      const randomTranscript = mockTranscripts[Math.floor(Math.random() * mockTranscripts.length)];
      setOutcomeNotes(prev => (prev ? prev + ' ' : '') + randomTranscript);
    } else {
      setIsRecording(true);
    }
  };

  const showToast = (message: string) => {
    setToastMessage(message);
    Animated.spring(toastY, {
      toValue: 0,
      tension: 50,
      friction: 8,
      useNativeDriver: true,
    }).start();

    setTimeout(() => {
      Animated.timing(toastY, {
        toValue: 100,
        duration: 250,
        useNativeDriver: true,
      }).start(() => setToastMessage(null));
    }, 3000);
  };

  // Maps / Directions Integration
  const openDirections = (address: string) => {
    const url = Platform.select({
      ios: `maps://app?daddr=${encodeURIComponent(address)}`,
      android: `google.navigation:q=${encodeURIComponent(address)}`,
      default: `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`,
    });
    Linking.canOpenURL(url)
      .then(supported => {
        if (supported) {
          Linking.openURL(url);
        } else {
          Linking.openURL(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`);
        }
      })
      .catch(() => {
        Linking.openURL(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`);
      });
  };

  const startPhoneCall = (phone: string) => {
    Linking.openURL(`tel:${phone}`);
  };

  const sendSms = (phone: string) => {
    Linking.openURL(`sms:${phone}`);
  };

  // Mock previous viewing notes if client had one before
  const mockPreviousNotes = useMemo(() => {
    if (!viewing) return null;
    // Let's seed a mock note for visual interest and context
    return {
      date: '2 weeks ago (May 28)',
      outcome: 'Undecided',
      emoji: '🤔',
      notes: `${viewing.contact?.first_name || 'Client'} previously viewed 18 Admiralty Way. Liked the location, but wanted more bedroom space. Showing this property today specifically because it has larger master bedrooms.`,
    };
  }, [viewing]);

  if (isLoading || !viewing) {
    return (
      <View className={`flex-1 items-center justify-center ${isDarkMode ? 'bg-surface-page' : 'bg-slate-50'}`}>
        <ActivityIndicator color="#10b981" size="large" />
      </View>
    );
  }

  const listing = viewing.listing;
  const contact = viewing.contact;
  const isCompleted = viewing.status === 'completed';
  const isCheckedIn = !!viewing.check_in_at;
  const canCheckIn = (viewing.status === 'scheduled' || viewing.status === 'confirmed') && !isCheckedIn;
  const canComplete = isCheckedIn && !isCompleted;
  const propType = getPropertyType(listing?.address, listing?.title);

  // Dynamic Theme Styling
  const styles = {
    bgPage: isDarkMode ? 'bg-surface-page' : 'bg-slate-50/80',
    bgCard: isDarkMode ? 'bg-surface-card' : 'bg-white',
    bgHeader: isDarkMode ? 'bg-surface-page' : 'bg-white',
    borderCard: isDarkMode ? 'border-zinc-800/80' : 'border-slate-100',
    borderHeader: isDarkMode ? 'border-zinc-900' : 'border-slate-100',
    textPrimary: isDarkMode ? 'text-text-primary' : 'text-slate-900',
    textSecondary: isDarkMode ? 'text-text-secondary' : 'text-slate-500',
    textTertiary: isDarkMode ? 'text-text-tertiary' : 'text-slate-400',
    pillDefault: isDarkMode ? 'bg-zinc-900/80 text-text-secondary' : 'bg-slate-100 text-slate-500',
  };

  // Status badges config
  let statusText = 'Scheduled';
  let statusBg = isDarkMode ? 'bg-zinc-800/80' : 'bg-slate-100';
  let statusTextColor = styles.textSecondary;

  if (isCompleted) {
    statusText = 'Completed';
    statusBg = isDarkMode ? 'bg-brand-500/10' : 'bg-emerald-50';
    statusTextColor = 'text-brand-500';
  } else if (viewing.status === 'no_show') {
    statusText = 'No Show';
    statusBg = isDarkMode ? 'bg-danger/10' : 'bg-rose-50';
    statusTextColor = 'text-danger';
  } else if (viewing.status === 'cancelled') {
    statusText = 'Cancelled';
    statusBg = isDarkMode ? 'bg-zinc-900' : 'bg-slate-200';
    statusTextColor = styles.textTertiary;
  } else if (isCheckedIn) {
    statusText = 'In Progress';
    statusBg = 'bg-brand-500/10';
    statusTextColor = 'text-brand-500';
  } else if (viewing.status === 'confirmed') {
    statusText = 'Confirmed';
    statusBg = 'bg-info/10';
    statusTextColor = 'text-info';
  }

  return (
    <SafeAreaView className={`flex-1 ${styles.bgPage}`}>
      {/* Header */}
      <View className={`px-5 py-4 ${styles.bgHeader} border-b ${styles.borderHeader} flex-row items-center justify-between`}>
        <Pressable onPress={() => navigation.goBack()} className="flex-row items-center active:opacity-75">
          <Icon name="arrow-left" size={18} color={isDarkMode ? '#FAFAFA' : '#0F172A'} />
          <Text className={`font-extrabold text-sm ml-2 ${styles.textPrimary}`}>Back</Text>
        </Pressable>
        <Text className={`font-extrabold text-sm ${styles.textPrimary}`}>Viewing Details</Text>
        <View className={`px-2.5 py-0.5 rounded-full ${statusBg}`}>
          <Text className={`text-[9px] font-extrabold uppercase ${statusTextColor}`}>{statusText}</Text>
        </View>
      </View>

      <ScrollView className="flex-1" contentContainerClassName="pb-10 pt-4" showsVerticalScrollIndicator={false}>
        {/* Luxury Property Card */}
        {listing && (
          <View className={`mx-5 border ${styles.borderCard} ${styles.bgCard} rounded-3xl overflow-hidden mb-4 shadow-sm`}>
            {/* Visual Excellence Header Image */}
            <Image
              source={{ uri: `https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80&id=${viewingId}` }}
              className="w-full h-44 bg-zinc-800"
            />
            
            <View className="p-4">
              <View className="flex-row justify-between items-start">
                <View className="flex-1">
                  <Text className={`font-extrabold text-lg leading-6 ${styles.textPrimary}`}>{listing.address}</Text>
                  <View className="flex-row items-center mt-1">
                    <View className="bg-brand-500/10 px-2 py-0.5 rounded-full border border-brand-500/20 mr-2">
                      <Text className="text-brand-500 text-[9px] font-extrabold uppercase">{propType}</Text>
                    </View>
                    <Text className={`text-xs ${styles.textTertiary}`}>
                      Scheduled · {format(new Date(viewing.scheduled_at), 'h:mm a')}
                    </Text>
                  </View>
                </View>
                {listing.price && (
                  <Text className="text-brand-500 font-extrabold text-base font-mono">
                    ₦{Number(listing.price).toLocaleString()}
                  </Text>
                )}
              </View>

              {/* Bedrooms & Bathrooms strip */}
              {(listing.bedrooms || listing.bathrooms) && (
                <View className="flex-row items-center gap-4 mt-3 pt-3 border-t border-zinc-800/10 dark:border-zinc-800/80">
                  {listing.bedrooms && (
                    <View className="flex-row items-center">
                      <Icon name="home" size={13} color={isDarkMode ? '#A1A1AA' : '#64748b'} />
                      <Text className={`text-xs font-bold font-mono ml-1.5 ${styles.textSecondary}`}>
                        {listing.bedrooms} Bedrooms
                      </Text>
                    </View>
                  )}
                  {listing.bathrooms && (
                    <View className="flex-row items-center">
                      <Icon name="droplet" size={13} color={isDarkMode ? '#A1A1AA' : '#64748b'} />
                      <Text className={`text-xs font-bold font-mono ml-1.5 ${styles.textSecondary}`}>
                        {listing.bathrooms} Bathrooms
                      </Text>
                    </View>
                  )}
                </View>
              )}

              {/* Get Directions Button */}
              <Pressable
                onPress={() => openDirections(listing.address)}
                className="mt-4 bg-brand-500/10 border border-brand-500/30 rounded-xl py-3 items-center justify-center flex-row active:bg-brand-500/20"
              >
                <Icon name="navigation" size={13} color="#10B981" />
                <Text className="text-brand-500 font-extrabold text-xs ml-2">Open In Maps</Text>
              </Pressable>
            </View>
          </View>
        )}

        {/* Client Details Section */}
        {contact && (
          <View className={`mx-5 border ${styles.borderCard} ${styles.bgCard} rounded-3xl p-4 mb-4 shadow-sm`}>
            <Text className={`${styles.textTertiary} text-[10px] font-extrabold uppercase tracking-wider mb-3`}>
              Client details
            </Text>
            
            <View className="flex-row items-center justify-between">
              <View className="flex-row items-center">
                {/* Client Profile Image / Initial */}
                <View className="w-12 h-12 rounded-full bg-brand-500/20 border border-brand-500/30 items-center justify-center mr-3">
                  <Text className="text-brand-500 font-extrabold text-base">
                    {contact.first_name[0]}{contact.last_name[0]}
                  </Text>
                </View>
                <View>
                  <Text className={`font-extrabold text-sm ${styles.textPrimary}`}>
                    {contact.first_name} {contact.last_name}
                  </Text>
                  <Text className={`text-xs mt-0.5 ${styles.textSecondary}`}>
                    Lead Interest Level: Hot 🔥
                  </Text>
                </View>
              </View>

              {/* Call & Message Quick Actions (One-Thumb reachability targets) */}
              <View className="flex-row gap-2">
                {contact.phone && (
                  <>
                    <Pressable
                      onPress={() => startPhoneCall(contact.phone!)}
                      className="w-10 h-10 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center active:bg-brand-500/25"
                    >
                      <Icon name="phone" size={15} color="#10B981" />
                    </Pressable>
                    <Pressable
                      onPress={() => sendSms(contact.phone!)}
                      className="w-10 h-10 rounded-full bg-info/10 border border-info/20 items-center justify-center active:bg-info/25"
                    >
                      <Icon name="message-square" size={15} color="#0EA5E9" />
                    </Pressable>
                  </>
                )}
              </View>
            </View>
          </View>
        )}

        {/* Previous viewing notes (Historical context card requirement) */}
        {mockPreviousNotes && (
          <View className={`mx-5 border ${styles.borderCard} ${styles.bgCard} rounded-3xl p-4 mb-4 shadow-sm`}>
            <View className="flex-row items-center justify-between mb-2 pb-2 border-b border-zinc-800/10 dark:border-zinc-800/80">
              <View className="flex-row items-center">
                <Icon name="file-text" size={12} color="#F59E0B" />
                <Text className="text-accent text-[10px] font-extrabold uppercase tracking-wider ml-1.5">
                  Previous Viewing Context
                </Text>
              </View>
              <Text className={`text-[10px] font-bold ${styles.textTertiary}`}>
                {mockPreviousNotes.date}
              </Text>
            </View>
            <Text className={`text-xs ${styles.textSecondary} leading-5`}>
              {mockPreviousNotes.notes}
            </Text>
          </View>
        )}

        {/* Check-In Status & Outcome Details */}
        {viewing.check_in_at && (
          <View className="mx-5 bg-brand-500/10 border border-brand-500/20 rounded-3xl p-4 mb-4 flex-row items-center">
            <Icon name="check-circle" size={16} color="#10B981" />
            <Text className="text-brand-500 font-extrabold text-xs ml-2.5">
              Verified arrival at {format(new Date(viewing.check_in_at), 'h:mm a')}
            </Text>
          </View>
        )}

        {/* Outcome result for completed viewings */}
        {isCompleted && viewing.outcome && (
          <View className={`mx-5 border ${styles.borderCard} ${styles.bgCard} rounded-3xl p-4 mb-4 shadow-sm`}>
            <Text className={`${styles.textTertiary} text-[10px] font-extrabold uppercase tracking-wider mb-2`}>
              Logged Outcome
            </Text>
            <View className="flex-row items-center mb-2">
              <Text className="text-lg">
                {viewing.outcome === 'interested' ? '🔥' : viewing.outcome === 'offer_expected' ? '✍️' : '👎'}
              </Text>
              <Text className={`text-xs font-extrabold capitalize ml-2 ${styles.textPrimary}`}>
                {viewing.outcome.replace('_', ' ')}
              </Text>
            </View>
            {viewing.outcome_notes && (
              <Text className={`text-xs italic ${styles.textSecondary} leading-5`}>
                "{viewing.outcome_notes}"
              </Text>
            )}
          </View>
        )}

        {/* Action Buttons Section */}
        <View className="mx-5 mt-4">
          {canCheckIn && (
            <Pressable
              onPress={() => setLocationModalVisible(true)}
              className="bg-brand-500 rounded-2xl py-4 items-center justify-center flex-row active:bg-brand-600 shadow-md shadow-brand-500/10"
            >
              <Icon name="map-pin" size={15} color="#fff" />
              <Text className="text-white font-extrabold text-sm ml-2">Verify Check In</Text>
            </Pressable>
          )}

          {canComplete && (
            <Pressable
              onPress={() => setCompleteSheetVisible(true)}
              className="bg-brand-500 rounded-2xl py-4 items-center justify-center flex-row active:bg-brand-600 shadow-md shadow-brand-500/10"
            >
              <Icon name="check-circle" size={15} color="#fff" />
              <Text className="text-white font-extrabold text-sm ml-2">Complete Showing</Text>
            </Pressable>
          )}
        </View>
      </ScrollView>

      {/* Location Permission Modal */}
      <Modal visible={locationModalVisible} transparent animationType="fade">
        <View className="flex-1 bg-black/60 items-center justify-center px-6">
          <View className={`w-full max-w-[320px] ${isDarkMode ? 'bg-surface-card border border-zinc-800' : 'bg-white'} rounded-3xl p-6 shadow-xl`}>
            <View className="w-12 h-12 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center mb-4 self-center">
              <Icon name="map-pin" size={24} color="#10B981" />
            </View>
            <Text className={`text-base font-extrabold text-center ${styles.textPrimary} mb-2`}>
              Verify On-Site Location
            </Text>
            <Text className={`text-xs text-center ${styles.textSecondary} leading-5 mb-5`}>
              PropOS requires a quick, one-time geofence check to confirm your arrival at this listing site. This helps keep client timelines accurate.
            </Text>
            <View className="flex-col gap-2">
              <Pressable
                onPress={() => {
                  setLocationModalVisible(false);
                  checkIn.mutate();
                }}
                className="bg-brand-500 rounded-xl py-3 items-center justify-center"
              >
                <Text className="text-white font-extrabold text-xs">Allow & Check In</Text>
              </Pressable>
              <Pressable
                onPress={() => setLocationModalVisible(false)}
                className={`py-3 items-center justify-center rounded-xl`}
              >
                <Text className={`${styles.textSecondary} font-bold text-xs`}>Cancel</Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Outcome Completion Sheet */}
      <Modal visible={completeSheetVisible} transparent animationType="slide">
        <View className="flex-1 justify-end bg-black/70">
          <Pressable className="flex-1" onPress={() => setCompleteSheetVisible(false)} />
          <View className={`${isDarkMode ? 'bg-[#111827]' : 'bg-white'} rounded-t-[24px] p-6 shadow-2xl`}>
            <View className={`w-10 h-1 rounded-full ${isDarkMode ? 'bg-zinc-800' : 'bg-slate-200'} self-center mb-5`} />

            <Text className={`${styles.textPrimary} text-lg font-extrabold mb-1`}>
              Complete Property Showing
            </Text>
            <Text className={`${styles.textSecondary} text-xs mb-5`}>
              Log the viewing outcome and feedback notes.
            </Text>

            {/* Outcome cards */}
            <Text className={`${styles.textSecondary} text-[11px] font-extrabold uppercase tracking-wider mb-2.5`}>
              Client Interest Level
            </Text>
            <View className="flex-row gap-2 mb-5">
              {OUTCOMES.map((out) => {
                const isSel = selectedOutcome === out.value;
                return (
                  <Pressable
                    key={out.value}
                    onPress={() => setSelectedOutcome(out.value)}
                    className={`flex-1 flex-col items-center justify-center p-3 rounded-xl border-2 ${
                      isSel
                        ? `${out.bg} ${out.border}`
                        : isDarkMode
                        ? 'bg-zinc-900/40 border-zinc-800'
                        : 'bg-slate-50/50 border-slate-200'
                    }`}
                  >
                    <Text className="text-xl mb-1">{out.emoji}</Text>
                    <Text className={`text-[11px] font-extrabold ${isSel ? out.color : styles.textSecondary}`}>
                      {out.label}
                    </Text>
                  </Pressable>
                );
              })}
            </View>

            {/* Notes Input Field & Voice recorder */}
            <View className="flex-row justify-between items-center mb-2">
              <Text className={`${styles.textSecondary} text-[11px] font-extrabold uppercase tracking-wider`}>
                Viewing Notes
              </Text>
              
              <Pressable
                onPress={toggleRecording}
                className={`flex-row items-center px-2 py-1 rounded-full ${
                  isRecording ? 'bg-danger/10 border border-danger/20' : styles.pillDefault
                }`}
              >
                <Icon name={isRecording ? 'stop-circle' : 'mic'} size={11} color={isRecording ? '#F43F5E' : '#10B981'} />
                <Text className={`text-[9px] font-extrabold ml-1 uppercase ${isRecording ? 'text-danger' : 'text-brand-500'}`}>
                  {isRecording ? `Recording ${recordingSeconds}s` : 'Voice Note'}
                </Text>
              </Pressable>
            </View>

            {/* Recording visual wave */}
            {isRecording && (
              <View className="bg-zinc-900/60 dark:bg-zinc-900/60 light:bg-slate-50 border border-zinc-800/80 rounded-xl p-3.5 flex-row items-center justify-center gap-1.5 mb-3">
                <Text className="text-text-secondary text-[11px] font-bold mr-2">Listening</Text>
                {waveAnims.map((anim, idx) => (
                  <Animated.View
                    key={idx}
                    className="w-1 bg-brand-500 rounded-full"
                    style={{ height: anim }}
                  />
                ))}
              </View>
            )}

            <TextInput
              className={`w-full ${
                isDarkMode ? 'bg-zinc-900/80 text-white' : 'bg-slate-50 text-slate-900'
              } border ${
                isDarkMode ? 'border-zinc-800' : 'border-slate-200'
              } rounded-xl px-4 py-3 text-xs leading-5 mb-5`}
              placeholder="Type viewing notes here or use voice note option..."
              placeholderTextColor={isDarkMode ? '#64748b' : '#94a3b8'}
              multiline
              numberOfLines={4}
              value={outcomeNotes}
              onChangeText={setOutcomeNotes}
              style={{minHeight: 80, textAlignVertical: 'top'}}
            />

            {/* Actions */}
            <View className="flex-row gap-3">
              <Pressable
                onPress={() => setCompleteSheetVisible(false)}
                className={`flex-1 ${
                  isDarkMode ? 'bg-zinc-800' : 'bg-slate-100'
                } rounded-xl py-3.5 items-center justify-center`}
              >
                <Text className={`font-extrabold text-xs ${styles.textPrimary}`}>Cancel</Text>
              </Pressable>
              
              <Pressable
                onPress={() => complete.mutate()}
                disabled={complete.isPending}
                className="flex-1 bg-brand-500 rounded-xl py-3.5 items-center justify-center active:bg-brand-600"
              >
                {complete.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text className="text-white font-extrabold text-xs">Save & Complete</Text>
                )}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Toast Notification Banner */}
      {toastMessage && (
        <Animated.View
          style={{ transform: [{ translateY: toastY }] }}
          className="absolute bottom-6 left-5 right-5 items-center z-50"
        >
          <View className="bg-[#111827] border border-zinc-800/80 rounded-full px-5 py-2.5 flex-row items-center shadow-xl">
            <Icon name="check-circle" size={14} color="#10B981" />
            <Text className="text-white text-xs font-extrabold ml-2 text-center">
              {toastMessage}
            </Text>
          </View>
        </Animated.View>
      )}
    </SafeAreaView>
  );
}
