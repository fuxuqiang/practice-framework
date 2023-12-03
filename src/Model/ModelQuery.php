<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\{Mysql, Str};

/**
 * @method array column(string $col, string $idx = null)
 * @method int count()
 * @method self fields(array $fields)
 * @method int|string insert(array $data)
 * @method self limit(int $limit)
 * @method self where(array|string $field, string $operator = null, float|int|string $value = null)
 * @method self whereBetween(string $field, array $values)
 * @method self whereIn(string $field, array $values)
 * @method self whereLike(string|array $field, string $value)
 * @method self offset(int $offset)
 * @method self orderBy(string $field)
 * @method self orderByDesc(string $field)
 * @method string|null value(string $field)
 */
readonly class ModelQuery
{
    private Mysql $query;

    public function __construct(private Model $model)
    {
        $this->query = $model::query();
    }

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
     * 模型是否存在
     */
    public function exists(): bool
    {
        return (bool)$this->query->first([$this->model->getPrimaryKey()]);
    }

    /**
     * 获取默认的表字段
     */
    private function getFields(?array $fields): array
    {
        return $fields ?: array_map(fn($field) => Str::snake($field->name), $this->model->getProperties());
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
