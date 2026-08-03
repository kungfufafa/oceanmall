<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\ZoneSessionManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ZoneController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
        ]);

        ZoneSessionManager::setSessionForCountryCode($data['country_code']);

        return back();
    }
}
