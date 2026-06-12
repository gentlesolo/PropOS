import React from 'react';
import {ActivityIndicator, FlatList, Pressable, SectionList, Text, View, SafeAreaView} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {viewingsApi, Viewing} from '../../api/viewings';
import {format, isToday} from 'date-fns';
import type {ViewingsStackParamList} from '../../navigation/stacks/ViewingsStack';

type NavProp = NativeStackNavigationProp<ViewingsStackParamList>;

const STATUS_STYLES: Record<string, {dot: string; label: string; bg: string; text: string}> = {
  scheduled:  {dot: 'bg-slate-400', label: 'Scheduled', bg: 'bg-slate-50', text: 'text-slate-600'},
  confirmed:  {dot: 'bg-green-500', label: 'Confirmed', bg: 'bg-green-50', text: 'text-green-700'},
  completed:  {dot: 'bg-brand-500', label: 'Completed', bg: 'bg-brand-50', text: 'text-brand-700'},
  no_show:    {dot: 'bg-red-500',   label: 'No Show',   bg: 'bg-red-50', text: 'text-red-700'},
  cancelled:  {dot: 'bg-slate-600', label: 'Cancelled', bg: 'bg-slate-100', text: 'text-slate-700'},
};

function ViewingCard({viewing, onPress}: {viewing: Viewing; onPress: () => void}) {
  const {dot, label, bg, text} = STATUS_STYLES[viewing.status] ?? STATUS_STYLES.scheduled;
  const time = format(new Date(viewing.scheduled_at), 'h:mm a');
  const contact = viewing.contact;

  return (
    <Pressable
      className="bg-white shadow-sm border border-slate-100 rounded-3xl p-5 mb-4 mx-5"
      onPress={onPress}>
      <View className="flex-row items-start justify-between">
        <View className="flex-1 pr-4">
          <Text className="text-brand-600 text-xs font-extrabold uppercase tracking-widest">
            {time}
            {viewing.duration_minutes ? ` · ${viewing.duration_minutes} min` : ''}
          </Text>
          <Text className="text-slate-900 font-extrabold text-lg mt-1 leading-tight" numberOfLines={2}>
            {viewing.listing?.title ?? 'Property Viewing'}
          </Text>
          {viewing.listing?.address && (
            <Text className="text-slate-500 font-medium text-sm mt-1" numberOfLines={1}>
              📍 {viewing.listing.address}
            </Text>
          )}
        </View>
        <View className={`flex-row items-center gap-1.5 px-3 py-1.5 rounded-full ${bg}`}>
          <View className={`w-2 h-2 rounded-full ${dot}`} />
          <Text className={`text-xs font-bold ${text}`}>{label}</Text>
        </View>
      </View>

      {contact && (
        <View className="flex-row items-center mt-4 pt-4 border-t border-slate-100">
          <View className="w-9 h-9 rounded-full bg-brand-50 items-center justify-center mr-3 border border-brand-100">
            <Text className="text-brand-600 text-sm font-extrabold">
              {contact.first_name.charAt(0)}{contact.last_name.charAt(0)}
            </Text>
          </View>
          <View className="flex-1">
            <Text className="text-slate-800 text-sm font-bold">
              {contact.first_name} {contact.last_name}
            </Text>
            {contact.phone && (
              <Text className="text-slate-500 text-xs font-medium mt-0.5">📞 {contact.phone}</Text>
            )}
          </View>
          <View className="bg-slate-50 px-3 py-2 rounded-xl">
            <Text className="text-slate-600 text-xs font-bold">View Details</Text>
          </View>
        </View>
      )}
    </Pressable>
  );
}

export function ViewingsScreen() {
  const navigation = useNavigation<NavProp>();

  const {data: todayViewings, isLoading: loadingToday, refetch: refetchToday} = useQuery({
    queryKey: ['viewings', 'today'],
    queryFn: () => viewingsApi.today().then(r => r.data),
  });

  const {data: upcoming, isLoading: loadingUpcoming, refetch: refetchUpcoming} = useQuery({
    queryKey: ['viewings', 'upcoming'],
    queryFn: () => viewingsApi.upcoming().then(r => r.data),
  });

  const isLoading = loadingToday || loadingUpcoming;

  const sections = [
    ...(todayViewings?.length ? [{title: `Today · ${todayViewings.length}`, data: todayViewings}] : []),
    ...(upcoming?.length ? [{title: 'Upcoming 7 days', data: upcoming}] : []),
  ];

  return (
    <SafeAreaView className="flex-1 bg-slate-50">
      <View className="px-5 pt-6 pb-4 bg-white border-b border-slate-100 shadow-sm z-10 flex-row justify-between items-center">
        <Text className="text-slate-900 text-3xl font-extrabold tracking-tight">Viewings</Text>
        <View className="w-10 h-10 bg-brand-50 rounded-full items-center justify-center">
          <Text className="text-brand-600 font-bold text-lg">{todayViewings?.length || 0}</Text>
        </View>
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : sections.length === 0 ? (
        <View className="flex-1 items-center justify-center px-8">
          <View className="w-24 h-24 bg-brand-50 rounded-full items-center justify-center mb-6">
            <Text className="text-4xl">🏡</Text>
          </View>
          <Text className="text-slate-800 text-xl font-bold mb-2 text-center">No viewings scheduled</Text>
          <Text className="text-slate-500 text-center font-medium">You don't have any viewings for today or the next 7 days.</Text>
        </View>
      ) : (
        <SectionList
          className="flex-1 pt-4"
          contentContainerStyle={{ paddingBottom: 40 }}
          sections={sections}
          keyExtractor={v => String(v.id)}
          renderSectionHeader={({section}) => (
            <View className="px-6 pt-4 pb-3">
              <Text className="text-slate-400 text-xs font-extrabold uppercase tracking-widest">
                {section.title}
              </Text>
            </View>
          )}
          renderItem={({item}) => (
            <ViewingCard
              viewing={item}
              onPress={() => navigation.navigate('ViewingDetail', {viewingId: item.id})}
            />
          )}
          onRefresh={() => { refetchToday(); refetchUpcoming(); }}
          refreshing={isLoading}
        />
      )}
    </SafeAreaView>
  );
}
