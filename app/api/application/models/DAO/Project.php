<?php

namespace DAO;

class ProjectModel extends \MysqlBase
{
    public function findOne($id)
    {
        $sql = "SELECT * FROM `project` WHERE id=:id ";
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':id' => $id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAll($projectIdArr = null)
    {
        if ($projectIdArr) {
            $sql = 'SELECT * FROM `project` WHERE find_in_set(cast(id as char), :id)';
            $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $projectIds = implode(',', $projectIdArr);
            $sth->execute([':id' => $projectIds]);
        } else {
            $sql = 'SELECT * FROM `project` WHERE 1';
            $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $sth->execute();
        }

        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function InsertOne(array $data)
    {
        $return = ['status', 'id'];
        $sql    = 'INSERT INTO `project`
                    (name ,project_descption,create_at,create_user)
                    VALUES
                (:name ,:project_descption,:create_at,:create_user)';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);

        $return['status'] = $sth->execute($data);
        if (!$return['status']) {
            $return['error'] = $sth->errorInfo();
        } else {
            $return['id'] = $this->db->lastInsertId();
            $uid = \JsonWebTokenModel::validateJWT()->getClaim('uid');

            $accModel  = new \DAO\AccreditModel;
            $accModel->insertOne([':user_id' => $uid, ':project_id' => $return['id'] , ':create_user' => $uid]);
        }
        return $return;
    }

    public function updateOne($id, array $data)
    {
        $sql = 'UPDATE `project`
                                SET
                                    project_descption=:project_descption
                                    
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
        $sql         = 'DELETE FROM `project` WHERE id=:id';
        $sth         = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $data[':id'] = $id;
        $status      = $sth->execute([':id' => $id]);
        if (!$status) {
            $error = $sth->errorInfo();
            return ['status' => false, 'error' => $error[2]];
        }
        // 清除缓存
        $this->deleteCache($id);
        return ['status' => true];
    }

    public function findOneByVarName($name)
    {
        $sql = 'SELECT id FROM `project` WHERE upper(name)=:name';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':name' => $name]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    // 通过缓存查询
    public function findOneByCache($id, $uid = null)
    {
        $redisModel = new \Tools\RedisModel();
        $redisKey   = 'project_cache_key';
        $data = json_decode($redisModel->redis->hget($redisKey, $id), true);
        if (empty($data)) {
            $data = $this->findOne($id, $uid);
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
        $redisKey   = 'project_cache_key';
        $redisModel->redis->hdel($redisKey, $id);
    }
}
