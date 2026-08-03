<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\InteractsWithStorefrontMedia;
use Shopper\Models\Collection as Model;

final class Collection extends Model
{
    use InteractsWithStorefrontMedia;
}
