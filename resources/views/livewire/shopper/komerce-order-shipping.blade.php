<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
    {{-- Header & E2E Admin Actions --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-5 py-4 bg-gray-50/80 border-b border-gray-200 dark:bg-gray-950/60 dark:border-gray-800">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                <x-heroicon-o-truck class="size-6" />
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">
                        Pengiriman RajaOngkir / Komerce
                    </h4>
                    @if ($canPrintAnyLabel && ! $hasUnprocessedShipment)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Terkirim ke Komerce
                        </span>
                    @elseif ($canPrintAnyLabel && $hasUnprocessedShipment)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                            Sebagian Terdaftar
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Belum Terdaftar di Komerce
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Kelola pengiriman otomatis RajaOngkir Komerce, resi AWB, dan stiker pengiriman.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 mt-2 sm:mt-0">
            @if ($order->payment_status !== \Shopper\Core\Enum\PaymentStatus::Paid)
                <x-filament::button
                    type="button"
                    wire:click="markPaidAndProcessDelivery"
                    wire:loading.attr="disabled"
                    color="success"
                    icon="heroicon-o-check-circle"
                    size="sm"
                >
                    <span wire:loading.remove wire:target="markPaidAndProcessDelivery">Tandai Lunas & Kirim Komerce</span>
                    <span wire:loading wire:target="markPaidAndProcessDelivery">Memproses…</span>
                </x-filament::button>
            @endif

            @if ($komerceEnabled && $hasUnprocessedShipment)
                <x-filament::button
                    type="button"
                    wire:click="processAllDeliveryOrders"
                    wire:loading.attr="disabled"
                    color="primary"
                    icon="heroicon-o-paper-airplane"
                    size="sm"
                >
                    <span wire:loading.remove wire:target="processAllDeliveryOrders">Kirim ke Komerce (Push Order)</span>
                    <span wire:loading wire:target="processAllDeliveryOrders">Mengirim…</span>
                </x-filament::button>
            @endif

            @if ($canPrintAnyLabel && $komerceEnabled)
                <x-filament::button
                    tag="a"
                    href="{{ route('shopper.orders.fulfillment.print-label', $order) }}"
                    target="_blank"
                    icon="heroicon-o-printer"
                    color="primary"
                    size="sm"
                >
                    Cetak Semua Stiker Resi
                </x-filament::button>
            @endif
        </div>
    </div>

    {{-- Alerts & Status Feedback --}}
    <div class="p-5 space-y-4">
        @unless ($komerceEnabled)
            <div class="flex items-center gap-2.5 rounded-lg bg-amber-50 border border-amber-200 p-3.5 text-xs text-amber-900 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-300">
                <x-heroicon-o-exclamation-triangle class="size-5 text-amber-600 dark:text-amber-400 shrink-0" />
                <span>Integrasi Komerce belum aktif. Pastikan API key terpasang di file .env.</span>
            </div>
        @endunless

        @if ($successMessage)
            <div class="flex items-center gap-2.5 rounded-lg bg-emerald-50 border border-emerald-200 p-3.5 text-xs text-emerald-900 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-300" role="status">
                <x-heroicon-o-check-circle class="size-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <span class="font-medium">{{ $successMessage }}</span>
            </div>
        @endif

        @if ($overrideError)
            <div class="flex items-center gap-2.5 rounded-lg bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-900 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-300" role="alert">
                <x-heroicon-o-x-circle class="size-5 text-rose-600 dark:text-rose-400 shrink-0" />
                <span class="font-medium">{{ $overrideError }}</span>
            </div>
        @endif

        @if (isset($errors) && $errors->has('label'))
            <div class="flex items-center gap-2.5 rounded-lg bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-900 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-300" role="alert">
                <x-heroicon-o-x-circle class="size-5 text-rose-600 dark:text-rose-400 shrink-0" />
                <span>{{ $errors->first('label') }}</span>
            </div>
        @endif

        {{-- Shipments Cards --}}
        @forelse ($shipments as $shipment)
            <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40 space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-200/80 dark:border-gray-700/60">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <x-heroicon-o-building-storefront class="size-4 text-gray-500 dark:text-gray-400 shrink-0" />
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                Gudang Pengirim: {{ $shipment['shipper_name'] }}
                            </span>
                            @if ($shipment['can_print_label'])
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                                    Resi Siap (Komerce)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                    Belum Terdaftar di Komerce
                                </span>
                            @endif
                        </div>

                        <div class="text-xs text-gray-600 dark:text-gray-300 flex items-center gap-2 flex-wrap">
                            <span>Kurir: <strong class="text-gray-900 dark:text-white">{{ $shipment['carrier'] }} ({{ $shipment['service'] }})</strong></span>
                            <span>•</span>
                            <span>Ongkir: <strong class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($shipment['cost'], 0, ',', '.') }}</strong></span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($shipment['can_print_label'] && $komerceEnabled)
                            <x-filament::button
                                tag="a"
                                href="{{ route('shopper.orders.fulfillment.print-label', ['order' => $order, 'shipment' => $shipment['id']]) }}"
                                target="_blank"
                                icon="heroicon-o-printer"
                                color="primary"
                                size="sm"
                            >
                                Cetak Stiker Resi
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                wire:click="processDeliveryOrder({{ $shipment['id'] }})"
                                wire:loading.attr="disabled"
                                icon="heroicon-o-arrow-path"
                                size="sm"
                                color="gray"
                            >
                                <span wire:loading.remove wire:target="processDeliveryOrder({{ $shipment['id'] }})">Re-Generate</span>
                                <span wire:loading wire:target="processDeliveryOrder({{ $shipment['id'] }})">Memproses…</span>
                            </x-filament::button>
                        @elseif ($komerceEnabled)
                            <x-filament::button
                                type="button"
                                wire:click="processDeliveryOrder({{ $shipment['id'] }})"
                                wire:loading.attr="disabled"
                                icon="heroicon-o-paper-airplane"
                                size="sm"
                                color="primary"
                            >
                                <span wire:loading.remove wire:target="processDeliveryOrder({{ $shipment['id'] }})">Generate Resi Komerce</span>
                                <span wire:loading wire:target="processDeliveryOrder({{ $shipment['id'] }})">Memproses…</span>
                            </x-filament::button>
                        @endif
                    </div>
                </div>

                {{-- Resi Info Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700/80 text-xs">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-[11px] uppercase tracking-wider font-semibold">ID Order Komerce</span>
                        <span class="font-mono font-bold text-sm text-primary-600 dark:text-primary-400 mt-0.5 block">
                            {{ $shipment['delivery_order_no'] ?? 'Belum ada (Klik Generate Resi)' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-[11px] uppercase tracking-wider font-semibold">No. Resi AirwayBill (AWB)</span>
                        <span class="font-mono font-bold text-sm text-gray-900 dark:text-white mt-0.5 block">
                            {{ $shipment['awb'] ?? ($shipment['tracking_number'] ?? 'Menunggu proses pickup') }}
                        </span>
                    </div>
                </div>

                {{-- Items --}}
                <div class="text-xs text-gray-600 dark:text-gray-300 flex flex-wrap items-center gap-2 pt-1">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Item Produk dalam Paket:</span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($shipment['lines'] as $line)
                            <span class="px-2.5 py-1 rounded-md bg-gray-200/80 dark:bg-gray-700/80 font-medium text-gray-900 dark:text-gray-100 text-xs">
                                {{ $line['name'] }} &times; {{ $line['qty'] }}
                            </span>
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
        <div class="px-5 py-3 bg-gray-50/80 dark:bg-gray-950/60 border-t border-gray-200 dark:border-gray-800">
            <details class="group">
                <summary class="flex cursor-pointer items-center justify-between text-xs font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    <span class="flex items-center gap-2">
                        <x-heroicon-o-arrows-right-left class="size-4 text-primary-500" />
                        <span>Pindah Lokasi Stok Gudang Pengirim</span>
                    </span>
                    <x-heroicon-o-chevron-down class="size-4 transition group-open:rotate-180" />
                </summary>

                <form wire:submit="applyOverride" class="mt-3 space-y-3 pt-2 text-xs">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300" for="shipment_line_id">Produk</label>
                            <select id="shipment_line_id" wire:model.live="shipment_line_id" class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                @foreach ($lineOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300" for="qty">Qty</label>
                            <input id="qty" type="number" min="1" wire:model="qty" class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300" for="from_inventory_id">Gudang Asal</label>
                            <select id="from_inventory_id" wire:model="from_inventory_id" class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory['id'] }}">{{ $inventory['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block font-medium text-gray-700 dark:text-gray-300" for="to_inventory_id">Gudang Tujuan</label>
                            <select id="to_inventory_id" wire:model="to_inventory_id" class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                @foreach ($inventories as $inventory)
                                    <option value="{{ $inventory['id'] }}">{{ $inventory['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($overrideError)
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-2">{{ $overrideError }}</p>
                    @endif

                    <x-filament::button type="submit" size="xs" color="gray" class="mt-2">
                        Pindahkan Stok
                    </x-filament::button>
                </form>
            </details>
        </div>
    @endif
</div>
