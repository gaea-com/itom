<?php
namespace DAO;

class GaeaServerModel extends \MysqlBase
{
    const STATUS_SUCCESS = 200; //正常绑定状态
    const STATUS_DELETE  = 400; //已删除销毁状态

    public function findOne($id)
    {
        $sql = "SELECT * FROM `gaea_server` WHERE id=:id AND status != " . self::STATUS_DELETE;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':id' => $id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAll()
    {
        $sql = 'SELECT B.*,P.* FROM `gaea_server` AS B
        LEFT JOIN `project_server` AS P ON B.`id`=P.`server_id`
         WHERE B.`status` != ' . self::STATUS_DELETE . ' AND
         (P.`status`=' . ProjectServerModel::STATUS_SUCCESS . ' OR (P.`status`=' . ProjectServerModel::STATUS_UNBIND . ' AND P.`project_id`=0))';
        //select B.* , P.* FROM `gaea_server` AS B LEFT JOIN `project_server` AS P ON B.`id` = P.`server_id`
        // WHERE B.`status`<400 AND (P.`status`=200 OR (P.`status`=100 AND P.`project_id`=0));
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute();
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function InsertOne(array $data)
    {
        $return = ['status', 'id'];
        $sql    = 'INSERT INTO `gaea_server`
                    (name ,status,create_time,internal_ip,public_ip,include_type,cpu,ram,cds)
                    VALUES
                    (:name,:status,:create_time,:internal_ip,:public_ip,:include_type,:cpu,:ram,:cds)';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        if (!isset($data[':include_type'])) {
            $data[':include_type'] = 200;
        }
        $return['status'] = $sth->execute($data);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        } else {
            $return['id'] = $this->db->lastInsertId();
        }

        return $return;
    }

    public function deleteOne($id)
    {
        $sql    = 'UPDATE `gaea_server` SET  status=:status WHERE id=:id';
        $sth    = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $status = $sth->execute([':id' => $id, ':status' => self::STATUS_DELETE]);
        if (!$status) {
            $error = $sth->errorInfo();
            return ['status' => false, 'error' => $error[2]];
        }
        // 清除缓存
        $this->deleteCache($id);
        return ['status' => true];
    }

    public function releaseOne($id)
    {
        $return           = [];
        $sql              = 'DELETE FROM `gaea_server` WHERE id=:id';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':id' => $id]);
        if (!$return['status']) {
            $error = $sth->errorInfo();
            return ['status' => false, 'error' => $error[2]];
        }
        return $return;
    }

    public function findAllByParams($paramsArr = null)
    {
        if (!empty($paramsArr['status']) && $paramsArr['status'] == 'success') {
            $isSuccess = true;
            unset($paramsArr['status']);
        }
        $sql = 'SELECT * FROM `gaea_server` ';
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
        if (!empty($isSuccess)) {
            $sql .= empty($paramsArr) ? ' where status != ' . self::STATUS_DELETE : ' and status != ' . self::STATUS_DELETE;
        }

        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function finOneByIp($internal_ip)
    {
        $sql = "SELECT * FROM `gaea_server` WHERE internal_ip=:internal_ip AND status != " . self::STATUS_DELETE;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':internal_ip' => $internal_ip]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    // 通过缓存查询
    public function findOneByCache($id)
    {
        $redisModel = new \Tools\RedisModel();
        $redisKey   = 'gaea_server_cache_key';
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
        $redisKey   = 'gaea_server_cache_key';
        $redisModel->redis->hdel($redisKey, $id);
    }
}
