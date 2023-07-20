<?php

namespace Modules\Order\Services;

class OrderEvent
{
    const CREATE        = 'ORDER.CREATE';
    const CREATE_LOCAL  = 'ORDER.CREATE_LOCAL';
    const CHANGE_STATUS = 'ORDER.CHANGE_STATUS';

    /**
     * Topics
     */
    const M32_ORDER         = 'm32-order';
    const M2_SHIPMENT_ORDER = 'm2-shipment';
}
