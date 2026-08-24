import { ProductCard } from '@/components/product-card';
import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import { api, type Product } from '@/lib/api';
import { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, View } from 'react-native';

export default function SearchScreen() {
  const [q, setQ] = useState('');
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const handle = setTimeout(() => {
      setLoading(true);
      const path =
        q.trim().length < 2
          ? '/catalog/products'
          : `/catalog/search?q=${encodeURIComponent(q.trim())}`;
      api<{ data: Product[] }>(path)
        .then((res) => setProducts(res.data ?? []))
        .catch(() => setProducts([]))
        .finally(() => setLoading(false));
    }, q.trim().length < 2 ? 0 : 350);
    return () => clearTimeout(handle);
  }, [q]);

  return (
    <ScrollView className="flex-1 bg-background" contentContainerClassName="p-4">
      <Input placeholder="Cari produk..." value={q} onChangeText={setQ} autoCapitalize="none" />
      {loading ? <ActivityIndicator className="mt-6" /> : null}
      <View className="mt-4 flex-row flex-wrap justify-between">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </View>
      {q.trim().length >= 2 && !loading && products.length === 0 ? (
        <Text className="mt-4 text-muted-foreground">Tidak ada hasil.</Text>
      ) : null}
    </ScrollView>
  );
}
