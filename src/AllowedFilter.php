<?php
namespace Ningwei\QueryBuilder;

use Illuminate\Support\Collection;
use Ningwei\QueryBuilder\Filters\FilterInternace;
use Ningwei\QueryBuilder\Filters\FilterCallback;
use Ningwei\QueryBuilder\Filters\FilterPartial;
use Ningwei\QueryBuilder\Filters\FilterExact;
use Ningwei\QueryBuilder\Filters\FilterScope;
use Ningwei\QueryBuilder\Filters\FilterTrashed;
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
     * @description 内部查询名
     * @var string 
     */
    protected $internalName;
    /**
     * @var Collection 
     */
    protected $ignored;
    public function __construct(string $name, FilterInternace $filterClass, ?string $internalName = null)
    {
        $this->name = $name;
        $this->filterClass = $filterClass;
        $this->internalName = $internalName ?? $name;
        $this->ignored = Collection::make();
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
        // 调用Filter __invoke 方法
        ($this->filterClass)($query->getEloquentBuilder(), $valueToFilter, $this->internalName);
    }
    /**
     * @description 去除需要忽略的值
     * @param array|string $value
     * @return array|string|null
     */
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
    /** 语法糖---------------------------------------------------------------------------------**/
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
     * 回调形式查询过滤
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

    /**
     * @description 精确查询过滤
     * @param string $name
     * @param string|null $internalName
     * @param bool $addRelationConstraint
     * @param string|null $arrayValueDelimiter
     * @return static
     */
    public static function exact(string $name, ?string $internalName = null, bool $addRelationConstraint = true, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FilterExact($addRelationConstraint), $internalName);
    }
    /**
     * @description 用来支持laravel 查询功能中scope
     * @param string $name
     * @param null $internalName
     * @param string|null $arrayValueDelimiter
     * @return static
     */
    public static function scope(string $name, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, new FilterScope(), $internalName);
    }

    /**
     * @description 支持软删除的过滤器
     * @param string $name
     * @param null $internalName
     * @return static
     */
    public static function trashed(string $name = 'trashed', $internalName = null): self
    {
        return new static($name, new FilterTrashed(), $internalName);
    }
    /**
     * @description 定制过滤器
     * @param string $name
     * @param FilterInternace $filterClass
     * @param null $internalName
     * @param string|null $arrayValueDelimiter
     * @return static
     */
    public static function custom(string $name, FilterInternace $filterClass, $internalName = null, string $arrayValueDelimiter = null): self
    {
        static::setFilterArrayValueDelimiter($arrayValueDelimiter);

        return new static($name, $filterClass, $internalName);
    }
    /**-------------------可有可无 -----------------------*/
    public function isForFilter(string $filterName): bool
    {
        return $this->name === $filterName;
    }

    public function ignore(...$values): self
    {
        $this->ignored = $this->ignored
            ->merge($values)
            ->flatten();

        return $this;
    }

    public function getIgnored(): array
    {
        return $this->ignored->toArray();
    }

    public function getInternalName(): string
    {
        return $this->internalName;
    }

    public function
    default($value): self
    {
        $this->default = $value;

        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function hasDefault(): bool
    {
        return isset($this->default);
    }
}