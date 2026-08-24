import { EmptyState } from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Text } from '@/components/ui/text';
import { api, type OrderSummary } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { formatIdr } from '@/lib/format';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, View } from 'react-native';

export default function OrdersScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const [orders, setOrders] = useState<OrderSummary[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    if (!user) {
      setOrders([]);
      setLoading(false);
      return;
    }
    try {
      const res = await api<{ data: OrderSummary[] }>('/orders');
      setOrders(res.data ?? []);
    } catch {
      setOrders([]);
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

  if (!user) {
    return (
      <EmptyState
        message="Masuk untuk melihat pesanan."
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
      {orders.map((order) => (
        <Pressable
          key={order.number}
          onPress={() => router.push(`/order/${order.number}`)}
          className="rounded-xl border border-border bg-card p-4">
          <View className="flex-row items-center justify-between">
            <Text className="font-semibold">{order.number}</Text>
            <Badge variant="secondary">
              <Text>{order.payment_status}</Text>
            </Badge>
          </View>
          <Text className="mt-1 text-muted-foreground">
            {order.status}
            {order.shipping_status ? ` · ${order.shipping_status}` : ''}
          </Text>
          <Text className="mt-1 font-medium">{formatIdr(order.amount)}</Text>
        </Pressable>
      ))}
      {orders.length === 0 ? <Text className="text-muted-foreground">Belum ada pesanan.</Text> : null}
    </ScrollView>
  );
}
