<?php

namespace Modules\User\Services;

use Modules\User\Models\User;

class UserService implements UserServiceInterface
{
    /**
     * Lay user he thong dai dien cho hệ thống
     *
     * @return User|null|object
     */
    public function getSystemUser()
    {
        return User::query()->where('username', User::USERNAME_SYSTEM)->first();
    }
}
