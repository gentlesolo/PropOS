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
  Animated,
  Image,
  Linking,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {viewingsApi} from '../../api/viewings';
import {format} from 'date-fns';
import type {ViewingsStackParamList} from '../../navigation/stacks/ViewingsStack';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme} from '../../theme/ThemeProvider';
import {AppIcon} from '../../components/AppIcon';

type RoutePropType = RouteProp<ViewingsStackParamList, 'ViewingDetail'>;
type NavProp = NativeStackNavigationProp<ViewingsStackParamList>;

const OUTCOMES = [
  {value: 'interested',     label: 'Interested',     icon: 'check-circle', color: '#10B981', bg: '#10B9811A', border: '#10B981'},
  {value: 'offer_expected', label: 'Offer Expected', icon: 'edit-2',       color: '#F59E0B', bg: '#F59E0B1A', border: '#F59E0B'},
  {value: 'not_interested', label: 'Not Interested', icon: 'x-circle',     color: '#71717A', bg: '#3F3F461A', border: '#3F3F46'},
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

export function ViewingDetailScreen() {
  const {tokens} = useTheme();
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {viewingId} = route.params;
  const queryClient = useQueryClient();

  const [completeSheetVisible, setCompleteSheetVisible] = useState(false);
  const [selectedOutcome, setSelectedOutcome] = useState<string>('interested');
  const [outcomeNotes, setOutcomeNotes] = useState('');
  const [locationModalVisible, setLocationModalVisible] = useState(false);

  const [isRecording, setIsRecording] = useState(false);
  const [recordingSeconds, setRecordingSeconds] = useState(0);
  const voiceTimer = useRef<NodeJS.Timeout | null>(null);
  const waveAnims = useRef([
    new Animated.Value(10), new Animated.Value(15), new Animated.Value(25),
    new Animated.Value(12), new Animated.Value(20),
  ]).current;

  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const toastY = useRef(new Animated.Value(100)).current;

  const {data: viewing, isLoading} = useQuery({
    queryKey: ['viewing', viewingId],
    queryFn: () => viewingsApi.get(viewingId).then((r) => r.data),
  });

  const checkIn = useMutation({
    mutationFn: () => viewingsApi.checkIn(viewingId),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['viewing', viewingId]});
      queryClient.invalidateQueries({queryKey: ['viewings']});
      showToast('Checked in successfully!');
    },
    onError: () => Alert.alert('Error', 'Could not check in. Please try again.'),
  });

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
      const mockTranscripts = [
        'Buyer loved the high ceilings and overall layout, but requested details on structural warranty and land title.',
        'Client was highly impressed by the finishes and requested to draft a formal offer by tomorrow evening.',
        'Client liked the apartment but wants to check options with a balcony or courtyard. Will schedule follow-up.',
      ];
      setOutcomeNotes((prev) => (prev ? prev + ' ' : '') + mockTranscripts[Math.floor(Math.random() * mockTranscripts.length)]);
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

  const openDirections = (address: string) => {
    const url = Platform.select({
      ios: `maps://app?daddr=${encodeURIComponent(address)}`,
      android: `google.navigation:q=${encodeURIComponent(address)}`,
      default: `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`,
    });
    Linking.canOpenURL(url)
      .then((supported) => Linking.openURL(supported ? url : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`))
      .catch(() => Linking.openURL(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`));
  };

  const mockPreviousNotes = useMemo(() => {
    if (!viewing) return null;
    return {
      date: '2 weeks ago (May 28)',
      notes: `${viewing.contact?.first_name || 'Client'} previously viewed 18 Admiralty Way. Liked the location, but wanted more bedroom space. Showing this property today specifically because it has larger master bedrooms.`,
    };
  }, [viewing]);

  if (isLoading || !viewing) {
    return (
      <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: tokens.surfacePage}}>
        <ActivityIndicator color={tokens.brandPrimary} size="large" />
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

  let statusText = 'Scheduled';
  let statusBg = tokens.surfaceRaised;
  let statusTextColor = tokens.textSecondary;
  if (isCompleted) { statusText = 'Completed'; statusBg = '#10B9811A'; statusTextColor = '#10B981'; }
  else if (viewing.status === 'no_show') { statusText = 'No Show'; statusBg = '#F43F5E1A'; statusTextColor = '#F43F5E'; }
  else if (viewing.status === 'cancelled') { statusText = 'Cancelled'; statusBg = tokens.surfaceRaised; statusTextColor = tokens.textTertiary; }
  else if (isCheckedIn) { statusText = 'In Progress'; statusBg = '#10B9811A'; statusTextColor = '#10B981'; }
  else if (viewing.status === 'confirmed') { statusText = 'Confirmed'; statusBg = '#0EA5E91A'; statusTextColor = '#0EA5E9'; }

  const sheetStyle = {
    backgroundColor: tokens.surfaceCard,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    borderTopWidth: 1,
    borderTopColor: tokens.borderDefault,
    padding: 24,
    paddingBottom: 36,
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View style={{paddingHorizontal: 20, paddingVertical: 16, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', borderBottomWidth: 1, backgroundColor: tokens.surfaceCard, borderBottomColor: tokens.borderDefault}}>
        <Pressable onPress={() => navigation.goBack()} style={{flexDirection: 'row', alignItems: 'center'}}>
          <Icon name="arrow-left" size={18} color={tokens.textPrimary} />
          <Text style={{fontWeight: '800', fontSize: 14, marginLeft: 8, color: tokens.textPrimary}}>Back</Text>
        </Pressable>
        <Text style={{fontWeight: '800', fontSize: 14, color: tokens.textPrimary}}>Viewing Details</Text>
        <View style={{paddingHorizontal: 10, paddingVertical: 2, borderRadius: 999, backgroundColor: statusBg}}>
          <Text style={{fontSize: 9, fontWeight: '800', textTransform: 'uppercase', color: statusTextColor}}>{statusText}</Text>
        </View>
      </View>

      <ScrollView style={{flex: 1}} contentContainerStyle={{paddingBottom: 40, paddingTop: 16}} showsVerticalScrollIndicator={false}>
        {/* Property Card */}
        {listing && (
          <View style={{marginHorizontal: 20, borderWidth: 1, borderRadius: 24, overflow: 'hidden', marginBottom: 16, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
            <Image
              source={{uri: `https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80&id=${viewingId}`}}
              style={{width: '100%', height: 176, backgroundColor: tokens.surfaceRaised}}
            />
            <View style={{padding: 16}}>
              <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start'}}>
                <View style={{flex: 1}}>
                  <Text style={{fontWeight: '800', fontSize: 18, lineHeight: 24, color: tokens.textPrimary}}>{listing.address}</Text>
                  <View style={{flexDirection: 'row', alignItems: 'center', marginTop: 4}}>
                    <View style={{backgroundColor: `${tokens.brandPrimary}1A`, paddingHorizontal: 8, paddingVertical: 2, borderRadius: 999, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, marginRight: 8}}>
                      <Text style={{color: tokens.brandPrimary, fontSize: 9, fontWeight: '800', textTransform: 'uppercase'}}>{propType}</Text>
                    </View>
                    <Text style={{fontSize: 12, color: tokens.textTertiary}}>
                      Scheduled · {format(new Date(viewing.scheduled_at), 'h:mm a')}
                    </Text>
                  </View>
                </View>
                {listing.price && (
                  <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 16, fontVariant: ['tabular-nums']}}>
                    ₦{Number(listing.price).toLocaleString()}
                  </Text>
                )}
              </View>

              {(listing.bedrooms || listing.bathrooms) && (
                <View style={{flexDirection: 'row', alignItems: 'center', gap: 16, marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: tokens.borderSubtle}}>
                  {listing.bedrooms && (
                    <View style={{flexDirection: 'row', alignItems: 'center'}}>
                      <Icon name="home" size={13} color={tokens.textSecondary} />
                      <Text style={{fontSize: 12, fontWeight: '700', marginLeft: 6, color: tokens.textSecondary}}>{listing.bedrooms} Bedrooms</Text>
                    </View>
                  )}
                  {listing.bathrooms && (
                    <View style={{flexDirection: 'row', alignItems: 'center'}}>
                      <Icon name="droplet" size={13} color={tokens.textSecondary} />
                      <Text style={{fontSize: 12, fontWeight: '700', marginLeft: 6, color: tokens.textSecondary}}>{listing.bathrooms} Bathrooms</Text>
                    </View>
                  )}
                </View>
              )}

              <Pressable
                onPress={() => openDirections(listing.address)}
                style={{marginTop: 16, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}4D`, borderRadius: 12, paddingVertical: 12, alignItems: 'center', justifyContent: 'center', flexDirection: 'row', gap: 8}}
              >
                <Icon name="navigation" size={13} color={tokens.brandPrimary} />
                <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12}}>Open In Maps</Text>
              </Pressable>
            </View>
          </View>
        )}

        {/* Client Details */}
        {contact && (
          <View style={{marginHorizontal: 20, borderWidth: 1, borderRadius: 24, padding: 16, marginBottom: 16, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
            <Text style={{fontSize: 10, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 12, color: tokens.textTertiary}}>Client Details</Text>
            <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between'}}>
              <View style={{flexDirection: 'row', alignItems: 'center'}}>
                <View style={{width: 48, height: 48, borderRadius: 24, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, alignItems: 'center', justifyContent: 'center', marginRight: 12}}>
                  <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 16}}>{contact.first_name[0]}{contact.last_name[0]}</Text>
                </View>
                <View>
                  <Text style={{fontWeight: '800', fontSize: 14, color: tokens.textPrimary}}>{contact.first_name} {contact.last_name}</Text>
                  <Text style={{fontSize: 12, marginTop: 2, color: tokens.textSecondary}}>Lead Interest Level: Hot</Text>
                </View>
              </View>
              {contact.phone && (
                <View style={{flexDirection: 'row', gap: 8}}>
                  <Pressable
                    onPress={() => Linking.openURL(`tel:${contact.phone}`)}
                    style={{width: 40, height: 40, borderRadius: 20, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, alignItems: 'center', justifyContent: 'center'}}
                  >
                    <Icon name="phone" size={15} color={tokens.brandPrimary} />
                  </Pressable>
                  <Pressable
                    onPress={() => Linking.openURL(`sms:${contact.phone}`)}
                    style={{width: 40, height: 40, borderRadius: 20, backgroundColor: '#0EA5E91A', borderWidth: 1, borderColor: '#0EA5E933', alignItems: 'center', justifyContent: 'center'}}
                  >
                    <Icon name="message-square" size={15} color="#0EA5E9" />
                  </Pressable>
                </View>
              )}
            </View>
          </View>
        )}

        {/* Previous viewing context */}
        {mockPreviousNotes && (
          <View style={{marginHorizontal: 20, borderWidth: 1, borderRadius: 24, padding: 16, marginBottom: 16, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
            <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8, paddingBottom: 8, borderBottomWidth: 1, borderBottomColor: tokens.borderSubtle}}>
              <View style={{flexDirection: 'row', alignItems: 'center'}}>
                <Icon name="file-text" size={12} color="#F59E0B" />
                <Text style={{color: '#F59E0B', fontSize: 10, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1, marginLeft: 6}}>Previous Viewing Context</Text>
              </View>
              <Text style={{fontSize: 10, fontWeight: '700', color: tokens.textTertiary}}>{mockPreviousNotes.date}</Text>
            </View>
            <Text style={{fontSize: 12, lineHeight: 20, color: tokens.textSecondary}}>{mockPreviousNotes.notes}</Text>
          </View>
        )}

        {/* Check-in confirmation */}
        {viewing.check_in_at && (
          <View style={{marginHorizontal: 20, backgroundColor: '#10B9811A', borderWidth: 1, borderColor: '#10B98133', borderRadius: 24, padding: 16, marginBottom: 16, flexDirection: 'row', alignItems: 'center'}}>
            <Icon name="check-circle" size={16} color="#10B981" />
            <Text style={{color: '#10B981', fontWeight: '800', fontSize: 12, marginLeft: 10}}>
              Verified arrival at {format(new Date(viewing.check_in_at), 'h:mm a')}
            </Text>
          </View>
        )}

        {/* Completed outcome */}
        {isCompleted && viewing.outcome && (
          <View style={{marginHorizontal: 20, borderWidth: 1, borderRadius: 24, padding: 16, marginBottom: 16, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
            <Text style={{fontSize: 10, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 8, color: tokens.textTertiary}}>Logged Outcome</Text>
            <View style={{flexDirection: 'row', alignItems: 'center', marginBottom: 8}}>
              <Icon
                name={viewing.outcome === 'interested' ? 'check-circle' : viewing.outcome === 'offer_expected' ? 'edit-2' : 'x-circle'}
                size={18}
                color={viewing.outcome === 'interested' ? '#10B981' : viewing.outcome === 'offer_expected' ? '#F59E0B' : '#71717A'}
              />
              <Text style={{fontSize: 12, fontWeight: '800', textTransform: 'capitalize', marginLeft: 8, color: tokens.textPrimary}}>{viewing.outcome.replace('_', ' ')}</Text>
            </View>
            {viewing.outcome_notes && (
              <Text style={{fontSize: 12, fontStyle: 'italic', lineHeight: 20, color: tokens.textSecondary}}>"{viewing.outcome_notes}"</Text>
            )}
          </View>
        )}

        {/* Action Buttons */}
        <View style={{marginHorizontal: 20, marginTop: 8}}>
          {canCheckIn && (
            <Pressable
              onPress={() => setLocationModalVisible(true)}
              style={{backgroundColor: tokens.brandPrimary, borderRadius: 16, paddingVertical: 16, alignItems: 'center', justifyContent: 'center', flexDirection: 'row', gap: 8}}
            >
              <Icon name="map-pin" size={15} color="#fff" />
              <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 14}}>Verify Check In</Text>
            </Pressable>
          )}
          {canComplete && (
            <Pressable
              onPress={() => setCompleteSheetVisible(true)}
              style={{backgroundColor: tokens.brandPrimary, borderRadius: 16, paddingVertical: 16, alignItems: 'center', justifyContent: 'center', flexDirection: 'row', gap: 8, marginTop: canCheckIn ? 12 : 0}}
            >
              <Icon name="check-circle" size={15} color="#fff" />
              <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 14}}>Complete Showing</Text>
            </Pressable>
          )}
        </View>
      </ScrollView>

      {/* Location Permission Modal */}
      <Modal visible={locationModalVisible} transparent animationType="fade">
        <View style={{flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', alignItems: 'center', justifyContent: 'center', paddingHorizontal: 24}}>
          <View style={{width: '100%', maxWidth: 320, borderRadius: 24, padding: 24, borderWidth: 1, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
            <View style={{width: 48, height: 48, borderRadius: 24, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, alignItems: 'center', justifyContent: 'center', marginBottom: 16, alignSelf: 'center'}}>
              <Icon name="map-pin" size={24} color={tokens.brandPrimary} />
            </View>
            <Text style={{fontSize: 16, fontWeight: '800', textAlign: 'center', marginBottom: 8, color: tokens.textPrimary}}>Verify On-Site Location</Text>
            <Text style={{fontSize: 12, textAlign: 'center', lineHeight: 20, marginBottom: 20, color: tokens.textSecondary}}>
              VillaCRM requires a quick, one-time geofence check to confirm your arrival at this listing site. This helps keep client timelines accurate.
            </Text>
            <Pressable
              onPress={() => { setLocationModalVisible(false); checkIn.mutate(); }}
              style={{backgroundColor: tokens.brandPrimary, borderRadius: 12, paddingVertical: 12, alignItems: 'center', marginBottom: 8}}
            >
              <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 12}}>Allow & Check In</Text>
            </Pressable>
            <Pressable onPress={() => setLocationModalVisible(false)} style={{paddingVertical: 12, alignItems: 'center'}}>
              <Text style={{fontWeight: '700', fontSize: 12, color: tokens.textSecondary}}>Cancel</Text>
            </Pressable>
          </View>
        </View>
      </Modal>

      {/* Complete Viewing Sheet */}
      <Modal visible={completeSheetVisible} transparent animationType="slide" onRequestClose={() => setCompleteSheetVisible(false)}>
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.7)'}}>
          <Pressable style={{flex: 1}} onPress={() => setCompleteSheetVisible(false)} />
          <View style={sheetStyle}>
            <View style={{width: 40, height: 4, borderRadius: 999, alignSelf: 'center', marginBottom: 20, backgroundColor: tokens.borderStrong}} />

            <Text style={{fontSize: 18, fontWeight: '800', marginBottom: 4, color: tokens.textPrimary}}>Complete Property Showing</Text>
            <Text style={{fontSize: 12, marginBottom: 20, color: tokens.textSecondary}}>Log the viewing outcome and feedback notes.</Text>

            {/* Outcome cards */}
            <Text style={{fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 10, color: tokens.textSecondary}}>Client Interest Level</Text>
            <View style={{flexDirection: 'row', gap: 8, marginBottom: 20}}>
              {OUTCOMES.map((out) => {
                const isSel = selectedOutcome === out.value;
                return (
                  <Pressable
                    key={out.value}
                    onPress={() => setSelectedOutcome(out.value)}
                    style={{flex: 1, alignItems: 'center', justifyContent: 'center', padding: 12, borderRadius: 12, borderWidth: 2, backgroundColor: isSel ? out.bg : tokens.surfaceRaised, borderColor: isSel ? out.border : tokens.borderDefault}}
                  >
                    <AppIcon name={out.icon} size="lg" color={isSel ? out.color : '#71717A'} />
                    <Text style={{fontSize: 11, fontWeight: '800', color: isSel ? out.color : tokens.textSecondary}}>{out.label}</Text>
                  </Pressable>
                );
              })}
            </View>

            {/* Voice note header */}
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8}}>
              <Text style={{fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1, color: tokens.textSecondary}}>Viewing Notes</Text>
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

            {/* Wave visualiser */}
            {isRecording && (
              <View style={{borderRadius: 12, padding: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, marginBottom: 12, backgroundColor: tokens.surfaceRaised, borderWidth: 1, borderColor: tokens.borderDefault}}>
                <Text style={{fontSize: 11, fontWeight: '700', marginRight: 8, color: tokens.textSecondary}}>Listening</Text>
                {waveAnims.map((anim, idx) => (
                  <Animated.View key={idx} style={{width: 4, borderRadius: 999, height: anim, backgroundColor: tokens.brandPrimary}} />
                ))}
              </View>
            )}

            <TextInput
              style={{width: '100%', borderRadius: 12, paddingHorizontal: 16, paddingVertical: 12, fontSize: 12, lineHeight: 20, borderWidth: 1, marginBottom: 20, minHeight: 80, textAlignVertical: 'top', backgroundColor: tokens.surfaceInput, color: tokens.textPrimary, borderColor: tokens.borderDefault}}
              placeholder="Type viewing notes here or use voice note option..."
              placeholderTextColor={tokens.textTertiary}
              multiline
              numberOfLines={4}
              value={outcomeNotes}
              onChangeText={setOutcomeNotes}
            />

            <View style={{flexDirection: 'row', gap: 12}}>
              <Pressable style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.surfaceRaised}} onPress={() => setCompleteSheetVisible(false)}>
                <Text style={{fontWeight: '800', fontSize: 12, color: tokens.textPrimary}}>Cancel</Text>
              </Pressable>
              <Pressable
                onPress={() => complete.mutate()}
                disabled={complete.isPending}
                style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.brandPrimary}}
              >
                {complete.isPending
                  ? <ActivityIndicator color="#fff" size="small" />
                  : <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 12}}>Save & Complete</Text>
                }
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Toast */}
      {toastMessage && (
        <Animated.View style={{transform: [{translateY: toastY}], position: 'absolute', bottom: 24, left: 20, right: 20, alignItems: 'center', zIndex: 50}}>
          <View style={{borderRadius: 999, paddingHorizontal: 20, paddingVertical: 10, flexDirection: 'row', alignItems: 'center', backgroundColor: tokens.surfaceRaised, borderWidth: 1, borderColor: tokens.borderDefault}}>
            <Icon name="check-circle" size={14} color="#10B981" />
            <Text style={{fontSize: 12, fontWeight: '800', marginLeft: 8, textAlign: 'center', color: tokens.textPrimary}}>{toastMessage}</Text>
          </View>
        </Animated.View>
      )}
    </SafeAreaView>
  );
}
