import React, { useRef } from 'react';
import {
  Animated,
  PanResponder,
  Pressable,
  View,
} from 'react-native';
import Icon from 'react-native-vector-icons/Feather';

interface SwipeableRowProps {
  children: React.ReactNode;
  onCall: () => void;
  onMessage: () => void;
}

export function SwipeableRow({ children, onCall, onMessage }: SwipeableRowProps) {
  const translateX = useRef(new Animated.Value(0)).current;
  const isOpen = useRef(false);
  const currentTranslation = useRef(0);

  const snap = (toValue: number) => {
    Animated.spring(translateX, {
      toValue,
      useNativeDriver: true,
      bounciness: 4,
      speed: 12,
    }).start(() => {
      currentTranslation.current = toValue;
      isOpen.current = toValue !== 0;
    });
  };

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: () => false,
      onMoveShouldSetPanResponder: (_, gestureState) => {
        const { dx, dy } = gestureState;
        return Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 10;
      },
      onPanResponderMove: (_, gestureState) => {
        let newX = currentTranslation.current + gestureState.dx;
        // Limit swipe range: don't swipe right beyond 0, and don't swipe left beyond -180
        if (newX > 0) newX = 0;
        if (newX < -180) newX = -180;
        translateX.setValue(newX);
      },
      onPanResponderRelease: (_, gestureState) => {
        const threshold = -60;
        if (isOpen.current) {
          // If open and swiping right, close it
          if (gestureState.dx > 40) {
            snap(0);
          } else {
            snap(-140);
          }
        } else {
          // If closed and swiping left past threshold, open it
          if (gestureState.dx < threshold) {
            snap(-140);
          } else {
            snap(0);
          }
        }
      },
      onPanResponderTerminate: () => {
        snap(isOpen.current ? -140 : 0);
      },
    })
  ).current;

  return (
    <View className="relative overflow-hidden mb-3 mx-4 rounded-xl bg-[#090d16] border border-slate-800/60">
      {/* Background Actions */}
      <View className="absolute right-0 top-0 bottom-0 flex-row w-[140px] z-0">
        <Pressable
          onPress={() => {
            snap(0);
            onCall();
          }}
          className="w-[70px] bg-brand-500 items-center justify-center h-full active:bg-brand-600"
        >
          <Icon name="phone" size={20} color="#ffffff" />
        </Pressable>
        <Pressable
          onPress={() => {
            snap(0);
            onMessage();
          }}
          className="w-[70px] bg-info items-center justify-center h-full active:bg-info/90"
        >
          <Icon name="message-square" size={20} color="#ffffff" />
        </Pressable>
      </View>

      {/* Foreground Content */}
      <Animated.View
        style={{ transform: [{ translateX }] }}
        {...panResponder.panHandlers}
        className="w-full bg-[#090d16]"
      >
        {children}
      </Animated.View>
    </View>
  );
}
