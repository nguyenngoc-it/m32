<?php

namespace Modules\Auth\Controllers;

use App\Base\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Service;

class AuthController extends Controller
{
    public function login()
    {
        $url = Service::auth()->oauth()->redirect()->getTargetUrl();

        return $this->response()->success(['url' => $url]);
    }

    public function loginCallback()
    {
        $authenticatedUser = Service::auth()->oauth()->user();
        $user = Service::auth()->saveUser($authenticatedUser);
        $url = Service::app()->frontendUrl('login/callback', ['token' => Auth::login($user)]);

        return redirect($url);
    }

    public function user()
    {
        return $this->response()->success(['user' => Auth::user()]);
    }
}
