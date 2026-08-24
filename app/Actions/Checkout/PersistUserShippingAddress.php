<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Models\User;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Address;

final class PersistUserShippingAddress
{
    /**
     * Upsert the checkout shipping address into the customer's address book
     * so the next purchase can reuse street + RajaOngkir district.
     *
     * @param  array<string, mixed>  $shippingAddress
     */
    public function handle(User $user, array $shippingAddress): Address
    {
        $countryId = (int) ($shippingAddress['country_id'] ?? 0);
        $street = trim((string) ($shippingAddress['street_address'] ?? ''));
        $postal = trim((string) ($shippingAddress['postal_code'] ?? ''));
        $city = trim((string) ($shippingAddress['city'] ?? ''));

        $existing = $user->addresses()
            ->where('type', AddressType::Shipping)
            ->where('street_address', $street)
            ->where('postal_code', $postal)
            ->where('city', $city)
            ->when($countryId > 0, fn ($q) => $q->where('country_id', $countryId))
            ->first();

        $metadata = $this->mergeMetadata($existing, $shippingAddress);

        $attributes = [
            'first_name' => (string) ($shippingAddress['first_name'] ?? ''),
            'last_name' => (string) ($shippingAddress['last_name'] ?? ''),
            'street_address' => $street,
            'street_address_plus' => $shippingAddress['street_address_plus'] ?? null,
            'postal_code' => $postal,
            'city' => $city,
            'state' => $shippingAddress['state'] ?? null,
            'phone_number' => $shippingAddress['phone_number'] ?? null,
            'country_id' => $countryId > 0 ? $countryId : ($existing?->country_id ?? null),
            'type' => AddressType::Shipping,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ];

        if ($existing) {
            $existing->update($attributes);
            $address = $existing->fresh();
        } else {
            $address = $user->addresses()->create($attributes);
        }

        $this->makeShippingDefault($user, $address);

        return $address->fresh();
    }

    /**
     * Build checkout session shipping address from a saved address book row.
     *
     * @return array<string, mixed>|null
     */
    public function toCheckoutShippingAddress(Address $address): ?array
    {
        $metadata = $this->decodeMetadata($address->metadata);
        $destinationId = trim((string) ($metadata['rajaongkir_destination_id'] ?? ''));

        if ($destinationId === '' && komerce_shipping_cost_enabled()) {
            // District missing — still usable as form prefills, but not as a
            // complete checkout session address for rate quotes.
            return null;
        }

        return [
            'first_name' => (string) ($address->first_name ?? ''),
            'last_name' => (string) $address->last_name,
            'street_address' => (string) $address->street_address,
            'street_address_plus' => $address->street_address_plus,
            'postal_code' => (string) $address->postal_code,
            'city' => (string) $address->city,
            'state' => $address->state,
            'phone_number' => $address->phone_number,
            'country_id' => $address->country_id,
            'rajaongkir_destination_id' => $destinationId !== '' ? $destinationId : null,
            'rajaongkir_destination_label' => $metadata['rajaongkir_destination_label'] ?? null,
            'rajaongkir_pin_point' => $metadata['rajaongkir_pin_point'] ?? null,
            'saved_address_id' => $address->id,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function mapSavedAddressesForCheckout(User $user): array
    {
        return $user->addresses()
            ->with('country')
            ->orderByDesc('shipping_default')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Address $address): array {
                $metadata = $this->decodeMetadata($address->metadata);

                return [
                    'id' => $address->id,
                    'first_name' => $address->first_name,
                    'last_name' => $address->last_name,
                    'street_address' => $address->street_address,
                    'street_address_plus' => $address->street_address_plus,
                    'postal_code' => $address->postal_code,
                    'city' => $address->city,
                    'state' => $address->state,
                    'phone_number' => $address->phone_number,
                    'country_id' => $address->country_id,
                    'type' => $address->type,
                    'shipping_default' => $address->shipping_default,
                    'billing_default' => $address->billing_default,
                    'country' => $address->country
                        ? [
                            'id' => $address->country->id,
                            'name' => $address->country->name,
                            'cca2' => $address->country->cca2,
                        ]
                        : null,
                    'rajaongkir_destination_id' => $metadata['rajaongkir_destination_id'] ?? null,
                    'rajaongkir_destination_label' => $metadata['rajaongkir_destination_label'] ?? null,
                    'rajaongkir_pin_point' => $metadata['rajaongkir_pin_point'] ?? null,
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $shippingAddress
     * @return array<string, mixed>
     */
    private function mergeMetadata(?Address $existing, array $shippingAddress): array
    {
        $metadata = $existing ? $this->decodeMetadata($existing->metadata) : [];

        $destinationId = trim((string) ($shippingAddress['rajaongkir_destination_id'] ?? ''));
        $destinationLabel = trim((string) ($shippingAddress['rajaongkir_destination_label'] ?? ''));

        if ($destinationId !== '') {
            $metadata['rajaongkir_destination_id'] = $destinationId;
        }

        if ($destinationLabel !== '') {
            $metadata['rajaongkir_destination_label'] = $destinationLabel;
        }

        $pin = trim((string) ($shippingAddress['rajaongkir_pin_point'] ?? ''));
        if ($pin !== '') {
            $metadata['rajaongkir_pin_point'] = $pin;
        }

        return $metadata;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function makeShippingDefault(User $user, Address $address): void
    {
        $user->addresses()
            ->where('shipping_default', true)
            ->whereKeyNot($address->id)
            ->update(['shipping_default' => false]);

        if (! $address->shipping_default) {
            $address->update(['shipping_default' => true]);
        }
    }
}
