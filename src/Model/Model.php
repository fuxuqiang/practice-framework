<?php

namespace Fuxuqiang\Framework\Model;

use Fuxuqiang\Framework\{Arr, Connector};

class Model extends Arr
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
        return strtolower(basename(str_replace('\\', '/', static::class)));
    }

    /**
     * 设置模型属性
     */
    public function setAttr($attrs)
    {
        parent::__construct($attrs);
        return $this;
    }

    /**
     * 设置模型字段
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 动态调用查询方法
     */
    public function __call($name, $args)
    {
        return self::$connector->connect()->table($this->table)->where('id', $this->id)->$name(...$args);
    }

    /**
     * 通过静态方法动态调用查询类的方法
     */
    public static function __callStatic($name, $args)
    {
        return (new ModelQuery(self::$connector->connect()->table(static::getTable()), static::class))->$name(...$args);
    }
}
