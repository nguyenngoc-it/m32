<?php

namespace Modules\LWE\Services;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\MakeSenderShippingTrait;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Modules\JNTP\Services\JNTPConstants;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\AbstractShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\ShippingPartner\Services\OrderStampRenderable;

class LWEShippingPartner extends AbstractShippingPartner implements OrderStampRenderable
{
    use RestApiRequestTrait;
    use MakeSenderShippingTrait;

    /**
     * @var string
     */
    protected $apiUrl = 'https://lweph.com/api/v1/';

    /**
     * @var array
     */
    protected $settings = [
        'access_token' => '',
        'head_sender' => '',
        'sender' => [],
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * LWELastMilePartner constructor.
     * @param string $apiUrl
     * @param array $settings
     */
    public function __construct(array $settings, $apiUrl = null)
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->apiUrl   = $apiUrl ?: $this->apiUrl;

        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';

        $this->http   = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => $headers,
        ]);
        $this->logger = LogService::logger('lwe');
    }

    /**
     * Lấy danh sách các đơn vị vận chuyển được hỗ trợ
     *
     * @return array
     */
    public function getShippingCarriers()
    {
        return [ShippingPartner::CARRIER_LWE, ShippingPartner::CARRIER_LWE_LBC, ShippingPartner::CARRIER_LWE_JNT];
    }

    /**
     * Test connection
     *
     * @throws ShippingPartnerException
     */
    public function test()
    {
        $this->sendRequest(function () {

        });
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
        $request = $this->makeOrderData($order);
        $this->logger->debug('CREATE_ORDER', $request);
        $res = $this->sendRequest(function () use ($request) {
            return $this->http->post($this->settings['access_token'] . '/package-create', ['body' => json_encode($request)]);
        });
        $this->logger->debug('CREATE_ORDER_RESPONSE', $res->getData());

        $success = $res->success();
        if ($success && $res->getData('isSuccessful')) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('data.waybill');
            $order->trackingNo = $res->getData('data.waybill');
            $order->fee        = $res->getData('data.shippingFee');
            $order->sender     = $this->makeSenderShippingPartner($this->settings['sender']);
            $order->query      = $request;
            $order->response   = (array)$res->getData();
            return $order;
        }
        return null;
    }

    /**
     * @param Order $order
     * @return array
     * @throws ShippingPartnerException
     */
    protected function makeOrderData(Order $order)
    {
        if (!$districtName = $this->getDistrictName($order->receiver_district_code)) {
            throw new ShippingPartnerException("LWE: Can not find district of {$order->receiver_district_code}");
        }

        if (!$provinceName = $this->getProvinceName($order->receiver_province_code)) {
            throw new ShippingPartnerException("LWE: Can not find province of {$order->receiver_province_code}");
        }

        return [
            'reference_number' => $this->makeReferenceNumber($order),
            'consignee_name' => (string)$order->receiver_name,
            'consignee_number' => (string)$order->receiver_phone,
            'consignee_address' => $this->makeReceiverAddress($order),
            'package_code' => $this->getPackageCode($order->weight),
            'actual_weight' => floatval($order->weight), // kg => g
            'length' => round($order->length * 100), // m => cm
            'width' => round($order->width * 100), // m => cm
            'height' => round($order->height * 100), // m => cm
            'description' => $this->settings['head_sender'] . '-' . $this->getRemark($order),
            'declared_value' => 0,
            'cod_amount' => floatval($order->cod),
            'province' => $provinceName,
            'municipality' => $districtName,
            'partner' => $this->getPartner($order),
            'destination' => $this->getDestinationByProvince($provinceName),
        ];
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function getPartner(Order $order)
    {
        switch ($order->shipping_carrier_code) {
            case ShippingPartner::CARRIER_LWE_JNT:
            {
                return 'JNT';
            }
            case ShippingPartner::CARRIER_LWE_LBC:
            {
                return 'LBC';
            }
            default :
            {
                return 'LWE';
            }
        }
    }

    /**
     * @param $districtCode
     * @return mixed|null|string
     */
    protected function getDistrictName($districtCode)
    {
        $location = Location::query()
            ->where('code', $districtCode)
            ->first();

        return ($location instanceof Location) ? $location->label : null;
    }

    /**
     * @param $provinceName
     * @return mixed|null|string
     */
    protected function getProvinceName($provinceName)
    {
        $location = Location::query()
            ->where('code', $provinceName)
            ->first();

        return ($location instanceof Location) ? $location->label : null;
    }

    /**
     * @param $provinceName
     * @return mixed
     */
    protected function getDestinationByProvince($provinceName)
    {
        $data = [
            'AKLAN' => 'VIS',
            'ANTIQUE' => 'VIS',
            'BOHOL' => 'VIS',
            'PAMPANGA' => 'NLA',
            'CAVITE' => 'SLA',
            'NUEVA-ECIJA' => 'NLA',
            'PANGASINAN' => 'NLA',
            'NORTH-COTABATO' => 'MIN',
            'TARLAC' => 'NLA',
            'ZAMBALES' => 'NLA',
            'AGUSAN-DEL-NORTE' => 'MIN',
            'AGUSAN-DEL-SUR' => 'MIN',
            'BASILAN' => 'MIN',
            'BUKIDNON' => 'MIN',
            'CAMIGUIN' => 'MIN',
            'COTABATO' => 'MIN',
            'LANAO-DEL-NORTE' => 'MIN',
            'LANAO-DEL-SUR' => 'MIN',
            'MAGUINDANAO' => 'MIN',
            'MISAMIS-OCCIDENTAL' => 'MIN',
            'MISAMIS-ORIENTAL' => 'MIN',
            'SULU' => 'MIN',
            'SURIGAO-DEL-NORTE' => 'MIN',
            'SURIGAO-DEL-SUR' => 'MIN',
            'TAWI-TAWI' => 'MIN',
            'ZAMBOANGA-DEL-NORTE' => 'MIN',
            'ZAMBOANGA-DEL-SUR' => 'MIN',
            'ZAMBOANGA-SIBUGAY' => 'MIN',
            'DINAGAT-ISLANDS' => 'MIN',
            'COMPOSTELA-VALLEY' => 'MIN',
            'CAPIZ' => 'VIS',
            'DAVAO-DEL-NORTE' => 'MIN',
            'DAVAO-DEL-SUR' => 'MIN',
            'DAVAO-ORIENTAL' => 'MIN',
            'SARANGANI' => 'MIN',
            'SOUTH-COTABATO' => 'MIN',
            'SULTAN-KUDARAT' => 'MIN',
            'ALBAY' => 'SLA',
            'CAMARINES-NORTE' => 'SLA',
            'CAMARINES-SUR' => 'SLA',
            'CATANDUANES' => 'SLA',
            'MASBATE' => 'SLA',
            'CEBU' => 'VIS',
            'SORSOGON' => 'SLA',
            'BILIRAN' => 'VIS',
            'EASTERN-SAMAR' => 'VIS',
            'SOUTHERN-LEYTE' => 'VIS',
            'LEYTE' => 'VIS',
            'NORTHERN-SAMAR' => 'VIS',
            'WESTERN-SAMAR' => 'VIS',
            'BATANGAS' => 'SLA',
            'OCCIDENTAL-MINDORO' => 'SLA',
            'ORIENTAL-MINDORO' => 'SLA',
            'ROMBLON' => 'SLA',
            'PALAWAN' => 'SLA',
            'LAGUNA' => 'SLA',
            'MARINDUQUE' => 'SLA',
            'QUEZON' => 'SLA',
            'RIZAL' => 'MMA',
            'APAYAO' => 'NLA',
            'BATANES' => 'NLA',
            'BULACAN' => 'NLA',
            'CAGAYAN' => 'NLA',
            'GUIMARAS' => 'VIS',
            'IFUGAO' => 'NLA',
            'ISABELA' => 'NLA',
            'ILOILO' => 'VIS',
            'MOUNTAIN-PROVINCE' => 'NLA',
            'NUEVA-VIZCAYA' => 'NLA',
            'QUIRINO' => 'NLA',
            'AURORA' => 'NLA',
            'KALINGA' => 'NLA',
            'METRO-MANILA' => 'MMA',
            'BATAAN' => 'NLA',
            'LA-UNION' => 'NLA',
            'ILOCOS-SUR' => 'NLA',
            'NEGROS-ORIENTAL' => 'VIS',
            'BENGUET' => 'NLA',
            'NEGROS-OCCIDENTAL' => 'VIS',
            'ABRA' => 'NLA',
            'ILOCOS-NORTE' => 'NLA',
            'SIQUIJOR' => 'VIS',
        ];

        return Arr::get($data, $provinceName, '');
    }

    /**
     * @param Order $order
     * @return string
     */
    protected function makeReceiverAddress(Order $order)
    {
        $receiver_address = (string)$order->receiver_address;
        if (!empty($order->receiver_district_code)) {
            $receiverDistrict = Location::query()->where('code', $order->receiver_district_code)->first();
            if ($receiverDistrict instanceof Location) {
                $receiver_address .= " - " . $receiverDistrict->label;
                $receiver_address .= " - " . $receiverDistrict->parent->label;
                $receiver_address .= " - " . $receiverDistrict->parent->parent->label;
            }
        }

        return $receiver_address;
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
     * Lấy thông tin package code theo cân nặng
     *
     * @param float $weight
     * @return string
     */
    protected function getPackageCode(float $weight)
    {
        if ($weight < 1) {
            return 'N-PACK SMALL';
        }

        if ($weight < 3) {
            return 'N-PACK LARGE';
        }

        return 'CARGO';
    }

    protected function getRemark(Order $order)
    {
        $contentRemark = '';
        /** @var OrderItem $orderItem */
        foreach ($order->items as $orderItem) {
            $contentRemark .= $orderItem->quantity . ' ' . $orderItem->name . ' / ';
        }
        if ($contentRemark) {
            $contentRemark = substr($contentRemark, 0, -3);
        }
        $totalQuantity = $order->items->sum('quantity');
        return 'Total ' . $totalQuantity . '-' . $contentRemark . '-' . $order->cod;
    }

    /**
     * Lấy thông tin đơn từ bên DVVC
     *
     * @param Order $order
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerException
     */
    public function getOrderInfo(Order $order)
    {
        $request = ['waybill' => $order->tracking_no];
        $res     = $this->sendRequest(function () use ($request) {
            return $this->http->post($this->settings['access_token'] . '/history', ['body' => json_encode($request)]);
        });

        $order = new ShippingPartnerOrder();

        $success = $res->getData('isSuccessful');
        if ($success == true) {
            $order->code       = $res->getData('data.package.waybill_number');
            $order->trackingNo = $res->getData('data.package.waybill_number');
            $order->fee        = $res->getData('data.package.shippingFee');

            $history       = $res->getData('data.history');
            $statusId      = $this->getStatusId($history);
            $order->status = Service::lwe()->mapStatus($statusId);

            $this->logger->info('LWE - Get Order Info : ', compact('history', 'statusId', 'order', 'request'));
        } else {
            $message = $res->getData('message');
            $this->logger->info('LWE - Get Order Info Error: ' . $message, $request);

            throw new ShippingPartnerException("LWE - Get Order Info Error - {$message}");
        }

        return $order;
    }

    /**
     * @param $histories
     * @return int|mixed
     */
    protected function getStatusId($histories)
    {
        $histories    = (array)$histories;
        $listTime     = 0;
        $lastStatusId = '';
        foreach ($histories as $history) {
            $history   = (array)$history;
            $dateAdded = strtotime($history['date_added']);
            if ($dateAdded > $listTime) {
                $listTime     = $dateAdded;
                $lastStatusId = $history['status_id'];
            }
        }

        return $lastStatusId;
    }


    /**
     * Render danh sách tem đơn
     *
     * @param Order[] $orders
     * @return Response
     */
    public function renderOrderStamps(array $orders)
    {
        $html = '';
        if ($orders) {
            $html = '';
            foreach ($orders as $order) {
                $request = ['waybill' => $order->tracking_no];
                $res     = $this->sendRequest(function () use ($request) {
                    return $this->http->post($this->settings['access_token'] . '/history', ['body' => json_encode($request)]);
                });

                $dataPackage = $res->getData('data.package');

                if ($dataPackage) {

                    $request = ['waybill_number' => $order->tracking_no];
                    $res     = $this->sendRequest(function () use ($request, $order) {
                        return $this->http->get($this->settings['access_token'] . '/labe-sticker?waybill_number=' . $order->tracking_no);
                    });

                    $html .= $res->getBody();
                }
            }
        }

        $search  = '<link rel="stylesheet" type="text/css" href="/css/print.css">';
        $replace = '<link rel="stylesheet" type="text/css" href="https://lweph.com/css/print.css">
                    <link rel="stylesheet" type="text/css" href="' .  \Modules\Service::app()->assetUrl('css/lwe-print.css') . '">';

        $html = str_replace($search, $replace, $html);

        return view('order-stamps/lwe', ['html' => $html]);
    }

    /**
     * Lấy thông tin trackings từ DVVC
     *
     * @param array $trackings
     * @return Tracking[]|array
     * @throws ShippingPartnerException
     */
    public function getTrackings(array $trackings)
    {
        $jsonParams = [
            'waybill_number' => $trackings,
        ];
        $this->logger->debug('TRACKING', $jsonParams);

        $res = $this->sendRequest(function () use ($jsonParams) {
            return $this->http->post($this->settings['access_token'] . '/history', ['body' => json_encode($jsonParams)]);
        });

        $this->logger->debug('TRACKING_RESPONSE', $res->getData());
        $result = [];
        if ($res->success() && $tracesList = $res->getData('responseitems', [])) {
            foreach ($tracesList as $trace) {
                $trackingCode = Arr::get($trace, 'billcode');
                $originStatus = Arr::get($trace, 'details.0.scantype');
                $status       = Service::lwe()->mapStatus($originStatus);
                if ($trackingCode && $originStatus && $status) {
                    $tracking = new Tracking($trackingCode, $originStatus, $status);
                    $result[] = $tracking;
                }
            }
        }
        return $result;
    }
}
