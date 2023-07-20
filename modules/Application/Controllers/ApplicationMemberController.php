<?php

namespace Modules\Application\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Application\Model\Application;
use Modules\Application\Model\ApplicationMember;
use Modules\Application\Validators\AddMemberValidator;
use Modules\Application\Validators\RemoveMemberValidator;

class ApplicationMemberController extends Controller
{
    /**
     * @param Application $application
     * @return JsonResponse
     */
    public function index(Application $application)
    {
        $members  = $application
            ->applicationMembers()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (ApplicationMember $member) use ($application) {
                return [
                    'member' => $member->user,
                    'is_owner' => $member->user_id == $application->creator_id,
                ];
            });

        return $this->response()->success(['members' => $members]);
    }

    /**
     * @param Application $application
     * @return JsonResponse
     */
    public function addMember(Application $application)
    {
        $input = $this->request()->only(['email']);
        $validator = new AddMemberValidator($input, $application);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user = $validator->getUser();

        ApplicationMember::create([
            'application_id' => $application->id,
            'user_id' => $user->id,
        ]);


        return $this->response()->success([]);
    }

    /**
     * @param Application $application
     * @param $member_id
     * @return JsonResponse
     */
    public function remove(Application $application, $member_id)
    {
        $validator = new RemoveMemberValidator(['member_id' => $member_id], $application);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        if ($application->creator_id == $member_id) {
            return $this->response()->error(403, 'Unauthorized', 403);
        }

        $member = $validator->getMember();

        ApplicationMember::query()
            ->where('application_id', $application->id)
            ->where('user_id', $member->id)
            ->delete();

        return $this->response()->success([]);
    }
}
