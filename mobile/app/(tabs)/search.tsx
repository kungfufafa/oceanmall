import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import { api, type Product } from '@/lib/api';
import { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, ScrollView, View } from 'react-native';

type PageMeta = { current_page?: number; last_page?: number };

function pathFor(query: string, page: number): string {
  return query.length < 2
    ? `/catalog/products?page=${page}`
    : `/catalog/search?q=${encodeURIComponent(query)}&page=${page}`;
}

export default function SearchScreen() {
  const [q, setQ] = useState('');
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const requestId = useRef(0);

  useEffect(() => {
    const query = q.trim();
    const handle = setTimeout(() => {
      const id = ++requestId.current;
      setLoading(true);
      api<{ data: Product[]; meta?: PageMeta }>(pathFor(query, 1))
        .then((res) => {
          if (id !== requestId.current) {
            return;
          }
          setProducts(res.data ?? []);
          setPage(res.meta?.current_page ?? 1);
          setLastPage(res.meta?.last_page ?? 1);
        })
        .catch(() => {
          if (id === requestId.current) {
            setProducts([]);
            setPage(1);
            setLastPage(1);
          }
        })
        .finally(() => {
          if (id === requestId.current) {
            setLoading(false);
          }
        });
    }, query.length < 2 ? 0 : 350);
    return () => clearTimeout(handle);
  }, [q]);

  async function loadMore() {
    if (loading || loadingMore || page >= lastPage) {
      return;
    }
    const id = requestId.current;
    setLoadingMore(true);
    try {
      const res = await api<{ data: Product[]; meta?: PageMeta }>(pathFor(q.trim(), page + 1));
      if (id !== requestId.current) {
        return;
      }
      const list = res.data ?? [];
      setProducts((current) => {
        const known = new Set(current.map((product) => product.id));
        return [...current, ...list.filter((product) => !known.has(product.id))];
      });
      setPage(res.meta?.current_page ?? page + 1);
      setLastPage(res.meta?.last_page ?? lastPage);
    } catch {
      // keep already-loaded results on failure
    } finally {
      setLoadingMore(false);
    }
  }

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
      {loadingMore ? <ActivityIndicator className="my-3" /> : null}
      {!loading && !loadingMore && page < lastPage ? (
        <Button variant="outline" className="mt-1 mb-3" onPress={() => void loadMore()}>
          <Text>Muat lebih banyak</Text>
        </Button>
      ) : null}
    </ScrollView>
  );
}
