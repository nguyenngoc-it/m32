<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application_members', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('application_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->unique(['application_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('application_members');
    }
}
