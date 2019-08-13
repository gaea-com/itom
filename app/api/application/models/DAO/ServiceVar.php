<?php
/**
 * ServiceVar Model
 * 查询变量具体信息的MODEL
 *
 * @author junjie.feng
 * @since  1.0
 */
namespace DAO;

class ServiceVarModel extends \MysqlBase
{
    //获得所有实例的count值
    public function getInstanceCount($params)
    {
        $type = $params['type']??'bcc';
        $project_id = $params['project_id']??null;
        $group_id = $params['group_id']??null;
        $server = $type.'_server';
        $sql = 'SELECT B.`id` FROM `'.$server.'` B  LEFT JOIN `project_server` PS ON PS.`server_id`=B.`id` WHERE PS.`type`=:type AND PS.`status` < '.ProjectServerModel::STATUS_DELETE;
        $execute = [':type'=>$type];
        if ($project_id) {
            $sql.=" AND PS.`project_id`=:project_id";
            $execute[':project_id'] = $project_id;
        }
        if ($group_id||$group_id===0) {
            $sql.=" AND PS.`group_id`=:group_id";
            $execute[':group_id'] = $group_id;
        }
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute($execute);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return count($red);
    }
    //获得所有容器的count值
    public function getContainerCount($params)
    {
        $type = $params['type']??'bcc';
        $project_id = $params['project_id']??null;
        $group_id = $params['group_id']??null;
        $sql = 'SELECT D.`id`,D.`container_id` FROM `project_docker` D LEFT JOIN `project_server` PS ON D.`instance_id`=PS.`server_id` AND D.`project_id`=PS.`project_id` AND D.`cloud_type`=PS.`type` AND PS.`type`=:type AND PS.`status` < '.ProjectServerModel::STATUS_DELETE.' AND D.`status`='.ProjectDockerModel::STATUS_OPEN;
        $execute = [':type'=>$type];
        if ($project_id) {
            $sql .= ' AND D.`project_id` = :project_id';
            $execute[':project_id'] = $project_id;
        }
        if ($group_id||$group_id===0) {
            $sql.=" AND PS.`group_id`=:group_id";
            $execute[':group_id'] = $group_id;
        }
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute($execute);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return count($red);
    }
    //获得指定实例的名称
    public function getInstanceNameById($params)
    {
        $instance_name = $params['instance_name']??null;
        if ($instance_name) {
            return $instance_name;
        }
        $type = $params['type'] ?? 'bcc';
        $id   = $params['instance_id'] ?? null;
        if (!$id) {
            throw new Exception("参数不正确");
        }
        $server = $type.'_server';
        $sql = 'SELECT name FROM `'.$server.'` WHERE id=:id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute([':id'=>$id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$red) {
            throw new \Exception("实例不存在");
        }
        return $red['name'];
    }
    //获得指定实例的内网IP
    public function getPrivateIpById($params)
    {
        $ip = $params['internal_ip']??null;
        if ($ip) {
            return $ip;
        }
        $type = $params['type'] ?? 'bcc';
        $id   = $params['instance_id'] ?? null;
        if (!$id) {
            throw new \Exception("参数不正确");
        }
        $server = $type.'_server';
        $sql = 'SELECT internal_ip FROM `'.$server.'` WHERE id=:id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute([':id'=>$id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$red) {
            throw new \Exception("实例不存在");
        }
        return $red['internal_ip'];
    }
    //获得指定实例的公网IP
    public function getPublicIpById($params)
    {
        $public_ip = $params['public_ip']??null;
        if ($public_ip) {
            return $public_ip;
        }
        $type = $params['type'] ?? 'bcc';
        $id   = $params['instance_id'] ?? null;
        if (!$id) {
            throw new \Exception("参数不正确");
        }
        $server = $type.'_server';
        $sql = 'SELECT public_ip FROM `'.$server.'` WHERE id=:id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute([':id'=>$id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$red) {
            throw new Exception("实例不存在");
        }
        return $red['public_ip'];
    }
    //获得指定容器的id
    public function getContainerIdById($params)
    {
        $container_id = $params['container_id']??null;
        if ($container_id) {
            return $container_id;
        }
        $id   = $params['docker_id'] ?? null;
        if (!$id) {
            throw new \Exception("参数不正确");
        }
        $sql = 'SELECT container_id FROM `project_docker` WHERE id=:id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute([':id'=>$id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$red) {
            throw new Exception("容器不存在");
        }
        return $red['container_id'];
    }
    //获得指定容器的hostname
    public function getContainerNameById($params)
    {
        $container_name = $params['container_name']??null;
        if ($container_name) {
            return $container_name;
        }
        $id   = $params['docker_id'] ?? null;
        if (!$id) {
            throw new \Exception("参数不正确");
        }
        $sql = 'SELECT name FROM `project_docker` WHERE id=:id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute([':id'=>$id]);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        if (!$red) {
            throw new \Exception("容器不存在");
        }
        return $red['name'];
    }
}
