<?php

namespace Modules\Order\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Application\Model\Application;
use Modules\Service;

/**
 * Class ListStocks
 * @package Modules\Stock\Commands
 */
class ListOrders
{
    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var mixed|string
     */
    protected $sort = 'desc';

    /**
     * @var mixed|string
     */
    protected $sortBy = 'id';

    /**
     * @var Application
     */
    protected $application;

    /**
     * ListProduct constructor.
     * @param array $filter
     * @param User $user
     */
    public function __construct(array $filter, Application $application)
    {
        $this->filter   = $filter;
        $this->sort     = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy   = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'updated_at';
        $this->application     = $application;
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $page = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        $filter = $this->filter;

        foreach (['sort', 'sortBy', 'page', 'per_page'] as $p) {
            if(isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::order()->orderQuery($filter)->getQuery();
        $query = $this->setFilter($query);

        $query = $this->setOrderBy($query);
        $query = $this->withData($query);

        return $query->paginate($per_page, ['*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function withData($query)
    {
        return $query->with(['shippingPartner']);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy  = $this->sortBy;

        $sort    = $this->sort;
        $table   = 'orders';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setFilter($query)
    {
        return $query;
    }
}
