<?php

namespace Modules\Application\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Application\Model\Application;

class ApplicationApiController extends \App\Base\ApplicationApiController
{
    /**
     * @return JsonResponse
     */
    public function createToken()
    {
        $code   = (string)$this->request()->get('code');
        $secret = (string)$this->request()->get('secret');

        if (!$code || !$secret || !($app = $this->findApplication($code, $secret))) {
            return $this->response()->error(401, ['message' => 'The application code & secret invalid'], 401);
        }

        $expire = config('services.application.token_expire');
        $token  = Auth::guard('application')->setTTL($expire)->login($app);

        return $this->response()->success([
            'access_token' => $token,
            'expires_in' => $expire,
        ]);
    }

    /**
     * @param string $code
     * @param string $secret
     * @return Application|object|null
     */
    protected function findApplication($code, $secret)
    {
        return Application::query()->firstWhere(compact('code', 'secret'));
    }
}
