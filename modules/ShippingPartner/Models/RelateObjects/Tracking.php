<?php

namespace Modules\ShippingPartner\Models\RelateObjects;

/**
 * Class Tracking
 * @package Modules\ShippingPartner\Models\RelateObjects
 *
 * @property string $trackingCode
 * @property string $status
 * @property string $originStatus
 */
class Tracking
{
    /** @var string $trackingCode */
    public $trackingCode;
    /** @var string $status */
    public $status;
    /** @var string $originStatus */
    public $originStatus;

    public function __construct($trackingCode, $originStatus, $status)
    {
        $this->trackingCode = $trackingCode;
        $this->originStatus = $originStatus;
        $this->status       = $status;
    }
}
