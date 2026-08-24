<?php

declare(strict_types=1);

return [

    'menu' => 'Pelanggan',
    'single' => 'pelanggan',
    'title' => 'Kelola pesanan & detail pelanggan',
    'content' => 'Di sini Anda dapat mengelola informasi pelanggan Anda dan melihat riwayat pembelian mereka.',

    'overview' => 'Ikhtisar profil',
    'overview_description' => 'Gunakan alamat tetap tempat pelanggan dapat menerima surat.',
    'security_title' => 'Keamanan',
    'security_description' => 'Masukkan kata sandi acak yang akan digunakan pengguna ini untuk masuk ke akunnya.',
    'address_title' => 'Alamat',
    'address_description' => 'Alamat utama pelanggan ini. Alamat ini akan ditentukan sebagai alamat pengiriman default.',
    'notification_title' => 'Notifikasi',
    'notification_description' => 'Informasikan pelanggan Anda mengenai akun mereka.',
    'marketing_email' => 'Pelanggan menyetujui untuk menerima email pemasaran.',
    'marketing_description' => 'Anda harus meminta izin pelanggan sebelum mengikutkan mereka ke email pemasaran Anda jika ada.',
    'send_credentials' => 'Kirim kredensial pelanggan.',
    'credential_description' => 'Email akan dikirimkan ke pelanggan ini berisi kredensial login ini.',

    'period' => 'Pelanggan selama :period',

    'modal' => [
        'title' => 'Arsipkan pelanggan ini',
        'description' => 'Apakah Anda yakin ingin menonaktifkan pelanggan ini? Semua datanya (pesanan & alamat) akan dihapus secara permanen dari toko Anda selamanya. Tindakan ini tidak dapat dibatalkan.',
        'success_message' => 'Anda berhasil mengarsipkan pelanggan ini, tidak lagi tersedia di daftar pelanggan Anda.',
    ],

    'profile' => [
        'title' => 'Profil',
        'description' => 'Semua informasi publik pelanggan Anda dapat ditemukan di sini.',
        'account' => 'Akun',
        'account_description' => 'Kelola bagaimana informasi digunakan pada akun pelanggan.',
        'marketing' => 'Pemasaran Email',
        'two_factor' => 'Autentikasi Dua Faktor',
    ],

    'addresses' => [
        'title' => 'Alamat',
        'shipping' => 'Alamat Pengiriman',
        'billing' => 'Alamat Tagihan',
        'default' => 'Alamat default',
        'customer' => 'Alamat pelanggan',
        'empty_text' => 'Pelanggan ini belum memiliki alamat pengiriman atau tagihan.',
    ],

    'orders' => [
        'placed' => 'Pesanan Dibuat',
        'total' => 'Total',
        'ship_to' => 'Kirim Ke',
        'order_number' => 'Pesanan :number',
        'details' => 'Detail Pesanan',
        'items' => 'Item pesanan',
        'view' => 'Lihat pesanan',
        'empty_text' => 'Tidak ada pesanan ditemukan...',
        'no_shipping' => 'Tidak ada metode pengiriman',
        'estimated' => 'Tanggal pengiriman',
    ],

    'anonymize' => [
        'action' => 'Anonimkan pelanggan',
        'title' => 'Anonimkan pelanggan ini',
        'description' => 'Tindakan ini akan menganonimkan semua data pribadi untuk pelanggan ini secara permanen (nama, email, telepon, alamat). Riwayat pesanan akan dipertahankan untuk keperluan akuntansi. Tindakan ini tidak dapat dibatalkan.',
        'confirm' => 'Ya, anonimkan',
        'success' => 'Pelanggan telah berhasil dianonimkan.',
        'first_name' => 'Dihapus',
        'last_name' => 'Pelanggan',
    ],

];
