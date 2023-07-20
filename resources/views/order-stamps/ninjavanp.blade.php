<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ninja Van Phillipines Shipping Document</title>
    <style>
        html,body {
            height:100%;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }
        table, th, td {
            text-align: center;
            overflow: hidden;
        }
        table img {
            width: 100%;
            max-height: 60px;
        }
        p {
            margin: 0px;
        }
        .container {
            width: 105mm;
            height: 148mm;
            margin: 0 auto;
            margin-bottom: 10px;
            font-size: 12px;
            font-family: arial;
            overflow: hidden;
            display: block;
        }
        .barcode {
            display: block;
        }
        .barcode p {
            text-align: center;
        }
        .shipping_info {
            text-align: left;
            display: block;
            overflow: hidden;
            padding: 5px;
        }
        .shipping_info p {
            padding: 2px 0px;
        }
        canvas {
            width: 80px!important;
            height: 80px!important;
        }
        .qrcode {
            text-align: left;
            padding: 10px;
        }
        .customer_info {
            height: 60%;
            display: block;
            border: 2px solid black;
            padding: 5px;
        }
        .head_title {
            padding: 10px;
            border-bottom: 2px solid black;
            font-weight: bold;
        }
        .box_info {
            padding: 10px;
        }
        .box_info p {
            padding: 2px;
        }
    </style>
    <script src="{{ \Modules\Service::app()->assetUrl('js/jquery.min.js') }}"></script>
    <script src="{{ \Modules\Service::app()->assetUrl('js/js-barcode-all.min.js') }}"></script>
    <script src="{{ \Modules\Service::app()->assetUrl('js/jquery.qrcode.min.js') }}"></script>
</head>
<body>
@foreach ($orders as $key => $order)
    <div class="container">
        <table style="height: 30%;">
            <tr>
                <td style="width: 40%; text-align: center;">
                    <div id="qrcode_{{ $key }}" class="qrcode"></div>
                </td>
                <td style="width: 60%; font-size: 12pt;padding: 10px;text-align: right;">
                    <p style="margin-bottom: 5px;"><img style="width: 50%" src="{{ \Modules\Service::app()->assetUrl('images/ninjavan-logo.png') }}" /></p>
                    <p style="font-weight: bold;letter-spacing: 2px;">AIRWAY BILL</p>
                    <p style="font-size: 10pt;">www.ninjavan.co</p>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="barcode" style="font-size: 9pt;font-weight: bold;letter-spacing: 2px;">
                        <p>
                            {{ $order->tracking_no }}
                        </p>
                        <p>
                            <img id="barcode"
                                 class="barcode-small"
                                 jsbarcode-value="{{ $order->tracking_no }}"
                                 jsbarcode-width="3"
                                 jsbarcode-displayValue="false"
                            >
                        </p>
                    </div>
                </td>
            </tr>
        </table>
        <div class="customer_info">
            <div class="head_title">
                FROM (SENDER)
            </div>
            <div class="box_info">
                <p>
                    <i class="icon">
                        <img style="width: 14px" src="{{ \Modules\Service::app()->assetUrl('images/user-icon.png') }}" />
                    </i>
                    <span>{{ $order->sender_name }}</span>
                </p>
                <p>
                    <i class="icon">
                        <img style="width: 14px" src="{{ \Modules\Service::app()->assetUrl('images/phone-icon.png') }}" />
                    </i>
                    <span>{{ $order->sender_phone }}</span>
                </p>
                <p>
                    <i class="icon">
                        <img style="width: 14px" src="{{ \Modules\Service::app()->assetUrl('images/marker-icon.png') }}" />
                    </i>
                    <span>{{ $order->getSenderFullAddressAttribute() }}</span>
                </p>
            </div>
            <div class="head_title">
                TO (ADDRESSEE)
            </div>
            <div class="box_info">
                <p>
                    <i class="icon">
                        <img style="width: 14px" src="{{ \Modules\Service::app()->assetUrl('images/user-icon.png') }}" />
                    </i>
                    <span>{{ $order->receiver_name }}</span>
                </p>
                <p>
                    <i class="icon">
                        <img style="width: 14px" src="{{ \Modules\Service::app()->assetUrl('images/phone-icon.png') }}" />
                    </i>
                    <span>{{ $order->receiver_phone }}</span>
                </p>
                <p>
                    <i class="icon">
                        <img style="width: 14px" src="{{ \Modules\Service::app()->assetUrl('images/marker-icon.png') }}" />
                    </i>
                    <span>{{ $order->getReceiverFullAddressAttribute() }}</span>
                </p>
            </div>
            <div class="head_title" style="padding-bottom: 0px;">

            </div>
            <div class="box_info">
                <p>
                    <span style="font-weight:bold;">COD:</span>
                    <span>{{ $order->cod }}</span>
                </p>
                <p>
                    <span style="font-weight:bold;">Items:</span>
                    <span>{{ $order->getItems('name') }}</span>
                </p>
                <p>
                    <span style="font-weight:bold;">Skus:</span>
                    <span>{{ $order->getItems('code') }}</span>
                </p>
            </div>
        </div>
        <script>
            (function() {
                $(<?php echo '"#qrcode_' . $key . '"' ?>).qrcode(<?php echo '"' . $order->tracking_no . '"' ?>)
            })();
        </script>
    </div>
@endforeach
</body>
<script>
    (function() {
        JsBarcode('#barcode').init()
    })();
</script>

</html>
