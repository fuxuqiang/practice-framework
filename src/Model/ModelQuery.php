<?php

namespace Fuxuqiang\Framework\Model;

class ModelQuery
{
    public function __construct(
        private \Fuxuqiang\Framework\Mysql $query,
        private Model $model
    ) {}

    /**
     * 根据主键查找模型
     */
    public function find($id, array $cols = null)
    {
        $pirmaryKey = $this->model->getPrimaryKey();
        if (is_array($id)) {
            $query = $this->query->whereIn($pirmaryKey, $id);
            return array_map(
                fn($data) => (clone $this->model)->setAttr($data),
                $cols ? $query->all(...$cols) : $query->all()
            );
        } else {
            return $this->query->where($pirmaryKey, $id)->get(get_class($this->model));
        }
    }

    /**
     * 动态调用Mysql类方法
     */
    public function __call($name, $args)
    {
        return $this->query->$name(...$args);
    }
}
