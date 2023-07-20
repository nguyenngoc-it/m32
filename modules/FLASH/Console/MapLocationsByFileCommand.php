<?php

namespace Modules\FLASH\Console;

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
    protected $signature = 'flash:map-locations-by-file {--type=update_postalcode}';

    protected $description = 'Mapping Flash locations with system locations';

    protected $listProvinceUpdate = [];

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        $typeOption = $this->option('type');
        if ($typeOption == 'update_postalcode') {
            (new FastExcel)->import(storage_path('locations_map_postal_code.xlsx'), function ($line) {
                // dd($line);
                $locationId         = $line['id'];
                $locationPostalCode = $line['postal_code'];
                $locationName       = $line['label'];

                $location = Location::find($locationId);
                if ($location) {
                    $location->postal_code = $locationPostalCode;
                    $location->save();
                    $this->info("Update location postal code success : {$locationPostalCode} for {$locationName}");
                }
            });
        
        } else {
            (new FastExcel)->import(storage_path('flash_locations.xlsx'), function ($line) {
                $province     = $line['province_en_name'];
                $provinceCode = $line['province_code'];
                $district     = $line['city_en_name'];
                $districtCode = $line['city_code'];
                $postalCode   = $line['postal_code'];
                $explode = explode(',', $postalCode);

                if (isset($explode[0])) {
                    $postalCode = $explode[0];
                }

                // Update for provinces
                if (!in_array($provinceCode, $this->listProvinceUpdate)) {
                    $this->listProvinceUpdate[] = $provinceCode;

                    // Lưu thông tin locations của FLASH
                    $dataResource = [
                        'type'        => Location::TYPE_PROVINCE,
                        'identity'    => $province,
                        'code'        => $provinceCode,
                        'postal_code' => $postalCode,
                    ];
                    $shippingPartnerLocation = $this->makeShippingPartnerLocation($dataResource);
                    $this->mapShippingPartnerLocation($shippingPartnerLocation);
                    $this->info("updated province {$province} - Code: {$provinceCode}");
                }

                // Update for districts
                // Lưu thông tin locations của FLASH
                $dataResource = [
                    'type'            => Location::TYPE_DISTRICT,
                    'identity'        => $district,
                    'code'            => $districtCode,
                    'postal_code'     => $postalCode,
                    'parent_identity' => $provinceCode,
                ];
                $shippingPartnerLocation = $this->makeShippingPartnerLocation($dataResource);
                $this->mapShippingPartnerLocation($shippingPartnerLocation);
                $this->info("updated district {$district} - Code: {$districtCode}");
            });
        }
    }

    /**
     * make shipping partner location from flash
     *
     * @param array $dataResource
     * @return ShippingPartnerLocation $shippingPartnerLocation
     */
    protected function makeShippingPartnerLocation(array $dataResource)
    {
        $query = [
            'partner_code' => "FLASH",
            'type'         => data_get($dataResource, 'type'),
            'code'         => data_get($dataResource, 'code'),
        ];
        $data = [
            'partner_code'    => "FLASH",
            'type'            => data_get($dataResource, 'type'),
            'identity'        => data_get($dataResource, 'identity'),
            'code'            => data_get($dataResource, 'code'),
            'postal_code'     => data_get($dataResource, 'postal_code'),
            'parent_identity' => data_get($dataResource, 'parent_identity'),
        ];

        $shippingPartnerLocation = ShippingPartnerLocation::updateOrCreate($query, $data);

        return $shippingPartnerLocation;
    }

    protected function mapShippingPartnerLocation(ShippingPartnerLocation $shippingPartnerLocation)
    {
        // Lấy thông tin bảng locations theo label
        $query = [
            'type'  => $shippingPartnerLocation->type,
            'label' => $shippingPartnerLocation->identity,
        ];
        
        $location = Location::where($query)->get()->first();
        if ($location instanceof Location) {
            $shippingPartnerLocation->name                 = $location->label;
            $shippingPartnerLocation->location_code        = $location->code;
            $shippingPartnerLocation->parent_location_code = $location->parent_code;
            $shippingPartnerLocation->save();
        }
    }
}
