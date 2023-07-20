<?php

namespace Modules\Order\Services;

use Gobiz\Workflow\WorkflowInterface;
use Modules\Order\Models\Order;
use Modules\Application\Model\Application;
use Modules\Order\Models\RelatedObjects\InputOrder;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;

interface OrderServiceInterface
{
    /**
     * Get ticket workflow
     *
     * @return WorkflowInterface
     */
    public function workflow();

    /**
     * @param ShippingPartner $shippingPartner
     * @param $input
     * @return mixed|Order|null|void
     * @throws ShippingPartnerException
     */
    public function create(ShippingPartner $shippingPartner,  $input);

    /**
     * Tạo đơn trên M32 nhưng không tạo vận đơn trên DVVC
     *
     * @param InputOrder $inputOrder
     * @return Order|null
     */
    public function createLocal(InputOrder $inputOrder);

    public function orderQuery(array $filter);

    public function listOrders(array $filter, Application $application);

    /**
     * Kiểm tra vận đơn từ dvvc đã có chưa, nếu chưa có thì tạo bên M32
     *
     * @param InputOrder $inputOrder
     * @return void
     */
    public function mappingTracking(InputOrder $inputOrder);
}
