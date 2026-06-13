import React, {useState, useRef, useEffect, useMemo} from 'react';
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
  Linking,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {format, formatDistanceToNow, parseISO} from 'date-fns';
import {contactsApi} from '../../api/contacts';
import {briefApi, TimelineActivity} from '../../api/brief';
import type {ContactsStackParamList} from '../../navigation/stacks/ContactsStack';
import {useAuthStore} from '../../store/authStore';
import {Contact, Call, Deal} from '../../types';
import {useTheme} from '../../theme/ThemeProvider';
import {ThemeTokens} from '../../theme/tokens';

type RoutePropType = RouteProp<ContactsStackParamList, 'ContactDetail'>;
type NavProp = NativeStackNavigationProp<ContactsStackParamList>;

const SENTIMENT_DOT_HEX: Record<string, string> = {
  hot: '#F43F5E', warm: '#F59E0B', cold: '#0EA5E9', neutral: '#71717A',
};

const STATUS_STYLE: Record<string, {bg: string; text: string; border: string}> = {
  new:       {bg: '#64748B1A', text: '#94A3B8', border: '#64748B33'},
  active:    {bg: '#10B9811A', text: '#10B981',  border: '#10B98133'},
  qualified: {bg: '#10B9811A', text: '#10B981',  border: '#10B98133'},
  nurturing: {bg: '#F59E0B1A', text: '#F59E0B',  border: '#F59E0B33'},
  closed:    {bg: '#A855F71A', text: '#A855F7',  border: '#A855F733'},
  archived:  {bg: '#3F3F461A', text: '#71717A',  border: '#3F3F4633'},
};

const ACTIVITY_ICON: Record<string, string> = {
  note: '📝', call: '📞', email: '✉️', sms: '📱',
  meeting: '🤝', viewing: '🏠', status_change: '🔄', system: '⚙️',
};

const formatNaira = (value?: number | string) => {
  if (value === undefined || value === null) return '—';
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return '—';
  if (num >= 1_000_000_000) return `₦${(num / 1_000_000_000).toFixed(1)}B`;
  if (num >= 1_000_000) return `₦${(num / 1_000_000).toFixed(1)}M`;
  return `₦${num.toLocaleString()}`;
};

function InlineAudioPlayer({duration, text, tokens}: {duration: string; text: string; tokens: ThemeTokens}) {
  const [isPlaying, setIsPlaying] = useState(false);
  const [progress, setProgress] = useState(0);
  const progressAnim = useRef(new Animated.Value(0)).current;
  const durationSec = useMemo(() => {
    const parts = duration.split(':');
    return parts.length === 2 ? parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10) : 10;
  }, [duration]);

  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isPlaying) {
      Animated.timing(progressAnim, {toValue: 1, duration: durationSec * 1000, useNativeDriver: false}).start();
      interval = setInterval(() => {
        setProgress((prev) => {
          if (prev >= 1) { setIsPlaying(false); setProgress(0); progressAnim.setValue(0); return 0; }
          return prev + 1 / durationSec;
        });
      }, 1000);
    } else {
      progressAnim.stopAnimation();
    }
    return () => clearInterval(interval);
  }, [isPlaying, durationSec]);

  const widthPercent = progressAnim.interpolate({inputRange: [0, 1], outputRange: ['0%', '100%']});
  const BARS = [10, 18, 14, 22, 12, 16, 24, 18, 12, 20, 14, 8, 16, 22, 14, 10, 18, 14, 22, 12];

  return (
    <View style={{borderRadius: 12, padding: 12, borderWidth: 1, marginBottom: 12, backgroundColor: tokens.surfaceRaised, borderColor: tokens.borderDefault}}>
      <View style={{flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 8}}>
        <Pressable
          onPress={() => setIsPlaying(!isPlaying)}
          style={({pressed}) => ({width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.brandPrimary, alignItems: 'center', justifyContent: 'center', transform: [{scale: pressed ? 0.95 : 1}]})}
        >
          <Icon name={isPlaying ? 'pause' : 'play'} size={14} color="#ffffff" />
        </Pressable>
        <View style={{flex: 1, height: 24, justifyContent: 'center', position: 'relative'}}>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 2, opacity: 0.4, position: 'absolute', left: 0, right: 0, top: 0, bottom: 0}}>
            {BARS.map((h, i) => (
              <View key={i} style={{height: h, flex: 1, borderRadius: 2, backgroundColor: tokens.borderStrong}} />
            ))}
          </View>
          <Animated.View style={{width: widthPercent, height: 24, flexDirection: 'row', alignItems: 'center', gap: 2, position: 'absolute', overflow: 'hidden'}}>
            {BARS.map((h, i) => (
              <View key={i} style={{height: h, width: 8, borderRadius: 2, backgroundColor: tokens.brandPrimary}} />
            ))}
          </Animated.View>
        </View>
        <Text style={{fontSize: 10, fontFamily: 'monospace', fontWeight: '700', color: tokens.textSecondary}}>
          {isPlaying ? `0:${String(Math.floor(progress * durationSec)).padStart(2, '0')}` : duration}
        </Text>
      </View>
      <Text style={{fontSize: 12, fontStyle: 'italic', lineHeight: 16, color: tokens.textSecondary}}>"{text}"</Text>
    </View>
  );
}

