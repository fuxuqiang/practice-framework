<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\Mysql;

class ModelQuery
{
    public function __construct(private readonly Mysql $query, private readonly Model $model) {}

    /**
     * 根据主键查找模型
     */
    public function find($id, array $fields = null)
    {
        $primaryKey = $this->model->getPrimaryKey();
        $fields = $this->getFields($fields);
        if (is_array($id)) {
            $this->query->whereIn($primaryKey, $id);
            return $this->all($fields);
        } else {
            return $this->query->where($primaryKey, $id)->first($fields, $this->model::class);
        }
    }

    /**
     * 查找模型集合
     */
    public function all(array $fields = null): array
    {
        return array_map(
            fn($data) => (clone $this->model)->setAttr($data),
            $this->query->all($this->getFields($fields))
        );
    }

    /**
     * 查找第一个模型
     */
    public function first(array $fields = null)
    {
        return $this->query->first($this->getFields($fields), $this->model::class);
    }

    /**
     * 获取默认的表字段
     */
    private function getFields(?array $fields): array
    {
        return $fields ?: array_map(
            fn($field) => $field->getName(),
            (new \ReflectionClass($this->model))->getProperties(\ReflectionProperty::IS_PUBLIC)
        );
    }

    /**
     * 动态调用Mysql类方法
     */
    public function __call($name, $args)
    {
        if (method_exists($this->model, $method = 'scope' . ucfirst($name))) {
            $result = $this->model->$method($this->query, ...$args);
        } else {
            $result = $this->query->$name(...$args);
        }
        return $result instanceof Mysql ? $this : $result;
    }
}
