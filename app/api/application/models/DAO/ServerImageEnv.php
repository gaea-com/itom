<?php

namespace DAO;

use JsonWebTokenModel;
use PDO;
use Tools\RedisModel;
use Yaf\Application;

/**
 *  实例下的镜像对应要启动时的env参数关联记录表
 * 每一个实例下面会pull下来很多的image镜像
 * 如果要让image 执行，实际上是docker run  image：tag -option
 * option就是env，里面会记录ENV环境变量、数据卷、CMD启动命令和Entrypoint启动参数
 * 此表就是记录这些启动参数
 */
class ServerImageEnvModel extends \MysqlBase
{
    const PARAMS_TYPE_ENV        = 100;
    const PARAMS_TYPE_DATA       = 200;
    const PARAMS_TYPE_CMD        = 300;
    const PARAMS_TYPE_ENTRYPOINT = 400;

    public function findOne($serverId, $imageName)
    {
        $sql
             = 'SELECT * FROM `server_image_env` WHERE server_id=:serverId AND image_name=:imageName';
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':serverId' => $serverId, ':imageName' => $imageName]);
        $red = $sth->fetchAll(PDO::FETCH_ASSOC);
        if (! empty($red)) {
            $data['server_id'] = $serverId;
            $data['image_name']  = $imageName;
            foreach ($red as $v) {
                if ($v['params_type'] == self::PARAMS_TYPE_ENV) {
                    $data['env'][$v['key_name']] = $v['key_value'];
                } else {
                    if ($v['params_type'] == self::PARAMS_TYPE_DATA) {
                        $data['data'][$v['key_name']] = $v['key_value'];
                    }
                }
            }

            return ['status' => true, 'data' => $data];
        }

        return ['status' => true, 'data' => ''];
    }


    /**
     * @param int   $serverId
     * @param int   $iamgeId
     * @param array $paramsData ['env' =>[] , 'data'=> []]
     *                          必须是全量的，因为修改是先删后重新插入
     *
     * @return array
     */
    public function insertOne(int $serverId, $imageName, array $paramsData)
    {
        $return = ['status' => false];
        $user   = JsonWebTokenModel::validateJWT()->getClaim('uid');

        $sql
             = 'SELECT * FROM `server_image_env` WHERE server_id=:serverId AND image_name=:imageName';
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute([':serverId' => $serverId, ':imageName' => $imageName]);
        $red = $sth->fetchAll(PDO::FETCH_ASSOC);
        if ($red) {
            $status = $this->deleteOne($serverId, $imageName);
            if (! $status['status']) {
                return ['status' => false, 'error' => '清除原数据失败'];
            }
        }
        $this->db->beginTransaction();
        //循环插入env相关变量
        if (isset($paramsData['env']) && ! empty($paramsData['env'])) {
            foreach ($paramsData['env'] as $key => $value) {
                $sql    = 'INSERT INTO `server_image_env` (server_id, image_name,
                           params_type,key_name,key_value,create_user) VALUES (
                           :serverId,:imageName,:type,:keyName,:keyValue,:user) ';
                $sth    = $this->db->prepare(
                    $sql,
                    [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
                );
                $status = $sth->execute(
                    [
                        ':serverId' => $serverId,
                        ':imageName'  => $imageName,
                        ':type'     => self::PARAMS_TYPE_ENV,
                        ':keyName'  => $key,
                        ':keyValue' => $value,
                        ':user'     => $user,
                    ]
                );

                if (! $status) {
                    $return['error'] = $sth->errorInfo()[2];
                    $this->db->rollBack();

                    return $return;
                }
            }
        }
        if (isset($paramsData['data']) && ! empty($paramsData['data'])) {
            foreach ($paramsData['data'] as $key => $value) {
                $sql    = 'INSERT INTO `server_image_env` (server_id, image_name,
                           params_type,key_name,key_value,create_user) VALUES (
                           :serverId,:imageName,:type,:keyName,:keyValue,:user) ';
                $sth    = $this->db->prepare(
                    $sql,
                    [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
                );
                $status = $sth->execute(
                    [
                        ':serverId' => $serverId,
                        ':imageName'  => $imageName,
                        ':type'     => self::PARAMS_TYPE_DATA,
                        ':keyName'  => $key,
                        ':keyValue' => $value,
                        ':user'     => $user,
                    ]
                );

                if (! $status) {
                    $return['error'] = $sth->errorInfo()[2];
                    $this->db->rollBack();

                    return $return;
                }
            }
        }

        $status = $this->db->commit();

        return ['status' => $status];
    }

    //update的逻辑需要delete，先删后重新插入，而不是修改
    public function deleteOne($serverId, $imageName)
    {
        $return = ['status' => false];
        $sql
                = 'DELETE FROM `server_image_env` WHERE server_id=:serverId AND image_Name=:imageName';
        $sth    = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $status = $sth->execute(
            [':serverId' => $serverId, ':imageName' => $imageName]
        );
        if (! $status) {
            $return['error'] = $sth->errorInfo();

            return $return;
        }

        return ['status' => true];
    }

    // 清除缓存
    public function deleteCache($id)
    {
        $redisModel = new RedisModel();
        $redisKey   = 'server_image_env_instance_cache_key';
        $redisModel->redis->hdel($redisKey, $id);
    }
}
