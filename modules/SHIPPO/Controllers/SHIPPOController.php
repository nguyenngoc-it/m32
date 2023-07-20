<?php

namespace Modules\SHIPPO\Controllers;

use App\Base\Controller;
use Modules\SHIPPO\Jobs\SyncOrderStatusJob;

class SHIPPOController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook()
    {
        $input = $this->request()->only([
            'Status',
            'order_number',
            'waybill_number'
        ]);

        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        $this->dispatch(new SyncOrderStatusJob($input, $this->getAuthUser()->id));

        return $this->response()->success(true);
    }
}
