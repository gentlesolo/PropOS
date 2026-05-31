import React, {useState} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {contactsApi} from '../../api/contacts';
import {Contact} from '../../types';
import type {ContactsStackParamList} from '../../navigation/stacks/ContactsStack';

type NavProp = NativeStackNavigationProp<ContactsStackParamList>;

const STATUS_COLORS: Record<string, string> = {
  new:       'bg-slate-500',
  active:    'bg-green-500',
  qualified: 'bg-brand-500',
  nurturing: 'bg-amber-500',
  closed:    'bg-purple-500',
  archived:  'bg-slate-700',
};

function ContactRow({contact, onPress}: {contact: Contact; onPress: () => void}) {
  const initials =
    contact.first_name.charAt(0).toUpperCase() +
    contact.last_name.charAt(0).toUpperCase();

  return (
    <Pressable
      className="flex-row items-center px-4 py-3 border-b border-slate-800"
      onPress={onPress}>
      <View className="w-11 h-11 rounded-full bg-brand-700 items-center justify-center mr-3">
        <Text className="text-white font-semibold">{initials}</Text>
      </View>
      <View className="flex-1">
        <Text className="text-white font-medium">
          {contact.first_name} {contact.last_name}
        </Text>
        <Text className="text-slate-400 text-sm mt-0.5">{contact.phone ?? contact.email}</Text>
      </View>
      <View className={`px-2 py-0.5 rounded-full ${STATUS_COLORS[contact.status]}`}>
        <Text className="text-white text-xs capitalize">{contact.status}</Text>
      </View>
    </Pressable>
  );
}

export function ContactsScreen() {
  const [search, setSearch] = useState('');
  const navigation = useNavigation<NavProp>();

  const {data, isLoading, refetch} = useQuery({
    queryKey: ['contacts', search],
    queryFn: () => contactsApi.list({search, mine: true}).then(r => r.data),
    staleTime: 1000 * 60 * 2,
  });

  return (
    <View className="flex-1 bg-surface">
      {/* Header + search */}
      <View className="pt-14 px-4 pb-3 bg-surface">
        <Text className="text-white text-2xl font-bold mb-3">Contacts</Text>
        <TextInput
          className="bg-surface-input text-white rounded-xl px-4 py-2.5 text-sm"
          placeholder="Search by name or phone…"
          placeholderTextColor="#64748b"
          value={search}
          onChangeText={setSearch}
          clearButtonMode="while-editing"
        />
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#3b82f6" />
        </View>
      ) : (
        <FlatList
          data={data?.data ?? []}
          keyExtractor={c => String(c.id)}
          renderItem={({item}) => (
            <ContactRow
              contact={item}
              onPress={() => navigation.navigate('ContactDetail', {contactId: item.id})}
            />
          )}
          onRefresh={refetch}
          refreshing={isLoading}
          ListEmptyComponent={
            <View className="flex-1 items-center justify-center py-16">
              <Text className="text-slate-500">No contacts found</Text>
            </View>
          }
        />
      )}
    </View>
  );
}
