<?php

namespace Modules\SHIPPO\Console;

use Gobiz\Event\EventService;
use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\Order\Services\OrderEvent;
use Modules\SHIPPO\Jobs\SyncOrderStatusJob;

class SubcribePublicOrder extends Command
{
    protected $signature = 'shippo:subcribe-public-order';

    protected $description = 'Subcribe public order for JNTP';

    public function handle()
    {
        $logger = LogService::logger('m32-order-subscribed-events');

        EventService::publicEventDispatcher()->subscribe([OrderEvent::M2_SHIPMENT_ORDER], 'm32-order-subscriber', function ($message) use ($logger) {
            $logger->debug('subscribed', array_merge($message, [
                'payload' => Arr::except($message['payload'], 'payload'),
            ]));

            $inputs = $message['payload'];
            $event  = Arr::get($inputs, 'event');
            $code   = Arr::get($inputs, 'payload.code');
            $status = Arr::get($inputs, 'payload.status');

            if (in_array($event, ['SHIPMENT_CREATE', 'SHIPMENT_STATUS_UPDATE', 'SHIPMENT_CANCELLED'])) {
                // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
                dispatch(new SyncOrderStatusJob(['code' => $code, 'status' => $status]));
            }
        });
    }

}
