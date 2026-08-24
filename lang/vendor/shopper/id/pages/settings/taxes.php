<?php

declare(strict_types=1);

return [

    'title' => 'Pajak',
    'single' => 'Zona Pajak',
    'description' => 'Kelola zona pajak, tarif, dan perilaku pajak untuk toko Anda.',
    'add_action' => 'Tambah zona pajak',
    'empty_heading' => 'Tidak ada zona pajak',
    'empty_detail_heading' => 'Tidak ada zona pajak yang dipilih',
    'empty_detail_description' => 'Pilih zona pajak untuk melihat detail dan tarifnya.',
    'inclusive' => 'Termasuk pajak',
    'exclusive' => 'Tidak termasuk pajak',
    'inclusive_help' => 'Aktifkan untuk harga inklusif gaya PPN (misalnya Eropa, Afrika).',
    'tax_behavior' => 'Perilaku pajak',
    'provider' => 'Penyedia pajak',
    'system_default' => 'Sistem (default)',
    'province_code' => 'Kode Provinsi / Negara Bagian',
    'province_code_help' => 'Kode subdivisi ISO 3166-2 (misalnya US-CA, FR-IDF, GB-ENG).',
    'name_help' => 'Nama tampilan opsional untuk zona ini (misalnya California, Île-de-France).',

    'rates' => [
        'title' => 'Tarif Pajak',
        'add' => 'Tambah Tarif',
        'add_heading' => 'Tarif pajak untuk :name',
        'update' => 'Perbarui :name',
        'rate' => 'Tarif',
        'empty_heading' => 'Tidak ada tarif yang dikonfigurasi',
        'default_help' => 'Gunakan tarif ini jika tidak ada penimpaan khusus produk yang berlaku.',
        'combinable' => 'Dapat digabungkan',
        'combinable_help' => 'Izinkan tarif ini untuk ditumpuk dengan tarif zona induk.',
    ],

    'overrides' => [
        'add' => 'Buat Penimpaan',
        'add_heading' => 'Tarif penimpaan untuk :name',
        'update' => 'Perbarui penimpaan :name',
        'description' => 'Penimpaan menerapkan tarif pajak yang berbeda pada produk, tipe produk, atau kategori tertentu.',
        'targets' => 'Target',
        'targets_help' => 'Pilih produk, tipe produk, atau kategori mana yang menerapkan penimpaan ini.',
        'target_type' => 'Tipe target',
        'target_value' => 'Nilai target',
        'add_target' => 'Tambah target',
        'product_types' => 'Tipe produk',
        'products' => 'Produk',
        'categories' => 'Kategori',
    ],

];
