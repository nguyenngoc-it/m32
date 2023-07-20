<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnNameLocalToShippingPartnerLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_partner_locations', function (Blueprint $table) {
            $table->string('name_local')->after('identity')
                ->comment('Địa chỉ tại Thái Lan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_partner_locations', function (Blueprint $table) {
            //
        });
    }
}
