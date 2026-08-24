import { API_URL } from '@/lib/config';

let authToken: string | null = null;

export function setAuthToken(token: string | null): void {
  authToken = token;
}

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public body: unknown
  ) {
    super(message);
  }
}

export function errorMessage(error: unknown, fallback = 'Terjadi kesalahan.'): string {
  if (error instanceof ApiError) {
    const body = error.body as { message?: string; errors?: Record<string, string[] | string> };
    if (body?.errors) {
      const first = Object.values(body.errors)[0];
      if (Array.isArray(first) && first[0]) {
        return first[0];
      }
      if (typeof first === 'string' && first !== '') {
        return first;
      }
    }
    return body?.message ?? error.message;
  }

  return error instanceof Error ? error.message : fallback;
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set('Accept', 'application/json');
  if (!headers.has('Content-Type') && init.body) {
    headers.set('Content-Type', 'application/json');
  }
  if (authToken) {
    headers.set('Authorization', `Bearer ${authToken}`);
  }

  const response = await fetch(`${API_URL}${path}`, { ...init, headers });
  const json = (await response.json().catch(() => ({}))) as {
    message?: string;
    data?: unknown;
  };

  if (!response.ok) {
    throw new ApiError(json.message ?? `HTTP ${response.status}`, response.status, json);
  }

  return json as T;
}

export type User = {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone_number?: string | null;
};

export type Product = {
  id: number;
  name: string;
  slug: string;
  thumbnail?: string | null;
  images?: { id?: number; url?: string }[];
  brand?: { id: number; name: string; slug: string } | null;
  price?: number | null;
  compare_price?: number | null;
  currency?: string;
  description?: string | null;
  variants?: { id: number; sku?: string | null; name?: string | null; price?: number | null }[];
};

export type Collection = {
  id: number;
  name: string;
  slug: string;
  thumbnail?: string | null;
  products_count?: number;
};

export type Cart = {
  id: number;
  currency: string;
  coupon_code?: string | null;
  lines: {
    id: number;
    quantity: number;
    unit_price: number;
    name: string;
    thumbnail?: string | null;
    purchasable_id: number;
  }[];
  totals: { subtotal: number; discount: number; tax: number; total: number };
};

export type PaymentInstructions = {
  payment_id?: string;
  payment_type?: string;
  virtual_account_number?: string | null;
  bank_code?: string | null;
  qris_string?: string | null;
  payment_url?: string | null;
  amount?: number;
  expiry_date?: string | null;
};

export type ShippingRate = {
  service_code: string;
  service_name: string;
  amount: number;
  currency?: string;
  carrier_code?: string;
  carrier_name?: string | null;
  estimated_days?: string | number | null;
};

export type PaymentMethodOption = {
  id: number;
  title: string;
  slug?: string;
  driver: string;
  description?: string | null;
  payment_type?: string | null;
  channel_code?: string | null;
};

export type SavedAddress = {
  id: number;
  first_name: string;
  last_name: string;
  street_address: string;
  city: string;
  postal_code: string;
  state?: string | null;
  phone_number?: string | null;
  rajaongkir_destination_id?: string | null;
  rajaongkir_destination_label?: string | null;
  shipping_default?: boolean;
};

export type Destination = {
  id: string;
  label: string;
  province_name?: string | null;
  city_name?: string | null;
  zip_code?: string | null;
};

export type CheckoutPayload = {
  cart: Cart;
  shipping_address: {
    first_name?: string;
    last_name?: string;
    street_address?: string;
    city?: string;
    postal_code?: string;
    phone_number?: string;
    rajaongkir_destination_id?: string;
    rajaongkir_destination_label?: string;
  } | null;
  shipping_option: {
    service_code?: string;
    service_name?: string;
    amount?: number;
    carrier_name?: string | null;
  } | null;
  shipping_rates: ShippingRate[];
  payment_methods: PaymentMethodOption[];
  saved_addresses: SavedAddress[];
};

export type OrderSummary = {
  id: number;
  number: string;
  status: string;
  payment_status: string;
  shipping_status?: string;
  amount: number;
  currency?: string;
  created_at?: string | null;
};

export type OrderDetail = OrderSummary & {
  items: { name: string; sku?: string | null; quantity: number; unit_price: number }[];
  shipments: {
    id: number;
    status: string;
    awb?: string | null;
    tracking_number?: string | null;
    carrier?: string | null;
    service?: string | null;
    cost?: number | null;
    tracking_history?: { description?: string; date?: string }[];
  }[];
  payment?: PaymentInstructions | null;
  can_retry_payment?: boolean;
};
