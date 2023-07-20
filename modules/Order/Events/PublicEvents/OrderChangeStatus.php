<?php

namespace Modules\Order\Events\PublicEvents;

use Gobiz\Event\PublicEvent;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;

class OrderChangeStatus extends PublicEvent
{
    /** @var Order $order */
    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the event name
     *
     * @return string
     */
    public function getName()
    {
        return str_replace(".", "_", OrderEvent::CHANGE_STATUS);
    }

    /**
     * Get the event payload
     *
     * @return array
     */
    public function getPayload()
    {
        $shippingPartner = $this->order->shippingPartner->only(['partner_code', 'code', 'name']);
        if ($shippingPartner) {
            $shippingPartner['code'] = $shippingPartner['partner_code'];
        }
        return [
            'order' => $this->order->attributesToArray(),
            'shipping_partner' => $shippingPartner,
            'application' => $this->order->application->only(['code', 'name'])
        ];
    }

    /**
     * Get the event key
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->order->code;
    }
}
