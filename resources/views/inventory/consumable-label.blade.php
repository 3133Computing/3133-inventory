<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('general.consumable_qr_label') }} - {{ $consumable->name }}</title>
    <style>
        @page { size: 62mm 29mm; margin: 2mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            background: #fff;
        }
        .label {
            width: 58mm;
            min-height: 25mm;
            display: flex;
            align-items: center;
            gap: 2.5mm;
            padding: 1mm;
            border: 1px dashed #ccc;
        }
        .qr {
            width: 22mm;
            height: 22mm;
            flex: 0 0 22mm;
        }
        .qr img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .details {
            flex: 1 1 auto;
            min-width: 0;
            line-height: 1.1;
        }
        .brand {
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: .4px;
        }
        .name {
            margin-top: 1mm;
            font-size: 8pt;
            font-weight: 700;
            overflow-wrap: anywhere;
        }
        .barcode {
            margin-top: 1mm;
            font-family: 'Courier New', monospace;
            font-size: 7pt;
            overflow-wrap: anywhere;
        }
        .url {
            margin-top: 1mm;
            font-size: 5pt;
            overflow-wrap: anywhere;
        }
        .actions {
            margin: 12px 0;
        }
        @media print {
            .actions { display: none; }
            .label { border: none; }
        }
    </style>
</head>
<body>
    <div class="actions hidden-print">
        <button type="button" onclick="window.print()">{{ trans('general.print_label') }}</button>
        <a href="{{ route('consumables.show', $consumable) }}">{{ trans('general.back') }}</a>
    </div>

    <div class="label">
        <div class="qr">
            <img src="{{ $qrDataUri }}" alt="{{ trans('general.consumable_qr_label') }}">
        </div>
        <div class="details">
            <div class="brand">3133</div>
            <div class="name">{{ $consumable->name }}</div>
            <div class="barcode">{{ $barcode }}</div>
            <div class="url">{{ $scanUrl }}</div>
        </div>
    </div>
</body>
</html>
