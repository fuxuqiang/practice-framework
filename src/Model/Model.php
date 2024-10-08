<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\{Mysql, Str};

/**
 * @method static static[] all(array $fields = null)
 * @method static int count()
 * @method static bool exists(string $field, string $operator = null, string|int|float $value = null)
 * @method static ModelQuery fields(array $fields)
 * @method static static|static[] find($id, array $fields = null)
 * @method static static first()
 * @method static static orderBy(string $field)
 * @method static bool truncate()
 * @method static ModelQuery where(array|string $field, string $operator = null, string|int|float $value = null)
 * @method static ModelQuery whereBetween(string $field, array $values))
 * @method static ModelQuery whereLike(string|array $field, string $value)
 * @method static ModelQuery whereRaw(string $cond, array $values = [])
 */
#[\AllowDynamicProperties]
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
     * @var bool
     */
    protected bool $exists = false;

    public final function __construct() {}

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
     * 设置模型是否存在
     */
    /**
     * @param bool $exists
     */
    public function setExists(bool $exists): void
    {
        $this->exists = $exists;
    }

    /**
     * 保存至数据库
     */
    public function save(): void
    {
        $data = $this->toArray();
        if ($this->exists) {
            $this->innerQuery()->update($data);
        } else {
            $this->{$this->primaryKey} = self::query()->insert($data);
        }
    }

    /**
     * 批量保存至数据库
     * @param static[] $data
     */
    public static function batchSave(array $data): void
    {
        self::query()->insert(array_map(fn($item) => $item->toArray(), $data));
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
     * 转为数组
     */
    public function toArray(): array
    {
        $data = [];
        foreach ($this->getProperties() as $property) {
            $data[Str::snake($property->name)] = $property->isInitialized($this) ? $property->getValue($this) : null;
        }
        return $data;
    }

    /**
     * 获取模型字段
     * @return \ReflectionProperty[]
     */
    public function getProperties(): array
    {
        return (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
    }

    /**
     * 获取限定主键WHERE条件的数据连接
     */
    private function innerQuery(): Mysql
    {
        return self::query()->where($this->primaryKey, $this->{$this->primaryKey});
    }
}
