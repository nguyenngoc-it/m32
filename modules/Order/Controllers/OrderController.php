<?php

namespace Modules\Order\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Application\Model\Application;
use Modules\Order\Models\Order;
use Modules\Order\Transformers\ListOrdersTransformer;
use Modules\Order\Validators\ListOrdersValidator;
use Modules\Service;
use Modules\ShippingPartner\Services\OrderStampRenderable;

class OrderController extends Controller
{

    /**
     * @param Application $application
     * @return JsonResponse
     */
    public function index(Application $application)
    {
        $filter  = $this->getQueryFilter();
        $filter['application_id'] = $application->id;
        $results = Service::order()->listOrders($filter, $application);

        return $this->response()->success([
            'orders' => array_map(function (Order $order) {
                return (new ListOrdersTransformer())->transform($order);
            }, $results->items()),
            'pagination' => $results
        ]);
    }

    /**
     * Trả về danh sách các trạng thái của đơn
     */
    public function listStatus()
    {
        return $this->response()->success([
            Order::STATUS_CREATING,
            Order::STATUS_READY_TO_PICK,
            Order::STATUS_DELIVERING,
            Order::STATUS_DELIVERED,
            Order::STATUS_RETURNING,
            Order::STATUS_RETURNED,
            Order::STATUS_ERROR,
            Order::STATUS_CANCEL
        ]);
    }

    /**
     * Tạo filter để query order
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListOrdersValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        return $filter;
    }

    public function stamps()
    {
        $token = $this->request()->get('token');
        $orderIds = Service::app()->tokenGenerator()->parse($token);
        $orderIds = explode(',', $orderIds);
        $orders = Order::query()->whereIn('id', $orderIds)->get();

        if ($orders->isEmpty()) {
            return $this->response()->error('ORDERS_NOT_FOUND');
        }

        if ($orders->pluck('shipping_partner_id')->unique()->count() > 1) {
            return $this->response()->error('ORDERS_NOT_BELONG_TO_SAME_SHIPPING_PARTNER');
        }

        $shippingPartner = $orders->first()->shippingPartner->partner();
        if (!$shippingPartner instanceof OrderStampRenderable) {
            return $this->response()->error('SHIPPING_PARTNER_DOEST_NOT_SUPPORT_STAMP');
        }

        return $shippingPartner->renderOrderStamps($orders->all());
    }
}
