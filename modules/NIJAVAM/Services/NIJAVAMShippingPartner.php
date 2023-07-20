<?php /** @noinspection ALL */

namespace Modules\NIJAVAM\Services;

use Carbon\Carbon;
use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\OrderStampRenderable;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\ShippingPartner\Services\ShippingPartnerSize;

class NIJAVAMShippingPartner extends AbstractShippingPartner implements OrderStampRenderable
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://api-sandbox.ninjavan.co/sg/';

    /**
     * @var array
     */
    protected $settings = [
        'client_id' => '',
        'client_key' => '',
        'sender' => [],
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * GHNLastMilePartner constructor.
     * @param string|null $apiUrl
     * @param array $settings
     */
    public function __construct(array $settings, string $apiUrl = null)
    {
        $this->logger   = LogService::logger('nijavam');
        $this->settings = array_merge($this->settings, $settings);
        $this->apiUrl   = $apiUrl ?: $this->apiUrl;
        $this->http     = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getToken(),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function makeOrderData(Order $order)
    {
        $items = $order->items->map(function (OrderItem $orderItem) {
            return [
                'item_description' => $orderItem->name,
                'quantity' => $orderItem->quantity,
                'is_dangerous_good' => false
            ];
        });
        /** @var Location|null $senderCityLocation */
        $senderCityLocation = Location::query()->where('code', $this->settings['sender']['district_code'])->first();
        /** @var Location|null $senderProvinceLocation */
        $senderProvinceLocation = Location::query()->where('code', $this->settings['sender']['province_code'])->first();

        return [
            'service_type' => 'Parcel',
            'service_level' => 'Standard',
            'requested_tracking_number' => $order->ref,
            'from' => [
                'name' => $this->settings['sender']['name'],
                'phone_number' => $this->settings['sender']['phone'],
                'address' => [
                    'address1' => $this->settings['sender']['address'],
                    'city' => $senderCityLocation ? $senderCityLocation->label : '',
                    'province' => $senderProvinceLocation ? $senderProvinceLocation->label : '',
                    'country' => 'MY',
                    'postcode' => $this->settings['postcode']
                ]
            ],
            'to' => [
                'name' => $order->receiver_name,
                'phone_number' => $order->receiver_phone,
                'address' => [
                    'address1' => $order->receiver_address,
                    'city' => $order->receiverWard ? $order->receiverWard->parent->label : '',
                    'province' => $order->receiverWard ? $order->receiverWard->parent->parent->label : '',
                    'country' => 'MY',
                    'postcode' => $order->receiver_postal_code ?? ''
                ],
            ],
            'parcel_job' => [
                'is_pickup_required' => true,
                'pickup_service_type' => 'Scheduled',
                'pickup_service_level' => 'Standard',
                'pickup_date' => Carbon::now()->addDay()->format('Y-m-d'),
                'pickup_timeslot' => [
                    'start_time' => '09:00',
                    'end_time' => '22:00',
                    'timezone' => 'Asia/Kuala_Lumpur'
                ],
                'delivery_start_date' => Carbon::now()->addDay()->format('Y-m-d'),
                'delivery_timeslot' => [
                    'start_time' => '09:00',
                    'end_time' => '22:00',
                    'timezone' => 'Asia/Kuala_Lumpur'
                ],
                'dimensions' => [
                    'weight' => $order->weight
                ],
                'items' => $items,
                'cash_on_delivery' => round($order->cod, 2)
            ],
        ];
    }

    /**
     * @param Closure $handler
     * @return RestApiResponse
     * @throws ShippingPartnerException
     */
    protected function sendRequest(Closure $handler)
    {
        return $this->request($handler);
    }

    /**
     * @return string|mixed
     * @throws ShippingPartnerException
     */
    protected function getToken()
    {
        $token = Cache::get('token_nijavam_' . $this->settings['client_id']);
        if ($token) {
            return $token;
        }
        $jsonParams = [
            'client_id' => $this->settings['client_id'],
            'client_secret' => $this->settings['client_key'],
            'grant_type' => 'client_credentials'
        ];
        $this->logger->debug('MAKE_TOKEN', $jsonParams);
        $res = $this->sendRequest(function () use ($jsonParams) {
            return Helper::quickCurl($this->apiUrl, '2.0/oauth/access_token', 'post', [], $jsonParams, false);
        });
        $this->logger->debug('MAKE_TOKEN_RESPONSE', $res->getData());
        $success = $res->success();
        if ($success) {
            $token = $res->getData('access_token');
            if ($token) {
                Cache::put('token_nijavam_' . $this->settings['client_id'], $token, 60 * 60 * 100);
                return $token;
            }
        }
        throw new ShippingPartnerException('not found token...');
    }

    /**
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_NIJAVAM];
    }

    /**
     * Test connection
     *
     */
    public function test()
    {

    }

    /**
     * Create order
     *
     * @param Order $order
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerException
     */
    public function createOrder(Order $order)
    {
        $jsonParams = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $jsonParams);
        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('4.1/orders', [
                'json' => $jsonParams
            ]);
        });
        $this->logger->debug('CREATE_ORDER_RESPONSE ' . $order->ref, $res->getData());

        $success = $res->success();
        if ($success == true) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('tracking_number');
            $order->trackingNo = $res->getData('tracking_number');
            $order->fee        = 0;
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = $jsonParams;
            $order->response   = (array)$res->getData();
            return $order;
        } else {
            if ($this->errorTokenInvalid($res->getData())) {
                Cache::forget('token_nijavam_' . $this->settings['client_id']);
            }
            throw new ShippingPartnerException("NIJAVAM - Create Order Error");
        }
        return null;
    }

    /**
     * @param array $res
     * @return bool
     */
    protected function errorTokenInvalid(array $res)
    {
        $title = Arr::get($res, 'error.title');
        return $title == 'ACCESS_TOKEN_ERR';
    }

    /**
     * Get order's stamp url
     *
     * @param Order $order
     * @return string
     * @throws ShippingPartnerException
     */
    // public function getOrderStamp(Order $order)
    // {
    //     $token = $this->sendRequest(function () use ($order) {
    //         return $this->http->post('shiip/public-api/v2/a5/gen-token', ['json' => ['order_codes' => [$order->code]]]);
    //     })->getData('data.token');

    //     return "{$this->apiUrl}a5/public-api/printA5?token={$token}";
    // }

    /**
     * Render danh sách tem đơn
     *
     * @param Order[] $orders
     * @return Response
     */
    public function renderOrderStamps(array $orders)
    {
        return view('order-stamps/ninjavanm', ['orders' => $orders]);
    }

    /**
     * @param array $waybills
     */
    public function getWaybills(array $waybills)
    {
        $jsonParams = [
            'eccompanyid' => $this->settings['eccompanyid'],
            'customerid' => $this->settings['customerid'],
            'billcode' => implode(',', $waybills)
        ];

        $this->logger->debug('QUERY_WAYBILL', $jsonParams);
    }

    /**
     * Tính phí VC
     * @param Location $senderWard
     * @param Location $receiveWard
     * @param ShippingPartnerSize $shippingPartnerSize
     * @return float|mixed
     */
    public function shippingFee(Location $senderWard, Location $receiveWard, ShippingPartnerSize $shippingPartnerSize)
    {
        return null;
    }


    /**
     * Lấy thông tin đơn từ bên DVVC
     *
     * @param Order $order
     * @return ShippingPartnerOrder
     */
    public function getOrderInfo(Order $order)
    {
        $order = new ShippingPartnerOrder();
        return $order;
    }

    /**
     * Lấy url tem của danh sách đơn
     *
     * @param Order[] $orders
     * @return string|null
     * @throws ShippingPartnerException
     */
    // public function getOrderStampsUrl(array $orders)
    // {
    //     // TODO: Implement getOrderStampsUrl() method.
    // }

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     */
    public function getTrackings(array $trackings)
    {
        $this->logger->debug('TRACKING', $trackings);
        $queryString = '';
        foreach ($trackings as $tracking) {
            $queryString .= 'tracking_number=' . $tracking . '&';
        }
        $queryString = substr($queryString, 0, -1);

        $res = $this->sendRequest(function () use ($queryString) {
            return $this->http->get('1.0/orders/tracking-events?' . $queryString);
        });
        /**
         * Save log response
         */
        $data = $res->getData();
        $this->logger->debug('TRACKING_RESPONSE', $data);

        $result = [];
        if ($res->success() && $tracesList = Arr::get($data, 'data')) {
            foreach ($tracesList as $trace) {
                $lastTrace    = end($trace['events']);
                $trackingCode = Arr::get($lastTrace, 'tracking_number');
                $originStatus = Arr::get($lastTrace, 'status');
                $status       = Service::nijavam()->mapStatus($originStatus);
                if ($trackingCode && $originStatus && $status) {
                    $tracking = new Tracking($trackingCode, $originStatus, $status);
                    $result[] = $tracking;
                }
            }
        }
        return $result;
    }
}
