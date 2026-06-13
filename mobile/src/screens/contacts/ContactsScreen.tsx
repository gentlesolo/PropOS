import React, {useState, useRef, useMemo} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
  Alert,
  Modal,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
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
import {useTheme} from '../../theme/ThemeProvider';
import {ThemeTokens} from '../../theme/tokens';

type NavProp = NativeStackNavigationProp<ContactsStackParamList>;
type FilterType = 'All' | 'Buyers' | 'Sellers' | 'Tenants' | 'Hot Leads' | 'My Contacts';

const STATUS_STYLE: Record<string, {bg: string; text: string; border: string}> = {
  new:       {bg: '#64748B1A', text: '#94A3B8', border: '#64748B33'},
  active:    {bg: '#10B9811A', text: '#10B981',  border: '#10B98133'},
  qualified: {bg: '#10B9811A', text: '#10B981',  border: '#10B98133'},
  nurturing: {bg: '#F59E0B1A', text: '#F59E0B',  border: '#F59E0B33'},
  closed:    {bg: '#A855F71A', text: '#A855F7',  border: '#A855F733'},
  archived:  {bg: '#3F3F461A', text: '#71717A',  border: '#3F3F4633'},
};

const SENTIMENT_DOT: Record<string, string> = {
  hot: '#F43F5E', warm: '#F59E0B', cold: '#0EA5E9', neutral: '#71717A',
};

