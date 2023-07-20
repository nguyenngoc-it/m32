<?php

namespace Modules\Order\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Order\Jobs\SyncOrderStatusJob;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;

class SyncOrderStatusCommand extends Command
{
    protected $signature = 'order:sync-status 
    {shipping-partner-code}
    {--from= : VD: 2018-01-02} 
    {--to= : VD: 2018-03-04}
    {--status= : VD: READY_TO_PICK,PICKED_UP} 
    {--batch=100}
    ';

    protected $description = 'sync order status from shipping partner';

    public function handle()
    {
        $shippingPartnerCode = $this->argument('shipping-partner-code');
        $shippingPartnerIds = ShippingPartner::query()->where('code', trim($shippingPartnerCode))->pluck('id')->toArray();
        if(empty($shippingPartnerIds)) {
            $this->info('ShippingPartner Invalid');
            return;
        }
        $batch = (!empty($this->option('batch'))) ? intval($this->option('batch')): 100;

        $query = Order::query()
            ->whereIn('shipping_partner_id', $shippingPartnerIds);

        $query = $this->makeQueryByTime($query, $this->option('from'), $this->option('to'));
        $query = $this->makeQueryByStatus($query, $this->option('status'));

        $this->info('Start ');

        $query->chunk($batch, function (Collection $orders) {
            $orders->map(function ($order) {

                $this->info('Order '.$order->id);

                dispatch(new SyncOrderStatusJob($order->id));
            });
        });

        $this->info('Done');
    }

    /**
     * @param Builder $query
     * @param $from
     * @param $to
     * @return Builder
     */
    protected function makeQueryByTime(Builder $query, $from, $to)
    {
        if (!$from) {
            $from = Carbon::now()->subMonths(3)->toDateTimeString();
        }

        if (!$to) {
            $to = Carbon::now()->toDateTimeString();
        }

        $query->where('created_at', '<=', $to);
        $query->where('created_at', '>=', $from);

        return $query;
    }

    /**
     * @param Builder $query
     * @param $status
     * @return Builder
     */
    protected function makeQueryByStatus(Builder $query, $status)
    {
        if(empty($status)) {
            $query->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_RETURNING, Order::STATUS_RETURNED]);
            return $query;
        }

        if (Str::contains($status, ',')) {
            $status = array_filter(array_map('trim', explode(',', $status)));

            $query->whereIn('status', $status);
        }  else {
            $query->where('status', $status);
        }

        return $query;
    }
}
