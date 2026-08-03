import type {
  Stripe,
  StripeElements,
  StripePaymentElement,
} from '@stripe/stripe-js';
import { onMounted, onUnmounted, ref } from 'vue';
import { stripe } from '@/lib/stripe';

type Options = {
  publishableKey: string;
  clientSecret: string;
  appearance?: 'stripe' | 'night' | 'flat' | 'auto';
};

/**
 * Mounts a Stripe PaymentElement into a target DOM node and
 * exposes a confirm helper.
 *
 * Returns refs for loading and error state so the page can
 * render the right UI.
 */
export function useStripeElements(
  options: Options,
  mountTarget: () => HTMLElement | null,
) {
  const ready = ref<boolean>(false);
  const submitting = ref<boolean>(false);
  const error = ref<string | null>(null);

  let stripeInstance: Stripe | null = null;
  let elements: StripeElements | null = null;
  let paymentElement: StripePaymentElement | null = null;

  onMounted(async () => {
    stripeInstance = await stripe(options.publishableKey);
    if (!stripeInstance) {
      error.value = 'Unable to load Stripe.';
      return;
    }

    elements = stripeInstance.elements({
      clientSecret: options.clientSecret,
      appearance: { theme: options.appearance ?? 'stripe' },
    });

    const node = mountTarget();
    if (!node) {
      error.value = 'Stripe mount target not found.';
      return;
    }

    paymentElement = elements.create('payment');
    paymentElement.mount(node);
    paymentElement.on('ready', () => (ready.value = true));
  });

  onUnmounted(() => {
    paymentElement?.unmount();
    paymentElement?.destroy();
  });

  async function confirm(returnUrl: string): Promise<void> {
    if (!stripeInstance || !elements) {
      error.value = 'Stripe is not ready.';
      return;
    }
    submitting.value = true;
    error.value = null;

    const result = await stripeInstance.confirmPayment({
      elements,
      confirmParams: { return_url: returnUrl },
    });

    submitting.value = false;

    if (result.error) {
      error.value = result.error.message ?? 'Payment failed.';
    }
  }

  return { ready, submitting, error, confirm };
}
