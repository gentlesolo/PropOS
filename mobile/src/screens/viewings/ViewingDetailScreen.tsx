import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {viewingsApi, Viewing} from '../../api/viewings';
import {format} from 'date-fns';
import type {ViewingsStackParamList} from '../../navigation/stacks/ViewingsStack';

type RoutePropType = RouteProp<ViewingsStackParamList, 'ViewingDetail'>;
type NavProp = NativeStackNavigationProp<ViewingsStackParamList>;

type Outcome = 'interested' | 'not_interested' | 'offer_expected' | 'undecided';

const OUTCOMES: {value: Outcome; label: string; emoji: string}[] = [
  {value: 'interested',     label: 'Interested',     emoji: '🔥'},
  {value: 'offer_expected', label: 'Offer Expected',  emoji: '✍️'},
  {value: 'undecided',      label: 'Undecided',       emoji: '🤔'},
  {value: 'not_interested', label: 'Not Interested',  emoji: '👎'},
];

export function ViewingDetailScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {viewingId} = route.params;
  const queryClient = useQueryClient();

  const [outcomeModalVisible, setOutcomeModalVisible] = useState(false);
  const [selectedOutcome, setSelectedOutcome] = useState<Outcome>('undecided');
  const [outcomeNotes, setOutcomeNotes] = useState('');

  const {data: viewing, isLoading} = useQuery({
    queryKey: ['viewing', viewingId],
    queryFn: () => viewingsApi.get(viewingId).then(r => r.data),
  });

  const checkIn = useMutation({
    mutationFn: () => viewingsApi.checkIn(viewingId),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['viewing', viewingId]});
      queryClient.invalidateQueries({queryKey: ['viewings']});
    },
    onError: () => Alert.alert('Error', 'Could not check in. Please try again.'),
  });

  const complete = useMutation({
    mutationFn: () => viewingsApi.complete(viewingId, selectedOutcome, outcomeNotes || undefined),
    onSuccess: () => {
      setOutcomeModalVisible(false);
      queryClient.invalidateQueries({queryKey: ['viewing', viewingId]});
      queryClient.invalidateQueries({queryKey: ['viewings']});
    },
    onError: () => Alert.alert('Error', 'Could not complete viewing. Please try again.'),
  });

  if (isLoading || !viewing) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#3b82f6" />
      </View>
    );
  }

  const canCheckIn = viewing.status === 'scheduled' || viewing.status === 'confirmed';
  const canComplete = viewing.status !== 'completed' && viewing.status !== 'cancelled';
  const isCompleted = viewing.status === 'completed';

  return (
    <ScrollView className="flex-1 bg-surface" contentContainerClassName="pb-10">
      {/* Header */}
      <View className="pt-14 px-4 pb-4">
        <Pressable onPress={() => navigation.goBack()} className="mb-4">
          <Text className="text-brand-500">← Back</Text>
        </Pressable>

        <Text className="text-white text-xl font-bold">
          {viewing.listing?.title ?? 'Property Viewing'}
        </Text>
        <Text className="text-slate-400 text-sm mt-1">
          {format(new Date(viewing.scheduled_at), 'PPpp')}
          {viewing.duration_minutes ? ` · ${viewing.duration_minutes} min` : ''}
        </Text>
      </View>

      {/* Listing info */}
      {viewing.listing && (
        <View className="mx-4 bg-surface-card rounded-xl p-4 mb-3">
          <Text className="text-slate-400 text-xs uppercase tracking-wide font-semibold mb-2">Property</Text>
          <Text className="text-white font-medium">{viewing.listing.address}</Text>
          {viewing.listing.price && (
            <Text className="text-brand-400 text-sm mt-1">
              ₦{Number(viewing.listing.price).toLocaleString()}
            </Text>
          )}
          {(viewing.listing.bedrooms || viewing.listing.bathrooms) && (
            <Text className="text-slate-400 text-sm mt-1">
              {viewing.listing.bedrooms ? `${viewing.listing.bedrooms} bed` : ''}
              {viewing.listing.bedrooms && viewing.listing.bathrooms ? ' · ' : ''}
              {viewing.listing.bathrooms ? `${viewing.listing.bathrooms} bath` : ''}
            </Text>
          )}
        </View>
      )}

      {/* Contact info */}
      {viewing.contact && (
        <View className="mx-4 bg-surface-card rounded-xl p-4 mb-3">
          <Text className="text-slate-400 text-xs uppercase tracking-wide font-semibold mb-2">Client</Text>
          <Text className="text-white font-medium">
            {viewing.contact.first_name} {viewing.contact.last_name}
          </Text>
          {viewing.contact.phone && (
            <Text className="text-slate-400 text-sm mt-1">{viewing.contact.phone}</Text>
          )}
        </View>
      )}

      {/* Check-in status */}
      {viewing.check_in_at && (
        <View className="mx-4 bg-green-900/30 border border-green-800 rounded-xl p-4 mb-3">
          <Text className="text-green-400 text-sm font-medium">
            ✓ Checked in at {format(new Date(viewing.check_in_at), 'h:mm a')}
          </Text>
        </View>
      )}

      {/* Outcome (if completed) */}
      {isCompleted && viewing.outcome && (
        <View className="mx-4 bg-surface-card rounded-xl p-4 mb-6">
          <Text className="text-slate-400 text-xs uppercase tracking-wide font-semibold mb-2">Outcome</Text>
          <Text className="text-white font-medium capitalize">
            {viewing.outcome.replace('_', ' ')}
          </Text>
          {viewing.outcome_notes && (
            <Text className="text-slate-300 text-sm mt-2 leading-5">{viewing.outcome_notes}</Text>
          )}
        </View>
      )}

      {/* Action buttons */}
      <View className="mx-4 gap-3">
        {canCheckIn && !viewing.check_in_at && (
          <Pressable
            className="bg-green-600 rounded-xl py-4 items-center"
            onPress={() => checkIn.mutate()}
            disabled={checkIn.isPending}>
            {checkIn.isPending
              ? <ActivityIndicator color="#fff" />
              : <Text className="text-white font-semibold text-base">📍 Check In</Text>
            }
          </Pressable>
        )}

        {canComplete && (
          <Pressable
            className="bg-brand-600 rounded-xl py-4 items-center"
            onPress={() => setOutcomeModalVisible(true)}>
            <Text className="text-white font-semibold text-base">Complete Viewing</Text>
          </Pressable>
        )}
      </View>

      {/* Outcome modal */}
      <Modal visible={outcomeModalVisible} transparent animationType="slide">
        <View className="flex-1 justify-end bg-black/60">
          <View className="bg-surface-card rounded-t-2xl p-5">
            <Text className="text-white font-semibold text-xl mb-4">How did it go?</Text>

            <View className="gap-2 mb-4">
              {OUTCOMES.map(o => (
                <Pressable
                  key={o.value}
                  className={`flex-row items-center p-4 rounded-xl border ${
                    selectedOutcome === o.value
                      ? 'bg-brand-900 border-brand-500'
                      : 'bg-surface border-slate-700'
                  }`}
                  onPress={() => setSelectedOutcome(o.value)}>
                  <Text className="text-xl mr-3">{o.emoji}</Text>
                  <Text className="text-white font-medium">{o.label}</Text>
                </Pressable>
              ))}
            </View>

            <TextInput
              className="bg-surface rounded-xl px-4 py-3 text-white text-sm mb-4"
              placeholder="Notes (optional)…"
              placeholderTextColor="#64748b"
              multiline
              numberOfLines={3}
              value={outcomeNotes}
              onChangeText={setOutcomeNotes}
              style={{minHeight: 72, textAlignVertical: 'top'}}
            />

            <View className="flex-row gap-3">
              <Pressable
                className="flex-1 bg-slate-700 rounded-xl py-3.5 items-center"
                onPress={() => setOutcomeModalVisible(false)}>
                <Text className="text-white">Cancel</Text>
              </Pressable>
              <Pressable
                className="flex-1 bg-brand-600 rounded-xl py-3.5 items-center"
                onPress={() => complete.mutate()}
                disabled={complete.isPending}>
                {complete.isPending
                  ? <ActivityIndicator color="#fff" size="small" />
                  : <Text className="text-white font-semibold">Save</Text>
                }
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
}
