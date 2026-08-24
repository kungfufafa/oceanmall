import { PaymentPanel } from '@/components/payment-panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { api, errorMessage, type OrderDetail } from '@/lib/api';
import { formatIdr } from '@/lib/format';
import { useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, View } from 'react-native';

export default function OrderScreen() {
  const { number } = useLocalSearchParams<{ number: string }>();
  const [order, setOrder] = useState<OrderDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!number) {
      return;
    }
    try {
      const res = await api<{ data: OrderDetail }>(`/orders/${number}`);
      setOrder(res.data);
      setError(null);
    } catch (e) {
      setError(errorMessage(e, 'Pesanan tidak ditemukan'));
    } finally {
      setLoading(false);
    }
  }, [number]);

  useFocusEffect(
    useCallback(() => {
      setLoading(true);
      void load();
    }, [load])
  );

  async function run(action: () => Promise<void>) {
    setBusy(true);
    setError(null);
    try {
      await action();
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setBusy(false);
    }
  }

  if (loading) {
    return (
      <View className="flex-1 items-center justify-center">
        <ActivityIndicator />
      </View>
    );
  }

  if (!order) {
    return (
      <View className="flex-1 items-center justify-center p-6">
        <Text className="text-destructive">{error ?? 'Pesanan tidak ditemukan'}</Text>
      </View>
    );
  }

  const canConfirm =
    order.payment_status === 'paid' &&
    order.shipments.some((shipment) => Boolean(shipment.awb || shipment.tracking_number));

  return (
    <ScrollView
      className="flex-1 bg-background"
      contentContainerClassName="p-4 gap-4"
      refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}>
      <View className="gap-2">
        <Text className="text-xl font-bold">{order.number}</Text>
        <View className="flex-row flex-wrap gap-2">
          <Badge variant="secondary">
            <Text>{order.payment_status}</Text>
          </Badge>
          <Badge variant="outline">
            <Text>{order.status}</Text>
          </Badge>
          {order.shipping_status ? (
            <Badge variant="outline">
              <Text>{order.shipping_status}</Text>
            </Badge>
          ) : null}
        </View>
        <Text className="font-semibold">{formatIdr(order.amount)}</Text>
      </View>

      {error ? <Text className="text-destructive">{error}</Text> : null}

      <View className="gap-1">
        <Text className="font-semibold">Barang</Text>
        {order.items.map((item, index) => (
          <Text key={`${item.name}-${index}`} className="text-muted-foreground">
            {item.name} × {item.quantity} · {formatIdr(item.unit_price)}
          </Text>
        ))}
      </View>

      {order.payment_status !== 'paid' ? <PaymentPanel payment={order.payment} /> : null}

      {order.payment_status !== 'paid' ? (
        <View className="gap-2">
          <Button
            variant="outline"
            disabled={busy}
            onPress={() =>
              void run(async () => {
                const res = await api<{ data: { payment_status: string; payment: OrderDetail['payment'] } }>(
                  `/orders/${order.number}/sync-payment`,
                  { method: 'POST' }
                );
                setOrder((current) =>
                  current
                    ? {
                        ...current,
                        payment_status: res.data.payment_status,
                        payment: res.data.payment,
                      }
                    : current
                );
              })
            }>
            <Text>Cek status bayar</Text>
          </Button>
          {order.can_retry_payment ? (
            <Button
              disabled={busy}
              onPress={() =>
                void run(async () => {
                  const res = await api<{ data: { payment: OrderDetail['payment'] } }>(
                    `/orders/${order.number}/retry-payment`,
                    { method: 'POST' }
                  );
                  setOrder((current) =>
                    current ? { ...current, payment: res.data.payment } : current
                  );
                })
              }>
              <Text>Buat pembayaran baru</Text>
            </Button>
          ) : null}
        </View>
      ) : null}

      <View className="gap-2">
        <Text className="font-semibold">Pengiriman</Text>
        {order.shipments.length === 0 ? (
          <Text className="text-muted-foreground">Resi belum terbit. Muncul setelah gudang memproses.</Text>
        ) : (
          order.shipments.map((shipment) => (
            <View key={shipment.id} className="gap-1 rounded-xl border border-border p-3">
              <Text className="font-medium">
                {shipment.carrier ?? 'Kurir'} {shipment.service ? `· ${shipment.service}` : ''}
              </Text>
              <Text className="text-muted-foreground">{shipment.status}</Text>
              <Text selectable>{shipment.awb || shipment.tracking_number || 'AWB belum ada'}</Text>
              {(shipment.tracking_history ?? []).slice(0, 5).map((event, index) => (
                <Text key={index} className="text-xs text-muted-foreground">
                  {event.date ? `${event.date} · ` : ''}
                  {event.description}
                </Text>
              ))}
              <Button
                variant="outline"
                size="sm"
                disabled={busy}
                onPress={() =>
                  void run(async () => {
                    const res = await api<{ data: OrderDetail }>(
                      `/orders/${order.number}/shipments/${shipment.id}/track`,
                      { method: 'POST' }
                    );
                    setOrder(res.data);
                  })
                }>
                <Text>Lacak</Text>
              </Button>
            </View>
          ))
        )}
      </View>

      {canConfirm && order.status !== 'completed' ? (
        <Button
          disabled={busy}
          onPress={() =>
            void run(async () => {
              const res = await api<{ data: OrderDetail }>(`/orders/${order.number}/confirm-received`, {
                method: 'POST',
              });
              setOrder(res.data);
            })
          }>
          <Text>Konfirmasi barang diterima</Text>
        </Button>
      ) : null}
    </ScrollView>
  );
}
