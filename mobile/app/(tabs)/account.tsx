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
import { Alert, Pressable, ScrollView, View } from 'react-native';

export default function AccountScreen() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const [addresses, setAddresses] = useState<SavedAddress[]>([]);
  const [countries, setCountries] = useState<AddressCountry[]>([]);
  const [adding, setAdding] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [street, setStreet] = useState('');
  const [phone, setPhone] = useState('');
  const [city, setCity] = useState('');
  const [state, setState] = useState('');
  const [postalCode, setPostalCode] = useState('');
  const [destinationQuery, setDestinationQuery] = useState('');
  const [destinations, setDestinations] = useState<Destination[]>([]);
  const [destination, setDestination] = useState<Destination | null>(null);
  const [pinPoint, setPinPoint] = useState('');
  const [locating, setLocating] = useState(false);
  const [komerceEnabled, setKomerceEnabled] = useState(false);

  const loadAddresses = useCallback(async () => {
    if (!user) {
      setAddresses([]);
      return;
    }
    try {
      const res = await api<{
        data: SavedAddress[];
        countries: AddressCountry[];
        komerce_enabled?: boolean;
      }>('/addresses');
      setAddresses(res.data ?? []);
      setCountries(res.countries ?? []);
      setKomerceEnabled(Boolean(res.komerce_enabled));
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
    if (!komerceEnabled || destinationQuery.trim().length < 2) {
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
  }, [destinationQuery, komerceEnabled]);

  function resetForm() {
    setEditingId(null);
    setFirstName(user?.first_name ?? '');
    setLastName(user?.last_name ?? '');
    setStreet('');
    setPhone(user?.phone_number ?? '');
    setCity('');
    setState('');
    setPostalCode('');
    setDestinationQuery('');
    setDestinations([]);
    setDestination(null);
    setPinPoint('');
    setError(null);
  }

  function startEdit(address: SavedAddress) {
    setEditingId(address.id);
    setFirstName(address.first_name);
    setLastName(address.last_name);
    setStreet(address.street_address);
    setPhone(address.phone_number ?? '');
    setCity(address.city ?? '');
    setState(address.state ?? '');
    setPostalCode(address.postal_code ?? '');
    setPinPoint(address.rajaongkir_pin_point ?? '');
    setDestinationQuery('');
    setDestinations([]);
    setDestination(
      address.rajaongkir_destination_id
        ? {
            id: String(address.rajaongkir_destination_id),
            label: address.rajaongkir_destination_label ?? address.city,
            city_name: address.city,
            province_name: address.state,
            zip_code: address.postal_code,
          }
        : null
    );
    setError(null);
    setAdding(true);
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
    if (komerceEnabled && !destination) {
      setError('Pilih kecamatan RajaOngkir dulu.');
      return;
    }
    const nextCity = (destination?.city_name ?? destination?.label ?? city).trim();
    const nextPostal = (destination?.zip_code ?? postalCode).trim();
    if (!nextCity || !nextPostal) {
      setError('Isi kota dan kode pos.');
      return;
    }
    const countryId =
      (editingId ? addresses.find((row) => row.id === editingId)?.country_id : null) ??
      countries.find((country) => country.cca2 === 'ID')?.id ??
      countries[0]?.id ??
      null;
    if (!countryId) {
      setError('Negara pengiriman belum tersedia.');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const payload = {
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        street_address: street.trim(),
        postal_code: nextPostal,
        city: nextCity,
        state: (destination?.province_name ?? state).trim() || null,
        phone_number: phone.trim(),
        country_id: countryId,
        type: 'shipping',
        rajaongkir_destination_id: destination?.id ?? null,
        rajaongkir_destination_label: destination?.label ?? null,
        rajaongkir_pin_point: pinPoint.trim() || null,
      };
      if (editingId) {
        await api(`/addresses/${editingId}`, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
      } else {
        await api('/addresses', {
          method: 'POST',
          body: JSON.stringify({
            ...payload,
            shipping_default: addresses.length === 0,
          }),
        });
      }
      resetForm();
      setAdding(false);
      await loadAddresses();
    } catch (e) {
      setError(errorMessage(e, 'Gagal menyimpan alamat'));
    } finally {
      setBusy(false);
    }
  }

  async function deleteAddress(address: SavedAddress) {
    setBusy(true);
    setError(null);
    try {
      await api(`/addresses/${address.id}`, { method: 'DELETE' });
      if (editingId === address.id) {
        resetForm();
        setAdding(false);
      }
      await loadAddresses();
    } catch (e) {
      setError(errorMessage(e, 'Gagal menghapus alamat'));
    } finally {
      setBusy(false);
    }
  }

  async function setDefaultShipping(address: SavedAddress) {
    setBusy(true);
    setError(null);
    try {
      await api(`/addresses/${address.id}/default-shipping`, { method: 'PATCH' });
      await loadAddresses();
    } catch (e) {
      setError(errorMessage(e, 'Gagal menjadikan alamat utama'));
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
      {error && !adding ? <Text className="text-destructive">{error}</Text> : null}
      <Text className="font-semibold">Alamat tersimpan</Text>
      {addresses.map((address) => (
        <View key={address.id} className="rounded-xl border border-border p-3 gap-2">
          <View className="flex-row items-center justify-between gap-2">
            <Text className="font-medium">
              {address.first_name} {address.last_name}
            </Text>
            {address.shipping_default ? (
              <Text className="text-xs text-muted-foreground">Utama</Text>
            ) : null}
          </View>
          <Text className="text-muted-foreground">
            {address.street_address}, {address.city} {address.postal_code}
          </Text>
          {address.rajaongkir_destination_label ? (
            <Text className="text-sm text-muted-foreground">{address.rajaongkir_destination_label}</Text>
          ) : null}
          {address.rajaongkir_pin_point ? (
            <Text className="text-xs text-muted-foreground">Pin {address.rajaongkir_pin_point}</Text>
          ) : null}
          <View className="flex-row flex-wrap gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={busy || adding}
              onPress={() => startEdit(address)}>
              <Text>Ubah</Text>
            </Button>
            {address.shipping_default ? null : (
              <Button
                variant="outline"
                size="sm"
                disabled={busy}
                onPress={() => void setDefaultShipping(address)}>
                <Text>Jadikan utama</Text>
              </Button>
            )}
            <Button
              variant="ghost"
              size="sm"
              disabled={busy}
              onPress={() =>
                Alert.alert('Hapus alamat', 'Yakin ingin menghapus alamat ini?', [
                  { text: 'Tidak', style: 'cancel' },
                  {
                    text: 'Ya, hapus',
                    style: 'destructive',
                    onPress: () => void deleteAddress(address),
                  },
                ])
              }>
              <Text className="text-destructive">Hapus</Text>
            </Button>
          </View>
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
              placeholder={
                komerceEnabled ? 'Cari kecamatan / kode pos...' : 'Opsional saat Komerce dinonaktifkan'
              }
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
                  setCity(row.city_name ?? row.label);
                  setState(row.province_name ?? '');
                  setPostalCode(row.zip_code ?? '');
                }}
                className="rounded-md border border-border p-2">
                <Text>{row.label}</Text>
              </Pressable>
            ))}
          </View>
          {komerceEnabled ? (
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
          ) : null}
          <Field
            label="Provinsi"
            value={state}
            onChangeText={setState}
            editable={!komerceEnabled}
            placeholder={komerceEnabled ? 'Pilih dari kecamatan' : 'Contoh: DKI Jakarta'}
          />
          <Field
            label="Kota"
            value={city}
            onChangeText={setCity}
            editable={!komerceEnabled}
            placeholder={komerceEnabled ? 'Pilih dari kecamatan' : 'Contoh: Jakarta Selatan'}
          />
          <Field
            label="Kode pos"
            value={postalCode}
            onChangeText={setPostalCode}
            editable={!komerceEnabled}
            keyboardType="number-pad"
            placeholder={komerceEnabled ? 'Pilih dari kecamatan' : 'Contoh: 12190'}
          />
          <Button
            disabled={
              busy ||
              !firstName ||
              !lastName ||
              !street ||
              !phone ||
              !city.trim() ||
              !postalCode.trim() ||
              (komerceEnabled && !destination)
            }
            onPress={() => void saveAddress()}>
            <Text>{busy ? 'Menyimpan...' : editingId ? 'Simpan perubahan' : 'Simpan alamat'}</Text>
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
