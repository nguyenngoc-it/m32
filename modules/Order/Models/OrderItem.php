<?php

namespace Modules\Order\Models;

use App\Base\Model;

/**
 * Class OrderItem
 * @package Modules\Order\Models
 *
 * @property string name
 * @property string quantity
 * @property string price
 */
class OrderItem extends Model
{
    protected $table = 'order_items';
}
