import React, {useEffect, useRef, useState} from 'react';
import {Animated, Text, View, useColorScheme} from 'react-native';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import NetInfo from '@react-native-community/netinfo';
import Icon from 'react-native-vector-icons/Feather';

export function OfflineIndicator() {
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme();
  const isDark = colorScheme !== 'light';

  const [isOffline, setIsOffline] = useState(false);
  const slideAnim = useRef(new Animated.Value(-100)).current;

  useEffect(() => {
    // Listen for network state changes
    const unsubscribe = NetInfo.addEventListener((state) => {
      // If isConnected is explicitly false, trigger offline state
      setIsOffline(state.isConnected === false);
    });

    return () => {
      unsubscribe();
    };
  }, []);

  useEffect(() => {
    if (isOffline) {
      Animated.spring(slideAnim, {
        toValue: 0,
        useNativeDriver: true,
        bounciness: 2,
        speed: 12,
      }).start();
    } else {
      Animated.timing(slideAnim, {
        toValue: -150,
        duration: 250,
        useNativeDriver: true,
      }).start();
    }
  }, [isOffline]);

  return (
    <Animated.View
      style={{
        transform: [{translateY: slideAnim}],
        paddingTop: insets.top,
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        zIndex: 99999,
      }}
      className={`${
        isDark ? 'bg-zinc-900 border-b border-zinc-800' : 'bg-zinc-200 border-b border-zinc-300'
      }`}
    >
      <View className="flex-row items-center justify-center py-2 px-4">
        <Icon name="wifi-off" size={13} color={isDark ? '#e4e4e7' : '#27272a'} />
        <Text className={`text-[11px] font-extrabold ml-2 ${isDark ? 'text-zinc-200' : 'text-zinc-800'}`}>
          You're offline — changes will sync when reconnected
        </Text>
      </View>
    </Animated.View>
  );
}
