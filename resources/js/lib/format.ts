import { isNoDivisionCurrency } from '@shopperlabs/shopper-types';

export { isNoDivisionCurrency };

export function formatMoney(
  amount: number,
  currency: string,
  locale = 'en-US',
): string {
  const value = isNoDivisionCurrency(currency) ? amount : amount / 100;

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
  }).format(value);
}

export function formatPercentage(value: number, locale = 'en-US'): string {
  return new Intl.NumberFormat(locale, {
    style: 'percent',
    maximumFractionDigits: 0,
  }).format(value / 100);
}

export function stripHtml(html: string | null | undefined): string {
  if (!html) return '';
  return html
    .replace(/<[^>]*>/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}
