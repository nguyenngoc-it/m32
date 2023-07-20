<?php

namespace Modules\Order\Commands;

use Exception;
use Gobiz\Event\EventService;
use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Modules\Application\Model\Application;
use Modules\Order\Events\PublicEvents\OrderChangeStatus;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Services\OrderEvent;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Psr\Log\LoggerInterface;

class CreateOrder
{
    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CreateOrder constructor.
     * @param ShippingPartner $shippingPartner
     * @param array $input
     */
    public function __construct(ShippingPartner $shippingPartner, array $input)
    {
        $this->input           = $input;
        $this->shippingPartner = $shippingPartner;
        $this->application     = $shippingPartner->application;

        $this->logger = LogService::logger('create_order', [
            'context' => ['input' => $this->input],
        ]);
    }


    /**
     * @return mixed|Order|null
     * @throws Exception
     */
    public function handle()
    {
        $order = $this->create();
        if ($order) {
            /** @var ShippingPartnerOrder $shippingPartnerOrder */
            $shippingPartnerOrder = Service::appLog()->logTimeExecute(function () use ($order) {
                try {
                    return $this->shippingPartner->partner()->createOrder($order);
                } catch (ShippingPartnerException $exception) {
                    $this->logger->error($exception->getMessage());
                    return null;
                }
            }, LogService::logger('create-order-time'),
                ' order: ' . json_encode($order->toArray())
                . ' - ref: ' . $order->ref
                . ' - shippingPartner: ' . $this->shippingPartner->code
            );

            if ($shippingPartnerOrder) {
                $this->receiveLastMilePartnerOrder($shippingPartnerOrder, $order);

                if (empty($order->tracking_no)) {
                    $order->delete();
                    return null;
                }
            } else {
                $order->delete();
                return null;
            }
            if ($order instanceof Order) {
                $order->logActivity(OrderEvent::CREATE, $this->application->creator);
                EventService::publicEventDispatcher()->publish(OrderEvent::M32_ORDER, new OrderChangeStatus($order));
            }
            return $order;
        }
        return $order;
    }


    /**
     * @param ShippingPartnerOrder $shippingPartnerOrder
     * @param Order $order
     */
    protected function receiveLastMilePartnerOrder(ShippingPartnerOrder $shippingPartnerOrder, Order $order)
    {
        $order->code        = $shippingPartnerOrder->code;
        $order->tracking_no = $shippingPartnerOrder->trackingNo;
        $order->fee         = $shippingPartnerOrder->fee;
        $order->sorting_code = $shippingPartnerOrder->sortingCode;
        $order->sorting_no  = $shippingPartnerOrder->sortingNo;
        if (!empty($shippingPartnerOrder->trackingNo)) {
            $order->status = Order::STATUS_READY_TO_PICK;
        }
        if ($shippingPartnerOrder->sender) {
            $order->sender_name          = $shippingPartnerOrder->sender[ShippingPartner::SENDER_NAME];
            $order->sender_phone         = $shippingPartnerOrder->sender[ShippingPartner::SENDER_PHONE];
            $order->sender_address       = $shippingPartnerOrder->sender[ShippingPartner::SENDER_ADDRESS];
            $order->sender_province_code = $shippingPartnerOrder->sender[ShippingPartner::SENDER_PROVINCE_CODE];
            $order->sender_district_code = $shippingPartnerOrder->sender[ShippingPartner::SENDER_DISTRICT_CODE];
            $order->sender_ward_code     = $shippingPartnerOrder->sender[ShippingPartner::SENDER_WARD_CODE];
        }
        $order->save();
    }

    /**
     * @return array
     */
    protected function makeOrderData()
    {
        $input = array_merge($this->input, [
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
        $order = Order::create($this->makeOrderData());
        if(!$order instanceof Order) {
            return  false;
        }

        $items      = Arr::get($this->input, 'items', []);
        if (!empty($items)) {
            $orderItems = [];
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
