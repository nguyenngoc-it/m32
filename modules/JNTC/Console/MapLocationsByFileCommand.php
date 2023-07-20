<?php

namespace Modules\JNTC\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Rap2hpoutre\FastExcel\FastExcel;

class MapLocationsByFileCommand extends Command
{
    protected $signature = 'jntc:map-locations-by-file';

    protected $description = 'Mapping JNTC locations with system locations';

    protected $notIssetProvinces = [];
    protected $notIssetDistricts = [];


    protected $provinces = [];
    protected $districts = [];

    /**
     *
     * @param $label
     * @return Location
     */
    protected function getProvince($label)
    {
        if(!isset($this->provinces[$label])) {
            $this->provinces[$label] = Location::query()->where('label', $label)
                ->where('parent_code', Location::COUNTRY_CODE_CAMBODIA)
                ->where('type', Location::TYPE_PROVINCE)->first();
        }
        return $this->provinces[$label];
    }

    /**
     * @param $label
     * @param $parentCode
     * @return Location|null
     */
    protected function getDistrict($label, $parentCode)
    {
        if(!isset($this->districts[$label])) {
            $this->districts[$label] = Location::query()->where('label', $label)
                ->where('parent_code', $parentCode)
                ->where('type', Location::TYPE_DISTRICT)->first();
        }
        return  $this->districts[$label];
    }

    /**
     * @param $provinceEn
     * @return string
     */
    protected function getProvinceLabel($provinceEn)
    {
        $provinceEn = trim(str_replace('Province', '', $provinceEn));
        $provinceEn = trim(str_replace('province', '', trim($provinceEn)));
        $provinceEn = trim(str_replace('municipality', '', trim($provinceEn)));
        $provinceEn = trim(str_replace('Municipality', '', trim($provinceEn)));

        return $provinceEn;
    }

    /**
     * @param $districtEn
     * @return string
     */
    protected function getDistrictLabel($districtEn)
    {
        $districtEn = trim(str_replace('District', '', $districtEn));
        $districtEn = trim(str_replace('district', '', $districtEn));
        $districtEn = trim(str_replace('municipality', '', $districtEn));
        $districtEn = trim(str_replace('Municipality', '', $districtEn));
        $districtEn = trim(str_replace('Distric', '', $districtEn));
        $districtEn = trim(str_replace('Distrisct', '', $districtEn));
        $districtEn = trim(str_replace('Disdtrict', '', $districtEn));

        return $districtEn;
    }


    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        $countryCode   = Location::COUNTRY_CODE_CAMBODIA;

        $provinceCreated = [];
        $districtCreated = [];

        (new FastExcel)->import(storage_path('location_cambodia_mapping.xlsx'), function ($row) use (&$provinceCreated, &$districtCreated, $countryCode) {
            $row = $this->makeRow($row);
            if(!empty($row)) {
                $provinceEnLabel = $this->getProvinceLabel(trim($row['province_en']));
                $districtEnLabel = $this->getDistrictLabel(trim($row['city_en']));

                $provinceCamLabel   = trim($row['province']);
                $districtCamLabel   = trim($row['city']);

                $provinceLocation = $this->getProvince($provinceEnLabel);
                $districtLocation = null;
                if($provinceLocation instanceof Location) {
                    $districtLocation = $this->getDistrict($districtEnLabel, $provinceLocation->code);
                } else {
                    $this->notIssetProvinces[$provinceEnLabel] = true;
                }

                if(!$districtLocation instanceof Location) {
                    $this->notIssetDistricts[$districtEnLabel] = true;
                }

                if(
                    !isset($provinceCreated[$provinceCamLabel])
                ) {
                    $provinceCode = $provinceLocation->code;
                    ShippingPartnerLocation::updateOrCreate([
                        'location_code' => $provinceCode,
                        'partner_code' => ShippingPartner::PARTNER_JNTC
                    ], [
                        'type' => Location::TYPE_PROVINCE,
                        'identity' => $provinceCamLabel.'_'.$provinceCode,
                        'name' => $provinceCamLabel,
                        'code' => $provinceCode,
                        'parent_location_code' => $provinceLocation->parent_code,
                        'parent_identity' => ''
                    ]);
                    $provinceCreated[$provinceCamLabel] = true;

                    $this->info('done province '.$districtEnLabel. ' - '.$provinceEnLabel .' - '. $provinceCode);
                }

                $locationDistrictCode = isset($districtLocation->code) ? $districtLocation->code : '';
                if(
                    $locationDistrictCode &&
                    !isset($districtCreated[$districtCamLabel])
                ) {
                    ShippingPartnerLocation::updateOrCreate([
                        'location_code' => $locationDistrictCode,
                        'partner_code' => ShippingPartner::PARTNER_JNTC
                    ], [
                        'type' => Location::TYPE_DISTRICT,
                        'identity' => $districtCamLabel.'_'.$locationDistrictCode,
                        'name' => $districtCamLabel,
                        'code' => $locationDistrictCode,
                        'parent_location_code' => $provinceLocation->code,
                        'parent_identity' => ''
                    ]);
                    $districtCreated[$districtCamLabel] = true;
                    $this->info('done district '.$districtEnLabel. ' - '.$provinceEnLabel .' - '. $locationDistrictCode);
                }

                if(!empty($locationDistrictCode)) {
                    $wardCamLabel = trim($row['ward']);
                    $wardEn       = trim($row['ward_en']);

                    $wardCode = 'CAM' . rand(100000, 99999999999);
                    Location::updateOrCreate([
                        'code' => $wardCode,
                        'type' => Location::TYPE_WARD,
                        'parent_code' => $locationDistrictCode
                    ], ['label' => $wardEn,]);

                    ShippingPartnerLocation::updateOrCreate([
                        'location_code' => $wardCode,
                        'partner_code' => ShippingPartner::PARTNER_JNTC
                    ], [
                        'type' => Location::TYPE_WARD,
                        'identity' => $wardCamLabel.'_'.$wardCode,
                        'name' => $wardCamLabel,
                        'code' => $wardCode,
                        'parent_location_code' => $locationDistrictCode,
                        'parent_identity' => ''
                    ]);

                    $this->info('done ward '.$districtEnLabel. ' - '.$provinceEnLabel .' - '. $wardCamLabel);
                }
            }
        });

        $this->info('province not isset: ');
        print_r($this->notIssetProvinces);

        $this->info('district not isset: ');
        print_r($this->notIssetDistricts);
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row)
    {
        $params = [
            'province',
            'city',
            'ward',
            'province_en',
            'city_en',
            'ward_en',
            'dp_code',
        ];

        if (isset($row[''])) {
            unset($row['']);
        }

        $values = array_values($row);
        if (count($values) != count($params)) {
            return [];
        }

        return array_combine($params, $values);
    }
}
