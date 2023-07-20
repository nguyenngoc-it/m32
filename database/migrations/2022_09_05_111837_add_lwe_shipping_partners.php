<?php

use Illuminate\Database\Migrations\Migration;
use Modules\ShippingPartner\Models\ShippingPartner;
class AddLweShippingPartners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $shippingPartners = ShippingPartner::query()
            ->where('partner_code', ShippingPartner::CARRIER_LWE)
            ->where('code', ShippingPartner::CARRIER_LWE)
            ->get();

        /** @var  ShippingPartner $shippingPartner */
        foreach ($shippingPartners as $shippingPartner) {
            foreach ([ShippingPartner::CARRIER_LWE_LBC, ShippingPartner::CARRIER_LWE_JNT] as $partner) {
                ShippingPartner::create([
                    'application_id' => $shippingPartner->application_id,
                    'partner_code' => 'LWE',
                    'code' => $partner,
                    'name' => $partner,
                    'settings' => $shippingPartner->settings,
                    'status' => $shippingPartner->status
                ]);
            }
        }
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
