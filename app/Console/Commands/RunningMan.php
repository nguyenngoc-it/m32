<?php

namespace App\Console\Commands;

use App\Jobs\AddLocationJob;
use Gobiz\Log\LogService;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Console\Command;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Psr\Log\LoggerInterface;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * Chạy những xử lý tức thời mà chưa biết nhu cầu dùng lại nhiều hay không
 *
 * 1. Giả lập webhook cập nhật vận đơn của dvvc - webhoohEmulator
 *
 * Class RunningMan
 * @package App\Console\Commands
 */
class RunningMan extends Command
{
    protected $signature = 'running_man {func=1 : stt function} {shipping_partner=jntp : 1} {tracking_no=xxx  : 1} {status=xxx : 1} {--shipping_partner_code=JNTT : 2}';
    protected $description = 'Chạy những xử lý tức thời mà chưa biết nhu cầu dùng lại nhiều hay không';


    /**
     * @throws WorkflowException
     */
    public function handle()
    {
        $func = (int)$this->argument('func');
        switch ($func) {
            case 1:
                $this->webhoohEmulator();
            case 2:
                $this->addLabelLocations();
            case 3:
                $this->updateJNTPShippingLocations();
        }
    }

    /**
     * @return LoggerInterface
     */
    protected function logger()
    {
        return LogService::logger('tools');
    }

    /**
     * @return array
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    public function addLabelLocations()
    {
        $shippingPartnerCode = strtoupper($this->option('shipping_partner_code'));
        dispatch(new AddLocationJob($shippingPartnerCode));
    }


    /**
     * 1. Giả lập webhook cập nhật vận đơn của dvvc - webhoohEmulator
     *
     * @throws WorkflowException
     */
    protected function webhoohEmulator()
    {
        $shippingPartnerCode = strtoupper($this->argument('shipping_partner'));
        $trackingNo          = $this->argument('tracking_no');
        $originStatus        = $this->argument('status');

        /** @var Order|null $order */
        $order = Order::query()->where(
            [
                'shipping_partner_code' => $shippingPartnerCode,
                'tracking_no' => $trackingNo
            ]
        )->first();
        if (empty($trackingNo) || empty($order) || empty($originStatus)) {
            $this->error('not found Order with tracking ' . $trackingNo);
            return;
        }

        $tracking = null;
        switch ($shippingPartnerCode) {
            case ShippingPartner::CARRIER_JNTM:
                $status = Service::jntm()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_JNTP:
                $status = Service::jntp()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_JNTT:
                $status = Service::jntt()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_JNTI:
                $status = Service::jnti()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_SAPI:
                $status = Service::sapi()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_FLASH:
                $status = Service::flash()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_JNEI:
                $status = Service::jnei()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_LWE:
                $status = Service::lwe()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_SNAPPY:
                $status = Service::snappy()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_GHN:
                $status = Service::ghn()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_GGE:
                $status = Service::gge()->mapStatus($originStatus);
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;
            case ShippingPartner::CARRIER_JNTC:
                $status = Service::jntc()->mapStatus(strtolower($originStatus));
                if ($originStatus && $status) {
                    $tracking = new Tracking($trackingNo, $originStatus, $status);
                }
                break;

        }

        if ($tracking && ($order->status != $tracking->status) && $order->canChangeStatus($tracking->status)) {
            $order->original_status = $tracking->originStatus;
            $order->save();
            $order->changeStatus($tracking->status, Service::user()->getSystemUser());
            $this->info('update status for tracking ' . $trackingNo);
        }
    }

    protected function updateJNTPShippingLocations()
    {
        (new FastExcel)->import(storage_path('jntp_shipping_locations_change.xlsx'), function ($line) {
            
            $provinceOld = $line['PROVINCE OLD'];
            $districtOld = $line['CITY OLD'];
            $wardOld     = $line['AREA NAME OLD'];
            $provinceNew = $line['PROVINCE NEW'];
            $districtNew = $line['CITY NEW'];
            $wardNew     = $line['AREA NAME NEW'];

            // Check Shipping Locations 
            $shippingLocationProvince = ShippingPartnerLocation::where('partner_code', 'JNTP')
                                                        ->where('type', 'PROVINCE')
                                                        ->where('identity', $provinceOld)
                                                        ->first();
            if ($shippingLocationProvince) {
                $shippingLocationProvince->identity = $provinceNew;
                $shippingLocationProvince->code = $provinceNew;
                $shippingLocationProvince->name = $provinceNew;
                $shippingLocationProvince->save();
                $this->info('update PROVINCE from :' . $provinceOld . ' - to: ' . $provinceNew);
            }

            $shippingLocationDistrict = ShippingPartnerLocation::where('partner_code', 'JNTP')
                                                        ->where('type', 'DISTRICT')
                                                        ->where('identity', $districtOld)
                                                        ->first();
            if ($shippingLocationDistrict) {
                $shippingLocationDistrict->identity = $districtNew;
                $shippingLocationDistrict->code = $districtNew;
                $shippingLocationDistrict->name = $districtNew;
                $shippingLocationDistrict->save();
                $this->info('update DISTRICT from :' . $districtOld . ' - to: ' . $districtNew);
            }

            $shippingLocationWard = ShippingPartnerLocation::where('partner_code', 'JNTP')
                                                        ->where('type', 'WARD')
                                                        ->where('identity', $wardOld)
                                                        ->first();
            if ($shippingLocationWard) {
                $shippingLocationWard->identity = $wardNew;
                $shippingLocationWard->code = $wardNew;
                $shippingLocationWard->name = $wardNew;
                $shippingLocationWard->save();
                $this->info('update WARD from :' . $wardOld . ' - to: ' . $wardNew);
            }
        });
    }

}
