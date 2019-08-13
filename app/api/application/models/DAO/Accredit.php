<?php

namespace DAO;

use JsonWebTokenModel;

class AccreditModel extends \MysqlBase
{
    public function findAll($pid = null, $uid = null)
    {
        $params = [];
        if (! empty($pid)) {
            $params[':project_id'] = $pid;
        }
        if (! empty($uid)) {
            $params[':user_id'] = $uid;
        }

        $sql = 'SELECT * FROM `accredit` WHERE create_at<>0  ';
        $sql .= empty($pid) ? '' : ' AND project_id=:project_id ';
        $sql .= empty($uid) ? '' : ' AND user_id=:user_id ';
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute($params);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function findOne($pid, $uid)
    {
        $params = [
            ':project_id' => $pid,
            ':user_id'    => $uid,
        ];

        $sql
             = 'SELECT * FROM `accredit` WHERE project_id=:project_id  AND user_id=:user_id  ';
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute($params);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);

        return $red;
    }

    public function insertOne($data)
    {
        $return = ['status' => false, 'error' => ''];

        $sql
             = 'INSERT INTO `accredit` (user_id,project_id,create_user) VALUES (:user_id,:project_id,:create_user)';
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );

        $status = $sth->execute($data);

        if (! $status) {
            $return['error'] = $sth->errorInfo();

            return $return;
        }

        $return['status'] = true;
        $return['id']     = $this->db->lastInsertId();

        return $return;
    }

    public function deleteOne($data)
    {
        $return = ['status' => false, 'error' => ''];

        $sql
                = 'DELETE FROM `accredit` WHERE project_id=:project_id  AND user_id=:user_id ';
        $sth    = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $status = $sth->execute($data);

        if (! $status) {
            $return['error'] = $sth->errorInfo();

            return $return;
        }

        $return['status'] = true;

        return $return;
    }

    // 获取某个用户被授权的项目
    public function getUserProject($uid)
    {
        $params = [];
        if (! empty($uid)) {
            $params[':user_id'] = $uid;
        }

        $sql = 'SELECT * FROM `accredit` WHERE create_at<>0  ';
        $sql .= empty($uid) ? '' : ' AND user_id=:user_id ';
        $sth = $this->db->prepare(
            $sql,
            [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]
        );
        $sth->execute($params);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $return = [];
        if (! empty($red)) {
            foreach ($red as $val) {
                $return[] = $val['project_id'];
            }
        }

        return $return;
    }

    public function checkPermission($uid, $projectId)
    {
        $sql = 'SELECT count(*) FROM `accredit` WHERE user_id=:uid AND project_id=:projectId';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute([':uid'=> $uid, ':projectId' => $projectId]);
        $red = $sth->fetchColumn();
        return $red;
    }
}
