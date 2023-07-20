<?php

namespace Modules\Order\Transformers;

use Gobiz\Transformer\TransformerInterface;
use Modules\Order\Models\Order;

class OrderPublicEventTransformer implements TransformerInterface
{
    /**
     * Transform the data
     *
     * @param Order $order
     * @return array
     */
    public function transform($order)
    {
        return $order->attributesToArray();
    }
}
