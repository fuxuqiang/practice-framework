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

    public function find($id)
    {
        if (is_array($id)) {
            return array_map(function ($data) {
                return (new $this->model)->setAttr($data);
            }, $this->query->whereIn('id', $id)->all());
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