function HighlightedTimelineItem({activity, isNew, tokens}: {activity: TimelineActivity; isNew: boolean; tokens: ThemeTokens}) {
  const bgAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (isNew) {
      Animated.timing(bgAnim, {toValue: 1, duration: 100, useNativeDriver: false}).start(() => {
        Animated.timing(bgAnim, {toValue: 0, duration: 1000, delay: 500, useNativeDriver: false}).start();
      });
    }
  }, [isNew]);

  const bgStyle = bgAnim.interpolate({inputRange: [0, 1], outputRange: ['transparent', 'rgba(16, 185, 129, 0.25)']});
  const icon = ACTIVITY_ICON[activity.type] ?? '•';
  const hasVoiceNote = activity.type === 'note' && activity.body?.startsWith('[Voice Note]');
  let voiceDuration = '', voiceText = '';
  if (hasVoiceNote) {
    const match = activity.body?.match(/\[Voice Note\] \((.*?)\) - (.*)/);
    if (match) { voiceDuration = match[1]; voiceText = match[2]; }
  }

  return (
    <Animated.View style={{backgroundColor: bgStyle, borderRadius: 12, padding: 10, marginBottom: 6}}>
      <View style={{flexDirection: 'row'}}>
        <View style={{width: 32, height: 32, borderRadius: 16, alignItems: 'center', justifyContent: 'center', marginRight: 12, marginTop: 2, backgroundColor: tokens.surfaceRaised}}>
          <Text style={{fontSize: 14}}>{icon}</Text>
        </View>
        <View style={{flex: 1}}>
          {activity.subject && (
            <Text style={{fontSize: 14, fontWeight: '700', color: tokens.textPrimary}}>{activity.subject}</Text>
          )}
          {hasVoiceNote ? (
            <View style={{marginTop: 4}}>
              <InlineAudioPlayer duration={voiceDuration} text={voiceText} tokens={tokens} />
            </View>
          ) : (
            activity.body && (
              <Text style={{fontSize: 14, marginTop: 2, lineHeight: 20, color: tokens.textSecondary}}>{activity.body}</Text>
            )
          )}
          <View style={{flexDirection: 'row', alignItems: 'center', marginTop: 6, gap: 8}}>
            <Text style={{fontSize: 10, color: tokens.textTertiary}}>
              {formatDistanceToNow(new Date(activity.occurred_at), {addSuffix: true})}
            </Text>
            {activity.user && (
              <Text style={{fontSize: 10, fontWeight: '700', color: tokens.textTertiary}}>
                · {activity.user.first_name}
              </Text>
            )}
          </View>
        </View>
      </View>
    </Animated.View>
  );
}

