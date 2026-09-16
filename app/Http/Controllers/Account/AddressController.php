<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Checkout\PersistUserShippingAddress;
use App\Actions\GetCountriesByZone;
use App\Actions\ZoneSessionManager;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AddressRajaOngkirMetadata;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Address;
use Shopper\Core\Models\Country;

final class AddressController extends Controller
{
    public function index(): Response
    {
        $currentZoneId = ZoneSessionManager::getSession()?->zoneId;

        $allowedCountryIds = resolve(GetCountriesByZone::class)
            ->handle()
            ->when($currentZoneId, fn ($items) => $items->where('zoneId', $currentZoneId))
            ->pluck('countryId');

        /** @var User $user */
        $user = auth()->user();

        return Inertia::render('account/addresses', [
            'addresses' => resolve(PersistUserShippingAddress::class)
                ->mapSavedAddressesForCheckout($user),
            'countries' => Country::query()
                ->when($allowedCountryIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $allowedCountryIds))
                ->orderBy('name')
                ->get(['id', 'name', 'cca2']),
            'komerceEnabled' => komerce_shipping_cost_enabled(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAddress($request);

        auth()->user()->addresses()->create($this->withRajaOngkirMetadata($request, $data));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Alamat berhasil ditambahkan.',
        ]);

        return redirect()->route('account.addresses');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $data = $this->validateAddress($request);

        $address->update($this->withRajaOngkirMetadata($request, $data, $address));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Alamat berhasil diperbarui.',
        ]);

        return redirect()->route('account.addresses');
    }

    public function destroy(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $address->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Alamat dihapus.',
        ]);

        return redirect()->route('account.addresses');
    }

    public function setDefaultShipping(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $this->applyDefault($address, 'shipping_default');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Alamat pengiriman utama diperbarui.',
        ]);

        return redirect()->route('account.addresses');
    }

    public function setDefaultBilling(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $this->applyDefault($address, 'billing_default');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Alamat penagihan utama diperbarui.',
        ]);

        return redirect()->route('account.addresses');
    }

    private function applyDefault(Address $address, string $column): void
    {
        auth()->user()->addresses()
            ->where($column, true)
            ->update([$column => false]);

        $address->update([$column => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAddress(Request $request): array
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

        unset(
            $data['rajaongkir_destination_id'],
            $data['rajaongkir_destination_label'],
            $data['rajaongkir_pin_point'],
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withRajaOngkirMetadata(Request $request, array $data, ?Address $existing = null): array
    {
        $posted = AddressRajaOngkirMetadata::posted(
            $request->all(),
            static fn (string $key): bool => $request->exists($key),
        );
        $metadata = AddressRajaOngkirMetadata::merge($existing?->metadata, $posted);
        $data['metadata'] = $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR);

        return $data;
    }
}
