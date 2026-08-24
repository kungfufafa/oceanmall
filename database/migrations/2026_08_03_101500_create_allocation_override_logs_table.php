<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_override_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained(shopper_table('orders'))->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('from_inventory_id')->constrained(shopper_table('inventories'));
            $table->foreignId('to_inventory_id')->constrained(shopper_table('inventories'));
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_override_logs');
    }
};
