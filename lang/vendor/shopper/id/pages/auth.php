<?php

declare(strict_types=1);

return [

    'login' => [
        'title' => 'Masuk dengan email',
        'subtitle' => 'Masuk ke Panel Admin Shopper Anda',
        'or' => 'Atau',
        'return_landing' => 'Kembali ke halaman utama',
        'forgot_password' => 'Lupa kata sandi Anda?',
        'action' => 'Masuk',
        'failed' => 'Kredensial ini tidak cocok dengan catatan kami.',
        'throttled' => 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam :seconds detik.',
        'return_login' => 'Kembali ke halaman masuk',
    ],

    'reset' => [
        'title' => 'Atur ulang kata sandi',
        'message' => 'Masukkan email Anda dan kata sandi baru yang ingin Anda gunakan untuk mengakses akun Anda.',
        'action' => 'Perbarui kata sandi',
    ],

    'email' => [
        'title' => 'Atur ulang kata sandi Anda',
        'message' => 'Masukkan email Anda di bawah ini, dan kami akan mengirimkan instruksi tentang cara mengatur ulang kata sandi Anda.',
        'action' => 'Kirim email atur ulang kata sandi',
        'return_to_login' => 'Kembali ke halaman masuk',
        'mail' => [
            'subject' => 'Atur Ulang Kata Sandi',
            'content' => 'Anda menerima email ini karena kami menerima permintaan atur ulang kata sandi untuk akun Anda.',
            'action' => 'Atur ulang kata sandi',
            'message' => 'Jika Anda tidak meminta atur ulang kata sandi, tidak ada tindakan lanjutan yang diperlukan.',
        ],
    ],

    'two_factor' => [
        'title' => 'Masuk dengan Autentikasi Dua Faktor',
        'subtitle' => 'Autentikasi Akun Anda',
        'authentication_code' => 'Harap konfirmasi akses ke akun Anda dengan memasukkan kode autentikasi yang diberikan oleh aplikasi autentikator Anda.',
        'recovery_code' => 'Harap konfirmasi akses ke akun Anda dengan memasukkan salah satu kode pemulihan darurat Anda.',
        'remember' => 'Tidak ingat kode ini?',
        'use_recovery_code' => 'Gunakan kode pemulihan',
        'use_authentication_code' => 'Gunakan kode autentikasi',
        'action' => 'Masuk',
        'recovery_not_enabled' => 'Kode pemulihan tidak diaktifkan untuk akun ini.',
        'invalid_recovery_code' => 'Kode pemulihan dua faktor yang diberikan tidak valid.',
        'invalid_code' => 'Kode autentikasi dua faktor yang diberikan tidak valid.',
    ],

    'account' => [
        'meta_title' => 'Profil Akun',
        'title' => 'Profil saya',

        'device_title' => 'Perangkat',
        'device_description' => 'Saat ini Anda masuk di perangkat ini. Jika Anda tidak mengenali perangkat, keluar untuk menjaga keamanan akun Anda.',
        'empty_device' => 'Jika perlu, Anda dapat keluar dari semua sesi peramban lainnya di semua perangkat Anda.',
        'current_device' => 'Perangkat ini',
        'device_last_activity' => 'Aktif terakhir',
        'device_location' => 'Tidak dapat memulihkan lokasi ini.',
        'device_enabled_feature' => 'Driver sesi database diperlukan untuk mengaktifkan fitur ini.',

        'password_title' => 'Perbarui Kata Sandi',
        'password_description' => 'Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.',
        'password_helper_validation' => 'Kata sandi Anda harus lebih dari 8 karakter dan mengandung setidaknya 1 huruf besar, 1 huruf kecil, dan 1 angka.',

        'two_factor_title' => 'Autentikasi Dua Faktor',
        'two_factor_description' => 'Setelah memasukkan kata sandi, verifikasi identitas Anda dengan metode autentikasi kedua.',
        'two_factor_enabled' => 'Anda telah mengaktifkan autentikasi dua faktor.',
        'two_factor_disabled' => 'Anda belum mengaktifkan autentikasi dua faktor.',
        'two_factor_install_message' => 'Untuk menggunakan autentikasi dua faktor, Anda harus menginstal aplikasi Google Authenticator di ponsel pintar Anda.',
        'two_factor_secure' => 'Dengan autentikasi dua faktor, hanya Anda yang dapat mengakses akun Anda — bahkan jika orang lain memiliki kata sandi Anda.',
        'two_factor_activation_message' => 'Ketika autentikasi dua faktor diaktifkan, Anda akan diminta memasukkan token acak yang aman selama autentikasi. Anda dapat mengambil token ini dari aplikasi Google Authenticator di ponsel Anda.',
        'two_factor_is_enabled' => 'Autentikasi dua faktor sekarang diaktifkan. Pindai kode QR berikut menggunakan aplikasi autentikator di ponsel Anda.',
        'two_factor_store_recovery_codes' => 'Simpan kode pemulihan ini di pengelola kata sandi yang aman. Kode ini dapat digunakan untuk memulihkan akses ke akun Anda jika perangkat autentikasi dua faktor Anda hilang.',

        'profile_title' => 'Informasi Profil',
        'profile_description' => 'Perbarui informasi profil akun dan alamat email Anda.',
    ],

];
