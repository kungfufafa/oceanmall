<?php

declare(strict_types=1);

namespace App\Livewire\Shopper;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Shopper\Core\Models\Contracts\Order;
use Shopper\Traits\HandlesAuthorizationExceptions;

final class OrderCustomer extends Component
{
    use HandlesAuthorizationExceptions;

    #[Locked]
    public Order $order;

    #[Computed]
    public function customer(): mixed
    {
        $userModel = config('auth.providers.users.model');
        $customer = null;

        if ($this->order->customer_id) {
            $customer = $userModel::query()
                ->withCount('orders')
                ->find($this->order->customer_id);
        }

        return $customer;
    }

    public function render(): View
    {
        $this->order->loadMissing(['shippingAddress', 'billingAddress']);

        return view('shopper::livewire.components.orders.order-customer', [
            'shippingAddress' => $this->order->shippingAddress,
            'billingAddress' => $this->order->billingAddress,
        ]);
    }
}
