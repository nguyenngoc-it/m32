<?php

namespace Modules\ShippingPartner\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Application\Model\Application;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\ShippingPartner\Services\ShippingPartnerInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ShippingPartner
 *
 * @property int $id
 * @property int $application_id
 * @property string $partner_code
 * @property string $code
 * @property string $name
 * @property string $description
 * @property array $settings
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Application $application
 *
 * @property Collection orders
 */
class ShippingPartner extends Model
{
    protected $table = 'shipping_partners';

    protected $casts = [
        'settings' => 'json',
    ];

    /*
     * Mã nhà cung cấp dv vận chuyển
     */
    const PARTNER_GHN     = 'GHN'; // Giao Hàng Nhanh
    const PARTNER_SNAPPY  = 'SNAPPY'; // Snappy Express
    const PARTNER_GOSHIP  = 'GOSHIP'; // Goship
    const PARTNER_JNTP    = 'JNTP'; // J&T Philippin
    const PARTNER_LWE     = 'LWE'; // Lwe
    const PARTNER_SHIPPO  = 'SHIPPO'; // SHIPPO
    const PARTNER_JNTVN   = 'JNTVN'; // J&T VietNam
    const PARTNER_SAPI    = 'SAPI'; // SAP INDO
    const PARTNER_NIJAVAI = 'NIJAVAI'; // NINJAVAN INDO
    const PARTNER_NIJAVAM = 'NIJAVAM'; // NINJAVAN Malaysia
    const PARTNER_NIJAVAP = 'NIJAVAP'; // NINJAVAN Phillipines
    const PARTNER_JNEI    = 'JNEI'; // JNE INDO
    const PARTNER_JNTT    = 'JNTT'; // JNT THAI
    const PARTNER_JNTI    = 'JNTI'; // JNT INDO
    const PARTNER_FLASH   = 'FLASH'; // FLASH THAI
    const PARTNER_JNTM    = 'JNTM'; // JNTM MaLai
    const PARTNER_GGE     = 'GGE'; // Gogo Express
    const PARTNER_JNTC    = 'JNTC'; // J&T cambodia


    /*
     * Mã đơn vị vận chuyển
     */
    const CARRIER_GHN     = 'GHN'; // Giao Hàng Nhanh
    const CARRIER_JNTP    = 'JNTP'; // J&T Express Philippines
    const CARRIER_SNAPPY  = 'SNAPPY'; // SNAPPY
    const CARRIER_LWE     = 'LWE'; // LWE
    const CARRIER_LWE_LBC = 'LWE-LBC'; // LWE-LBC
    const CARRIER_LWE_JNT = 'LWE-JNT'; // LWE-JNT
    const CARRIER_SHIPPO  = 'SHIPPO'; // SHIPPO
    const CARRIER_JNTVN   = 'JNTVN'; // J&T Express VietNam
    const CARRIER_SAPI    = 'SAPI'; // SAP INDO
    const CARRIER_NIJAVAI = 'NIJAVAI'; // NINJAVAN INDO
    const CARRIER_NIJAVAM = 'NIJAVAM'; // NINJAVAN Malaysia
    const CARRIER_NIJAVAP = 'NIJAVAP'; // NINJAVAN Philippines
    const CARRIER_JNEI    = 'JNEI'; // JNE INDO
    const CARRIER_JNTT    = 'JNTT'; // JNTT THAI
    const CARRIER_JNTI    = 'JNTI'; // JNTT INDO
    const CARRIER_FLASH   = 'FLASH'; // FLASH THAI
    const CARRIER_JNTM    = 'JNTM'; // JNTM
    const CARRIER_GGE     = 'GGE'; // Gogo Express
    const CARRIER_JNTC    = 'JNTC'; // J&T cambodia

    /*
     * Statuses
     */
    const STATUS_ACTIVE = 'ACTIVE';

    public static $nameShippingProviders = [
        self::PARTNER_GHN => 'Giao hàng nhanh',
        self::PARTNER_SNAPPY => 'Snappy',
        self::PARTNER_GOSHIP => 'Goship',
        self::PARTNER_LWE => 'Lwe',
        self::PARTNER_JNTP => 'J&T Philippines',
        self::PARTNER_SHIPPO => 'Shippo',
        self::PARTNER_JNTVN => 'J&T VietNam',
        self::PARTNER_SAPI => 'SAP INDONESIA',
        self::PARTNER_NIJAVAI => 'NINJAVAN INDONESIA',
        self::PARTNER_NIJAVAM => 'NINJAVAN MALAYSIA',
        self::PARTNER_JNEI => 'JNE INDONESIA',
        self::PARTNER_JNTT => 'J&T THAI',
        self::PARTNER_JNTI => 'J&T INDO',
        self::PARTNER_FLASH => 'FLASH THAI',
        self::PARTNER_JNTC => 'J&T cambodia',
    ];

    /**
     * Thông tin sender được lưu trong cấu hình JNTP, LWE
     */
    const SENDER_NAME          = 'name';
    const SENDER_PHONE         = 'phone';
    const SENDER_EMAIL         = 'email';
    const SENDER_PROVINCE_CODE = 'province_code';
    const SENDER_DISTRICT_CODE = 'district_code';
    const SENDER_WARD_CODE     = 'ward_code';
    const SENDER_POSTAL_CODE   = 'postal_code';
    const SENDER_ADDRESS       = 'address';

    /**
     * @return BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'shipping_partner_id', 'id');
    }

    /**
     * Lấy đối tượng xử lý tích hợp của đối tác vận chuyển
     *
     * @return ShippingPartnerInterface
     */
    public function partner()
    {
        return Service::shippingPartner()->partner($this);
    }
}
