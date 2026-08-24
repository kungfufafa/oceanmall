import { EmptyState } from '@/components/empty-state';
import { Field } from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import {
  api,
  errorMessage,
  type CheckoutPayload,
  type Destination,
  type PaymentMethodOption,
  type SavedAddress,
  type ShippingRate,
} from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { formatIdr } from '@/lib/format';
import { useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  View,
} from 'react-native';

export default function CheckoutScreen() {
  const { user } = useAuth();
  const router = useRouter();
  const [checkout, setCheckout] = useState<CheckoutPayload | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [street, setStreet] = useState('');
  const [phone, setPhone] = useState('');
  const [destinationQuery, setDestinationQuery] = useState('');
  const [destinations, setDestinations] = useState<Destination[]>([]);
  const [destination, setDestination] = useState<Destination | null>(null);
  const [selectedRate, setSelectedRate] = useState<string | null>(null);
  const [selectedPayment, setSelectedPayment] = useState<number | null>(null);

  const applyCheckout = useCallback((data: CheckoutPayload) => {
    setCheckout(data);
    const address = data.shipping_address;
    if (address) {
      setFirstName(address.first_name ?? '');
      setLastName(address.last_name ?? '');
      setStreet(address.street_address ?? '');
      setPhone(address.phone_number ?? '');
      if (address.rajaongkir_destination_id) {
        setDestination({
          id: address.rajaongkir_destination_id,
          label: address.rajaongkir_destination_label ?? address.city ?? address.rajaongkir_destination_id,
        });
      }
    }
    setSelectedRate(data.shipping_option?.service_code ?? null);
  }, []);

  const load = useCallback(async () => {
    if (!user) {
      setLoading(false);
      return;
    }
    try {
      const res = await api<{ data: CheckoutPayload }>('/checkout');
      applyCheckout(res.data);
    } catch (e) {
      setError(errorMessage(e, 'Gagal memuat checkout'));
    } finally {
      setLoading(false);
    }
  }, [applyCheckout, user]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (destinationQuery.trim().length < 2) {
      setDestinations([]);
      return;
    }
    const handle = setTimeout(() => {
      void api<{ data: Destination[] }>(
        `/checkout/destinations?q=${encodeURIComponent(destinationQuery.trim())}`
      )
        .then((res) => setDestinations(res.data ?? []))
        .catch(() => setDestinations([]));
    }, 350);
    return () => clearTimeout(handle);
  }, [destinationQuery]);

  async function saveAddress() {
    if (!destination) {
      setError('Pilih kecamatan RajaOngkir dulu.');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await api<{ data: CheckoutPayload }>('/checkout/shipping-address', {
        method: 'POST',
        body: JSON.stringify({
          first_name: firstName.trim(),
          last_name: lastName.trim(),
          street_address: street.trim(),
          postal_code: destination.zip_code ?? checkout?.shipping_address?.postal_code ?? '00000',
          city: destination.city_name ?? destination.label,
          state: destination.province_name,
          phone_number: phone.trim(),
          rajaongkir_destination_id: destination.id,
          rajaongkir_destination_label: destination.label,
        }),
      });
      applyCheckout(res.data);
    } catch (e) {
      setError(errorMessage(e, 'Gagal menyimpan alamat'));
    } finally {
      setBusy(false);
    }
  }

  async function useSavedAddress(address: SavedAddress) {
    setBusy(true);
    setError(null);
    try {
      const res = await api<{ data: CheckoutPayload }>('/checkout/shipping-address/saved', {
        method: 'POST',
        body: JSON.stringify({ address_id: address.id }),
      });
      applyCheckout(res.data);
    } catch (e) {
      setError(errorMessage(e, 'Alamat belum bisa dipakai'));
    } finally {
      setBusy(false);
    }
  }

  async function chooseRate(rate: ShippingRate) {
    setBusy(true);
    setError(null);
    try {
      const res = await api<{ data: CheckoutPayload }>('/checkout/shipping-option', {
        method: 'POST',
        body: JSON.stringify({ service_code: rate.service_code }),
      });
      applyCheckout(res.data);
      setSelectedRate(rate.service_code);
    } catch (e) {
      setError(errorMessage(e, 'Gagal memilih kurir'));
    } finally {
      setBusy(false);
    }
  }

  async function placeOrder() {
    if (!selectedPayment) {
      setError('Pilih metode pembayaran.');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await api<{ data: { number: string } }>('/checkout/place-order', {
        method: 'POST',
        body: JSON.stringify({ payment_method_id: selectedPayment }),
      });
      router.replace(`/order/${res.data.number}`);
    } catch (e) {
      setError(errorMessage(e, 'Gagal membuat pesanan'));
    } finally {
      setBusy(false);
    }
  }

  if (!user) {
    return (
      <EmptyState
        message="Masuk untuk checkout."
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

  if (!checkout?.cart.lines.length) {
    return (
      <EmptyState
        message="Keranjang kosong."
        actionLabel="Belanja"
        onAction={() => router.replace('/')}
      />
    );
  }

  return (
    <KeyboardAvoidingView
      className="flex-1 bg-background"
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerClassName="p-4 gap-4" keyboardShouldPersistTaps="handled">
        {error ? <Text className="text-destructive">{error}</Text> : null}

        <Text className="font-semibold">Ringkasan</Text>
        {checkout.cart.lines.map((line) => (
          <Text key={line.id} className="text-muted-foreground">
            {line.name} × {line.quantity} · {formatIdr(line.unit_price * line.quantity)}
          </Text>
        ))}
        <Text className="font-medium">Subtotal {formatIdr(checkout.cart.totals.total)}</Text>

        {(checkout.saved_addresses ?? []).length > 0 ? (
          <View className="gap-2">
            <Text className="font-semibold">Alamat tersimpan</Text>
            {checkout.saved_addresses.map((address) => (
              <Pressable
                key={address.id}
                onPress={() => void useSavedAddress(address)}
                className="rounded-xl border border-border p-3">
                <Text className="font-medium">
                  {address.first_name} {address.last_name}
                </Text>
                <Text className="text-sm text-muted-foreground">
                  {address.street_address}, {address.city}
                </Text>
              </Pressable>
            ))}
          </View>
        ) : null}

        <Text className="font-semibold">Alamat pengiriman</Text>
        <Field label="Nama depan" value={firstName} onChangeText={setFirstName} />
        <Field label="Nama belakang" value={lastName} onChangeText={setLastName} />
        <Field label="Alamat" value={street} onChangeText={setStreet} />
        <Field label="Nomor HP" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
        <View className="gap-1.5">
          <Text className="text-sm font-medium">Kecamatan (RajaOngkir)</Text>
          <Input
            placeholder="Cari kecamatan / kode pos..."
            value={destination ? destination.label : destinationQuery}
            onChangeText={(value) => {
              setDestination(null);
              setDestinationQuery(value);
            }}
          />
          {destinations.map((row) => (
            <Pressable
              key={row.id}
              onPress={() => {
                setDestination(row);
                setDestinationQuery('');
                setDestinations([]);
              }}
              className="rounded-md border border-border p-2">
              <Text>{row.label}</Text>
            </Pressable>
          ))}
        </View>
        <Button
          variant="outline"
          disabled={busy || !firstName || !lastName || !street || !phone || !destination}
          onPress={() => void saveAddress()}>
          <Text>Simpan alamat & hitung ongkir</Text>
        </Button>

        <Text className="font-semibold">Kurir</Text>
        {(checkout.shipping_rates ?? []).map((rate) => (
          <Pressable
            key={rate.service_code}
            onPress={() => void chooseRate(rate)}
            className={`rounded-xl border p-3 ${
              selectedRate === rate.service_code ? 'border-primary bg-secondary' : 'border-border'
            }`}>
            <Text className="font-medium">
              {rate.carrier_name ?? rate.carrier_code} · {rate.service_name}
            </Text>
            <Text className="text-muted-foreground">
              {formatIdr(rate.amount)}
              {rate.estimated_days
                ? ` · ${String(rate.estimated_days)}${/hari|day|jam/i.test(String(rate.estimated_days)) ? '' : ' hari'}`
                : ''}
            </Text>
          </Pressable>
        ))}
        {checkout.shipping_address && checkout.shipping_rates.length === 0 ? (
          <Text className="text-muted-foreground">Ongkir belum tersedia untuk alamat ini.</Text>
        ) : null}

        <Text className="font-semibold">Pembayaran</Text>
        {(checkout.payment_methods ?? []).map((method: PaymentMethodOption) => (
          <Pressable
            key={method.id}
            onPress={() => setSelectedPayment(method.id)}
            className={`rounded-xl border p-3 ${
              selectedPayment === method.id ? 'border-primary bg-secondary' : 'border-border'
            }`}>
            <Text className="font-medium">{method.title}</Text>
            {method.description ? (
              <Text className="text-sm text-muted-foreground">{method.description}</Text>
            ) : null}
          </Pressable>
        ))}

        <Button disabled={busy || !selectedRate || !selectedPayment} onPress={() => void placeOrder()}>
          <Text>{busy ? 'Memproses...' : 'Buat pesanan'}</Text>
        </Button>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
