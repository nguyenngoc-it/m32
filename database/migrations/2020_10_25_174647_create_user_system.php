<?php

use Illuminate\Database\Migrations\Migration;
use Modules\User\Models\User;

class CreateUserSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        User::query()->updateOrCreate(['username' => User::USERNAME_SYSTEM], [
            'name' => 'Hệ thống',
            'email' => User::USERNAME_SYSTEM . '@app.com',
        ]);
    }
}
