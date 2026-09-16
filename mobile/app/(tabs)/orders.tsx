import { EmptyState } from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { api, type OrderSummary } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { formatIdr } from '@/lib/format';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useRef, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, View } from 'react-native';

type PageMeta = { current_page?: number; last_page?: number };

export default function OrdersScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const [orders, setOrders] = useState<OrderSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const fetchingPage = useRef<number | null>(null);

  const loadPage = useCallback(async (pageNumber: number, append: boolean) => {
    if (fetchingPage.current === pageNumber) {
      return;
    }
    fetchingPage.current = pageNumber;
    try {
      const res = await api<{ data: OrderSummary[]; meta?: PageMeta }>(
        `/orders?page=${pageNumber}`
      );
      const list = res.data ?? [];
      setOrders((current) => {
        if (!append) {
          return list;
        }
        const known = new Set(current.map((order) => order.number));
        return [...current, ...list.filter((order) => !known.has(order.number))];
      });
      setPage(res.meta?.current_page ?? pageNumber);
      setLastPage(res.meta?.last_page ?? pageNumber);
    } finally {
      fetchingPage.current = null;
    }
  }, []);

  const load = useCallback(async () => {
    if (!user) {
      setOrders([]);
      setLoading(false);
      return;
    }
    try {
      await loadPage(1, false);
    } catch {
      setOrders([]);
    } finally {
      setLoading(false);
    }
  }, [user, loadPage]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load])
  );

  async function loadMore() {
    if (loadingMore || page >= lastPage) {
      return;
    }
    setLoadingMore(true);
    try {
      await loadPage(page + 1, true);
    } catch {
      // keep already-loaded orders on failure
    } finally {
      setLoadingMore(false);
    }
  }

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
      {loadingMore ? <ActivityIndicator className="my-2" /> : null}
      {!loadingMore && page < lastPage ? (
        <Button variant="outline" onPress={() => void loadMore()}>
          <Text>Muat lebih banyak</Text>
        </Button>
      ) : null}
    </ScrollView>
  );
}
