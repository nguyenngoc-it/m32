<?php

namespace Modules\JNTP\Services;

class JNTPOrderStatus
{
    const ARRIVAL       = 'Arrival';
    const PICKUP        = 'Picked Up';
    const DEPARTURE     = 'Departure';
    const PICKUP_FAILED = 'Problematic';
    const ON_DELIVERY   = 'On Delivery';
    const DELIVERED     = 'Delivered';
    const RETURNED      = 'Return Delivered';
}
