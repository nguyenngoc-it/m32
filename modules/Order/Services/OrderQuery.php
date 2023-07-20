<?php

namespace Modules\Order\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Order\Models\Order;

class OrderQuery extends ModelQueryFactory
{
    protected $joins = [];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Order();
    }
}
