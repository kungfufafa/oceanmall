import { Icon } from '@/components/ui/icon';
import { Tabs } from 'expo-router';
import { HomeIcon, PackageIcon, SearchIcon, ShoppingBagIcon, UserIcon } from 'lucide-react-native';

export default function TabsLayout() {
  return (
    <Tabs
      screenOptions={{
        headerTitleStyle: { fontWeight: '600' },
        tabBarLabelStyle: { fontSize: 11 },
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'OceanMall',
          tabBarLabel: 'Beranda',
          tabBarIcon: ({ color, size }) => <Icon as={HomeIcon} color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="search"
        options={{
          title: 'Cari',
          tabBarIcon: ({ color, size }) => <Icon as={SearchIcon} color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="cart"
        options={{
          title: 'Keranjang',
          tabBarIcon: ({ color, size }) => <Icon as={ShoppingBagIcon} color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="orders"
        options={{
          title: 'Pesanan',
          tabBarIcon: ({ color, size }) => <Icon as={PackageIcon} color={color} size={size} />,
        }}
      />
      <Tabs.Screen
        name="account"
        options={{
          title: 'Akun',
          tabBarIcon: ({ color, size }) => <Icon as={UserIcon} color={color} size={size} />,
        }}
      />
    </Tabs>
  );
}
