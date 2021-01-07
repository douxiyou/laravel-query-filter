<?php
namespace Ningwei\QueryBuilder\Filters;
use Illuminate\Database\Eloquent\Builder;

interface FilterInternace
{
    /**
     * 通过这个方法使用所有过滤器，过滤器对入口
     * @param Builder $query
     * @param $value
     * @param string $property
     * @return mixed
     */
    function __invoke(Builder $query, $value, string $property);
}
