import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import type { PaymentInstructions } from '@/lib/api';
import { formatIdr } from '@/lib/format';
import { Linking, View } from 'react-native';

export function PaymentPanel({ payment }: { payment?: PaymentInstructions | null }) {
  if (!payment) {
    return null;
  }

  return (
    <View className="gap-2 rounded-xl border border-border bg-card p-4">
      <Text className="font-semibold">Instruksi pembayaran</Text>
      {payment.payment_type ? (
        <Text className="text-muted-foreground">{payment.payment_type.toUpperCase()}</Text>
      ) : null}
      {payment.amount ? <Text>{formatIdr(payment.amount)}</Text> : null}
      {payment.bank_code ? <Text selectable>Bank {payment.bank_code}</Text> : null}
      {payment.virtual_account_number ? (
        <Text selectable className="text-lg font-semibold">
          VA {payment.virtual_account_number}
        </Text>
      ) : null}
      {payment.qris_string ? (
        <Text selectable className="text-xs text-muted-foreground">
          QRIS: {payment.qris_string}
        </Text>
      ) : null}
      {payment.expiry_date ? (
        <Text className="text-sm text-muted-foreground">Berlaku sampai {payment.expiry_date}</Text>
      ) : null}
      {payment.payment_url ? (
        <Button onPress={() => void Linking.openURL(payment.payment_url as string)}>
          <Text>Buka halaman bayar</Text>
        </Button>
      ) : null}
    </View>
  );
}
