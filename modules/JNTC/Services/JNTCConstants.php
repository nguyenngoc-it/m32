<?php

namespace Modules\JNTC\Services;

class JNTCConstants
{
    /**
     * Message type
     *
     * Mỗi khi call api của J&T cần truyền lên message type cụ thể cho hành động mong muốn
     */
    const MSG_TYPE_ORDERCREATE        = 'ORDERCREATE'; // Online order interface
    const MSG_TYPE_ORDERCANCEL        = 'ORDERCANCEL'; // Cancel order interface
    const MSG_TYPE_TRACKQUERY         = 'TRACKQUERY'; // Tracking query interface
    const MSG_TYPE_OBTAINPROVCITYAREA = 'OBTAINPROVCITYAREA'; // Get the basic information interface of provinces and cities
    const MSG_TYPE_ORDERQUERY         = 'ORDERQUERY'; // Order query interface
}
