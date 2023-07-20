<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Application\Model\Application;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;

class GetOrderStampsUrlValidator extends Validator
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;

    /**
     * @var Collection
     */
    protected $orders;

    /**
     * GetOrderStampsUrlValidator constructor
     *
     * @param Application $application
     * @param array $input
     */
    public function __construct(Application $application, array $input = [])
    {
        parent::__construct($input);
        $this->application = $application;
    }

    public function rules()
    {
        return [
            'shipping_connect_code' => 'required',
            'tracking_nos' => 'required|array',
        ];
    }

    public function customValidate()
    {
        $this->shippingPartner = $this->application->shippingPartners()->firstWhere('code', $this->input('shipping_connect_code'));
        if (!$this->shippingPartner) {
            $this->errors()->add('shipping_connect_code', static::ERROR_NOT_EXIST);
        }

        $trackingNos = $this->input('tracking_nos');
        $this->orders = Order::query()->where('shipping_carrier_code', $this->shippingPartner->partner_code)
            ->whereIn('tracking_no', $trackingNos)->get();
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }

    /**
     * @return Collection
     */
    public function getOrders()
    {
        return $this->orders;
    }
}
