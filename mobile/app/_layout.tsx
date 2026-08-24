import '@/global.css';

import { AuthGate, AuthProvider } from '@/lib/auth';
import { NAV_THEME } from '@/lib/theme';
import { PortalHost } from '@rn-primitives/portal';
import { Stack, ThemeProvider } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useColorScheme } from 'nativewind';

export { ErrorBoundary } from 'expo-router';

export default function RootLayout() {
  const { colorScheme } = useColorScheme();

  return (
    <ThemeProvider value={NAV_THEME[colorScheme ?? 'light']}>
      <AuthProvider>
        <AuthGate>
          <StatusBar style={colorScheme === 'dark' ? 'light' : 'dark'} />
          <Stack screenOptions={{ headerBackTitle: 'Kembali' }}>
            <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
            <Stack.Screen name="login" options={{ title: 'Masuk' }} />
            <Stack.Screen name="register" options={{ title: 'Daftar' }} />
            <Stack.Screen name="product/[slug]" options={{ title: 'Produk' }} />
            <Stack.Screen name="checkout" options={{ title: 'Checkout' }} />
            <Stack.Screen name="order/[number]" options={{ title: 'Pesanan' }} />
          </Stack>
          <PortalHost />
        </AuthGate>
      </AuthProvider>
    </ThemeProvider>
  );
}
