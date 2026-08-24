<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;

final class OrderShipment extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * @return BelongsTo<Inventory, $this>
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * @return HasMany<OrderShipmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderShipmentLine::class);
    }

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'metadata' => 'array',
        ];
    }
}
