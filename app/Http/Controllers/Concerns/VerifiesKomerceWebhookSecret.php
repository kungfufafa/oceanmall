<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Support\KomerceCallbackSignature;
use Illuminate\Http\Request;

trait VerifiesKomerceWebhookSecret
{
    protected function hasValidKomerceWebhookSecret(Request $request): bool
    {
        $expected = (string) config('komerce.webhook_secret', '');
        $actual = (string) (
            $request->header('X-Callback-Api-Key')
            ?? $request->header('x-api-key')
            ?? $request->header('X-Komerce-Webhook-Secret')
            ?? ''
        );

        return KomerceCallbackSignature::isValid($request->getContent(), $expected, $actual);
    }
}
