<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\{Connector, Mysql, Str};

/**
 * @method static ModelQuery fields(array $fields)
 * @method static ModelQuery where(array|string $field, string $operator = null, string|int|float $value = null)
 * @method static static first()
 * @method static static|array find($id, array $fields = null)
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
     * @param ?string $table
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
        return Str::snake(basename(str_replace('\\', '/', static::class)));
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
        $query = $this->query();
        if (empty($this->{$this->primaryKey})) {
            $query->insert($data);
        } else {
            $query->update($data);
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
        return $this->query()->where(Str::snake($this->primaryKey), $this->{$this->primaryKey})->$name(...$args);
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
