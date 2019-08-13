<?php

namespace DAO;

class VarQuoteModel extends \MysqlBase
{
    public function findOne($paramsArr = null)
    {
        $sql = 'SELECT * FROM `var_quote` WHERE type<>0  ';
        $arr = [];
        if (!empty($paramsArr)) {
            foreach ($paramsArr as $key => $value) {
                $sql .= ' AND ' . $key . '=:' . $key . ' ';
                $arr[':' . $key] = $value;
            }
        }
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAll($paramsArr = null)
    {
        $sql = 'SELECT * FROM `var_quote` ';
        $arr = [];
        if (!empty($paramsArr)) {
            $sql .= ' WHERE ';
            $strArr = [];
            foreach ($paramsArr as $key => $value) {
                $strArr[]        = $key . '=:' . $key . ' ';
                $arr[':' . $key] = $value;
            }
            $sql .= ' ' . implode(' and ', $strArr);
        }

        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function insertOne($paramsArr = null)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';
            return $return;
        }

        $sql       = 'INSERT INTO `var_quote` (' . implode(',', array_keys($paramsArr)) . ') VALUES (\'' . implode('\',\'', $paramsArr) . '\') ';
        $sth       = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();

        if (!$serStatus) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
            return $return;
        }
        $return['status'] = true;
        $return['id']     = $this->db->lastInsertId();
        return $return;
    }

    public function updateOne($paramsArr = null)
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

        $sql = 'UPDATE `var_quote` set  ';
        $arr = [];
        foreach ($paramsArr as $key => $value) {
            $arr[] = $key . '=\'' . $value . '\'';
        }
        $sql .= implode(' , ', $arr);
        $sql .= ' WHERE id=' . $id;
        $sth       = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();

        if (!$serStatus) {
            $error           = $sth->errorInfo();
            $return['error'] = $error;
            return $return;
        }
        $return['status'] = true;
        $return['id']     = $id;
        return $return;
    }

    public function deleteOne($id)
    {
        $return    = ['status' => false, 'error' => ''];
        $sql       = 'DELETE FROM var_quote WHERE id=' . $id;
        $sth       = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();
        if (!$serStatus) {
            $error           = $sth->errorInfo();
            $return['error'] = $error;
            return $return;
        }
        $return['status'] = true;
        $return['id']     = $id;
        return $return;
    }

    // 环境变量添加引用
    public static function envQuote($env_id)
    {
        $envModel = new \DAO\ServerEnvModel;
        $model = new self;
        $envData = $envModel->findOneByID($env_id);
        $varNameArr = $model->getVarInfo($envData);
        
        self::envDelQuote($env_id);
        foreach ($varNameArr as $var_name) {
            $paramsArr = [
                'var_name' => $var_name,
                'type' => 100,
                'quote_id' => $env_id,
            ];
            $model->insertOne($paramsArr);
        }
    }


    // 环境变量删除引用
    public static function envDelQuote($env_id)
    {
        $model = new self;
        $data = $model->findAll(['type' => 100, 'quote_id' => $env_id]);
        if (!empty($data)) {
            foreach ($data as $value) {
                $model->deleteOne($value['id']);
            }
        }
    }

    // 命令添加引用
    public static function orderQuote($order_id)
    {
        $orderModel = new \DAO\OrderInfoModel;
        $model = new self;
        $orderData = $orderModel->findOne(['id' => $order_id]);
        if (!empty($orderData)) {
            $varNameArr = $model->getVarInfo($orderData);
            self::orderDelQuote($order_id);
            foreach ($varNameArr as $var_name) {
                $paramsArr = [
                    'var_name' => $var_name,
                    'type' => 200,
                    'quote_id' => $order_id,
                ];
                $model->insertOne($paramsArr);
            }
        }
    }

    // 命令删除引用
    public static function orderDelQuote($order_id)
    {
        $model = new self;
        $data = $model->findAll(['type' => 200, 'quote_id' => $order_id]);
        if (!empty($data)) {
            foreach ($data as $value) {
                $model->deleteOne($value['id']);
            }
        }
    }


    // 获取变量信息
    public function getVarInfo($data)
    {
        $varNameArr = [];
        if (is_array($data)) {
            foreach ($data as $value) {
                $varNameArr = array_merge($varNameArr, $this->getVarInfo($value));
            }
        } else {
            $preg = '/\{\$.*?\}/';
            preg_match_all($preg, $data, $matches);

            if (!empty($matches)) {
                $matches = array_unique($matches[0]);
                foreach ($matches as $varName) {
                    $varNameArr[] = str_replace('}', '', str_replace('{$', '', $varName));
                }
            }
        }

        $varNameArr = array_unique($varNameArr);
        return $varNameArr;
    }
}
