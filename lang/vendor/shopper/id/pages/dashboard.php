<?php

declare(strict_types=1);

return [

    'menu' => 'Dasbor',
    'welcome_message' => 'Selamat datang di Shopper',
    'welcome_description' => 'Berikut yang Anda perlukan untuk menyiapkan dan menjalankan toko Anda.',

    'cards' => [
        'doc_title' => 'Dokumentasi',
    ],

    'guide' => [
        'title' => 'Panduan penyiapan',
        'description' => 'Selesaikan langkah-langkah ini untuk mulai berjualan.',
        'progress' => 'dari :total selesai',
        'dismiss' => 'Tutup',
        'footer_hint' => 'Anda selalu dapat mengakses pengaturan ini di lain waktu.',

        'steps' => [
            'add_product' => [
                'title' => 'Tambahkan produk pertama Anda',
                'description' => 'Tambahkan produk dengan harga, gambar, dan varian untuk mulai membangun katalog Anda.',
                'action' => 'Tambahkan produk',
            ],
            'create_collection' => [
                'title' => 'Buat koleksi',
                'description' => 'Organisasikan produk Anda ke dalam koleksi untuk memudahkan pelanggan menjelajahi toko Anda.',
                'action' => 'Buat koleksi',
            ],
            'setup_zones' => [
                'title' => 'Atur zona pengiriman',
                'description' => 'Konfigurasikan zona pengiriman Anda untuk menentukan lokasi pengiriman dan biayanya.',
                'action' => 'Atur pengiriman',
            ],
            'setup_payments' => [
                'title' => 'Atur metode pembayaran',
                'description' => 'Tambahkan metode pembayaran agar pelanggan Anda dapat membayar pesanan mereka.',
                'action' => 'Atur pembayaran',
            ],
            'setup_taxes' => [
                'title' => 'Konfigurasi pajak',
                'description' => 'Atur zona dan tarif pajak untuk menghitung pajak pesanan secara otomatis.',
                'action' => 'Konfigurasi pajak',
            ],
        ],
    ],

    'stats' => [
        'revenue' => 'Total Pendapatan',
        'products' => 'Total Produk',
        'orders' => 'Total Pesanan',
        'customers' => 'Total Pelanggan',
        'vs_last_month' => 'dibandingkan bulan lalu',
        'view_more' => 'Lihat lebih banyak',
    ],

    'chart' => [
        'heading' => 'Performa',
        'series_label' => 'Pendapatan',
    ],

    'recent_orders' => [
        'heading' => 'Pesanan Terbaru',
        'view_all' => 'Lihat semua',
        'empty' => 'Belum ada pesanan.',
    ],

    'top_products' => [
        'heading' => 'Produk Terlaris',
        'view_all' => 'Lihat semua',
        'product' => 'Produk',
        'sales' => 'Penjualan',
        'reviews' => 'Ulasan',
        'empty' => 'Belum ada penjualan.',
    ],

    'addons' => [
        'title' => 'Kembangkan toko Anda',
        'badge' => 'Pengaya',
        'learn_more' => 'Pelajari lebih lanjut',
        'configure' => 'Konfigurasi kurir',

        'stripe' => [
            'title' => 'Stripe',
            'description' => 'Terima kartu kredit, Apple Pay, dan Google Pay dengan Stripe.',
        ],
        'carriers' => [
            'title' => 'Kurir pengiriman',
            'description' => 'Hubungkan UPS, FedEx, USPS, dan lainnya untuk tarif pengiriman langsung.',
        ],
    ],

];
