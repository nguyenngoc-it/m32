<?php

namespace Modules\Tools\Commands;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Event\EventService;
use Gobiz\Transformer\TransformerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Order\Events\PublicEvents\OrderChangeStatus;
use Modules\Order\Services\OrderEvent;
use Modules\ShippingPartner\Models\ShippingPartnerLocation;
use Modules\Tools\Events\PublicEvents\SyncLocation;
use Modules\Tools\Validators\SyncLocationMappingValidator;
use Rap2hpoutre\FastExcel\FastExcel;

class SyncLocationMapping
{
    /**
     * @var array
     */
    protected $inputs = [];
    /** @var Location */
    protected $country;

    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * CreateOrder constructor.
     * @param Location $country
     * @param array $inputs
     */
    public function __construct(Location $country, array $inputs)
    {
        $this->inputs  = $inputs;
        $this->country = $country;
        $this->file    = Arr::get($this->inputs, 'file');
    }


    /**
     * @return array
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle(): array
    {
        $line = 1;
        (new FastExcel())->import($this->file, function ($row) use (&$line) {
            $line++;
            $this->processRow($row, $line);
        });

        return $this->errors;
    }

    /**
     * @param array $row
     * @param int $line
     */
    protected function processRow(array $row, int $line)
    {
        $row     = array_map(function ($value) {
            return trim($value);
        }, $row);
        $rowData = array_filter($row, function ($value) {
            return !empty($value);
        });
        if (!count($rowData)) {
            return;
        }
        $rowInput  = $this->makeRow($row);
        $validator = new SyncLocationMappingValidator($this->country, $rowInput);
        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $line,
                'label' => $rowInput['label'] ?? null,
                'errors' => TransformerService::transform($validator),
            ];
            return;
        }

        $this->syncLocation($rowInput);
        $this->syncLocationMapping($rowInput);
    }

    /**
     * @param array $row
     * @return array
     */
    protected function makeRow(array $row): array
    {
        $params = [
            'label',
            'name',
            'type',
            'code',
            'parent_code',
            'postal_code',
            'identity',
            'name_local'
        ];
        $values = array_values(array_map('trim', $row));

        return array_combine($params, $values);
    }

    private function syncLocation(array $rowInput)
    {
        $location = Location::updateOrCreate(
            [
                'parent_code' => $rowInput['parent_code'],
                'code' => $rowInput['code'],
                'type' => $rowInput['type']
            ],
            [
                'label' => $rowInput['label'],
                'postal_code' => $rowInput['postal_code']
            ]
        );
        EventService::publicEventDispatcher()->publish('m32-locations', new SyncLocation($location));
    }

    private function syncLocationMapping(array $rowInput)
    {
        ShippingPartnerLocation::updateOrCreate(
            [
                'partner_code' => $this->inputs['partner_code'],
                'type' => $rowInput['type'],
                'location_code' => $rowInput['code']
            ],
            [
                'parent_location_code' => $rowInput['parent_code'],
                'name' => $rowInput['name'],
                'identity' => $rowInput['identity'],
                'name_local' => $rowInput['name_local']
            ]
        );
    }
}
