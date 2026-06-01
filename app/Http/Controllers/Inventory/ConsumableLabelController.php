<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\View\View;

class ConsumableLabelController extends Controller
{
    public function show(Consumable $consumable): View
    {
        $this->authorize('view', $consumable);

        $barcode = $consumable->item_no ?: (string) $consumable->id;
        $scanUrl = route('inventory.scan', ['barcode' => $barcode]);

        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd
        );

        $qrSvg = (new Writer($renderer))->writeString($scanUrl);

        return view('inventory.consumable-label', [
            'consumable' => $consumable,
            'barcode' => $barcode,
            'scanUrl' => $scanUrl,
            'qrDataUri' => 'data:image/svg+xml;base64,'.base64_encode($qrSvg),
        ]);
    }
}
