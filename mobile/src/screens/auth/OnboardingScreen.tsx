import React, {useState, useRef} from 'react';
import {
  View,
  Text,
  ScrollView,
  Dimensions,
  Pressable,
  SafeAreaView,
  NativeSyntheticEvent,
  NativeScrollEvent,
} from 'react-native';
import Icon from 'react-native-vector-icons/Feather';
import {useAuthStore} from '../../store/authStore';

const {width: SCREEN_WIDTH} = Dimensions.get('window');

export function OnboardingScreen() {
  const {setHasSeenOnboarding} = useAuthStore();
  const [activeIndex, setActiveIndex] = useState(0);
  const scrollViewRef = useRef<ScrollView>(null);

  const slides = [
    {
      title: 'Your CRM, in your pocket',
      subtitle: 'PropOS brings your active files, client histories, and daily agenda into the palm of your hand. Built for the field.',
      icon: 'phone',
      color: '#10B981',
      renderIllustration: () => (
        <View className="items-center justify-center h-64 w-full">
          {/* Abstract Phone + Waveform Call Illustration */}
          <View className="relative w-40 h-64 border-4 border-zinc-800 rounded-[30px] bg-surface-card items-center justify-center shadow-2xl">
            {/* Phone notch */}
            <View className="absolute top-2 w-16 h-4 bg-zinc-800 rounded-full" />
            
            {/* Glowing active waveform center */}
            <View className="w-20 h-20 rounded-full bg-brand-500/10 border border-brand-500/30 items-center justify-center animate-pulse">
              <Icon name="phone" size={32} color="#10B981" />
            </View>
            
            {/* Absolute rings representing waveform pulses */}
            <View className="absolute w-28 h-28 rounded-full border border-brand-500/10 scale-90" />
            <View className="absolute w-36 h-36 rounded-full border border-brand-500/5 scale-110" />
            
            {/* Waveform lines */}
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
      subtitle: 'Never scribble notes on your steering wheel again. PropOS automatically records, transcribes, and extracts key action items.',
      icon: 'mic',
      color: '#10B981',
      renderIllustration: () => (
        <View className="items-center justify-center h-64 w-full">
          {/* Abstract transcription and summary illustration */}
          <View className="relative w-56 h-56 items-center justify-center">
            {/* Radial background glow */}
            <View className="absolute w-44 h-44 rounded-full bg-brand-500/5 border border-brand-500/10" />
            
            {/* Message Bubble Left */}
            <View className="absolute -top-4 left-0 bg-surface-card border border-zinc-800 rounded-2xl p-3 shadow-lg flex-row items-center gap-3 w-40">
              <View className="w-8 h-8 rounded-full bg-zinc-800 items-center justify-center">
                <Icon name="user" size={14} color="#A1A1AA" />
              </View>
              <View className="flex-1 gap-1">
                <View className="h-2 w-16 bg-zinc-700 rounded-full" />
                <View className="h-1.5 w-20 bg-zinc-800 rounded-full" />
              </View>
            </View>

            {/* Transcription Wave bubble right */}
            <View className="absolute bottom-4 right-0 bg-surface-card border border-brand-500/30 rounded-2xl p-3 shadow-lg w-44 gap-2">
              <View className="flex-row items-center justify-between">
                <Text className="text-[10px] text-brand-500 font-bold uppercase tracking-wider">AI Summary</Text>
                <Icon name="sparkles" size={10} color="#10B981" />
              </View>
              <View className="gap-1.5">
                <View className="h-2 w-full bg-brand-500/20 rounded-full" />
                <View className="h-2 w-5/6 bg-brand-500/20 rounded-full" />
                <View className="h-2 w-4/6 bg-brand-500/10 rounded-full" />
              </View>
            </View>

            {/* Central micro pin or target */}
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
      color: '#10B981',
      renderIllustration: () => (
        <View className="items-center justify-center h-64 w-full">
          {/* Abstract checkmark / Map Pin & Deal Closed Illustration */}
          <View className="relative w-56 h-56 items-center justify-center">
            {/* Background ring */}
            <View className="absolute w-48 h-48 rounded-full border border-zinc-800 border-dashed" />
            
            {/* Left: Map Pin */}
            <View className="absolute top-2 left-6 bg-surface-card border border-zinc-800 w-12 h-12 rounded-full items-center justify-center shadow-md">
              <Icon name="map-pin" size={18} color="#F59E0B" />
            </View>

            {/* Right: Message Notification */}
            <View className="absolute top-8 right-4 bg-surface-card border border-zinc-800 w-12 h-12 rounded-full items-center justify-center shadow-md">
              <Icon name="message-square" size={18} color="#0EA5E9" />
            </View>

            {/* Central Giant Checkmark representing deal closed */}
            <View className="w-24 h-24 rounded-full bg-brand-500/10 border-2 border-brand-500 items-center justify-center shadow-xl shadow-brand-500/20">
              <View className="w-16 h-16 rounded-full bg-brand-500 items-center justify-center">
                <Icon name="check" size={32} color="#FAFAFA" />
              </View>
            </View>

            {/* Floating task list tags */}
            <View className="absolute -bottom-2 bg-surface-card border border-brand-500/40 rounded-full px-4 py-2 shadow-lg flex-row items-center gap-2">
              <Icon name="trending-up" size={14} color="#10B981" />
              <Text className="text-text-primary text-xs font-bold font-mono">$1,240,000 Deal Closed</Text>
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
    <SafeAreaView className="flex-1 bg-surface-page">
      {/* Skip Button Top Right */}
      <View className="absolute top-12 right-6 z-10">
        <Pressable 
          onPress={handleSkip} 
          hitSlop={{top: 15, bottom: 15, left: 15, right: 15}}
          className="active:opacity-75"
        >
          <Text className="text-text-secondary text-base font-semibold">Skip</Text>
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
            {/* Illustration Area */}
            <View className="mb-8 items-center">
              {slide.renderIllustration()}
            </View>

            {/* Text Area */}
            <View className="items-center mb-12">
              <Text className="text-text-primary text-3xl font-extrabold text-center tracking-tight mb-4">
                {slide.title}
              </Text>
              <Text className="text-text-secondary text-base text-center leading-6 max-w-xs">
                {slide.subtitle}
              </Text>
            </View>
          </View>
        ))}
      </ScrollView>

      {/* Bottom Controls Area */}
      <View className="px-8 pb-10 gap-8">
        {/* Page Indicator Dots */}
        <View className="flex-row justify-center gap-2">
          {slides.map((_, index) => (
            <View
              key={index}
              className={`h-2 rounded-full transition-all duration-300 ${
                index === activeIndex ? 'w-6 bg-brand-500' : 'w-2 bg-zinc-700'
              }`}
            />
          ))}
        </View>

        {/* CTA Button */}
        <Pressable
          onPress={handleNext}
          className="w-full bg-brand-500 rounded-[10px] h-[52px] items-center justify-center shadow-lg shadow-brand-500/20 active:bg-brand-600 active:scale-[0.98]"
        >
          <Text className="text-text-primary font-bold text-lg">
            {activeIndex === slides.length - 1 ? 'Get Started' : 'Continue'}
          </Text>
        </Pressable>
      </View>
    </SafeAreaView>
  );
}
