import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { api, type Collection, type Product } from '@/lib/api';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, View } from 'react-native';

type PageMeta = { current_page?: number; last_page?: number };

export default function HomeScreen() {
  const [products, setProducts] = useState<Product[]>([]);
  const [collections, setCollections] = useState<Collection[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [error, setError] = useState<string | null>(null);
  const fetchingPage = useRef<number | null>(null);

  const loadPage = useCallback(async (pageNumber: number, append: boolean) => {
    if (fetchingPage.current === pageNumber) {
      return;
    }
    fetchingPage.current = pageNumber;
    try {
      const catalog = await api<{ data: Product[]; meta?: PageMeta }>(
        `/catalog/products?page=${pageNumber}`
      );
      const list = catalog.data ?? [];
      setProducts((current) => {
        if (!append) {
          return list;
        }
        const known = new Set(current.map((product) => product.id));
        return [...current, ...list.filter((product) => !known.has(product.id))];
      });
      setPage(catalog.meta?.current_page ?? pageNumber);
      setLastPage(catalog.meta?.last_page ?? pageNumber);
    } finally {
      fetchingPage.current = null;
    }
  }, []);

  const load = useCallback(async () => {
    setError(null);
    try {
      const home = await api<{ data: { collections: Collection[] } }>('/catalog/home');
      setCollections(home.data.collections ?? []);
      await loadPage(1, false);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Gagal memuat katalog');
    } finally {
      setLoading(false);
    }
  }, [loadPage]);

  useEffect(() => {
    void load();
  }, [load]);

  async function loadMore() {
    if (loadingMore || page >= lastPage) {
      return;
    }
    setLoadingMore(true);
    try {
      await loadPage(page + 1, true);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Gagal memuat produk berikutnya');
    } finally {
      setLoadingMore(false);
    }
  }

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
      {loadingMore ? <ActivityIndicator className="my-3" /> : null}
      {!loadingMore && page < lastPage ? (
        <Button variant="outline" className="mt-1 mb-3" onPress={() => void loadMore()}>
          <Text>Muat lebih banyak</Text>
        </Button>
      ) : null}
    </ScrollView>
  );
}
