<?php
namespace DAO;

use JsonWebTokenModel;

class DockerComposeModel extends \MysqlBase
{
    const STATUS_FAILURE = 100;
    const STATUS_NORMAL  = 200;

    public function findOne($id)
    {
        $sql = "SELECT * FROM `docker_compose` WHERE id=:id AND status=" . self::STATUS_NORMAL;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':id' => $id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAllForList($pid = null, $uid = null, $isRoot = false)
    {
        $temSql = 'SELECT * FROM `docker_compose` ';
        $paramsArr = [];
        if (!empty($pid)) {
            $temSql .= 'WHERE  project_id=:project_id';
            $paramsArr[':project_id'] = $pid;
        } else {
            if ($uid && !$isRoot) {
                $pModel     = new AccreditModel();
                $userPidArr = $pModel->getUserProject($uid);
                $temSql = 'WHERE  find_in_set(cast(project_id as char), :project_ids)';
                $paramsArr[':project_id'] = $userPidArr;
            }
        }

        $sth = $this->db->prepare($temSql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        if (empty($paramsArr)) {
            $sth->execute();
        } else {
            $sth->execute($paramsArr);
        }
        $temRed = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        if (!empty($temRed)) {
            foreach ($temRed as $key => $rowVal) {
                $data[$key]['id']          = $rowVal['id'];
                $data[$key]['name']        = $rowVal['name'];
                $data[$key]['description'] = $rowVal['description'];

                $data[$key]['create_at']   = $rowVal['create_at'];
                $data[$key]['image_name']  = json_decode($rowVal['image_name'], true);
                $data[$key]['image_times'] = json_decode($rowVal['image_times'], true);

                $proModel                 = new ProjectModel;
                $proData                  = $proModel->findOne($rowVal['project_id']);
                $data[$key]['project_id'] = empty($proData) ? null : [$proData['id'] => $proData['name']];
                $data[$key]['status']     = (int)$rowVal['status'];
            }
        }

        return $data;
    }

    //根据ID获得模板详细信息
    public function getDockerComposeById($id, $sid = null, $type = null)
    {
        $info = $this->findOne($id);
        $data = [];
        if (!empty($info)) {
            $imageArr = json_decode($info['image_name'], true);
            if (!empty($imageArr)) {
                $data['id']          = $info['id'];
                $data['name']        = $info['name'];
                $data['description'] = $info['description'];
                $data['create_at']   = $info['create_at'];
                $data['project_id']  = $info['project_id'];

                $imageArr = array_values($imageArr);

                $data['image_name']  = $imageArr;
                $data['image_times'] = json_decode($info['image_times'], true);
            }
        }

        return $data;
    }

    // 添加容器编排模板
    public function insertOne($data)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($data['user_id'])) {
            $user_id = JsonWebTokenModel::validateJWT()->getClaim('uid');
        } else {
            $user_id = $data['user_id'];
            unset($data['user_id']);
        }

        $data[':create_user'] = $user_id;
        $data[':status']      = self::STATUS_NORMAL;

        $sql              = 'INSERT INTO `docker_compose` (name,description,project_id,image_name,image_times,status,create_user) VALUES (:name,:description,:project_id,:image_name,:image_times,:status,:create_user)';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute($data);
        if (!$return['status']) {
            $return['error'] = $sth->errorInfo();
        } else {
            $return['id'] = $this->db->lastInsertId();
            $this->deleteCache($return['id']);
        }

        return $return;
    }

