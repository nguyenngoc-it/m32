<?php

namespace Modules\GGE\Services;

class GGEOrderStatus
{
    const PENDING                   = 'pending';
    const FOR_PICKUP                = 'for_pickup';
    const PICKUP_RIDER_FOUND        = 'pickup_rider_found';
    const OUT_FOR_PICKUP            = 'out_for_pickup';
    const FAILED_PICKUP_ATTEMPT     = 'failed_pickup_attempt';
    const CANCELED                  = 'canceled';
    const FAILED_PICKUP             = 'failed_pickup';
    const PICKED_UP                 = 'picked_up';
    const RECEIVED_AT_PICKUP_HUB    = 'received_at_pickup_hub';
    const IN_TRANSIT                = 'in_transit';
    const AT_SORTING_CENTER         = 'at_sorting_center';
    const RECEIVED_AT_DELIVERY_AREA = 'received_at_delivery_area';
    const RECEIVED_AT_DELIVERY_HUB  = 'received_at_delivery_hub';
    const OUT_FOR_DELIVERY          = 'out_for_delivery';
    const DELIVERED                 = 'delivered';
    const FAILED_DELIVERY           = 'failed_delivery';
    const FOR_RETURN                = 'for_return';
    const ARRIVED_AT_RTS_HUB        = 'arrived_at_rts_hub';
    const OUT_FOR_RETURN            = 'out_for_return';
    const RETURN_IN_TRANSIT         = 'return_in_transit';
    const RETURNED                  = 'returned';
    const FAILED_RETURN             = 'failed_return';
    const FOR_CLAIMS                = 'for_claims';
    const CLAIMED                   = 'claimed';
}
