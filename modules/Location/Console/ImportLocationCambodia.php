<?php

namespace Modules\Location\Console;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Support\Helper;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportLocationCambodia extends Command
{
    protected $signature = 'location:import_location_cambodia';
    protected $description = 'import cambodia locations';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        Location::updateOrCreate([
            'code' => Location::COUNTRY_CODE_CAMBODIA,
            'type' => Location::TYPE_COUNTRY,
            'parent_code' => ''
        ], ['label' => Location::COUNTRY_CODE_CAMBODIA,]);

        $codes = [];
        $provinceCreated = [];
        $districtCreated = [];


        (new FastExcel)->import(storage_path('location_cambodia.xlsx'), function ($line) use (&$codes, &$provinceCreated, &$districtCreated) {

            $provinceLabel = trim($line['province']);
            $provinceLabelClean = Helper::clean($provinceLabel);

            $districtLabel = trim($line['district']);
            $districtLabelClean = Helper::clean($districtLabel);

            if (empty($codes['province'][$provinceLabelClean])) {
                $codes['province'][$provinceLabelClean] = rand(111, 999);
                while (count($codes['province']) > count(array_unique($codes['province']))) {
                    unset($codes['province'][$provinceLabelClean]);
                    $codes['province'][$provinceLabelClean] = rand(111, 999);
                }
            }
            if (empty($codes['district'][$provinceLabelClean . $districtLabelClean])) {
                $codes['district'][$provinceLabelClean . $districtLabelClean] = $codes['province'][$provinceLabelClean] . rand(111, 999);
                while (count($codes['district']) > count(array_unique($codes['district']))) {
                    unset($codes['district'][$provinceLabelClean . $districtLabelClean]);
                    $codes['district'][$provinceLabelClean . $districtLabelClean] = $codes['province'][$provinceLabelClean] . rand(111, 999);
                }
            }

            $provinceCode = 'CAM' . $codes['province'][$provinceLabelClean];
            $districtCode = 'CAM' . $codes['district'][$provinceLabelClean . $districtLabelClean];

            if(!isset($provinceCreated[$provinceLabel])) {
                $province     = Location::updateOrCreate([
                    'code' => $provinceCode,
                    'type' => Location::TYPE_PROVINCE,
                    'parent_code' => Location::COUNTRY_CODE_CAMBODIA
                ], ['label' => $provinceLabel]);
                $this->info('inserted province ' . $province->label);

                $provinceCreated[$provinceLabel] = true;
            }

            if(!isset($districtCreated[$districtLabel])) {
                $district = Location::updateOrCreate([
                    'code' => $districtCode,
                    'type' => Location::TYPE_DISTRICT,
                    'parent_code' => $provinceCode
                ], ['label' => $districtLabel,]);
                $this->info('inserted district ' . $district->label);

                $districtCreated[$districtLabel] = true;
            }
        });
    }
}
