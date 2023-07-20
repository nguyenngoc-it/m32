<?php /** @noinspection ALL */

namespace Modules;

use Modules\App\Services\AppServiceInterface;
use Modules\Application\Services\ApplicationServiceInterface;
use Modules\Auth\Services\AuthServiceInterface;
use Modules\GHN\Services\GHNServiceInterface;
use Modules\JNEI\Services\JNEIServiceInterface;
use Modules\JNTI\Services\JNTIServiceInterface;
use Modules\JNTM\Services\JNTMServiceInterface;
use Modules\JNTP\Services\JNTPServiceInterface;
use Modules\JNTT\Services\JNTTServiceInterface;
use Modules\JNTVN\Services\JNTVNServiceInterface;
use Modules\NIJAVAI\Services\NIJAVAIServiceInterface;
use Modules\NIJAVAM\Services\NIJAVAMServiceInterface;
use Modules\NIJAVAP\Services\NIJAVAPServiceInterface;
use Modules\Order\Services\OrderServiceInterface;
use Modules\SAPI\Services\SAPIServiceInterface;
use Modules\ShippingPartner\Services\ShippingPartnerServiceInterface;
use Modules\SHIPPO\Services\SHIPPOServiceInterface;
use Modules\User\Services\UserServiceInterface;
use Modules\SNAPPY\Services\SNAPPYServiceInterface;
use Modules\LWE\Services\LWEServiceInterface;
use App\Services\Log\LogServiceInterface as AppLogServiceInterface;
use Modules\FLASH\Services\FLASHServiceInterface;
use Modules\GGE\Services\GGEServiceInterface;
use Modules\JNTC\Services\JNTCServiceInterface;

class Service
{
    /**
     * @return AppServiceInterface
     */
    public static function app()
    {
        return app(AppServiceInterface::class);
    }

    /**
     * @return AuthServiceInterface
     */
    public static function auth()
    {
        return app(AuthServiceInterface::class);
    }

    /**
     * @return UserServiceInterface
     */
    public static function user()
    {
        return app(UserServiceInterface::class);
    }

    /**
     * @return ApplicationServiceInterface
     */
    public static function application()
    {
        return app(ApplicationServiceInterface::class);
    }

    /**
     * @return OrderServiceInterface
     */
    public static function order()
    {
        return app(OrderServiceInterface::class);
    }

    /**
     * @return ShippingPartnerServiceInterface
     */
    public static function shippingPartner()
    {
        return app(ShippingPartnerServiceInterface::class);
    }

    /**
     * @return GHNServiceInterface
     */
    public static function ghn()
    {
        return app(GHNServiceInterface::class);
    }

    /**
     * @return SNAPPYServiceInterface
     */
    public static function snappy()
    {
        return app(SNAPPYServiceInterface::class);
    }

    /**
     * @return LWEServiceInterface
     */
    public static function lwe()
    {
        return app(LWEServiceInterface::class);
    }

    /**
     * @return JNTPServiceInterface
     */
    public static function jntp()
    {
        return app(JNTPServiceInterface::class);
    }

    /**
     * @return SHIPPOServiceInterface
     */
    public static function shippo()
    {
        return app(SHIPPOServiceInterface::class);
    }

    /**
     * @return JNTVNServiceInterface
     */
    public static function jntvn()
    {
        return app(JNTVNServiceInterface::class);
    }

    /**
     * @return SAPIServiceInterface
     */
    public static function sapi()
    {
        return app(SAPIServiceInterface::class);
    }

    /**
     * @return NIJAVAIServiceInterface
     */
    public static function nijavai()
    {
        return app(NIJAVAIServiceInterface::class);
    }

    /**
     * @return NIJAVAMServiceInterface
     */
    public static function nijavam()
    {
        return app(NIJAVAMServiceInterface::class);
    }

    /**
     * @return NIJAVAPServiceInterface
     */
    public static function nijavap()
    {
        return app(NIJAVAPServiceInterface::class);
    }

    /**
     * @return JNEIServiceInterface
     */
    public static function jnei()
    {
        return app(JNEIServiceInterface::class);
    }

    /**
     * @return JNTTServiceInterface
     */
    public static function jntt()
    {
        return app(JNTTServiceInterface::class);
    }

    /**
     * @return JNTIServiceInterface
     */
    public static function jnti()
    {
        return app(JNTIServiceInterface::class);
    }

    /**
     * @return FLASHServiceInterface
     */
    public static function flash()
    {
        return app(FLASHServiceInterface::class);
    }

    /**
     * @return AppLogServiceInterface
     */
    public static function appLog()
    {
        return app(AppLogServiceInterface::class);
    }

    /**
     * @return JNTMServiceInterface
     */
    public static function jntm()
    {
        return app(JNTMServiceInterface::class);
    }

    /**
     * @return GGEServiceInterface
     */
    public static function gge()
    {
        return app(GGEServiceInterface::class);
    }

    /**
     * @return JNTCServiceInterface
     */
    public static function jntc()
    {
        return app(JNTCServiceInterface::class);
    }
}
