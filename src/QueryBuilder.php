<?php
namespace Ningwei\QueryBuilder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Traits\ForwardsCalls;
use Ningwei\QueryBuilder\Exceptions\InvalidFilterQuery;
use Ningwei\QueryBuilder\Exceptions\InvalidSubject;

class QueryBuilder implements \ArrayAccess
{
    use ForwardsCalls, AppendsAttributesToResults;
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $filters;
    /**
     * @var QueryBuilderRequest
     */
    protected $request;
    /**
     * @var EloquentBuilder|Relation
     */
    protected $subject;
    /**
     * QueryBuilder constructor.
     * @param EloquentBuilder|string|Relation $subject 查询主体
     * @param Request|null $request
     */
    public function __construct($subject, ?Request $request)
    {
//        $this->initializeSubject($subject)->initializeRequest($request??app(Request::class));
        throw_unless($subject instanceof EloquentBuilder || $subject instanceof Relation, InvalidSubject::make($subject));
        $this->subject = $subject;
        $this->request = $request
            ? QueryBuilderRequest::fromRequest($request)
            : app(QueryBuilderRequest::class);
    }

    /**
     * @param $name
     * @param $arguments
     * @return QueryBuilder|mixed
     */
    function __call($name, $arguments)
    {
        $result = $this->subject->{$name}(...$arguments);
        // 如果调用对方法返回的对象是查询主体，那就返回$this,以继续链式操作
        if ($result === $this->subject) {
            return $this;
        }
        // 统一类型
        // TODO::::: php8 match(){}
        if ($result instanceof Model) {
            $tmpArg = collect([$result]);
        }
        if ($result instanceof Collection) {
            $tmpArg = collect($result);
        }
        if ($result instanceof LengthAwarePaginator) {
            $tmpArg = collect($result->items());
        }
        $this->appendToResult($tmpArg);
        return $result;
    }

    /**
     * @return EloquentBuilder|Relation
     */
    function getSubject() {
        return $this->subject;
    }

    /**
     * @return EloquentBuilder
     */
    function getEloquentBuilder(): EloquentBuilder
    {
        if ($this->subject instanceof EloquentBuilder) {
            return $this->subject;
        }

        if ($this->subject instanceof Relation) {
            return $this->subject->getQuery();
        }

        throw InvalidSubject::make($this->subject);
    }

    /**
     * 实例化query builder
     * @param EloquentBuilder|string| Relation $subject 查询主体
     * @param Request|null $request
     * @return QueryBuilder
     */
    static function for($subject, ?Request $request = null): self {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }
        return new static($subject, $request);
    }

    /**
     * @description 添加过滤字段(过滤器对象)
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
//        if (!config('filter.is_exception')) {
//            return;
//        }
        $this->ensureAllFiltersExist();
        $this->addFiltersToQuery();
        return $this;
    }
    protected function addFiltersToQuery()
    {
        $this->filters->each(function (AllowedFilter $filter) {
            // 验证过滤器是否被包含在请求中
            if ($this->isFilterRequested($filter)) {
                // 获取对应过滤器的值
                $value = $this->request->filters()->get($filter->getName());
                $filter->filter($this, $value);

                return;
            }
//            if ($filter->hasDefault()) {
//                $filter->filter($this, $filter->getDefault());
//
//                return;
//            }
        });
    }

    /**
     * 
     * @param AllowedFilter $allowedFilter
     * @return bool
     */
    protected function isFilterRequested(AllowedFilter $allowedFilter): bool
    {
        return $this->request->filters()->has($allowedFilter->getName());
    }

    /**
     * @description 如果请求中包含指定的过滤字段以外的内容，是否发出异常警告
     */
    protected function ensureAllFiltersExist()
    {
        $filterNames = $this->request->filters()->keys();
        $allowedFilterNames = $this->filters->map(function (AllowedFilter $allowedFilter) {
            return $allowedFilter->getName();
        });

        $diff = $filterNames->diff($allowedFilterNames);
        if ($diff->count()) {
            throw InvalidFilterQuery::filtersNotAllowed($diff, $allowedFilterNames);
        }
    }



    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->subject[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->subject[$offset];
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->subject[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->subject[$offset]);
    }

    /**
     * @param $name
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    public function __get($name)
    {
        return $this->subject->{$name};
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->subject->{$name} = $value;
    }
}
