<?php

namespace Modules\User\Services;

use Modules\User\Models\User;

interface UserServiceInterface
{
    /**
     * Lấy user đại diện cho hệ thống
     *
     * @return User|null
     */
    public function getSystemUser();
}
