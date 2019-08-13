<?php

namespace DAO;

class TaskOrderModel extends \MysqlBase
{
    public function findOne($paramsArr=null)
    {
        $sql = 'SELECT * FROM `task_order` WHERE id<>0  ';
        $arr = [];
        if (!empty($paramsArr)) {
            foreach ($paramsArr as $key => $value) {
                $sql .= ' AND '.$key.'=:'.$key.' ';
                $arr[':'.$key] = $value;
            }
        }
        $sql .= ' ORDER BY order_sort ';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!empty($red)) {
            $red['order_object'] = json_decode($red['order_object'], true);
        }

        return $red;
    }


    public function findAll($paramsArr=null)
    {
        $sql = 'SELECT * FROM `task_order` ';
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
        $sql .= ' ORDER BY order_sort ';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $red = empty($red) ? [] : $red;

        if (!empty($red)) {
            foreach ($red as $key => $value) {
                $red[$key]['order_object'] = json_decode($value['order_object'], true);
            }
        }

        return $red;
    }

    public function insertOne($paramsArr=null)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr) || empty($paramsArr['order_object'])) {
            $return['error'] = '参数不能为空';
            return $return;
        }
        $paramsArr['order_object'] = json_encode($paramsArr['order_object']);
        
        $sql = 'INSERT INTO `task_order` (`'.implode('`,`', array_keys($paramsArr)).'`) VALUES (\''.implode('\',\'', $paramsArr).'\') ';
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
        if (empty($paramsArr) || empty($paramsArr['order_object'])) {
            $return['error'] = '参数为空';
            return $return;
        }

        if (empty($paramsArr['id'])) {
            $return['error'] = 'ID为空';
            return $return;
        }

        $id = $paramsArr['id'];
        unset($paramsArr['id']);
        $paramsArr['order_object'] = json_encode($paramsArr['order_object']);

        $sql = 'UPDATE `task_order` set  ';
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
        $sql = 'DELETE FROM task_order WHERE id='.$id;
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

    public function deleteAll($paramsArr=null)
    {
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';
            return $return;
        }
        $return = ['status' => false, 'error' => ''];
        $sql = 'DELETE FROM `task_order` ';
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
        $serStatus = $sth->execute($arr);
        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error['2'];
            return $return;
        }
        $return['status'] = true;
        return $return;
    }
}
