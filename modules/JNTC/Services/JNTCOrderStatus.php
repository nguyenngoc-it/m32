<?php

namespace Modules\JNTC\Services;

class JNTCOrderStatus
{
    const PICKUP        = "快件揽收";
    const DEPARTURE     = "装车发件";
    const ARRIVAL       = "卸车到件";
    const DELIVERED     = "快件签收";
    const PROBLEM_PACKAGE = "问题件扫描";
    const ON_HOLD         = "留仓件扫描";
    const RETURN          = "退件登记";
    const RETURN_POD      = "退件签收";
}
