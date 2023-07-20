<?php

namespace Modules\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Application\Model\Application;
use Modules\Application\Model\ApplicationMember;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class ApplicationService implements ApplicationServiceInterface
{
    /**
     * Tạo app
     *
     * @param array $input
     * @param User $creator
     * @return Application
     */
    public function create(array $input, User $creator)
    {
        $app = Application::create(array_merge($input, [
            'code' => (string)Str::uuid(),
            'secret' => Str::random(16),
            'creator_id' => $creator->id,
            'status' => Application::STATUS_ACTIVE,
        ]));

        ApplicationMember::create([
            'application_id' => $app->id,
            'user_id' => $creator->id,
        ]);

        $app->logActivity(ApplicationEvent::CREATE, $creator, ['application' => $app->attributesToArray()]);

        return $app;
    }

    /**
     * Tạo đối tác vận chuyển cho app
     *
     * @param Application $application
     * @param array $input
     * @param User $user
     * @return ShippingPartner
     */
    public function createShippingPartner(Application $application, array $input, User $user)
    {
        return ShippingPartner::create(
            [
                'application_id' => $application->id,
                'partner_code' => Arr::get($input, 'partner_code'),
                'code' => Arr::get($input, 'code'),
                'name' => Arr::get($input, 'name'),
                'description' => Arr::get($input, 'description'),
                'settings' => Arr::get($input, 'setting_params'),
                'status' => ShippingPartner::STATUS_ACTIVE
            ]
        );
    }
}
