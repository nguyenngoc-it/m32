<?php

namespace Modules\Order\Commands;

use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Application\Model\Application;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Models\RelatedObjects\InputOrder;
use Modules\Order\Services\OrderEvent;
use Modules\ShippingPartner\Models\ShippingPartner;
use mysql_xdevapi\Exception;

class CreateLocalOrder
{
    protected $logger;
    /** @var InputOrder $inputOrder */
    protected $inputOrder;
    /** @var ShippingPartner $shippingPartner */
    protected $shippingPartner;
    /** @var Application $application */
    protected $application;

    public function __construct(InputOrder $inputOrder)
    {
        $this->inputOrder      = $inputOrder;
        $this->shippingPartner = $inputOrder->getShippingPartner();
        $this->application     = $this->shippingPartner->application;
        $this->logger          = LogService::logger('create-local-order');
    }

    /**
     * @return Order|null
     */
    public function handle()
    {
        return DB::transaction(function () {
            $order = $this->create();
            $this->receiveLastMilePartnerOrder($order);
            if ($order instanceof Order) {
                $order->logActivity(OrderEvent::CREATE_LOCAL, $this->application->creator);
            }

            return $order;
        });
    }


    /**
     * @param Order $order
     */
    protected function receiveLastMilePartnerOrder(Order $order)
    {
        if (!empty($order->trackingNo)) {
            $order->status = Order::STATUS_READY_TO_PICK;
        }
        $settingShippingPartner = $this->shippingPartner->settings;
        $sender                 = Arr::get($settingShippingPartner, 'sender');
        if (empty($sender)) {
            $this->logger->error('not found sender', $settingShippingPartner);
            throw new Exception('not found sender');
        }

        $order->sender_name          = Arr::get($sender, ShippingPartner::SENDER_NAME);
        $order->sender_phone         = Arr::get($sender, ShippingPartner::SENDER_PHONE);
        $order->sender_address       = Arr::get($sender, ShippingPartner::SENDER_ADDRESS);
        $order->sender_province_code = Arr::get($sender, ShippingPartner::SENDER_PROVINCE_CODE);
        $order->sender_district_code = Arr::get($sender, ShippingPartner::SENDER_DISTRICT_CODE);
        $order->sender_ward_code     = Arr::get($sender, ShippingPartner::SENDER_WARD_CODE);

        $order->save();
    }

    /**
     * @return array
     */
    protected function makeOrderData()
    {
        $input = array_merge($this->inputOrder->attributes(), [
            'shipping_partner_id' => $this->shippingPartner->id,
            'shipping_partner_code' => $this->shippingPartner->partner_code,
            'application_id' => $this->application->id,
            'status' => Order::STATUS_CREATING
        ]);

        if (isset($input['ref']) && empty($input['ref'])) {
            unset($input['ref']);
        }

        return $input;
    }

    /**
     * @return Order
     */
    protected function create()
    {
        $dataOrder = $this->makeOrderData();
        $this->logger->debug('INPUT_TRANSFORM', $dataOrder);
        $order = new Order($dataOrder);
        $order->save();
        $items      = $this->inputOrder->items;
        $orderItems = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                foreach (['quantity', 'price'] as $p) {
                    if (isset($item[$p])) {
                        $item[$p] = floatval($item[$p]);
                    }
                }
                $orderItems[] = new OrderItem($item);
            }

            $order->items()->saveMany($orderItems);
        }

        return $order;
    }
}
