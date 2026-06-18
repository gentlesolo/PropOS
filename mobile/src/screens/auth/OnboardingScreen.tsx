import React, {useState, useRef} from 'react';
import {
  View,
  Text,
  ScrollView,
  Dimensions,
  Pressable,
  NativeSyntheticEvent,
  NativeScrollEvent,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/Feather';
import {useAuthStore} from '../../store/authStore';
import {useTheme} from '../../theme/ThemeProvider';

const {width: SCREEN_WIDTH} = Dimensions.get('window');

export function OnboardingScreen() {
  const {tokens} = useTheme();
  const {setHasSeenOnboarding} = useAuthStore();
  const [activeIndex, setActiveIndex] = useState(0);
  const scrollViewRef = useRef<ScrollView>(null);

  const slides = [
    {
      title: 'Your CRM, in your pocket',
      subtitle: 'VillaCRM brings your active files, client histories, and daily agenda into the palm of your hand. Built for the field.',
      icon: 'phone',
      renderIllustration: () => (
        <View className="items-center justify-center h-64 w-full">
          <View
            style={{
              width: 160,
              height: 256,
              borderWidth: 4,
              borderColor: tokens.borderDefault,
              borderRadius: 30,
              backgroundColor: tokens.surfaceCard,
              alignItems: 'center',
              justifyContent: 'center',
              ...tokens.shadowMd,
            }}
          >
            {/* Phone notch */}
            <View
              style={{
                position: 'absolute',
                top: 8,
                width: 64,
                height: 16,
                backgroundColor: tokens.surfaceRaised,
                borderRadius: 999,
              }}
            />
            {/* Active waveform */}
            <View className="w-20 h-20 rounded-full bg-brand-500/10 border border-brand-500/30 items-center justify-center">
              <Icon name="phone" size={32} color="#10B981" />
            </View>
            {/* Pulse rings */}
            <View className="absolute w-28 h-28 rounded-full border border-brand-500/10" />
            <View className="absolute w-36 h-36 rounded-full border border-brand-500/5" />
            {/* Waveform bars */}
            <View className="absolute bottom-6 flex-row items-center justify-center gap-1.5 w-full px-4">
              <View className="w-1.5 h-6 bg-brand-500 rounded-full" />
              <View className="w-1.5 h-10 bg-brand-400 rounded-full" />
              <View className="w-1.5 h-14 bg-brand-500 rounded-full" />
              <View className="w-1.5 h-8 bg-brand-600 rounded-full" />
              <View className="w-1.5 h-12 bg-brand-400 rounded-full" />
            </View>
          </View>
        </View>
      ),
    },
    {
      title: 'Every call, transcribed & summarised',
      subtitle: 'Never scribble notes on your steering wheel again. VillaCRM automatically records, transcribes, and extracts key action items.',
      icon: 'mic',
      renderIllustration: () => (
        <View className="items-center justify-center h-64 w-full">
          <View className="relative w-56 h-56 items-center justify-center">
            <View className="absolute w-44 h-44 rounded-full bg-brand-500/5 border border-brand-500/10" />

            {/* Left bubble — speaker */}
            <View
              style={{
                position: 'absolute',
                top: -16,
                left: 0,
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 16,
                padding: 12,
                flexDirection: 'row',
                alignItems: 'center',
                gap: 12,
                width: 160,
                ...tokens.shadowSm,
              }}
            >
              <View
                style={{
                  width: 32,
                  height: 32,
                  borderRadius: 16,
                  backgroundColor: tokens.surfaceRaised,
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Icon name="user" size={14} color={tokens.textTertiary} />
              </View>
              <View style={{flex: 1, gap: 4}}>
                <View style={{height: 8, width: 64, backgroundColor: tokens.borderStrong, borderRadius: 999}} />
                <View style={{height: 6, width: 80, backgroundColor: tokens.borderSubtle, borderRadius: 999}} />
              </View>
            </View>

            {/* Right bubble — AI summary */}
            <View
              style={{
                position: 'absolute',
                bottom: 16,
                right: 0,
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: `${tokens.brandPrimary}4D`,
                borderRadius: 16,
                padding: 12,
                width: 176,
                gap: 8,
                ...tokens.shadowSm,
              }}
            >
              <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between'}}>
                <Text style={{color: tokens.brandPrimary}} className="text-[10px] font-bold uppercase tracking-wider">
                  AI Summary
                </Text>
                <Icon name="zap" size={10} color={tokens.brandPrimary} />
              </View>
              <View style={{gap: 6}}>
                <View style={{height: 8, width: '100%', backgroundColor: `${tokens.brandPrimary}33`, borderRadius: 999}} />
                <View style={{height: 8, width: '83%', backgroundColor: `${tokens.brandPrimary}33`, borderRadius: 999}} />
                <View style={{height: 8, width: '67%', backgroundColor: `${tokens.brandPrimary}1A`, borderRadius: 999}} />
              </View>
            </View>

            {/* Central icon */}
            <View className="w-16 h-16 rounded-full bg-brand-500 items-center justify-center shadow-lg shadow-brand-500/50">
              <Icon name="file-text" size={24} color="#FAFAFA" />
            </View>
          </View>
        </View>
      ),
    },
    {
      title: 'Action items to deals closed',
      subtitle: 'Transcripts automatically turn into scheduled viewings, tasks, and follow-ups. Close deals faster with zero friction.',
      icon: 'check-circle',
      renderIllustration: () => (
        <View className="items-center justify-center h-64 w-full">
          <View className="relative w-56 h-56 items-center justify-center">
            {/* Dashed ring */}
            <View
              style={{
                position: 'absolute',
                width: 192,
                height: 192,
                borderRadius: 96,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderStyle: 'dashed',
              }}
            />

            {/* Map pin badge */}
            <View
              style={{
                position: 'absolute',
                top: 8,
                left: 24,
                width: 48,
                height: 48,
                borderRadius: 24,
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                alignItems: 'center',
                justifyContent: 'center',
                ...tokens.shadowSm,
              }}
            >
              <Icon name="map-pin" size={18} color="#F59E0B" />
            </View>

            {/* Message badge */}
            <View
              style={{
                position: 'absolute',
                top: 32,
                right: 16,
                width: 48,
                height: 48,
                borderRadius: 24,
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                alignItems: 'center',
                justifyContent: 'center',
                ...tokens.shadowSm,
              }}
            >
              <Icon name="message-square" size={18} color="#0EA5E9" />
            </View>

            {/* Central checkmark */}
            <View
              style={{
                width: 96,
                height: 96,
                borderRadius: 48,
                backgroundColor: `${tokens.brandPrimary}1A`,
                borderWidth: 2,
                borderColor: tokens.brandPrimary,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <View className="w-16 h-16 rounded-full bg-brand-500 items-center justify-center">
                <Icon name="check" size={32} color="#FAFAFA" />
              </View>
            </View>

            {/* Deal closed badge */}
            <View
              style={{
                position: 'absolute',
                bottom: -8,
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: `${tokens.brandPrimary}66`,
                borderRadius: 999,
                paddingHorizontal: 16,
                paddingVertical: 8,
                flexDirection: 'row',
                alignItems: 'center',
                gap: 8,
                ...tokens.shadowSm,
              }}
            >
              <Icon name="trending-up" size={14} color={tokens.brandPrimary} />
              <Text style={{color: tokens.textPrimary}} className="text-xs font-bold font-mono">
                $1,240,000 Deal Closed
              </Text>
            </View>
          </View>
        </View>
      ),
    },
  ];

  const handleScroll = (event: NativeSyntheticEvent<NativeScrollEvent>) => {
    const scrollOffset = event.nativeEvent.contentOffset.x;
    const index = Math.round(scrollOffset / SCREEN_WIDTH);
    setActiveIndex(index);
  };

  const handleNext = () => {
    if (activeIndex < slides.length - 1) {
      scrollViewRef.current?.scrollTo({
        x: (activeIndex + 1) * SCREEN_WIDTH,
        animated: true,
      });
    } else {
      setHasSeenOnboarding(true);
    }
  };

  const handleSkip = () => {
    setHasSeenOnboarding(true);
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Skip Button */}
      <View className="absolute top-12 right-6 z-10">
        <Pressable
          onPress={handleSkip}
          hitSlop={{top: 15, bottom: 15, left: 15, right: 15}}
          className="active:opacity-75"
        >
          <Text style={{color: tokens.textSecondary}} className="text-base font-semibold">
            Skip
          </Text>
        </Pressable>
      </View>

      <ScrollView
        ref={scrollViewRef}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onScroll={handleScroll}
        scrollEventThrottle={16}
        className="flex-1"
      >
        {slides.map((slide, index) => (
          <View
            key={index}
            style={{width: SCREEN_WIDTH}}
            className="flex-1 justify-center px-8"
          >
            <View className="mb-8 items-center">
              {slide.renderIllustration()}
            </View>

            <View className="items-center mb-12">
              <Text
                style={{color: tokens.textPrimary}}
                className="text-3xl font-extrabold text-center tracking-tight mb-4"
              >
                {slide.title}
              </Text>
              <Text
                style={{color: tokens.textSecondary}}
                className="text-base text-center leading-6 max-w-xs"
              >
                {slide.subtitle}
              </Text>
            </View>
          </View>
        ))}
      </ScrollView>

      {/* Bottom Controls */}
      <View className="px-8 pb-10 gap-8">
        {/* Dots */}
        <View className="flex-row justify-center gap-2">
          {slides.map((_, index) => (
            <View
              key={index}
              style={{
                height: 8,
                borderRadius: 999,
                width: index === activeIndex ? 24 : 8,
                backgroundColor: index === activeIndex ? tokens.brandPrimary : tokens.borderStrong,
              }}
            />
          ))}
        </View>

        <Pressable
          onPress={handleNext}
          className="w-full bg-brand-500 rounded-[10px] h-[52px] items-center justify-center shadow-lg shadow-brand-500/20 active:bg-brand-600"
        >
          <Text style={{color: tokens.textInverse}} className="font-bold text-lg">
            {activeIndex === slides.length - 1 ? 'Get Started' : 'Continue'}
          </Text>
        </Pressable>
      </View>
    </SafeAreaView>
  );
}
