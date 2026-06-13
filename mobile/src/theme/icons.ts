// Single source of truth for icon names — swap here to restyle everywhere.
// All names reference react-native-vector-icons/Feather.
// Use via AppIcon component which enforces the size scale and theme tokens.

export const Icons = {
  // ── Navigation (tab bar) ──────────────────────────────────────────────
  navHome:      'home',
  navContacts:  'users',
  navInbox:     'message-circle',
  navTasks:     'check-square',
  navMore:      'grid',

  // ── Call controls ─────────────────────────────────────────────────────
  mute:         'mic',
  muted:        'mic-off',
  speaker:      'volume-2',
  hold:         'pause',
  keypad:       'grid',
  addNote:      'file-text',
  endCall:      'phone-off',

  // ── Phone ──────────────────────────────────────────────────────────────
  phone:        'phone',
  phoneCall:    'phone-call',
  callInbound:  'phone-incoming',
  callOutbound: 'phone-outgoing',
  callMissed:   'phone-missed',

  // ── Messaging channels ────────────────────────────────────────────────
  channelWhatsapp: 'message-circle',
  channelSms:      'message-square',
  channelEmail:    'mail',

  // ── AI / sparkle ──────────────────────────────────────────────────────
  // zap is the closest Feather equivalent for "AI did this" signaling
  ai: 'zap',

  // ── Actions ───────────────────────────────────────────────────────────
  add:          'plus',
  search:       'search',
  send:         'send',
  share:        'share-2',
  edit:         'edit-2',
  close:        'x',
  back:         'arrow-left',
  forward:      'chevron-right',
  expand:       'chevron-down',
  collapse:     'chevron-up',
  eye:          'eye',
  eyeOff:       'eye-off',
  refresh:      'refresh-cw',

  // ── Status / completion ───────────────────────────────────────────────
  check:           'check',
  checkCircle:     'check-circle',
  checkSquare:     'check-square',
  viewingComplete: 'check',
  viewingNoShow:   'x',
  play:            'play',
  pause:           'pause',
  stop:            'square',

  // ── Audio / recording ─────────────────────────────────────────────────
  mic:         'mic',
  micOff:      'mic-off',
  stopRecord:  'square',
  volume:      'volume-2',

  // ── More menu rows ────────────────────────────────────────────────────
  viewings:      'map-pin',
  callHistory:   'phone-call',
  settings:      'settings',
  profileUser:   'user',
  notifications: 'bell',
  notificationsOff: 'bell-off',
  tenants:       'key',
  finance:       'dollar-sign',
  intelligence:  'bar-chart-2',

  // ── Activity types (ContactDetail timeline) ────────────────────────────
  activityCall:    'phone',
  activityNote:    'file-text',
  activityMeeting: 'users',
  activityViewing: 'home',
  activityStatus:  'refresh-cw',
  activitySystem:  'settings',

  // ── Content / data ────────────────────────────────────────────────────
  calendar:    'calendar',
  clock:       'clock',
  mapPin:      'map-pin',
  mail:        'mail',
  file:        'file-text',
  user:        'user',
  users:       'users',
  home:        'home',
  trending:    'trending-up',
  activity:    'activity',
  barChart:    'bar-chart-2',
  loader:      'loader',

  // ── Feedback / system ─────────────────────────────────────────────────
  alertCircle:   'alert-circle',
  alertTriangle: 'alert-triangle',
  info:          'info',
  shield:        'shield',
  wifiOff:       'wifi-off',
  navigation:    'navigation',
  droplet:       'droplet',

  // ── Theme picker ──────────────────────────────────────────────────────
  themeLight:  'sun',
  themeDark:   'moon',
  themeSystem: 'monitor',
} as const;

export type IconKey = keyof typeof Icons;

// ── Size scale ────────────────────────────────────────────────────────────
// Use these constants everywhere — never arbitrary pixel values.
export const ICON_SIZE = {
  xs:  12,  // inline with small text, badges
  sm:  16,  // secondary metadata, list trailing icons
  md:  20,  // default: buttons, list leading icons, form fields
  lg:  24,  // tab bar, primary actions, headers
  xl:  32,  // empty states, large call controls
} as const;

export type IconSizeName = keyof typeof ICON_SIZE;
