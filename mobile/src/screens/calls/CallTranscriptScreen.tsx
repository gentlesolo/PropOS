import React, {useState, useEffect, useRef} from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  Vibration,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/Feather';
import {callsApi} from '../../api/calls';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';
import {useTheme} from '../../theme/ThemeProvider';

type RoutePropType = RouteProp<CallsStackParamList, 'CallTranscript'>;
type NavProp = NativeStackNavigationProp<CallsStackParamList>;

const WAVEFORM_BARS = [
  15, 25, 12, 35, 45, 20, 15, 30, 25, 42, 28, 14, 22, 38, 48, 30,
  24, 18, 35, 40, 28, 30, 15, 20, 12, 26, 34, 44, 32, 22, 16, 28,
];

function pad(n: number) { return String(n).padStart(2, '0'); }
function formatTime(seconds: number) { return `${Math.floor(seconds / 60)}:${pad(Math.floor(seconds % 60))}`; }

export function CallTranscriptScreen() {
  const {tokens} = useTheme();
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();
  const {callId} = route.params;

  const {data: call, isLoading} = useQuery({
    queryKey: ['call', callId],
    queryFn: () => callsApi.get(callId).then(r => r.data),
  });

  const [isPlaying, setIsPlaying] = useState(false);
  const [currentSeconds, setCurrentSeconds] = useState(0);
  const [speed, setSpeed] = useState<1 | 1.5 | 2>(1);
  const [activeLineIdx, setActiveLineIdx] = useState<number | null>(null);
  const [isSearching, setIsSearching] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const playTimer = useRef<ReturnType<typeof setInterval> | null>(null);
  const totalDuration = call?.duration_seconds || 165;

  useEffect(() => {
    if (isPlaying) {
      playTimer.current = setInterval(() => {
        setCurrentSeconds(sec => {
          const next = sec + 0.5 * speed;
          if (next >= totalDuration) { setIsPlaying(false); return totalDuration; }
          return next;
        });
      }, 500);
    }
    return () => { if (playTimer.current) clearInterval(playTimer.current); };
  }, [isPlaying, speed, totalDuration]);

  if (isLoading || !call) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, alignItems: 'center', justifyContent: 'center'}}>
        <ActivityIndicator color={tokens.brandPrimary} size="large" />
      </View>
    );
  }

  const {contact, transcript} = call;
  const displayName = contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number;

  const rawSegments = transcript?.speaker_segments && transcript.speaker_segments.length > 0
    ? transcript.speaker_segments
    : [
        {speaker: 'Agent', text: 'Hi Sarah, thanks for taking my call. I wanted to follow up on the Lekki 4-bedroom property.', time: 0},
        {speaker: 'Contact', text: 'Oh yes! I actually viewed the listing online twice yesterday. It looks gorgeous.', time: 12},
        {speaker: 'Agent', text: 'It is a stunning build. What is your budget limit for this purchase?', time: 25},
        {speaker: 'Contact', text: 'We have a strict max budget of ₦85 million.', time: 38},
        {speaker: 'Agent', text: 'Understood. And what is your timeline looking like?', time: 48},
        {speaker: 'Contact', text: 'We want to close by Q3 2026 if possible.', time: 58},
        {speaker: 'Agent', text: 'Perfect. Did you have any specific concerns about the Lekki location?', time: 70},
        {speaker: 'Contact', text: "My main concern is the distance from our children's school. We need to make sure the commute is manageable.", time: 82},
        {speaker: 'Agent', text: 'That is valid. I can send you the brochure and share some nearby school listings to compare.', time: 99},
        {speaker: 'Contact', text: 'That would be wonderful. Can we follow up with another call on Friday, May 31st?', time: 115},
        {speaker: 'Agent', text: 'Absolutely. I will schedule a follow-up call for Friday, May 31st. I will send over the school info today.', time: 130},
        {speaker: 'Contact', text: 'Thank you so much. Have a great day!', time: 145},
        {speaker: 'Agent', text: 'You too, Sarah. Talk soon.', time: 155},
      ];

  const handlePlayPause = () => { Vibration.vibrate(15); setIsPlaying(!isPlaying); };
  const handleSpeedToggle = () => { Vibration.vibrate(10); setSpeed(prev => (prev === 1 ? 1.5 : prev === 1.5 ? 2 : 1)); };
  const handleLinePress = (index: number, timeSeconds: number) => {
    Vibration.vibrate(10);
    setActiveLineIdx(activeLineIdx === index ? null : index);
    setCurrentSeconds(timeSeconds);
  };

  const renderHighlightedText = (text: string, query: string, isAgent: boolean) => {
    if (!query || !query.trim()) {
      return <Text style={{color: tokens.textPrimary, fontSize: 14, lineHeight: 24}}>{text}</Text>;
    }
    const cleanQuery = query.trim();
    const parts = text.split(new RegExp(`(${cleanQuery})`, 'gi'));
    return (
      <Text style={{color: tokens.textPrimary, fontSize: 14, lineHeight: 24}}>
        {parts.map((part, i) =>
          part.toLowerCase() === cleanQuery.toLowerCase() ? (
            <Text key={i} style={{backgroundColor: '#F59E0B66', color: '#F59E0B', fontWeight: '600', paddingHorizontal: 2, borderRadius: 2}}>
              {part}
            </Text>
          ) : part
        )}
      </Text>
    );
  };

  const progress = currentSeconds / totalDuration;

  return (
    <View
      style={{
        paddingTop: Math.max(insets.top, 16),
        paddingBottom: Math.max(insets.bottom, 16),
        flex: 1,
        backgroundColor: tokens.surfacePage,
      }}
    >
      {/* Header */}
      <View
        style={{
          paddingHorizontal: 24,
          paddingBottom: 16,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'space-between',
        }}
      >
        <Pressable
          onPress={() => navigation.goBack()}
          style={{
            width: 40,
            height: 40,
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: tokens.surfaceRaised,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 20,
          }}
        >
          <Icon name="arrow-left" size={18} color={tokens.textPrimary} />
        </Pressable>

        {isSearching ? (
          <TextInput
            autoFocus
            value={searchQuery}
            onChangeText={setSearchQuery}
            placeholder="Search transcript…"
            placeholderTextColor={tokens.textTertiary}
            style={{
              flex: 1,
              marginHorizontal: 16,
              color: tokens.textPrimary,
              fontSize: 14,
              backgroundColor: tokens.surfaceInput,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              borderRadius: 12,
              paddingHorizontal: 16,
              paddingVertical: 8,
            }}
          />
        ) : (
          <View style={{flex: 1, alignItems: 'center', marginHorizontal: 16}}>
            <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', textAlign: 'center', lineHeight: 20}}>
              {displayName}
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 10, textTransform: 'uppercase', fontWeight: '700', letterSpacing: 1, marginTop: 2}}>
              Transcript
            </Text>
          </View>
        )}

        <Pressable
          onPress={() => { Vibration.vibrate(10); setIsSearching(!isSearching); if (isSearching) setSearchQuery(''); }}
          style={{
            width: 40,
            height: 40,
            alignItems: 'center',
            justifyContent: 'center',
            borderRadius: 20,
            borderWidth: 1,
            backgroundColor: isSearching ? `${tokens.brandPrimary}33` : tokens.surfaceRaised,
            borderColor: isSearching ? tokens.brandPrimary : tokens.borderDefault,
          }}
        >
          <Icon name={isSearching ? 'x' : 'search'} size={18} color={isSearching ? tokens.brandPrimary : tokens.textPrimary} />
        </Pressable>
      </View>

      {/* Audio player bar */}
      <View
        style={{
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderSubtle,
          paddingHorizontal: 24,
          paddingVertical: 16,
          flexDirection: 'row',
          alignItems: 'center',
          gap: 16,
          zIndex: 25,
        }}
      >
        <Pressable
          onPress={handlePlayPause}
          style={({pressed}) => [{
            width: 44,
            height: 44,
            borderRadius: 22,
            backgroundColor: tokens.brandPrimary,
            alignItems: 'center',
            justifyContent: 'center',
            transform: [{scale: pressed ? 0.95 : 1}],
          }]}
        >
          <Icon name={isPlaying ? 'pause' : 'play'} size={18} color="#FAFAFA" style={!isPlaying ? {marginLeft: 2} : {}} />
        </Pressable>

        {/* Waveform */}
        <View style={{flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', height: 40, paddingHorizontal: 8, gap: 3}}>
          {WAVEFORM_BARS.map((height, i) => {
            const isFinished = (i / WAVEFORM_BARS.length) <= progress;
            return (
              <View
                key={i}
                style={{
                  height,
                  flex: 1,
                  borderRadius: 999,
                  backgroundColor: isFinished ? tokens.brandPrimary : tokens.borderStrong,
                }}
              />
            );
          })}
        </View>

        <View style={{alignItems: 'flex-end', gap: 4, minWidth: 50}}>
          <Text style={{color: tokens.textPrimary, fontSize: 11, fontFamily: 'monospace', fontWeight: '600'}}>
            {formatTime(currentSeconds)}
          </Text>
          <Pressable
            onPress={handleSpeedToggle}
            style={{
              backgroundColor: tokens.surfaceRaised,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              paddingHorizontal: 8,
              paddingVertical: 2,
              borderRadius: 4,
            }}
          >
            <Text style={{color: tokens.brandPrimary, fontSize: 10, fontWeight: '700', textTransform: 'uppercase'}}>{speed}x</Text>
          </Pressable>
        </View>
      </View>

      {/* Progress scrubber */}
      <View style={{height: 2, width: '100%', backgroundColor: tokens.borderSubtle}}>
        <View style={{width: `${progress * 100}%`, height: '100%', backgroundColor: tokens.brandPrimary}} />
      </View>

      {/* Transcript segments */}
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{paddingVertical: 20}}
        style={{flex: 1}}
      >
        <View style={{gap: 24, paddingHorizontal: 16}}>
          {rawSegments.map((seg, idx) => {
            const isAgent = seg.speaker === 'Agent';
            const showTimestamp = activeLineIdx === idx;

            return (
              <View key={idx} style={{width: '100%', flexDirection: 'column'}}>
                {/* Speaker label */}
                <Text
                  style={{
                    fontSize: 10,
                    fontWeight: '800',
                    textTransform: 'uppercase',
                    letterSpacing: 2,
                    marginBottom: 6,
                    paddingHorizontal: 12,
                    textAlign: isAgent ? 'right' : 'left',
                    color: isAgent ? tokens.brandPrimary : tokens.textSecondary,
                  }}
                >
                  {seg.speaker}
                </Text>

                {/* Bubble */}
                <Pressable
                  onPress={() => handleLinePress(idx, (seg as any).time ?? 0)}
                  style={({pressed}) => [{
                    // Agent: right-aligned, brand tint; Contact: left-aligned, surface
                    alignSelf: isAgent ? 'flex-end' : 'flex-start',
                    marginLeft: isAgent ? 48 : 8,
                    marginRight: isAgent ? 8 : 48,
                    backgroundColor: isAgent ? `${tokens.brandPrimary}1A` : tokens.surfaceRaised,
                    borderWidth: 1,
                    borderColor: isAgent ? `${tokens.brandPrimary}40` : tokens.borderDefault,
                    borderRadius: 16,
                    borderTopRightRadius: isAgent ? 4 : 16,
                    borderTopLeftRadius: isAgent ? 16 : 4,
                    paddingHorizontal: 16,
                    paddingVertical: 14,
                    transform: [{scale: pressed ? 0.98 : 1}],
                  }]}
                >
                  {renderHighlightedText(seg.text, searchQuery, isAgent)}
                </Pressable>

                {/* Timestamp on tap */}
                {showTimestamp && (
                  <Text
                    style={{
                      color: tokens.textTertiary,
                      fontSize: 10,
                      fontFamily: 'monospace',
                      marginTop: 6,
                      paddingHorizontal: 12,
                      textTransform: 'uppercase',
                      letterSpacing: 1,
                      textAlign: isAgent ? 'right' : 'left',
                    }}
                  >
                    ✦ Seeked to {formatTime((seg as any).time ?? 0)}
                  </Text>
                )}
              </View>
            );
          })}
        </View>
      </ScrollView>
    </View>
  );
}
