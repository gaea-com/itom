<?php
/**
 * OrderOpreateLog Model
 *
 * @author junjie.feng
 * @since  1.0
 */

namespace DAO;

use PDO;
use Yaf\Application;

class OrderOperateLogModel extends \MysqlBase
{
    const TASK_TYPE_ASYNC = 'async';
    const TASK_TYPE_SYNC  = 'sync';

    public function insertOne($data)
    {
        $return = ['status' => false, 'error' => ''];
        empty($data[':result']) ? $data[':result'] = '' : null;
        $sql
            = 'INSERT INTO `order_operate_log` (task_id,task_type,step_no,project_id,project_name,instance_id,instance_name,uid,request,operate,ip,cloud_type,result) VALUES (:task_id ,:task_type,:step_no,:project_id,:project_name,:instance_id,:instance_name,:uid,:request,:operate,:ip,:cloud_type,:result)';

        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        foreach ($data as $key => &$value) {
            if ($key == ':result') {
                $sth->bindParam($key, $value, PDO::PARAM_LOB);
            } else {
                $sth->bindParam($key, $value);
            }
        }
        $return['status'] = $sth->execute();
        if (! $return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        } else {
            $return['id'] = $this->db->lastInsertId();
        }

        return $return;
    }

    public function insertOneNew($paramsArr)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';

