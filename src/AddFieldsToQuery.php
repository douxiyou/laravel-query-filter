<?php


use Illuminate\Support\Collection;

trait AddFieldsToQuery
{
    /**
     * @var Collection
     */
    protected $filterFields;
    function additionalFields($fields): self {
        $fields = is_array($fields) ?? func_get_args();
        $this->filterFields = collect($fields)
            ->map(function (string $item) {
                return $this->prepareField($item);
            });
        return $this;
    }
    protected function prepareField(string $field, ?string $table = null): string {
        // 获取表名
        if (!$table) {
            $table = $this->getModel();
        }
        return '';
    }
}