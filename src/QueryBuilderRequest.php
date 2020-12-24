<?php
namespace Ningwei\QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QueryBuilderRequest extends Request
{
    private static $filterArrayValueDelimiter = ',';
    /**
     * @param Request $request
     * @return static
     */
    static function fromRequest(Request $request): self {
        return static::createFrom($request, new self());
    }

    function filters(): Collection {
        $filterParts = collect($this->query());
        return $filterParts->map(function ($value){
            return $this->formatFilterValue($value);
        });
    }
    protected function formatFilterValue($value) {
        if (is_array($value)){
            return collect($value)->map(function ($item) {
                return $this->formatFilterValue($item);
            })->all();
        }
        if (Str::contains($value, ',')) {
            return explode(',', $value);
        }
        if ($value === 'true') {
            return  true;
        }
        if ($value === false) {
            return false;
        }
        return $value;
    }


    public static function setFilterArrayValueDelimiter(string $filterArrayValueDelimiter): void
    {
        static::$filterArrayValueDelimiter = $filterArrayValueDelimiter;
    }
}