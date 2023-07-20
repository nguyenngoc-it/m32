<?php

namespace Modules\JNTI\Services;

use Carbon\Carbon;
use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\Helper;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\OrderStampRenderable;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;

class JNTIShippingPartner extends AbstractShippingPartner implements OrderStampRenderable
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://test-jk.jet.co.id/jts-idn-blibli-api/api/';

    /**
     * @var array
     */
    protected $settings = [
        'api_key' => '',
        'key' => '',
        'username' => '',
        'sender' => [],
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * GHNLastMilePartner constructor.
     * @param string $apiUrl
     * @param array $settings
     */
    public function __construct(array $settings, $apiUrl = null)
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->apiUrl   = $apiUrl ?: $this->apiUrl;
        $this->http     = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
        $this->logger   = LogService::logger('jnti');
    }

    /**
     * Lấy tham số bắt buộc cho mỗi request
     * Do J&t không truyền qua headers nên sẽ phải gán qua form_params với
     * Json parameters
     *
     * @param array $jsonParams
     * @return array
     */
    protected function getProtocolParameters(array $jsonParams)
    {
        $dataDigest = base64_encode(md5(json_encode($jsonParams) . $this->settings['key']));
        return [
            'data_param' => json_encode($jsonParams),
            'data_sign' => $dataDigest
        ];
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        if (!$receiverWardCode = $this->getJNTILocationId($order->receiver_ward_code)) {
            throw new ShippingPartnerException("JNTI: Can not find ward of {$order->receiver_ward_code}");
        }

        if (!$receiverDistrictCode = $this->getJNTILocationId($order->receiver_district_code)) {
            throw new ShippingPartnerException("JNTI: Can not find district of {$order->receiver_district_code}");
        }

        if (!$receiverProvinceCode = $this->getJNTILocationId($order->receiver_province_code)) {
            throw new ShippingPartnerException("JNTI: Can not find province receiver of {$order->receiver_province_code}");
        }

        $itemName           = $order->items->first()->name;
        $countQuantityItems = (int)$order->items->sum('quantity');
        $totalQuantity      = $countQuantityItems ?: 1;

        $sender = $this->settings['sender'];
        if (!$senderDistrictCode = $this->getJNTILocationId($sender[ShippingPartner::SENDER_DISTRICT_CODE])) {
            throw new ShippingPartnerException("JNTI: Can not find district of {$sender[ShippingPartner::SENDER_DISTRICT_CODE]}");
        }
        return [
            'detail' => [[
                'username' => $this->settings['username'],
                'api_key' => $this->settings['api_key'],
                'orderid' => $this->makeReferenceNumber($order),
                'shipper_name' => $sender[ShippingPartner::SENDER_NAME],
                'shipper_contact' => $sender[ShippingPartner::SENDER_NAME],
                'shipper_phone' => $sender[ShippingPartner::SENDER_PHONE],
                'shipper_addr' => $sender[ShippingPartner::SENDER_ADDRESS],
                'origin_code' => $senderDistrictCode,
                'receiver_name' => $order->receiver_name,
                'receiver_phone' => $order->receiver_phone,
                'receiver_addr' => $order->receiver_address,
                'receiver_zip' => '00000',
                'destination_code' => $receiverDistrictCode,
                'receiver_area' => $receiverWardCode,
                'qty' => $totalQuantity,
                'weight' => $totalQuantity,
                'goodsdesc' => Service::jnti()->getRemark($order),
                'servicetype' => '1',
//                'insurance' => round(0.002 * $order->cod, 2),
                'insurance' => 0,
                'orderdate' => Carbon::now()->format('Y-m-d H:i:s'),
                'item_name' => $itemName,
                'cod' => $order->cod,
                'sendstarttime' => Carbon::now()->addHours(1)->format('Y-m-d H:i:s'),
                'sendendtime' => Carbon::now()->addHours(48)->format('Y-m-d H:i:s'),
                'expresstype' => '1',
                'goodsvalue' => $order->cod,
            ]]
        ];
    }

    /**
     * @param string $locationCode
     * @return string|null
     */
    protected function getJNTILocationId($locationCode)
    {
        /** @var ShippingPartnerLocation $location */
        $location = ShippingPartnerLocation::query()
            ->where('partner_code', ShippingPartner::PARTNER_JNTI)
            ->where('location_code', $locationCode)
            ->first();

        return $location ? ($location->identity ?: $location->name) : $locationCode;
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
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_JNTI];
    }

    /**
     * Test connection
     *
     */
    public function test()
    {
        return;
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
            return $this->http->post('api/order/create', [
                'form_params' => array_merge($this->getProtocolParameters($jsonParams), $jsonParams),
                'curl' => [
                    CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'
                ]
            ]);
        });
        $this->logger->debug('RESPONSE', $res->getData());

        $success = $res->success();
        if ($success && $trackingNo = $res->getData('detail.0.awb_no')) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('detail.0.orderid');
            $order->trackingNo = $trackingNo;
            $order->fee        = 0;
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = $jsonParams;
            $order->response   = (array)$res->getData();
            return $order;
        }
        return null;
    }

    /**
     * Render danh sách tem đơn
     *
     * @param Order[] $orders
     * @return Response
     */
    public function renderOrderStamps(array $orders)
    {
        return view('order-stamps/jnti', ['orders' => $orders]);
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
     * @throws GuzzleException
     * @throws ShippingPartnerException
     */
    public function getTrackings(array $trackings)
    {
        $tracking   = $trackings[0];
        $jsonParams = [
            'awb' => $tracking,
            'eccompanyid' => 'TECHVELA'
        ];
        $this->logger->debug('TRACKING', $jsonParams);

        $res = $this->sendRequest(function () use ($jsonParams) {
            return Helper::quickCurl(config('services.jnti.api_tracking'),
                'track/trackAction!tracking.action',
                'post',
                [],
                $jsonParams,
                false
            );
        });
        $this->logger->debug('RESPONSE', $res->getData());

        $result = [];
        if ($res->success() && is_array($res->getData())) {
            $history = Arr::get($res->getData(), 'history');
            if ($history) {
                $trace        = end($history);
                $trackingCode = $tracking;
                $originStatus = (string)Arr::get($trace, 'status_code');
                $status       = Service::jntp()->mapStatus($originStatus);
                if ($trackingCode && $originStatus && $status) {
                    $tracking = new Tracking($trackingCode, $originStatus, $status);
                    $result[] = $tracking;
                }
            }
        }

        return $result;
    }
}
