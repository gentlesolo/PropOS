import React, {useRef} from 'react';
import {Animated, PanResponder, Pressable, View} from 'react-native';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme} from '../theme/ThemeProvider';

interface SwipeableRowProps {
  children: React.ReactNode;
  onCall: () => void;
  onMessage: () => void;
}

export function SwipeableRow({children, onCall, onMessage}: SwipeableRowProps) {
  const {tokens} = useTheme();
  const translateX = useRef(new Animated.Value(0)).current;
  const isOpen = useRef(false);
  const currentTranslation = useRef(0);

  const snap = (toValue: number) => {
    Animated.spring(translateX, {toValue, useNativeDriver: true, bounciness: 4, speed: 12}).start(() => {
      currentTranslation.current = toValue;
      isOpen.current = toValue !== 0;
    });
  };

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: () => false,
      onMoveShouldSetPanResponder: (_, gestureState) => {
        const {dx, dy} = gestureState;
        return Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 10;
      },
      onPanResponderMove: (_, gestureState) => {
        let newX = currentTranslation.current + gestureState.dx;
        if (newX > 0) newX = 0;
        if (newX < -180) newX = -180;
        translateX.setValue(newX);
      },
      onPanResponderRelease: (_, gestureState) => {
        if (isOpen.current) {
          snap(gestureState.dx > 40 ? 0 : -140);
        } else {
          snap(gestureState.dx < -60 ? -140 : 0);
        }
      },
      onPanResponderTerminate: () => snap(isOpen.current ? -140 : 0),
    })
  ).current;

  return (
    <View style={{position: 'relative', overflow: 'hidden', marginBottom: 12, marginHorizontal: 16, borderRadius: 12, borderWidth: 1, backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault}}>
      {/* Background Actions */}
      <View style={{position: 'absolute', right: 0, top: 0, bottom: 0, flexDirection: 'row', width: 140, zIndex: 0}}>
        <Pressable
          onPress={() => { snap(0); onCall(); }}
          style={{width: 70, backgroundColor: tokens.brandPrimary, alignItems: 'center', justifyContent: 'center'}}
        >
          <Icon name="phone" size={20} color="#ffffff" />
        </Pressable>
        <Pressable
          onPress={() => { snap(0); onMessage(); }}
          style={{width: 70, backgroundColor: '#0EA5E9', alignItems: 'center', justifyContent: 'center'}}
        >
          <Icon name="message-square" size={20} color="#ffffff" />
        </Pressable>
      </View>

      {/* Foreground Content */}
      <Animated.View style={{transform: [{translateX}], width: '100%', backgroundColor: tokens.surfaceCard}} {...panResponder.panHandlers}>
        {children}
      </Animated.View>
    </View>
  );
}
