import React, {useState, useRef, useMemo} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
  SafeAreaView,
  Alert,
  Modal,
  useColorScheme,
} from 'react-native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {formatDistanceToNow, parseISO} from 'date-fns';
import {contactsApi} from '../../api/contacts';
import {Contact} from '../../types';
import type {ContactsStackParamList} from '../../navigation/stacks/ContactsStack';
import {useAuthStore} from '../../store/authStore';
import {SwipeableRow} from '../../components/SwipeableRow';

type NavProp = NativeStackNavigationProp<ContactsStackParamList>;

type FilterType = 'All' | 'Buyers' | 'Sellers' | 'Tenants' | 'Hot Leads' | 'My Contacts';

const STATUS_COLORS: Record<string, { bg: string; text: string; border: string }> = {
  new:       { bg: 'bg-zinc-800/60', text: 'text-zinc-400', border: 'border-zinc-700/60' },
  active:    { bg: 'bg-emerald-500/10', text: 'text-emerald-400', border: 'border-emerald-500/20' },
  qualified: { bg: 'bg-brand-500/10', text: 'text-brand-400', border: 'border-brand-500/20' },
  nurturing: { bg: 'bg-amber-500/10', text: 'text-amber-400', border: 'border-amber-500/20' },
  closed:    { bg: 'bg-purple-500/10', text: 'text-purple-400', border: 'border-purple-500/20' },
  archived:  { bg: 'bg-zinc-800', text: 'text-zinc-500', border: 'border-zinc-700' },
};

const SENTIMENT_COLORS: Record<string, string> = {
  hot:     'bg-danger',
  warm:    'bg-accent',
  cold:    'bg-info',
  neutral: 'bg-text-tertiary',
};

function ContactRow({
  contact,
  onPress,
  onCall,
  onMessage,
  isDark,
}: {
  contact: Contact;
  onPress: () => void;
  onCall: () => void;
  onMessage: () => void;
  isDark: boolean;
}) {
  const initials =
    contact.first_name.charAt(0).toUpperCase() +
    contact.last_name.charAt(0).toUpperCase();

  // Sentiment dot color based on latest call's sentiment
  const sentiment = contact.latestCall?.summary?.sentiment;
  const sentimentColor = sentiment ? SENTIMENT_COLORS[sentiment] : null;

  // Relative time for last contact
  const lastContactText = useMemo(() => {
    if (!contact.last_contacted_at) return 'Never contacted';
    try {
      const date = typeof contact.last_contacted_at === 'string'
        ? parseISO(contact.last_contacted_at)
        : new Date(contact.last_contacted_at);
      return `Last contact: ${formatDistanceToNow(date, {addSuffix: true})}`;
    } catch (e) {
      return 'Last contact: unknown';
    }
  }, [contact.last_contacted_at]);

  const statusStyle = STATUS_COLORS[contact.status] || STATUS_COLORS.new;

  return (
    <SwipeableRow onCall={onCall} onMessage={onMessage}>
      <Pressable
        className="flex-row items-center px-4 py-3.5 active:opacity-90"
        onPress={onPress}
      >
        {/* Avatar with sentiment dot overlay */}
        <View className="relative mr-3">
          <View className="w-10 h-10 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center">
            <Text className="text-brand-500 font-extrabold text-sm">{initials}</Text>
          </View>
          {sentimentColor && (
            <View className={`absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 ${
              isDark ? 'border-surface-card' : 'border-white'
            } ${sentimentColor}`} />
          )}
        </View>

        {/* Info */}
        <View className="flex-1 mr-2">
          <View className="flex-row items-center flex-wrap gap-1.5">
            <Text className={`font-bold text-base ${isDark ? 'text-text-primary' : 'text-slate-900'}`}>
              {contact.first_name} {contact.last_name}
            </Text>
            <View className={`px-2 py-0.5 rounded-full border ${statusStyle.bg} ${statusStyle.border}`}>
              <Text className={`text-[9px] font-bold uppercase tracking-wider ${statusStyle.text}`}>
                {contact.status}
              </Text>
            </View>
          </View>
          <Text className={`text-xs mt-0.5 ${isDark ? 'text-text-tertiary' : 'text-slate-400'}`}>
            {lastContactText}
          </Text>
        </View>

        {/* Quick-call button */}
        <Pressable
          onPress={(e) => {
            e.stopPropagation();
            onCall();
          }}
          className="w-10 h-10 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center active:bg-brand-500/20"
        >
          <Icon name="phone" size={16} color="#10B981" />
        </Pressable>
      </Pressable>
    </SwipeableRow>
  );
}

