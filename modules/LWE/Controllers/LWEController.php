<?php

namespace Modules\LWE\Controllers;

use App\Base\Controller;
use Modules\LWE\Jobs\SyncOrderStatusJob;

class LWEController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook()
    {
        $input = $this->requests->except('token');

        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        $this->dispatch(new SyncOrderStatusJob($input, $this->getAuthUser()->id));

        return $this->response()->success();
    }
}
