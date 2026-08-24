import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Text } from '@/components/ui/text';
import { api, type SavedAddress } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { ScrollView, View } from 'react-native';

export default function AccountScreen() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const [addresses, setAddresses] = useState<SavedAddress[]>([]);

  useFocusEffect(
    useCallback(() => {
      if (!user) {
        setAddresses([]);
        return;
      }
      void api<{ data: SavedAddress[] }>('/addresses')
        .then((res) => setAddresses(res.data ?? []))
        .catch(() => setAddresses([]));
    }, [user])
  );

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
    <ScrollView className="flex-1 bg-background" contentContainerClassName="p-4 gap-4">
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
        </View>
      ))}
      {addresses.length === 0 ? (
        <Text className="text-muted-foreground">Belum ada alamat. Isi saat checkout.</Text>
      ) : null}

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
