import { Field } from '@/components/field';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { errorMessage } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { Link, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, View } from 'react-native';

export default function LoginScreen() {
  const { login, user } = useAuth();
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (user) {
      router.replace('/');
    }
  }, [router, user]);

  async function submit() {
    setBusy(true);
    setError(null);
    try {
      await login(email.trim(), password);
      router.replace('/');
    } catch (e) {
      setError(errorMessage(e, 'Gagal masuk'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <KeyboardAvoidingView
      className="flex-1 bg-background"
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerClassName="p-4 gap-4" keyboardShouldPersistTaps="handled">
        <Text className="text-2xl font-bold">Masuk</Text>
        <Text className="text-muted-foreground">Pakai akun customer OceanMall.</Text>
        {error ? <Text className="text-destructive">{error}</Text> : null}
        <Field
          label="Email"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          keyboardType="email-address"
          autoComplete="email"
        />
        <Field
          label="Kata sandi"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoComplete="password"
        />
        <Button disabled={busy || !email || !password} onPress={() => void submit()}>
          <Text>{busy ? 'Masuk...' : 'Masuk'}</Text>
        </Button>
        <View className="flex-row justify-center gap-1">
          <Text className="text-muted-foreground">Belum punya akun?</Text>
          <Link href="/register">
            <Text className="text-primary">Daftar</Text>
          </Link>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
