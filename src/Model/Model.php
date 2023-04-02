<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\{Connector, Mysql};

/**
 * @method static ModelQuery fields(array $fields)
 * @method static ModelQuery where(array|string $field, string $operator = null, string|int|float $value = null)
 * @method static static first()
 */
class Model
{
    /**
     * @var string
     */
    private string $table;

    /**
     * @var Connector
     */
    private static Connector $connector;

    /**
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * @param string|null $table
     */
    public function __construct(string $table = null)
    {
        $this->table = $table ?: static::getTable();
    }

    /**
     * 设置获取数据库操作类的方法
     */
    public static function setConnector(Connector $connector): void
    {
        self::$connector = $connector;
    }

    /**
     * 获取当前表名
     */
    public static function getTable(): string
    {
        return strtolower(
            preg_replace(
                '/(?<=[a-z])[A-Z]/',
                '_$0',
                basename(str_replace('\\', '/', static::class))
            )
        );
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
    public function save(): int|string
    {
        $id = $this->query()->insert(
            array_map(
                fn($property) => [$property->name => $property->getValue()],
                (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC)
            )
        );
        $this->{$this->primaryKey} = $id;
        return $id;
    }

    /**
     * 设置模型属性
     */
    public function setAttr($attrs): static
    {
        foreach ($attrs as $key => $attr) {
            $this->{$key} = $attr;
        }
        return $this;
    }

    /**
     * 动态调用查询方法
     */
    public function __call($name, $args)
    {
        return $this->query()->where($this->primaryKey, $this->{$this->primaryKey})->$name(...$args);
    }

    /**
     * 通过静态方法动态调用查询类的方法
     */
    public static function __callStatic($name, $args)
    {
        return (new ModelQuery(self::$connector->connect()->table(static::getTable()), new static))->$name(...$args);
    }

    /**
     * 获取数据库连接
     */
    public function query(): Mysql
    {
        return self::$connector->connect()->table($this->table);
    }
}
