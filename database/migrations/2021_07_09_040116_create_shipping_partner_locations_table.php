<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShippingPartnerLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_partner_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('partner_code')->comment('Mã đối tác vận chuyển')->nullable();
            $table->string('type')->comment('Location type: COUNTRY | PROVINCE | DISTRICT | WARD')->nullable();
            $table->string('identity')->comment('Location id bên đối tác')->nullable();
            $table->string('code')->comment('Location code bên đối tác')->nullable();
            $table->string('name')->comment('Location name bên đối tác')->nullable();
            $table->string('parent_identity')->comment('ID location cha')->nullable();
            $table->string('location_code')->comment('Location code tương ứng của hệ thống')->nullable();

            $table->unique(['partner_code', 'type', 'identity']);
            $table->index('location_code');
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
