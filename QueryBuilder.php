<?php

namespace Core\DB;

class QueryBuilder
{

    protected static $conn;
    protected $query;

    protected $table;
    protected $fields;
    protected $wheres;
    protected $limit;
    protected $offset;
    protected $orders;

    public static function connect(array $confs)
    {
        $dbh = "mysql:host={$confs['host']};dbname={$confs['db']};charset=utf8";
        try {
            self::$conn = new \PDO($dbh, $confs['user'], $confs['pass']);
            self::$conn->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );
        } catch (\PDOException $e) {
             echo "Connection failed: " . $e->getMessage();
        }
        unset($confs);
    }

    public function __construct()
    {
        if (!isset(self::$conn)) {
            self::connect(getConfs('db'));
        }
    }

    protected function select(): string
    {
        $query = 'SELECT '
               . $this->getFields()
               . $this->getFrom()
               . $this->getWheres()
               . $this->getOrders()
               . $this->getLimits();
        return $query;
    }

    public function insert(array $data)
    {
        $query = 'INSERT INTO '
               . $this->getTable()
               . $this->getSet($data)
               . $this->getLimits();
        return $this->rawQuery($query);
    }

    public function insertId(): int
    {
        return self::$conn->lastInsertId();
    }

    public function update(array $data)
    {
        $query = 'UPDATE '
               . $this->getTable()
               . $this->getSet($data)
               . $this->getWheres()
               . $this->getOrders()
               . $this->getLimits();
        return $this->rawQuery($query);
    }

    public function delete()
    {
        $query = 'DELETE '
               . $this->getFrom()
               . $this->getWheres()
               . $this->getLimits();
        return $this->rawQuery($query);
    }

    protected function getTable(): string
    {
        return '`' . $this->table . '`';
    }

    public function where(string $key, string $opr, string $val = null)
    {
        if ($val === null) {
            $val = $opr;
            $opr = '=';
        }
        return $this->whereHandler($key, $opr, $val);
    }

    public function orWhere(string $key, string $opr, string $val = null)
    {
        if ($val === null) {
            $val = $opr;
            $opr = '=';
        }
        return $this->whereHandler($key, $opr, $val, 'OR');
    }

    protected function whereHandler(
        string $key,
        string $opr,
        string $val = null,
        string $joiner = 'AND'
    ) {
        if (empty($this->wheres)) {
            $joiner = '';
        }
        $this->wheres[] = compact('key', 'opr', 'val', 'joiner');
        return $this;
    }

    public function order(string $key, string $dir = 'ASC')
    {
        $this->orders[] = compact('key', 'dir');
        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset)
    {
        $this->offset = $offset;
        return $this;
    }

    protected function rawQuery(string $query)
    {
        try {
            $res = self::$conn->query($query);
            $res->setFetchMode(\PDO::FETCH_ASSOC);

            if (!$res) {
                echo "<pre>";
                print_r(self::$conn->errorInfo());
            }
            return $res;
        } catch (\PDOException $e) {
            echo "<pre>";
            echo '<b>Error:</b> ' . $e->getMessage() . '<br>';
            echo '<b>Query:</b> ' . $query . '<br>';
            echo '<b>Trace:</b> <br>';
            print_r($e->getTrace());
            exit;
        }
    }

    protected function getFields(): string
    {
        $query = $comma = '';
        foreach ($this->fields as $field) {
            $query.= $comma . '`' . $field . '`';
            $comma = ', ';
        }
        $query .= ' ';
        return $query;
    }

    protected function getFrom(): string
    {
        $query = 'FROM ' . $this->getTable();
        return $query;
    }

    protected function getWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        $query  = ' WHERE ';
        foreach ($this->wheres as $where) {
            if (!empty($where['joiner'])) {
                $query .= ' ' . $where['joiner'] . ' ';
            }
            $query .= '`' . $where['key'] . '`';
            $query .= ' ' . $where['opr'] . ' ';
            $query .= "'" . $where['val'] . "'";
        }
        return $query;
    }

    protected function getOrders(): string
    {
        if (empty($this->orders)) {
            return '';
        }
        $query = ' ORDER BY ';
        $comma = '';
        foreach ($this->orders as $order) {
            $query.= $comma . '`' . $order['key'] . '` ' . $order['dir'];
            $comma = ',';
        }
        return $query;
    }

    protected function getLimits(): string
    {
        if (empty($this->limit)) {
            return '';
        }
        $query = ' LIMIT ';
        if (isset($this->offset)) {
            $query .= $this->offset . ', ';
        }
        $query.= $this->limit;
        return $query;
    }

    public function getSet(array $data): string
    {
        $query = ' SET ';
        $comma = '';
        foreach ($data as $key => $value) {
            $query .= "{$comma} `{$key}` = '{$value}'";
            $comma  = ",";
        }
        return $query;
    }
}
