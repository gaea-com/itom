<?php
namespace DAO;

class RedisDataModel extends \MysqlBase
{
    public function insertOneKey(array $data)
    {
        $return = ['status', 'id'];
        $sql    = 'INSERT INTO `redis_key` (`key` ,`type`) VALUES (:key ,:type)';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute($data);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        } else {
            $return['id'] = $this->db->lastInsertId();
        }
        return $return;
    }

    public function insertOneData(array $data)
    {
        $return = ['status', 'id'];
        $sql    = 'INSERT INTO `redis_data` (`key_id` ,`field`,`value`) VALUES (:key_id ,:field,:value)';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute($data);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        } else {
            $return['id'] = $this->db->lastInsertId();
        }
        return $return;
    }

    public function findAllDataBykey($key_id)
    {
        $sql = "SELECT * FROM `redis_data` WHERE key_id=".$key_id;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute();
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAllKey()
    {
        $sql = "SELECT * FROM `redis_key`";
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute();
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }
}
