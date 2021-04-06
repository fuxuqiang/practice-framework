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
    private $cols, $relation, $conds, $params = [];

    /**
     * @param \mysqli
     */
    public function __construct(\mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * 执行查询
     * @return \mysqli_result|bool
     */
    public function query(string $sql, array $vars = [])
    {
        if ($this->stmt = $this->mysqli->prepare($sql)) {
            if ($types = str_repeat('s', count($vars) + count($this->params))) {
                $vars = array_merge($vars, $this->params);
                $this->stmt->bind_param($types, ...array_values($vars));
            }
            if (!$this->stmt->execute()) {
                throw new \ErrorException($this->mysqli->error);
            }
        } else {
            throw new \ErrorException($this->mysqli->error);
        }
        return $this->stmt->get_result() ?: true;
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
    public function cols(...$cols)
    {
        $this->cols = $cols;
        return $this;
    }

    /**
     * 设置关联查询
     */
    public function with(array $relation)
    {
        $this->relation = $relation;
        return $this;
    }

    /**
     * 添加WHERE条件
     */
    public function where($col, $operator = null, $val = null)
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
        $this->whereRaw("`$col`$operator?", [$val]);
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
        $this->order = ' ORDER BY RAND()';
        $this->limit = 'LIMIT ' . $limit;
        return $this;
    }

    /**
     * 返回查询结果首行对象
     */
    public function get(string $class = null, array $params = [])
    {
        $this->limit = 'LIMIT 1';
        $stmt = $this->query($this->getSql());
        return $class ? $stmt->fetch_object($class, $params) : $stmt->fetch_object();
    }

    /**
     * 获取查询结果首行单个列的值
     */
    public function val(string $col)
    {
        $this->limit = 'LIMIT 1';
        return ($row = $this->cols($col)->get()) ? $row->$col : null;
    }

    /**
     * 获取查询结果集
     */
    public function all(...$cols)
    {
        $this->cols || $this->cols = $cols;
        $data = $this->select($this->getSql());
        if (
            $this->relation && ($table = key($this->relation))
            && $foreignKeysVal = array_column($data, $table . '_id')
        ) {
            $relationData = (new self($this->mysqli))->cols(...$this->relation[$table])
                ->table($table)->whereIn('id', $foreignKeysVal)->col(null, 'id');
            $data = array_map(function ($item) use ($table, $relationData) {
                $item[$table] = $relationData[$item[$table . '_id']];
                return $item;
            }, $data);
        }
        return $data;
    }

    /**
     * 获取查询结果的指定列
     */
    public function col($col, string $idx = null)
    {
        $col && $this->cols = $idx ? [$col, $idx] : [$col];
        return array_column($this->all(), $col, $idx);
    }

    /**
     * 数据是否存在
     */
    public function exists(string $col, $val)
    {
        $this->limit = 'LIMIT 1';
        return $this->where($col, $val)->query($this->getSql('`' . $col . '`'))->num_rows > 0;
    }

    /**
     * 分页查询
     */
    public function paginate(int $page, int $perPage)
    {
        $this->limit = 'LIMIT ' . ($page - 1) * $perPage . ',' . $perPage;
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
        return $this->query($this->getSql('COUNT(*)'))->fetch_row()[0];
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
            $cols = $this->cols;
            $markers = implode(',', array_map(function ($item) {
                return $this->markers($item);
            }, $data));
            $binds = array_merge(...$data);
        } else {
            $cols = array_keys($data);
            $markers = $this->markers($data);
            $binds = $data;
        }
        return $this->query(
            $action . " `$this->table` (" . $this->gather($cols, '`%s`') . ') VALUES ' . $markers,
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
            $data
        );
    }

    /**
     * 字段自增
     */
    public function inc($col, $num)
    {
        return $this->query("UPDATE `$this->table` SET `$col`=`$col`+$num" . $this->getWhere());
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
        self::$trans++ || $this->mysqli->begin_transaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        --self::$trans || $this->mysqli->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        --self::$trans || $this->mysqli->rollback();
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
        return implode(',', array_map(function ($val) use ($format) {
            return sprintf($format, $val);
        }, $arr));
    }

    /**
     * 获取查询sql
     */
    private function getSql(string $col = null)
    {
        return 'SELECT ' . ($col ?: $this->selectExpr ?: ($this->cols ? $this->gather($this->cols, '`%s`') : '*'))
            . ' FROM ' . ($this->from ?: "`$this->table`") . $this->getWhere()
            . $this->order . ' ' . $this->limit . $this->lock;
    }

    /**
     * 获取参数数组的绑定标记
     */
    private function markers(array $data)
    {
        return '(' . rtrim(str_repeat('?,', count($data)), ',') . ')';
    }

    /**
     * 关闭连接
     */
    public function __destruct()
    {
        $this->mysqli->close();
    }
}
