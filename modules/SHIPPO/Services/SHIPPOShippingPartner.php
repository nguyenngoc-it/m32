<?php

namespace Modules\SHIPPO\Services;

use Carbon\Carbon;
use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\ShippingPartner\Services\ShippingPartnerSize;

class SHIPPOShippingPartner extends AbstractShippingPartner
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://dathang.orderhang.com/';
    /** @var ShippingPartner */
    protected $shippingPartner;
    protected $addressId, $services = [];

    /**
     * @var array
     */
    protected $settings = [
        'base_url' => '',
        'client_id' => '',
        'username' => '',
        'password' => '',
        'token' => '',
        'sender' => [],
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * GHNLastMilePartner constructor.
     * @param ShippingPartner $shippingPartner
     * @param string $apiUrl
     * @throws GuzzleException
     * @throws ShippingPartnerException
     */
    public function __construct(ShippingPartner $shippingPartner, $apiUrl = null)
    {
        $this->shippingPartner = $shippingPartner;
        $this->settings        = array_merge($this->settings, $this->shippingPartner->settings);
        $this->apiUrl          = $this->settings['base_url'] ? $this->settings['base_url'] : $apiUrl;
        $this->setClientId();
        $this->updateToken($shippingPartner->updated_at);
        $this->http   = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['token'],
                'Content-Type' => 'application/json'
            ],
        ]);
        $this->logger = LogService::logger('shippo');
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
     * Cập nhật token cho shipping partner nếu đã quá hạn
     *
     * @param Carbon $updatedAt
     * @throws GuzzleException
     * @throws ShippingPartnerException
     */
    public function updateToken(Carbon $updatedAt)
    {
        if (empty($this->settings['token']) || $updatedAt->timestamp < Carbon::now()->timestamp - 80000) {
            $this->settings['token']         = $this->getToken();
            $this->shippingPartner->settings = $this->settings;
            $this->shippingPartner->save();
        }
    }

    /**
     * @return string
     * @throws GuzzleException
     * @throws ShippingPartnerException
     */
    public function getToken()
    {
        $response = Helper::quickCurl($this->apiUrl, 'oauth/token', 'post', [], [
            'username' => $this->settings['username'],
            'password' => $this->settings['password'],
            'grant_type' => 'password',
            'scope' => 'all',
            'client_id' => $this->settings['client_id']
        ]);

        if (!empty($response['access_token'])) {
            return $response['access_token'];
        }

        throw new ShippingPartnerException('Get Access Token Failed!');
    }

    /**
     * @return void
     * @throws ShippingPartnerException
     * @throws GuzzleException
     */
    public function setClientId()
    {
        if (empty($this->shippingPartner->settings['client_id'])) {
            $response = Helper::quickCurl($this->apiUrl, 'api/tenants/current');
            if (!empty($response['id'])) {
                $this->settings['client_id']     = $response['id'];
                $this->shippingPartner->settings = $this->settings;
                $this->shippingPartner->save();
            } else {
                throw new ShippingPartnerException('Client Id empty!');
            }
        }
    }

    /**
     * @throws ShippingPartnerException
     */
    protected function setAddressIdAndServices()
    {
        $response = Helper::quickMultipleCurls($this->apiUrl, [
            'address' => 'api/customer/addresses?size=25&sort=defaultAddress:desc,createdAt:desc',
            'services' => 'api/categories/shipment_services?size=1000&sort=position:asc'
        ], [
            'Authorization' => 'Bearer ' . $this->settings['token'],
            'Content-Type' => 'application/json'
        ]);

        if (empty($response) || empty($response['services']) || empty($response['address'])) {
            throw new ShippingPartnerException('Services not support!');
        }

        foreach ($response['address'] as $address) {
            if ($address['defaultAddress']) {
                $this->addressId = $address['id'];
                break;
            }
        }

        foreach ($response['services'] as $service) {
            if ($service['defaultApplied']) {
                $this->services[] = $service['code'];
            }
        }
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function makeOrderData(Order $order)
    {
        $items = $order->items->map(function (OrderItem $orderItem) {
            return [
                'itemname' => $orderItem->name,
                'number' => $orderItem->quantity,
                'itemvalue' => $orderItem->price,
            ];
        });

        return [
            'addressId' => $this->addressId,
            'services' => $this->services,
            'items' => $items
        ];
    }

    /**
     * Tạo 1 bản đơn nháp để xác nhận đặt được đơn rồi mới cho đặt đơn
     *
     * @param Order $order
     * @return array|mixed|null
     * @throws ShippingPartnerException
     */
    public function getDraftOrder(Order $order)
    {
        $this->setAddressIdAndServices();
        $jsonParams = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $jsonParams);

        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post('api/customer/draft_shipments', ['json' => $jsonParams]);
        });

        if ($res->success()) {
            return $res->getData('id');
        }
        return null;
    }

    /**
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_SHIPPO];
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
        $draftOrderId = $this->getDraftOrder($order);
        if (empty($draftOrderId)) {
            throw new ShippingPartnerException('Draft order id exists!');
        }

        $res = $this->sendRequest(function () use ($draftOrderId) {
            return $this->http->post('api/customer/shipments', [
                'json' => ['draftShipmentId' => $draftOrderId]
            ]);
        });

        $success = $res->getData('success');
        if ($success) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('code');
            $order->trackingNo = $res->getData('code');
            $order->fee        = $res->getData('totalFee');
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = (array)$draftOrderId;
            $order->response   = (array)$res->getData();
            return $order;
        }
        return null;
    }

    /**
     * Get order's stamp url
     *
     * @param Order $order
     * @return string
     * @throws ShippingPartnerException
     */
    public function getOrderStamp(Order $order)
    {
        $token = $this->sendRequest(function () use ($order) {
            return $this->http->post('shiip/public-api/v2/a5/gen-token', ['json' => ['order_codes' => [$order->code]]]);
        })->getData('data.token');

        return "{$this->apiUrl}a5/public-api/printA5?token={$token}";
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
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     */
    public function getTrackings(array $trackings)
    {
        // TODO: Implement getTrackings() method.
    }
}
