import { EmptyState } from '@/components/empty-state';
import { Field } from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Text } from '@/components/ui/text';
import {
  api,
  errorMessage,
  type AddressCountry,
  type Destination,
  type SavedAddress,
} from '@/lib/api';
import { useAuth } from '@/lib/auth';
import * as Location from 'expo-location';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { Pressable, ScrollView, View } from 'react-native';

export default function AccountScreen() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const [addresses, setAddresses] = useState<SavedAddress[]>([]);
  const [countries, setCountries] = useState<AddressCountry[]>([]);
  const [adding, setAdding] = useState(false);
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

  const loadAddresses = useCallback(async () => {
    if (!user) {
      setAddresses([]);
      return;
    }
    try {
      const res = await api<{ data: SavedAddress[]; countries: AddressCountry[] }>('/addresses');
      setAddresses(res.data ?? []);
      setCountries(res.countries ?? []);
    } catch {
      setAddresses([]);
    }
  }, [user]);

  useFocusEffect(
    useCallback(() => {
      void loadAddresses();
    }, [loadAddresses])
  );

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

  function resetForm() {
    setFirstName(user?.first_name ?? '');
    setLastName(user?.last_name ?? '');
    setStreet('');
    setPhone(user?.phone_number ?? '');
    setDestinationQuery('');
    setDestinations([]);
    setDestination(null);
    setPinPoint('');
    setError(null);
  }

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
    const countryId =
      countries.find((country) => country.cca2 === 'ID')?.id ?? countries[0]?.id ?? null;
    if (!countryId) {
      setError('Negara pengiriman belum tersedia.');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await api('/addresses', {
        method: 'POST',
        body: JSON.stringify({
          first_name: firstName.trim(),
          last_name: lastName.trim(),
          street_address: street.trim(),
          postal_code: destination.zip_code ?? '00000',
          city: destination.city_name ?? destination.label,
          state: destination.province_name,
          phone_number: phone.trim(),
          country_id: countryId,
          type: 'shipping',
          shipping_default: addresses.length === 0,
          rajaongkir_destination_id: destination.id,
          rajaongkir_destination_label: destination.label,
          rajaongkir_pin_point: pinPoint.trim() || null,
        }),
      });
      resetForm();
      setAdding(false);
      await loadAddresses();
    } catch (e) {
      setError(errorMessage(e, 'Gagal menyimpan alamat'));
    } finally {
      setBusy(false);
    }
  }

  if (!user) {
    return (
      <EmptyState
        message="Masuk untuk mengelola akun, alamat, dan pesanan."
        actionLabel="Masuk"
        onAction={() => router.push('/login')}
      />
    );
  }

  return (
    <ScrollView
      className="flex-1 bg-background"
      contentContainerClassName="p-4 gap-4"
      keyboardShouldPersistTaps="handled">
      <View className="rounded-xl border border-border bg-card p-4">
        <Text className="text-lg font-semibold">
          {user.first_name} {user.last_name}
        </Text>
        <Text className="text-muted-foreground">{user.email}</Text>
        {user.phone_number ? <Text className="text-muted-foreground">{user.phone_number}</Text> : null}
      </View>

      <Button variant="outline" onPress={() => router.push('/(tabs)/orders')}>
        <Text>Lihat pesanan</Text>
      </Button>

      <Separator />
      <Text className="font-semibold">Alamat tersimpan</Text>
      {addresses.map((address) => (
        <View key={address.id} className="rounded-xl border border-border p-3">
          <Text className="font-medium">
            {address.first_name} {address.last_name}
          </Text>
          <Text className="text-muted-foreground">
            {address.street_address}, {address.city} {address.postal_code}
          </Text>
          {address.rajaongkir_destination_label ? (
            <Text className="text-sm text-muted-foreground">{address.rajaongkir_destination_label}</Text>
          ) : null}
          {address.rajaongkir_pin_point ? (
            <Text className="text-xs text-muted-foreground">Pin {address.rajaongkir_pin_point}</Text>
          ) : null}
        </View>
      ))}
      {addresses.length === 0 && !adding ? (
        <Text className="text-muted-foreground">Belum ada alamat. Tambah di sini atau isi saat checkout.</Text>
      ) : null}

      {adding ? (
        <View className="gap-3 rounded-xl border border-border p-3">
          {error ? <Text className="text-destructive">{error}</Text> : null}
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
          </View>
          <Button
            disabled={busy || !firstName || !lastName || !street || !phone || !destination}
            onPress={() => void saveAddress()}>
            <Text>{busy ? 'Menyimpan...' : 'Simpan alamat'}</Text>
          </Button>
          <Button
            variant="ghost"
            onPress={() => {
              setAdding(false);
              resetForm();
            }}>
            <Text>Batal</Text>
          </Button>
        </View>
      ) : (
        <Button
          variant="outline"
          onPress={() => {
            resetForm();
            setAdding(true);
          }}>
          <Text>Tambah alamat</Text>
        </Button>
      )}

      <Button
        variant="destructive"
        onPress={() => {
          void logout();
        }}>
        <Text>Keluar</Text>
      </Button>
    </ScrollView>
  );
}
