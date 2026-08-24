<?php

declare(strict_types=1);

namespace App\Support;

use App\Actions\GetCountriesByZone;
use App\Models\Channel;
use App\Models\User;
use Shopper\Cart\Models\Cart;

/**
 * Resolve the open Shopper cart for a mobile customer without PHP session.
 */
final class CustomerCart
{
    public function current(User $user): Cart
    {
        $existing = Cart::query()
            ->where('customer_id', $user->id)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();

        if ($existing instanceof Cart) {
            return $existing;
        }

        $countries = resolve(GetCountriesByZone::class)->handle();
        $zone = $countries->firstWhere('countryCode', 'ID') ?? $countries->first();
        $defaultChannel = Channel::query()->scopes('default')->first();

        return Cart::query()->create([
            'currency_code' => shopper_currency(),
            'channel_id' => $defaultChannel?->id,
            'zone_id' => $zone?->zoneId,
            'customer_id' => $user->id,
        ]);
    }
}
