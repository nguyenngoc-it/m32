<?php

namespace Modules\ShippingPartner\Services;

use InvalidArgumentException;
use Modules\ShippingPartner\Models\ShippingPartner;

class ShippingPartnerService implements ShippingPartnerServiceInterface
{
    /**
     * @var ShippingPartnerProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var ShippingPartnerInterface[]
     */
    protected $partners = [];

    /**
     * ShippingPartnerService constructor
     *
     * @param ShippingPartnerProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Lấy danh sách đối tượng đối tác vận chuyển được hỗ trợ
     *
     * @return ShippingPartnerProviderInterface[]
     */
    public function providers()
    {
        return $this->providers;
    }

    /**
     * Lấy đối tượng xử lý việc khai báo thông tin đối tác vận chuyển
     *
     * @param string $code
     * @return ShippingPartnerProviderInterface|null
     */
    public function provider($code)
    {
        foreach ($this->providers as $provider) {
            if ($provider->getCode() === $code) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Lấy đối tượng xử lý tích hợp của đối tác vận chuyển
     *
     * @param ShippingPartner $partner
     * @return ShippingPartnerInterface
     */
    public function partner(ShippingPartner $partner)
    {
        return $this->partners[$partner->id] ?? $this->partners[$partner->id] = $this->makeShippingPartner($partner);
    }

    /**
     * @param ShippingPartner $shippingPartner
     * @return ShippingPartnerInterface
     */
    protected function makeShippingPartner(ShippingPartner $shippingPartner)
    {
        $code = $shippingPartner->partner_code;

        if (!$provider = $this->provider($code)) {
            throw new InvalidArgumentException("The partner [{$code}] is not supported");
        }

        return $provider->make($shippingPartner);
    }

    /**
     * @param $applicationId
     * @param $code
     * @return ShippingPartner|null|mixed
     */
    public function findByCode($applicationId, $code)
    {
        return ShippingPartner::query()->where([
            'application_id' => $applicationId,
            'code' => $code
        ])->first();
    }
}
