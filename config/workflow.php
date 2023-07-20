<?php

use Modules\Order\Middleware\LogOrderChangeStatus;
use Modules\Order\Models\Order;

return [
    'workflows' => [

        'order' => [
            // Danh sách status
            'places' => [
                Order::STATUS_CREATING,
                Order::STATUS_READY_TO_PICK,
                Order::STATUS_PICKED_UP,
                Order::STATUS_DELIVERING,
                Order::STATUS_DELIVERED,
                Order::STATUS_RETURNING,
                Order::STATUS_RETURNED,
                Order::STATUS_ERROR,
                Order::STATUS_CANCEL,
            ],

            // Khai báo status flow
            'transitions' => [
                Order::STATUS_CREATING => [
                    Order::STATUS_READY_TO_PICK,
                    Order::STATUS_PICKED_UP,
                    Order::STATUS_DELIVERING,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_RETURNING,
                    Order::STATUS_RETURNED,
                    Order::STATUS_ERROR,
                    Order::STATUS_CANCEL,
                ],
                Order::STATUS_READY_TO_PICK => [
                    Order::STATUS_PICKED_UP,
                    Order::STATUS_DELIVERING,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_RETURNING,
                    Order::STATUS_RETURNED,
                    Order::STATUS_ERROR,
                    Order::STATUS_CANCEL,
                ],
                Order::STATUS_PICKED_UP => [
                    Order::STATUS_DELIVERING,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_RETURNING,
                    Order::STATUS_RETURNED,
                    Order::STATUS_ERROR,
                    Order::STATUS_CANCEL,
                ],
                Order::STATUS_DELIVERING => [
                    Order::STATUS_PICKED_UP,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_RETURNING,
                    Order::STATUS_RETURNED,
                    Order::STATUS_ERROR,
                    Order::STATUS_CANCEL,
                ],
                Order::STATUS_RETURNING => [
                    Order::STATUS_DELIVERED,
                    Order::STATUS_RETURNED,
                    Order::STATUS_ERROR,
                    Order::STATUS_CANCEL,
                    Order::STATUS_DELIVERING
                ],
                Order::STATUS_RETURNED => [
                    Order::STATUS_DELIVERED,
                    Order::STATUS_ERROR,
                    Order::STATUS_CANCEL,
                ],
                Order::STATUS_ERROR => [
                    Order::STATUS_PICKED_UP,
                    Order::STATUS_DELIVERING,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_RETURNING,
                    Order::STATUS_RETURNED,
                    Order::STATUS_ERROR,
                    Order::STATUS_CANCEL,
                ],
            ],

            // Cho phép chuyển ngược status trước đó hay không?
            'reverse_transitions' => false,

            // Khai báo các middleware khi chuyển status
            'middleware' => [
                LogOrderChangeStatus::class,
            ],
        ],

    ],
];
