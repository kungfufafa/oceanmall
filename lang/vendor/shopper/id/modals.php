<?php

declare(strict_types=1);

return [

    'permissions' => [
        'new' => 'Izin baru',
        'new_description' => 'Tambah izin baru dan langsung tetapkan ke peran ini',
        'labels' => [
            'name' => 'Nama izin (dalam huruf kecil)',
        ],
    ],

    'roles' => [
        'new' => 'Tambah peran baru',
        'new_description' => 'Tambah peran baru dan tetapkan izin untuk administrator.',
        'labels' => [
            'name' => 'Nama (dalam huruf kecil)',
        ],
        'confirm_delete_msg' => 'Apakah Anda yakin ingin menghapus peran ini? Semua pengguna yang memiliki peran ini tidak lagi dapat mengakses tindakan yang diberikan oleh peran ini',
    ],

    'attributes' => [
        'new_value' => 'Tambah nilai baru untuk :attribute',
        'key_description' => 'Kunci akan digunakan untuk nilai dalam penyimpanan formulir (opsi, radio, dll.). Harus dalam format slug',
        'update_value' => 'Perbarui nilai untuk :name',
    ],

    'inventories' => [
        'confirm_delete_msg' => 'Apakah Anda yakin ingin menghapus inventaris ini? Semua data ini akan dihapus. Tindakan ini tidak dapat dibatalkan',
    ],

];
