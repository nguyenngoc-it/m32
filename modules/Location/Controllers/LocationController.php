<?php

namespace Modules\Location\Controllers;

use App\Base\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Modules\Location\Models\Location;
use Modules\Location\Validators\ListLocationValidator;

class LocationController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $input     = $this->request()->only(ListLocationValidator::$keyRequests);
        $validator = new ListLocationValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $locations = Location::query()
            ->where('type', $input['type'])
            ->where(function (Builder $query) use ($input) {
                return isset($input['parent_code'])
                    ? $query->where('parent_code', $input['parent_code'])
                    : $query;
            })
            ->where('active', 1)
            ->orderBy('label')
            ->get();

        return $this->response()->success(['locations' => $locations]);
    }
}
