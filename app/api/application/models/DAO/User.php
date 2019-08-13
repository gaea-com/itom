<?php
/**
 * USER Model
 * 用户详情表 MODEL
 *
 * @author daqian.sun <fxlt.sdq@gmail.com>
 * @since  1.0
 */
namespace DAO;

class UserModel extends \MysqlBase
{
    //最多登录失败次数,达到此次数，需修改用户status为LOGIN_STATS_ERR
    const LOGIN_ERR_TIME         = 5;
    const LOGIN_STATUS_SUCCESS   = 200; //账号状态：正常
    const LOGIN_STATUS_EXCEPTION = 300; //账号状态：异常或封禁或冻结
    const LOGIN_STATUS_ERR       = 400; //账号状态：错误（由登录错误造成）

    //注册一个itom账号
    public function insertOne($passwd, $name, $type='admin')
    {
        $return = ['status', 'uid'];
        $sql    = 'INSERT INTO `user` (password,name,status,type) VALUES (:password,:name,' . self::LOGIN_STATUS_SUCCESS . ',:type)';

        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':password' => $passwd, ':name' => $name, ':type'=>$type]);
        if (!$return['status']) {
            $return['error'] = $sth->errorInfo();
        } else {
            $return['uid'] = $this->db->lastInsertId();
        }
        return $return;
    }

    /**
     * 修改登录成功信息
     *
     * @param  [type] $uid [description]
     * @param  [type] $ip  [description]
     * @return [type]      [description]
     */
    public function updateSucessLogin($uid, $ip)
    {
        $sql = 'UPDATE `user`
                    SET login_err = 0 , login_time = :time , login_ip = :ip
                WHERE id = :uid';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        return $sth->execute([':uid' => $uid, ':time' => date('Y-m-d H:i:s'), ':ip' => ip2long($ip)]);
    }


    public function deleteOne($id)
    {
        $return = ['status' => false, 'error' => ''];
        $sql = 'DELETE FROM `user` WHERE  id=:id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $return['status'] = $sth->execute([':id' => $id]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }

    /**
     * 重置密码
     *
     * @param  [type] $uid       [description]
     * @param  [type] $newPasswd [description]
     * @param  [type] $error     冻结可解冻
     * @return [type]            [description]
     */
    public function updatePasswd($uid, $newPasswd, $error = null)
    {
        $return = ['status' => false, 'error' => ''];
        $sql    = 'UPDATE `user` SET login_err = 0 , password = :passwd';
        if ($error == self::LOGIN_STATUS_ERR) {
            $sql .= ' , status = ' . self::LOGIN_STATUS_SUCCESS;
        }
        $sql .= ' WHERE id = :uid';
        $sth              = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $return['status'] = $sth->execute([':uid' => $uid, ':passwd' => $newPasswd]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }

    /**
     * 修改登录错误信息
     *
     * @param  [type] $uid    [description]
     * @param  [type] $ip     [description]
     * @param  [type] $status [description]
     * @return [type]          [description]
     */
    public function updateErrorLogin($uid, $loginErr, $status = self::LOGIN_STATUS_SUCCESS)
    {
        $return = ['status' => false, 'error' => ''];
        $sql    = 'UPDATE `user`
               SET login_err = :login_err , login_err_at = :err_times ';
        if ($status != self::LOGIN_STATUS_SUCCESS) {
            $sql .= ' ,status = :status';
        }
        $sql .= ' WHERE id = :uid';
        $sth        = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $bindParams = [':uid' => $uid, 'login_err' => $loginErr, ':err_times' => date('Y-m-d H:i:s')];
        if ($status != self::LOGIN_STATUS_SUCCESS) {
            $bindParams[':status'] = $status;
        }
        $return['status'] = $sth->execute($bindParams);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }


    public function findName($name)
    {
        $sql = 'SELECT id,name, password,status, login_err,type
                    FROM `user`
                WHERE  name = :name LIMIT 1';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute(array(':name' => $name));
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findOne($uid)
    {
        $sql = 'SELECT id, name,password,status,type
                    FROM `user`
                WHERE  id = :id';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute(array(':id' => $uid));
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAll()
    {
        $sql = 'SELECT id,name,login_ip,status,reg_time,login_time,type FROM `user`';
        $sth = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute();
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $red;
    }

    public function findAllByIds($idArr)
    {
        $idIn = implode(',', $idArr);
        $sql  = 'SELECT id,name FROM `user` WHERE find_in_set(cast(id as char), :ids)';
        $sth  = $this->db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $sth->execute([':ids' => $idIn]);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateName($id, $name, $status)
    {
        $return           = ['status' => false, 'error' => ''];
        $sql              = 'UPDATE `user` SET name=:name , status=:status WHERE id=:id';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':name' => $name, ':id' => $id, ':status' => $status]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }

    public static function getUserStatus()
    {
        $obClass = new \ReflectionClass(__CLASS__);
        return $obClass->getConstants();
    }

    public function updateStatus($id, $status)
    {
        $return           = ['status' => false, 'error' => ''];
        $sql              = 'UPDATE `user` SET status=:status WHERE id=:id';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':id' => $id, ':status' => $status]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }

    public function updateType($id, $type)
    {
        $return           = ['status' => false, 'error' => ''];
        if ($type <> 'root' && $type <> 'admin') {
            $return['error'] = '用户类型不符';
            return $return;
        }
        $sql              = 'UPDATE `user` SET type=:type WHERE id=:id';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':id' => $id, ':type' => $type]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }

    public function updateUserInfo($id, $name, $type, $status)
    {
        $return           = ['status' => false, 'error' => ''];
        if ($type <> 'root' && $type <> 'admin') {
            $return['error'] = '用户类型不符';
            return $return;
        }
        $sql              = 'UPDATE `user` SET name=:name, type=:type, status=:status WHERE id=:id';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':id' => $id, ':type' => $type,':name'=>$name, ':status'=>$status]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }
    public function updateStatusAll($idArr, $status)
    {
        $return           = ['status' => false, 'error' => ''];
        $idIn             = implode(',', $idArr);
        $sql              = 'UPDATE `user` SET status=:status WHERE find_in_set(cast(id as char), :ids)';
        $sth              = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $return['status'] = $sth->execute([':ids' => $idIn, ':status' => $status]);
        if (!$return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        }
        return $return;
    }
}
