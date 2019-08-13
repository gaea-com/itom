<?php

namespace DAO;

class ServerGroupModel extends \MysqlBase
{
    const TYPE_COPY_CAN = 200; //可以复制的实例组类型
    const TYPE_COPY_BAN = 100; //不可复制的实例组类型

    public function findOne($id)
    {
        $sql = 'SELECT * FROM `server_group` WHERE id=:id';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':id' => $id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAll($id)
    {
        $sql = 'SELECT * FROM `server_group` WHERE project_id=:pid ORDER BY create_at';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':pid' => $id]);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function InsertOne(array $data)
    {
        $return = ['status', 'id'];
        $sql    = 'INSERT INTO `server_group`
                    (name , type ,project_id,create_at,create_user)
                    VALUES
                (:name , :type,:project_id,:create_at,:create_user)';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute($data);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        } else {
            $return['id'] = $this->db->lastInsertId();
        }

        return $return;
    }

    public function updateOne($id, array $data)
    {
        $sql = 'UPDATE `server_group`
                                SET name=:name ,
                                    type=:type ,
                                    project_id=:project_id
                   WHERE id=:id';
        $sth         = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $data[':id'] = $id;
        $status      = $sth->execute($data);
        if (!$status) {
            $error = $sth->errorInfo();
            return ['status' => false, 'error' => $error[2]];
        }
        // 清除缓存
        $this->deleteCache($id);
        return ['status' => true];
    }

    public function deleteOne($id)
    {
        //先查询实例，如果实例存在则不能删除
        $sql = 'SELECT id,group_id,project_id FROM `project_server` WHERE group_id=:id LIMIT 1';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':id' => $id]);
        $project = $sth->fetch(\PDO::FETCH_ASSOC);
        if ($project) {
            return ['status' => false, 'error' => '存在关联的服务器实例'];
        }
        $sql    = 'DELETE FROM `server_group` WHERE id=:id';
        $sth    = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $status = $sth->execute([':id' => $id]);
        if (!$status) {
            $error = $sth->errorInfo();
            return ['status' => false, 'error' => $error[2]];
        }
        // 清除缓存
        $this->deleteCache($id);
        return ['status' => true];
    }

    public static function getType()
    {
        $obClass = new \ReflectionClass(__CLASS__);
        return $obClass->getConstants();
    }

    public function findAllByParams($paramsArr = null)
    {
        $sql = 'SELECT * FROM `server_group` ';
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

    // 通过缓存查询
    public function findOneByCache($id)
    {
        $redisModel = new \Tools\RedisModel();
        $redisKey   = 'server_group_cache_key';
        $data       = json_decode($redisModel->redis->hget($redisKey, $id), true);
        if (empty($data)) {
            $data = $this->findOne($id);
            if (!empty($data)) {
                $redisModel->redis->hset($redisKey, $id, json_encode($data));
            }
        }
        return $data;
    }
    // 清除缓存
    public function deleteCache($id)
    {
        $redisModel = new \Tools\RedisModel();
        $redisKey   = 'server_group_cache_key';
        $redisModel->redis->hdel($redisKey, $id);
    }
}
