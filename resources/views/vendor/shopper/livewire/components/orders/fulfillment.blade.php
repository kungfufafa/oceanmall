<div>
    <x-shopper::card>
        @if ($shippingAddress)
            <x-slot:title>
                <div>
                    <p class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <span>{{ __('shopper::pages/orders.expedition_to') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ $shippingAddress->full_name }}
                        </span>
                        @if ($country)
                            <img
                                src="{{ $country->svg_flag }}"
                                class="size-4 rounded-full object-cover object-center"
                                alt="{{ $country->translated_name }}"
                            />
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $country->cca2 }}, {{ $country->translated_name }}
                            </span>
                        @elseif ($shippingAddress->country_name)
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $shippingAddress->country_name }}
                            </span>
                        @endif
                    </p>
                </div>
            </x-slot:title>
        @endif

        <div class="grid grid-cols-4 gap-3">
            @foreach ($steps as $index => $step)
                @php
                    $stepNumber = $index + 1;
                    $isLast = $stepNumber === count($steps);
                    $isCompleted = $currentStep > $stepNumber || ($isLast && $currentStep === $stepNumber);
                    $isCurrent = $currentStep === $stepNumber && ! $isLast;
                @endphp

                <div>
                    <div @class([
                        'flex items-center gap-1.5 text-sm',
                        'font-semibold text-gray-900 dark:text-white' => $isCurrent,
                        'font-medium text-success-600 dark:text-success-400' => $isCompleted,
                        'font-medium text-gray-400 dark:text-gray-500' => ! $isCompleted && ! $isCurrent,
                    ])>
                        @if ($isCompleted)
                            <x-heroicon-s-check-circle class="size-4 text-success-500" />
                        @elseif ($isCurrent)
                            <x-filament::icon :icon="\Shopper\Core\Enum\OrderStatus::Processing->getIcon()" class="size-4 animate-spin" />
                        @else
                            <x-filament::icon :icon="$step['icon']" class="size-4" />
                        @endif
                        <span>{{ $step['label'] }}</span>
                    </div>
                    <div @class([
                        'mt-4 h-1 w-full rounded-full',
                        'bg-success-500' => $isCompleted,
                        'bg-gray-900 dark:bg-white' => $isCurrent,
                        'bg-gray-200 dark:bg-white/10' => ! $isCompleted && ! $isCurrent,
                    ])></div>
                </div>
            @endforeach
        </div>
    </x-shopper::card>

    @if ($rajaOngkirEnabled ?? false)
        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-300">
                @if ($rajaOngkirAwbs ?? [])
                    <p>
                        Resi RajaOngkir:
                        <span class="font-semibold text-gray-900 dark:text-white">{{ implode(', ', $rajaOngkirAwbs) }}</span>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Shopper hanya mencatat AWB resmi. Nomor resi tidak diketik manual.</p>
                @elseif ($orderIsPaid ?? false)
                    <p>Pembayaran lunas. Terbitkan AWB dan stiker dari RajaOngkir — bukan form resi Shopper.</p>
                @else
                    <p>Menunggu pelunasan. Setelah lunas, resi RajaOngkir terbit otomatis.</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($canPrintRajaOngkirLabel ?? false)
                    <x-filament::button
                        tag="a"
                        :href="$printRajaOngkirRoute"
                        target="_blank"
                        icon="heroicon-o-printer"
                        color="primary"
                    >
                        Cetak Stiker RajaOngkir
                    </x-filament::button>
                @elseif ($orderIsPaid ?? false)
                    <x-filament::button
                        wire:click="openShippingLabel"
                        wire:loading.attr="disabled"
                        icon="heroicon-o-paper-airplane"
                        color="primary"
                    >
                        <span wire:loading.remove wire:target="openShippingLabel">Terbitkan Resi RajaOngkir</span>
                        <span wire:loading wire:target="openShippingLabel">Menerbitkan…</span>
                    </x-filament::button>
                @endif
            </div>
        </div>
    @elseif ($currentStep > 0 && $currentStep < 4 && $this->hasUnfulfilledItems())
        <div class="flex items-center justify-end mt-5">
            <x-filament::button wire:click="openShippingLabel">
                {{ __('shopper::pages/orders.create_shipping_label') }}
            </x-filament::button>
        </div>
    @elseif ($currentStep >= 3)
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            {{ __('shopper::pages/orders.all_items_fulfilled') }}
        </p>
    @endif
</div>
