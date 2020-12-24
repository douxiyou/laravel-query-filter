<?php
namespace Ningwei\QueryBuilder\Filters;

use Illuminate\Database\Eloquent\Builder;

class FilterCallback implements FilterInternace
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * FilterCallback constructor.
     * @param $callback
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param Builder $query
     * @param $value
     * @param string $property
     * @return mixed
     */
    function __invoke(Builder $query, $value, string $property)
    {
        return call_user_func($this->callback, $query, $value, $property);
    }
}