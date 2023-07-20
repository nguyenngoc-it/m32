<?php

namespace Modules\JNTC\Console;

use Carbon\Carbon;
use function Clue\StreamFilter\fun;
use Illuminate\Console\Command;
use Modules\JNTC\Jobs\SyncQueryTrackingJob;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;

class QueryTrackingCommand extends Command
{
    protected $signature = 'jntc:query-trackings';

    protected $description = 'Lấy danh sách đơn hàng của JNTC để cập nhật thông tin trạng thái';

    public function handle()
    {
        $shippingPartnerIds = ShippingPartner::query()->where('partner_code', ShippingPartner::CARRIER_JNTC)->pluck('id')->toArray();

        Order::query()
            ->whereIn('shipping_partner_id', $shippingPartnerIds)
            ->whereNotNull('tracking_no')
            ->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_RETURNED, Order::STATUS_CANCEL])
            ->where('created_at', '>', Carbon::now()->subMonth())
            ->chunkById(100, function ($orders)  {
                 foreach ($orders as $order) {
                     dispatch(new SyncQueryTrackingJob($order->id));
                 }
        });
    }

}
