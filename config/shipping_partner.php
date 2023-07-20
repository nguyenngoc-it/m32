<?php

use Modules\FLASH\Services\FLASHShippingPartnerProvider;
use Modules\GHN\Services\GHNShippingPartnerProvider;
use Modules\JNEI\Services\JNEIShippingPartnerProvider;
use Modules\JNTI\Services\JNTIShippingPartnerProvider;
use Modules\JNTM\Services\JNTMShippingPartnerProvider;
use Modules\JNTP\Services\JNTPShippingPartnerProvider;
use Modules\JNTT\Services\JNTTShippingPartnerProvider;
use Modules\JNTVN\Services\JNTVNShippingPartnerProvider;
use Modules\LWE\Services\LWEShippingPartnerProvider;
use Modules\NIJAVAI\Services\NIJAVAIShippingPartnerProvider;
use Modules\NIJAVAM\Services\NIJAVAMShippingPartnerProvider;
use Modules\NIJAVAP\Services\NIJAVAPShippingPartnerProvider;
use Modules\SAPI\Services\SAPIShippingPartnerProvider;
use Modules\SHIPPO\Services\SHIPPOShippingPartnerProvider;
use Modules\SNAPPY\Services\SNAPPYShippingPartnerProvider;
use Modules\GGE\Services\GGEShippingPartnerProvider;
use Modules\JNTC\Services\JNTCShippingPartnerProvider;

return [
    /*
     * Danh sách đối tác vận chuyển được hỗ trợ
     */
    'providers' => [
        GHNShippingPartnerProvider::class,
        SNAPPYShippingPartnerProvider::class,
        LWEShippingPartnerProvider::class,
        JNTPShippingPartnerProvider::class,
        SHIPPOShippingPartnerProvider::class,
        JNTVNShippingPartnerProvider::class,
        SAPIShippingPartnerProvider::class,
        NIJAVAIShippingPartnerProvider::class,
        NIJAVAMShippingPartnerProvider::class,
        NIJAVAPShippingPartnerProvider::class,
        JNEIShippingPartnerProvider::class,
        JNTTShippingPartnerProvider::class,
        JNTIShippingPartnerProvider::class,
        FLASHShippingPartnerProvider::class,
        JNTMShippingPartnerProvider::class,
        GGEShippingPartnerProvider::class,
        JNTCShippingPartnerProvider::class,
    ],
];
