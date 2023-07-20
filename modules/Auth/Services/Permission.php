<?php

namespace Modules\Auth\Services;

class Permission
{
    /*
     * Public permissions (gán cho user thực tế của hệ thống)
     */
    const VIEW_APPLICATION          = 'view-application';
    const ADD_APPLICATION_MEMBER    = 'add-application-member';
    const REMOVE_APPLICATION_MEMBER = 'remove-application-member';

    /*
     * Private permissions (gán cho các user đại diện trong các kết nối nội bộ giữa các service)
     */
    const WEBHOOK_GHN    = 'webhook-ghn';
    const WEBHOOK_SNAPPY = 'webhook-snappy';
    const WEBHOOK_LWE    = 'webhook-lwe';

    const WEBHOOK_JNTP = 'webhook-jntp';
    const WEBHOOK_JNTC = 'webhook-jntc';

    const WEBHOOK_JNTVN   = 'webhook-jntvn';
    const WEBHOOK_SAPI    = 'webhook-sapi';
    const WEBHOOK_NIJAVAI = 'webhook-nijavai';
    const WEBHOOK_FLASH   = 'webhook-flash';
    const WEBHOOK_JNTM   = 'webhook-jntm';

    /**
     * Quyền thao tác công cụ
     */
    const TOOLS           = 'tools';
}
