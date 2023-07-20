<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Application\Model\Application;
use Modules\Application\Model\ApplicationMember;

class MigrateDataApplicationMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $applications = Application::all();
        foreach ($applications as $application) {
            ApplicationMember::create([
                'application_id' => $application->id,
                'user_id' => $application->creator_id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ApplicationMember::truncate();
    }
}
