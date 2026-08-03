import { loadStripe, type Stripe } from '@stripe/stripe-js';

/**
 * Lazy Stripe.js loader.
 *
 * Caches the Stripe instance per publishable key so multiple
 * components on the same page reuse a single Stripe object.
 */
const cache = new Map<string, Promise<Stripe | null>>();

export function stripe(publishableKey: string): Promise<Stripe | null> {
  if (!cache.has(publishableKey)) {
    cache.set(publishableKey, loadStripe(publishableKey));
  }
  return cache.get(publishableKey)!;
}
