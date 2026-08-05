<div class="overflow-hidden rounded-lg divide-y divide-gray-200 bg-white ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-white/10 dark:divide-white/10">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-950">
        <h4 class="text-base/5 text-gray-900 font-semibold dark:text-white">
            Pengiriman Komerce (RajaOngkir)
        </h4>

        <div class="flex items-center gap-2">
            @if ($hasUnprocessedShipment && $komerceEnabled)
                <x-filament::button
                    type="button"
                    wire:click="processAllDeliveryOrders"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-sparkles"
                    size="sm"
                >
                    <span wire:loading.remove wire:target="processAllDeliveryOrders">Otomatiskan Resi</span>
                    <span wire:loading wire:target="processAllDeliveryOrders">Memproses…</span>
                </x-filament::button>
            @endif

            @if ($canPrintAnyLabel && $komerceEnabled)
                <x-filament::button
                    tag="a"
                    href="{{ route('shopper.orders.fulfillment.print-label', $order) }}"
                    target="_blank"
                    icon="heroicon-o-printer"
                    color="gray"
                    size="sm"
                >
                    Cetak Semua Resi
                </x-filament::button>
            @endif
        </div>
    </div>

    {{-- Body / Shipments --}}
    <div class="p-4 space-y-4">
        @unless ($komerceEnabled)
            <div class="flex items-center gap-2 rounded-md bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-400">
                <x-heroicon-o-exclamation-triangle class="size-4 text-amber-500 shrink-0" />
                <span>Komerce shipping belum aktif. Konfigurasi API key di .env.</span>
            </div>
        @endunless

        @if ($errors->has('label'))
            <div class="flex items-center gap-2 rounded-md bg-danger-50 p-3 text-xs text-danger-800 dark:bg-danger-500/10 dark:text-danger-400" role="alert">
                <x-heroicon-o-x-circle class="size-4 text-danger-500 shrink-0" />
                <span>{{ $errors->first('label') }}</span>
            </div>
        @endif

        @forelse ($shipments as $shipment)
            <div class="space-y-3.5 {{ ! $loop->last ? 'pb-4 border-b border-gray-200 dark:border-white/10' : '' }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $shipment['shipper_name'] }}
                            </span>

                            @if ($shipment['can_print_label'])
                                <x-filament::badge color="success" size="sm" icon="heroicon-s-check-circle">
                                    Resi Siap
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="warning" size="sm" icon="heroicon-s-clock">
                                    Menunggu Resi
                                </x-filament::badge>
                            @endif
                        </div>

                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $shipment['carrier'] }} ({{ $shipment['service'] }})</span>
                            <span class="mx-1">•</span>
                            <span>Ongkir: <strong class="text-gray-900 dark:text-white">Rp {{ number_format($shipment['cost'], 0, ',', '.') }}</strong></span>
                        </div>
                    </div>

                    <div>
                        @if (! $shipment['can_print_label'] && $komerceEnabled)
                            <x-filament::button
                                type="button"
                                wire:click="processDeliveryOrder({{ $shipment['id'] }})"
                                wire:loading.attr="disabled"
                                icon="heroicon-o-paper-airplane"
                                size="sm"
                            >
                                <span wire:loading.remove wire:target="processDeliveryOrder({{ $shipment['id'] }})">Generate Resi</span>
                                <span wire:loading wire:target="processDeliveryOrder({{ $shipment['id'] }})">Memproses…</span>
                            </x-filament::button>
                        @endif

                        @if ($shipment['can_print_label'] && $komerceEnabled)
                            <x-filament::button
                                tag="a"
                                href="{{ route('shopper.orders.fulfillment.print-label', ['order' => $order, 'shipment' => $shipment['id']]) }}"
                                target="_blank"
                                icon="heroicon-o-printer"
                                color="gray"
                                size="sm"
                            >
                                Cetak Stiker Resi
                            </x-filament::button>
                        @endif
                    </div>
                </div>

                {{-- Resi Info & Items in clean line format --}}
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <div>
                        <span>No. Resi (AWB):</span>
                        <span class="ml-1 font-semibold text-gray-900 dark:text-white">{{ $shipment['awb'] ?? 'Belum terbit' }}</span>
                    </div>
                    <div>
                        <span>ID Komerce:</span>
                        <span class="ml-1 font-semibold text-gray-900 dark:text-white">{{ $shipment['delivery_order_no'] ?? 'Pending' }}</span>
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                    <span>Item Paket:</span>
                    <div class="flex flex-wrap items-center gap-1.5 font-medium text-gray-900 dark:text-white">
                        @foreach ($shipment['lines'] as $line)
                            <span>{{ $line['name'] }} &times; {{ $line['qty'] }}</span>
                            @if (! $loop->last) <span class="text-gray-300 dark:text-gray-700">,</span> @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada data pengiriman untuk pesanan ini.</p>
        @endforelse
    </div>

    {{-- Collapsible Footer for Stock Transfer --}}
    @if (count($overridableShipments) > 0)
        <div class="p-3 bg-gray-50 dark:bg-gray-950">
            <details class="group">
                <summary class="flex cursor-pointer items-center justify-between text-xs text-gray-500 hover:text-gray-900 dark:hover:text-white">
                    <span class="flex items-center gap-1.5">
                        <x-heroicon-o-arrows-right-left class="size-4" />
                        <span>Pindah Lokasi Stok Gudang Pengirim</span>
                    </span>
                    <x-heroicon-o-chevron-down class="size-4 transition group-open:rotate-180" />
                </summary>

                <form wire:submit="applyOverride" class="mt-3 space-y-3 pt-2 text-xs">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300" for="shipment_line_id">Produk</label>
                            <select id="shipment_line_id" wire:model.live="shipment_line_id" class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900">
                                @foreach ($lineOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300" for="qty">Qty</label>
                            <input id="qty" type="number" min="1" wire:model="qty" class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900" />
                        </div>
                        <div>
                            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300" for="from_inventory_id">Gudang Asal</label>
                            <select id="from_inventory_id" wire:model="from_inventory_id" class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900">
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory['id'] }}">{{ $inventory['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300" for="to_inventory_id">Gudang Tujuan</label>
                            <select id="to_inventory_id" wire:model="to_inventory_id" class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900">
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory['id'] }}">{{ $inventory['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($overrideError)
                        <p class="text-xs text-danger-600 dark:text-danger-400">{{ $overrideError }}</p>
                    @endif

                    <x-filament::button type="submit" size="xs" color="gray">
                        Pindahkan Stok
                    </x-filament::button>
                </form>
            </details>
        </div>
    @endif
</div>
