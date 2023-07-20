<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Gobiz\Support\Helper;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Modules\JNTM\Services\JNTMShippingPartner;
use Modules\JNTP\Services\JNTPShippingPartner;
use Modules\NIJAVAM\Services\NIJAVAMOrderStatus;
use Modules\NIJAVAM\Services\NIJAVAMShippingPartner;
use Modules\Order\Models\Order;
use Modules\SAPI\Jobs\SyncQueryTrackingJob;
use Modules\SAPI\Services\SAPIShippingPartner;
use Modules\Service;
use Modules\ShippingPartner\Models\RelateObjects\Tracking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerException;
use Modules\ShippingPartner\Services\ShippingPartnerProvider;

class TestCommand extends Command
{
    protected $signature = 'test';

    protected $description = 'Test';

    public function handle()
    {
        $trackings = [
            0 => "VJM00126187709",
            1 => "VJM00126187732",
            2 => "VJM00126187766",
            3 => "VJM00126187849",
            4 => "VJM00126187905",
            5 => "VJM00126187988",
            6 => "VJM00126188532",
            7 => "VJM00126188549",
            8 => "VJM00126188613",
            9 => "VJM00126188641"
        ];
        $shippingPartner = ShippingPartner::find(3);
        (new SyncQueryTrackingJob($shippingPartner, $trackings))->handle();


    }

    private function gexMatchSearch($search)
    {
        $arr    = ['huyện đảo', 'huyện', 'thị xã', 'xã'];
        $search = strtolower($search);
        foreach ($arr as $item) {
            $search = str_replace($item, '', $search);
        }
        return trim($search);
    }
}
