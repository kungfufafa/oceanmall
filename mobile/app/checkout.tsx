import { CouponField } from '@/components/coupon-field';
import { EmptyState } from '@/components/empty-state';
import { Field } from '@/components/field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import {
  api,
  errorMessage,
  type AllocationPackage,
  type CheckoutPayload,
  type Destination,
  type PaymentMethodOption,
  type SavedAddress,
  type ShippingRate,
} from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { formatIdr } from '@/lib/format';
import * as Location from 'expo-location';
import { useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
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
  const [pinPoint, setPinPoint] = useState('');
  const [locating, setLocating] = useState(false);
  const [selectedRate, setSelectedRate] = useState<string | null>(null);
  const [ratesByPackage, setRatesByPackage] = useState<Record<string, string>>({});
  const [selectedPayment, setSelectedPayment] = useState<number | null>(null);

  const applyCheckout = useCallback((data: CheckoutPayload) => {
    setCheckout(data);
    const address = data.shipping_address;
    if (address) {
      setFirstName(address.first_name ?? '');
      setLastName(address.last_name ?? '');
      setStreet(address.street_address ?? '');
      setPhone(address.phone_number ?? '');
      setPinPoint(address.rajaongkir_pin_point ?? '');
      if (address.rajaongkir_destination_id) {
        setDestination({
          id: address.rajaongkir_destination_id,
          label: address.rajaongkir_destination_label ?? address.city ?? address.rajaongkir_destination_id,
        });
      }
    }
    setSelectedRate(data.shipping_option?.service_code ?? null);
    const perPackage: Record<string, string> = {};
    for (const pkg of data.allocation ?? []) {
      if (pkg.selected_service_code) {
        perPackage[String(pkg.inventory_id)] = pkg.selected_service_code;
      }
    }
    setRatesByPackage(perPackage);
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

  async function useCurrentLocation() {
    setLocating(true);
    setError(null);
    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        setError('Izin lokasi ditolak. Isi pin point manual (format: lat,long).');
        return;
      }
      const position = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.Balanced,
      });
      setPinPoint(`${position.coords.latitude.toFixed(6)},${position.coords.longitude.toFixed(6)}`);
    } catch {
      setError('Gagal membaca lokasi. Isi pin point manual (format: lat,long).');
    } finally {
      setLocating(false);
    }
  }

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
          rajaongkir_pin_point: pinPoint.trim() || null,
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

  async function submitPackageRates() {
    setBusy(true);
    setError(null);
    try {
      const res = await api<{ data: CheckoutPayload }>('/checkout/shipping-option', {
        method: 'POST',
        body: JSON.stringify({ rates: ratesByPackage }),
      });
      applyCheckout(res.data);
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
    const destinationBlocked = packages.some((pkg) => pkg.destination_pin_ready === false);
    const destinationMessage = packages.find((pkg) => pkg.pin_ready_message)?.pin_ready_message;
    if (destinationBlocked && destinationMessage) {
      setError(destinationMessage);
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await api<{ data: { number: string }; message?: string }>('/checkout/place-order', {
        method: 'POST',
        body: JSON.stringify({ payment_method_id: selectedPayment }),
      });
      if (res.message) {
        Alert.alert('Pesanan dibuat', res.message);
      }
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

  const packages: AllocationPackage[] = checkout.allocation ?? [];
  const destinationBlocked = packages.some((pkg) => pkg.destination_pin_ready === false);
  const isMultiPackage = packages.length > 1;
  const allPackagesSelected =
    packages.length > 0 && packages.every((pkg) => !!ratesByPackage[String(pkg.inventory_id)]);
  const selectedPackageRates = packages.map((pkg) => ({
    pkg,
    rate: pkg.rates.find(
      (rate) => rate.service_code === ratesByPackage[String(pkg.inventory_id)]
    ),
  }));
  const shippingTotal = selectedPackageRates.reduce(
    (sum, entry) => sum + (entry.rate?.amount ?? 0),
    0
  );
  const selectedSingleRate =
    checkout.shipping_rates.find((rate) => rate.service_code === selectedRate) ??
    (checkout.shipping_option?.service_code === selectedRate ? checkout.shipping_option : null);
  const shippingAmount = isMultiPackage
    ? shippingTotal
    : (selectedSingleRate?.amount ?? 0);
  const shippingKnown = isMultiPackage ? allPackagesSelected : Boolean(selectedRate);

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
        <Text className="text-muted-foreground">
          Subtotal {formatIdr(checkout.cart.totals.subtotal)}
        </Text>
        {checkout.cart.totals.discount > 0 ? (
          <Text className="text-emerald-600">
            Diskon −{formatIdr(checkout.cart.totals.discount)}
          </Text>
        ) : null}
        <CouponField
          couponCode={checkout.cart.coupon_code}
          disabled={busy}
          onCart={(cart) =>
            setCheckout((current) => (current ? { ...current, cart } : current))
          }
        />
        {shippingKnown ? (
          <Text className="text-muted-foreground">Ongkir {formatIdr(shippingAmount)}</Text>
        ) : null}
        <Text className="font-medium">
          Total {formatIdr(checkout.cart.totals.total + (shippingKnown ? shippingAmount : 0))}
        </Text>

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
                <View className="mt-1.5 flex-row">
                  {address.rajaongkir_pin_point ? (
                    <Badge variant="secondary">
                      <Text>Pin point tersimpan</Text>
                    </Badge>
                  ) : (
                    <Badge variant="outline">
                      <Text>Belum ada pin point</Text>
                    </Badge>
                  )}
                </View>
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
        <View className="gap-1.5">
          <Text className="text-sm font-medium">Pin point (lat,long)</Text>
          <Input
            placeholder="-6.238000,106.783000"
            value={pinPoint}
            onChangeText={setPinPoint}
            autoCapitalize="none"
          />
          <Button variant="outline" disabled={locating} onPress={() => void useCurrentLocation()}>
            <Text>{locating ? 'Membaca lokasi...' : 'Gunakan lokasi saat ini'}</Text>
          </Button>
          <Text className="text-xs text-muted-foreground">
            Titik antar dipakai kurir instan & penerbitan resi RajaOngkir.
          </Text>
        </View>
        <Button
          variant="outline"
          disabled={busy || !firstName || !lastName || !street || !phone || !destination}
          onPress={() => void saveAddress()}>
          <Text>Simpan alamat & hitung ongkir</Text>
        </Button>

        <Text className="font-semibold">Kurir</Text>
        {isMultiPackage ? (
          <View className="gap-3">
            <Text className="text-sm text-muted-foreground">
              Pesanan dikirim dalam {packages.length} paket dari gudang berbeda. Pilih kurir untuk
              setiap paket.
            </Text>
            {packages.map((pkg, index) => (
              <View key={pkg.inventory_id} className="gap-2 rounded-xl border border-border p-3">
                <Text className="font-medium">
                  Paket {index + 1} · {pkg.inventory_name}
                </Text>
                {pkg.pin_ready_message ? (
                  <Text className="text-sm text-amber-800">{pkg.pin_ready_message}</Text>
                ) : null}
                {pkg.lines.map((line, lineIndex) => (
                  <Text key={lineIndex} className="text-sm text-muted-foreground">
                    {line.name || `Produk #${line.purchasable_id}`} × {line.qty}
                  </Text>
                ))}
                {pkg.rates.map((rate) => (
                  <Pressable
                    key={rate.service_code}
                    onPress={() =>
                      setRatesByPackage((prev) => ({
                        ...prev,
                        [String(pkg.inventory_id)]: rate.service_code,
                      }))
                    }
                    className={`rounded-xl border p-3 ${
                      ratesByPackage[String(pkg.inventory_id)] === rate.service_code
                        ? 'border-primary bg-secondary'
                        : 'border-border'
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
                {pkg.rates.length === 0 ? (
                  <Text className="text-muted-foreground">Ongkir belum tersedia untuk paket ini.</Text>
                ) : null}
              </View>
            ))}
            {allPackagesSelected ? (
              <View className="gap-1.5 rounded-xl border border-border p-3">
                <Text className="font-semibold">Rincian ongkir</Text>
                {selectedPackageRates.map(({ pkg, rate }, index) =>
                  rate ? (
                    <View key={pkg.inventory_id} className="flex-row items-center justify-between gap-2">
                      <Text className="flex-1 text-sm text-muted-foreground">
                        Paket {index + 1} · {rate.carrier_name ?? rate.carrier_code} ·{' '}
                        {rate.service_name}
                      </Text>
                      <Text className="text-sm">{formatIdr(rate.amount)}</Text>
                    </View>
                  ) : null
                )}
                <View className="mt-1 flex-row items-center justify-between border-t border-border pt-2">
                  <Text className="font-medium">Total ongkir</Text>
                  <Text className="font-medium">{formatIdr(shippingTotal)}</Text>
                </View>
              </View>
            ) : null}
            <Button
              variant="outline"
              disabled={busy || !allPackagesSelected}
              onPress={() => void submitPackageRates()}>
              <Text>Simpan pilihan kurir</Text>
            </Button>
          </View>
        ) : (
          <>
            {packages[0]?.pin_ready_message ? (
              <Text className="text-sm text-amber-800">{packages[0].pin_ready_message}</Text>
            ) : null}
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
          </>
        )}

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

        <Button
          disabled={busy || !selectedRate || !selectedPayment || destinationBlocked}
          onPress={() => void placeOrder()}>
          <Text>{busy ? 'Memproses...' : 'Buat pesanan'}</Text>
        </Button>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
