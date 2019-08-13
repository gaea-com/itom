<?php
/**
 * DOCKER镜像 MODEL
 *
 * 容器上的镜像列表
 */
namespace DAO;

class DockerImageModel extends \MysqlBase
{
    public function findOneByIpName($ip, $imageName)
    {
        $return = ['status' => false, 'error' => ''];
        $sql = 'SELECT * FROM `docker_image` WHERE ip=:ip AND name_version=:imageName';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':ip' => $ip , ':imageName' => $imageName]);
        $res = $sth->fetch(\PDO::FETCH_ASSOC);
        if (empty($res)) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        } else {
            $return['status'] = true;
            $return['data'] = $res;
        }

        return $return;
    }

    //新增镜像信息
    public function insertOne($image_id, $short_id, $ip, $name_version)
    {
        $return = ['status' => false, 'error' => ''];
        $sql    = 'INSERT INTO `docker_image` (image_id,short_id,ip,name_version) VALUES (:image_id,:short_id,:ip,:name_version)';

        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':image_id' => $image_id, ':short_id' => $short_id, ':ip' => $ip, ':name_version' => $name_version]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        } else {
            $return['id'] = $this->db->lastInsertId();
        }

        return $return;
    }

    //更新镜像信息
    public function updateOne($image_id, $short_id, $ip, $name_version)
    {
        $return           = ['status' => false, 'error' => ''];
        $sql              = 'UPDATE `docker_image` SET image_id=:image_id,short_id=:short_id,create_at=:create_at WHERE ip=:ip AND name_version=:name_version';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':image_id' => $image_id, ':short_id' => $short_id, ':ip' => $ip, ':name_version' => $name_version, ':create_at'=>date('Y-m-d H:i:s')]);
        if (!$return['status']) {
            $return['error'] = $sth->errorInfo();
        } else {
            $rows = $sth->rowCount();
            if ($rows<1) {
                $res = $this->insertOne($image_id, $short_id, $ip, $name_version);
                if (!$res['status']) {
                    $error           = $sth->errorInfo();
                    $return['error'] = $error[2];
                } else {
                    $return['id'] = $this->db->lastInsertId();
                }
            }
        }
        return $return;
    }

    //更新镜像信息
    public function updateInfo($image_list, $ip)
    {
        $return = ['status' => false, 'error' => ''];
        try {
            // 开始事务
            $this->db->beginTransaction();
            $sql              = 'DELETE FROM `docker_image` WHERE ip = :ip';
            $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
            $return['status'] = $sth->execute([':ip' => $ip]);
            if (!$return['status']) {
                $this->db->rollBack();
                $error           = $sth->errorInfo();
                $return['error'] = $error[2];
                return $return;
            }
            foreach ($image_list as $value) {
                $sql              = 'INSERT INTO `docker_image` (image_id,short_id,ip,name_version) VALUES (:image_id,:short_id,:ip,:name_version)';
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
        $sql = 'SELECT * FROM `docker_image`
                WHERE  id = :id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute(array(':id' => $id));
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAll()
    {
        $sql = 'SELECT D.*,B.`name` as instance_name,P.`name` as project_name FROM `docker_image` D LEFT JOIN `gaea_server` B ON D.`ip`=B.`internal_ip` LEFT JOIN `project_server` PS ON PS.`server_id`= B.`id` LEFT JOIN `project` P ON  PS.`project_id`= P.`id`';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute();
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findByIp($ip)
    {
        $sql = 'SELECT * FROM `docker_image`
                WHERE  ip = :ip';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute(array(':ip' => $ip));
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }
    //局域网ip 内网ip
    public function deleteAllByServerId($areaIp)
    {
        $return           = [];
        $sql              = 'DELETE FROM `docker_image` WHERE ip=:ip';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':ip' => $areaIp]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }
}
