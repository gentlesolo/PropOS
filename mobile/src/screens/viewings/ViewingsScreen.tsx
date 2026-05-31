import React from 'react';
import {ActivityIndicator, FlatList, Pressable, SectionList, Text, View} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {viewingsApi, Viewing} from '../../api/viewings';
import {format, isToday} from 'date-fns';
import type {ViewingsStackParamList} from '../../navigation/stacks/ViewingsStack';

type NavProp = NativeStackNavigationProp<ViewingsStackParamList>;

const STATUS_STYLES: Record<string, {dot: string; label: string}> = {
  scheduled:  {dot: 'bg-slate-400', label: 'Scheduled'},
  confirmed:  {dot: 'bg-green-500', label: 'Confirmed'},
  completed:  {dot: 'bg-brand-500', label: 'Completed'},
  no_show:    {dot: 'bg-red-500',   label: 'No Show'},
  cancelled:  {dot: 'bg-slate-600', label: 'Cancelled'},
};

function ViewingCard({viewing, onPress}: {viewing: Viewing; onPress: () => void}) {
  const {dot, label} = STATUS_STYLES[viewing.status] ?? STATUS_STYLES.scheduled;
  const time = format(new Date(viewing.scheduled_at), 'h:mm a');
  const contact = viewing.contact;

  return (
    <Pressable
      className="bg-surface-card rounded-xl p-4 mb-3 mx-4"
      onPress={onPress}>
      <View className="flex-row items-start justify-between">
        <View className="flex-1">
          <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide">
            {time}
            {viewing.duration_minutes ? ` · ${viewing.duration_minutes} min` : ''}
          </Text>
          <Text className="text-white font-semibold text-base mt-1" numberOfLines={1}>
            {viewing.listing?.title ?? 'Property Viewing'}
          </Text>
          {viewing.listing?.address && (
            <Text className="text-slate-400 text-sm mt-0.5" numberOfLines={1}>
              {viewing.listing.address}
            </Text>
          )}
        </View>
        <View className={`flex-row items-center gap-1.5 px-2 py-1 rounded-full bg-slate-800`}>
          <View className={`w-2 h-2 rounded-full ${dot}`} />
          <Text className="text-slate-300 text-xs">{label}</Text>
        </View>
      </View>

      {contact && (
        <View className="flex-row items-center mt-3 pt-3 border-t border-slate-700">
          <View className="w-7 h-7 rounded-full bg-brand-800 items-center justify-center mr-2">
            <Text className="text-white text-xs font-semibold">
              {contact.first_name.charAt(0)}{contact.last_name.charAt(0)}
            </Text>
          </View>
          <Text className="text-slate-300 text-sm">
            {contact.first_name} {contact.last_name}
          </Text>
          {contact.phone && (
            <Text className="text-slate-500 text-xs ml-2">{contact.phone}</Text>
          )}
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
    <View className="flex-1 bg-surface">
      <View className="pt-14 px-4 pb-3">
        <Text className="text-white text-2xl font-bold">Viewings</Text>
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#3b82f6" />
        </View>
      ) : sections.length === 0 ? (
        <View className="flex-1 items-center justify-center px-8">
          <Text className="text-slate-500 text-center">No viewings scheduled for today or the next 7 days.</Text>
        </View>
      ) : (
        <SectionList
          sections={sections}
          keyExtractor={v => String(v.id)}
          renderSectionHeader={({section}) => (
            <View className="px-4 pt-4 pb-2">
              <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide">
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
    </View>
  );
}
