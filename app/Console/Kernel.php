<?php

namespace App\Console;

use App\Console\Commands\RunningMan;
use App\Console\Commands\TestCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Modules\App\Console\TestConnectionCommand;
use Modules\Application\Console\GetApplicationJWTCommand;
use Modules\JNEI\Console\InsertGoodsCodeCommand;
use Modules\JNEI\Console\InsertZipCodeCommand;
use Modules\JNEI\Console\UpdateZipDestCommand;
use Modules\JNTI\Console\MapLocationCLearCodeIndentityCommand;
use Modules\JNTM\Console\QueryTrackingCommand;
use Modules\JNTP\Console\MapLocationsByFileCommand;
use Modules\JNTP\Console\MapSortCodeLocationsCommand;
use Modules\JNTP\Console\PullLocationsCommand;
use Modules\Order\Console\SubscribeFobizOrderCommand;
use Modules\Order\Console\SubscribeM28FreightBillCommand;
use Modules\SAPI\Console\InsertIndoLocationsCommand;
use Modules\SAPI\Console\MapLocationsCommand;
use Modules\SHIPPO\Console\SubcribePublicOrder;
use Modules\Order\Console\SyncOrderStatusCommand;
use Modules\User\Console\GetUserJWTCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        TestCommand::class,
        TestConnectionCommand::class,
        GetUserJWTCommand::class,
        GetApplicationJWTCommand::class,
        \Modules\GHN\Console\MapLocationsCommand::class,
        \Modules\GHN\Console\PullLocationsCommand::class,
        PullLocationsCommand::class,
        \Modules\JNTP\Console\MapLocationsCommand::class,
        SubcribePublicOrder::class,
        \Modules\JNTVN\Console\MapLocationsCommand::class,
        InsertIndoLocationsCommand::class,
        MapLocationsCommand::class,
        MapLocationsByFileCommand::class,
        \Modules\FLASH\Console\MapLocationsByFileCommand::class,
        \Modules\FLASH\Console\PullLocationsCommand::class,
        MapSortCodeLocationsCommand::class,
        SyncOrderStatusCommand::class,
        SubscribeFobizOrderCommand::class,
        SubscribeM28FreightBillCommand::class,
        InsertIndoLocationsCommand::class,
        MapLocationsCommand::class,
        \Modules\JNEI\Console\MapLocationsCommand::class,
        InsertZipCodeCommand::class,
        InsertGoodsCodeCommand::class,
        \Modules\JNTT\Console\MapLocationsCommand::class,
        UpdateZipDestCommand::class,
        \Modules\JNTI\Console\PullLocationsCommand::class,
        \Modules\JNTI\Console\MapLocationsByFileCommand::class,
        MapLocationCLearCodeIndentityCommand::class,
        \Modules\JNTP\Console\QueryTrackingCommand::class,
        \Modules\JNTT\Console\QueryTrackingCommand::class,
        \Modules\JNTI\Console\QueryTrackingCommand::class,
        \Modules\SAPI\Console\QueryTrackingCommand::class,
        \Modules\FLASH\Console\QueryTrackingCommand::class,
        \Modules\JNEI\Console\QueryTrackingCommand::class,
        \Modules\GGE\Console\QueryTrackingCommand::class,
        RunningMan::class,
        \Modules\JNTM\Console\PullLocationsCommand::class,
        \Modules\GGE\Console\PullLocationsCommand::class,
        \Modules\GGE\Console\MapLocationsByFileCommand::class,
        QueryTrackingCommand::class,
        \Modules\SNAPPY\Console\MapLocationsCommand::class,
        \Modules\SNAPPY\Console\PullLocationsCommand::class,
        \Modules\SNAPPY\Console\QueryTrackingCommand::class,
        \Modules\JNTC\Console\QueryTrackingCommand::class,
        \Modules\JNTC\Console\MapLocationsByFileCommand::class,
        \Modules\Location\Console\ImportLocationCambodia::class,
        \Modules\Location\Console\UpdateLabelLocationCambodia::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('jntp:query-trackings')->dailyAt('01:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('jntt:query-trackings')->dailyAt('02:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('jnti:query-trackings')->dailyAt('03:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('sapi:query-trackings')->dailyAt('04:00')->runInBackground()->withoutOverlapping(1500);
        $schedule->command('flash:query-trackings')->dailyAt('05:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('jnei:query-trackings')->dailyAt('06:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('jntm:query-trackings')->dailyAt('07:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('snappy:query-trackings')->dailyAt('08:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('ghn:query-trackings')->dailyAt('09:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('gge:query-trackings')->dailyAt('10:00')->runInBackground()->withoutOverlapping(500);
        $schedule->command('jntc:query-trackings')->dailyAt('11:00')->runInBackground()->withoutOverlapping(500);
    }
}
