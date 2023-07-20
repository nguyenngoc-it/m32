<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPostalCodeToShippingPartnerLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_partner_locations', function (Blueprint $table) {
            $table->string('postal_code')->after('name')->nullable()->comment('Postal code location');
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
