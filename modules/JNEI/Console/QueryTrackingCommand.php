<?php

namespace Modules\JNEI\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\JNEI\Jobs\SyncQueryTrackingJob;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;

class QueryTrackingCommand extends Command
{
    protected $signature = 'jnei:query-trackings';

    protected $description = 'Lấy danh sách đơn hàng của JNEI để cập nhật thông tin trạng thái';

    public function handle()
    {
        $shippingPartners = ShippingPartner::query()->where('partner_code', ShippingPartner::CARRIER_JNEI)->get();
        /** @var ShippingPartner $shippingPartner */
        foreach ($shippingPartners as $shippingPartner) {
            $orders      = $shippingPartner->orders()
                ->whereNotNull('tracking_no')
                ->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_RETURNED, Order::STATUS_CANCEL])
                ->where('created_at', '>', Carbon::now()->subWeek())->orderBy('orders.id');
            $trackingNos = $orders->pluck('tracking_no')->all();
            if (count($trackingNos) > 10) {
                foreach (array_chunk($trackingNos, 10) as $item) {
                    dispatch(new SyncQueryTrackingJob($shippingPartner, $item));
                }
            } else {
                dispatch(new SyncQueryTrackingJob($shippingPartner, $trackingNos));
            }
        }
    }

}
