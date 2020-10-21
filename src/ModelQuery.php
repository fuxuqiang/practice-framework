<?php

namespace Fuxuqiang\Framework;

class ModelQuery
{
    private $model, $query;

    public function __construct(Mysql $query, string $model)
    {
        $this->query = $query;
        $this->model = $model;
    }

    public function find($id, array $cols = null)
    {
        if (is_array($id)) {
            $query = $this->query->whereIn('id', $id);
            return array_map(function ($data) {
                return (new $this->model)->setAttr($data);
            }, $cols ? $query->all(...$cols) : $query->all());
        } else {
            $model = new $this->model;
            $model->id = $id;
            return $model;
        }
    }

    public function __call($name, $args)
    {
        $this->query->$name(...$args);
        return $this;
    }
}
