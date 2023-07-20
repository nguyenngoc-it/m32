<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Application\Model\Application;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('secret');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->string('allowed_ips')->nullable();
            $table->string('status')->default(Application::STATUS_ACTIVE);
            $table->integer('creator_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applications');
    }
}
