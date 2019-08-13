<?php

namespace DAO;

class CustomGroupModel extends \MysqlBase
{
    public function findOne($paramsArr=null)
    {
        $sql = 'SELECT * FROM `custom_group` WHERE id<>0  ';
        $arr = [];
        if (!empty($paramsArr)) {
            foreach ($paramsArr as $key => $value) {
                $sql .= ' AND '.$key.'=:'.$key.' ';
                $arr[':'.$key] = $value;
            }
        }
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }


    public function findAll($paramsArr=null)
    {
        $sql = 'SELECT * FROM `custom_group` ';
        $arr = [];
        if (!empty($paramsArr)) {
            $sql .= ' WHERE ';
            $strArr = [];
            foreach ($paramsArr as $key => $value) {
                $strArr[] = $key.'=:'.$key.' ';
                $arr[':'.$key] = $value;
            }
            $sql .= ' '.implode(' and ', $strArr);
        }

        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function insertOne($paramsArr=null)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';
            return $return;
        }
        
        $sql = 'INSERT INTO `custom_group` (`'.implode('`,`', array_keys($paramsArr)).'`) VALUES (\''.implode('\',\'', $paramsArr).'\') ';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();

        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error[2];
            return $return;
        }
        $return['status'] = true;
        $return['id'] = $this->db->lastInsertId();
        return $return;
    }

    public function updateOne($paramsArr=null)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';
            return $return;
        }

        if (empty($paramsArr['id'])) {
            $return['error'] = 'ID为空';
            return $return;
        }

        $id = $paramsArr['id'];
        unset($paramsArr['id']);

        $sql = 'UPDATE `custom_group` set  ';
        $arr = [];
        foreach ($paramsArr as $key => $value) {
            $arr[] = '`'.$key.'`=\''.$value.'\'';
        }
        $sql .= implode(' , ', $arr);
        $sql .= ' WHERE id='.$id;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();

        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error['2'];
            return $return;
        }
        $return['status'] = true;
        $return['id'] = $id;
        return $return;
    }

    public function deleteOne($id)
    {
        $return = ['status' => false, 'error' => ''];
        $sql = 'DELETE FROM custom_group WHERE id='.$id;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();
        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error['2'];
            return $return;
        }
        $return['status'] = true;
        $return['id'] = $id;
        return $return;
    }
}
