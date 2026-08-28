import { Text } from '@/components/ui/text';
import { formatIdr, mediaUrl } from '@/lib/format';
import type { Product } from '@/lib/api';
import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { Pressable, View } from 'react-native';

export function ProductCard({ product }: { product: Product }) {
  const hasSale = product.compare_price != null && product.compare_price > (product.price ?? 0);
  return (
    <Link href={`/product/${product.slug}`} asChild>
      <Pressable className="mb-3 w-[48%] overflow-hidden rounded-md border border-border bg-card">
        <View className="relative">
          <Image
            source={{ uri: mediaUrl(product.thumbnail ?? product.images?.[0]?.url) }}
            style={{ width: '100%', height: 148, backgroundColor: '#f4f4f5' }}
            contentFit="cover"
          />
          {hasSale ? (
            <View className="absolute left-2 top-2 rounded-full bg-om-sale px-2 py-0.5">
              <Text className="text-[10px] font-bold text-white">SALE</Text>
            </View>
          ) : null}
        </View>
        <View className="gap-1 p-3">
          <Text className="text-[11px] font-medium text-muted-foreground" numberOfLines={1}>
            {product.brand?.name ?? 'OceanMall'}
          </Text>
          <Text className="text-[13px] font-semibold leading-tight" numberOfLines={2}>
            {product.name}
          </Text>
          <View className="flex-row items-baseline gap-1.5">
            <Text className="text-[13px] font-bold text-om-navy">{formatIdr(product.price)}</Text>
            {hasSale ? (
              <Text className="text-[11px] text-muted-foreground line-through">
                {formatIdr(product.compare_price)}
              </Text>
            ) : null}
          </View>
        </View>
      </Pressable>
    </Link>
  );
}