    /**
     * 编辑容器编排模板
     *
     * @param $id
     * @param $data
     *
     * @return array
     */
    public function updateOne($id, $data)
    {
        $return = ['status' => false, 'error' => ''];
        // 清除缓存
        $this->deleteCache($id);
        $oldData = $this->findOne($id);
        if (empty($oldData)) {
            $return['error'] = '编排模板ID错误';
            return $return;
        }

        $imageChange = false;
        $oldImageArr = json_decode($oldData['image_name'], true);
        $oldImageArr = $this->getImageNameArr($oldImageArr);
        $newImageArr = json_decode($data[':image_name'], true);
        $newImageArr = $this->getImageNameArr($newImageArr);
        if (!empty($oldImageArr)) {
            if (!empty($newImageArr)) {
                $newArr = array_values($newImageArr);
                // sort($newArr);
                $oldArr = array_values($oldImageArr);
                // sort($oldArr);
                if ($oldArr != $newArr) {
                    $imageChange = true;
                }
            } else {
                $imageChange = true;
            }
        } elseif (!empty($newImageArr)) {
            $imageChange = true;
        }
        //镜像变更
        if ($imageChange) {
            \DAO\ProjectServerModel::setComposeChangeServer($id);
        }

        $user_id          = JsonWebTokenModel::validateJWT()->getClaim('uid');
        $data[':id']      = $id;
        $sql              = 'UPDATE `docker_compose` SET name=:name, description=:description, image_name=:image_name, image_times=:image_times WHERE id=:id ;';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute($data);
        if (!$return['status']) {
            $return['error'] = $sth->errorInfo();
        } else {
            $return['id'] = $id;
        }

        return $return;
    }

    // 获取去掉版本号的镜像名称数组，供模板编辑时判断小橙框使用
    public function getImageNameArr($imageNameArr = [])
    {
        $arr = [];
        if (!empty($imageNameArr)) {
            foreach ($imageNameArr as $image_name) {
                $arr[] = $this->getImageName($image_name);
            }
        }
        return $arr;
    }

    // 禁用容器编排模板
    public function disableTem($id)
    {
        $return  = ['status' => false, 'error' => ''];
        $user_id = JsonWebTokenModel::validateJWT()->getClaim('uid');

        $sql              = 'UPDATE `docker_compose` SET status=:status WHERE id=:id ; ';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':id' => $id, 'status' => self::STATUS_FAILURE]);

        if (!$return['status']) {
            $return['error'] = $sth->errorInfo();
        } else {
            $return['id'] = $id;
        }

        // 清除缓存
        $this->deleteCache($id);

        return $return;
    }

    // 启用容器编排模板
    public function enabledTem($id)
    {
        $return  = ['status' => false, 'error' => ''];
        $user_id = JsonWebTokenModel::validateJWT()->getClaim('uid');

        $sql              = 'UPDATE `docker_compose` SET status=:status WHERE id=:id ; ';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':id' => $id, 'status' => self::STATUS_NORMAL]);

        if (!$return['status']) {
            $return['error'] = $sth->errorInfo();
        } else {
            $return['id'] = $id;
        }

        // 清除缓存
        $this->deleteCache($id);

        return $return;
    }

    //获得容器编排模板列表
    public function getDockerComposeList($project_id = null)
    {
        $return = ['status' => false, 'error' => ''];
        $temSql = empty($project_id) ? 'SELECT * FROM `docker_compose`; ' : 'SELECT * FROM `docker_compose` WHERE project_id=' . $project_id . ' AND status=' . self::STATUS_NORMAL . ' ; ';
        $sth    = $this->db->prepare($temSql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute();
        $temRed = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];

        if (!empty($temRed)) {
            foreach ($temRed as $key => $temArr) {
                $imageArr = json_decode($temArr['image_name'], true);
                if (!empty($imageArr)) {
                    $data[$key]['image_name']  = array_values($imageArr);
                    $data[$key]['image_times'] = json_decode($temArr['image_times'], true);
                    $data[$key]['id']          = $parent_id          = $temArr['id'];
                    $data[$key]['name']        = $temArr['name'];
                } else {
                    $data[$key]['id']   = $temArr['id'];
                    $data[$key]['name'] = $temArr['name'];
                }
            }
        }

        return $data;
    }

    public function getImageName($image_name)
    {
        $arr = explode(':', $image_name);
        if (count($arr) >= 2) {
            array_pop($arr);
            $imageName = implode(':', $arr);
        } else {
            $imageName = $image_name;
        }
        return $imageName;
    }

    // 通过缓存查询
    public function findOneByCache($id)
    {
        $redisModel = new \Tools\RedisModel();
        $redisKey   = 'docker_compose_cache_key';
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
        $redisKey   = 'docker_compose_cache_key';
        $redisModel->redis->hdel($redisKey, $id);
    }
}
