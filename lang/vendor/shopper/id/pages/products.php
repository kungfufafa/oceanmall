<?php

declare(strict_types=1);

return [

    'menu' => 'Produk',
    'single' => 'produk',
    'title' => 'Kelola Katalog',
    'content' => 'Lebih dekat dengan penjualan pertama Anda dengan menambah dan mengelola produk.',
    'about_pricing' => 'Tentang tampilan harga',
    'about_pricing_content' => 'Semua harga dalam sen secara default. Untuk menyimpan 10€ (atau 10$) Anda harus memasukkan 1000 sen agar pemformatan mata uang menjadi benar.',

    'amount_price_help_text' => 'Harga beli, sebelum diskon.',
    'compare_price_help_text' => 'Harga jual yang disarankan, untuk perbandingan dengan harga beli. Harga ini lebih sering lebih tinggi',
    'cost_per_items_help_text' => 'Harga manufaktur asli. Pelanggan tidak akan melihatnya',
    'safety_security_help_text' => 'Stok pengaman adalah batas stok untuk produk Anda yang mengingatkan Anda jika stok produk akan segera habis.',
    'quantity_inventory' => 'Kuantitas Inventaris',
    'manage_inventories' => 'Kelola Inventaris',
    'inventory_name' => 'Nama inventaris',
    'product_can_returned' => 'Produk ini dapat dikembalikan',
    'product_can_returned_help_text' => 'Pengguna memiliki opsi untuk mengembalikan produk ini jika ada masalah atau ketidakpuasan.',
    'product_shipped' => 'Produk ini akan dikirim',
    'product_shipped_help_text' => 'Pastikan untuk mengisi informasi mengenai pengiriman produk.',
    'general' => 'Informasi produk',
    'status' => 'Ketersediaan produk',
    'featured_help_text' => 'Produk ini akan ditandai sebagai produk unggulan.',
    'visible_help_text' => 'Produk ini akan disembunyikan dari semua saluran penjualan.',
    'availability_description' => 'Tentukan tanggal publikasi agar produk Anda dijadwalkan di toko Anda.',
    'type' => 'Tipe produk',
    'product_type' => 'Tetapkan sebagai tipe produk default',
    'product_type_helpText' => 'Konfigurasi ini akan disimpan untuk produk berikutnya yang Anda buat.',
    'product_associations' => 'Asosiasi',
    'related_products' => 'Produk Terkait',
    'quantity_available' => 'Kuantitas Tersedia',
    'current_qty_inventory' => 'Kuantitas saat ini di inventaris ini',
    'stock_inventory_heading' => 'Stok & Inventaris',
    'stock_inventory_description' => 'Konfigurasikan inventaris dan stok untuk :item ini',
    'files_helpText' => 'Tambahkan file yang dapat diunduh dengan pembelian produk ini.',
    'images_helpText' => 'Tambahkan gambar ke produk Anda.',
    'variant_images_helpText' => 'Tambahkan gambar ke varian Anda.',
    'thumbnail_helpText' => 'Digunakan untuk mewakili produk Anda selama checkout, berbagi sosial, dan lainnya.',
    'weight_dimension' => 'Berat dan Dimensi',
    'weight_dimension_help_text' => 'Digunakan untuk menghitung biaya pengiriman saat checkout dan untuk memberi label harga selama pemrosesan pesanan.',
    'external_id_description' => 'Pengidentifikasi asli produk Anda dari pemasok eksternal',
    'allow_backorder' => 'Izinkan backorder',

    'modals' => [
        'title' => 'Hapus :item ini',
        'message' => 'Apakah Anda yakin ingin menghapus produk ini? Semua informasi yang terkait dengan produk ini akan dihapus.',

        'variants' => [
            'title' => 'Manajemen stok untuk varian ini',
            'select' => 'Pilih inventaris',
            'add' => 'Tambah varian baru',
            'options' => [
                'title' => 'Atribut varian',
                'description' => 'Pilih opsi atribut untuk varian ini.',
            ],
        ],
    ],

    'variants' => [
        'menu' => 'Varian',
        'single' => 'varian',
        'title' => 'Variasi produk',
        'description' => 'Semua variasi produk Anda. Variasi masing-masing dapat memiliki stok dan harga sendiri.',
        'add' => 'Tambah varian',
        'generate' => 'Hasilkan varian',
        'generate_description' => 'Produk Anda dihasilkan sesuai dengan atribut yang telah Anda pilih',
        'variant_title' => 'Varian ~ :name',
        'empty' => 'Tidak ada varian ditemukan',
        'search_label' => 'Cari varian',
        'search_placeholder' => 'Cari varian produk',
        'variant_information' => 'Informasi varian',
    ],

    'reviews' => [
        'single' => 'ulasan',
        'title' => 'Ulasan pelanggan',
        'description' => 'Di sinilah Anda akan melihat ulasan pelanggan Anda dan penilaian yang diberikan pada produk Anda.',
        'view' => 'Ulasan untuk :product',
        'published' => 'Diterbitkan',
        'pending' => 'Tertunda',
        'approved' => 'Ulasan Disetujui',
        'is_recommended' => 'Ulasan Direkomendasikan',
        'approved_status' => 'Status disetujui',
        'approved_message' => 'Status persetujuan ulasan diperbarui!',

        'subtitle' => 'Ulasan untuk produk ini.',
        'reviewer' => 'Peninjau',
        'review' => 'Ulasan',
        'review_content' => 'Konten',
        'status' => 'Status',
        'rating' => 'Penilaian',
        'star' => 'bintang',
        'stars' => 'bintang',

        'modal' => [
            'title' => 'Hapus Ulasan',
            'description' => 'Apakah Anda yakin ingin menghapus ulasan ini? Ulasan ini tidak dapat dipulihkan lagi.',
            'success_message' => 'Ulasan berhasil dihapus!',
        ],
    ],

    'attributes' => [
        'title' => 'Atribut Produk',
        'description' => 'Semua atribut yang terkait dengan produk ini.',
        'choose' => 'Pilih atribut',
        'empty_title' => 'Tidak ada Atribut yang diaktifkan',
        'empty_values' => 'Atribut yang terkait dengan produk ini tercantum di sini.',

        'session' => [
            'delete' => 'Atribut dihapus',
            'delete_message' => 'Anda berhasil menghapus atribut ini dari produk!',
            'delete_value' => 'Nilai atribut dihapus',
            'delete_value_message' => 'Anda berhasil menghapus nilai atribut ini!',
            'added' => 'Atribut Ditambahkan',
            'added_message' => 'Anda berhasil menambahkan atribut ke produk ini!',
        ],
    ],

    'inventory' => [
        'title' => 'Atribut inventaris',
        'description' => 'Bidang yang terkait dengan manajemen stok di toko Anda.',
        'stock_title' => 'Manajemen stok',
        'stock_description' => 'Manajemen stok di berbagai inventaris Anda.',
        'empty' => 'Tidak ada penyesuaian yang dilakukan pada inventaris.',
        'movement' => 'Pergerakan Kuantitas',
        'initial' => 'Inventaris awal',
        'add' => 'Ditambahkan secara manual',
        'remove' => 'Dihapus secara manual',
    ],

    'shipping' => [
        'description' => 'Informasi produk tentang pengembalian produk atau menentukan apakah produk dapat dikirim ke pelanggan.',
        'package_dimension' => 'Dimensi paket',
        'package_dimension_description' => 'Kenakan biaya pengiriman tambahan berdasarkan dimensi paket yang dicakup di sini.',
    ],

    'related' => [
        'title' => 'Produk Serupa',
        'description' => 'Semua produk yang dapat diidentifikasi sebagai serupa atau pelengkap produk Anda.',
        'empty' => 'Tidak ada produk serupa ditemukan',
        'add_content' => 'Mulai dengan menambahkan produk terkait ke produk Anda.',

        'modal' => [
            'title' => 'Tambah Produk Serupa',
            'search' => 'Cari produk',
            'search_placeholder' => 'Cari produk berdasarkan nama',
            'action' => 'Tambahkan Produk yang Dipilih',
            'success_message' => 'Produk yang dipilih berhasil ditambahkan',
            'no_results' => 'Tidak ada produk ditemukan',
        ],
    ],

    'notifications' => [
        'files_update' => 'File produk diperbarui!',
        'media_update' => 'Media produk diperbarui!',
        'replicated' => 'Produk direplikasi!',
        'stock_update' => 'Stok produk berhasil diperbarui!',
        'seo_update' => 'SEO produk berhasil diperbarui!',
        'shipping_update' => 'Pengiriman produk berhasil diperbarui!',
        'variation_generate' => 'Varian produk berhasil disimpan',
        'variation_create' => 'Varian produk berhasil ditambahkan!',
        'variation_delete' => 'Varian telah berhasil dihapus!',
        'variation_update' => 'Varian berhasil diperbarui!',
        'related_added' => 'Produk berhasil ditambahkan ke produk terkait!',
        'remove_related' => 'Produk berhasil dihapus dari produk terkait!',
        'manage_pricing' => 'Harga produk Anda telah diperbarui!',
        'variant_already_exists' => 'Varian ini sudah ada!',
    ],

    'pricing' => [
        'title' => 'Penetapan harga produk',
        'description' => 'Berbagai harga yang terkait dengan produk Anda. Ini tergantung pada mata uang yang Anda miliki di toko Anda.',
        'add' => 'Tambahkan harga baru',
        'empty' => 'Tidak ada penetapan harga produk yang ditambahkan',
    ],

];
