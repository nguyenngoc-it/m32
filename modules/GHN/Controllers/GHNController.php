<?php

namespace Modules\GHN\Controllers;

use App\Base\Controller;
use Modules\GHN\Jobs\SyncOrderStatusJob;

class GHNController extends Controller
{
    /**
     * Webhook nhận thông tin đơn
     */
    public function webhook()
    {
        $input = $this->request()->only([
            'OrderCode',
            'Status',
            'TotalFee',
            'CODAmount',
            'ClientOrderCode',
            'Fee',
        ]);

        // Push vào queue xử lý sau để tránh trường hợp nếu lỗi thì còn có thể retry
        $this->dispatch(new SyncOrderStatusJob($input, $this->getAuthUser()->id));

        return $this->response()->success();
    }
}