            return $return;
        }
        $paramsArr2 = [];
        foreach ($paramsArr as $key => $value) {
            $newKey              = ":".$key;
            $paramsArr2[$newKey] = $value;
        }

        $sql = 'INSERT INTO `order_operate_log` ('.implode(
            ',',
            array_keys($paramsArr)
        ).') VALUES ('.implode(',', array_keys($paramsArr2)).') ';
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        // var_dump($sql,$paramsArr2);die;
        foreach ($paramsArr2 as $key => &$value) {
            if ($key == ':result') {
                $sth->bindParam($key, $value, PDO::PARAM_LOB);
            } else {
                $sth->bindParam($key, $value);
            }
        }
        $serStatus = $sth->execute();
        if (! $serStatus) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];

            return $return;
        }
        $return['status'] = true;
        $return['id']     = $this->db->lastInsertId();

        return $return;
    }

    public function findAllByParams($paramsArr)
    {
        $sql = 'SELECT * FROM `order_operate_log` WHERE id<>0  ';
        $arr = [];
        if (! empty($paramsArr)) {
            foreach ($paramsArr as $key => $value) {
                $sql           .= ' AND '.$key.'=:'.$key.' ';
                $arr[':'.$key] = $value;
            }
        }
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute($arr);
        $red = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $red;
    }

    /**
     * 更新日志
     *
     * @param [type] $uid    [description]
     * @param [type] $ip     [description]
     * @param [type] $status [description]
     *
     * @return [type]          [description]
     */
    public function updateOne($task_id, $ip, $result, $step_no = 1)
    {
        $return     = ['status' => false, 'error' => ''];
        $sql
                    = 'UPDATE `order_operate_log` SET result = :result ,end_at = :end_at  WHERE task_id = :task_id AND ip = :ip AND step_no = :step_no';
        $sth        = $this->db->prepare(
            $sql,
            array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
        );
        $bindParams = [
            ':task_id' => $task_id,
            ':ip'      => $ip,
            ':end_at'  => date('Y-m-d H:i:s'),
            ':result'  => $result,
            ':step_no' => $step_no,
        ];
        foreach ($bindParams as $key => &$value) {
            if ($key == ':result') {
                $sth->bindParam($key, $value, PDO::PARAM_LOB);
            } else {
                $sth->bindParam($key, $value);
            }
        }
        $return['status'] = $sth->execute();
        if (! $return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
        } else {
            $return['status'] = true;
        }

        return $return;
    }

    /**
     * 更新日志2
     *
     * @param [type] $uid    [description]
     * @param [type] $ip     [description]
     * @param [type] $status [description]
     *
     * @return [type]          [description]
     */
    public function updateOneNew($search, $data)
    {
        $searchStr = ' WHERE ';
        foreach ($search as $k => $v) {
            $searchStr .= $k.'='.$v.' AND ';
        }
        $where = trim($searchStr, ' AND ');
        if (! empty($data[':instance_id'])) {
            $sql
                = 'UPDATE `order_operate_log` SET result = :result ,instance_id= :instance_id ,end_at = :end_at '
                .$where;
        } else {
            $sql
                = 'UPDATE `order_operate_log` SET result = :result,end_at = :end_at '
                .$where;
        }
        $return          = ['status' => false, 'error' => ''];
        $sth             = $this->db->prepare(
            $sql,
            array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
        );
        $data[':end_at'] = date('Y-m-d H:i:s');
        foreach ($data as $key => &$value) {
            if ($key == ':result') {
                $sth->bindParam($key, $value, PDO::PARAM_LOB);
            } else {
                $sth->bindParam($key, $value);
            }
        }
        $return['status'] = $sth->execute();
        if (! $return['status']) {
            $error           = $sth->errorInfo();
            $return['error'] = $error[2];
            $return['sql']   = $sql;
        } else {
            $return['status'] = true;
        }

        return $return;
    }

    public function findOne($id)
    {
        $sql = 'SELECT * FROM `order_operate_log`
                WHERE  id = :id';
        $sth = $this->db->prepare(
            $sql,
            array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
        );
        $sth->execute(array(':id' => $id));
        $red = $sth->fetch(PDO::FETCH_ASSOC);

        return $red;
    }

    public function findAll(
        $uid = null,
        $project_id = null,
        $instance_id = null,
        $type = null,
        $page = 1,
        $count = 10,
        $start_date = null,
        $end_date = null,
        $isRoot
    ) {
        $sql
                 = 'SELECT L.*,U.`name` as user_name FROM `order_operate_log` L LEFT JOIN `user` U ON L.`uid`=U.`id` WHERE L.`id`>0';
        $sql2    = 'SELECT COUNT(*) FROM `order_operate_log` L WHERE L.`id`>0 ';
        $offset  = ($page - 1) * $count;
        $execute = [];
        if ($uid && $isRoot === false) {
            $sql             .= ' AND L.`uid` = :uid';
            $sql2            .= ' AND L.`uid` = :uid';
            $execute[':uid'] = $uid;
        }

        if ($instance_id) {
            $sql                     .= ' AND L.`instance_id` = :instance_id';
            $sql2                    .= ' AND L.`instance_id` = :instance_id';
            $execute[':instance_id'] = $instance_id;
        }

        if ($start_date) {
            $sql                    .= ' AND L.`create_at` >= :start_date';
            $sql2                   .= ' AND L.`create_at` >= :start_date';
            $execute[':start_date'] = $start_date;
        }
        if ($end_date) {
            $sql                  .= ' AND L.`create_at` <= :end_date';
            $sql2                 .= ' AND L.`create_at` <= :end_date';
            $execute[':end_date'] = $end_date;
        }

        if ($project_id) {
            $sql                    .= ' AND L.`project_id` = :project_id';
            $sql2                   .= ' AND L.`project_id` = :project_id';
            $execute[':project_id'] = $project_id;
        } else {
            $acctModel = new AccreditModel();
            if (! $isRoot) {
                $accPidArr = $acctModel->getUserProject($uid);
                if (! empty($accPidArr)) {
                    $sql  .= ' AND find_in_set(cast(L.project_id as char) , :project_id)';
                    $sql2 .= ' AND find_in_set(cast(L.project_id as char) , :project_id)';
                    $execute[':project_id'] = $accPidArr;
                }
            }
        }

        if ($type == 'task') {
            $sql .= ' GROUP BY L.`task_id` ';
            $sql2 .= ' GROUP BY L.`task_id ';
        }

        $sql .= " ORDER BY L.`create_at` DESC LIMIT $offset,$count";
        $sth = $this->db->prepare(
            $sql,
            array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
        );
        $sth->execute($execute);
        //$sth->debugDumpParams();
        $red  = $sth->fetchAll(PDO::FETCH_ASSOC);
        $sth2 = $this->db->prepare(
            $sql2,
            array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
        );
        $sth2->execute($execute);
        if ($type == 'task') {
            $res = $sth2->fetchAll(PDO::FETCH_ASSOC);
            $totalCount = count($res);
        } else {
            $totalCount = $sth2->fetchColumn();
        }
        return ['pageData' => $red, 'totalCount' => $totalCount];
    }
}
