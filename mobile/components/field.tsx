import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { View } from 'react-native';

export function Field({
  label,
  ...props
}: { label: string } & React.ComponentProps<typeof Input>) {
  return (
    <View className="gap-1.5">
      <Label>{label}</Label>
      <Input {...props} />
    </View>
  );
}