function ContactRow({
  contact,
  onPress,
  onCall,
  onMessage,
  tokens,
}: {
  contact: Contact;
  onPress: () => void;
  onCall: () => void;
  onMessage: () => void;
  tokens: ThemeTokens;
}) {
  const initials =
    contact.first_name.charAt(0).toUpperCase() + contact.last_name.charAt(0).toUpperCase();

  const sentiment = contact.latestCall?.summary?.sentiment;
  const dotColor = sentiment ? SENTIMENT_DOT[sentiment] : null;

  const lastContactText = useMemo(() => {
    if (!contact.last_contacted_at) return 'Never contacted';
    try {
      const date = typeof contact.last_contacted_at === 'string'
        ? parseISO(contact.last_contacted_at)
        : new Date(contact.last_contacted_at);
      return `Last contact: ${formatDistanceToNow(date, {addSuffix: true})}`;
    } catch {
      return 'Last contact: unknown';
    }
  }, [contact.last_contacted_at]);

  const statusStyle = STATUS_STYLE[contact.status] || STATUS_STYLE.new;

  return (
    <SwipeableRow onCall={onCall} onMessage={onMessage}>
      <Pressable
        style={{flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 14}}
        onPress={onPress}
      >
        {/* Avatar + sentiment dot */}
        <View style={{position: 'relative', marginRight: 12}}>
          <View
            style={{
              width: 40,
              height: 40,
              borderRadius: 20,
              backgroundColor: `${tokens.brandPrimary}1A`,
              borderWidth: 1,
              borderColor: `${tokens.brandPrimary}33`,
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 14}}>{initials}</Text>
          </View>
          {dotColor && (
            <View
              style={{
                position: 'absolute',
                bottom: 0,
                right: 0,
                width: 12,
                height: 12,
                borderRadius: 6,
                borderWidth: 2,
                borderColor: tokens.surfacePage,
                backgroundColor: dotColor,
              }}
            />
          )}
        </View>

        {/* Info */}
        <View style={{flex: 1, marginRight: 8}}>
          <View style={{flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap', gap: 6}}>
            <Text style={{color: tokens.textPrimary, fontWeight: '700', fontSize: 16}}>
              {contact.first_name} {contact.last_name}
            </Text>
            <View
              style={{
                paddingHorizontal: 8,
                paddingVertical: 2,
                borderRadius: 999,
                borderWidth: 1,
                backgroundColor: statusStyle.bg,
                borderColor: statusStyle.border,
              }}
            >
              <Text style={{color: statusStyle.text, fontSize: 9, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8}}>
                {contact.status}
              </Text>
            </View>
          </View>
          <Text style={{color: tokens.textTertiary, fontSize: 12, marginTop: 2}}>
            {lastContactText}
          </Text>
        </View>

        {/* Quick-call button */}
        <Pressable
          onPress={(e) => { e.stopPropagation(); onCall(); }}
          style={{
            width: 40,
            height: 40,
            borderRadius: 20,
            backgroundColor: `${tokens.brandPrimary}1A`,
            borderWidth: 1,
            borderColor: `${tokens.brandPrimary}33`,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Icon name="phone" size={16} color={tokens.brandPrimary} />
        </Pressable>
      </Pressable>
    </SwipeableRow>
  );
}

export function ContactsScreen() {
  const {tokens} = useTheme();
  const [search, setSearch] = useState('');
  const [activeFilter, setActiveFilter] = useState<FilterType>('All');
  const [createModalVisible, setCreateModalVisible] = useState(false);

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [contactType, setContactType] = useState<'buyer' | 'seller' | 'tenant' | 'landlord' | 'investor'>('buyer');

  const navigation = useNavigation<NavProp>();
  const queryClient = useQueryClient();
  const {user} = useAuthStore();
  const flatListRef = useRef<FlatList<Contact>>(null);

  const {data, isLoading, refetch} = useQuery({
    queryKey: ['contacts', search, activeFilter === 'My Contacts'],
    queryFn: () =>
      contactsApi.list({search, mine: activeFilter === 'My Contacts' ? true : undefined}).then((r) => r.data),
    staleTime: 1000 * 60 * 2,
  });

  const createContact = useMutation({
    mutationFn: (newContact: Partial<Contact>) => contactsApi.create(newContact),
    onSuccess: (response) => {
      queryClient.invalidateQueries({queryKey: ['contacts']});
      setFirstName(''); setLastName(''); setPhone(''); setEmail(''); setContactType('buyer');
      setCreateModalVisible(false);
      const newId = response.data?.id;
      if (newId) navigation.navigate('ContactDetail', {contactId: newId});
    },
    onError: () => Alert.alert('Error', 'Could not create contact. Please try again.'),
  });

  const handleCreateContact = () => {
    if (!firstName.trim() || !lastName.trim() || !phone.trim()) {
      Alert.alert('Required Fields', 'First name, last name, and phone number are required.');
      return;
    }
    createContact.mutate({
      first_name: firstName.trim(), last_name: lastName.trim(),
      phone: phone.trim(), email: email.trim() || undefined,
      type: contactType, status: 'new',
    });
  };

  const filteredContacts = useMemo(() => {
    const rawList = data?.data ?? [];
    let list = [...rawList];
    if (activeFilter === 'Buyers') list = list.filter((c) => c.type === 'buyer');
    else if (activeFilter === 'Sellers') list = list.filter((c) => c.type === 'seller');
    else if (activeFilter === 'Tenants') list = list.filter((c) => c.type === 'tenant');
    else if (activeFilter === 'Hot Leads') list = list.filter((c) => (c.intent_score && c.intent_score >= 70) || c.status === 'qualified');
    return list.sort((a, b) =>
      `${a.first_name} ${a.last_name}`.toLowerCase().localeCompare(`${b.first_name} ${b.last_name}`.toLowerCase())
    );
  }, [data, activeFilter]);

  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
  const letterIndices = useMemo(() => {
    const indices: Record<string, number> = {};
    filteredContacts.forEach((contact, idx) => {
      const l = contact.first_name.charAt(0).toUpperCase();
      if (l && /^[A-Z]$/.test(l) && indices[l] === undefined) indices[l] = idx;
    });
    return indices;
  }, [filteredContacts]);

  const scrollToLetter = (letter: string) => {
    const idx = letterIndices[letter];
    if (idx !== undefined && flatListRef.current) {
      flatListRef.current.scrollToIndex({index: idx, animated: true, viewPosition: 0});
    }
  };

  const handleCall = (contact: Contact) => {
    if (!contact.phone) { Alert.alert('No Phone', 'This contact does not have a phone number.'); return; }
    navigation.navigate('InCall', {contactId: contact.id, phoneNumber: contact.phone});
  };

  const handleMessage = (contact: Contact) => {
    (navigation as any).navigate('Inbox', {
      screen: 'Conversation',
      params: {contactId: contact.id, contactName: `${contact.first_name} ${contact.last_name}`},
    });
  };

  const getItemLayout = (_: any, index: number) => ({length: 76, offset: 76 * index, index});
  const onScrollToIndexFailed = (info: {index: number}) => {
    flatListRef.current?.scrollToOffset({offset: info.index * 76, animated: true});
  };

  const inputStyle = {
    backgroundColor: tokens.surfaceInput,
    color: tokens.textPrimary,
    borderWidth: 1,
    borderColor: tokens.borderDefault,
    borderRadius: 12,
    paddingHorizontal: 16,
    paddingVertical: 12,
    fontSize: 14,
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header + Search */}
      <View
        style={{
          paddingHorizontal: 16,
          paddingTop: 16,
          paddingBottom: 12,
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          zIndex: 10,
        }}
      >
        <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12}}>
          <Text style={{color: tokens.textPrimary, fontSize: 24, fontWeight: '900', letterSpacing: -0.5}}>Contacts</Text>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 12}}>
            <View
              style={{
                paddingHorizontal: 10,
                paddingVertical: 4,
                backgroundColor: `${tokens.brandPrimary}1A`,
                borderRadius: 999,
                borderWidth: 1,
                borderColor: `${tokens.brandPrimary}33`,
              }}
            >
              <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12}}>{filteredContacts.length}</Text>
            </View>
            <Pressable
              onPress={() => setCreateModalVisible(true)}
              style={{width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.brandPrimary, alignItems: 'center', justifyContent: 'center'}}
            >
              <Icon name="plus" size={16} color="#FFFFFF" />
            </Pressable>
          </View>
        </View>

        {/* Search */}
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            backgroundColor: tokens.surfaceInput,
            borderRadius: 12,
            paddingHorizontal: 12,
            paddingVertical: 10,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            gap: 8,
          }}
        >
          <Icon name="search" size={16} color={tokens.textTertiary} />
          <TextInput
            style={{flex: 1, fontSize: 14, color: tokens.textPrimary, padding: 0}}
            placeholder="Search by name or phone…"
            placeholderTextColor={tokens.textTertiary}
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
          contentContainerStyle={{paddingTop: 12, paddingBottom: 2, gap: 8}}
          renderItem={({item}) => {
            const isActive = activeFilter === item;
            return (
              <Pressable
                onPress={() => setActiveFilter(item)}
                style={{
                  paddingHorizontal: 16,
                  paddingVertical: 8,
                  borderRadius: 999,
                  borderWidth: 1,
                  backgroundColor: isActive ? tokens.brandPrimary : tokens.surfaceRaised,
                  borderColor: isActive ? tokens.brandPrimary : tokens.borderDefault,
                }}
              >
                <Text style={{fontSize: 12, fontWeight: '700', color: isActive ? '#FFFFFF' : tokens.textSecondary}}>
                  {item}
                </Text>
              </Pressable>
            );
          }}
        />
      </View>

      {/* List + A-Z index */}
      <View style={{flex: 1, flexDirection: 'row'}}>
        {isLoading ? (
          <View style={{flex: 1, alignItems: 'center', justifyContent: 'center'}}>
            <ActivityIndicator color={tokens.brandPrimary} size="large" />
          </View>
        ) : (
          <FlatList
            ref={flatListRef}
            style={{flex: 1, paddingTop: 12, paddingRight: 32}}
            contentContainerStyle={{paddingBottom: 40}}
            data={filteredContacts}
            keyExtractor={(c) => String(c.id)}
            getItemLayout={getItemLayout}
            onScrollToIndexFailed={onScrollToIndexFailed}
            renderItem={({item}) => (
              <ContactRow
                contact={item}
                tokens={tokens}
                onPress={() => navigation.navigate('ContactDetail', {contactId: item.id})}
                onCall={() => handleCall(item)}
                onMessage={() => handleMessage(item)}
              />
            )}
            onRefresh={refetch}
            refreshing={isLoading}
            ListEmptyComponent={
              <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 80, paddingHorizontal: 32}}>
                <View
                  style={{
                    width: 64,
                    height: 64,
                    backgroundColor: `${tokens.brandPrimary}1A`,
                    borderWidth: 1,
                    borderColor: `${tokens.brandPrimary}33`,
                    borderRadius: 32,
                    alignItems: 'center',
                    justifyContent: 'center',
                    marginBottom: 16,
                  }}
                >
                  <Icon name="users" size={24} color={tokens.brandPrimary} />
                </View>
                <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '700', marginBottom: 6, textAlign: 'center'}}>
                  No contacts found
                </Text>
                <Text style={{color: tokens.textSecondary, fontSize: 12, textAlign: 'center', lineHeight: 16, maxWidth: 240}}>
                  No matches. Try modifying your search or filters.
                </Text>
              </View>
            }
          />
        )}

        {/* A-Z index */}
        <View style={{position: 'absolute', right: 6, top: 0, bottom: 0, justifyContent: 'center', alignItems: 'center', width: 24, paddingVertical: 16, zIndex: 20}}>
          {alphabet.map((letter) => {
            const hasContacts = letterIndices[letter] !== undefined;
            return (
              <Pressable
                key={letter}
                onPress={() => hasContacts && scrollToLetter(letter)}
                style={{paddingVertical: 2, width: '100%', alignItems: 'center', justifyContent: 'center'}}
                hitSlop={{top: 5, bottom: 5, left: 5, right: 5}}
              >
                <Text
                  style={{
                    fontSize: 9,
                    fontWeight: '800',
                    color: hasContacts ? tokens.brandPrimary : tokens.textDisabled,
                  }}
                >
                  {letter}
                </Text>
              </Pressable>
            );
          })}
        </View>
      </View>

      {/* New Contact modal */}
      <Modal
        visible={createModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setCreateModalVisible(false)}
      >
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(2,6,23,0.6)'}}>
          <Pressable style={{flex: 1}} onPress={() => setCreateModalVisible(false)} />
          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderTopWidth: 1,
              borderTopColor: tokens.borderDefault,
              borderTopLeftRadius: 24,
              borderTopRightRadius: 24,
              padding: 20,
              paddingBottom: 32,
              maxHeight: '90%',
            }}
          >
            <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 16}} />

            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16}}>
              <Text style={{color: tokens.textPrimary, fontWeight: '900', fontSize: 20}}>New Contact</Text>
              <Pressable
                onPress={() => setCreateModalVisible(false)}
                style={{width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.surfaceRaised, alignItems: 'center', justifyContent: 'center'}}
              >
                <Icon name="x" size={16} color={tokens.textSecondary} />
              </Pressable>
            </View>

            <View style={{gap: 14, marginBottom: 24}}>
              {[
                {label: 'First Name', value: firstName, onChange: setFirstName, placeholder: 'e.g. John', required: true},
                {label: 'Last Name', value: lastName, onChange: setLastName, placeholder: 'e.g. Doe', required: true},
                {label: 'Phone Number', value: phone, onChange: setPhone, placeholder: 'e.g. +234 803 123 4567', required: true, keyboardType: 'phone-pad' as const},
                {label: 'Email Address', value: email, onChange: setEmail, placeholder: 'e.g. john@example.com', required: false, keyboardType: 'email-address' as const},
              ].map(({label, value, onChange, placeholder, required, keyboardType}) => (
                <View key={label}>
                  <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 6}}>
                    {label} {required && <Text style={{color: '#F43F5E'}}>*</Text>}
                  </Text>
                  <TextInput
                    style={inputStyle}
                    placeholder={placeholder}
                    placeholderTextColor={tokens.textTertiary}
                    value={value}
                    onChangeText={onChange}
                    keyboardType={keyboardType}
                    autoCapitalize={keyboardType === 'email-address' ? 'none' : 'words'}
                  />
                </View>
              ))}

              <View>
                <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 6}}>Contact Type</Text>
                <View style={{flexDirection: 'row', gap: 8, flexWrap: 'wrap'}}>
                  {(['buyer', 'seller', 'tenant', 'landlord', 'investor'] as const).map((t) => {
                    const selected = contactType === t;
                    return (
                      <Pressable
                        key={t}
                        onPress={() => setContactType(t)}
                        style={{
                          paddingHorizontal: 12,
                          paddingVertical: 8,
                          borderRadius: 12,
                          borderWidth: 1,
                          backgroundColor: selected ? `${tokens.brandPrimary}1A` : tokens.surfaceRaised,
                          borderColor: selected ? tokens.brandPrimary : tokens.borderDefault,
                        }}
                      >
                        <Text
                          style={{
                            fontSize: 12,
                            fontWeight: '700',
                            textTransform: 'capitalize',
                            color: selected ? tokens.brandPrimary : tokens.textSecondary,
                          }}
                        >
                          {t}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>
            </View>

            <View style={{flexDirection: 'row', gap: 12}}>
              <Pressable
                style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.surfaceRaised}}
                onPress={() => setCreateModalVisible(false)}
              >
                <Text style={{fontWeight: '700', fontSize: 14, color: tokens.textSecondary}}>Cancel</Text>
              </Pressable>
              <Pressable
                style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.brandPrimary, opacity: (!firstName.trim() || !lastName.trim() || !phone.trim() || createContact.isPending) ? 0.5 : 1}}
                onPress={handleCreateContact}
                disabled={!firstName.trim() || !lastName.trim() || !phone.trim() || createContact.isPending}
              >
                {createContact.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text style={{color: tokens.textInverse, fontWeight: '700', fontSize: 14}}>Save Contact</Text>
                )}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
