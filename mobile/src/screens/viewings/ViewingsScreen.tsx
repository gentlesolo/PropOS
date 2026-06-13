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
  Animated,
  Image,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {viewingsApi, Viewing} from '../../api/viewings';
import {format, isToday, addDays, isSameDay} from 'date-fns';
import type {ViewingsStackParamList} from '../../navigation/stacks/ViewingsStack';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme} from '../../theme/ThemeProvider';

type NavProp = NativeStackNavigationProp<ViewingsStackParamList>;

// Outcome colors are semantic/fixed — not theme-dependent
const OUTCOMES = [
  {value: 'interested',     label: 'Interested',     emoji: '🔥', color: '#10B981', bg: '#10B9811A', border: '#10B981'},
  {value: 'offer_expected', label: 'Offer Expected', emoji: '✍️', color: '#0EA5E9', bg: '#0EA5E91A', border: '#0EA5E9'},
  {value: 'not_interested', label: 'Not Interested', emoji: '👎', color: '#71717A', bg: '#71717A1A', border: '#71717A'},
];

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
  const {tokens, resolvedTheme} = useTheme();
  const navigation = useNavigation<NavProp>();
  const queryClient = useQueryClient();

  const [selectedDate, setSelectedDate] = useState<Date>(new Date());
  const [dismissedNoShows, setDismissedNoShows] = useState<number[]>([]);
  const [pulsingViewingId, setPulsingViewingId] = useState<number | null>(null);
  const [locationModalVisible, setLocationModalVisible] = useState(false);
  const [pendingCheckInViewing, setPendingCheckInViewing] = useState<Viewing | null>(null);
  const [completeSheetVisible, setCompleteSheetVisible] = useState(false);
  const [completingViewing, setCompletingViewing] = useState<Viewing | null>(null);
  const [selectedOutcome, setSelectedOutcome] = useState<string>('interested');
  const [outcomeNotes, setOutcomeNotes] = useState('');
  const [isRecording, setIsRecording] = useState(false);
  const [recordingSeconds, setRecordingSeconds] = useState(0);
  const voiceTimer = useRef<NodeJS.Timeout | null>(null);
  const waveAnims = useRef([
    new Animated.Value(10), new Animated.Value(15), new Animated.Value(25),
    new Animated.Value(12), new Animated.Value(20),
  ]).current;
  const checkInPulseScale = useRef(new Animated.Value(1)).current;
  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const toastY = useRef(new Animated.Value(100)).current;

  const {data: todayViewings = [], isLoading: loadingToday} = useQuery({
    queryKey: ['viewings', 'today'],
    queryFn: () => viewingsApi.today().then((r) => r.data),
  });
  const {data: upcoming = [], isLoading: loadingUpcoming} = useQuery({
    queryKey: ['viewings', 'upcoming'],
    queryFn: () => viewingsApi.upcoming().then((r) => r.data),
  });
  const isLoading = loadingToday || loadingUpcoming;

  const allViewings = useMemo(() => {
    const map = new Map<number, Viewing>();
    todayViewings.forEach((v) => map.set(v.id, v));
    upcoming.forEach((v) => map.set(v.id, v));
    return Array.from(map.values()).sort((a, b) => new Date(a.scheduled_at).getTime() - new Date(b.scheduled_at).getTime());
  }, [todayViewings, upcoming]);

  const dateStrip = useMemo(() => {
    const dates = [];
    for (let i = -1; i <= 5; i++) dates.push(addDays(new Date(), i));
    return dates;
  }, []);

  const filteredViewings = useMemo(
    () => allViewings.filter((v) => isSameDay(new Date(v.scheduled_at), selectedDate)),
    [allViewings, selectedDate]
  );

  const nextViewingId = useMemo(() => {
    if (!isSameDay(selectedDate, new Date())) return null;
    const pending = filteredViewings.filter((v) => (v.status === 'scheduled' || v.status === 'confirmed') && !v.check_in_at);
    return pending[0]?.id || null;
  }, [filteredViewings, selectedDate]);

  const checkInMutation = useMutation({
    mutationFn: (id: number) => viewingsApi.checkIn(id),
    onSuccess: (data) => {
      setPulsingViewingId(data.data.id);
      Animated.sequence([
        Animated.timing(checkInPulseScale, {toValue: 2, duration: 350, useNativeDriver: true}),
        Animated.timing(checkInPulseScale, {toValue: 1, duration: 250, useNativeDriver: true}),
      ]).start(() => setPulsingViewingId(null));
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
      const clientName = data.data.contact ? `${data.data.contact.first_name}` : 'Client';
      queryClient.invalidateQueries({queryKey: ['viewings']});
      showToast(`Added to ${clientName}'s timeline`);
      setCompletingViewing(null);
      setOutcomeNotes('');
      setSelectedOutcome('interested');
    },
    onError: () => Alert.alert('Error', 'Could not complete viewing. Please try again.'),
  });

  const markNoShowMutation = useMutation({
    mutationFn: (id: number) => viewingsApi.updateStatus(id, 'no_show'),
    onSuccess: () => { queryClient.invalidateQueries({queryKey: ['viewings']}); showToast('Viewing marked as No-Show'); },
    onError: () => Alert.alert('Error', 'Could not update status.'),
  });

  useEffect(() => {
    if (isRecording) {
      voiceTimer.current = setInterval(() => {
        setRecordingSeconds((prev) => prev + 1);
        waveAnims.forEach((anim) => {
          Animated.timing(anim, {toValue: Math.random() * 28 + 6, duration: 120, useNativeDriver: false}).start();
        });
      }, 1000);
    } else {
      if (voiceTimer.current) { clearInterval(voiceTimer.current); voiceTimer.current = null; }
      setRecordingSeconds(0);
    }
    return () => { if (voiceTimer.current) clearInterval(voiceTimer.current); };
  }, [isRecording]);

  const toggleRecording = () => {
    if (isRecording) {
      setIsRecording(false);
      const transcripts = [
        'Buyer loved the natural light in the living room and wants to arrange a second viewing with their spouse this Saturday.',
        'Client felt the kitchen was too small, but showed high interest in the general location. Will follow up with other matches.',
        'Highly motivated buyer. Expressed that they want to prepare an offer immediately. Expecting draft contract by tomorrow morning.',
      ];
      const t = transcripts[Math.floor(Math.random() * transcripts.length)];
      setOutcomeNotes((prev) => (prev ? prev + ' ' : '') + t);
    } else {
      setIsRecording(true);
    }
  };

  const showToast = (message: string) => {
    setToastMessage(message);
    Animated.spring(toastY, {toValue: 0, tension: 50, friction: 8, useNativeDriver: true}).start();
    setTimeout(() => {
      Animated.timing(toastY, {toValue: 100, duration: 250, useNativeDriver: true}).start(() => setToastMessage(null));
    }, 3000);
  };

  const handleCheckInPress = (viewing: Viewing) => { setPendingCheckInViewing(viewing); setLocationModalVisible(true); };

  const confirmLocationCheckIn = () => {
    setLocationModalVisible(false);
    if (pendingCheckInViewing) { checkInMutation.mutate(pendingCheckInViewing.id); setPendingCheckInViewing(null); }
  };

  const handleCompletePress = (viewing: Viewing) => { setCompletingViewing(viewing); setCompleteSheetVisible(true); };

  const submitCompletion = () => {
    if (completingViewing) completeMutation.mutate({id: completingViewing.id, outcome: selectedOutcome, notes: outcomeNotes || undefined});
  };

  const isNoShowEligible = (viewing: Viewing) => {
    if (viewing.status !== 'scheduled' && viewing.status !== 'confirmed') return false;
    if (viewing.check_in_at || dismissedNoShows.includes(viewing.id)) return false;
    const schedDate = new Date(viewing.scheduled_at);
    const fifteenMinutesAgo = new Date(Date.now() - 15 * 60 * 1000);
    return schedDate < fifteenMinutesAgo;
  };

  const sheetStyle = {
    backgroundColor: tokens.surfaceCard,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    borderTopWidth: 1,
    borderTopColor: tokens.borderDefault,
    padding: 24,
    paddingBottom: 40,
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View style={{paddingHorizontal: 20, paddingTop: 16, paddingBottom: 12, backgroundColor: tokens.surfaceCard, borderBottomWidth: 1, borderBottomColor: tokens.borderDefault, zIndex: 10, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
        <View>
          <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '800', letterSpacing: -0.5}}>Today's Viewings</Text>
          <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '600', marginTop: 2}}>
            {format(selectedDate, 'EEEE, d MMMM yyyy')}
          </Text>
        </View>
        <View style={{width: 40, height: 40, backgroundColor: `${tokens.brandPrimary}1A`, borderRadius: 20, alignItems: 'center', justifyContent: 'center', borderWidth: 1, borderColor: `${tokens.brandPrimary}33`}}>
          <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 16}}>{filteredViewings.length}</Text>
        </View>
      </View>

      {/* Date strip */}
      <View style={{paddingVertical: 12}}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{paddingHorizontal: 20, gap: 10}}>
          {dateStrip.map((date, index) => {
            const isSel = isSameDay(date, selectedDate);
            const isTod = isToday(date);
            return (
              <Pressable
                key={index}
                onPress={() => setSelectedDate(date)}
                style={{
                  flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
                  width: 60, paddingVertical: 10, borderRadius: 16, borderWidth: 1,
                  backgroundColor: isSel ? tokens.brandPrimary : tokens.surfaceCard,
                  borderColor: isSel ? tokens.brandPrimary : tokens.borderDefault,
                }}
              >
                <Text style={{fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, color: isSel ? 'rgba(255,255,255,0.8)' : tokens.textTertiary}}>
                  {isTod ? 'Today' : format(date, 'EEE')}
                </Text>
                <Text style={{fontSize: 16, fontWeight: '800', fontVariant: ['tabular-nums'], marginTop: 4, color: isSel ? '#FFFFFF' : tokens.textPrimary}}>
                  {format(date, 'd')}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>
      </View>

      {/* Content */}
      {isLoading ? (
        <View style={{flex: 1, alignItems: 'center', justifyContent: 'center'}}>
          <ActivityIndicator color={tokens.brandPrimary} size="large" />
        </View>
      ) : filteredViewings.length === 0 ? (
        <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 32}}>
          <View style={{width: 80, height: 80, backgroundColor: `${tokens.brandPrimary}1A`, borderRadius: 40, alignItems: 'center', justifyContent: 'center', marginBottom: 20, borderWidth: 1, borderColor: `${tokens.brandPrimary}1A`}}>
            <Icon name="calendar" size={32} color={tokens.brandPrimary} />
          </View>
          <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '700', marginBottom: 4, textAlign: 'center'}}>
            No viewings scheduled {isToday(selectedDate) ? 'today' : 'for this day'}
          </Text>
          <Text style={{color: tokens.textSecondary, fontSize: 14, textAlign: 'center', marginBottom: 24, paddingHorizontal: 16}}>
            Your schedule is clear. Check viewings scheduled for other days.
          </Text>
          {!isToday(selectedDate) && (
            <Pressable
              onPress={() => setSelectedDate(new Date())}
              style={{backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}4D`, borderRadius: 12, paddingHorizontal: 20, paddingVertical: 10}}
            >
              <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12}}>Return to Today</Text>
            </Pressable>
          )}
        </View>
      ) : (
        <ScrollView style={{flex: 1}} contentContainerStyle={{paddingBottom: 96, paddingTop: 8}} showsVerticalScrollIndicator={false}>
          {filteredViewings.map((viewing, index) => {
            const timeStr = format(new Date(viewing.scheduled_at), 'HH:mm');
            const contact = viewing.contact;
            const listing = viewing.listing;
            const isCompleted = viewing.status === 'completed';
            const isNoShow = viewing.status === 'no_show';
            const isCancelled = viewing.status === 'cancelled';
            const isCheckedIn = !!viewing.check_in_at;
            const isNext = viewing.id === nextViewingId;
            const showNoShowAlert = isNoShowEligible(viewing);
            const isFirst = index === 0;
            const isLast = index === filteredViewings.length - 1;
            const isSingle = filteredViewings.length === 1;

            // Status chip
            let statusText = 'Scheduled';
            let statusBg = tokens.surfaceRaised;
            let statusTextColor = tokens.textSecondary;
            if (isCompleted) { statusText = 'Completed'; statusBg = tokens.surfaceSunken; statusTextColor = tokens.textTertiary; }
            else if (isNoShow) { statusText = 'No Show'; statusBg = '#F43F5E1A'; statusTextColor = '#F43F5E'; }
            else if (isCancelled) { statusText = 'Cancelled'; statusBg = tokens.surfaceSunken; statusTextColor = tokens.textTertiary; }
            else if (isCheckedIn) { statusText = 'In Progress'; statusBg = `${tokens.brandPrimary}1A`; statusTextColor = tokens.brandPrimary; }
            else if (viewing.status === 'confirmed') { statusText = 'Confirmed'; statusBg = '#0EA5E91A'; statusTextColor = '#0EA5E9'; }

            // Timeline dot
            let dotBg = tokens.surfacePage;
            let dotBorderColor = tokens.borderDefault;
            let dotIcon: React.ReactNode = null;
            if (isCompleted) { dotBg = tokens.borderStrong; dotBorderColor = tokens.borderStrong; dotIcon = <Icon name="check" size={10} color="#fff" />; }
            else if (isCheckedIn) { dotBg = tokens.brandPrimary; dotBorderColor = tokens.brandPrimary; }
            else if (isNext) { dotBorderColor = tokens.brandPrimary; }

            const propType = getPropertyType(listing?.address, listing?.title);

            return (
              <View key={viewing.id} style={{flexDirection: 'row', alignItems: 'stretch', paddingHorizontal: 20, minHeight: 90}}>
                {/* Time column */}
                <View style={{width: 56, alignItems: 'flex-end', justifyContent: 'flex-start', paddingTop: 16, paddingRight: 12}}>
                  <Text style={{fontFamily: 'monospace', fontWeight: '700', fontSize: 15, color: isCompleted ? tokens.textTertiary : tokens.textPrimary}}>
                    {timeStr}
                  </Text>
                  {viewing.duration_minutes && !isCompleted && (
                    <Text style={{fontFamily: 'monospace', fontSize: 10, marginTop: 2, color: tokens.textTertiary}}>
                      {viewing.duration_minutes}m
                    </Text>
                  )}
                </View>

                {/* Dot & line column */}
                <View style={{width: 32, alignItems: 'center', justifyContent: 'flex-start', position: 'relative'}}>
                  {!isSingle && (
                    <View
                      style={{
                        position: 'absolute', width: 2, backgroundColor: tokens.borderDefault,
                        top: isFirst ? 24 : 0, bottom: isLast ? '76%' : 0,
                        left: '50%', marginLeft: -1,
                      }}
                    />
                  )}
                  <View style={{paddingTop: 18, zIndex: 10}}>
                    <View style={{width: 20, height: 20, borderRadius: 10, borderWidth: 2, alignItems: 'center', justifyContent: 'center', backgroundColor: dotBg, borderColor: dotBorderColor}}>
                      {dotIcon}
                    </View>
                  </View>
                </View>

                {/* Card column */}
                <View style={{flex: 1, paddingBottom: 16}}>
                  <Pressable
                    onPress={() => navigation.navigate('ViewingDetail', {viewingId: viewing.id})}
                    style={{
                      borderRadius: 12, borderWidth: isNext && !isCheckedIn ? 1.5 : 1,
                      backgroundColor: tokens.surfaceCard,
                      borderColor: isNext && !isCheckedIn ? tokens.brandPrimary : tokens.borderDefault,
                      padding: 16, opacity: isCompleted ? 0.75 : 1,
                      paddingVertical: isCompleted ? 10 : 16,
                    }}
                  >
                    {/* Title + status */}
                    <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 4}}>
                      <View style={{flex: 1}}>
                        {isCompleted ? (
                          <Text style={{fontWeight: '800', fontSize: 14, color: tokens.textSecondary, textDecorationLine: 'line-through'}} numberOfLines={1}>
                            {listing?.address || 'Property Viewing'}
                          </Text>
                        ) : (
                          <View style={{flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap', gap: 4}}>
                            <Text style={{fontWeight: '800', fontSize: 14, lineHeight: 20, color: tokens.textPrimary, flex: 1}} numberOfLines={1}>
                              {listing?.address || 'Property Viewing'}
                            </Text>
                            <View style={{backgroundColor: `${tokens.brandPrimary}1A`, paddingHorizontal: 8, paddingVertical: 2, borderRadius: 999, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`}}>
                              <Text style={{color: tokens.brandPrimary, fontSize: 9, fontWeight: '800', textTransform: 'uppercase'}}>{propType}</Text>
                            </View>
                          </View>
                        )}
                      </View>
                      <View style={{paddingHorizontal: 8, paddingVertical: 2, borderRadius: 999, backgroundColor: statusBg}}>
                        <Text style={{fontSize: 9, fontWeight: '800', textTransform: 'uppercase', color: statusTextColor}}>{statusText}</Text>
                      </View>
                    </View>

                    {/* Navigation hint for next */}
                    {isNext && !isCheckedIn && (
                      <View style={{flexDirection: 'row', alignItems: 'center', marginTop: 4, backgroundColor: `${tokens.brandPrimary}0D`, borderWidth: 1, borderColor: `${tokens.brandPrimary}1A`, borderRadius: 8, paddingVertical: 4, paddingHorizontal: 10, alignSelf: 'flex-start'}}>
                        <Icon name="navigation" size={10} color={tokens.brandPrimary} />
                        <Text style={{color: tokens.brandPrimary, fontSize: 10, fontWeight: '700', marginLeft: 4}}>You're 12 min away</Text>
                      </View>
                    )}

                    {/* Body: thumbnail + client */}
                    {!isCompleted && (
                      <View style={{flexDirection: 'row', alignItems: 'center', marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: tokens.borderSubtle}}>
                        <Image
                          source={{uri: `https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=120&q=80&id=${viewing.id}`}}
                          style={{width: 48, height: 48, borderRadius: 8, marginRight: 12, backgroundColor: tokens.surfaceRaised}}
                        />
                        <View style={{flex: 1}}>
                          <View style={{flexDirection: 'row', alignItems: 'center'}}>
                            <View style={{width: 20, height: 20, borderRadius: 10, backgroundColor: `${tokens.brandPrimary}33`, borderWidth: 1, borderColor: `${tokens.brandPrimary}4D`, alignItems: 'center', justifyContent: 'center', marginRight: 6}}>
                              <Text style={{color: tokens.brandPrimary, fontSize: 9, fontWeight: '800'}}>
                                {contact ? `${contact.first_name[0]}${contact.last_name[0]}` : '?'}
                              </Text>
                            </View>
                            <Text style={{fontSize: 12, fontWeight: '700', color: tokens.textPrimary}}>
                              {contact ? `${contact.first_name} ${contact.last_name}` : 'Unknown Client'}
                            </Text>
                          </View>
                          {viewing.notes && (
                            <Text style={{fontSize: 11, marginTop: 4, color: tokens.textSecondary}} numberOfLines={1}>
                              "{viewing.notes}"
                            </Text>
                          )}
                        </View>
                      </View>
                    )}

                    {/* Completed outcome notes */}
                    {isCompleted && viewing.outcome_notes && (
                      <Text style={{fontSize: 12, marginTop: 4, fontStyle: 'italic', color: tokens.textSecondary}} numberOfLines={1}>
                        "{viewing.outcome_notes}"
                      </Text>
                    )}

                    {/* Action buttons */}
                    {!isCompleted && !isCancelled && !isNoShow && (
                      <View style={{marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: tokens.borderSubtle}}>
                        {isNext && !isCheckedIn && (
                          <Pressable
                            onPress={() => handleCheckInPress(viewing)}
                            style={{backgroundColor: tokens.brandPrimary, borderRadius: 8, paddingVertical: 10, flexDirection: 'row', alignItems: 'center', justifyContent: 'center'}}
                          >
                            <Icon name="map-pin" size={13} color="#fff" />
                            <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 12, marginLeft: 6}}>Check In</Text>
                          </Pressable>
                        )}
                        {isCheckedIn && (
                          <>
                            <Pressable
                              onPress={() => handleCompletePress(viewing)}
                              style={{backgroundColor: tokens.brandPrimary, borderRadius: 8, paddingVertical: 10, flexDirection: 'row', alignItems: 'center', justifyContent: 'center'}}
                            >
                              <Icon name="check-circle" size={13} color="#fff" />
                              <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 12, marginLeft: 6}}>Complete Viewing</Text>
                            </Pressable>
                            {viewing.check_in_at && (
                              <View style={{marginTop: 8, flexDirection: 'row', alignItems: 'center', justifyContent: 'center'}}>
                                <Icon name="check" size={10} color={tokens.brandPrimary} />
                                <Text style={{color: tokens.brandPrimary, fontWeight: '700', fontSize: 10, marginLeft: 4}}>
                                  Checked in at {format(new Date(viewing.check_in_at), 'HH:mm')}
                                </Text>
                              </View>
                            )}
                          </>
                        )}
                      </View>
                    )}

                    {/* No-show alert */}
                    {showNoShowAlert && (
                      <View style={{marginTop: 12, backgroundColor: '#F43F5E1A', borderWidth: 1, borderColor: '#F43F5E33', borderRadius: 8, padding: 10}}>
                        <View style={{flexDirection: 'row', alignItems: 'center'}}>
                          <Icon name="alert-triangle" size={12} color="#F43F5E" />
                          <Text style={{color: '#F43F5E', fontWeight: '800', fontSize: 11, marginLeft: 6, flex: 1}}>
                            Viewing overdue by 15m+. Mark as no-show?
                          </Text>
                        </View>
                        <View style={{flexDirection: 'row', gap: 8, marginTop: 8}}>
                          <Pressable
                            onPress={() => markNoShowMutation.mutate(viewing.id)}
                            style={{flex: 1, backgroundColor: '#F43F5E', borderRadius: 6, paddingVertical: 6, alignItems: 'center'}}
                          >
                            <Text style={{color: '#ffffff', fontWeight: '700', fontSize: 10}}>Confirm No-Show</Text>
                          </Pressable>
                          <Pressable
                            onPress={() => setDismissedNoShows((prev) => [...prev, viewing.id])}
                            style={{backgroundColor: tokens.surfaceRaised, borderRadius: 6, paddingHorizontal: 12, paddingVertical: 6, alignItems: 'center'}}
                          >
                            <Text style={{color: tokens.textSecondary, fontWeight: '700', fontSize: 10}}>Dismiss</Text>
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

      {/* Location permission modal */}
      <Modal visible={locationModalVisible} transparent animationType="fade">
        <View style={{flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', alignItems: 'center', justifyContent: 'center', paddingHorizontal: 24}}>
          <View style={{width: '100%', maxWidth: 320, backgroundColor: tokens.surfaceCard, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 24, padding: 24}}>
            <View style={{width: 48, height: 48, borderRadius: 24, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, alignItems: 'center', justifyContent: 'center', marginBottom: 16, alignSelf: 'center'}}>
              <Icon name="map-pin" size={24} color={tokens.brandPrimary} />
            </View>
            <Text style={{fontSize: 16, fontWeight: '800', textAlign: 'center', color: tokens.textPrimary, marginBottom: 8}}>
              Verify On-Site Location
            </Text>
            <Text style={{fontSize: 12, textAlign: 'center', color: tokens.textSecondary, lineHeight: 20, marginBottom: 20}}>
              PropOS requires a quick geofence check to confirm your arrival at this listing site. This helps keep client timelines accurate.
            </Text>
            <View style={{gap: 8}}>
              <Pressable onPress={confirmLocationCheckIn} style={{backgroundColor: tokens.brandPrimary, borderRadius: 12, paddingVertical: 12, alignItems: 'center'}}>
                <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 12}}>Allow & Check In</Text>
              </Pressable>
              <Pressable onPress={() => setLocationModalVisible(false)} style={{paddingVertical: 12, alignItems: 'center', borderRadius: 12}}>
                <Text style={{color: tokens.textSecondary, fontWeight: '700', fontSize: 12}}>Cancel</Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Complete sheet */}
      <Modal visible={completeSheetVisible} transparent animationType="slide">
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.7)'}}>
          <Pressable style={{flex: 1}} onPress={() => setCompleteSheetVisible(false)} />
          <View style={sheetStyle}>
            <View style={{width: 40, height: 4, borderRadius: 999, backgroundColor: tokens.borderStrong, alignSelf: 'center', marginBottom: 20}} />
            <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '800', marginBottom: 4}}>Complete Property Showing</Text>
            <Text style={{color: tokens.textSecondary, fontSize: 12, marginBottom: 20}}>Log the viewing outcome and feedback notes.</Text>

            <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 10}}>Client Interest Level</Text>
            <View style={{flexDirection: 'row', gap: 8, marginBottom: 20}}>
              {OUTCOMES.map((out) => {
                const isSel = selectedOutcome === out.value;
                return (
                  <Pressable
                    key={out.value}
                    onPress={() => setSelectedOutcome(out.value)}
                    style={{flex: 1, alignItems: 'center', justifyContent: 'center', padding: 12, borderRadius: 12, borderWidth: 2, backgroundColor: isSel ? out.bg : tokens.surfaceRaised, borderColor: isSel ? out.border : tokens.borderDefault}}
                  >
                    <Text style={{fontSize: 20, marginBottom: 4}}>{out.emoji}</Text>
                    <Text style={{fontSize: 11, fontWeight: '800', color: isSel ? out.color : tokens.textSecondary}}>{out.label}</Text>
                  </Pressable>
                );
              })}
            </View>

            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8}}>
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1}}>Viewing Notes</Text>
              <Pressable
                onPress={toggleRecording}
                style={{flexDirection: 'row', alignItems: 'center', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 999, backgroundColor: isRecording ? '#F43F5E1A' : tokens.surfaceRaised, borderWidth: 1, borderColor: isRecording ? '#F43F5E33' : tokens.borderDefault}}
              >
                <Icon name={isRecording ? 'stop-circle' : 'mic'} size={11} color={isRecording ? '#F43F5E' : tokens.brandPrimary} />
                <Text style={{fontSize: 9, fontWeight: '800', marginLeft: 4, textTransform: 'uppercase', color: isRecording ? '#F43F5E' : tokens.brandPrimary}}>
                  {isRecording ? `Recording ${recordingSeconds}s` : 'Voice Note'}
                </Text>
              </Pressable>
            </View>

            {isRecording && (
              <View style={{backgroundColor: tokens.surfaceRaised, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 12, padding: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, marginBottom: 12}}>
                <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700', marginRight: 8}}>Listening</Text>
                {waveAnims.map((anim, idx) => (
                  <Animated.View key={idx} style={{width: 4, backgroundColor: tokens.brandPrimary, borderRadius: 999, height: anim}} />
                ))}
              </View>
            )}

            <TextInput
              style={{width: '100%', backgroundColor: tokens.surfaceInput, color: tokens.textPrimary, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 12, paddingHorizontal: 16, paddingVertical: 12, fontSize: 12, lineHeight: 20, marginBottom: 20, minHeight: 80, textAlignVertical: 'top'}}
              placeholder="Type viewing notes here or use voice note option..."
              placeholderTextColor={tokens.textTertiary}
              multiline
              numberOfLines={4}
              value={outcomeNotes}
              onChangeText={setOutcomeNotes}
            />

            <View style={{flexDirection: 'row', gap: 12}}>
              <Pressable onPress={() => setCompleteSheetVisible(false)} style={{flex: 1, backgroundColor: tokens.surfaceRaised, borderRadius: 12, paddingVertical: 14, alignItems: 'center'}}>
                <Text style={{fontWeight: '800', fontSize: 12, color: tokens.textPrimary}}>Cancel</Text>
              </Pressable>
              <Pressable onPress={submitCompletion} disabled={completeMutation.isPending} style={{flex: 1, backgroundColor: tokens.brandPrimary, borderRadius: 12, paddingVertical: 14, alignItems: 'center'}}>
                {completeMutation.isPending ? <ActivityIndicator color="#fff" size="small" /> : <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 12}}>Save & Complete</Text>}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Toast */}
      {toastMessage && (
        <Animated.View style={{position: 'absolute', bottom: 82, left: 20, right: 20, alignItems: 'center', zIndex: 50, transform: [{translateY: toastY}]}}>
          <View style={{backgroundColor: tokens.surfaceRaised, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 999, paddingHorizontal: 20, paddingVertical: 10, flexDirection: 'row', alignItems: 'center'}}>
            <Icon name="check-circle" size={14} color={tokens.brandPrimary} />
            <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '800', marginLeft: 8}}>{toastMessage}</Text>
          </View>
        </Animated.View>
      )}
    </SafeAreaView>
  );
}
