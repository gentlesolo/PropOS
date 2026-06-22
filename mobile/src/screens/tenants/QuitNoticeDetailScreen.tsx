import React from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  Text,
  View,
} from 'react-native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation} from '@react-navigation/native';
import type {RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {quitNoticesApi, STATUS_LABELS, STATUS_COLORS} from '../../api/quitNotices';
import type {TenantsStackParamList} from '../../navigation/stacks/TenantsStack';
import {useTheme} from '../../theme/ThemeProvider';

type Route = RouteProp<TenantsStackParamList, 'QuitNoticeDetail'>;
type NavProp = NativeStackNavigationProp<TenantsStackParamList>;

function InfoRow({label, value}: {label: string; value: string}) {
  const {tokens} = useTheme();
  return (
    <View style={{marginBottom: 14}}>
      <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 3}}>
        {label}
      </Text>
      <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '600'}}>
        {value}
      </Text>
    </View>
  );
}

export function QuitNoticeDetailScreen() {
  const {tokens} = useTheme();
  const route = useRoute<Route>();
  const navigation = useNavigation<NavProp>();
  const {noticeId, tenantId} = route.params;
  const queryClient = useQueryClient();

  const {data: notice, isLoading} = useQuery({
    queryKey: ['quitNotice', noticeId],
    queryFn: () => quitNoticesApi.show(noticeId),
  });

  const sendMutation = useMutation({
    mutationFn: () => quitNoticesApi.send(noticeId),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['quitNotice', noticeId]});
      queryClient.invalidateQueries({queryKey: ['quitNotices', tenantId]});
      Alert.alert('Notice Sent', 'The quit notice has been emailed to the tenant.');
    },
    onError: () => Alert.alert('Error', 'Failed to send notice. Please try again.'),
  });

  const withdrawMutation = useMutation({
    mutationFn: () => quitNoticesApi.withdraw(noticeId),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['quitNotice', noticeId]});
      queryClient.invalidateQueries({queryKey: ['quitNotices', tenantId]});
      Alert.alert('Withdrawn', 'The quit notice has been withdrawn.');
      navigation.goBack();
    },
    onError: () => Alert.alert('Error', 'Failed to withdraw notice. Please try again.'),
  });

  const confirmSend = () => {
    Alert.alert(
      'Send Quit Notice',
      'This will email the notice and PDF to the tenant. This cannot be undone.',
      [
        {text: 'Cancel', style: 'cancel'},
        {text: 'Send', style: 'destructive', onPress: () => sendMutation.mutate()},
      ],
    );
  };

  const confirmWithdraw = () => {
    Alert.alert(
      'Withdraw Notice',
      'Are you sure you want to withdraw this quit notice?',
      [
        {text: 'Cancel', style: 'cancel'},
        {text: 'Withdraw', style: 'destructive', onPress: () => withdrawMutation.mutate()},
      ],
    );
  };

  if (isLoading || !notice) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, alignItems: 'center', justifyContent: 'center'}}>
        <ActivityIndicator color={tokens.brandPrimary} />
      </View>
    );
  }

  const statusStyle = STATUS_COLORS[notice.status];
  const canSend = notice.status === 'drafted' || notice.status === 'disputed';
  const canWithdraw = ['drafted', 'sent', 'acknowledged', 'disputed'].includes(notice.status);
  const isBusy = sendMutation.isPending || withdrawMutation.isPending;

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
          Quit Notice
        </Text>
        <View style={{width: 28}} />
      </View>

      <ScrollView contentContainerStyle={{padding: 20, paddingBottom: 40}}>
        {/* Reference + Status */}
        <View
          style={{
            backgroundColor: tokens.surfaceCard,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 16,
            padding: 16,
            marginBottom: 16,
            ...tokens.shadowSm,
          }}
        >
          <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 14}}>
            <View>
              <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5}}>
                Reference
              </Text>
              <Text style={{color: tokens.textPrimary, fontSize: 17, fontWeight: '800', marginTop: 2}}>
                {notice.reference}
              </Text>
            </View>
            <View
              style={{
                backgroundColor: statusStyle.bg,
                borderWidth: 1,
                borderColor: statusStyle.border,
                borderRadius: 20,
                paddingHorizontal: 12,
                paddingVertical: 5,
              }}
            >
              <Text style={{color: statusStyle.text, fontSize: 12, fontWeight: '800'}}>
                {STATUS_LABELS[notice.status]}
              </Text>
            </View>
          </View>

          <InfoRow label="Vacate By" value={notice.vacate_by_date} />
          <InfoRow label="Issue Date" value={notice.issue_date} />
          <InfoRow label="Days Remaining" value={notice.notice_period_days > 0 ? `${notice.notice_period_days} days` : 'Past due'} />
          <InfoRow label="Delivery Method" value={notice.delivery_method.replace(/_/g, ' ')} />
          {notice.sent_at && <InfoRow label="Sent At" value={new Date(notice.sent_at).toLocaleDateString()} />}
          {notice.issued_by_name && <InfoRow label="Issued By" value={notice.issued_by_name} />}
          {notice.lease_ref && <InfoRow label="Lease Ref" value={notice.lease_ref} />}
        </View>

        {/* Reason */}
        <View
          style={{
            backgroundColor: tokens.surfaceCard,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 16,
            padding: 16,
            marginBottom: 16,
            ...tokens.shadowSm,
          }}
        >
          <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 8}}>
            Reason
          </Text>
          <Text style={{color: tokens.textPrimary, fontSize: 13, lineHeight: 20}}>
            {notice.reason}
          </Text>
        </View>

        {/* Notice Body */}
        {notice.notice_body && (
          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              borderRadius: 16,
              padding: 16,
              marginBottom: 16,
              ...tokens.shadowSm,
            }}
          >
            <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 8}}>
              Notice Body
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 12, lineHeight: 19}}>
              {notice.notice_body}
            </Text>
          </View>
        )}
      </ScrollView>

      {/* Action Buttons */}
      {(canSend || canWithdraw) && (
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
          {canSend && (
            <Pressable
              onPress={confirmSend}
              disabled={isBusy}
              style={{
                backgroundColor: tokens.brandPrimary,
                borderRadius: 14,
                paddingVertical: 14,
                alignItems: 'center',
                flexDirection: 'row',
                justifyContent: 'center',
                gap: 8,
                opacity: isBusy ? 0.6 : 1,
              }}
            >
              {sendMutation.isPending ? (
                <ActivityIndicator color="#ffffff" size="small" />
              ) : (
                <>
                  <Icon name="send" size={16} color="#ffffff" />
                  <Text style={{color: '#ffffff', fontSize: 15, fontWeight: '800'}}>
                    Send to Tenant
                  </Text>
                </>
              )}
            </Pressable>
          )}

          {canWithdraw && (
            <Pressable
              onPress={confirmWithdraw}
              disabled={isBusy}
              style={{
                backgroundColor: tokens.surfaceRaised,
                borderRadius: 14,
                paddingVertical: 14,
                alignItems: 'center',
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                opacity: isBusy ? 0.6 : 1,
              }}
            >
              {withdrawMutation.isPending ? (
                <ActivityIndicator color={tokens.textSecondary} size="small" />
              ) : (
                <Text style={{color: tokens.textSecondary, fontSize: 15, fontWeight: '700'}}>
                  Withdraw Notice
                </Text>
              )}
            </Pressable>
          )}
        </View>
      )}
    </View>
  );
}
