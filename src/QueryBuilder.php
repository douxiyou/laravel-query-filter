<?php
namespace Ningwei\QueryBuilder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\ForwardsCalls;
use Ningwei\QueryBuilder\Exceptions\InvalidSubject;

class QueryBuilder implements \ArrayAccess
{
    use \AddFieldsToQuery, ForwardsCalls;
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
        $this->initializeSubject($subject)->initializeRequest($request??app(Request::class));
    }
    protected function initializeSubject($subject): self {
        throw_unless($subject instanceof EloquentBuilder || $subject instanceof Relation, InvalidSubject::make($subject));
        $this->subject = $subject;
        return $this;
    }
    protected function initializeRequest(?Request $request = null): self
    {
        $this->request = $request
            ? QueryBuilderRequest::fromRequest($request)
            : app(QueryBuilderRequest::class);

        return $this;
    }
    function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->subject, $name, $arguments);
        // 如果调用对方法返回的对象是查询主体，那就返回$this,以继续链式操作
        // 不直接返回result的原因就是可能会失去QueryBuilder提供的方法
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
     * @param EloquentBuilder|string| Relation $subject 查询主体
     * @param Request|null $request
     * @return $this
     */
    static function for($subject, ?Request $request): self {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }
        return new static($subject, $request);
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