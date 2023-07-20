<?php

namespace Modules\JNEI\Controllers;

use App\Base\Controller;
use Modules\JNEI\Jobs\SyncOrderStatusJob;

class JNEIController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook()
    {
        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        $this->dispatch(new SyncOrderStatusJob($this->requests->toArray(), $this->getAuthUser()->id));

        return $this->response()->success(true);
    }
}
