import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { api, errorMessage, type Cart } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { formatIdr, mediaUrl } from '@/lib/format';
import { Image } from 'expo-image';
import { Link, useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, View } from 'react-native';

export default function CartScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const [cart, setCart] = useState<Cart | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!user) {
      setCart(null);
      setLoading(false);
      return;
    }
    setError(null);
    try {
      const res = await api<{ data: Cart }>('/cart');
      setCart(res.data);
    } catch (e) {
      setError(errorMessage(e, 'Gagal memuat keranjang'));
    } finally {
      setLoading(false);
    }
  }, [user]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load])
  );

  async function changeQty(lineId: number, quantity: number) {
    try {
      if (quantity < 1) {
        const res = await api<{ data: Cart }>(`/cart/items/${lineId}`, { method: 'DELETE' });
        setCart(res.data);
        return;
      }
      const res = await api<{ data: Cart }>(`/cart/items/${lineId}`, {
        method: 'PATCH',
        body: JSON.stringify({ quantity }),
      });
      setCart(res.data);
    } catch (e) {
      setError(errorMessage(e, 'Gagal mengubah jumlah'));
    }
  }

  if (!user) {
    return (
      <EmptyState
        message="Masuk dulu untuk melihat keranjang."
        actionLabel="Masuk"
        onAction={() => router.push('/login')}
      />
    );
  }

  if (loading) {
    return (
      <View className="flex-1 items-center justify-center">
        <ActivityIndicator />
      </View>
    );
  }

  return (
    <ScrollView
      className="flex-1 bg-background"
      contentContainerClassName="p-4 gap-3"
      refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}>
      {error ? <Text className="text-destructive">{error}</Text> : null}
      {(cart?.lines ?? []).map((line) => (
        <View key={line.id} className="flex-row gap-3 rounded-xl border border-border bg-card p-3">
          <Image
            source={{ uri: mediaUrl(line.thumbnail) }}
            style={{ width: 72, height: 72, borderRadius: 8, backgroundColor: '#f4f4f5' }}
          />
          <View className="flex-1 gap-1">
            <Text className="font-medium">{line.name}</Text>
            <Text className="font-semibold">{formatIdr(line.unit_price * line.quantity)}</Text>
            <View className="mt-1 flex-row items-center gap-3">
              <Pressable
                onPress={() => void changeQty(line.id, line.quantity - 1)}
                className="h-8 w-8 items-center justify-center rounded-md border border-border">
                <Text>-</Text>
              </Pressable>
              <Text>{line.quantity}</Text>
              <Pressable
                onPress={() => void changeQty(line.id, line.quantity + 1)}
                className="h-8 w-8 items-center justify-center rounded-md border border-border">
                <Text>+</Text>
              </Pressable>
            </View>
          </View>
        </View>
      ))}
      {cart?.lines?.length ? (
        <View className="mt-2 gap-3">
          <Text className="text-lg font-bold">Total {formatIdr(cart.totals.total)}</Text>
          <Link href="/checkout" asChild>
            <Button>
              <Text>Checkout</Text>
            </Button>
          </Link>
        </View>
      ) : (
        <Text className="text-muted-foreground">Keranjang kosong.</Text>
      )}
    </ScrollView>
  );
}
