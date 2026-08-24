<?php

declare(strict_types=1);

return [

    'menu' => 'Pengaturan',
    'single' => 'pengaturan',

    'empty_country_selector' => 'Silakan pilih negara',
    'logo_description' => 'Logo toko Anda yang akan terlihat di situs Anda. Aset ini akan muncul di faktur Anda.',
    'confirm_password_content' => 'Demi keamanan Anda, silakan konfirmasi kata sandi Anda untuk melanjutkan.',

    'general' => [
        'title' => 'Pengaturan Toko',
        'store_details' => 'Detail toko',
        'store_detail_summary' => 'Pelanggan Anda akan menggunakan informasi ini untuk menghubungi Anda.',
        'email_helper' => 'Pelanggan Anda akan menggunakan alamat ini jika mereka perlu menghubungi Anda.',
        'phone_number_helper' => 'Pelanggan Anda akan menggunakan nomor telepon ini jika mereka perlu menelpon Anda secara langsung.',
        'assets' => 'Aset',
        'assets_summary' => 'Logo dan gambar sampul toko Anda yang akan terlihat di situs Anda. Aset ini akan muncul di faktur Anda.',
        'store_address' => 'Alamat toko',
        'store_address_summary' => 'Alamat ini akan muncul di faktur Anda. Anda dapat mengedit alamat yang digunakan.',
        'store_currency' => 'Mata uang toko',
        'social_links' => 'Tautan sosial',
        'social_links_summary' => 'Informasi tentang berbagai akun Anda di jejaring sosial. Pengguna akan dapat menghubungi Anda secara langsung di halaman resmi Anda.',
    ],

    'location' => [
        'menu' => 'Lokasi',
        'single' => 'lokasi',
        'description' => 'Kelola tempat Anda menyimpan stok inventaris, memproses pesanan, dan menjual produk.',
        'count' => 'Anda memiliki :count lokasi yang dikonfigurasi.',
        'add' => 'Tambah lokasi',
        'detail' => 'Detail',
        'detail_summary' => 'Berikan nama singkat untuk lokasi ini agar mudah diidentifikasi. Anda akan melihat nama ini di area seperti produk.',
        'address' => 'Alamat lokasi',
        'address_summary' => 'Informasi lengkap lokasi Anda. Harap berikan informasi yang valid agar dapat diakses oleh pelanggan Anda.',
        'set_default' => 'Tetapkan sebagai lokasi default',
        'set_default_summary' => 'Inventaris di lokasi ini tersedia untuk dijual secara online dan akan digunakan sebagai default',
        'priority_summary' => 'Nilai yang lebih rendah dipenuhi terlebih dahulu saat mengalokasikan stok di beberapa lokasi.',
        'is_default' => 'Ini adalah lokasi default Anda. Untuk mengubah apakah Anda memproses pesanan online dari lokasi ini, pilih lokasi default lain terlebih dahulu.',
        'rajaongkir_origin' => 'Origin RajaOngkir',
        'rajaongkir_origin_summary' => 'ID destinasi yang dipakai sebagai origin gudang saat menghitung ongkir RajaOngkir Cost dan membuat order Komerce Delivery. Kosongkan untuk diisi otomatis dari alamat lokasi.',
        'rajaongkir_origin_id' => 'ID origin RajaOngkir',
        'rajaongkir_origin_helper' => 'ID subdistrict dari pencarian destinasi RajaOngkir. Wajib agar checkout menampilkan tarif dari gudang ini.',
    ],

    'analytics' => [
        'google' => 'Google Analytics',
        'google_description' => 'Google Analytics memungkinkan Anda melacak pengunjung situs Anda dan menghasilkan laporan yang akan membantu pemasaran Anda.',
        'gtag' => 'Google Tag Manager',
        'gtag_description' => 'Google Tag Manager memungkinkan pengelola pemasaran menambahkan tag (Analytics, remarketing, dll.) dengan mudah',
        'pixel' => 'Facebook Pixel',
        'pixel_description' => 'Facebook Pixel membantu Anda membuat kampanye iklan untuk menemukan pelanggan baru yang paling mirip dengan pembeli Anda.',
        'no_json' => 'Tidak ada file json yang ditambahkan',
    ],

    'legal' => [
        'title' => 'Kebijakan hukum',
        'refund' => 'Kebijakan pengembalian dana',
        'privacy' => 'Kebijakan privasi',
        'shipping' => 'Kebijakan pengiriman',
        'terms_of_use' => 'Syarat penggunaan',
        'summary' => 'Tentukan :policy yang akan mengikat semua pengguna dan konsumen produk di toko Anda.',
    ],
];
