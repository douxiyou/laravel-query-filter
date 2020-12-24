<?php
namespace Ningwei\QueryBuilder;

use Illuminate\Support\Collection;
use Ningwei\QueryBuilder\Filters\FilterInternace;
use Ningwei\QueryBuilder\Filters\FilterCallback;
use Ningwei\QueryBuilder\Filters\FilterPartial;

class AllowedFilter
{
    /**
     * @var string 
     */
    protected $name;
    /**
     * @var FilterInternace 
     */
    protected $filterClass;
    /**
     * @var string 
     */
    protected $internalName;
    /**
     * @var Collection 
     */
    protected $ignord;
    public function __construct(string $name, FilterInternace $filterClass, ?string $internalName = null)
    {
        $this->name = $name;
        $this->filterClass = $filterClass;
        $this->internalName = $internalName ?? $name;
        $this->ignord = Collection::make();
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function filter(QueryBuilder $query, $value)
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        if (is_null($valueToFilter)) {
            return;
        }

        ($this->filterClass)($query->getEloquentBuilder(), $valueToFilter, $this->internalName);
    }
    protected function resolveValueForFiltering($value)
    {
        if (is_array($value)) {
            $remainingProperties = array_diff_assoc($value, $this->ignored->toArray());

            return ! empty($remainingProperties) ? $remainingProperties : null;
        }

        return ! $this->ignored->contains($value) ? $value : null;
    }
    public static function setFilterArrayValueDelimiter(string $delimiter = null): void
    {
        if (isset($delimiter)) {
            QueryBuilderRequest::setFilterArrayValueDelimiter($delimiter);
        }
    }
    /**---------------------------------------------------------------------------------**/
    /**
     * @param string $name
     * @param null $internalName
     * @param bool $addRelationConstraint
     * @param string|null $arrayValueDelimiter
     * @return static
     */
    public static function partial(string $name, $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FilterPartial($addRelationConstraint), $internalName);
    }
    /**
     * 添加进入的查询回调
     * @param string $name
     * @param $callback
     * @param null $internalName
     * @param string|null $arrayValueDelimiter
     * @return static
     */
    public static function callback(string $name, $callback, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);
        return new static($name, new FilterCallback($callback), $internalName);
    }
}