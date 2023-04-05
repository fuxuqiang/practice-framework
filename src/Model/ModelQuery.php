<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\{Mysql, Str};

/**
 * @method self fields(array $fields)
 * @method self limit(int $offset, int $rowCount = null)
 * @method self where(array|string $field, string $operator = null, float|int|string $value = null)
 * @method self whereBetween(string $field, array $values)
 * @method self whereIn(string $field, array $values)
 * @method self whereLike(string|array $field, string $value)
 * @method self orderByDesc(string $field)
 * @method value(string $field)
 * @method int|string insert(array $data)
 */
class ModelQuery
{
    public function __construct(private readonly Mysql $query, private readonly Model $model) {}

    /**
     * 根据主键查找模型
     */
    public function find($id, array $fields = null): array|Model|null
    {
        $primaryKey = Str::snake($this->model->getPrimaryKey());
        $fields = $this->getFields($fields);
        if (is_array($id)) {
            $this->query->whereIn($primaryKey, $id);
            return $this->all($fields);
        } else {
            return $this->where($primaryKey, $id)->first($fields);
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
    public function first(array $fields = null): ?Model
    {
        if ($data = $this->query->first($this->getFields($fields))) {
            return $this->model->setAttr($data);
        }
        return null;
    }

    /**
     * 查找第一个模型，查不到则抛出异常
     * @throws ModelNotFoundException
     */
    public function firstOrFail(array $fields = null)
    {
        return ($model = $this->first($fields)) ? $model : throw new ModelNotFoundException;
    }

    /**
     * 获取默认的表字段
     */
    private function getFields(?array $fields): array
    {
        return $fields ?: array_map(
            fn($field) => Str::snake($field->name),
            (new \ReflectionObject($this->model))->getProperties(\ReflectionProperty::IS_PUBLIC)
        );
    }

    /**
     * 动态调用Mysql类方法
     */
    public function __call($name, $args)
    {
        if (method_exists($this->model, $method = 'scope'.ucfirst($name))) {
            $result = $this->model->$method($this, ...$args);
        } else {
            $result = $this->query->$name(...$args);
        }
        return $result instanceof Mysql ? $this : $result;
    }
}
