<?php

namespace Modules\JNTVN\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Rap2hpoutre\FastExcel\FastExcel;

class MapLocationsCommand extends Command
{
    protected $signature = 'jntvn:map-locations';

    protected $description = 'Mapping JNTVN locations with system locations';


    protected $provincesImported = [];

    protected $districtsImported = [];

    protected $wardsImported = [];


    public function handle()
    {
        DB::transaction(function () {
            try {
                $filePath = __DIR__.'/jntvn_locations.xlsx';
                (new FastExcel())->import($filePath, function ($row) {

                    $province = trim($row['Province']);
                    $district = trim($row['District']);
                    $ward     = trim($row['Area']);

                    if (!$province) return;

                    $systemProvince = $this->getProvince($province);

                    if (!in_array($province, $this->provincesImported)) {
                        array_push($this->provincesImported, $province);

                        ShippingPartnerLocation::create([
                            'partner_code' => ShippingPartner::PARTNER_JNTVN,
                            'type' => Location::TYPE_PROVINCE,
                            'identity' => $province,
                            'code' => $province,
                            'name' => $province,
                            'location_code' => $systemProvince instanceof Location ? $systemProvince->code : null,
                        ]);
                    }

                    $systemDistrict = $this->getDistrict($systemProvince, $district);

                    if (!in_array($district . '_' . $province, $this->districtsImported)) {
                        array_push($this->districtsImported, $district . '_' . $province);

                        ShippingPartnerLocation::create([
                            'partner_code' => ShippingPartner::PARTNER_JNTVN,
                            'type' => Location::TYPE_DISTRICT,
                            'identity' => $district . '_' . $province,
                            'code' => $district,
                            'name' => $district . ', ' . $province,
                            'parent_identity' => $province,
                            'location_code' => $systemDistrict instanceof Location ? $systemDistrict->code : null,
                        ]);
                    }

                    $ward = explode('-', $ward);
                    [$wardLabel, $wardIdentity] = $ward;
                    $systemWard = $this->getWard($systemDistrict, $wardLabel);

                    if (!in_array($wardLabel . '_' . $district . '_' . $province, $this->wardsImported)) {
                        array_push($this->wardsImported, $wardLabel . '_' . $district . '_' . $province);

                        ShippingPartnerLocation::create([
                            'partner_code' => ShippingPartner::PARTNER_JNTVN,
                            'type' => Location::TYPE_WARD,
                            'identity' => $wardIdentity,
                            'code' => $wardLabel,
                            'name' => $wardLabel . ', ' . $district . ', ' . $province,
                            'parent_identity' => $district . '_' . $province,
                            'location_code' => $systemWard instanceof Location ? $systemWard->code : null,
                        ]);
                    }
                });
                print_r("Imported\n");
            } catch (\Throwable $th) {
                throw $th;
            }
        });
    }

    /**
     * @param string $province
     */
    public function getProvince($province)
    {
        return Location::query()
            ->where('type', Location::TYPE_PROVINCE)
            ->where(function ($query) use ($province) {
                return $query
                    ->where('label', 'Tỉnh ' . $province)
                    ->orWhere('label', 'Thành phố ' . $province);
            })
            ->first();
    }

    /**
     * @param string $district
     */
    public function getDistrict($systemProvince, $district)
    {
        $queryLabel = explode('(', $district);
        return $systemProvince instanceof Location
            ? Location::query()
                ->where('parent_code', $systemProvince->code)
                ->where('type', Location::TYPE_DISTRICT)
                ->where('label', $queryLabel[0])
                ->first()
            : null;
    }

    /**
     * @param string $ward
     */
    public function getWard($systemDistrict, $ward)
    {
        return $systemDistrict instanceof Location
            ? Location::query()
                ->where('parent_code', $systemDistrict->code)
                ->where('type', Location::TYPE_WARD)
                ->where('label', $ward)
                ->first()
            : null;
    }
}
