<?php

namespace Modules\Application\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Application\Model\Application;
use Modules\Application\Validators\CreateApplicationValidator;
use Modules\Application\Validators\CreateShippingPartnerValidator;
use Modules\Service;

class ApplicationController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function create()
    {
        $input     = $this->requests->only(['name', 'description']);
        $validator = (new CreateApplicationValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $application = Service::application()->create($input, $this->user);

        return $this->response()->success(compact('application'));
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $applications = $this->getAuthUser()
            ->applications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Application $application) {
                return [
                    'application' => $application,
                    'is_owner' => $application->creator_id == $this->getAuthUser()->id,
                ];
            });

        return $this->response()->success(['applications' => $applications]);
    }

    /**
     * @param Application $application
     * @return JsonResponse
     */
    public function detail(Application $application)
    {
        $user     = $this->getAuthUser();
        $response = [
            'is_owner' => $user->id == $application->creator_id,
            'application' => $application,
        ];

        return $this->response()->success($response);
    }

    /**
     * @param Application $application
     * @return JsonResponse
     */
    public function getSecret(Application $application)
    {
        $secret = $application->secret;

        return $this->response()->success(['secret' => $secret]);
    }

    /**
     * Thêm đối tác vận chuyển vào application
     *
     * @param Application $application
     * @return JsonResponse
     */
    public function createShippingPartner(Application $application)
    {
        $input                = $this->requests->only(['name', 'code', 'description', 'partner_code', 'setting_params']);
        $input['application'] = $application;
        $validator            = new CreateShippingPartnerValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $shippingPartner = Service::application()->createShippingPartner($application, $input, $this->user);

        return $this->response()->success(compact('shippingPartner'));
    }

    /**
     * Thêm đối tác vận chuyển vào application
     *
     * @param Application $application
     * @return JsonResponse
     */
    public function listingShippingPartner(Application $application)
    {
        $shippingPartners = $application->shippingPartners;
        return $this->response()->success(compact('shippingPartners'));
    }

    /**
     * @param Application $application
     * @return JsonResponse
     */
    public function whitelistIp(Application $application)
    {
        if (!$this->request()->exists('allowed_ips')) {
            return $this->response()->error('INPUT_INVALID', ['allowed_ips' => 'required']);
        }

        $allowed_ips              = (array)$this->request()->get('allowed_ips');
        $application->allowed_ips = $allowed_ips;
        $application->save();

        return $this->response()->success(['application' => $application]);
    }

    /**
     * @param Application $application
     * @return JsonResponse
     * @throws \Gobiz\Support\RestApiException
     */
    public function webhookUrl(Application $application)
    {
        if (!$this->request()->exists('webhook_url')) {
            return $this->response()->error('INPUT_INVALID', ['webhook_url' => 'required']);
        }

        $webhookUrl = $this->request()->get('webhook_url');
        $application->webhook()->updateWebhookUrl($webhookUrl);

        return $this->response()->success(['application' => $application]);
    }
}
