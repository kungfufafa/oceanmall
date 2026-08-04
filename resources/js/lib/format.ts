import { isNoDivisionCurrency } from '@shopperlabs/shopper-types';

export { isNoDivisionCurrency };

export function formatMoney(
  amount: number,
  currency: string,
  locale = 'id-ID',
): string {
  const value = isNoDivisionCurrency(currency) ? amount : amount / 100;

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    maximumFractionDigits: isNoDivisionCurrency(currency) ? 0 : undefined,
  }).format(value);
}

export function formatPercentage(value: number, locale = 'id-ID'): string {
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
