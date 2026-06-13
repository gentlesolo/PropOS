import React, {useState, useEffect, useRef} from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  Vibration,
  Dimensions,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/Feather';
import {callsApi} from '../../api/calls';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type RoutePropType = RouteProp<CallsStackParamList, 'CallTranscript'>;
type NavProp = NativeStackNavigationProp<CallsStackParamList>;

// Beautiful static waveform heights
const WAVEFORM_BARS = [
  15, 25, 12, 35, 45, 20, 15, 30, 25, 42, 28, 14, 22, 38, 48, 30,
  24, 18, 35, 40, 28, 30, 15, 20, 12, 26, 34, 44, 32, 22, 16, 28
];

function pad(n: number) {
  return String(n).padStart(2, '0');
}

function formatTime(seconds: number) {
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${pad(secs)}`;
}

export function CallTranscriptScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();
  const {callId} = route.params;

  const {data: call, isLoading} = useQuery({
    queryKey: ['call', callId],
    queryFn: () => callsApi.get(callId).then(r => r.data),
  });

  // Player state
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentSeconds, setCurrentSeconds] = useState(0);
  const [speed, setSpeed] = useState<1 | 1.5 | 2>(1);
  const [activeLineIdx, setActiveLineIdx] = useState<number | null>(null);

  // Search state
  const [isSearching, setIsSearching] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const playTimer = useRef<ReturnType<typeof setInterval> | null>(null);
  const totalDuration = call?.duration_seconds || 165; // fallback duration

  // Manage fake playback timer
  useEffect(() => {
    if (isPlaying) {
      playTimer.current = setInterval(() => {
        setCurrentSeconds(sec => {
          const next = sec + 0.5 * speed; // update twice a second for smoother scrolling
          if (next >= totalDuration) {
            setIsPlaying(false);
            return totalDuration;
          }
          return next;
        });
      }, 500);
    }
    return () => {
      if (playTimer.current) clearInterval(playTimer.current);
    };
  }, [isPlaying, speed, totalDuration]);

  if (isLoading || !call) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#10B981" size="large" />
      </View>
    );
  }

  const {contact, transcript} = call;
  const displayName = contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number;

  // Use backend segments if available, otherwise fallback to rich simulated segments matching post-call details
  const rawSegments = transcript?.speaker_segments && transcript.speaker_segments.length > 0
    ? transcript.speaker_segments
    : [
        { speaker: 'Agent', text: 'Hi Sarah, thanks for taking my call. I wanted to follow up on the Lekki 4-bedroom property.', time: 0 },
        { speaker: 'Contact', text: 'Oh yes! I actually viewed the listing online twice yesterday. It looks gorgeous.', time: 12 },
        { speaker: 'Agent', text: 'It is a stunning build. What is your budget limit for this purchase?', time: 25 },
        { speaker: 'Contact', text: 'We have a strict max budget of ₦85 million.', time: 38 },
        { speaker: 'Agent', text: 'Understood. And what is your timeline looking like?', time: 48 },
        { speaker: 'Contact', text: 'We want to close by Q3 2026 if possible.', time: 58 },
        { speaker: 'Agent', text: 'Perfect. Did you have any specific concerns about the Lekki location?', time: 70 },
        { speaker: 'Contact', text: 'My main concern is the distance from our children\'s school. We need to make sure the commute is manageable.', time: 82 },
        { speaker: 'Agent', text: 'That is valid. I can send you the brochure and share some nearby school listings to compare.', time: 99 },
        { speaker: 'Contact', text: 'That would be wonderful. Can we follow up with another call on Friday, May 31st?', time: 115 },
        { speaker: 'Agent', text: 'Absolutely. I will schedule a follow-up call for Friday, May 31st. I will send over the school info today.', time: 130 },
        { speaker: 'Contact', text: 'Thank you so much. Have a great day!', time: 145 },
        { speaker: 'Agent', text: 'You too, Sarah. Talk soon.', time: 155 },
      ];

  const handlePlayPause = () => {
    Vibration.vibrate(15);
    setIsPlaying(!isPlaying);
  };

  const handleSpeedToggle = () => {
    Vibration.vibrate(10);
    setSpeed(prev => (prev === 1 ? 1.5 : prev === 1.5 ? 2 : 1));
  };

  const handleLinePress = (index: number, timeSeconds: number) => {
    Vibration.vibrate(10);
    setActiveLineIdx(activeLineIdx === index ? null : index);
    setCurrentSeconds(timeSeconds);
  };

  const renderHighlightedText = (text: string, query: string) => {
    if (!query || !query.trim()) {
      return <Text className="text-text-primary text-sm leading-6">{text}</Text>;
    }
    const cleanQuery = query.trim();
    const parts = text.split(new RegExp(`(${cleanQuery})`, 'gi'));
    return (
      <Text className="text-text-primary text-sm leading-6">
        {parts.map((part, i) =>
          part.toLowerCase() === cleanQuery.toLowerCase() ? (
            <Text key={i} className="bg-accent/40 text-accent font-semibold px-0.5 rounded">{part}</Text>
          ) : (
            part
          )
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
      }}
      className="flex-1 bg-surface"
    >
      {/* ── HEADER ────────────────────────────────────────────────────── */}
      <View className="px-6 pb-4 border-b border-slate-900/80 flex-row items-center justify-between">
        <Pressable onPress={() => navigation.goBack()} className="w-10 h-10 items-center justify-center bg-surface-raised border border-slate-800 rounded-full">
          <Icon name="arrow-left" size={18} color="#FAFAFA" />
        </Pressable>

        {isSearching ? (
          <TextInput
            autoFocus
            value={searchQuery}
            onChangeText={setSearchQuery}
            placeholder="Search transcript…"
            placeholderTextColor="#71717A"
            className="flex-1 mx-4 text-white text-sm bg-surface-raised border border-slate-800 rounded-xl px-4 py-2"
          />
        ) : (
          <View className="flex-1 items-center mx-4">
            <Text className="text-white text-base font-bold text-center leading-5">{displayName}</Text>
            <Text className="text-text-secondary text-[10px] uppercase font-bold tracking-wider mt-0.5">Transcript</Text>
          </View>
        )}

        <Pressable
          onPress={() => {
            Vibration.vibrate(10);
            setIsSearching(!isSearching);
            if (isSearching) setSearchQuery('');
          }}
          className={`w-10 h-10 items-center justify-center rounded-full border ${
            isSearching ? 'bg-brand-500/20 border-brand-500' : 'bg-surface-raised border-slate-800'
          }`}
        >
          <Icon name={isSearching ? 'x' : 'search'} size={18} color={isSearching ? '#10B981' : '#FAFAFA'} />
        </Pressable>
      </View>

      {/* ── AUDIO PLAYER BAR (Pinned at top) ─────────────────────────── */}
      <View className="bg-[#090d16]/80 border-b border-slate-900/85 px-6 py-4 flex-row items-center gap-4 z-25">
        {/* Play/Pause Button */}
        <Pressable
          onPress={handlePlayPause}
          className="w-11 h-11 rounded-full bg-brand-500 items-center justify-center shadow"
          style={({pressed}) => [{transform: [{scale: pressed ? 0.95 : 1}]}]}
        >
          <Icon name={isPlaying ? 'pause' : 'play'} size={18} color="#FAFAFA" style={!isPlaying ? {marginLeft: 2} : {}} />
        </Pressable>

        {/* Waveform Visualization */}
        <View className="flex-1 flex-row items-center justify-between h-10 px-2 gap-[3px]">
          {WAVEFORM_BARS.map((height, i) => {
            const isFinished = (i / WAVEFORM_BARS.length) <= progress;
            return (
              <View
                key={i}
                style={{height}}
                className={`flex-1 rounded-full ${isFinished ? 'bg-brand-500' : 'bg-slate-800'}`}
              />
            );
          })}
        </View>

        {/* Speed & Scrubber duration */}
        <View className="items-end gap-1 min-w-[50px]">
          <Text className="text-text-primary text-[11px] font-mono font-semibold">
            {formatTime(currentSeconds)}
          </Text>
          <Pressable
            onPress={handleSpeedToggle}
            className="bg-surface-raised border border-slate-850 px-2 py-0.5 rounded"
          >
            <Text className="text-brand-500 text-[10px] font-bold uppercase">{speed}x</Text>
          </Pressable>
        </View>
      </View>

      {/* Scrubber slider bar */}
      <View className="h-0.5 w-full bg-slate-900">
        <View style={{width: `${progress * 100}%`}} className="h-full bg-brand-500" />
      </View>

      {/* ── SCROLLABLE SPEAKER SEGMENTS ──────────────────────────────── */}
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{paddingVertical: 20}}
        className="flex-1"
      >
        <View className="gap-6 px-4">
          {rawSegments.map((seg, idx) => {
            const isAgent = seg.speaker === 'Agent';
            const showTimestamp = activeLineIdx === idx;
            const bubbleStyle = isAgent
              ? 'bg-brand-500/10 border border-brand-500/25 rounded-2xl rounded-tr-none px-4 py-3.5 self-end ml-12 mr-2'
              : 'bg-[#111827] border border-slate-800/80 rounded-2xl rounded-tl-none px-4 py-3.5 self-start mr-12 ml-2';

            return (
              <View key={idx} className="w-full flex-col">
                {/* Speaker Label */}
                <Text
                  className={`text-[10px] font-extrabold uppercase tracking-widest mb-1.5 px-3 ${
                    isAgent ? 'text-brand-500 text-right' : 'text-text-secondary text-left'
                  }`}
                >
                  {seg.speaker}
                </Text>

                {/* Bubble Container */}
                <Pressable
                  onPress={() => handleLinePress(idx, (seg as any).time ?? 0)}
                  className={bubbleStyle}
                  style={({pressed}) => [{transform: [{scale: pressed ? 0.98 : 1}]}]}
                >
                  {renderHighlightedText(seg.text, searchQuery)}
                </Pressable>

                {/* Timestamp (Geist Mono style, shown on tap) */}
                {showTimestamp && (
                  <Text
                    className={`text-text-tertiary text-[10px] font-mono mt-1.5 px-3 uppercase tracking-wider ${
                      isAgent ? 'text-right' : 'text-left'
                    }`}
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
