<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->index();
            $table->string('address')->nullable();
            $table->string('avatar')->nullable();
            $table->string('language')->default('vi');
            $table->text('permissions')->nullable();
            $table->dateTime('synced_at')->nullable()->index();
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
    }
}
