import React, {useState} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
  SafeAreaView,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {contactsApi} from '../../api/contacts';
import {Contact} from '../../types';
import type {ContactsStackParamList} from '../../navigation/stacks/ContactsStack';

type NavProp = NativeStackNavigationProp<ContactsStackParamList>;

const STATUS_COLORS: Record<string, string> = {
  new:       'bg-slate-100 text-slate-700 border-slate-200',
  active:    'bg-green-50 text-green-700 border-green-200',
  qualified: 'bg-brand-50 text-brand-700 border-brand-200',
  nurturing: 'bg-amber-50 text-amber-700 border-amber-200',
  closed:    'bg-purple-50 text-purple-700 border-purple-200',
  archived:  'bg-slate-200 text-slate-600 border-slate-300',
};

function ContactRow({contact, onPress}: {contact: Contact; onPress: () => void}) {
  const initials =
    contact.first_name.charAt(0).toUpperCase() +
    contact.last_name.charAt(0).toUpperCase();

  return (
    <Pressable
      className="flex-row items-center bg-white shadow-sm border border-slate-100 rounded-3xl mx-5 mb-3 p-4"
      onPress={onPress}>
      <View className="w-14 h-14 rounded-full bg-brand-50 border border-brand-100 items-center justify-center mr-4">
        <Text className="text-brand-600 font-extrabold text-lg">{initials}</Text>
      </View>
      <View className="flex-1">
        <Text className="text-slate-900 font-bold text-base">
          {contact.first_name} {contact.last_name}
        </Text>
        <Text className="text-slate-500 font-medium text-sm mt-0.5">{contact.phone ?? contact.email}</Text>
      </View>
      <View className={`px-3 py-1 rounded-full border ${STATUS_COLORS[contact.status]}`}>
        <Text className={`text-[10px] font-bold uppercase tracking-wider ${STATUS_COLORS[contact.status].split(' ')[1]}`}>
          {contact.status}
        </Text>
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
    <SafeAreaView className="flex-1 bg-slate-50">
      {/* Header + search */}
      <View className="px-5 pt-6 pb-4 bg-white border-b border-slate-100 shadow-sm z-10">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-slate-900 text-3xl font-extrabold tracking-tight">Contacts</Text>
          <View className="w-10 h-10 bg-brand-50 rounded-full items-center justify-center">
            <Text className="text-brand-600 font-bold text-lg">{data?.data?.length || 0}</Text>
          </View>
        </View>
        <View className="flex-row items-center bg-slate-50 rounded-2xl px-4 py-3 border border-slate-200">
          <Text className="text-slate-400 mr-2">🔍</Text>
          <TextInput
            className="flex-1 text-slate-900 text-base"
            placeholder="Search by name or phone…"
            placeholderTextColor="#94a3b8"
            value={search}
            onChangeText={setSearch}
            clearButtonMode="while-editing"
          />
        </View>
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : (
        <FlatList
          className="flex-1 pt-4"
          contentContainerStyle={{ paddingBottom: 40 }}
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
            <View className="flex-1 items-center justify-center py-20 px-10">
              <View className="w-24 h-24 bg-brand-50 rounded-full items-center justify-center mb-6">
                <Text className="text-4xl">👥</Text>
              </View>
              <Text className="text-slate-800 text-xl font-bold mb-2 text-center">No contacts found</Text>
              <Text className="text-slate-500 text-center font-medium">Add a new contact or try adjusting your search terms.</Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}
