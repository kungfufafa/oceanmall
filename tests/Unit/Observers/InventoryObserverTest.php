<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Observers\InventoryObserver;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Models\Inventory;
use Tests\TestCase;

final class InventoryObserverTest extends TestCase
{
    public function test_inventory_observer_automatically_resolves_rajaongkir_origin_id(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'id' => '17248',
                        'label' => 'KERTAWINANGUN, KEDAWUNG, CIREBON, JAWA BARAT, 45153',
                        'province_name' => 'JAWA BARAT',
                        'city_name' => 'CIREBON',
                        'district_name' => 'KEDAWUNG',
                        'subdistrict_name' => 'KERTAWINANGUN',
                        'zip_code' => '45153',
                    ],
                ],
            ]),
        ]);

        $inventory = new Inventory([
            'name' => 'Test Gudang',
            'code' => 'test-gudang-' . uniqid(),
            'street_address' => 'Jl. Tuparev No. 109F, Kertawinangun, Kedawung',
            'city' => 'Cirebon',
            'state' => 'Jawa Barat',
            'postal_code' => '45153',
            'rajaongkir_origin_id' => null,
        ]);

        $observer = new InventoryObserver();
        $observer->saving($inventory);

        $this->assertSame('17248', $inventory->rajaongkir_origin_id);
    }
}
