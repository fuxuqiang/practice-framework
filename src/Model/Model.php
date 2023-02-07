<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\Connector;

class Model
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var Connector
     */
    private static $connector;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @param string $table
     */
    public function __construct(string $table = null)
    {
        $this->table = $table ?: static::getTable();
    }

    /**
     * 设置获取数据库操作类的方法
     */
    public static function setConnector(Connector $connector)
    {
        self::$connector = $connector;
    }

    /**
     * 获取当前表名
     */
    public static function getTable()
    {
        return strtolower(
            preg_replace('/([a-z])([A-Z])/', '$1_$2', basename(str_replace('\\', '/', static::class)))
        );
    }

    /**
     * 获取表主键
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * 设置模型属性
     */
    public function setAttr($attrs)
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
        return self::$connector->connect()
            ->table($this->table)
            ->where($this->primaryKey, $this->{$this->primaryKey})
            ->$name(...$args);
    }

    /**
     * 通过静态方法动态调用查询类的方法
     */
    public static function __callStatic($name, $args)
    {
        return (new ModelQuery(self::$connector->connect()->table(static::getTable()), new static))->$name(...$args);
    }
}
