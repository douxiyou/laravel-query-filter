<?php
namespace Ningwei\QueryBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait AppendsAttributesToResults
{
    protected function appendToResult(Collection $collection) {
        return $collection->each(function (Model $result) {
            return $result->append($this->request->appends()->toArray());
        });
    }
}