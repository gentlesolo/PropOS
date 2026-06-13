import React from 'react';
import FeatherIcon from 'react-native-vector-icons/Feather';
import {useTheme} from '../theme/ThemeProvider';
import {ThemeTokens} from '../theme/tokens';
import {ICON_SIZE, IconSizeName} from '../theme/icons';

interface AppIconProps {
  name: string;
  size?: IconSizeName | number;
  // Pass a ThemeTokens key for automatic light/dark adaptation,
  // or a raw hex string for the rare cases that require it (e.g. brand channel colors).
  color?: keyof ThemeTokens | string;
}

export function AppIcon({name, size = 'md', color = 'textSecondary'}: AppIconProps) {
  const {tokens} = useTheme();

  const resolvedSize =
    typeof size === 'number' ? size : ICON_SIZE[size as IconSizeName];

  // If color is a key of ThemeTokens resolve it; otherwise treat as raw value.
  const resolvedColor =
    color in tokens ? (tokens as any)[color] as string : color;

  return <FeatherIcon name={name} size={resolvedSize} color={resolvedColor} />;
}
