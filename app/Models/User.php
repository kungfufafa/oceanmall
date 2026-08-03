<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Shopper\Models\Contracts\ShopperUser;
use Shopper\Traits\InteractsWithShopper;

#[Unguarded]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'store_two_factor_secret',
    'store_two_factor_recovery_codes',
])]
final class User extends Authenticatable implements ShopperUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use InteractsWithShopper;
    use Notifiable;
    use TwoFactorAuthenticatable;

    public function initials(): string
    {
        return Str::of($this->full_name ?? $this->name ?? '')
            ->explode(' ')
            ->take(2)
            ->map(fn ($word): string => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
