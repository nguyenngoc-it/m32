<?php
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
/**
 * @var Order[] $orders
 * @var ShippingPartnerLocation $shippingLocation
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>J&T</title>
    <link href="{{ \Modules\Service::app()->assetUrl('css/print.css') }}" type="text/css" rel="stylesheet" />
</head>
<body>
<?php
foreach ($orders as $order):
    $shippingLocation = ShippingPartnerLocation::query()
        ->where('partner_code', ShippingPartner::PARTNER_JNTP)
        ->where('location_code', $order->receiver_ward_code)
        ->first();
?>
    <table class="tbl_wrapper" border="1" cellspacing="0" cellpadding="0">
        <tr>
            <td class="text-center">
                <img src="{{ \Modules\Service::app()->assetUrl('images/jnt-logo.png') }}" class="logo">
            </td>
            <td class="fz-16 text-center">EZ</td>
        </tr>
        <tr>
            <td colspan="2" class="fz-12 text-center">
                <img id="barcode"
                     class="barcode"
                     jsbarcode-value="{{ $order->tracking_no }}"
                     jsbarcode-width="4"
                     jsbarcode-displayValue	="false"
                >
                <p>{{ $order->tracking_no }}</p>
            </td>
        </tr>
        <tr>
            <td>Order No : {{ $order->ref }}</td>
            <td class="text-center">{{ $order->receiverWard->label ?? '' }}</td>
        </tr>
        <tr class="fz-23 font-bold text-center">
            <td>{{ $order->sorting_code ? : $shippingLocation->meta_data['sortingcode'] ?? '--' }}</td>
            <td>{{ $order->sorting_no ? : $shippingLocation->meta_data['sortingNo'] ?? '--' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="p-0">
                <table class="tbl_location font-bold" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="h45">Sender</td>
                        <td class="valign-top">
                            <table class="tbl_location_sub" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="p-0">{{ $order->sender_name }}</td>
                                    <td class="p-0">{{ $order->sender_phone }}</td>
                                </tr>
                            </table>
                            <p class="mt-5">{{ $order->sender_full_address }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="h45">Receiver</td>
                        <td class="valign-top">
                            <table class="tbl_location_sub" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="p-0">{{ $order->receiver_name }}</td>
                                    <td class="p-0">{{ $order->receiver_phone }}</td>
                                </tr>
                            </table>
                            <p class="mt-5">{{ $order->receiver_full_address }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="p-0 h55">
                <table class="tbl_delivery" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="font-bold text-center" colspan="2">No.of Delivery Attempts</td>
                    </tr>
                    <tr class="fz-12 text-center">
                        <td>1</td>
                        <td>2</td>
                    </tr>
                </table>
            </td>
            <td>COD : {{ $order->cod }} PHP</td>
        </tr>
        <tr>
            <td class="h35">
                <table class="tbl_goods" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="p-0">Piece : {{ $order->items->sum('quantity') }}</td>
                        <td class="p-0">Pouches : </td>
                    </tr>
                </table>
                <p class="mt-5">Weight : {{ $order->weight }}</p>
                <p class="mt-5">Goods : {{ $order->items->pluck('name')->implode(' - ') }}</p>
            </td>
            <td class="fz-7 valign-top">Remarks: {{ \Modules\Service::jntp()->getRemark($order) }}</td>
        </tr>
        <tr>
            <td class="h35 text-center">
                <img id="barcode"
                     class="barcode-small"
                     jsbarcode-value="{{ $order->tracking_no }}"
                     jsbarcode-width="3"
                     jsbarcode-displayValue	="false"
                >
                <p>{{ $order->tracking_no }}</p>
            </td>
            <td class="fz-7 valign-top">Signature</td>
        </tr>
    </table>
<?php endforeach; ?>

<script src="{{ \Modules\Service::app()->assetUrl('js/js-barcode-all.min.js') }}"></script>
<script>
(function() {
    JsBarcode('#barcode').init();
})();
</script>

</body>
</html>
