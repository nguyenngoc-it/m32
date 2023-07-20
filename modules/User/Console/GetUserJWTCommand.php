<?php

namespace Modules\User\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Modules\User\Models\User;

class GetUserJWTCommand extends Command
{
    protected $signature = 'user:jwt {username} {--expire= : Hạn sử dụng của token (minutes)}';

    protected $description = 'Lấy JWT token của user';

    public function handle()
    {
        $username = $this->argument('username');
        $expire = $this->option('expire') ?: 5*365*24*60;

        $user = User::query()->where('username', $username)->first();

        if (!$user) {
            $this->error("User {$username} does not exists");
            return;
        }

        $token = Auth::setTTL($expire)->login($user);

        $this->line('JWT Token:');
        $this->info($token);
        $this->line('Expire: ' . (new Carbon())->addMinutes($expire)->toDateTimeString());
    }
}