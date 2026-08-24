<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained(shopper_table('orders'))->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained(shopper_table('inventories'));
            $table->string('carrier_code')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('service_code')->nullable();
            $table->string('service_name')->nullable();
            $table->unsignedInteger('cost')->default(0);
            $table->string('currency_code', 3)->default('IDR');
            $table->string('status', 32)->default('pending');
            $table->string('awb')->nullable();
            $table->string('tracking_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'inventory_id']);
            $table->index('status');
        });

        Schema::create('order_shipment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_shipment_id')->constrained('order_shipments')->cascadeOnDelete();
            $table->morphs('purchasable');
            $table->unsignedInteger('qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipment_lines');
        Schema::dropIfExists('order_shipments');
    }
};
