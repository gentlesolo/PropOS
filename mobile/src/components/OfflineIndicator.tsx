import React, {useEffect, useRef, useState} from 'react';
import {Animated, Text, View} from 'react-native';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import NetInfo from '@react-native-community/netinfo';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme} from '../theme/ThemeProvider';

export function OfflineIndicator() {
  const insets = useSafeAreaInsets();
  const {tokens} = useTheme();
  const [isOffline, setIsOffline] = useState(false);
  const slideAnim = useRef(new Animated.Value(-100)).current;

  useEffect(() => {
    const unsubscribe = NetInfo.addEventListener((state) => {
      setIsOffline(state.isConnected === false);
    });
    return () => unsubscribe();
  }, []);

  useEffect(() => {
    if (isOffline) {
      Animated.spring(slideAnim, {toValue: 0, useNativeDriver: true, bounciness: 2, speed: 12}).start();
    } else {
      Animated.timing(slideAnim, {toValue: -150, duration: 250, useNativeDriver: true}).start();
    }
  }, [isOffline]);

  return (
    <Animated.View
      style={{
        transform: [{translateY: slideAnim}],
        paddingTop: insets.top,
        position: 'absolute',
        top: 0, left: 0, right: 0,
        zIndex: 99999,
        backgroundColor: tokens.surfaceRaised,
        borderBottomWidth: 1,
        borderBottomColor: tokens.borderDefault,
      }}
    >
      <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 8, paddingHorizontal: 16}}>
        <Icon name="wifi-off" size={13} color={tokens.textPrimary} />
        <Text style={{fontSize: 11, fontWeight: '800', marginLeft: 8, color: tokens.textPrimary}}>
          You're offline — changes will sync when reconnected
        </Text>
      </View>
    </Animated.View>
  );
}
