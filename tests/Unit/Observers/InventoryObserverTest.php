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
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
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
            'code' => 'test-gudang-'.uniqid(),
            'street_address' => 'Jl. Tuparev No. 109F, Kertawinangun, Kedawung',
            'city' => 'Cirebon',
            'state' => 'Jawa Barat',
            'postal_code' => '45153',
            'rajaongkir_origin_id' => null,
        ]);

        $observer = new InventoryObserver;
        $observer->saving($inventory);

        $this->assertSame('17248', $inventory->rajaongkir_origin_id);
    }

    public function test_inventory_observer_does_not_guess_between_ambiguous_results(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'id' => '17248',
                        'label' => 'KELURAHAN SATU, CIREBON, 45153',
                        'province_name' => 'JAWA BARAT',
                        'city_name' => 'CIREBON',
                        'district_name' => 'KECAMATAN SATU',
                        'subdistrict_name' => 'KELURAHAN SATU',
                        'zip_code' => '45153',
                    ],
                    [
                        'id' => '17249',
                        'label' => 'KELURAHAN DUA, CIREBON, 45153',
                        'province_name' => 'JAWA BARAT',
                        'city_name' => 'CIREBON',
                        'district_name' => 'KECAMATAN DUA',
                        'subdistrict_name' => 'KELURAHAN DUA',
                        'zip_code' => '45153',
                    ],
                ],
            ]),
        ]);

        $inventory = new Inventory([
            'street_address' => 'Jl. Tanpa Kelurahan',
            'city' => 'Cirebon',
            'state' => 'Jawa Barat',
            'postal_code' => '45153',
            'rajaongkir_origin_id' => null,
        ]);

        (new InventoryObserver)->saving($inventory);

        $this->assertNull($inventory->rajaongkir_origin_id);
    }

    public function test_delivery_key_does_not_enable_inventory_destination_lookup(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'delivery-key');
        Http::fake();

        $inventory = new Inventory([
            'street_address' => 'Jl. Tuparev',
            'city' => 'Cirebon',
            'postal_code' => '45153',
            'rajaongkir_origin_id' => null,
        ]);

        (new InventoryObserver)->saving($inventory);

        $this->assertNull($inventory->rajaongkir_origin_id);
        Http::assertNothingSent();
    }

    public function test_inventory_observer_rejects_a_unique_postcode_result_that_conflicts_with_city(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [[
                    'id' => '17248',
                    'label' => 'KERTAWINANGUN, CIREBON, 45153',
                    'province_name' => 'JAWA BARAT',
                    'city_name' => 'CIREBON',
                    'district_name' => 'KEDAWUNG',
                    'subdistrict_name' => 'KERTAWINANGUN',
                    'zip_code' => '45153',
                ]],
            ]),
        ]);

        $inventory = new Inventory([
            'street_address' => 'Jl. Merdeka',
            'city' => 'Bandung',
            'state' => 'Jawa Barat',
            'postal_code' => '45153',
            'rajaongkir_origin_id' => null,
        ]);

        (new InventoryObserver)->saving($inventory);

        $this->assertNull($inventory->rajaongkir_origin_id);
    }
}
