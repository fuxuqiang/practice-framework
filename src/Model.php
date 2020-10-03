<?php

namespace Fuxuqiang\Framework;

class Model extends Arr
{
    /**
     * @var Connector
     */
    private static $connector;

    /**
     * @var string
     */
    private $table;

    /**
     * 设置获取数据库操作类的方法
     */
    public static function setConnector(Connector $connector)
    {
        self::$connector = $connector;
    }

    /**
     * @param string $table
     */
    public function __construct(string $table = null)
    {
        $this->table = $table ?: self::getTable();
    }

    /**
     * 获取当前表名
     */
    private static function getTable()
    {
        return strtolower(basename(str_replace('\\', '/', static::class)));
    }

    /**
     * 设置模型字段
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 调用数据库操作类的方法
     */
    public function __call($name, $args)
    {
        return self::$connector->connect()->table($this->table)->where('id', $this->id)->$name(...$args);
    }

    /**
     * 根据id查找模型
     */
    public static function find($id)
    {
        if (is_array($id)) {
            return array_map(function ($id) {
                return self::getModel($id);
            }, $id);
        } else {
            return self::getModel($id);
        }
    }

    /**
     * 获取设置了主键值的模型实例
     */
    private static function getModel($id)
    {
        $model = new static;
        $model->id = $id;
        return $model;
    }
}
