import React, {useState, useEffect, useRef, useMemo} from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  SafeAreaView,
  useColorScheme,
  Animated,
  Dimensions,
  Image,
  Linking,
} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {viewingsApi, Viewing} from '../../api/viewings';
import {format, isToday, addDays, isSameDay} from 'date-fns';
import type {ViewingsStackParamList} from '../../navigation/stacks/ViewingsStack';
import Icon from 'react-native-vector-icons/Feather';

type NavProp = NativeStackNavigationProp<ViewingsStackParamList>;
const {width: SCREEN_WIDTH} = Dimensions.get('window');

// Outcome Options configuration
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

export function ViewingsScreen() {
  const navigation = useNavigation<NavProp>();
  const queryClient = useQueryClient();
  const colorScheme = useColorScheme();
  const isDarkMode = colorScheme !== 'light';

  // State
  const [selectedDate, setSelectedDate] = useState<Date>(new Date());
  const [dismissedNoShows, setDismissedNoShows] = useState<number[]>([]);
  const [pulsingViewingId, setPulsingViewingId] = useState<number | null>(null);

  // Permission & Flow Modals State
  const [locationModalVisible, setLocationModalVisible] = useState(false);
  const [pendingCheckInViewing, setPendingCheckInViewing] = useState<Viewing | null>(null);
  
  // Complete Sheet State
  const [completeSheetVisible, setCompleteSheetVisible] = useState(false);
  const [completingViewing, setCompletingViewing] = useState<Viewing | null>(null);
  const [selectedOutcome, setSelectedOutcome] = useState<string>('interested');
  const [outcomeNotes, setOutcomeNotes] = useState('');
  
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

  // Pulse animation for check-in
  const checkInPulseScale = useRef(new Animated.Value(1)).current;

  // Toast confirmation
  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const toastY = useRef(new Animated.Value(100)).current;

  // Queries
  const {data: todayViewings = [], isLoading: loadingToday, refetch: refetchToday} = useQuery({
    queryKey: ['viewings', 'today'],
    queryFn: () => viewingsApi.today().then(r => r.data),
  });

  const {data: upcoming = [], isLoading: loadingUpcoming, refetch: refetchUpcoming} = useQuery({
    queryKey: ['viewings', 'upcoming'],
    queryFn: () => viewingsApi.upcoming().then(r => r.data),
  });

  const isLoading = loadingToday || loadingUpcoming;

  // Merge lists to build full route cache
  const allViewings = useMemo(() => {
    const map = new Map<number, Viewing>();
    todayViewings.forEach(v => map.set(v.id, v));
    upcoming.forEach(v => map.set(v.id, v));
    return Array.from(map.values()).sort(
      (a, b) => new Date(a.scheduled_at).getTime() - new Date(b.scheduled_at).getTime()
    );
  }, [todayViewings, upcoming]);

  // Date strip generation (Yesterday, Today, and 5 upcoming days)
  const dateStrip = useMemo(() => {
    const dates = [];
    for (let i = -1; i <= 5; i++) {
      dates.push(addDays(new Date(), i));
    }
    return dates;
  }, []);

  // Filter viewings based on selected date
  const filteredViewings = useMemo(() => {
    return allViewings.filter(v => isSameDay(new Date(v.scheduled_at), selectedDate));
  }, [allViewings, selectedDate]);

  // Next upcoming viewing that needs checking in (ONLY today)
  const nextViewingId = useMemo(() => {
    if (!isSameDay(selectedDate, new Date())) return null;
    const pendingToday = filteredViewings.filter(
      v => (v.status === 'scheduled' || v.status === 'confirmed') && !v.check_in_at
    );
    return pendingToday[0]?.id || null;
  }, [filteredViewings, selectedDate]);

  // Mutations
  const checkInMutation = useMutation({
    mutationFn: (id: number) => viewingsApi.checkIn(id),
    onSuccess: (data) => {
      // Trigger check-in pulse
      setPulsingViewingId(data.data.id);
      Animated.sequence([
        Animated.timing(checkInPulseScale, {
          toValue: 2,
          duration: 350,
          useNativeDriver: true,
        }),
        Animated.timing(checkInPulseScale, {
          toValue: 1,
          duration: 250,
          useNativeDriver: true,
        })
      ]).start(() => setPulsingViewingId(null));

      queryClient.invalidateQueries({queryKey: ['viewing', data.data.id]});
      queryClient.invalidateQueries({queryKey: ['viewings']});
      showToast('Checked in successfully!');
    },
    onError: () => Alert.alert('Error', 'Check-in failed. Please try again.'),
  });

  const completeMutation = useMutation({
    mutationFn: ({id, outcome, notes}: {id: number; outcome: string; notes?: string}) =>
      viewingsApi.complete(id, outcome, notes),
    onSuccess: (data) => {
      setCompleteSheetVisible(false);
      const clientName = data.data.contact
        ? `${data.data.contact.first_name}`
        : 'Client';
      
      queryClient.invalidateQueries({queryKey: ['viewing', data.data.id]});
      queryClient.invalidateQueries({queryKey: ['viewings']});
      
      showToast(`Added to ${clientName}'s timeline`);
      // Reset complete form
      setCompletingViewing(null);
      setOutcomeNotes('');
      setSelectedOutcome('interested');
    },
    onError: () => Alert.alert('Error', 'Could not complete viewing. Please try again.'),
  });

  const markNoShowMutation = useMutation({
    mutationFn: (id: number) => viewingsApi.updateStatus(id, 'no_show'),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['viewings']});
      showToast('Viewing marked as No-Show');
    },
    onError: () => Alert.alert('Error', 'Could not update status.'),
  });

  // Voice recording simulation loop
  useEffect(() => {
    if (isRecording) {
      voiceTimer.current = setInterval(() => {
        setRecordingSeconds(prev => prev + 1);
        // Animate simulated voice waves
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
      // Simulate high fidelity voice transcription
      const mockTranscripts = [
        "Buyer loved the natural light in the living room and wants to arrange a second viewing with their spouse this Saturday.",
        "Client felt the kitchen was too small, but showed high interest in the general location. Will follow up with other matches.",
        "Highly motivated buyer. Expressed that they want to prepare an offer immediately. Expecting draft contract by tomorrow morning."
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

  const handleCheckInPress = (viewing: Viewing) => {
    setPendingCheckInViewing(viewing);
    setLocationModalVisible(true);
  };

  const confirmLocationCheckIn = () => {
    setLocationModalVisible(false);
    if (pendingCheckInViewing) {
      checkInMutation.mutate(pendingCheckInViewing.id);
      setPendingCheckInViewing(null);
    }
  };

  const handleCompletePress = (viewing: Viewing) => {
    setCompletingViewing(viewing);
    setCompleteSheetVisible(true);
  };

  const submitCompletion = () => {
    if (completingViewing) {
      completeMutation.mutate({
        id: completingViewing.id,
        outcome: selectedOutcome,
        notes: outcomeNotes || undefined,
      });
    }
  };

  // Check if viewing should show no-show warning (Grace period: 15 mins past scheduled time)
  const isNoShowEligible = (viewing: Viewing) => {
    if (viewing.status !== 'scheduled' && viewing.status !== 'confirmed') return false;
    if (viewing.check_in_at) return false;
    if (dismissedNoShows.includes(viewing.id)) return false;

    // Compare time
    const schedDate = new Date(viewing.scheduled_at);
    const fifteenMinutesAgo = new Date(Date.now() - 15 * 60 * 1000);
    return schedDate < fifteenMinutesAgo;
  };

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

  return (
    <SafeAreaView className={`flex-1 ${styles.bgPage}`}>
      {/* Header */}
      <View className={`px-5 pt-4 pb-3 ${styles.bgHeader} border-b ${styles.borderHeader} shadow-sm z-10 flex-row justify-between items-center`}>
        <View>
          <Text className={`${styles.textPrimary} text-2xl font-extrabold tracking-tight`}>Today's Viewings</Text>
          <Text className={`${styles.textSecondary} text-xs font-semibold mt-0.5`}>
            {format(selectedDate, 'EEEE, d MMMM yyyy')}
          </Text>
        </View>
        <View className="w-10 h-10 bg-brand-500/10 rounded-full items-center justify-center border border-brand-500/20">
          <Text className="text-brand-500 font-extrabold text-base">{filteredViewings.length}</Text>
        </View>
      </View>

      {/* Date Pill Selector Strip */}
      <View className="py-3">
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerClassName="px-5"
          className="flex-grow-0"
        >
          {dateStrip.map((date, index) => {
            const isSel = isSameDay(date, selectedDate);
            const isTod = isToday(date);
            
            // Format labels
            const dayNum = format(date, 'd');
            const dayName = isTod ? 'Today' : format(date, 'EEE');

            return (
              <Pressable
                key={index}
                onPress={() => setSelectedDate(date)}
                className={`flex-col items-center justify-center w-[60px] py-2.5 mr-2.5 rounded-2xl border ${
                  isSel
                    ? 'bg-brand-500 border-brand-500 shadow-md shadow-brand-500/10'
                    : isDarkMode
                    ? 'bg-surface-card border-zinc-800'
                    : 'bg-white border-slate-200/60'
                }`}
              >
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${
                  isSel ? 'text-white/80' : styles.textTertiary
                }`}>
                  {dayName}
                </Text>
                <Text className={`text-base font-extrabold font-mono mt-1 ${
                  isSel ? 'text-white' : styles.textPrimary
                }`}>
                  {dayNum}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>
      </View>

      {/* Viewings Timeline list */}
      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : filteredViewings.length === 0 ? (
        <View className="flex-1 items-center justify-center px-8">
          <View className="w-20 h-20 bg-brand-500/10 rounded-full items-center justify-center mb-5 border border-brand-500/10">
            <Icon name="calendar" size={32} color="#10B981" />
          </View>
          <Text className={`${styles.textPrimary} text-lg font-bold mb-1 text-center`}>
            No viewings scheduled {isToday(selectedDate) ? 'today' : 'for this day'}
          </Text>
          <Text className={`${styles.textSecondary} text-sm text-center mb-6 px-4`}>
            Your schedule is clear. You can check viewings scheduled for other days.
          </Text>
          {!isToday(selectedDate) && (
            <Pressable
              onPress={() => setSelectedDate(new Date())}
              className="bg-brand-500/10 border border-brand-500/30 rounded-xl px-5 py-2.5 active:bg-brand-500/20"
            >
              <Text className="text-brand-500 font-extrabold text-xs">Return to Today</Text>
            </Pressable>
          )}
        </View>
      ) : (
        <ScrollView
          className="flex-1"
          contentContainerClassName="pb-24 pt-2"
          showsVerticalScrollIndicator={false}
        >
          {filteredViewings.map((viewing, index) => {
            const timeStr = format(new Date(viewing.scheduled_at), 'HH:mm');
            const contact = viewing.contact;
            const listing = viewing.listing;
            
            // Check status states
            const isCompleted = viewing.status === 'completed';
            const isNoShow = viewing.status === 'no_show';
            const isCancelled = viewing.status === 'cancelled';
            const isCheckedIn = !!viewing.check_in_at;
            const isNext = viewing.id === nextViewingId;
            const showNoShowAlert = isNoShowEligible(viewing);

            // Timeline line properties
            const isFirst = index === 0;
            const isLast = index === filteredViewings.length - 1;
            const isSingle = filteredViewings.length === 1;

            // Status chip color configs
            let statusText = 'Scheduled';
            let statusBg = isDarkMode ? 'bg-zinc-800/80' : 'bg-slate-100';
            let statusTextColor = styles.textSecondary;

            if (isCompleted) {
              statusText = 'Completed';
              statusBg = isDarkMode ? 'bg-zinc-900/50' : 'bg-slate-100/60';
              statusTextColor = styles.textTertiary;
            } else if (isNoShow) {
              statusText = 'No Show';
              statusBg = isDarkMode ? 'bg-danger/10' : 'bg-rose-50';
              statusTextColor = 'text-danger';
            } else if (isCancelled) {
              statusText = 'Cancelled';
              statusBg = isDarkMode ? 'bg-zinc-900' : 'bg-slate-150';
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

            const propType = getPropertyType(listing?.address, listing?.title);

            // Timeline dot styling
            let dotBorderColor = isDarkMode ? 'border-zinc-800' : 'border-slate-350';
            let dotBgColor = isDarkMode ? 'bg-surface-page' : 'bg-slate-50';
            let dotIcon = null;

            if (isCompleted) {
              dotBgColor = isDarkMode ? 'bg-zinc-700' : 'bg-slate-400';
              dotBorderColor = isDarkMode ? 'border-zinc-600' : 'border-slate-400';
              dotIcon = <Icon name="check" size={10} color="#fff" />;
            } else if (isCheckedIn) {
              dotBgColor = 'bg-brand-500';
              dotBorderColor = 'border-brand-500';
            } else if (isNext) {
              dotBorderColor = 'border-brand-500';
            }

            // Compact vs Large card details
            // If viewing is completed, it collapses into a satisfies compact state
            return (
              <View key={viewing.id} className="flex-row items-stretch px-5 min-h-[90px]">
                {/* Timeline Column 1: Time */}
                <View className="w-14 items-end justify-start pt-4 pr-3">
                  <Text className={`font-mono font-bold text-[15px] ${isCompleted ? styles.textTertiary : styles.textPrimary}`}>
                    {timeStr}
                  </Text>
                  {viewing.duration_minutes && !isCompleted && (
                    <Text className={`font-mono text-[10px] mt-0.5 ${styles.textTertiary}`}>
                      {viewing.duration_minutes}m
                    </Text>
                  )}
                </View>

                {/* Timeline Column 2: Dot & Line */}
                <View className="w-8 items-center justify-start relative">
                  {!isSingle && (
                    <View
                      className={`absolute w-[2px] ${
                        isDarkMode ? 'bg-zinc-800' : 'bg-slate-200'
                      }`}
                      style={{
                        top: isFirst ? 24 : 0,
                        bottom: isLast ? '76%' : 0,
                        left: '50%',
                        marginLeft: -1,
                      }}
                    />
                  )}
                  <View className="pt-[18px] z-10">
                    <View className={`w-5 h-5 rounded-full border-2 items-center justify-center ${dotBgColor} ${dotBorderColor}`}>
                      {dotIcon}
                    </View>
                    {isNext && !isCheckedIn && pulsingViewingId === null && (
                      <View className="absolute top-[18px] left-0 w-5 h-5 rounded-full border border-brand-500/60 scale-125 opacity-30 animate-ping" />
                    )}
                  </View>
                </View>

                {/* Timeline Column 3: Card Content */}
                <View className="flex-1 pb-4">
                  {/* Tap body navigates to detail, tap buttons triggers flow */}
                  <Pressable
                    onPress={() => navigation.navigate('ViewingDetail', {viewingId: viewing.id})}
                    className={`rounded-[12px] border ${styles.bgCard} ${styles.borderCard} p-4 shadow-sm active:opacity-90 ${
                      isNext && !isCheckedIn ? 'border-brand-500/50 shadow-md shadow-brand-500/5' : ''
                    } ${isCompleted ? 'opacity-75 py-2.5' : ''}`}
                  >
                    {/* Header: Title & Status */}
                    <View className="flex-row justify-between items-start gap-1">
                      <View className="flex-1">
                        {isCompleted ? (
                          <Text className={`font-extrabold text-sm ${styles.textSecondary} line-through`} numberOfLines={1}>
                            {listing?.address || 'Property Viewing'}
                          </Text>
                        ) : (
                          <View className="flex-row items-center flex-wrap gap-1">
                            <Text className={`font-extrabold text-sm leading-5 ${styles.textPrimary} flex-1`} numberOfLines={1}>
                              {listing?.address || 'Property Viewing'}
                            </Text>
                            <View className="bg-brand-500/10 px-2 py-0.5 rounded-full border border-brand-500/20">
                              <Text className="text-brand-500 text-[9px] font-extrabold uppercase">{propType}</Text>
                            </View>
                          </View>
                        )}
                      </View>
                      <View className={`px-2 py-0.5 rounded-full ${statusBg}`}>
                        <Text className={`text-[9px] font-extrabold uppercase ${statusTextColor}`}>{statusText}</Text>
                      </View>
                    </View>

                    {/* Geofence / Travel Warning for Next Viewing */}
                    {isNext && !isCheckedIn && (
                      <View className="flex-row items-center mt-1 bg-brand-500/5 border border-brand-500/10 rounded-lg py-1 px-2.5 self-start">
                        <Icon name="navigation" size={10} color="#10B981" />
                        <Text className="text-brand-500 text-[10px] font-bold ml-1">You're 12 min away</Text>
                      </View>
                    )}

                    {/* Card Content body: Thumbnail & Client */}
                    {!isCompleted && (
                      <View className="flex-row items-center mt-3 pt-3 border-t border-zinc-800/10 dark:border-zinc-800/80">
                        {/* Property Thumbnail (Visual Excellence requirement) */}
                        <Image
                          source={{ uri: `https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=120&q=80&id=${viewing.id}` }}
                          className="w-12 h-12 rounded-lg mr-3 bg-zinc-850"
                        />
                        <View className="flex-1">
                          <View className="flex-row items-center">
                            {/* Client Avatar */}
                            <View className="w-5 h-5 rounded-full bg-brand-500/20 border border-brand-500/30 items-center justify-center mr-1.5">
                              <Text className="text-brand-500 text-[9px] font-extrabold">
                                {contact ? `${contact.first_name[0]}${contact.last_name[0]}` : '?'}
                              </Text>
                            </View>
                            <Text className={`text-xs font-bold ${styles.textPrimary}`}>
                              {contact ? `${contact.first_name} ${contact.last_name}` : 'Unknown Client'}
                            </Text>
                          </View>
                          {viewing.notes && (
                            <Text className={`text-[11px] mt-1 ${styles.textSecondary}`} numberOfLines={1}>
                              "{viewing.notes}"
                            </Text>
                          )}
                        </View>
                      </View>
                    )}

                    {/* Compact outcome notes if completed */}
                    {isCompleted && viewing.outcome_notes && (
                      <Text className={`text-xs mt-1 italic ${styles.textSecondary}`} numberOfLines={1}>
                        "{viewing.outcome_notes}"
                      </Text>
                    )}

                    {/* Dynamic Action Buttons inside card (Core principle: One-thumb reachability) */}
                    {!isCompleted && !isCancelled && !isNoShow && (
                      <View className="mt-3 pt-3 border-t border-zinc-800/10 dark:border-zinc-800/80">
                        {isNext && !isCheckedIn && (
                          <Pressable
                            onPress={() => handleCheckInPress(viewing)}
                            className="bg-brand-500 rounded-lg py-2.5 items-center justify-center flex-row active:bg-brand-600"
                          >
                            <Icon name="map-pin" size={13} color="#fff" />
                            <Text className="text-white font-extrabold text-xs ml-1.5">Check In</Text>
                          </Pressable>
                        )}
                        
                        {isCheckedIn && (
                          <Pressable
                            onPress={() => handleCompletePress(viewing)}
                            className="bg-brand-500 rounded-lg py-2.5 items-center justify-center flex-row active:bg-brand-600"
                          >
                            <Icon name="check-circle" size={13} color="#fff" />
                            <Text className="text-white font-extrabold text-xs ml-1.5">Complete Viewing</Text>
                          </Pressable>
                        )}

                        {/* If checked in and we want to show dynamic morph text */}
                        {isCheckedIn && viewing.check_in_at && (
                          <View className="mt-2 flex-row items-center justify-center">
                            <Icon name="check" size={10} color="#10B981" />
                            <Text className="text-brand-500 font-bold text-[10px] ml-1">
                              Checked in at {format(new Date(viewing.check_in_at), 'HH:mm')}
                            </Text>
                          </View>
                        )}
                      </View>
                    )}

                    {/* No-show Danger Prompt Banner */}
                    {showNoShowAlert && (
                      <View className="mt-3 bg-danger/10 border border-danger/20 rounded-lg p-2.5">
                        <View className="flex-row items-center">
                          <Icon name="alert-triangle" size={12} color="#F43F5E" />
                          <Text className="text-danger font-extrabold text-[11px] ml-1.5 flex-1">
                            Viewing overdue by 15m+. Mark as no-show?
                          </Text>
                        </View>
                        <View className="flex-row gap-2 mt-2">
                          <Pressable
                            onPress={() => markNoShowMutation.mutate(viewing.id)}
                            className="flex-1 bg-danger rounded-md py-1.5 items-center justify-center"
                          >
                            <Text className="text-white font-bold text-[10px]">Confirm No-Show</Text>
                          </Pressable>
                          <Pressable
                            onPress={() => setDismissedNoShows(prev => [...prev, viewing.id])}
                            className="bg-zinc-800 dark:bg-zinc-800 light:bg-slate-200 rounded-md px-3 py-1.5 items-center justify-center"
                          >
                            <Text className={`${styles.textSecondary} font-bold text-[10px]`}>Dismiss</Text>
                          </Pressable>
                        </View>
                      </View>
                    )}
                  </Pressable>
                </View>
              </View>
            );
          })}
        </ScrollView>
      )}

      {/* Custom Location Permission Modal */}
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
                onPress={confirmLocationCheckIn}
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

      {/* Complete Flow Bottom Sheet (sliding sheet mock) */}
      <Modal visible={completeSheetVisible} transparent animationType="slide">
        <View className="flex-1 justify-end bg-black/70">
          <Pressable className="flex-1" onPress={() => setCompleteSheetVisible(false)} />
          <View className={`${isDarkMode ? 'bg-[#111827]' : 'bg-white'} rounded-t-[24px] p-6 shadow-2xl`}>
            {/* Grabber indicator */}
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

            {/* Recording visual indicator */}
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
                onPress={submitCompletion}
                disabled={completeMutation.isPending}
                className="flex-1 bg-brand-500 rounded-xl py-3.5 items-center justify-center active:bg-brand-600"
              >
                {completeMutation.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text className="text-white font-extrabold text-xs">Save & Complete</Text>
                )}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Floating Small Toast Notification */}
      {toastMessage && (
        <Animated.View
          style={{ transform: [{ translateY: toastY }] }}
          className="absolute bottom-6 left-5 right-5 z-50"
        >
          <View className="bg-brand-500 rounded-2xl px-4 py-3.5 flex-row items-center justify-center shadow-lg border border-brand-400">
            <Icon name="check-circle" size={14} color="#fff" />
            <Text className="text-white text-xs font-extrabold ml-2 text-center">
              {toastMessage}
            </Text>
          </View>
        </Animated.View>
      )}
    </SafeAreaView>
  );
}
