<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\Mysql;

class ModelQuery
{
    public function __construct(private Mysql $query, private Model $model) {}

    /**
     * 根据主键查找模型
     */
    public function find($id, array $cols = null)
    {
        $pirmaryKey = $this->model->getPrimaryKey();
        if (is_array($id)) {
            $this->query->whereIn($pirmaryKey, $id);
            return $this->all($cols);
        } else {
            return $this->query->where($pirmaryKey, $id)->first($this->model::class);
        }
    }

    /**
     * 查找模型集合
     */
    public function all(array $cols = null)
    {
        return array_map(
            fn($data) => (clone $this->model)->setAttr($data),
            $cols ? $this->query->all(...$cols) : $this->query->all()
        );
    }

    /**
     * 查找第一个模型
     */
    public function first()
    {
        return $this->query->first($this->model::class);
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
