<?php

namespace DAO;

class TimedTaskModel extends \MysqlBase
{
    public function findOne($paramsArr=null)
    {
        $sql = 'SELECT * FROM `timed_task` WHERE id<>0  ';
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
        $sql = 'SELECT * FROM `timed_task` ';
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
        
        $sql = 'INSERT INTO `timed_task` (`'.implode('`,`', array_keys($paramsArr)).'`) VALUES (\''.implode('\',\'', $paramsArr).'\') ';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();

        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error[2];
            return $return;
        }
        $return['status'] = true;
        $return['id'] = $this->db->lastInsertId();

        $data = $this->findOne(['id' => $return['id']]);
        $this->redisFlush($return['id'], $data);

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

        $sql = 'UPDATE `timed_task` set  ';
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

        $data = $this->findOne(['id' => $return['id']]);
        $this->redisFlush($return['id'], $data);

        return $return;
    }

    public function deleteOne($id)
    {
        $return = ['status' => false, 'error' => ''];
        $sql = 'DELETE FROM timed_task WHERE id='.$id;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();
        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error['2'];
            return $return;
        }
        $return['status'] = true;
        $return['id'] = $id;

        $this->allRedisFlush();

        return $return;
    }

    public function redisFlush($id, $data)
    {
        $key = 'timed_task_redis_key';
        $timeKey = 'timed_task_redis_time_key';
        $timeOut = 3600*24;
        $time = time();
        $RedisModel = new \Tools\RedisModel();

        $redisTime = $RedisModel->redis->get($timeKey);
        if (empty($redisTime) || ($time-$redisTime) >= $timeOut) {
            $this->allRedisFlush();
            $RedisModel->redis->set($timeKey, $time);
        }
        $RedisModel->redis->hset($key, $id, json_encode($data));
    }

    public function allRedisFlush()
    {
        $key = 'timed_task_redis_key';
        $RedisModel = new \Tools\RedisModel();

        $allData = $this->findAll();
        $RedisModel->redis->del($key);
        if (!empty($allData)) {
            foreach ($allData as $value) {
                $RedisModel->redis->hset($key, $value['id'], json_encode($value));
            }
        }
    }
}
