import { API_URL } from '@/lib/config';

export function formatIdr(amount: number | null | undefined): string {
  const value = typeof amount === 'number' ? amount : 0;

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value);
}

export function mediaUrl(url?: string | null): string | undefined {
  if (!url) {
    return undefined;
  }
  if (/^https?:\/\//i.test(url)) {
    return url;
  }

  const origin = API_URL.replace(/\/api\/v1\/?$/, '');
  return `${origin}${url.startsWith('/') ? '' : '/'}${url}`;
}

export function stripHtml(value?: string | null): string {
  if (!value) {
    return '';
  }

  return value.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}
