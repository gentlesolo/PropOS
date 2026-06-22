import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation} from '@react-navigation/native';
import type {RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {quitNoticesApi} from '../../api/quitNotices';
import type {TenantsStackParamList} from '../../navigation/stacks/TenantsStack';
import {useTheme} from '../../theme/ThemeProvider';

type Route = RouteProp<TenantsStackParamList, 'CreateQuitNotice'>;
type NavProp = NativeStackNavigationProp<TenantsStackParamList>;

type DeliveryMethod = 'email' | 'hand_delivered' | 'registered_post' | 'email_and_post';

const DELIVERY_OPTIONS: {value: DeliveryMethod; label: string}[] = [
  {value: 'email', label: 'Email'},
  {value: 'hand_delivered', label: 'Hand Delivered'},
  {value: 'registered_post', label: 'Registered Post'},
  {value: 'email_and_post', label: 'Email & Post'},
];

function FieldLabel({children}: {children: string}) {
  const {tokens} = useTheme();
  return (
    <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 6}}>
      {children}
    </Text>
  );
}

export function CreateQuitNoticeScreen() {
  const {tokens} = useTheme();
  const route = useRoute<Route>();
  const navigation = useNavigation<NavProp>();
  const {tenantId, leaseId, tenantName} = route.params;
  const queryClient = useQueryClient();

  const [vacateByDate, setVacateByDate] = useState('');
  const [reason, setReason] = useState('');
  const [noticeBody, setNoticeBody] = useState('');
  const [deliveryMethod, setDeliveryMethod] = useState<DeliveryMethod>('email');
  const [internalNotes, setInternalNotes] = useState('');
  const [isGenerating, setIsGenerating] = useState(false);

  const createMutation = useMutation({
    mutationFn: (sendAfter: boolean) =>
      quitNoticesApi.create({
        lease_id: leaseId,
        vacate_by_date: vacateByDate,
        reason,
        notice_body: noticeBody,
        delivery_method: deliveryMethod,
        internal_notes: internalNotes || undefined,
      }),
    onSuccess: async (notice, sendAfter) => {
      queryClient.invalidateQueries({queryKey: ['quitNotices', tenantId]});
      if (sendAfter) {
        try {
          await quitNoticesApi.send(notice.id);
          Alert.alert('Notice Sent', 'Quit notice created and sent to the tenant.');
        } catch {
          Alert.alert('Saved', 'Quit notice drafted. You can send it from the notice detail screen.');
        }
      } else {
        Alert.alert('Saved', 'Quit notice saved as draft.');
      }
      navigation.goBack();
    },
    onError: () => Alert.alert('Error', 'Failed to save notice. Please try again.'),
  });

  const generateAiDraft = async () => {
    if (!reason.trim()) {
      Alert.alert('Reason Required', 'Please enter a reason before generating the AI draft.');
      return;
    }
    if (!vacateByDate.trim()) {
      Alert.alert('Vacate Date Required', 'Please enter a vacate date before generating the AI draft.');
      return;
    }

    setIsGenerating(true);
    try {
      const body = await quitNoticesApi.generateContent({
        lease_id: leaseId,
        reason,
        vacate_by_date: vacateByDate,
      });
      setNoticeBody(body);
    } catch {
      Alert.alert('Error', 'Failed to generate AI draft. Please write the notice body manually.');
    } finally {
      setIsGenerating(false);
    }
  };

  const validate = (): string | null => {
    if (!vacateByDate.trim()) return 'Please enter the vacate by date (YYYY-MM-DD).';
    if (!reason.trim() || reason.length < 5) return 'Please enter a reason (at least 5 characters).';
    if (!noticeBody.trim() || noticeBody.length < 20) return 'Please enter the notice body (at least 20 characters) or use AI Generate.';
    return null;
  };

  const handleSave = (sendAfter: boolean) => {
    const error = validate();
    if (error) { Alert.alert('Validation', error); return; }
    createMutation.mutate(sendAfter);
  };

  const inputStyle = {
    backgroundColor: tokens.surfaceSunken,
    borderWidth: 1,
    borderColor: tokens.borderDefault,
    borderRadius: 12,
    padding: 12,
    color: tokens.textPrimary,
    fontSize: 13,
  };

  return (
    <View style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'space-between',
          paddingHorizontal: 16,
          paddingVertical: 12,
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
        }}
      >
        <Pressable onPress={() => navigation.goBack()} style={{padding: 4}}>
          <Icon name="arrow-left" size={20} color={tokens.textPrimary} />
        </Pressable>
        <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700'}}>
          New Quit Notice
        </Text>
        <View style={{width: 28}} />
      </View>

      <ScrollView contentContainerStyle={{padding: 20, paddingBottom: 120}} keyboardShouldPersistTaps="handled">
        {/* Tenant chip */}
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            backgroundColor: `${tokens.brandPrimary}12`,
            borderRadius: 10,
            paddingHorizontal: 12,
            paddingVertical: 8,
            marginBottom: 20,
            gap: 8,
            alignSelf: 'flex-start',
          }}
        >
          <Icon name="user" size={13} color={tokens.brandPrimary} />
          <Text style={{color: tokens.brandPrimary, fontSize: 13, fontWeight: '700'}}>
            {tenantName}
          </Text>
        </View>

        {/* Vacate By Date */}
        <View style={{marginBottom: 16}}>
          <FieldLabel>Vacate By Date</FieldLabel>
          <TextInput
            placeholder="YYYY-MM-DD"
            placeholderTextColor={tokens.textTertiary}
            value={vacateByDate}
            onChangeText={setVacateByDate}
            style={inputStyle}
          />
        </View>

        {/* Reason */}
        <View style={{marginBottom: 16}}>
          <FieldLabel>Reason for Notice</FieldLabel>
          <TextInput
            placeholder="e.g. Non-payment of rent for 3 consecutive months"
            placeholderTextColor={tokens.textTertiary}
            value={reason}
            onChangeText={setReason}
            multiline
            numberOfLines={3}
            style={[inputStyle, {textAlignVertical: 'top', minHeight: 80}]}
          />
        </View>

        {/* Notice Body */}
        <View style={{marginBottom: 16}}>
          <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6}}>
            <FieldLabel>Notice Body</FieldLabel>
            <Pressable
              onPress={generateAiDraft}
              disabled={isGenerating}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                gap: 5,
                backgroundColor: '#F0FDF4',
                borderWidth: 1,
                borderColor: '#BBF7D0',
                borderRadius: 8,
                paddingHorizontal: 10,
                paddingVertical: 5,
              }}
            >
              {isGenerating ? (
                <ActivityIndicator size="small" color="#16A34A" />
              ) : (
                <Text style={{fontSize: 11}}>✦</Text>
              )}
              <Text style={{color: '#16A34A', fontSize: 11, fontWeight: '800'}}>
                {isGenerating ? 'Generating...' : 'AI Generate'}
              </Text>
            </Pressable>
          </View>
          <TextInput
            placeholder="Write the notice body here or use AI Generate above..."
            placeholderTextColor={tokens.textTertiary}
            value={noticeBody}
            onChangeText={setNoticeBody}
            multiline
            numberOfLines={8}
            style={[inputStyle, {textAlignVertical: 'top', minHeight: 160}]}
          />
        </View>

        {/* Delivery Method */}
        <View style={{marginBottom: 16}}>
          <FieldLabel>Delivery Method</FieldLabel>
          <View style={{flexDirection: 'row', flexWrap: 'wrap', gap: 8}}>
            {DELIVERY_OPTIONS.map(opt => (
              <Pressable
                key={opt.value}
                onPress={() => setDeliveryMethod(opt.value)}
                style={{
                  paddingHorizontal: 14,
                  paddingVertical: 8,
                  borderRadius: 20,
                  borderWidth: 1.5,
                  borderColor: deliveryMethod === opt.value ? tokens.brandPrimary : tokens.borderDefault,
                  backgroundColor: deliveryMethod === opt.value ? `${tokens.brandPrimary}12` : tokens.surfaceCard,
                }}
              >
                <Text
                  style={{
                    fontSize: 12,
                    fontWeight: '700',
                    color: deliveryMethod === opt.value ? tokens.brandPrimary : tokens.textSecondary,
                  }}
                >
                  {opt.label}
                </Text>
              </Pressable>
            ))}
          </View>
        </View>

        {/* Internal Notes */}
        <View style={{marginBottom: 16}}>
          <FieldLabel>Internal Notes (optional)</FieldLabel>
          <TextInput
            placeholder="Notes visible only to your team..."
            placeholderTextColor={tokens.textTertiary}
            value={internalNotes}
            onChangeText={setInternalNotes}
            multiline
            numberOfLines={3}
            style={[inputStyle, {textAlignVertical: 'top', minHeight: 70}]}
          />
        </View>
      </ScrollView>

      {/* Footer Buttons */}
      <View
        style={{
          padding: 16,
          paddingBottom: 32,
          backgroundColor: tokens.surfaceCard,
          borderTopWidth: 1,
          borderTopColor: tokens.borderDefault,
          gap: 10,
        }}
      >
        <Pressable
          onPress={() => handleSave(true)}
          disabled={createMutation.isPending}
          style={{
            backgroundColor: tokens.brandPrimary,
            borderRadius: 14,
            paddingVertical: 14,
            alignItems: 'center',
            flexDirection: 'row',
            justifyContent: 'center',
            gap: 8,
            opacity: createMutation.isPending ? 0.6 : 1,
          }}
        >
          {createMutation.isPending ? (
            <ActivityIndicator color="#ffffff" size="small" />
          ) : (
            <>
              <Icon name="send" size={16} color="#ffffff" />
              <Text style={{color: '#ffffff', fontSize: 15, fontWeight: '800'}}>
                Save & Send Now
              </Text>
            </>
          )}
        </Pressable>

        <Pressable
          onPress={() => handleSave(false)}
          disabled={createMutation.isPending}
          style={{
            backgroundColor: tokens.surfaceRaised,
            borderRadius: 14,
            paddingVertical: 14,
            alignItems: 'center',
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            opacity: createMutation.isPending ? 0.6 : 1,
          }}
        >
          <Text style={{color: tokens.textSecondary, fontSize: 15, fontWeight: '700'}}>
            Save as Draft
          </Text>
        </Pressable>
      </View>
    </View>
  );
}
