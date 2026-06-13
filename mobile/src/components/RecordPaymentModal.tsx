import React, {useState, useEffect} from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme} from '../theme/ThemeProvider';
import {createMMKV} from 'react-native-mmkv';

// Safe access to MMKV storage to respect configured currency symbol
let localStore: any;
try {
  localStore = createMMKV({id: 'invoices-local-store-v1'});
} catch (e) {
  const store: Record<string, string> = {};
  localStore = {
    getString: (key: string) => store[key] || null,
  };
}

export interface RecordPaymentModalProps {
  visible: boolean;
  onClose: () => void;
  prefilledAmount: string;
  onConfirm: (amount: number, date: string, method: 'Bank Transfer' | 'Cash' | 'Card') => void | Promise<void>;
  isSubmitting?: boolean;
}

export function RecordPaymentModal({
  visible,
  onClose,
  prefilledAmount,
  onConfirm,
  isSubmitting = false,
}: RecordPaymentModalProps) {
  const {tokens} = useTheme();
  const insets = useSafeAreaInsets();

  const [amountInput, setAmountInput] = useState('');
  const [dateInput, setDateInput] = useState('');
  const [paymentMethod, setPaymentMethod] = useState<'Bank Transfer' | 'Cash' | 'Card'>('Bank Transfer');

  // Reset/sync pre-filled amount and current date on open
  useEffect(() => {
    if (visible) {
      setAmountInput(prefilledAmount);
      setDateInput(new Date().toISOString().split('T')[0]);
      setPaymentMethod('Bank Transfer');
    }
  }, [visible, prefilledAmount]);

  const currencySymbol = localStore.getString('currency_symbol') || '₦';

  const handleSubmit = () => {
    const amt = parseFloat(amountInput);
    if (isNaN(amt) || amt <= 0) {
      return;
    }
    onConfirm(amt, dateInput, paymentMethod);
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onClose}
    >
      <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: tokens.surfaceOverlay}}>
        <Pressable style={{flex: 1}} onPress={onClose} />
        <View
          style={{
            backgroundColor: tokens.surfaceCard,
            borderTopLeftRadius: 24,
            borderTopRightRadius: 24,
            borderWidth: 1,
            borderColor: tokens.borderStrong,
            padding: 24,
            paddingBottom: insets.bottom > 0 ? insets.bottom + 20 : 30,
          }}
        >
          {/* Header */}
          <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20}}>
            <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '800'}}>
              Record Rent Payment
            </Text>
            <Pressable
              onPress={onClose}
              style={{
                width: 32,
                height: 32,
                borderRadius: 16,
                backgroundColor: tokens.surfaceRaised,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Icon name="x" size={16} color={tokens.textSecondary} />
            </Pressable>
          </View>

          {/* Amount Paid field */}
          <View style={{marginBottom: 16}}>
            <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
              Amount Paid ({currencySymbol})
            </Text>
            <TextInput
              value={amountInput}
              onChangeText={setAmountInput}
              keyboardType="decimal-pad"
              style={{
                backgroundColor: tokens.surfaceSunken,
                color: tokens.textPrimary,
                fontSize: 16,
                fontWeight: '800',
                paddingVertical: 12,
                paddingHorizontal: 16,
                borderRadius: 12,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
              }}
            />
          </View>

          {/* Date Paid field */}
          <View style={{marginBottom: 16}}>
            <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
              Date Paid
            </Text>
            <TextInput
              value={dateInput}
              onChangeText={setDateInput}
              placeholder="YYYY-MM-DD"
              placeholderTextColor={tokens.textTertiary}
              style={{
                backgroundColor: tokens.surfaceSunken,
                color: tokens.textPrimary,
                fontSize: 13,
                paddingVertical: 12,
                paddingHorizontal: 16,
                borderRadius: 12,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
              }}
            />
          </View>

          {/* Payment method selection row */}
          <View style={{marginBottom: 24}}>
            <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
              Payment Method
            </Text>
            <View style={{flexDirection: 'row', gap: 8}}>
              {(['Bank Transfer', 'Cash', 'Card'] as const).map((meth) => {
                const active = paymentMethod === meth;
                return (
                  <Pressable
                    key={meth}
                    onPress={() => setPaymentMethod(meth)}
                    style={{
                      flex: 1,
                      paddingVertical: 12,
                      borderRadius: 8,
                      backgroundColor: active ? tokens.brandPrimary : tokens.surfaceRaised,
                      borderWidth: 1,
                      borderColor: active ? tokens.brandPrimary : tokens.borderDefault,
                      alignItems: 'center',
                    }}
                  >
                    <Text style={{color: active ? '#ffffff' : tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>
                      {meth}
                    </Text>
                  </Pressable>
                );
              })}
            </View>
          </View>

          {/* CTA Mark Paid */}
          <Pressable
            onPress={handleSubmit}
            disabled={!amountInput || isSubmitting}
            style={{
              backgroundColor: tokens.brandPrimary,
              borderRadius: 12,
              paddingVertical: 14,
              alignItems: 'center',
            }}
          >
            {isSubmitting ? (
              <ActivityIndicator color="#ffffff" size="small" />
            ) : (
              <Text style={{color: '#ffffff', fontSize: 14, fontWeight: '800'}}>
                Mark as Paid
              </Text>
            )}
          </Pressable>
        </View>
      </View>
    </Modal>
  );
}
