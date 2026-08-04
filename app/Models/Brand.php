<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\InteractsWithStorefrontMedia;
use Shopper\Models\Brand as Model;

final class Brand extends Model
{
    use InteractsWithStorefrontMedia;
}
