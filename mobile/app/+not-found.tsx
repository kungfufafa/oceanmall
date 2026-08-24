import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { Link, Stack } from 'expo-router';
import { View } from 'react-native';

export default function NotFoundScreen() {
  return (
    <>
      <Stack.Screen options={{ title: 'Tidak ditemukan' }} />
      <View className="flex-1 items-center justify-center gap-4 p-6">
        <Text className="text-center text-muted-foreground">Halaman ini tidak ada.</Text>
        <Link href="/" asChild>
          <Button>
            <Text>Kembali ke beranda</Text>
          </Button>
        </Link>
      </View>
    </>
  );
}
