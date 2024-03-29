<?php

namespace Fuxuqiang\Framework;

use mysqli;
use mysqli_stmt;
use mysqli_result;

class Mysql
{
    private mysqli_stmt $stmt;

    private static int $trans = 0;

    private string $table, $selectExpr, $from, $lock = '', $order = '';

    private int $offset = 0, $limit = 0;

    private ?array $fields;

    private array $conditions = [], $params = [];

    public function __construct(private readonly mysqli $mysqli) {}

    /**
     * 执行查询并获取结果集
     */
    public function query(string $sql, array $vars = []): mysqli_result
    {
        $this->executeQuery($sql, $vars);
        return $this->stmt->get_result();
    }

    /**
     * 执行select查询
     */
    public function fetch(string $sql, array $vars = []): array
    {
        return $this->query($sql, $vars)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * 设置select原生字段
     */
    public function selectRaw(string $expr): static
    {
        $this->selectExpr = $expr;
        return $this;
    }

    /**
     * 设置表名
     */
    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 设置from后的原生表达式
     */
    public function from(string $from): static
    {
        $this->from = $from;
        return $this;
    }

    /**
     * 设置查询列
     */
    public function fields(?array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * 添加WHERE条件
     */
    public function where(array|string $field, string $operator = null, string|int|float $value = null): static
    {
        if (is_array($field)) {
            foreach ($field as $key => $item) {
                if (is_array($item)) {
                    $this->setWhere($item[0], $item[1], $item[2]);
                } else {
                    $this->setWhere($key, '=', $item);
                }
            }
        } else {
            if (is_null($value)) {
                $value = $operator;
                $operator = '=';
            }
            $this->setWhere($field, $operator, $value);
        }
        return $this;
    }

    /**
     * 设置WHERE条件
     */
    private function setWhere(string $field, string $operator, string|int|float $value): static
    {
        return $this->whereRaw("`$field`$operator ?", [$value]);
    }

    /**
     * 设置原生WHERE条件
     */
    public function whereRaw(string $condition, array $values = []): static
    {
        $this->conditions[] = $condition;
        array_push($this->params, ...$values);
        return $this;
    }

    /**
     * 添加 WHERE {COLUMN} IS NULL 条件
     */
    public function whereNull(string $field): static
    {
        return $this->whereRaw("`$field` IS NULL");
    }

    /**
     * 添加 WHERE {COLUMN} IS NOT NULL 条件
     */
    public function whereNotNull(string $field): static
    {
        return $this->whereRaw("`$field` IS NOT NULL");
    }

    /**
     * 添加 WHERE {COLUMN} IN 条件
     */
    public function whereIn(string $field, array $values): static
    {
        return $this->whereRaw("`$field` IN " . $this->markers($values), $values);
    }

    /**
     * 添加 WHERE {COLUMN} BETWEEN 条件
     */
    public function whereBetween(string $field, array $values): static
    {
        return $this->whereRaw("`$field` BETWEEN ? AND ?", $values);
    }

    /**
     * 添加 WHERE LIKE 条件
     */
    public function whereLike(string|array $field, string $value): static
    {
        if (is_array($field)) {
            $conditions = $values = [];
            foreach ($field as $item) {
                $conditions[] = "`$item` LIKE ?";
                $values[] = $value;
            }
            return $this->whereRaw(implode(' OR ', $conditions), $values);
        }
        return $this->setWhere($field, 'LIKE', $value);
    }

    /**
     * 添加 FOR UPDATE 锁
     */
    public function lock(): static
    {
        $this->lock = ' FOR UPDATE';
        return $this;
    }

    /**
     * ORDER BY RAND()
     */
    public function rand(int $limit): static
    {
        $this->order = 'RAND()';
        return $this->limit($limit);
    }

    /**
     * ORDER BY expr
     */
    public function orderBy($field): static
    {
        $this->order = "`$field`";
        return $this;
    }

    /**
     * ORDER BY expr DESC
     */
    public function orderByDesc(string $field): static
    {
        $this->order = "`$field` DESC";
        return $this;
    }

    /**
     * 返回查询结果首行对象
     */
    public function first(array $fields = null): bool|array|null
    {
        $this->limit = 1;
        $result = $this->fields($fields)->query($this->getSql());
        return $result->fetch_assoc();
    }

    /**
     * 获取查询结果首行单个列的值
     */
    public function value(string $field)
    {
        $this->limit = 1;
        return ($row = $this->first([$field])) ? $row[$field] : null;
    }

    /**
     * 获取查询结果集
     */
    public function all(array $fields = null): array
    {
        return $this->fields($fields)->fetch($this->getSql());
    }

    /**
     * 获取查询结果的指定列
     */
    public function column(string $col, string $idx = null): array
    {
        $this->fields = $idx ? [$col, $idx] : [$col];
        return array_column($this->all(), $col, $idx);
    }

    /**
     * 数据是否存在
     */
    public function exists(string $field, string $operator = null, string|int|float $value = null): bool
    {
        $this->limit = 1;
        return $this->where($field, $operator, $value)->query($this->getSql("`$field`"))->num_rows > 0;
    }

    /**
     * 分页查询
     */
    public function paginate(int $page, int $perPage): array
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return [
            'data' => $this->all(),
            'total' => $this->count()
        ];
    }

    /**
     * COUNT查询
     */
    public function count(): int
    {
        return $this->aggregate('COUNT(*)');
    }

    /**
     * SUM查询
     */
    public function sum(string $field): int|float
    {
        return $this->aggregate("SUM(`$field`)");
    }

    /**
     * 聚合查询
     */
    private function aggregate($expr): int|float
    {
        return $this->query($this->getSql($expr))->fetch_row()[0];
    }

    /**
     * 执行INSERT语句
     */
    public function insert(array $data): int|string
    {
        $this->into('INSERT', $data);
        return $this->stmt->insert_id;
    }

    /**
     * 执行REPLACE语句
     */
    public function replace(array $data): bool
    {
        return $this->into('REPLACE', $data);
    }

    /**
     * 执行INSERT或REPLACE语句
     */
    private function into(string $action, array $data): bool
    {
        $item = reset($data);
        if (is_array($item)) {
            $fields = array_keys($item);
            $markers = implode(',', array_map(fn($item) => $this->markers($item), $data));
            $binds = array_merge(...array_map(fn($item) => array_values($item), $data));
        } else {
            $fields = array_keys($data);
            $markers = $this->markers($data);
            $binds = array_values($data);
        }
        return $this->executeQuery(
            sprintf('%s `%s` (%s) VALUES %s', $action, $this->table, $this->gather($fields, '`%s`'), $markers),
            $binds
        );
    }

    /**
     * 执行prepare、绑定参数和execute
     */
    private function executeQuery(string $sql, array $vars = []): bool
    {
        $this->stmt = $this->mysqli->prepare($sql);
        return $this->stmt->execute(array_merge($vars, $this->params));
    }

    /**
     * 执行UPDATE语句
     */
    public function update(array $data): bool
    {
        return $this->executeQuery(
            "UPDATE `$this->table` SET " . $this->gather(array_keys($data), '`%s`=?') . $this->getWhere(),
            array_values($data)
        );
    }

    /**
     * 设置row_count
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 设置offset
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * 字段自增
     */
    public function increment(string $col, $num): bool
    {
        return $this->incOrDec($col, '+', $num);
    }

    /**
     * 字段自减
     */
    public function decrement(string $col, $num): bool
    {
        return $this->incOrDec($col, '-', $num);
    }

    /**
     * 字段自增或自减
     */
    private function incOrDec(string $col, string $operator, int|float $num): bool
    {
        return $this->executeQuery("UPDATE `$this->table` SET `$col`=`$col`$operator?" . $this->getWhere(), [$num]);
    }

    /**
     * 执行DELETE语句
     */
    public function delete(): bool
    {
        return $this->executeQuery("DELETE FROM `$this->table`" . $this->getWhere());
    }

    /**
     * 执行TRUNCATE语句
     */
    public function truncate(): bool
    {
        return $this->mysqli->real_query("TRUNCATE `$this->table`");
    }

    /**
     * 开始事务
     */
    public function begin(): bool
    {
        return self::$trans++ || $this->mysqli->begin_transaction();
    }

    /**
     * 提交事务
     */
    public function commit(): bool
    {
        return --self::$trans || $this->mysqli->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback(): bool
    {
        return --self::$trans || $this->mysqli->rollback();
    }

    /**
     * 获取WHERE子句
     */
    private function getWhere(): string
    {
        return $this->conditions ? ' WHERE ' . implode(' AND ', $this->conditions) : '';
    }

    /**
     * 格式化数组元素后用,连接成字符串
     */
    private function gather(array $arr, string $format): string
    {
        return implode(',', array_map(fn($val) => sprintf($format, $val), $arr));
    }

    /**
     * 获取查询sql
     */
    private function getSql(string $col = null): string
    {
        return sprintf(
            'SELECT %s FROM %s',
            ($col ?: $this->selectExpr ?? ($this->fields ? $this->gather($this->fields, '`%s`') : '*')),
            ($this->from ?? "`$this->table`")
            . $this->getWhere()
            . ($this->order ? ' ORDER BY ' . $this->order : '')
            . ($this->limit ? ' LIMIT ' . ($this->offset ? $this->offset . ',' . $this->limit : $this->limit) : '')
            . $this->lock
        );
    }

    /**
     * 获取参数数组的绑定标记
     */
    private function markers(array $data): string
    {
        return sprintf('(%s)', rtrim(str_repeat('?,', count($data)), ','));
    }
}
