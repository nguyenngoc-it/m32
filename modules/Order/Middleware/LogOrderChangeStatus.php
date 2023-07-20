<?php

namespace Modules\Order\Middleware;

use Closure;
use Gobiz\Workflow\ApplyTransitionCommand;
use Gobiz\Workflow\WorkflowMiddlewareInterface;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;

class LogOrderChangeStatus implements WorkflowMiddlewareInterface
{
    /**
     * @param ApplyTransitionCommand $command
     * @param Closure $next
     * @return mixed
     */
    public function handle(ApplyTransitionCommand $command, Closure $next)
    {
        /**
         * @var Order $order
         */
        $order = $command->subject;
        $fromStatus = $order->status;
        $res = $next($command);

        $order->logActivity(OrderEvent::CHANGE_STATUS, $command->getPayload('creator'), [
            'order' => $order,
            'from_status' => $fromStatus,
            'to_status' => $order->status,
        ]);

        return $res;
    }
}