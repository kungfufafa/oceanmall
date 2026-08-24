<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Per-user checkout draft for token-authenticated mobile clients.
 *
 * @phpstan-type CheckoutState array<string, mixed>
 */
final class CustomerCheckoutState
{
    private const TTL_SECONDS = 7200;

    /**
     * @return array<string, mixed>
     */
    public function get(User $user): array
    {
        $state = Cache::get($this->key($user));

        return is_array($state) ? $state : [];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function put(User $user, array $state): void
    {
        Cache::put($this->key($user), $state, now()->addSeconds(self::TTL_SECONDS));
    }

    public function forget(User $user): void
    {
        Cache::forget($this->key($user));
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function putShippingAddress(User $user, array $address): void
    {
        $state = $this->get($user);
        data_set($state, 'shipping_address', $address);
        unset($state['shipping_option'], $state['shipping_options_by_shipment'], $state['allocation_plan'], $state['payment']);
        $this->put($user, $state);
    }

    /**
     * @param  list<array<string, mixed>>  $option
     */
    public function putShippingOption(User $user, array $option): void
    {
        $state = $this->get($user);
        $state['shipping_option'] = $option;
        $this->put($user, $state);
    }

    /**
     * @param  array<string, mixed>  $method
     */
    public function putPayment(User $user, array $method): void
    {
        $state = $this->get($user);
        $state['payment'] = [$method];
        $this->put($user, $state);
    }

    public function asSessionShape(User $user): array
    {
        return $this->get($user);
    }

    private function key(User $user): string
    {
        return 'customer-checkout:'.$user->id;
    }
}
