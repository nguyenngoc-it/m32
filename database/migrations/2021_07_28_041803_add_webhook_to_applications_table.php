<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookToApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->integer('webhook_id')->after('allowed_ips')->nullable()->comment('Webhook id đồng bộ từ webhook service');
            $table->string('webhook_secret')->after('webhook_url')->nullable()->comment('Webhook secret đồng bộ từ webhook service');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('webhook_id');
            $table->dropColumn('webhook_secret');
        });
    }
}
