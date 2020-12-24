<?php
namespace Ningwei\QueryBuilder\Filters;
use Illuminate\Database\Eloquent\Builder;

interface FilterInternace
{
    function __invoke(Builder $query, $value, string $property);
}