export function ContactDetailScreen() {
  const {tokens} = useTheme();
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {contactId} = route.params;
  const queryClient = useQueryClient();

  const [tab, setTab] = useState<'timeline' | 'calls' | 'notes'>('timeline');
  const [noteVisible, setNoteVisible] = useState(false);
  const [dealModalVisible, setDealModalVisible] = useState(false);
  const [noteText, setNoteText] = useState('');
  const [isRecording, setIsRecording] = useState(false);
  const [recordDuration, setRecordDuration] = useState(0);
  const recordInterval = useRef<NodeJS.Timeout | null>(null);
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const highlightNoteId = useRef<number | null>(null);
  const bar1 = useRef(new Animated.Value(6)).current;
  const bar2 = useRef(new Animated.Value(10)).current;
  const bar3 = useRef(new Animated.Value(8)).current;
  const bar4 = useRef(new Animated.Value(14)).current;
  const bar5 = useRef(new Animated.Value(7)).current;
  const bar6 = useRef(new Animated.Value(11)).current;

  const {data, isLoading} = useQuery({
    queryKey: ['contact', contactId],
    queryFn: () => contactsApi.get(contactId).then((r) => r.data),
  });

  const {data: timeline, isLoading: timelineLoading} = useQuery({
    queryKey: ['timeline', contactId],
    queryFn: () => briefApi.timeline(contactId).then((r) => r.data),
  });

  const {user} = useAuthStore();

  const addNote = useMutation({
    mutationFn: (bodyText: string) => contactsApi.addNote(contactId, bodyText),
    onSuccess: (response: any) => {
      queryClient.invalidateQueries({queryKey: ['contact', contactId]});
      queryClient.invalidateQueries({queryKey: ['timeline', contactId]});
      if (response.data?.id) {
        highlightNoteId.current = response.data.id;
        setTimeout(() => { highlightNoteId.current = null; }, 2000);
      }
      setNoteText('');
      setNoteVisible(false);
    },
    onError: () => Alert.alert('Error', 'Could not save note.'),
  });

  useEffect(() => {
    if (isRecording) {
      const loop = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {toValue: 1.25, duration: 600, useNativeDriver: true}),
          Animated.timing(pulseAnim, {toValue: 1.0, duration: 600, useNativeDriver: true}),
        ])
      );
      loop.start();
      const bounce = (val: Animated.Value, max: number) =>
        Animated.loop(Animated.sequence([
          Animated.timing(val, {toValue: max, duration: 350, useNativeDriver: false}),
          Animated.timing(val, {toValue: 6, duration: 400, useNativeDriver: false}),
        ]));
      const bounces = [bounce(bar1, 24), bounce(bar2, 36), bounce(bar3, 28), bounce(bar4, 42), bounce(bar5, 20), bounce(bar6, 32)];
      bounces.forEach((b) => b.start());
      return () => { loop.stop(); bounces.forEach((b) => b.stop()); };
    } else {
      pulseAnim.setValue(1.0);
    }
  }, [isRecording]);

  // ── Derived data — all hooks MUST be before any early return ──────────────
  const contact = data?.contact;
  const recent_calls = data?.recent_calls;

  const aiInsight = useMemo(() => {
    if (!contact) return '';
    const latestCallSentiment = contact.latestCall?.summary?.sentiment || 'Warm';
    const minB = contact.preferences?.min_budget ? formatNaira(contact.preferences.min_budget) : '₦80M';
    const maxB = contact.preferences?.max_budget ? formatNaira(contact.preferences.max_budget) : '₦100M';
    const prefAreas = contact.preferences?.areas?.join(', ') || 'Lekki';
    return `${contact.first_name} is a motivated buyer, budget ${minB}-${maxB}, prefers ${prefAreas}. Last call sentiment: ${latestCallSentiment}. Hasn't seen ${prefAreas} listing with husband yet.`;
  }, [contact]);

  const callsData = useMemo(() => recent_calls ?? [], [recent_calls]);

  // ── Loading / error guard ──────────────────────────────────────────────────
  if (isLoading || !data || !contact) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, alignItems: 'center', justifyContent: 'center'}}>
        <ActivityIndicator color={tokens.brandPrimary} size="large" />
      </View>
    );
  }

  const activeDeal = contact.deals?.find((d: Deal) => d.status === 'open');
  const isAssignedToSelf = contact.assigned_agent_id === user?.id;
  const agentName = contact.assigned_agent
    ? `${contact.assigned_agent.first_name} ${contact.assigned_agent.last_name}`
    : 'Unassigned';

  const handleCall = () => {
    if (!contact.phone) { Alert.alert('No Phone', 'This contact has no phone number on record.'); return; }
    navigation.navigate('InCall', {contactId: contact.id, phoneNumber: contact.phone});
  };
  const handleWhatsApp = () => {
    if (!contact.phone) { Alert.alert('No Phone', 'This contact has no phone number on record.'); return; }
    const cleanPhone = contact.phone.replace(/[^0-9+]/g, '');
    Linking.openURL(`whatsapp://send?phone=${cleanPhone}`).catch(() => Alert.alert('WhatsApp not installed', 'Could not open WhatsApp on this device.'));
  };
  const handleSMS = () => {
    if (!contact.phone) { Alert.alert('No Phone', 'This contact has no phone number on record.'); return; }
    Linking.openURL(`sms:${contact.phone}`);
  };
  const handleEmail = () => {
    if (!contact.email) { Alert.alert('No Email', 'This contact has no email on record.'); return; }
    Linking.openURL(`mailto:${contact.email}`);
  };
  const handleOverflow = () => {
    Alert.alert('Contact Management', `Actions for ${contact.first_name}`, [
      {text: 'Add to Native Contacts', onPress: () => Alert.alert('Success', 'Added to device contacts list.')},
      {text: 'Block Contact', style: 'destructive', onPress: () => Alert.alert('Blocked', 'Contact will no longer ring.')},
      {text: 'Cancel', style: 'cancel'},
    ]);
  };

  const startRecording = () => {
    setIsRecording(true); setRecordDuration(0);
    recordInterval.current = setInterval(() => setRecordDuration((p) => p + 1), 1000);
  };
  const stopRecording = () => {
    setIsRecording(false);
    if (recordInterval.current) { clearInterval(recordInterval.current); recordInterval.current = null; }
    const seconds = recordDuration;
    const m = String(Math.floor(seconds / 60));
    const s = String(seconds % 60).padStart(2, '0');
    const transcripts = [
      'Motivated client. Mentioned budget range of ₦80-100M, interested in Lekki houses.',
      'Spoke briefly. Requested another viewing next Tuesday at 3:00 PM.',
      'Confirmed they will discuss with partner and get back to me by Thursday morning.',
      'Client wants to proceed with drawing up offers for the duplex in Lekki.',
    ];
    setNoteText(`[Voice Note] (${m}:${s}) - ${transcripts[Math.floor(Math.random() * transcripts.length)]}`);
  };
  const saveNoteText = () => { if (noteText.trim()) addNote.mutate(noteText); };

  const timelineData = timeline?.data ?? [];
  const notesData = timelineData.filter((a: TimelineActivity) => a.type === 'note');

  const statusStyle = STATUS_STYLE[contact.status] || STATUS_STYLE.new;


  const sheetStyle = {
    backgroundColor: tokens.surfaceCard,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    borderTopWidth: 1,
    borderTopColor: tokens.borderDefault,
    padding: 20,
    paddingBottom: 32,
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View style={{paddingHorizontal: 16, paddingTop: 12, paddingBottom: 16, borderBottomWidth: 1, backgroundColor: tokens.surfaceCard, borderBottomColor: tokens.borderDefault}}>
        <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12}}>
          <Pressable onPress={() => navigation.goBack()} style={{flexDirection: 'row', alignItems: 'center', gap: 6}}>
            <Icon name="arrow-left" size={18} color={tokens.brandPrimary} />
            <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 14}}>Back</Text>
          </Pressable>
          <Text style={{fontSize: 10, fontWeight: '700', color: tokens.textTertiary}}>Last synced 2h ago</Text>
        </View>

        <View style={{flexDirection: 'row', alignItems: 'center', gap: 16}}>
          <View style={{width: 80, height: 80, borderRadius: 40, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, alignItems: 'center', justifyContent: 'center'}}>
            <Text style={{color: tokens.brandPrimary, fontWeight: '900', fontSize: 28}}>{contact.first_name.charAt(0)}{contact.last_name.charAt(0)}</Text>
          </View>
          <View style={{flex: 1}}>
            <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '900', lineHeight: 28}}>{contact.first_name} {contact.last_name}</Text>
            <View style={{flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 6, flexWrap: 'wrap'}}>
              <View style={{paddingHorizontal: 10, paddingVertical: 2, borderRadius: 999, borderWidth: 1, backgroundColor: statusStyle.bg, borderColor: statusStyle.border}}>
                <Text style={{fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, color: statusStyle.text}}>{contact.status}</Text>
              </View>
              {!isAssignedToSelf && (
                <View style={{paddingHorizontal: 8, paddingVertical: 2, borderRadius: 999, backgroundColor: tokens.surfaceRaised, borderWidth: 1, borderColor: tokens.borderDefault}}>
                  <Text style={{color: tokens.textTertiary, fontWeight: '700', fontSize: 9}}>Assigned: {agentName.split(' ')[0]}</Text>
                </View>
              )}
            </View>
            {activeDeal && (
              <Pressable onPress={() => setDealModalVisible(true)} style={{flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 10}}>
                <Icon name="activity" size={13} color={tokens.brandPrimary} />
                <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12, textDecorationLine: 'underline'}}>View deal in Pipeline</Text>
              </Pressable>
            )}
          </View>
        </View>
      </View>

      <ScrollView style={{flex: 1}} contentContainerStyle={{paddingBottom: 88}} showsVerticalScrollIndicator={false}>
        {/* Quick actions */}
        <View style={{paddingHorizontal: 16, paddingVertical: 12}}>
          <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', borderRadius: 12, padding: 12, borderWidth: 1, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
            {[
              {label: 'Call',      icon: 'phone',          color: tokens.brandPrimary, bg: `${tokens.brandPrimary}1A`, onPress: handleCall},
              {label: 'WhatsApp',  icon: 'message-circle', color: '#25D366',            bg: '#25D3661A',                onPress: handleWhatsApp},
              {label: 'SMS',       icon: 'mail',           color: tokens.brandPrimary, bg: `${tokens.brandPrimary}1A`, onPress: handleSMS},
              {label: 'Email',     icon: 'send',           color: '#0EA5E9',            bg: '#0EA5E91A',                onPress: handleEmail},
              {label: 'More',      icon: 'more-horizontal', color: tokens.textSecondary, bg: tokens.surfaceRaised,     onPress: handleOverflow},
            ].map(({label, icon, color, bg, onPress}) => (
              <Pressable key={label} onPress={onPress} style={{alignItems: 'center', flex: 1, paddingVertical: 4}}>
                <View style={{width: 40, height: 40, borderRadius: 20, backgroundColor: bg, alignItems: 'center', justifyContent: 'center', marginBottom: 4}}>
                  <Icon name={icon} size={18} color={color} />
                </View>
                <Text style={{fontSize: 10, fontWeight: '800', color: tokens.textPrimary}}>{label}</Text>
              </Pressable>
            ))}
          </View>
        </View>

        {/* Key info */}
        <View style={{paddingHorizontal: 16, marginBottom: 16}}>
          <View style={{borderRadius: 12, padding: 16, borderWidth: 1, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
            <Text style={{fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 2, marginBottom: 12, color: tokens.textTertiary}}>Key Information</Text>
            <View style={{flexDirection: 'row', flexWrap: 'wrap', gap: 16}}>
              {[
                {label: 'Phone Number', value: contact.phone || '—', onPress: handleCall},
                {label: 'Email Address', value: contact.email || '—', onPress: handleEmail},
                {label: 'Budget Range', value: contact.preferences?.min_budget || contact.preferences?.max_budget ? `${formatNaira(contact.preferences.min_budget)} - ${formatNaira(contact.preferences.max_budget)}` : '₦80M - ₦100M'},
                {label: 'Preferred Areas', value: contact.preferences?.areas?.length ? contact.preferences.areas.join(', ') : 'Lekki Phase 1'},
                {label: 'Bedrooms', value: contact.preferences?.min_bedrooms ? `${contact.preferences.min_bedrooms}+ beds` : '3+ Bedrooms'},
                {label: 'Timeline', value: contact.preferences?.timeline || 'Immediate'},
              ].map(({label, value, onPress}) => (
                <Pressable key={label} onPress={onPress} style={{width: '47%'}}>
                  <Text style={{fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, color: tokens.textTertiary}}>{label}</Text>
                  <Text style={{fontSize: 14, fontWeight: '700', marginTop: 2, color: tokens.textPrimary}} numberOfLines={1}>{value}</Text>
                </Pressable>
              ))}
            </View>
          </View>
        </View>

        {/* AI insight */}
        <View style={{paddingHorizontal: 16, marginBottom: 20}}>
          <View style={{borderLeftWidth: 4, borderLeftColor: tokens.brandPrimary, padding: 16, borderTopRightRadius: 12, borderBottomRightRadius: 12, borderWidth: 1, borderColor: tokens.borderDefault, backgroundColor: tokens.surfaceCard}}>
            <View style={{flexDirection: 'row', alignItems: 'center', gap: 4, marginBottom: 8}}>
              <Icon name="zap" size={14} color={tokens.brandPrimary} />
              <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 11, textTransform: 'uppercase', letterSpacing: 0.5}}>✦ AI Insight</Text>
            </View>
            <Text style={{fontSize: 12, lineHeight: 20, fontWeight: '600', color: tokens.textPrimary}}>{aiInsight}</Text>
          </View>
        </View>

        {/* Tab bar */}
        <View style={{flexDirection: 'row', borderBottomWidth: 1, borderBottomColor: tokens.borderDefault, paddingHorizontal: 16, marginBottom: 12, backgroundColor: tokens.surfaceCard}}>
          {(['timeline', 'calls', 'notes'] as const).map((t) => {
            const isActive = tab === t;
            return (
              <Pressable
                key={t}
                onPress={() => setTab(t)}
                style={{marginRight: 24, paddingBottom: 10, borderBottomWidth: 2, borderBottomColor: isActive ? tokens.brandPrimary : 'transparent'}}
              >
                <Text style={{fontSize: 14, fontWeight: '800', textTransform: 'capitalize', color: isActive ? tokens.textPrimary : tokens.textTertiary}}>
                  {t}
                </Text>
              </Pressable>
            );
          })}
        </View>

        {/* Tab content */}
        <View style={{paddingHorizontal: 16}}>
          {tab === 'timeline' && (
            timelineLoading ? (
              <View style={{paddingVertical: 40, alignItems: 'center'}}>
                <ActivityIndicator color={tokens.brandPrimary} />
              </View>
            ) : timelineData.length === 0 ? (
              <View style={{paddingVertical: 32, alignItems: 'center'}}>
                <Text style={{fontSize: 12, color: tokens.textTertiary}}>No timeline activities yet</Text>
              </View>
            ) : (
              <View style={{gap: 4}}>
                {timelineData.map((activity: TimelineActivity) => (
                  <HighlightedTimelineItem key={activity.id} activity={activity} isNew={activity.id === highlightNoteId.current} tokens={tokens} />
                ))}
              </View>
            )
          )}

          {tab === 'calls' && (
            callsData.length === 0 ? (
              <View style={{paddingVertical: 32, alignItems: 'center'}}>
                <Text style={{fontSize: 12, color: tokens.textTertiary}}>No calls recorded yet</Text>
              </View>
            ) : (
              <View style={{gap: 8}}>
                {callsData.map((item: Call) => {
                  const sentiment = item.summary?.sentiment;
                  return (
                    <View key={item.id} style={{flexDirection: 'row', alignItems: 'center', padding: 12, borderRadius: 12, borderWidth: 1, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
                      <View style={{width: 32, height: 32, borderRadius: 16, backgroundColor: `${tokens.brandPrimary}1A`, alignItems: 'center', justifyContent: 'center', marginRight: 12}}>
                        <Icon name={item.direction === 'inbound' ? 'phone-incoming' : 'phone-outgoing'} size={14} color={tokens.brandPrimary} />
                      </View>
                      <View style={{flex: 1}}>
                        <Text style={{fontSize: 12, fontWeight: '700', textTransform: 'capitalize', color: tokens.textPrimary}}>{item.direction} Call</Text>
                        <Text style={{fontSize: 10, marginTop: 2, color: tokens.textTertiary}}>
                          {item.started_at ? format(parseISO(item.started_at), 'd MMM, h:mm a') : '—'}
                          {item.duration_formatted ? ` · ${item.duration_formatted}` : ''}
                        </Text>
                      </View>
                      {sentiment && <View style={{width: 10, height: 10, borderRadius: 5, backgroundColor: SENTIMENT_DOT_HEX[sentiment] || '#71717A'}} />}
                    </View>
                  );
                })}
              </View>
            )
          )}

          {tab === 'notes' && (
            notesData.length === 0 ? (
              <View style={{paddingVertical: 32, alignItems: 'center'}}>
                <Text style={{fontSize: 12, color: tokens.textTertiary}}>No notes added yet</Text>
              </View>
            ) : (
              <View style={{gap: 4}}>
                {notesData.map((activity: TimelineActivity) => (
                  <HighlightedTimelineItem key={activity.id} activity={activity} isNew={activity.id === highlightNoteId.current} tokens={tokens} />
                ))}
              </View>
            )
          )}
        </View>
      </ScrollView>

      {/* FAB */}
      {(tab === 'timeline' || tab === 'notes') && (
        <Pressable
          onPress={() => setNoteVisible(true)}
          style={({pressed}) => ({position: 'absolute', bottom: 24, right: 16, width: 56, height: 56, backgroundColor: tokens.brandPrimary, borderRadius: 28, alignItems: 'center', justifyContent: 'center', zIndex: 30, transform: [{scale: pressed ? 0.95 : 1}]})}
        >
          <Icon name="mic" size={24} color="#ffffff" />
        </Pressable>
      )}

      {/* Add Note sheet */}
      <Modal visible={noteVisible} transparent animationType="slide" onRequestClose={() => setNoteVisible(false)}>
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(2,6,23,0.6)'}}>
          <Pressable style={{flex: 1}} onPress={() => setNoteVisible(false)} />
          <View style={sheetStyle}>
            <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 20}} />
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24}}>
              <Text style={{color: tokens.textPrimary, fontWeight: '900', fontSize: 18}}>Add Activity Note</Text>
              <Pressable onPress={() => setNoteVisible(false)} style={{width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.surfaceRaised, alignItems: 'center', justifyContent: 'center'}}>
                <Icon name="x" size={16} color={tokens.textSecondary} />
              </Pressable>
            </View>

            <View style={{alignItems: 'center', marginBottom: 24}}>
              <View style={{width: 96, height: 96, alignItems: 'center', justifyContent: 'center', position: 'relative', marginBottom: 12}}>
                {isRecording && (
                  <Animated.View style={{transform: [{scale: pulseAnim}], position: 'absolute', top: 0, bottom: 0, left: 0, right: 0, borderRadius: 48, backgroundColor: '#F43F5E26'}} />
                )}
                <Pressable
                  onPress={isRecording ? stopRecording : startRecording}
                  style={{width: 64, height: 64, borderRadius: 32, alignItems: 'center', justifyContent: 'center', backgroundColor: isRecording ? '#F43F5E' : tokens.brandPrimary}}
                >
                  <Icon name={isRecording ? 'square' : 'mic'} size={24} color="#ffffff" />
                </Pressable>
              </View>
              <Text style={{fontSize: 14, fontFamily: 'monospace', fontWeight: '700', marginBottom: 10, color: isRecording ? '#F43F5E' : tokens.textSecondary}}>
                {isRecording ? `Recording... ${String(Math.floor(recordDuration / 60))}:${String(recordDuration % 60).padStart(2, '0')}` : 'Tap mic to start voice note'}
              </Text>
              {isRecording && (
                <View style={{flexDirection: 'row', alignItems: 'center', gap: 3, height: 48, justifyContent: 'center', width: '100%'}}>
                  {[bar1, bar2, bar3, bar4, bar5, bar6, bar5, bar3, bar2, bar1].map((b, i) => (
                    <Animated.View key={i} style={{height: b, width: 6, borderRadius: 999, backgroundColor: '#F43F5E'}} />
                  ))}
                </View>
              )}
            </View>

            <Text style={{fontSize: 12, fontWeight: '700', color: tokens.textSecondary, marginBottom: 6}}>Note Message / Transcript</Text>
            <TextInput
              style={{borderRadius: 12, paddingHorizontal: 16, paddingVertical: 12, fontSize: 14, borderWidth: 1, backgroundColor: tokens.surfaceInput, color: tokens.textPrimary, borderColor: tokens.borderDefault, minHeight: 100, textAlignVertical: 'top'}}
              placeholder="Record a voice note or type notes here…"
              placeholderTextColor={tokens.textTertiary}
              multiline
              numberOfLines={4}
              value={noteText}
              onChangeText={setNoteText}
            />

            <View style={{flexDirection: 'row', gap: 12, marginTop: 20}}>
              <Pressable style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.surfaceRaised}} onPress={() => setNoteVisible(false)}>
                <Text style={{fontWeight: '700', fontSize: 14, color: tokens.textSecondary}}>Cancel</Text>
              </Pressable>
              <Pressable
                style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.brandPrimary, opacity: (!noteText.trim() || addNote.isPending) ? 0.55 : 1}}
                onPress={saveNoteText}
                disabled={!noteText.trim() || addNote.isPending}
              >
                {addNote.isPending ? <ActivityIndicator color="#fff" size="small" /> : <Text style={{color: '#ffffff', fontWeight: '700', fontSize: 14}}>Save Note</Text>}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Deal Summary sheet */}
      {activeDeal && (
        <Modal visible={dealModalVisible} transparent animationType="slide" onRequestClose={() => setDealModalVisible(false)}>
          <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(2,6,23,0.6)'}}>
            <Pressable style={{flex: 1}} onPress={() => setDealModalVisible(false)} />
            <View style={sheetStyle}>
              <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 20}} />
              <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16}}>
                <View style={{flexDirection: 'row', alignItems: 'center', gap: 6}}>
                  <Icon name="activity" size={18} color={tokens.brandPrimary} />
                  <Text style={{color: tokens.textPrimary, fontWeight: '900', fontSize: 18}}>Pipeline Deal Summary</Text>
                </View>
                <Pressable onPress={() => setDealModalVisible(false)} style={{width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.surfaceRaised, alignItems: 'center', justifyContent: 'center'}}>
                  <Icon name="x" size={16} color={tokens.textSecondary} />
                </Pressable>
              </View>

              <View style={{borderRadius: 12, padding: 16, borderWidth: 1, marginBottom: 20, backgroundColor: tokens.surfaceRaised, borderColor: tokens.borderDefault}}>
                <Text style={{fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, color: tokens.textTertiary}}>Deal Name</Text>
                <Text style={{fontSize: 16, fontWeight: '800', marginTop: 2, color: tokens.textPrimary}}>{activeDeal.name}</Text>
                <View style={{flexDirection: 'row', marginTop: 16}}>
                  <View style={{width: '50%'}}>
                    <Text style={{fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, color: tokens.textTertiary}}>Value</Text>
                    <Text style={{fontSize: 16, fontWeight: '800', marginTop: 2, color: tokens.brandPrimary}}>{activeDeal.value ? formatNaira(activeDeal.value) : '—'}</Text>
                  </View>
                  <View style={{width: '50%'}}>
                    <Text style={{fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, color: tokens.textTertiary}}>Pipeline Stage</Text>
                    <View style={{flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 4}}>
                      <View style={{width: 10, height: 10, borderRadius: 5, backgroundColor: activeDeal.stage?.color || tokens.brandPrimary}} />
                      <Text style={{fontSize: 14, fontWeight: '700', color: tokens.textPrimary}}>{activeDeal.stage?.name || 'Qualified'}</Text>
                    </View>
                  </View>
                </View>
                <View style={{flexDirection: 'row', marginTop: 16}}>
                  <View style={{width: '50%'}}>
                    <Text style={{fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, color: tokens.textTertiary}}>Momentum</Text>
                    <View style={{flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 4}}>
                      <Icon name="trending-up" size={14} color="#F59E0B" />
                      <Text style={{fontSize: 14, fontWeight: '800', color: '#F59E0B'}}>{activeDeal.momentum_label || 'Warm'} ({activeDeal.momentum_score ?? 60}%)</Text>
                    </View>
                  </View>
                  <View style={{width: '50%'}}>
                    <Text style={{fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, color: tokens.textTertiary}}>Deal Status</Text>
                    <Text style={{fontSize: 14, fontWeight: '700', textTransform: 'capitalize', marginTop: 2, color: tokens.textPrimary}}>{activeDeal.status}</Text>
                  </View>
                </View>
              </View>

              <Pressable
                style={{width: '100%', backgroundColor: tokens.brandPrimary, borderRadius: 12, paddingVertical: 14, alignItems: 'center'}}
                onPress={() => setDealModalVisible(false)}
              >
                <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 14}}>Close Summary</Text>
              </Pressable>
            </View>
          </View>
        </Modal>
      )}
    </SafeAreaView>
  );
}
