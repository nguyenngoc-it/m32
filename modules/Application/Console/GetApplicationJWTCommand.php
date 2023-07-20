<?php

namespace Modules\Application\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Modules\Application\Model\Application;

class GetApplicationJWTCommand extends Command
{
    protected $signature = 'application:jwt {code : Application code} {--expire= : Hạn sử dụng của token (minutes)}';

    protected $description = 'Lấy JWT token của application';

    public function handle()
    {
        $code = $this->argument('code');
        $expire = $this->option('expire') ?: config('services.application.token_expire');

        $app = Application::query()->firstWhere(['code' => $code]);

        if (!$app) {
            $this->error("Application {$code} does not exists");
            return;
        }

        $token = Auth::guard('application')->setTTL($expire)->login($app);

        $this->line('JWT Token:');
        $this->info($token);
        $this->line('Expire: ' . (new Carbon())->addMinutes($expire)->toDateTimeString());
    }
}