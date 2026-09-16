<?php

declare(strict_types=1);

/**
 * App-added RajaOngkir location keys. Merged onto vendor Shopper English
 * strings so English admin locale does not render raw shopper:: keys.
 * Copy matches lang/vendor/shopper/id/pages/settings/global.php.
 */
return [

    'location' => [
        'rajaongkir_origin' => 'Origin RajaOngkir',
        'rajaongkir_origin_summary' => 'ID destinasi yang dipakai sebagai origin gudang saat menghitung ongkir RajaOngkir Cost dan membuat order Komerce Delivery. Cost hanya butuh ID origin. Resi Komerce membutuhkan pinpoint gudang (latitude + longitude); jangan simpan origin tanpa pin jika gudang ini menerbitkan AWB. Kosongkan ID untuk diisi otomatis dari alamat lokasi.',
        'rajaongkir_origin_id' => 'ID origin RajaOngkir',
        'rajaongkir_origin_helper' => 'ID subdistrict dari pencarian destinasi RajaOngkir. Wajib agar checkout menampilkan tarif dari gudang ini.',
        'rajaongkir_latitude' => 'Latitude gudang',
        'rajaongkir_longitude' => 'Longitude gudang',
        'rajaongkir_pin_point_helper' => 'Koordinat pinpoint gudang (contoh: -6.7366). Wajib bersama longitude agar Komerce Delivery dapat menerbitkan resi (AWB).',
    ],

];
