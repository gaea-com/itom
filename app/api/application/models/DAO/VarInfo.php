<?php

namespace DAO;

class VarInfoModel extends \MysqlBase
{
    public function findOne($paramsArr = null)
    {
        $sql = 'SELECT * FROM `var_info` WHERE type=:type  ';
        $arr = [':type' => 200];
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

    public function findOneVar($paramsArr = null)
    {
        $sql = 'SELECT * FROM `var_info` WHERE type<>0  ';
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
        $sql = 'SELECT * FROM `var_info` WHERE type=:type  ';
        $arr = [':type' => 200];
        if (!empty($paramsArr)) {
            foreach ($paramsArr as $key => $value) {
                $sql .= ' AND ' . $key . '=:' . $key . ' ';
                $arr[':' . $key] = $value;
            }
        }
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAllArrInfo($paramsArr = null)
    {
        $sql = 'SELECT * FROM `var_info` ';
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

        $sql       = 'INSERT INTO `var_info` (' . implode(',', array_keys($paramsArr)) . ') VALUES (\'' . implode('\',\'', $paramsArr) . '\') ';
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

        $sql = 'UPDATE `var_info` set  ';
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

        //更新缓存
        $RedisModel = new \Tools\RedisModel();
        $redisKey       = 'var_info_cache_data';
        $redisTimeKey   = 'var_info_cache_time';
        $RedisModel->redis->del($redisKey);
        $RedisModel->redis->set($redisTimeKey, time());

        $return['status'] = true;
        $return['id']     = $id;
        return $return;
    }

    public function deleteOne($id)
    {
        $return    = ['status' => false, 'error' => ''];
        $sql       = 'DELETE FROM var_info WHERE id=' . $id;
        $sth       = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();
        if (!$serStatus) {
            $error           = $sth->errorInfo();
            $return['error'] = $error;
            return $return;
        }

        //更新缓存
        $RedisModel = new \Tools\RedisModel();
        $redisKey       = 'var_info_cache_data';
        $redisTimeKey   = 'var_info_cache_time';
        $RedisModel->redis->del($redisKey);
        $RedisModel->redis->set($redisTimeKey, time());

        $return['status'] = true;
        $return['id']     = $id;
        return $return;
    }

    public function getValueByVarName($var_name, $project_id)
    {
        $data    = $this->findOneVar(['var_name' => $var_name, 'project_id' => $project_id]);
        $varName = empty($data) ? null : $data['var_value'];
        return $varName;
    }

    // 根据引用名称获得变量详细信息,缓存至redis中,每半天清除缓存
    public static function findOneByVarName($var_name, $project_id)
    {
        $redisKey       = 'var_info_cache_data';
        $redisTimeKey   = 'var_info_cache_time';
        $redisCacheTime = 3600 * 12;
        $time           = time();

        $RedisModel = new \Tools\RedisModel();
        $redisTime  = $RedisModel->redis->get($redisTimeKey);
        if (empty($redisTime) || ($time - $redisTime) >= $redisCacheTime) {
            $RedisModel->redis->del($redisKey);
            $RedisModel->redis->set($redisTimeKey, $time);
        }

        $data = json_decode($RedisModel->redis->hGet($redisKey, $project_id.'_'.$var_name), true);
        if (empty($data)) {
            $model = new self;
            if ($project_id) {
                $data  = $model->findOneVar(['var_name' => $var_name, 'project_id' => $project_id]);
            } else {
                $data  = $model->findOneVar(['var_name' => $var_name]);
            }
            if (!empty($data)) {
                $RedisModel->redis->hSet($redisKey, $project_id.'_'.$var_name, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } else {
                $data = [];
            }
        }

        return $data;
    }
}
