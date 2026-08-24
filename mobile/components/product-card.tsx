import { Text } from '@/components/ui/text';
import { formatIdr, mediaUrl } from '@/lib/format';
import type { Product } from '@/lib/api';
import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { Pressable, View } from 'react-native';

export function ProductCard({ product }: { product: Product }) {
  return (
    <Link href={`/product/${product.slug}`} asChild>
      <Pressable className="mb-3 w-[48%] overflow-hidden rounded-xl border border-border bg-card">
        <Image
          source={{ uri: mediaUrl(product.thumbnail ?? product.images?.[0]?.url) }}
          style={{ width: '100%', height: 140, backgroundColor: '#f4f4f5' }}
          contentFit="cover"
        />
        <View className="gap-1 p-3">
          <Text className="text-xs text-muted-foreground" numberOfLines={1}>
            {product.brand?.name ?? 'OceanMall'}
          </Text>
          <Text className="text-sm font-medium" numberOfLines={2}>
            {product.name}
          </Text>
          <Text className="text-sm font-semibold">{formatIdr(product.price)}</Text>
        </View>
      </Pressable>
    </Link>
  );
}
