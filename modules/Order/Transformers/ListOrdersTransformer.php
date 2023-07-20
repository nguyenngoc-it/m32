<?php

namespace Modules\Order\Transformers;

use App\Base\Transformer;

class ListOrdersTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Stock $stock
     * @return mixed
     */
    public function transform($order)
    {
        $shippingPartner = $order->shippingPartner;

        return compact('order', 'shippingPartner');
    }
}
