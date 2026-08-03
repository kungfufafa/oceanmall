<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class OrderShipmentLine extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<OrderShipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class, 'order_shipment_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }
}
