import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { api, errorMessage, type Product } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { formatIdr, mediaUrl, stripHtml } from '@/lib/format';
import { Image } from 'expo-image';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, View } from 'react-native';

export default function ProductScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { user } = useAuth();
  const router = useRouter();
  const [product, setProduct] = useState<Product | null>(null);
  const [variantId, setVariantId] = useState<number | null>(null);
  const [qty, setQty] = useState(1);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) {
      return;
    }
    setLoading(true);
    api<{ data: Product }>(`/catalog/products/${slug}`)
      .then((res) => {
        setProduct(res.data);
        setVariantId(res.data.variants?.[0]?.id ?? null);
      })
      .catch((e) => setError(errorMessage(e, 'Produk tidak ditemukan')))
      .finally(() => setLoading(false));
  }, [slug]);

  async function addToCart() {
    if (!product) {
      return;
    }
    if (!user) {
      router.push('/login');
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await api('/cart/items', {
        method: 'POST',
        body: JSON.stringify({
          product_id: product.id,
          ...(variantId ? { variant_id: variantId } : {}),
          quantity: qty,
        }),
      });
      router.push('/(tabs)/cart');
    } catch (e) {
      setError(errorMessage(e, 'Gagal menambah ke keranjang'));
    } finally {
      setBusy(false);
    }
  }

  if (loading) {
    return (
      <View className="flex-1 items-center justify-center">
        <ActivityIndicator />
      </View>
    );
  }

  if (!product) {
    return (
      <View className="flex-1 items-center justify-center p-6">
        <Text className="text-destructive">{error ?? 'Produk tidak ditemukan'}</Text>
      </View>
    );
  }

  const image = mediaUrl(product.thumbnail ?? product.images?.[0]?.url);
  const selectedVariant = product.variants?.find((variant) => variant.id === variantId);
  const price = selectedVariant?.price ?? product.price;

  return (
    <ScrollView className="flex-1 bg-background" contentContainerClassName="pb-8">
      <Image
        source={{ uri: image }}
        style={{ width: '100%', height: 320, backgroundColor: '#f4f4f5' }}
        contentFit="cover"
      />
      <View className="gap-3 p-4">
        <Text className="text-xs text-muted-foreground">{product.brand?.name ?? 'OceanMall'}</Text>
        <Text className="text-2xl font-bold">{product.name}</Text>
        <Text className="text-xl font-semibold">{formatIdr(price)}</Text>
        {product.description ? (
          <Text className="text-muted-foreground">{stripHtml(product.description)}</Text>
        ) : null}
        {error ? <Text className="text-destructive">{error}</Text> : null}

        {(product.variants?.length ?? 0) > 0 ? (
          <View className="gap-2">
            <Text className="font-medium">Varian</Text>
            <View className="flex-row flex-wrap gap-2">
              {product.variants?.map((variant) => (
                <Pressable
                  key={variant.id}
                  onPress={() => setVariantId(variant.id)}
                  className={`rounded-full border px-3 py-1.5 ${
                    variantId === variant.id ? 'border-primary bg-secondary' : 'border-border'
                  }`}>
                  <Text>{variant.name ?? variant.sku ?? `#${variant.id}`}</Text>
                </Pressable>
              ))}
            </View>
          </View>
        ) : null}

        <View className="flex-row items-center gap-3">
          <Pressable
            onPress={() => setQty((value) => Math.max(1, value - 1))}
            className="h-10 w-10 items-center justify-center rounded-md border border-border">
            <Text>-</Text>
          </Pressable>
          <Text>{qty}</Text>
          <Pressable
            onPress={() => setQty((value) => Math.min(10, value + 1))}
            className="h-10 w-10 items-center justify-center rounded-md border border-border">
            <Text>+</Text>
          </Pressable>
        </View>

        <Button disabled={busy} onPress={() => void addToCart()}>
          <Text>{busy ? 'Menambah...' : 'Tambah ke keranjang'}</Text>
        </Button>
      </View>
    </ScrollView>
  );
}
