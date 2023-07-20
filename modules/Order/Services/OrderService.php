<?php

namespace Modules\Order\Services;

use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Gobiz\Workflow\WorkflowInterface;
use Gobiz\Workflow\WorkflowService;
use Modules\Application\Model\Application;
use Modules\Order\Commands\CreateLocalOrder;
use Modules\Order\Commands\CreateOrder;
use Modules\Order\Commands\ListOrders;
use Modules\Order\Models\Order;
use Modules\Order\Models\RelatedObjects\InputOrder;
use Modules\ShippingPartner\Models\ShippingPartner;

class OrderService implements OrderServiceInterface
{
    /**
     * Get ticket workflow
     *
     * @return WorkflowInterface
     */
    public function workflow()
    {
        return WorkflowService::workflow('order');
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @param $input
     * @return mixed|Order|null|void
     * @throws Exception
     */
    public function create(ShippingPartner $shippingPartner, $input)
    {
        return (new CreateOrder($shippingPartner, $input))->handle();
    }

    /**
     * Tạo đơn trên M32 nhưng không tạo vận đơn trên DVVC
     *
     * @param InputOrder $inputOrder
     * @return Order|null
     */
    public function createLocal(InputOrder $inputOrder)
    {
        return (new CreateLocalOrder($inputOrder))->handle();
    }

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function orderQuery(array $filter)
    {
        return (new OrderQuery())->query($filter);
    }

    public function listOrders(array $filter, Application $application)
    {
        return (new ListOrders($filter, $application))->handle();
    }

    /**
     * Kiểm tra vận đơn từ dvvc đã có chưa, nếu chưa có thì tạo bên M32
     *
     * @param InputOrder $inputOrder
     * @return void
     */
    public function mappingTracking(InputOrder $inputOrder)
    {
        /** @var Order|null $order */
        $order = Order::query()->where([
            'application_id' => $inputOrder->getShippingPartner()->application_id,
            'ref' => $inputOrder->ref
        ])->first();
        if (empty($order)) {
            $this->createLocal($inputOrder);
        } else {
            /**
             * Nếu đơn chưa có tracking no thì cập nhật theo tracking no bên M28
             */
            if (empty($order->tracking_no)) {
                $order->tracking_no = (string)$inputOrder->trackingNo;
                $order->save();
            }
        }
    }
}
