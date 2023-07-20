<?php

namespace Modules\GGE\Console;

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
    protected $signature = 'gge:map-locations-by-file';

    protected $description = 'Mapping GGE locations with system locations';

    protected $listProvinceUpdate = [];

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        (new FastExcel)->import(storage_path('gge_locations.xlsx'), function ($line) {
            $province   = $line['province'];
            $city       = $line['city'];
            $district   = $line['district'];
            $postalCode = $line['postal'];
            $explode    = explode('/', $postalCode);

            if (isset($explode[0])) {
                $postalCode = $explode[0];
            }

            // Update for provinces
            if (!in_array($province, $this->listProvinceUpdate)) {
                $this->listProvinceUpdate[] = $province;

                // Lưu thông tin locations của GGE
                $dataResource = [
                    'type'        => Location::TYPE_PROVINCE,
                    'identity'    => $province,
                    'code'        => $province,
                    'postal_code' => $postalCode,
                ];
                $shippingPartnerLocation = $this->makeShippingPartnerLocation($dataResource);
                $this->mapShippingPartnerLocation($shippingPartnerLocation);
                $this->info("updated province {$province}");
            }

            // Update for cities
            // Lưu thông tin locations của GGE
            $dataResource = [
                'type'            => Location::TYPE_DISTRICT,
                'identity'        => $city,
                'code'            => $city,
                'postal_code'     => $postalCode,
                'parent_identity' => $province,
            ];
            $shippingPartnerLocation = $this->makeShippingPartnerLocation($dataResource);
            $this->mapShippingPartnerLocation($shippingPartnerLocation);
            $this->info("updated city {$city}");

            // Update for districts
            // Lưu thông tin locations của GGE
            $dataResource = [
                'type'            => Location::TYPE_WARD,
                'identity'        => $district,
                'code'            => $district,
                'postal_code'     => $postalCode,
                'parent_identity' => $province,
            ];
            $shippingPartnerLocation = $this->makeShippingPartnerLocation($dataResource);
            $this->mapShippingPartnerLocation($shippingPartnerLocation);
            $this->info("updated district {$district}");
        });
    }

    /**
     * make shipping partner location from GGE
     *
     * @param array $dataResource
     * @return ShippingPartnerLocation $shippingPartnerLocation
     */
    protected function makeShippingPartnerLocation(array $dataResource)
    {
        $query = [
            'partner_code' => ShippingPartner::PARTNER_GGE,
            'type'         => data_get($dataResource, 'type'),
            'code'         => data_get($dataResource, 'code'),
        ];
        $data = [
            'partner_code'    => ShippingPartner::PARTNER_GGE,
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
