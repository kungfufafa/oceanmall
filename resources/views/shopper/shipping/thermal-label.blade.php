<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stiker Label Pengiriman - {{ $order->number }}</title>
    <style>
        @page {
            size: 100mm 150mm;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 8mm;
            background: #fff;
            color: #000;
            font-size: 11px;
            box-sizing: border-box;
        }
        .label-container {
            border: 2px solid #000;
            padding: 8px;
            max-width: 100mm;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .logo-box {
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .service-badge {
            font-size: 14px;
            font-weight: 900;
            border: 2px solid #000;
            padding: 2px 8px;
            text-transform: uppercase;
        }
        .barcode-section {
            text-align: center;
            border-bottom: 2px solid #000;
            padding: 8px 0;
            margin-bottom: 8px;
        }
        .barcode-text {
            font-family: monospace;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 2px;
        }
        .address-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .address-box strong {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .address-name {
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 2px;
        }
        .items-box {
            font-size: 10px;
        }
        .items-box table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .items-box th, .items-box td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: left;
        }
        .footer-note {
            margin-top: 8px;
            font-size: 9px;
            text-align: center;
            border-top: 1px stroke #ccc;
            padding-top: 4px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align: center; margin-bottom: 12px;">
    <button onclick="window.print()" style="padding: 8px 16px; font-size: 14px; font-weight: bold; background: #000; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
        🖨️ Cetak Stiker Label Ini
    </button>
</div>

@foreach ($shipmentPresentations as $shipment)
    <div class="label-container" style="page-break-after: always; margin-bottom: 16px;">
        <div class="header">
            <div class="logo-box">
                {{ $shipment['carrier'] }}
            </div>
            <div class="service-badge">
                {{ $shipment['service'] }}
            </div>
        </div>

        <div class="barcode-section">
            <div style="font-size: 9px; text-transform: uppercase; margin-bottom: 2px;">NOMOR RESI / NO. DELIVERY ORDER</div>
            <div class="barcode-text">{{ $shipment['delivery_order_no'] ?? $shipment['awb'] ?? $order->number }}</div>
        </div>

        <div class="address-grid">
            <div class="address-box" style="border-right: 1px solid #000; padding-right: 6px;">
                <strong>PENGIRIM (FROM):</strong>
                <div class="address-name">{{ $shipment['shipper_name'] }}</div>
                <div>{{ $shipment['shipper_address'] }}</div>
                <div>Telp: {{ $shipment['shipper_phone'] }}</div>
            </div>
            <div class="address-box">
                <strong>PENERIMA (TO):</strong>
                <div class="address-name">{{ $shipment['receiver_name'] }}</div>
                <div>{{ $shipment['receiver_address'] }}</div>
                <div>Telp: {{ $shipment['receiver_phone'] }}</div>
            </div>
        </div>

        <div class="items-box">
            <strong>DAFTAR ISI PAKET (PACKING LIST):</strong>
            <table>
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th style="width: 40px; text-align: center;">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shipment['lines'] as $line)
                        <tr>
                            <td>{{ $line['name'] }}</td>
                            <td style="text-align: center; font-weight: bold;">{{ $line['qty'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="footer-note">
            Order #{{ $order->number }} · RajaOngkir / Komerce Shipping · OceanMall Official Store
        </div>
    </div>
@endforeach

<script>
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => window.print(), 500);
    });
</script>
</body>
</html>
