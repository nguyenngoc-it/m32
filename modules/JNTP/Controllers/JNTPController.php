<?php

namespace Modules\JNTP\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Modules\JNTP\Jobs\SyncOrderStatusJob;

class JNTPController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook()
    {
        $inputs = $this->request()->all();
        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        LogService::logger('nijavam-hook')->debug('WEBHOOK', $inputs);
        $this->dispatch(new SyncOrderStatusJob($inputs, $this->getAuthUser()->id));

        return $this->response()->success(true);
    }
}
