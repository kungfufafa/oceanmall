<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(shopper_table('inventories'), function (Blueprint $table): void {
            if (! Schema::hasColumn(shopper_table('inventories'), 'rajaongkir_origin_id')) {
                $table->string('rajaongkir_origin_id')->nullable()->after('is_default');
            }
        });
    }

    public function down(): void
    {
        Schema::table(shopper_table('inventories'), function (Blueprint $table): void {
            if (Schema::hasColumn(shopper_table('inventories'), 'rajaongkir_origin_id')) {
                $table->dropColumn('rajaongkir_origin_id');
            }
        });
    }
};
