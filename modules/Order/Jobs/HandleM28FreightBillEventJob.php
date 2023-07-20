<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Application\Model\Application;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;

class HandleM28FreightBillEventJob extends Job
{
    const CHANGE_STATUS = 'FREIGHT_BILL_CHANGE_STATUS';

    public $queue = 'm28_freight_bill_event';

    /**
     * @var array
     */
    protected $payload = [];


    /**
     * HandleFobizOrderEventJob constructor.
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @param $message
     * @param array $context
     */
    private function log($message, $context = [])
    {
        if(empty($contex)) {
            $context = $this->payload;
        }

        return LogService::logger('m28_freight_bill_job')->info($message, $context);
    }

    public function handle()
    {
        $this->log('debug');

        $event               = Arr::get($this->payload, 'event');
        $payload             = Arr::get($this->payload, 'payload', []);
        $shippingPartnerCode = Arr::get($payload, 'shipping_partner_code', '');
        $applicationCode     = Arr::get($payload, 'application_code', '');
        $freightBill         = Arr::get($payload, 'freight_bill', []);
        $freightBillStatus   = Arr::get($freightBill, 'status', '');
        $freightBillCode     = Arr::get($freightBill, 'freight_bill_code', []);

        $app = Application::query()->where('code', trim($applicationCode))->first();
        if(!$app instanceof Application) {
            $this->log('applicationCode invalid');
            return;
        }

        $order = Order::query()->where('tracking_no', $freightBillCode)
            ->where('shipping_carrier_code', $shippingPartnerCode)
            ->first();
        if(!$order instanceof  Order) {
            $this->log('freightBillCode invalid');
            return;
        }

        if($event == self::CHANGE_STATUS && $freightBillStatus == 'CANCELLED') {
            $order->status = Order::STATUS_CANCEL;
            $order->ref    = $order->ref.'-'.time(); //logic hủy M28 phải đổi lại ref, do m28 tạo lại vận đơn sẽ k trùng ref cũ
            $order->save();
            return;
        }


    }
}
