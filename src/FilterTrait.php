<?php
namespace Ningwei\QueryBuilder;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Ningwei\QueryBuilder\Exceptions\InvalidFilterQuery;

trait FilterTrait
{
    /**
     * @var Collection
     */
    protected $filters;

    /**
     * 添加过滤字段
     * @param array|string $filters
     * @return $this
     */
    function additionalFilter(array|string $filters): self {
        $filters = is_array($filters) ? $filters : func_get_args();
        $this->filters = collect($filters)
            ->map(function ($filter) {
                if ($filter instanceof AllowedFilter) {
                    return $filter;
                }
                return AllowedFilter::partial($filter);
            });
        $this->ensureAllFiltersExist();
        $this->addFiltersToQuery();
        return $this;
    }
    protected function addFiltersToQuery()
    {
        $this->filters->each(function (AllowedFilter $filter) {
            if ($this->isFilterRequested($filter)) {
                $value = $this->request->filters()->get($filter->getName());
                $filter->filter($this, $value);

                return;
            }

            if ($filter->hasDefault()) {
                $filter->filter($this, $filter->getDefault());

                return;
            }
        });
    }
    protected function isFilterRequested(AllowedFilter $allowedFilter): bool
    {
        return $this->request->filters()->has($allowedFilter->getName());
    }
    protected function ensureAllFiltersExist()
    {
        if (!config('filter.is_exception')) {
            return;
        }
        $filterNames = $this->request->filters()->keys();
        $allowedFilterNames = $this->filters->map(function (AllowedFilter $allowedFilter) {
            return $allowedFilter->getName();
        });

        $diff = $filterNames->diff($allowedFilterNames);
        dd($diff);
        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }
}