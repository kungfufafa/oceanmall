import { ProductCard } from '@/components/product-card';
import { Text } from '@/components/ui/text';
import { api, fetchBrands, fetchCategories, fetchCollections, fetchFeatured, fetchPromo, type Brand, type Category, type Collection, type Product } from '@/lib/api';
import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Image, RefreshControl, ScrollView, View } from 'react-native';

export default function HomeScreen() {
  const [featured, setFeatured] = useState<Product[]>([]);
  const [promo, setPromo] = useState<Product[]>([]);
  const [collections, setCollections] = useState<Collection[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [brands, setBrands] = useState<Brand[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [feat, pr, cats, brs, cols] = await Promise.all([
        fetchFeatured(),
        fetchPromo(),
        fetchCategories(),
        fetchBrands(),
        fetchCollections(),
      ]);

      let featuredList = feat;
      if (featuredList.length === 0) {
        const fallback = await api<{ data: Product[] }>('/catalog/products');
        featuredList = fallback.data ?? [];
      }

      setFeatured(featuredList);
      setPromo(pr);
      setCategories(cats);
      setBrands(brs);
      setCollections(cols);
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

      {/* Categories - parity with web HomeController categories */}
      {categories.length > 0 ? (
        <View className="mb-4">
          <Text className="mb-2 text-sm font-semibold">Kategori</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            {categories.map((cat) => (
              <View key={cat.id} className="mr-3 w-16 items-center">
                <View className="mb-1 size-11 items-center justify-center overflow-hidden rounded-full bg-muted">
                  {cat.thumbnail ? (
                    <Image source={{ uri: cat.thumbnail }} className="size-full" resizeMode="cover" />
                  ) : (
                    <Text className="text-xs font-bold">{cat.name.slice(0, 2).toUpperCase()}</Text>
                  )}
                </View>
                <Text className="text-center text-[10px] leading-tight" numberOfLines={2}>
                  {cat.name}
                </Text>
              </View>
            ))}
          </ScrollView>
        </View>
      ) : null}

      {/* Featured Collections - parity with web HeroCarousel */}
      {collections.length > 0 ? (
        <View className="mb-4">
          <Text className="mb-2 text-sm font-semibold">Koleksi Unggulan</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            {collections.map((collection) => (
              <View key={collection.id} className="mr-2 rounded-full border border-border px-3 py-1.5">
                <Text className="text-sm">{collection.name}</Text>
              </View>
            ))}
          </ScrollView>
        </View>
      ) : null}

      {/* Brands - parity with web brands rail */}
      {brands.length > 0 ? (
        <View className="mb-4">
          <Text className="mb-2 text-sm font-semibold">Brand</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            {brands.map((brand) => (
              <View key={brand.id} className="mr-3 w-16 items-center">
                <View className="size-12 items-center justify-center overflow-hidden rounded-full bg-card ring-1 ring-border">
                  {brand.thumbnail ? (
                    <Image source={{ uri: brand.thumbnail }} className="size-full" resizeMode="contain" />
                  ) : (
                    <Text className="text-[11px] font-bold">{brand.name.slice(0, 2).toUpperCase()}</Text>
                  )}
                </View>
                <Text className="mt-1 text-center text-[10px] text-muted-foreground" numberOfLines={1}>
                  {brand.name}
                </Text>
              </View>
            ))}
          </ScrollView>
        </View>
      ) : null}

      {/* Promo - parity with web promoProducts */}
      {promo.length > 0 ? (
        <View className="mb-4">
          <Text className="mb-2 text-sm font-semibold">Promo hari ini</Text>
          <View className="flex-row flex-wrap justify-between">
            {promo.map((product) => (
              <ProductCard key={`promo-${product.id}`} product={product} />
            ))}
          </View>
        </View>
      ) : null}

      {/* Featured */}
      <View className="mb-2 flex-row items-center justify-between">
        <Text className="text-sm font-semibold">Produk unggulan</Text>
      </View>
      <View className="flex-row flex-wrap justify-between">
        {featured.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </View>
      {featured.length === 0 && promo.length === 0 && !error ? (
        <Text className="text-muted-foreground">Belum ada produk.</Text>
      ) : null}
    </ScrollView>
  );
}
