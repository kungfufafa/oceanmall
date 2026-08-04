<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Warehouse\OverrideAllocation;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Shopper\Core\Models\Order;

final class OverrideAllocationController extends Controller
{
    public function __invoke(Order $order, OverrideAllocation $overrideAllocation): Response|RedirectResponse
    {
        Gate::authorize('override-allocation', $order);

        $payload = request()->validate([
            'moves' => ['required', 'array', 'min:1'],
            'moves.*.shipment_line_id' => ['nullable', 'integer'],
            'moves.*.purchasable_type' => ['nullable', 'string'],
            'moves.*.purchasable_id' => ['nullable', 'integer'],
            'moves.*.qty' => ['required', 'integer', 'min:1'],
            'moves.*.from_inventory_id' => ['required', 'integer', 'exists:'.shopper_table('inventories').',id'],
            'moves.*.to_inventory_id' => ['required', 'integer', 'different:moves.*.from_inventory_id', 'exists:'.shopper_table('inventories').',id'],
        ]);

        $actor = auth()->user();

        if (! $actor instanceof User) {
            throw ValidationException::withMessages(['user' => __('Authenticated admin user is required.')]);
        }

        $overrideAllocation->handle($order, $payload['moves'], $actor);

        if (request()->expectsJson() && ! request()->header('X-Inertia')) {
            return response()->noContent();
        }

        return back()->with('status', __('Allocation updated.'));
    }
}
