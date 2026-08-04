<div class="space-y-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                RajaOngkir / Komerce shipping
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if ($printableShipmentCount > 0)
                    {{ $printableShipmentCount }} paket siap cetak label.
                @else
                    Label aktif setelah pembayaran sukses dan delivery order dibuat.
                @endif
            </p>
        </div>

        @if ($canPrintAnyLabel && $komerceEnabled)
            <a
                href="{{ route('shopper.orders.fulfillment.print-label', $order) }}"
                class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-500"
            >
                Cetak semua label
            </a>
        @endif
    </div>

    @unless ($komerceEnabled)
        <p class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">
            Komerce shipping belum dikonfigurasi. Isi API key Shipping Delivery di .env.
        </p>
    @endunless

    @if ($errors->has('label'))
        <p class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
            {{ $errors->first('label') }}
        </p>
    @endif

    <div class="space-y-3">
        @forelse ($shipments as $shipment)
            <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $shipment['inventory_name'] ?? 'Warehouse #'.$shipment['inventory_id'] }}
                            </p>
                            <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-950/50 dark:text-sky-100">
                                {{ $shipment['status_label'] }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ $shipment['carrier'] ?? 'Courier' }}
                            @if ($shipment['service'])
                                · {{ $shipment['service'] }}
                            @endif
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            AWB · <span class="font-medium">{{ $shipment['awb'] ?? 'Belum ada' }}</span>
                            · Delivery # · <span class="font-medium">{{ $shipment['delivery_order_no'] ?? 'Pending' }}</span>
                        </p>
                        <ul class="space-y-0.5 text-sm text-gray-600 dark:text-gray-300">
                            @foreach ($shipment['lines'] as $line)
                                <li>{{ $line['name'] }} <span class="text-gray-400">×{{ $line['qty'] }}</span></li>
                            @endforeach
                        </ul>
                        @if (! $shipment['can_print_label'] && $shipment['print_hint'])
                            <p class="text-xs text-gray-500">{{ $shipment['print_hint'] }}</p>
                        @endif
                    </div>

                    @if ($shipment['can_print_label'] && $komerceEnabled)
                        <a
                            href="{{ route('shopper.orders.fulfillment.print-label', ['order' => $order, 'shipment' => $shipment['id']]) }}"
                            class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5"
                        >
                            Cetak label
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">Belum ada shipment untuk order ini.</p>
        @endforelse
    </div>

    @if (count($overridableShipments) > 0)
        <form wire:submit="applyOverride" class="space-y-3 border-t border-gray-200 pt-4 dark:border-white/10">
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Pindah stok sebelum AWB</h4>
                <p class="mt-1 text-xs text-gray-500">Hanya tersedia selama paket belum punya AWB.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300" for="shipment_line_id">Item</label>
                    <select id="shipment_line_id" wire:model.live="shipment_line_id" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800">
                        @foreach ($lineOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300" for="qty">Qty</label>
                    <input id="qty" type="number" min="1" wire:model="qty" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300" for="from_inventory_id">Dari gudang</label>
                    <select id="from_inventory_id" wire:model="from_inventory_id" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800">
                        @foreach ($inventories as $inventory)
                            <option value="{{ $inventory['id'] }}">
                                {{ $inventory['name'] }}{{ $inventory['ready_for_shipping'] ? '' : ' (tanpa origin)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300" for="to_inventory_id">Ke gudang</label>
                    <select id="to_inventory_id" wire:model="to_inventory_id" class="w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-800">
                        @foreach ($inventories as $inventory)
                            <option value="{{ $inventory['id'] }}">
                                {{ $inventory['name'] }}{{ $inventory['is_default'] ? ' · default' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($overrideError)
                <p class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
                    {{ $overrideError }}
                </p>
            @endif

            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Terapkan pindah stok</span>
                <span wire:loading>Menyimpan…</span>
            </button>
        </form>
    @endif
</div>
