import { Field } from '@/components/field';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { errorMessage } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { Link, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, View } from 'react-native';

export default function RegisterScreen() {
  const { register, user } = useAuth();
  const router = useRouter();
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
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
      await register({
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        phone_number: phone.trim() || undefined,
        password,
        password_confirmation: confirm,
      });
      router.replace('/');
    } catch (e) {
      setError(errorMessage(e, 'Gagal daftar'));
    } finally {
      setBusy(false);
    }
  }

  return (
    <KeyboardAvoidingView
      className="flex-1 bg-background"
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerClassName="p-4 gap-4" keyboardShouldPersistTaps="handled">
        <Text className="text-2xl font-bold">Daftar</Text>
        {error ? <Text className="text-destructive">{error}</Text> : null}
        <Field label="Nama depan" value={firstName} onChangeText={setFirstName} autoComplete="given-name" />
        <Field label="Nama belakang" value={lastName} onChangeText={setLastName} autoComplete="family-name" />
        <Field
          label="Email"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          keyboardType="email-address"
          autoComplete="email"
        />
        <Field
          label="Nomor HP"
          value={phone}
          onChangeText={setPhone}
          keyboardType="phone-pad"
          autoComplete="tel"
        />
        <Field label="Kata sandi" value={password} onChangeText={setPassword} secureTextEntry />
        <Field label="Ulangi kata sandi" value={confirm} onChangeText={setConfirm} secureTextEntry />
        <Button
          disabled={busy || !firstName || !lastName || !email || !password || password !== confirm}
          onPress={() => void submit()}>
          <Text>{busy ? 'Mendaftar...' : 'Daftar'}</Text>
        </Button>
        <View className="flex-row justify-center gap-1">
          <Text className="text-muted-foreground">Sudah punya akun?</Text>
          <Link href="/login">
            <Text className="text-primary">Masuk</Text>
          </Link>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
