<?php

namespace Modules\App\Console;

use Carbon\Carbon;
use Gobiz\Email\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Modules\Service;
use Modules\Tenant\Models\Tenant;

class TestConnectionCommand extends Command
{
    protected $signature = 'app:test';

    protected $description = 'Test connections';

    public function handle()
    {
        $this->testDB();
        $this->testMongo();
        $this->testRedis();
        $this->testStorage();
        $this->testEmail();
        $this->testWebhook();
    }

    protected function testDB()
    {
        $this->warn('DB: Connecting');
        DB::select('SHOW TABLES');
        $this->info('DB: Connected');
    }

    protected function testMongo()
    {
        $this->warn('Mongo: Connecting');
        DB::connection('mongodb')->getMongoDB()->listCollections();
        $this->info('Mongo: Connected');
    }

    protected function testRedis()
    {
        $this->warn('Redis: Connecting');
        Redis::connection()->set('test', time(), 10);
        Redis::connection()->get('test');
        Redis::connection()->del('test');
        $this->info('Redis: Connected');
    }

    protected function testStorage()
    {
        $this->warn("Storage: Connecting");
        Storage::put('test.log', Carbon::now()->toDateTimeString(), 'public');
        $file = Storage::url('test.log');
        $this->info("Storage: Connected - File: {$file}");
    }

    protected function testEmail($to = null)
    {
        $this->warn("Email: Sending");
        if (EmailService::email()->send($to ?: 'nguyensontung@gobiz.vn', 'Test', 'Test...')) {
            $this->info("Email: Sent");
        } else {
            $this->error("Email: Error");
        }
    }

    protected function testWebhook()
    {
        $this->warn('Webhook: Connecting');
        $res = Service::app()->webhook()->me();
        $this->info("Webhook: Connected, User: {$res->getData('user.username')}");
    }
}
