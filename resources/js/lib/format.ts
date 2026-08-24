import { isNoDivisionCurrency as shopperIsNoDivisionCurrency } from '@shopperlabs/shopper-types';

export function isNoDivisionCurrency(currency: string): boolean {
    if (!currency || currency.toUpperCase() === 'IDR') {
        return true;
    }

    return shopperIsNoDivisionCurrency(currency);
}

export function formatMoney(
    amount: number,
    currency: string,
    locale = 'id-ID',
): string {
    const value = isNoDivisionCurrency(currency) ? amount : amount / 100;

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency || 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatPercentage(value: number, locale = 'id-ID'): string {
    return new Intl.NumberFormat(locale, {
        style: 'percent',
        maximumFractionDigits: 0,
    }).format(value / 100);
}

export function stripHtml(html: string | null | undefined): string {
    if (!html) {
return '';
}

    return html
        .replace(/<[^>]*>/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}
