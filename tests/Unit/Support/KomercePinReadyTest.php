<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\KomercePinReady;
use Shopper\Core\Models\Inventory;
use Tests\TestCase;

final class KomercePinReadyTest extends TestCase
{
    public function test_message_is_null_when_both_pins_are_ready(): void
    {
        $this->assertNull(resolve(KomercePinReady::class)->message(true, true));
    }

    public function test_message_names_only_the_missing_origin_pin(): void
    {
        $this->assertSame(
            'Resi Komerce membutuhkan pinpoint gudang dan tujuan. Pinpoint gudang belum diisi.',
            resolve(KomercePinReady::class)->message(false, true),
        );
    }

    public function test_message_names_both_missing_pins(): void
    {
        $this->assertSame(
            'Resi Komerce membutuhkan pinpoint gudang dan tujuan. Pinpoint gudang belum diisi. Pinpoint tujuan belum diisi.',
            resolve(KomercePinReady::class)->message(false, false),
        );
    }

    public function test_inventory_without_coordinates_is_not_pin_ready(): void
    {
        $inventory = new Inventory;
        $inventory->forceFill([
            'rajaongkir_origin_id' => '17248',
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->assertFalse(resolve(KomercePinReady::class)->inventoryHasPinPoint($inventory));
    }

    public function test_address_pin_point_string_counts_as_ready(): void
    {
        $this->assertTrue(resolve(KomercePinReady::class)->addressHasDestinationPin([
            'rajaongkir_pin_point' => '-6.2380,106.7830',
        ]));
        $this->assertFalse(resolve(KomercePinReady::class)->addressHasDestinationPin([
            'rajaongkir_destination_id' => '152',
        ]));
    }

    public function test_place_order_allows_missing_destination_pin_when_delivery_is_disabled(): void
    {
        config()->set('komerce.shipping_delivery_api_key', '');

        $this->assertNull(resolve(KomercePinReady::class)->placeOrderDestinationError([
            'rajaongkir_destination_id' => '152',
        ]));
    }

    public function test_place_order_blocks_missing_destination_pin_when_delivery_is_enabled(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-delivery-key');

        $this->assertSame(
            'Resi Komerce membutuhkan pinpoint gudang dan tujuan. Pinpoint tujuan belum diisi.',
            resolve(KomercePinReady::class)->placeOrderDestinationError([
                'rajaongkir_destination_id' => '152',
            ]),
        );
        $this->assertNull(resolve(KomercePinReady::class)->placeOrderDestinationError([
            'rajaongkir_pin_point' => '-6.2380,106.7830',
        ]));
    }
}
