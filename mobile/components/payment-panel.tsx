import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import type { PaymentInstructions } from '@/lib/api';
import { formatIdr } from '@/lib/format';
import { useEffect, useState } from 'react';
import { Linking, View } from 'react-native';
import QRCode from 'react-native-qrcode-svg';

function parseExpiry(value?: string | null): Date | null {
  if (!value) {
    return null;
  }
  // Komerce returns "YYYY-MM-DD HH:mm:ss"; Hermes only parses ISO-8601.
  const date = new Date(value.includes('T') ? value : value.replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? null : date;
}

function formatCountdown(ms: number): string {
  const total = Math.max(0, Math.floor(ms / 1000));
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const seconds = total % 60;
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
}

export function PaymentPanel({ payment }: { payment?: PaymentInstructions | null }) {
  const expiry = parseExpiry(payment?.expiry_date);
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (!expiry) {
      return;
    }
    const interval = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [payment?.expiry_date]);

  if (!payment) {
    return null;
  }

  const remainingMs = expiry ? expiry.getTime() - now : null;
  const expired = remainingMs !== null && remainingMs <= 0;

  if (expired) {
    return (
      <View className="gap-2 rounded-xl border border-destructive/40 bg-card p-4">
        <Text className="font-semibold text-destructive">Pembayaran kedaluwarsa</Text>
        <Text className="text-muted-foreground">
          Batas waktu pembayaran sudah lewat. Buat pembayaran baru untuk melanjutkan pesanan.
        </Text>
      </View>
    );
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
        <View className="gap-2">
          <View className="items-center rounded-xl bg-white p-4">
            <QRCode value={payment.qris_string} size={200} />
          </View>
          <Text className="text-center text-sm text-muted-foreground">
            Scan QRIS di atas dengan aplikasi pembayaran.
          </Text>
          <Text selectable className="text-xs text-muted-foreground">
            QRIS: {payment.qris_string}
          </Text>
        </View>
      ) : null}
      {expiry && remainingMs !== null ? (
        <View className="gap-0.5">
          <Text className="text-sm text-muted-foreground">
            Bayar sebelum{' '}
            {expiry.toLocaleString('id-ID', {
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit',
            })}
          </Text>
          <Text className="text-sm font-semibold">Sisa waktu {formatCountdown(remainingMs)}</Text>
        </View>
      ) : payment.expiry_date ? (
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
