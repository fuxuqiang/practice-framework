<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\{Mysql, Str};

/**
 * @method static array all(array $fields = null)
 * @method static bool exists(string $field, string $operator = null, string|int|float $value = null)
 * @method static ModelQuery fields(array $fields)
 * @method static static|array find($id, array $fields = null)
 * @method static static first()
 * @method static static orderByDesc(string $field)
 * @method static bool truncate()
 * @method static ModelQuery where(array|string $field, string $operator = null, string|int|float $value = null)
 * @method static ModelQuery whereRaw(string $cond, array $values = [])
 */
abstract class Model
{
    /**
     * @var Connector
     */
    private static Connector $connector;

    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * 设置获取数据库操作类的方法
     */
    public static function setConnector(Connector $connector): void
    {
        self::$connector = $connector;
    }

    /**
     * 获取表主键
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * 保存至数据库
     */
    public function save(): void
    {
        $data = [];
        foreach ((new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isInitialized($this)) {
                $data[Str::snake($property->name)] = $property->getValue($this);
            }
        }
        if (empty($this->{$this->primaryKey})) {
            self::query()->insert($data);
        } else {
            $this->innerQuery()->update($data);
        }
    }

    /**
     * 设置模型属性
     */
    public function setAttr($attrs): static
    {
        foreach ($attrs as $key => $attr) {
            $this->{Str::camel($key)} = $attr;
        }
        return $this;
    }

    /**
     * 动态调用查询方法
     */
    public function __call($name, $args)
    {
        return $this->innerQuery()->$name(...$args);
    }

    /**
     * 通过静态方法动态调用查询类的方法
     */
    public static function __callStatic($name, $args)
    {
        return (new ModelQuery(new static))->$name(...$args);
    }

    /**
     * 获取数据库连接
     */
    public static function query(): Mysql
    {
        return self::$connector->connect()
            ->table(Str::snake(basename(str_replace('\\', '/', static::class))));
    }

    /**
     * 获取限定主键WHERE条件的数据连接
     */
    private function innerQuery(): Mysql
    {
        return self::query()->where($this->primaryKey, $this->{$this->primaryKey});
    }
}
