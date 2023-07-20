<?php

namespace Modules\JNTI\Controllers;

use App\Base\Controller;
use Modules\JNTI\Jobs\SyncOrderStatusJob;

class JNTIController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook()
    {
        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        $this->dispatch(new SyncOrderStatusJob($this->requests->except('token'), $this->getAuthUser()->id));

        return $this->response()->success(true);
    }
}
