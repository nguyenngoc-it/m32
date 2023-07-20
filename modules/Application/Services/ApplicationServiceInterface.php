<?php

namespace Modules\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Application\Model\Application;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

interface ApplicationServiceInterface
{
    /**
     * Tạo app
     *
     * @param array $input
     * @param User $creator
     * @return Application
     */
    public function create(array $input, User $creator);

    /**
     * Tạo đối tác vận chuyển cho app
     *
     * @param Application $application
     * @param array $input
     * @param User $user
     * @return ShippingPartner
     */
    public function createShippingPartner(Application $application, array $input, User $user);
}
