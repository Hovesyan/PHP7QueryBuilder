<?php

namespace Core\DB;

class QueryCustom extends QueryBuilder
{

    public function __construct(string $table)
    {
        parent::__construct();
        $this->table = $table;
    }

    public function getOne(string $field)
    {
        $this->fields[] = $field;
        $this->limit(1);
        $query = $this->select();
        if ($res = $this->rawQuery($query)) {
            $row = $res->fetch();
            if (is_array($row)) {
                return reset($row);
            }
        }
        return false;
    }

    public function find(string ...$fields)
    {
        if (count($fields) === 1) {
            return $this->getOne(reset($fields));
        }
        $this->fields = $fields;
        $this->limit(1);
        $query = $this->select();
        if ($res = $this->rawQuery($query)) {
            $ret = $res->fetch();
            $res->closeCursor($res);
        }
        return $ret;
    }

    public function getAll(string ...$fields)
    {
        $this->fields = $fields;
        $query = $this->select();
        if ($res = $this->rawQuery($query)) {
            while ($row = $res->fetch()) {
                $ret[] = $row;
            }
            $res->closeCursor($res);
        }
        return $ret;
    }

    public function getInd(string ...$fields)
    {
        $ind = $fields[0];
        $this->fields = $fields;
        $query = $this->select();
        $ret   = [];
        if ($res = $this->rawQuery($query)) {
            while ($row = $res->fetch()) {
                $ret[$row[$ind]] = $row;
            }
            $res->closeCursor($res);
        }
        return $ret;
    }
}