export function ContactsScreen() {
  const [search, setSearch] = useState('');
  const [activeFilter, setActiveFilter] = useState<FilterType>('All');
  const [createModalVisible, setCreateModalVisible] = useState(false);
  
  // New Contact Form State
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [contactType, setContactType] = useState<'buyer' | 'seller' | 'tenant' | 'landlord' | 'investor'>('buyer');

  const navigation = useNavigation<NavProp>();
  const queryClient = useQueryClient();
  const {user} = useAuthStore();
  const colorScheme = useColorScheme();
  const isDark = colorScheme !== 'light';

  const flatListRef = useRef<FlatList<Contact>>(null);

  // Queries
  const {data, isLoading, refetch} = useQuery({
    queryKey: ['contacts', search, activeFilter === 'My Contacts'],
    queryFn: () =>
      contactsApi
        .list({
          search,
          mine: activeFilter === 'My Contacts' ? true : undefined,
        })
        .then((r) => r.data),
    staleTime: 1000 * 60 * 2,
  });

  // Create Contact Mutation
  const createContact = useMutation({
    mutationFn: (newContact: Partial<Contact>) => contactsApi.create(newContact),
    onSuccess: (response) => {
      queryClient.invalidateQueries({queryKey: ['contacts']});
      setFirstName('');
      setLastName('');
      setPhone('');
      setEmail('');
      setContactType('buyer');
      setCreateModalVisible(false);
      
      const newId = response.data?.id;
      if (newId) {
        navigation.navigate('ContactDetail', {contactId: newId});
      }
    },
    onError: () => {
      Alert.alert('Error', 'Could not create contact. Please try again.');
    },
  });

  const handleCreateContact = () => {
    if (!firstName.trim() || !lastName.trim() || !phone.trim()) {
      Alert.alert('Required Fields', 'First name, last name, and phone number are required.');
      return;
    }
    createContact.mutate({
      first_name: firstName.trim(),
      last_name: lastName.trim(),
      phone: phone.trim(),
      email: email.trim() || undefined,
      type: contactType,
      status: 'new',
    });
  };

  // Client-side filtering & sorting
  const filteredContacts = useMemo(() => {
    const rawList = data?.data ?? [];
    let list = [...rawList];

    if (activeFilter === 'Buyers') {
      list = list.filter((c) => c.type === 'buyer');
    } else if (activeFilter === 'Sellers') {
      list = list.filter((c) => c.type === 'seller');
    } else if (activeFilter === 'Tenants') {
      list = list.filter((c) => c.type === 'tenant');
    } else if (activeFilter === 'Hot Leads') {
      list = list.filter(
        (c) => (c.intent_score && c.intent_score >= 70) || c.status === 'qualified'
      );
    }

    // Sort alphabetically by first name
    return list.sort((a, b) => {
      const nameA = `${a.first_name} ${a.last_name}`.toLowerCase();
      const nameB = `${b.first_name} ${b.last_name}`.toLowerCase();
      return nameA.localeCompare(nameB);
    });
  }, [data, activeFilter]);

  // A-Z indices calculation
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
  const letterIndices = useMemo(() => {
    const indices: Record<string, number> = {};
    filteredContacts.forEach((contact, idx) => {
      const firstLetter = contact.first_name.charAt(0).toUpperCase();
      if (firstLetter && /^[A-Z]$/.test(firstLetter) && indices[firstLetter] === undefined) {
        indices[firstLetter] = idx;
      }
    });
    return indices;
  }, [filteredContacts]);

  const scrollToLetter = (letter: string) => {
    const idx = letterIndices[letter];
    if (idx !== undefined && flatListRef.current) {
      flatListRef.current.scrollToIndex({
        index: idx,
        animated: true,
        viewPosition: 0,
      });
    }
  };

  const handleCall = (contact: Contact) => {
    if (!contact.phone) {
      Alert.alert('No Phone', 'This contact does not have a phone number.');
      return;
    }
    navigation.navigate('InCall', {contactId: contact.id, phoneNumber: contact.phone});
  };

  const handleMessage = (contact: Contact) => {
    (navigation as any).navigate('Inbox', {
      screen: 'Conversation',
      params: {
        contactId: contact.id,
        contactName: `${contact.first_name} ${contact.last_name}`,
      },
    });
  };

  const getItemLayout = (_: any, index: number) => ({
    length: 76, // Height of row + padding
    offset: 76 * index,
    index,
  });

  const onScrollToIndexFailed = (info: { index: number }) => {
    flatListRef.current?.scrollToOffset({
      offset: info.index * 76,
      animated: true,
    });
  };

  // Styling helpers
  const bgScreen = isDark ? 'bg-surface-page' : 'bg-slate-50';
  const bgCard = isDark ? 'bg-[#090d16]' : 'bg-white';
  const bgInput = isDark ? 'bg-[#111827]' : 'bg-slate-100';
  const textPrimary = isDark ? 'text-text-primary' : 'text-slate-900';
  const borderHeader = isDark ? 'border-zinc-800/80' : 'border-slate-200/60';

  return (
    <SafeAreaView className={`flex-1 ${bgScreen}`}>
      {/* Header + Search bar pinned at top */}
      <View className={`px-4 pt-4 pb-3 ${bgCard} border-b ${borderHeader} z-10`}>
        <View className="flex-row justify-between items-center mb-3">
          <Text className={`${textPrimary} text-2xl font-black tracking-tight`}>Contacts</Text>
          
          <View className="flex-row items-center gap-3">
            {/* Total Contacts badge */}
            <View className="px-2.5 py-1 bg-brand-500/10 rounded-full border border-brand-500/20">
              <Text className="text-brand-500 font-extrabold text-xs">{filteredContacts.length}</Text>
            </View>
            
            {/* Add Contact Button */}
            <Pressable
              onPress={() => setCreateModalVisible(true)}
              className="w-8 h-8 rounded-full bg-brand-500 items-center justify-center active:bg-brand-600"
            >
              <Icon name="plus" size={16} color="#ffffff" />
            </Pressable>
          </View>
        </View>

        {/* Search bar */}
        <View className={`flex-row items-center ${bgInput} rounded-xl px-3 py-2.5 border ${
          isDark ? 'border-zinc-800' : 'border-slate-200'
        }`}>
          <Icon name="search" size={16} color={isDark ? '#71717A' : '#94a3b8'} className="mr-2" />
          <TextInput
            className={`flex-1 text-sm ${textPrimary} p-0`}
            placeholder="Search by name or phone…"
            placeholderTextColor={isDark ? '#71717A' : '#94a3b8'}
            value={search}
            onChangeText={setSearch}
            clearButtonMode="while-editing"
          />
        </View>

        {/* Filter chips */}
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={['All', 'Buyers', 'Sellers', 'Tenants', 'Hot Leads', 'My Contacts'] as FilterType[]}
          keyExtractor={(item) => item}
          contentContainerStyle={{paddingTop: 12, paddingBottom: 2}}
          renderItem={({item}) => {
            const isActive = activeFilter === item;
            return (
              <Pressable
                onPress={() => setActiveFilter(item)}
                className={`px-4 py-2 rounded-full border mr-2 ${
                  isActive
                    ? 'bg-brand-500 border-brand-500 shadow-md shadow-brand-500/20'
                    : isDark
                    ? 'bg-[#111827] border-zinc-800 active:bg-zinc-800'
                    : 'bg-slate-100 border-slate-200 active:bg-slate-200'
                }`}
              >
                <Text
                  className={`text-xs font-bold ${
                    isActive ? 'text-white' : isDark ? 'text-text-secondary' : 'text-slate-600'
                  }`}
                >
                  {item}
                </Text>
              </Pressable>
            );
          }}
        />
      </View>

      {/* Main List & A-Z index container */}
      <View className="flex-1 flex-row">
        {isLoading ? (
          <View className="flex-1 items-center justify-center">
            <ActivityIndicator color="#10b981" size="large" />
          </View>
        ) : (
          <FlatList
            ref={flatListRef}
            className="flex-1 pt-3 pr-8" // Add padding right to avoid overlap with A-Z index
            contentContainerStyle={{paddingBottom: 40}}
            data={filteredContacts}
            keyExtractor={(c) => String(c.id)}
            getItemLayout={getItemLayout}
            onScrollToIndexFailed={onScrollToIndexFailed}
            renderItem={({item}) => (
              <ContactRow
                contact={item}
                isDark={isDark}
                onPress={() => navigation.navigate('ContactDetail', {contactId: item.id})}
                onCall={() => handleCall(item)}
                onMessage={() => handleMessage(item)}
              />
            )}
            onRefresh={refetch}
            refreshing={isLoading}
            ListEmptyComponent={
              <View className="flex-1 items-center justify-center py-20 px-8">
                <View className="w-16 h-16 bg-brand-500/10 border border-brand-500/20 rounded-full items-center justify-center mb-4">
                  <Icon name="users" size={24} color="#10B981" />
                </View>
                <Text className={`${textPrimary} text-lg font-bold mb-1.5 text-center`}>No contacts found</Text>
                <Text className="text-text-secondary text-xs text-center leading-4 max-w-[240px]">
                  No matches. Try modifying your search or filters.
                </Text>
              </View>
            }
          />
        )}

        {/* A-Z scroll index on the right edge */}
        <View className="absolute right-1.5 top-0 bottom-0 justify-center items-center w-6 py-4 z-20">
          {alphabet.map((letter) => {
            const hasContacts = letterIndices[letter] !== undefined;
            return (
              <Pressable
                key={letter}
                onPress={() => hasContacts && scrollToLetter(letter)}
                className="py-0.5 w-full items-center justify-center"
                hitSlop={{top: 5, bottom: 5, left: 5, right: 5}}
              >
                <Text
                  className={`text-[9px] font-extrabold ${
                    hasContacts
                      ? 'text-brand-500 scale-110'
                      : isDark
                      ? 'text-zinc-700/50'
                      : 'text-slate-300/60'
                  }`}
                >
                  {letter}
                </Text>
              </Pressable>
            );
          })}
        </View>
      </View>

      {/* New Contact Bottom Sheet Modal */}
      <Modal
        visible={createModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setCreateModalVisible(false)}
      >
        <View className="flex-1 justify-end bg-[#020617]/60">
          <Pressable
            className="flex-1"
            onPress={() => setCreateModalVisible(false)}
          />
          <View className={`${bgCard} border-t ${
            isDark ? 'border-zinc-800' : 'border-slate-200'
          } rounded-t-3xl p-5 pb-8 max-h-[90%]`}>
            {/* Grab handle */}
            <View className={`w-12 h-1 ${isDark ? 'bg-zinc-800' : 'bg-slate-300'} rounded-full self-center mb-4`} />
            
            <View className="flex-row justify-between items-center mb-4">
              <Text className={`${textPrimary} font-black text-xl`}>New Contact</Text>
              <Pressable
                onPress={() => setCreateModalVisible(false)}
                className={`w-8 h-8 rounded-full ${
                  isDark ? 'bg-zinc-800' : 'bg-slate-100'
                } items-center justify-center`}
              >
                <Icon name="x" size={16} color={isDark ? '#A1A1AA' : '#64748b'} />
              </Pressable>
            </View>

            {/* Form */}
            <View className="gap-3.5 mb-6">
              <View>
                <Text className={`text-xs font-bold mb-1.5 ${isDark ? 'text-text-secondary' : 'text-slate-600'}`}>
                  First Name <Text className="text-danger">*</Text>
                </Text>
                <TextInput
                  className={`rounded-xl px-4 py-3 text-sm ${bgInput} ${textPrimary} border ${
                    isDark ? 'border-zinc-850' : 'border-slate-200'
                  }`}
                  placeholder="e.g. John"
                  placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
                  value={firstName}
                  onChangeText={setFirstName}
                />
              </View>

              <View>
                <Text className={`text-xs font-bold mb-1.5 ${isDark ? 'text-text-secondary' : 'text-slate-600'}`}>
                  Last Name <Text className="text-danger">*</Text>
                </Text>
                <TextInput
                  className={`rounded-xl px-4 py-3 text-sm ${bgInput} ${textPrimary} border ${
                    isDark ? 'border-zinc-850' : 'border-slate-200'
                  }`}
                  placeholder="e.g. Doe"
                  placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
                  value={lastName}
                  onChangeText={setLastName}
                />
              </View>

              <View>
                <Text className={`text-xs font-bold mb-1.5 ${isDark ? 'text-text-secondary' : 'text-slate-600'}`}>
                  Phone Number <Text className="text-danger">*</Text>
                </Text>
                <TextInput
                  className={`rounded-xl px-4 py-3 text-sm ${bgInput} ${textPrimary} border ${
                    isDark ? 'border-zinc-850' : 'border-slate-200'
                  }`}
                  placeholder="e.g. +234 803 123 4567"
                  placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
                  keyboardType="phone-pad"
                  value={phone}
                  onChangeText={setPhone}
                />
              </View>

              <View>
                <Text className={`text-xs font-bold mb-1.5 ${isDark ? 'text-text-secondary' : 'text-slate-600'}`}>
                  Email Address
                </Text>
                <TextInput
                  className={`rounded-xl px-4 py-3 text-sm ${bgInput} ${textPrimary} border ${
                    isDark ? 'border-zinc-850' : 'border-slate-200'
                  }`}
                  placeholder="e.g. john.doe@example.com"
                  placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  value={email}
                  onChangeText={setEmail}
                />
              </View>

              <View>
                <Text className={`text-xs font-bold mb-1.5 ${isDark ? 'text-text-secondary' : 'text-slate-600'}`}>
                  Contact Type
                </Text>
                <View className="flex-row gap-2 flex-wrap">
                  {(['buyer', 'seller', 'tenant', 'landlord', 'investor'] as const).map((t) => {
                    const selected = contactType === t;
                    return (
                      <Pressable
                        key={t}
                        onPress={() => setContactType(t)}
                        className={`px-3 py-2 rounded-xl border capitalize ${
                          selected
                            ? 'bg-brand-500/15 border-brand-500'
                            : isDark
                            ? 'bg-zinc-900 border-zinc-800'
                            : 'bg-slate-100 border-slate-200'
                        }`}
                      >
                        <Text
                          className={`text-xs font-bold ${
                            selected ? 'text-brand-500' : isDark ? 'text-text-secondary' : 'text-slate-600'
                          }`}
                        >
                          {t}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>
            </View>

            {/* Actions */}
            <View className="flex-row gap-3">
              <Pressable
                className={`flex-1 rounded-xl py-3.5 items-center ${
                  isDark ? 'bg-zinc-800 active:bg-zinc-700' : 'bg-slate-100 active:bg-slate-200'
                }`}
                onPress={() => setCreateModalVisible(false)}
              >
                <Text className={`font-bold text-sm ${isDark ? 'text-text-secondary' : 'text-slate-600'}`}>Cancel</Text>
              </Pressable>
              
              <Pressable
                className={`flex-1 rounded-xl py-3.5 items-center bg-brand-500 active:bg-brand-600 ${
                  (!firstName.trim() || !lastName.trim() || !phone.trim() || createContact.isPending)
                    ? 'opacity-50'
                    : ''
                }`}
                onPress={handleCreateContact}
                disabled={!firstName.trim() || !lastName.trim() || !phone.trim() || createContact.isPending}
              >
                {createContact.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text className="text-white font-bold text-sm">Save Contact</Text>
                )}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
