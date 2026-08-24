import { ProductCard } from '@/components/product-card';
import { Text } from '@/components/ui/text';
import { api, type Collection, type Product } from '@/lib/api';
import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, View } from 'react-native';

export default function HomeScreen() {
  const [products, setProducts] = useState<Product[]>([]);
  const [collections, setCollections] = useState<Collection[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const home = await api<{ data: { featured_products: Product[]; collections: Collection[] } }>(
        '/catalog/home'
      );
      let list = home.data.featured_products ?? [];
      setCollections(home.data.collections ?? []);

      if (list.length === 0) {
        const catalog = await api<{ data: Product[] }>('/catalog/products');
        list = catalog.data ?? [];
      }

      setProducts(list);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Gagal memuat katalog');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  if (loading) {
    return (
      <View className="flex-1 items-center justify-center">
        <ActivityIndicator />
      </View>
    );
  }

  return (
    <ScrollView
      className="flex-1 bg-background"
      contentContainerClassName="p-4"
      refreshControl={<RefreshControl refreshing={false} onRefresh={() => void load()} />}>
      <Text className="mb-1 text-2xl font-bold">OceanMall</Text>
      <Text className="mb-4 text-muted-foreground">Belanja, bayar, lacak, terima barang.</Text>
      {error ? <Text className="mb-3 text-destructive">{error}</Text> : null}
      {collections.length > 0 ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} className="mb-4">
          {collections.map((collection) => (
            <View key={collection.id} className="mr-2 rounded-full border border-border px-3 py-1.5">
              <Text className="text-sm">{collection.name}</Text>
            </View>
          ))}
        </ScrollView>
      ) : null}
      <View className="flex-row flex-wrap justify-between">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </View>
      {products.length === 0 && !error ? (
        <Text className="text-muted-foreground">Belum ada produk.</Text>
      ) : null}
    </ScrollView>
  );
}
