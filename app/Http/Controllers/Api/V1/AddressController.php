<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Checkout\PersistUserShippingAddress;
use App\Actions\GetCountriesByZone;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Address;
use Shopper\Core\Models\Country;

final class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->customer($request);

        return response()->json([
            'data' => resolve(PersistUserShippingAddress::class)->mapSavedAddressesForCheckout($user),
            'countries' => Country::query()
                ->whereIn('id', resolve(GetCountriesByZone::class)->handle()->pluck('countryId'))
                ->orderBy('name')
                ->get(['id', 'name', 'cca2']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $data = $this->validated($request);
        $address = $user->addresses()->create($data);

        if ($request->boolean('shipping_default')) {
            $this->applyDefault($user, $address, 'shipping_default');
        }

        return response()->json(['data' => $this->payload($address->fresh())], 201);
    }

    public function update(Request $request, int $address): JsonResponse
    {
        $user = $this->customer($request);
        $row = $this->owned($user, $address);
        $row->update($this->validated($request));

        return response()->json(['data' => $this->payload($row->fresh())]);
    }

    public function destroy(Request $request, int $address): JsonResponse
    {
        $this->owned($this->customer($request), $address)->delete();

        return response()->json(['message' => 'Alamat dihapus.']);
    }

    public function setDefaultShipping(Request $request, int $address): JsonResponse
    {
        $user = $this->customer($request);
        $row = $this->owned($user, $address);
        $this->applyDefault($user, $row, 'shipping_default');

        return response()->json(['data' => $this->payload($row->fresh())]);
    }

    public function setDefaultBilling(Request $request, int $address): JsonResponse
    {
        $user = $this->customer($request);
        $row = $this->owned($user, $address);
        $this->applyDefault($user, $row, 'billing_default');

        return response()->json(['data' => $this->payload($row->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'street_address_plus' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'country_id' => ['required', 'integer', 'exists:'.(new Country)->getTable().',id'],
            'type' => ['required', Rule::enum(AddressType::class)],
            'rajaongkir_destination_id' => ['nullable', 'string', 'max:50'],
            'rajaongkir_destination_label' => ['nullable', 'string', 'max:255'],
            'rajaongkir_pin_point' => ['nullable', 'string', 'max:64'],
        ]);

        $metadata = array_filter([
            'rajaongkir_destination_id' => $data['rajaongkir_destination_id'] ?? null,
            'rajaongkir_destination_label' => $data['rajaongkir_destination_label'] ?? null,
            'rajaongkir_pin_point' => $data['rajaongkir_pin_point'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && $value !== '');

        unset($data['rajaongkir_destination_id'], $data['rajaongkir_destination_label'], $data['rajaongkir_pin_point']);
        $data['metadata'] = $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR);

        return $data;
    }

    private function owned(User $user, int $id): Address
    {
        return $user->addresses()->whereKey($id)->firstOrFail();
    }

    private function applyDefault(User $user, Address $address, string $column): void
    {
        $user->addresses()->where($column, true)->update([$column => false]);
        $address->update([$column => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Address $address): array
    {
        $owner = User::query()->find($address->user_id);
        if (! $owner instanceof User) {
            return ['id' => $address->id];
        }

        $list = resolve(PersistUserShippingAddress::class)->mapSavedAddressesForCheckout($owner);
        foreach ($list as $row) {
            if ((int) $row['id'] === (int) $address->id) {
                return $row;
            }
        }

        return ['id' => $address->id];
    }

    private function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
