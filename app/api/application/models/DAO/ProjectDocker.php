<?php
/**
 * ProjectDocker Model
 * 项目容器镜像 MODEL
 *
 * @author junjie.feng
 * @since  1.0
 */
namespace DAO;

class ProjectDockerModel extends \MysqlBase
{
    const STATUS_CLOSE = 100;
    const STATUS_OPEN  = 200;

    //创建容器
    public function insertOne(array $data)
    {
        $return = ['status' => false, 'error' => ''];
        $sql    = 'INSERT INTO `project_docker` (name,description,container_id,project_id,image_name,instance_id,cloud_type,ip,create_user,create_at) VALUES (:name,:description,:container_id,:project_id,:image_name,:instance_id,:cloud_type,:ip,:create_user,:create_at)';

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

    //更新状态
    public function updateContainerStatus($container_id, $ip, $status)
    {
        $return = ['status' => false, 'error' => ''];
        $sql    = 'UPDATE `project_docker` SET status = :status';
        $sql .= ' WHERE container_id = :container_id and ip = :ip';
        $sth              = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $return['status'] = $sth->execute([':container_id' => $container_id, ':ip' => $ip, ':status' => $status]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }

    //更新状态
    public function updateContainers($ip, $data)
    {
        $return = ['status' => false, 'error' => ''];
        try {
            // 开始事务
            $this->db->beginTransaction();
            $sql              = 'DELETE FROM `project_docker` WHERE ip = :ip';
            $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $return['status'] = $sth->execute([':ip' => $ip]);
            if (!$return['status']) {
                $this->db->rollBack();
                $error           = $sth->errorInfo();
                $return['error'] = $error[2];
                return $return;
            }
            foreach ($data as $value) {
                $sql              = 'INSERT INTO `project_docker` (project_id,ip,instance_id,container_id,name,status,create_user,create_at,cloud_type,image_name,description) VALUES (:project_id,:ip,:instance_id,:container_id,:name,:status,:create_user,:create_at,:cloud_type,:image_name,:description)';
                $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
                $return['status'] = $sth->execute($value);
                if (!$return['status']) {
                    $this->db->rollBack();
                    $error           = $sth->errorInfo();
                    $return['error'] = $error[2];
                    return $return;
                }
            }
            // 提交事务
            $this->db->commit();
        } catch (PDOException $e) {
            // 如果执行失败回滚
            $this->db->rollBack();
            $return['error'] = json_encode($e->getMessage());
        }
        return $return;
    }

    public function findOne($id)
    {
        $sql = 'SELECT * FROM `project_docker`
                WHERE  id = :id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute(array(':id' => $id));
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAll()
    {
        $sql = 'SELECT id,name,ip,description,container_id,status FROM `project_docker`';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute();
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAllOpen($project_id = null, $instance_id = null, $cloud_type = null)
    {
        $server                    = $cloud_type . '_server';
        $sql                       = 'SELECT D.*,B.`name` as instance_name,P.`name` as project_name FROM `project_docker` D LEFT JOIN `' . $server . '` B ON D.`instance_id`=B.`id` LEFT JOIN `project` P ON D.`project_id`=P.`id`';
        $sql .= ' WHERE D.`status` = :status';
        $execute = [':status' => self::STATUS_OPEN];
        if ($project_id) {
            $sql .= ' AND D.`project_id` = :project_id';
            $execute[':project_id'] = $project_id;
        }
        if ($instance_id) {
            $sql .= ' AND D.`instance_id` = :instance_id';
            $execute[':instance_id'] = $instance_id;
        }
        if ($cloud_type) {
            $sql .= ' AND D.`cloud_type` = :cloud_type';
            $execute[':cloud_type'] = $cloud_type;
        }
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute($execute);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAllByPid($pid)
    {
        $sql = 'SELECT id,name FROM `project_docker` WHERE  project_id=:project_id AND  status=' . self::STATUS_OPEN;
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute([':project_id' => $pid]);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getContainers($project_id = null, $instance_id = null, $cloud_type = null)
    {
        $server                    = $cloud_type . '_server';
        $sql     = 'SELECT D.*,B.`name` as instance_name,P.`name` as project_name FROM `project_docker` D LEFT JOIN `'.$server.'` B ON D.`instance_id`=B.`id` LEFT JOIN `project` P ON D.`project_id`=P.`id`';
        $execute = [];
        if ($project_id && $instance_id) {
            $sql .= ' WHERE D.`project_id` = :project_id AND D.`instance_id` = :instance_id';
            $execute[':project_id']  = $project_id;
            $execute[':instance_id'] = $instance_id;
        } elseif ($project_id) {
            $sql .= ' WHERE D.`project_id` = :project_id';
            $execute[':project_id'] = $project_id;
        } elseif ($instance_id) {
            $sql .= ' WHERE D.`instance_id` = :instance_id';
            $execute[':instance_id'] = $instance_id;
        }
        if ($cloud_type) {
            $sql .= ' AND D.`cloud_type` = :cloud_type';
            $execute[':cloud_type'] = $cloud_type;
        }
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute($execute);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function deleteAllByServerId($serverid, $type)
    {
        $return           = [];
        $sql              = 'DELETE FROM `project_docker` WHERE instance_id=:instance_id AND cloud_type=:type';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':instance_id' => $serverid, ':type' => $type]);
        if (!$return['status']) {
            $error = $sth->errorInfo();
            return ['status' => false, 'error' => $error[2]];
        }
        return $return;
    }

    public function findAllByParams($paramsArr = null)
    {
        $sql = 'SELECT * FROM `project_docker` ';
        $arr = [];
        if (!empty($paramsArr)) {
            $sql .= ' WHERE ';
            $strArr = [];
            foreach ($paramsArr as $key => $value) {
                if ($key == 'image_name') {
                    $strArr[] = $key . ' like \'%' . $value . '%\' ';
                } else {
                    $strArr[]        = $key . '=:' . $key . ' ';
                    $arr[':' . $key] = $value;
                }
            }
            $sql .= ' ' . implode(' and ', $strArr);
        }

        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function updateAllByIds($idArr, $paramsArr)
    {
        $sql = 'UPDATE `project_docker` set  ';
        $arr = [];
        foreach ($paramsArr as $key => $value) {
            $arr[] = '`'.$key.'`=\''.$value.'\'';
        }
        $sql .= implode(' , ', $arr);
        $sql .= ' WHERE find_in_set(cast(`id` as char), :ids)';
        $sth  = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $idIn = implode(',', $idArr);
        $status = $sth->execute([':ids' => $idIn]);
        if (!$status) {
            $error = $sth->errorInfo();
            return ['status' => false, 'error' => $error[2]];
        }
        return ['status' => true];
    }
}
