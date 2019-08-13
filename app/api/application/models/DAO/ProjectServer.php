<?php

namespace DAO;

class ProjectServerModel extends \MysqlBase
{
    const STATUS_UNBIND  = 100; //正常未绑定状态
    const STATUS_SUCCESS = 200; //正常绑定状态
    const STATUS_DELETE  = 400; //已删除状态（delete action） 如果是销毁action则是删记录
    //include_type = 100 是导入进来的实例，200是创建的
    public static $cloud = ['gaea' => 'gaea',];

    public function findOne($id)
    {
        $sql = "SELECT * FROM `project_server` WHERE id=:id AND status < "
            .self::STATUS_DELETE;
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':id' => $id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findOneByGroupId($groupId, $includeType = null)
    {
        $str = '';
        if ($includeType) {
            $str = ' AND include_type = 100';
        }
        $sql
             = 'SELECT * FROm `project_server` WHERE group_id=:group_id AND status = '
            .self::STATUS_SUCCESS.$str;
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':group_id' => $groupId]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findOneByServerId($project_id, $server_id, $type)
    {
        $sql
             = 'SELECT * FROM `project_server` WHERE project_id=:project_id AND server_id=:server_id AND type=:type AND status = '
            .self::STATUS_SUCCESS;
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute(
            [
                ':project_id' => $project_id,
                ':server_id'  => $server_id,
                ':type'       => $type,
            ]
        );
        $red = $sth->fetch(\PDO::FETCH_ASSOC);

        return $red;
    }

    // 根据传入的条件查询所有渠道的Server（支持分页）
    public function findAllServerByParamsLimit($paramsArr = [])
    {
        // 渠道商列表
        $serverTypeAll = !empty($paramsArr['type']) ? [$paramsArr['type']] : array_flip(self::$cloud);

        $sortStr = empty($paramsArr['sort']['name']) ? '' : '`name` ' . strtoupper($paramsArr['sort']['name']) . ', ';
        unset($paramsArr['sort']);

        $serverSql      = 'SELECT server_id,type FROM project_server WHERE ';
        $serverCountSql = 'SELECT count(*) as totalCount FROM project_server WHERE ';
        $serverSql .= '`type` in (\'' . implode('\',\'', $serverTypeAll) . '\') AND ';
        $serverCountSql .= '`type` in (\'' . implode('\',\'', $serverTypeAll) . '\') AND ';
        $arr = empty($paramsArr['status']) ? ['`status` = ' . self::STATUS_SUCCESS] : [];
        foreach ($paramsArr as $key => $value) {
            if ($key == 'name') {
                $arr[] = '`' . $key . '` LIKE \'%' . $value . '%\'';
            } elseif (!in_array($key, ['limit', 'offset'])) {
                if (is_array($value)) {
                    if (count($value) == 1) {
                        $arr[] = '`' . $key . '` = \'' . $value[0] . '\'';
                    } else {
                        $arr[] = '`' . $key . '` in (\'' . implode('\',\'', $value) . '\')';
                    }
                } else {
                    $arr[] = '`' . $key . '` = \'' . $value . '\'';
                }
            }
        }
        $serverSql .= implode(' AND ', $arr) . ' ORDER BY ' . $sortStr . ' field(`type`,"' . implode('","', $serverTypeAll) . '"),`server_id` ';
        $serverCountSql .= implode(' AND ', $arr) . ' ORDER BY ' . $sortStr . ' field(`type`,"' . implode('","', $serverTypeAll) . '"),`server_id` ';

        if (!empty($paramsArr['limit'])) {
            $serverSql .= ' LIMIT ' . $paramsArr['limit'] . ' ';
        }
        if (!empty($paramsArr['offset'])) {
            $serverSql .= ' OFFSET ' . $paramsArr['offset'] . ' ';
        }

        $serverSth = $this->db->prepare($serverSql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serverSth->execute();
        $serverRed   = $serverSth->fetchAll(\PDO::FETCH_ASSOC);
        $serverIDArr = [];
        if (!empty($serverRed)) {
            foreach ($serverRed as $val) {
                $serverIDArr[$val['type']][] = $val['server_id'];
            }
        }

        $serverCountSth = $this->db->prepare($serverCountSql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serverCountSth->execute();
        $serverCountRed = $serverCountSth->fetch(\PDO::FETCH_ASSOC);
        $totalCount     = $serverCountRed['totalCount'];

        $returnArr = [];
        unset($paramsArr['limit']);
        unset($paramsArr['offset']);
        $sortStr = empty($sortStr) ? '' : 'P.' . $sortStr;
        foreach ($serverTypeAll as $type) {
            if (!empty($serverIDArr[$type])) {
                // 获取每个运营商机型模板中的配置字段
                $sql  = 'SELECT * FROM `project_server`  WHERE  ';
                $sql .= ' `server_id` in (\'' . implode('\',\'', $serverIDArr[$type]) . '\') AND ';
                $arr = empty($paramsArr['status']) ? [' `status` = ' . self::STATUS_SUCCESS] : [];
                if (empty($paramsArr['type'])) {
                    $arr[] = '`type` = \'' . $type . '\'';
                }
                if (!empty($paramsArr)) {
                    foreach ($paramsArr as $key => $value) {
                        if ($key == 'name') {
                            $arr[] = '`' . $key . '` LIKE \'%' . $value . '%\'';
                        } else {
                            if (is_array($value)) {
                                if (count($value) == 1) {
                                    $arr[] = '`' . $key . '` = \'' . $value[0] . '\'';
                                } else {
                                    $arr[] = '`' . $key . '` in (\'' . implode('\',\'', $value) . '\')';
                                }
                            } else {
                                $arr[] = '`' . $key . '` = \'' . $value . '\'';
                            }
                        }
                    }
                }
                $sql .= implode(' AND ', $arr) . ' ORDER BY ' . $sortStr . ' `server_id`';
                $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
                $sth->execute();
                $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
                if (!empty($red)) {
                    $returnArr = array_merge($returnArr, $red);
                }
            }
        }
        $returnArr['totalCount'] = $totalCount;
        return $returnArr;
    }
    // 根据传入的条件查询所有渠道的Server
    public function findAllServerByParams($paramsArr = [])
    {
        // 渠道商列表
        $serverTypeAll = !empty($paramsArr['type']) ? [$paramsArr['type']] : array_flip(self::$cloud);
        $returnArr     = [];
        if (!empty($paramsArr['name'])) {
            unset($paramsArr['name']);
        }
        if (!empty($paramsArr['sort'])) {
            unset($paramsArr['sort']);
        }

        foreach ($serverTypeAll as $type) {

            //$selectStr = 'T.`' . implode('`,T.`', $fieldArr) . '`, T.id as template_id, T.name as template_name';
            $sql       = 'SELECT * FROM `project_server` WHERE  ';
            $arr       = empty($paramsArr['status']) ? ['`status` = ' . self::STATUS_SUCCESS] : [];
            if (empty($paramsArr['type'])) {
                $arr[] = '`type` = \'' . $type . '\'';
            }
            if (!empty($paramsArr)) {
                foreach ($paramsArr as $key => $value) {
                    $arr[] = '`' . $key . '` = \'' . $value . '\'';
                }
            }
            $sql .= implode(' AND ', $arr) . ' ORDER BY `server_id` ';
            $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $sth->execute();
            $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($red)) {
                $returnArr = array_merge($returnArr, $red);
            }
        }
        return $returnArr;
    }

    //查询服务器绑定状态记录
    public function findOneBindByServerId($serverId, $type)
    {
        $sql
             = 'SELECT * FROM `project_server` WHERE server_id=:server_id AND type=:type AND status='
            .self::STATUS_SUCCESS;
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':server_id' => $serverId, ':type' => $type]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findAll()
    {
        $sql = 'SELECT * FROM `project_server` WHERE status < '
            .self::STATUS_DELETE;
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute();
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findAllByProjectId($projectId, $groupId = null)
    {
        $sql    = 'SELECT * FROM `project_server` WHERE `status` = '
            .self::STATUS_SUCCESS;
        $params = [];
        if (! empty($projectId)) {
            $sql                   .= '  AND project_id=:project_id ';
            $params[':project_id'] = $projectId;
        }

        if (isset($groupId)) {
            $sql                 .= '  AND group_id=:group_id ';
            $params[':group_id'] = $groupId;
        }

        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute($params);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findAllByComposeId($compose_id)
    {
        $sql = 'SELECT * FROM project_server WHERE compose_id='.$compose_id;
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute();
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findAllByServerId($serverId, $type = 'gaea')
    {
        $sql
             = 'SELECT * FROM `project_server` WHERE `server_id`= :server_id AND `type`= :type AND `status`= '
            .self::STATUS_SUCCESS;
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':server_id' => $serverId, ':type' => $type]);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findAllByGroupId($groupId)
    {
        $sql
             = 'SELECT * FROM `project_server` WHERE group_id=:group_id   AND status = '
            .self::STATUS_SUCCESS;
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':group_id' => $groupId]);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function InsertOne(array $data)
    {
        $return = ['status', 'id'];
        $sql    = 'INSERT INTO `project_server`
                    (name ,project_id,server_id, status,run_status,include_type,description,
                    create_at,create_user,type,group_id,compose_id,template_id)
                    VALUES
                (:name ,:project_id,:server_id ,:status,:run_status,:include_type,:description,
                :create_at,:create_user,:type,:group_id,:compose_id,:template_id)';
        $sth    = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        if (! isset($data[':include_type'])) {
            $data[':include_type'] = 200; //200原生 100导入
        }
        $return['status'] = $sth->execute($data);
        if (! $return['status']) {
            $error = $sth->errorInfo();

            return ['status' => false, 'error' => $error[2]];
        } else {
            $return['id'] = $this->db->lastInsertId();
        }

        return $return;
    }

    public function updateOne($id, array $data)
    {
        $sql         = 'UPDATE `project_server`
                                SET name=:name ,
                                    status=:status ,
                                    description=:description
                   WHERE id=:id';
        $sth         = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $data[':id'] = $id;
        $status      = $sth->execute($data);
        if (! $status) {
            $error = $sth->errorInfo();

            return ['status' => false, 'error' => $error[2]];
        }

        return ['status' => true];
    }

    public function deleteOne($serverId, $type)
    {
        $sql
                = 'UPDATE `project_server` SET status=:status WHERE server_id=:server_id AND type=:type';
        $sth    = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $status = $sth->execute(
            [
                ':server_id' => $serverId,
                ':status'    => self::STATUS_DELETE,
                'type'       => $type,
            ]
        );
        if (! $status) {
            $error = $sth->errorInfo();

            return ['status' => false, 'error' => $error[2]];
        }

        return ['status' => true];
    }

    public function releaseOne($serverId, $type)
    {
        $return           = [];
        $sql              = 'DELETE FROM `project_server` WHERE server_id=:server_id AND type=:type';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':server_id' => $serverId, ':type' => $type]);
        if (!$return['status']) {
            $error = $sth->errorInfo();
            return ['status' => false, 'error' => $error[2]];
        }
        return $return;
    }


    public function recoverOne($serverId, $type)
    {
        $sql
                = 'UPDATE `project_server` SET status=:status WHERE server_id=:server_id AND type=:type';
        $sth    = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $status = $sth->execute(
            [
                ':server_id' => $serverId,
                ':status'    => self::STATUS_SUCCESS,
                'type'       => $type,
            ]
        );
        if (! $status) {
            $error = $sth->errorInfo();

            return ['status' => false, 'error' => $error[2]];
        }

        return ['status' => true];
    }

    public static function getStatus()
    {
        $obClass = new \ReflectionClass(__CLASS__);

        return $obClass->getConstants();
    }

    public static function getCloud()
    {
        return static::$cloud;
    }

    //绑定项目
    public function bindProject($projectId, $groupId, $serverId, $type)
    {
        $sql
                = 'UPDATE `project_server` SET project_id=:project_id , group_id=:group_id ,status='
            .self::STATUS_SUCCESS.' WHERE server_id=:server_id AND type=:type';
        $sth    = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $status = $sth->execute(
            [
                ':server_id'  => $serverId,
                ':project_id' => $projectId,
                ':group_id'   => $groupId,
                ':type'       => $type,
            ]
        );
        if (! $status) {
            $error = $sth->errorInfo();

            return ['status' => false, 'error' => $error[2]];
        }

        return ['status' => true];
    }

    //解除绑定项目
    public function unbindProject($serverId, $projectId, $groupId, $type)
    {
        $sql    = 'UPDATE `project_server` SET project_id=0 , group_id=0 ,status='
            .self::STATUS_UNBIND
            .' WHERE server_id=:server_id AND project_id=:project_id AND group_id=:group_id AND type=:type';
        $sth    = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $status = $sth->execute(
            [
                ':server_id'  => $serverId,
                ':project_id' => $projectId,
                ':group_id'   => $groupId,
                ':type'       => $type,
            ]
        );
        if (! $status) {
            $error = $sth->errorInfo();

            return ['status' => false, 'error' => $error[2]];
        }

        return ['status' => true];
    }

    //绑定实例组
    public function bindGroup(
        $composeId,
        $groupId,
        $serverId,
        $description,
        $type
    ) {
        try {
            // 开始事务
            $this->db->beginTransaction();
            $sql
                    = 'UPDATE `project_server` SET compose_id=:compose_id , group_id=:group_id ,description=:description ,status='
                .self::STATUS_SUCCESS
                .' WHERE server_id=:server_id AND type=:type';
            $sth    = $this->db->prepare(
                $sql,
                [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            );
            $status = $sth->execute(
                [
                    ':server_id'   => $serverId,
                    ':compose_id'  => $composeId,
                    ':group_id'    => $groupId,
                    ':description' => $description,
                    ':type'        => $type,
                ]
            );
            if (! $status) {
                $error = $sth->errorInfo();

                return ['status' => false, 'error' => $error[2]];
            }
            $sql    = 'UPDATE `server_group` SET type=100 WHERE id=:id';
            $sth    = $this->db->prepare(
                $sql,
                [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
            );
            $status = $sth->execute([':id' => $groupId]);
            if (! $status) {
                $error = $sth->errorInfo();

                return ['status' => false, 'error' => $error[2]];
            }
            // 提交事务
            $this->db->commit();
        } catch (PDOException $e) {
            // 如果执行失败回滚
            $this->db->rollBack();

            return [
                'status' => false,
                'error'  => json_encode($e->getMessage()),
            ];
        }

        return ['status' => true];
    }

    // 添加image变了的实例到redis缓存中
    public static function setComposeChangeServer($compose_id)
    {
        $redisModel = new \Tools\RedisModel();
        $redisKey   = 'image_change_server';

        $model     = new self;
        $serverArr = $model->findAllByComposeId($compose_id);
        if (! empty($serverArr)) {
            foreach ($serverArr as $value) {
                $hkey = $value['type'].'_'.$value['server_id'];
                $redisModel->redis->hset($redisKey, $hkey, $hkey);
            }
        }
    }

    // 从redis缓存中去掉image已经更新了的实例
    public function delComposeChangeServer($server_id, $type)
    {
        $redisModel = new \Tools\RedisModel();
        $redisKey   = 'image_change_server';
        $hkey       = $type.'_'.$server_id;
        $redisModel->redis->hdel($redisKey, $hkey);
    }

    // 获得所有image更新了的实例列表
    public function getAllComposeChangeServer()
    {
        $data       = [];
        $redisModel = new \Tools\RedisModel();
        $dataArr    = $redisModel->redis->hgetAll('image_change_server');
        if (! empty($dataArr)) {
            foreach ($dataArr as $hkey) {
                $data[] = $hkey;
            }
        }

        return $data;
    }

    public function getIncludeServer($project_id, $type)
    {
        ! $type ? $type = 'gaea' : null;
        $server = $type.'_server';
        $sql    = "SELECT PS.`server_id` as server_id,
                            B.`name` as name
                        FROM `project_server` PS
                        LEFT JOIN `".$server."` B
                        ON PS.`server_id`=B.`id`
                        WHERE PS.`include_type`=100
                         AND PS.`project_id`=:project_id
                         AND PS.`type`=:type
                         AND PS.`group_id`=0
                         AND PS.`status` < ".self::STATUS_DELETE;
        $sth    = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':project_id' => $project_id, ':type' => $type]);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findAllByIps($ipArr, $type)
    {
        $server  = $type.'_server';
        $sql
                 = 'SELECT PS.`server_id` as instance_id,PS.`project_id`,PS.`group_id`,B.`name` as instance_name,B.`internal_ip` as ip,B.`public_ip`,P.`name` as project_name FROM `project_server` PS LEFT JOIN `'
            .$server
            .'` B ON PS.`server_id`=B.`id` LEFT JOIN `project` P ON PS.`project_id`=P.`id` WHERE PS.`type`=:type';
        $execute = [':type' => $type];
        if ($ipArr) {
            $ipIn            = implode(',', $ipArr);
            $sql             .= ' AND find_in_set(cast(B.`internal_ip` as char), :ips)';
            $execute[':ips'] = $ipIn;
        }
        $sth = $this->db->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );
        $sth->execute($execute);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findAllData($project_id = null, $instance_id = null, $type)
    {
        $server  = $type.'_server';
        $sql
                 = 'SELECT PS.`server_id` as instance_id,PS.`project_id`,PS.`type` as cloud_type,PS.`compose_id`,B.`name` as instance_name,B.`internal_ip` as ip,P.`name` as project_name FROM `project_server` PS LEFT JOIN `'
            .$server
            .'` B ON PS.`server_id`=B.`id` LEFT JOIN `project` P ON PS.`project_id`=P.`id` WHERE PS.`type`=:type AND PS.`status`<'
            .self::STATUS_DELETE;
        $execute = [':type' => $type];
        if ($project_id) {
            $sql                    .= ' AND PS.`project_id` = :project_id';
            $execute[':project_id'] = $project_id;
        }
        if ($instance_id) {
            $sql                     .= ' AND PS.`server_id` = :instance_id';
            $execute[':instance_id'] = $instance_id;
        }
        $sth = $this->db->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );
        $sth->execute($execute);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findAllByParams($paramsArr = null)
    {
        $sql = 'SELECT * FROM `project_server` ';
        $arr = [];
        if (! empty($paramsArr)) {
            $sql    .= ' WHERE ';
            $strArr = [];
            foreach ($paramsArr as $key => $value) {
                $strArr[]      = $key.'=:'.$key.' ';
                $arr[':'.$key] = $value;
            }
            $sql .= ' '.implode(' and ', $strArr);
        }

        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute($arr);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    // 根据type获取个渠道服务器model
    public static function getServerModel($type)
    {
        switch ($type) {
        case 'gaea':
            $model = new \DAO\GaeaServerModel;
            break;

        default:
            return false;
        }

        return $model;
    }
}
