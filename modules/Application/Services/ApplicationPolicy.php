<?php

namespace Modules\Application\Services;

use Modules\Application\Model\Application;
use Modules\User\Models\User;

class ApplicationPolicy
{
    /**
     * Quyền xem thông tin application
     *
     * @param User $user
     * @param Application $application
     * @return bool
     */
    public function viewApplication(User $user, Application $application)
    {
        return $application->hasMember($user);
    }

    /**
     * Quyền thêm member vào application
     *
     * @param User $user
     * @param Application $application
     * @return bool
     */
    public function addApplicationMember(User $user, Application $application)
    {
        return $application->creator_id == $user->id;
    }

    /**
     * Quyền xóa member khỏi application
     *
     * @param User $user
     * @param Application $application
     * @return bool
     */
    public function removeApplicationMember(User $user, Application $application)
    {
        return $application->creator_id == $user->id;
    }
}
