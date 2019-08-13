<?php

namespace DAO;

class OrderInfoModel extends \MysqlBase
{
    public function findOne($paramsArr=null)
    {
        $sql = 'SELECT * FROM `order_info` WHERE id<>0  ';
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
        if (!empty($red)) {
            if (strpos($red['order'], 'base64_') !== false) {
                $red['order'] = base64_decode(str_replace('base64_', '', $red['order']));
            }
        }
        return $red;
    }


    public function findAll($paramsArr=null)
    {
        $sql = 'SELECT * FROM `order_info` ';
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
        if (!empty($red)) {
            foreach ($red as $redKey => $redValue) {
                if (strpos($redValue['order'], 'base64_') !== false) {
                    $red[$redKey]['order'] = base64_decode(str_replace('base64_', '', $redValue['order']));
                }
            }
        }
        return $red;
    }

    public function insertOne($paramsArr=null)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';
            return $return;
        }

        $paramsArr['order'] = 'base64_'.base64_encode($paramsArr['order']);
        
        $sql = 'INSERT INTO `order_info` (`'.implode('`,`', array_keys($paramsArr)).'`) VALUES (\''.implode('\',\'', $paramsArr).'\') ';
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

        if (!empty($paramsArr['order'])) {
            $paramsArr['order'] = 'base64_'.base64_encode($paramsArr['order']);
        }
            

        $id = $paramsArr['id'];
        unset($paramsArr['id']);

        $sql = 'UPDATE `order_info` set  ';
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
        $sql = 'DELETE FROM order_info WHERE id='.$id;
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
