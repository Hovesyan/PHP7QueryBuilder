<?php

namespace Core\DB;

class QueryObject extends QueryBuilder
{

    private $entity;

    public function __construct($entity)
    {
        parent::__construct();
        if (is_object($entity)) {
            $this->entity = $entity;
            $this->entity->setQuery($this);
        } else {
            $className = '\\Entity\\' . $entity;
            $this->entity = new $className;
            $this->entity->setQuery($this);
        }
        $this->table  = $this->entity->table();
        $this->fields = $this->entity->fields();
    }

    public function find()
    {
        $this->limit(1);

        $query = $this->select();
        if ($res = $this->rawQuery($query)) {
            $data = $res->fetchAll();
            $data = reset($data);
            if (empty($data)) {
                return null;
            }
            $this->entity
                 ->exchangeArray($data)
                 ->cache();
            $res->closeCursor();
            return $this->entity;
        }
        return false;
    }

    public function getAll()
    {
        $query = $this->select();
        $ret   = [];
        if ($res = $this->rawQuery($query)) {
            while ($data = $res->fetch()) {
                $inst = clone $this->entity;
                $inst->exchangeArray($data)->cache();
                $ret[] = $inst;
            }
            $res->closeCursor();
            return $ret;
        }
        return false;
    }

    public function getInd(string $ind)
    {
        $query = $this->select();
        $ret   = [];
        if ($res = $this->rawQuery($query)) {
            while ($data = $res->fetch()) {
                $inst = clone $this->entity;
                $inst->exchangeArray($data)->cache();
                $ret[$data[$ind]] = $inst;
            }
            $res->closeCursor();
            return $ret;
        }
        return false;
    }
}
