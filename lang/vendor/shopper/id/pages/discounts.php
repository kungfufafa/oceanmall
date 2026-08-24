<?php

declare(strict_types=1);

return [

    'menu' => 'Diskon',
    'single' => 'diskon',
    'title' => 'Kelola diskon dan promosi',
    'description' => 'Buat & Kelola kode diskon dan promosi yang berlaku saat checkout atau pesanan pelanggan.',

    'empty_message' => 'Tidak ada diskon ditemukan...',
    'search' => 'Cari kode diskon',
    'name_helptext' => 'Pelanggan akan memasukkan kode diskon ini saat checkout.',
    'percentage' => 'Persentase',
    'percentage_description' => 'Diskon diterapkan dalam %',
    'fixed_amount' => 'Jumlah tetap',
    'fixed_amount_description' => 'Diskon dalam angka bulat',
    'configuration_description' => 'Kode diskon berlaku sejak Anda menekan tombol terbitkan, dan tetap aktif jika tidak diubah.',
    'condition_description' => 'Kode diskon berlaku untuk semua produk jika tidak diubah.',
    'applies_to' => 'Berlaku Untuk',
    'entire_order' => 'Seluruh pesanan',
    'specific_products' => 'Produk tertentu',
    'select_products' => 'Pilih produk',
    'min_requirement' => 'Persyaratan minimum',
    'none' => 'Tidak ada',
    'min_amount' => 'Jumlah pembelian minimum (:currency)',
    'min_value' => 'Nilai Minimum yang Diperlukan',
    'applies_only_selected' => 'Hanya berlaku untuk produk yang dipilih.',
    'min_quantity' => 'Jumlah item minimum',
    'customer_eligibility' => 'Kelayakan pelanggan',
    'everyone' => 'Semua orang',
    'specific_customers' => 'Pelanggan tertentu',
    'select_customers' => 'Pilih pelanggan',
    'usage_limits' => 'Batas penggunaan',
    'usage_label' => 'Batasi berapa kali diskon ini dapat digunakan secara total',
    'usage_label_description' => 'Batas ini berlaku untuk semua pelanggan, tidak secara individu.',
    'usage_value' => 'Nilai batas penggunaan',
    'limit_one_per_user' => 'Batasi satu kali penggunaan per pelanggan',
    'active_dates' => 'Tanggal aktif',
    'active_dates_description' => 'Tanggal di mana diskon akan tersedia untuk pengguna.',
    'start_date' => 'Tanggal mulai',
    'choose_start_date' => 'Pilih periode tanggal mulai',
    'end_date' => 'Tanggal berakhir',
    'choose_end_date' => 'Pilih tanggal berakhir',
    'empty_code' => 'Belum ada informasi yang dimasukkan.',
    'count_items' => ':count item',
    'min_purchase' => 'Pembelian minimum sebesar',

    'modals' => [
        'stock_available' => 'Tersedia :stock',
        'add_products' => 'Tambah Produk',
        'add_selected_products' => 'Tambahkan Produk yang Dipilih',
        'search_product' => 'Cari produk berdasarkan nama',

        'add_customers' => 'Tambah Pelanggan',
        'search_customer' => 'Cari pelanggan berdasarkan nama',
        'add_selected_customers' => 'Tambahkan Pelanggan yang Dipilih',

        'remove' => [
            'title' => 'Hapus kode ini',
            'description' => 'Apakah Anda yakin ingin menghapus kode ini? Semua data ini akan dihapus. Tindakan ini tidak dapat dibatalkan.',
            'success_message' => 'Kode diskon berhasil dihapus!',
        ],
    ],

    'active_today' => 'Aktif hari ini',
    'active_from_today' => 'Aktif mulai hari ini',
    'active_from' => 'Aktif mulai :date',
    'active_date' => 'Aktif :date',
    'active_from_to' => 'Aktif dari :start hingga :end',
    'one_per_customer' => 'satu per pelanggan',

    'save' => 'Kode diskon :code berhasil disimpan!',
    'total_use' => 'Penukaran',

];
