<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Shopper\Core\Models\Review;

class ReviewSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private array $comments = [
        'Barangnya original, packing rapi, pengiriman cepat. Recommended.',
        'Kualitas oke sesuai harga. Seller responsif saat saya tanya spesifikasi.',
        'Sudah dipakai seminggu, berfungsi normal. Mantap.',
        'Pengiriman agak lama, tapi produknya bagus dan sesuai deskripsi.',
        'Baterai awet, layar jernih. Worth it buat daily use.',
        'Ada goresan kecil di dus, isinya aman. Overall puas.',
        'Sesuai foto. Charging cepat, tidak panas berlebihan.',
        'Harga kompetitif dibanding toko lain. Akan order lagi.',
        'CS membantu pilih varian. Terima kasih OceanMall.',
        'Produk resmi, ada garansi. Pengalaman belanja enak.',
        'Kurang cocok di tangan saya, tapi kualitasnya tetap bagus.',
        'Speaker kencang, koneksi stabil. Recommended buat kerja.',
        'Packing double bubble wrap. Aman sampai tujuan.',
        'Warna beda sedikit dari foto, spek sesuai. Oke lah.',
        'Proses checkout mudah, resi cepat keluar. Bintang 5.',
        'Kamera tajam, warna natural. Cocok buat konten harian.',
        'Fingerprint / face unlock responsif. Tidak lemot.',
        'Charger ikut dalam dus, full charge relatif cepat.',
        'Signal stabil di area saya. Streaming lancar.',
        'Overall puas. Bakal rekomendasikan ke teman.',
    ];

    public function run(): void
    {
        $products = Product::query()->pluck('id');
        $customers = User::query()->scopes('customers')->pluck('id');

        if ($products->isEmpty() || $customers->isEmpty()) {
            $this->command->warn('No products or customers found. Skipping reviews.');

            return;
        }

        $this->command->warn(PHP_EOL.'Creating reviews...');

        $created = 0;

        // Ensure every product has a solid review base (storefront doesn't look empty).
        foreach ($products as $productId) {
            $count = fake()->numberBetween(8, 18);

            for ($i = 0; $i < $count; $i++) {
                $rating = fake()->randomElement([3, 4, 4, 5, 5, 5]);

                Review::query()->create([
                    'rating' => $rating,
                    'content' => fake()->randomElement($this->comments),
                    'reviewrateable_id' => $productId,
                    'reviewrateable_type' => 'product',
                    'approved' => fake()->boolean(90),
                    'is_recommended' => $rating >= 4,
                    'author_id' => $customers->random(),
                    'author_type' => User::class,
                    'created_at' => fake()->dateTimeBetween('-90 days', 'now'),
                    'updated_at' => now(),
                ]);

                $created++;
            }
        }

        $this->command->info("Reviews created successfully ({$created}).");
    }
}
