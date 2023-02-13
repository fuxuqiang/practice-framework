<?php

namespace Fuxuqiang\Framework;

class Mysql
{
    /**
     * @var \mysqli
     */
    private $mysqli;

    /**
     * @var \mysqli_stmt
     */
    private $stmt;

    /**
     * @var int
     */
    private static $trans = 0;

    /**
     * @var string
     */
    private $table, $limit, $lock, $order, $selectExpr, $from;

    /**
     * @var array
     */
    private $fields, $conds, $params = [];

    /**
     * @param \mysqli
     */
    public function __construct(\mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * 执行查询
     */
    public function query(string $sql, array $vars = [])
    {
        $this->stmt = $this->mysqli->prepare($sql);
        $this->stmt->execute(array_merge($vars, $this->params));
        return $this->stmt->get_result();
    }

    /**
     * 执行select查询
     */
    public function select(string $sql, array $vars = [])
    {
        return $this->query($sql, $vars)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * 设置select原生字段
     */
    public function selectRaw(string $expr)
    {
        $this->selectExpr = $expr;
        return $this;
    }

    /**
     * 设置表名
     */
    public function table(string $table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 设置from后的原生表达式
     */
    public function from(string $from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * 设置查询列
     */
    public function fields(array $fields = null)
    {
        if ($fields) {
            $this->fields = $fields;   
        }
        return $this;
    }

    /**
     * 添加WHERE条件
     */
    public function where(array|string $col, string $operator = null, $val = null)
    {
        if (is_array($col)) {
            foreach ($col as $key => $item) {
                if (is_array($item)) {
                    $this->setWhere($item[0], $item[1], $item[2]);
                } else {
                    $this->setWhere($key, '=', $item);
                }
            }
        } else {
            if (is_null($val)) {
                $val = $operator;
                $operator = '=';
            }
            $this->setWhere($col, $operator, $val);
        }
        return $this;
    }

    /**
     * 设置WHERE条件
     */
    private function setWhere(string $col, string $operator, $val)
    {
        $this->whereRaw("`$col`$operator ?", [$val]);
    }

    /**
     * 设置原生WHERE条件
     */
    public function whereRaw(string $cond, array $vals = [])
    {
        $this->conds[] = $cond;
        array_push($this->params, ...$vals);
        return $this;
    }

    /**
     * 添加 WHERE {COLUMN} IS NULL 条件
     */
    public function whereNull(string $col)
    {
        return $this->whereRaw("`$col` IS NULL");
    }

    /**
     * 添加 WHERE {COLUMN} IS NOT NULL 条件
     */
    public function whereNotNull(string $col)
    {
        return $this->whereRaw("`$col` IS NOT NULL");
    }

    /**
     * 添加 WHERE {COLUMN} IN 条件
     */
    public function whereIn(string $col, array $vals)
    {
        return $this->whereRaw("`$col` IN " . $this->markers($vals), $vals);
    }

    /**
     * 添加 WHERE {COLUMN} BETWEEN 条件
     */
    public function whereBetween(string $col, array $vals)
    {
        return $this->whereRaw("`$col` BETWEEN ? AND ?", $vals);
    }

    /**
     * 添加 WHERE LIKE 条件
     */
    public function whereLike(string|array $column, string $val)
    {
        if (is_array($column)) {
            foreach ($column as $item) {
                $conds[] = "`$item` LIKE ?";
                $vals[] = $val;
            }
            return $this->whereRaw(implode(' OR ', $conds), $vals);
        }
        $this->setWhere($column, 'LIKE', $val);
        return $this;
    }

    /**
     * 添加 FOR UPDATE 锁
     */
    public function lock()
    {
        $this->lock = ' FOR UPDATE';
        return $this;
    }

    /**
     * ORDER BY RAND()
     */
    public function rand(int $limit)
    {
        $this->order = 'RAND()';
        $this->limit = $limit;
        return $this;
    }

    /**
     * ORDER BY expr
     */
    public function orderBy($field)
    {
        $this->order = "`$field`";
        return $this;
    }

    /**
     * ORDER BY expr DESC
     */
    public function orderByDesc($field)
    {
        $this->order = "`$field` DESC";
        return $this;
    }

    /**
     * 返回查询结果首行对象
     */
    public function first(array $fields = null, string $class = null)
    {
        $this->limit = 1;
        $result = $this->fields($fields)->query($this->getSql());
        return $class ? $result->fetch_object($class) : $result->fetch_object();
    }

    /**
     * 获取查询结果首行单个列的值
     */
    public function value(string $field)
    {
        $this->limit = 1;
        return ($row = $this->first([$field])) ? $row->$field : null;
    }

    /**
     * 获取查询结果集
     */
    public function all(array $fields = null)
    {
        return $this->fields($fields)->select($this->getSql());
    }

    /**
     * 获取查询结果的指定列
     */
    public function column(string $col, string $idx = null)
    {
        $this->fields = $idx ? [$col, $idx] : [$col];
        return array_column($this->all(), $col, $idx);
    }

    /**
     * 数据是否存在
     */
    public function exists(string $col, $val)
    {
        $this->limit = 1;
        return $this->where($col, $val)->query($this->getSql("`$col`"))->num_rows > 0;
    }

    /**
     * 分页查询
     */
    public function paginate(int $page, int $perPage)
    {
        $this->limit = ($page - 1) * $perPage . ',' . $perPage;
        return [
            'data' => $this->all(),
            'total' => $this->count()
        ];
    }

    /**
     * COUNT查询
     */
    public function count()
    {
        return $this->aggregate('COUNT(*)');
    }

    /**
     * SUM查询
     */
    public function sum($field)
    {
        return $this->aggregate("SUM(`$field`)");
    }

    /**
     * 聚合查询
     */
    private function aggregate($expr)
    {
        return $this->query($this->getSql($expr))->fetch_row()[0];
    }

    /**
     * 执行INSERT语句
     */
    public function insert(array $data)
    {
        $this->into('INSERT', $data);
        return $this->stmt->insert_id;
    }

    /**
     * 执行REPLACE语句
     */
    public function replace(array $data)
    {
        return $this->into('REPLACE', $data);
    }

    /**
     * 执行INSERT或REPLACE语句
     */
    private function into(string $action, array $data)
    {
        if (is_array(reset($data))) {
            $fields = $this->fields;
            $markers = implode(',', array_map(fn($item) => $this->markers($item), $data));
            $binds = array_merge(...$data);
        } else {
            $fields = array_keys($data);
            $markers = $this->markers($data);
            $binds = array_values($data);
        }
        return $this->query(
            sprintf('%s `%s` (%s) VALUES %s', $action, $this->table, $this->gather($fields, '`%s`'), $markers),
            $binds
        );
    }

    /**
     * 执行UPDATE语句
     */
    public function update(array $data)
    {
        return $this->query(
            "UPDATE `$this->table` SET " . $this->gather(array_keys($data), '`%s`=?') . $this->getWhere(),
            array_values($data)
        );
    }

    /**
     * 添加LIMIT子句
     */
    public function limit(int $offset, int $rowCount = null)
    {
        $this->limit = $rowCount ?  $offset . ',' . $rowCount : $offset;
        return $this;
    }

    /**
     * 字段自增
     */
    public function increment(string $col, $num)
    {
        $this->incOrDec($col, '+', $num);
    }

    /**
     * 字段自减
     */
    public function decrement(string $col, $num)
    {
        return $this->incOrDec($col, '-', $num);
    }

    /**
     * 字段自增或自减
     */
    private function incOrDec(string $col, string $operator, $num)
    {
        if (!is_numeric($num)) {
            throw new \ErrorException('第二个参数必须为数字');
        }
        return $this->query("UPDATE `$this->table` SET `$col`=`$col`$operator$num" . $this->getWhere());
    }

    /**
     * 执行DELETE语句
     */
    public function del(int $id = null)
    {
        return $id ? $this->query("DELETE FROM `$this->table` WHERE `id`=?", [$id])
            : $this->query("DELETE FROM `$this->table`" . $this->getWhere());
    }

    /**
     * 开始事务
     */
    public function begin()
    {
        return self::$trans++ || $this->mysqli->begin_transaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        return --self::$trans || $this->mysqli->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        return --self::$trans || $this->mysqli->rollback();
    }

    /**
     * 获取WHERE子句
     */
    private function getWhere()
    {
        return $this->conds ? ' WHERE ' . implode(' AND ', $this->conds) : '';
    }

    /**
     * 格式化数组元素后用,连接成字符串
     */
    private function gather(array $arr, string $format)
    {
        return implode(',', array_map(fn($val) => sprintf($format, $val), $arr));
    }

    /**
     * 获取查询sql
     */
    private function getSql(string $col = null)
    {   
        return sprintf(
            'SELECT %s FROM %s',
            ($col ?: $this->selectExpr ?: ($this->fields ? $this->gather($this->fields, '`%s`') : '*')),
            ($this->from ?: "`$this->table`")
                . $this->getWhere()
                . ($this->order ? ' ORDER BY ' . $this->order : '')
                . ($this->limit ? ' LIMIT ' . $this->limit : '')
                . $this->lock
        );
    }

    /**
     * 获取参数数组的绑定标记
     */
    private function markers(array $data)
    {
        return sprintf('(%s)', rtrim(str_repeat('?,', count($data)), ','));
    }
}
