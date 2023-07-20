<?php /** @noinspection ALL */

namespace Modules\Tools\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Generator;
use Gobiz\Support\Helper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Modules\Location\Models\Location;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerProvider;
use Modules\Tools\Commands\SyncLocationMapping;
use Illuminate\Support\Collection;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManualToolsController extends Controller
{
    /**
     * Giả lập webhook fake chuyển trạng thái vận đơn từ dvvc
     *
     * @return JsonResponse
     */
    public function webhookEmulator()
    {
        $inputs    = $this->request()->only([
            'shipping_partner',
            'tracking_no',
            'status'
        ]);
        $validator = Validator::make($inputs, [
            'shipping_partner' => 'required',
            'tracking_no' => 'required',
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        Artisan::call('running_man', array_merge($inputs, ['func' => 1]));

        return $this->response()->success(1);
    }

    /**
     * Đồng bộ Locations và mapping location cho dvvc cụ thể
     *
     * @return JsonResponse
     */
    public function syncLocationMapping()
    {
        $inputs    = $this->request()->only([
            'partner_code',
            'file'
        ]);
        $validator = Validator::make($inputs, [
            'partner_code' => 'required',
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $partnerCode = $inputs['partner_code'];
        $country     = array_map(function (ShippingPartnerProvider $provider) use ($partnerCode) {
            if ($provider->getCode() == $partnerCode) {
                return $provider->getCountryCode();
            }
            return null;
        }, Service::shippingPartner()->providers());
        $country     = array_values(array_filter($country));
        if (empty($country)) {
            return $this->response()->error('partner_code', 'not_found_country');
        }
        $country = Location::query()->where('code', $country[0])->first();

        $errors = (new SyncLocationMapping($country, $inputs))->handle();

        return $this->response()->success(compact('errors'));
    }


    /**
     * @param Builder $builder
     * @return Generator
     */
    protected function trackingStatisticGenerator(Builder $builder): Generator
    {
        foreach ($builder->cursor() as $trackingStatistic) {
            yield $trackingStatistic;
        }
    }
}
