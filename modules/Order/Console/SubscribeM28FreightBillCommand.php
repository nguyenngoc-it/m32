<?php

namespace Modules\Order\Console;

use Gobiz\Event\EventService;
use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\Order\Jobs\HandleFobizOrderEventJob;
use Modules\Order\Jobs\HandleM28FreightBillEventJob;

class SubscribeM28FreightBillCommand extends Command
{
    protected $signature = 'order:subscribe-m28-freight-bill';

    protected $description = 'Subscribe topic m28 freight bill events';

    public function handle()
    {
        $logger = LogService::logger('m28-freight-bill-events');

        EventService::publicEventDispatcher()->subscribe('m28-freight-bill', 'm28-freight-bill-subscriber', function ($message) use ($logger) {
            $logger->debug('subscribed', array_merge($message, [
                'payload' => Arr::except($message['payload'], 'payload'),
            ]));

            // Lưu vào queue xử lý sau vì khi sub kafka không thể catch exception
            dispatch(new HandleM28FreightBillEventJob($message['payload']));
        });
    }

}
