<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Application\Model\Application;
use Modules\ShippingPartner\Models\ShippingPartner;

class CreateShippingPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_partners', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('application_id');
            $table->string('partner_code')->comment('Loại đối tác vận chuyển');
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('settings')->nullable();
            $table->string('status')->default(ShippingPartner::STATUS_ACTIVE);
            $table->timestamps();

            $table->unique(['application_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_partners');
    }
}
