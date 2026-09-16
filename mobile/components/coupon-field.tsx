import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import { api, errorMessage, type Cart } from '@/lib/api';
import { useState } from 'react';
import { View } from 'react-native';

export function CouponField({
  couponCode,
  disabled,
  onCart,
}: {
  couponCode?: string | null;
  disabled?: boolean;
  onCart: (cart: Cart) => void;
}) {
  const [code, setCode] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function applyCoupon() {
    const normalized = code.trim().toUpperCase();
    if (!normalized) {
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await api<{ data: Cart }>('/cart/coupon', {
        method: 'POST',
        body: JSON.stringify({ code: normalized }),
      });
      setCode('');
      onCart(res.data);
    } catch (e) {
      setError(errorMessage(e, 'Kode kupon tidak valid.'));
    } finally {
      setBusy(false);
    }
  }

  async function removeCoupon() {
    setBusy(true);
    setError(null);
    try {
      const res = await api<{ data: Cart }>('/cart/coupon', { method: 'DELETE' });
      onCart(res.data);
    } catch (e) {
      setError(errorMessage(e, 'Kupon tidak bisa dihapus.'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <View className="gap-2">
      <Text className="text-sm font-medium">Kode kupon</Text>
      {couponCode ? (
        <View className="flex-row items-center justify-between gap-2 rounded-xl border border-border bg-card p-3">
          <View className="flex-1">
            <Text className="font-semibold">{couponCode}</Text>
            <Text className="text-sm text-muted-foreground">Kupon diterapkan</Text>
          </View>
          <Button variant="ghost" size="sm" disabled={disabled || busy} onPress={() => void removeCoupon()}>
            <Text className="text-destructive">Hapus</Text>
          </Button>
        </View>
      ) : (
        <View className="flex-row items-center gap-2">
          <Input
            className="flex-1"
            placeholder="Masukkan kode"
            autoCapitalize="characters"
            autoCorrect={false}
            value={code}
            onChangeText={setCode}
            editable={!disabled && !busy}
          />
          <Button
            variant="outline"
            disabled={disabled || busy || !code.trim()}
            onPress={() => void applyCoupon()}>
            <Text>Terapkan</Text>
          </Button>
        </View>
      )}
      {error ? <Text className="text-sm text-destructive">{error}</Text> : null}
    </View>
  );
}
