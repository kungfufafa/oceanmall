import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { View } from 'react-native';

export function EmptyState({
  message,
  actionLabel,
  onAction,
}: {
  message: string;
  actionLabel?: string;
  onAction?: () => void;
}) {
  return (
    <View className="flex-1 items-center justify-center gap-4 p-6">
      <Text className="text-center text-muted-foreground">{message}</Text>
      {actionLabel && onAction ? (
        <Button onPress={onAction}>
          <Text>{actionLabel}</Text>
        </Button>
      ) : null}
    </View>
  );
}
