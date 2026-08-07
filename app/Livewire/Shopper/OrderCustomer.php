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

        if ($customer) {
            return $customer;
        }

        // Fallback for guest checkout or orders without a linked User account
        $shippingAddress = $this->order->shippingAddress;
        $metadata = is_array($this->order->metadata)
            ? $this->order->metadata
            : (json_decode((string) $this->order->metadata, true) ?: []);

        $email = data_get($metadata, 'shipping_address.email')
            ?? data_get($metadata, 'email');

        $phone = $shippingAddress?->phone_number
            ?? $shippingAddress?->phone
            ?? data_get($metadata, 'shipping_address.phone_number')
            ?? data_get($metadata, 'phone_number');

        $firstName = $shippingAddress?->first_name ?? data_get($metadata, 'shipping_address.first_name', '');
        $lastName = $shippingAddress?->last_name ?? data_get($metadata, 'shipping_address.last_name', '');
        $fullName = trim("{$firstName} {$lastName}");

        if ($fullName === '' && ! $email && ! $phone) {
            return null;
        }

        $fullName = $fullName !== '' ? $fullName : 'Pelanggan Guest';

        return (object) [
            'id' => null,
            'full_name' => $fullName,
            'email' => $email ?: 'Guest Checkout (Tanpa Akun)',
            'phone_number' => $phone ?: '-',
            'picture' => 'https://ui-avatars.com/api/?name='.urlencode($fullName).'&color=7F9CF5&background=EBF4FF',
            'created_at' => $this->order->created_at,
            'orders_count' => 1,
        ];
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
