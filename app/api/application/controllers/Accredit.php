<?php
/**
 * Accredit Controller
 * 项目权限相关控制器
 *
 */

use DAO\AccreditModel;

class AccreditController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * GET 获取所有用户的项目授权列表
     *
     * @param  根据项目id或者用户id 获取指定项目被授权的用户列表
     *         如果不传参数，则默认查询自己的项目权限
     * @return array
     */
    public function indexAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $model  = new AccreditModel;
        $pid    = $_GET['pid'] ?? null;
        $uid    = $_GET['uid'] ?? null;

        if (empty($pid) && empty($uid)) {
            $uid = $this->getUserId();
        }
        $data   = $model->findAll($pid, $uid);
        if (! empty($data)) {
            foreach ($data as $key => $value) {
                $userModel                  = new \DAO\UserModel;
                $userinfo                   = $userModel->findOne(
                    $value['user_id']
                );
                $data[$key]['user_name']    = empty($userinfo) ? ''
                    : $userinfo['name'];
                $proModel                   = new \DAO\ProjectModel;
                $proinfo                    = $proModel->findOne(
                    $value['project_id']
                );
                $data[$key]['project_name'] = empty($proinfo) ? ''
                    : $proinfo['name'];
            }
        }
        $return['status'] = 200;
        $return['data']   = $data;

        return $this->json = $return;
    }

    /**
     * POST 为指定uid添加执行项目权限
     *
     * @param  uid  指定的用户ID
     * @param  pid  指定的项目ID
     * @return array
     */
    public function createAction()
    {
        $return = ['status' => 400, 'error' => '', 'errorUid' => []];
        $model  = new AccreditModel;

        $create_id = $this->getUserId();

        $pid = $_POST['pid'] ?? null;
        if (empty($pid) || empty($_POST['uid'])) {
            $return['error'] = 'uid和pid不能为空';

            return $this->json = $return;
        }

        $uidArr = json_decode($_POST['uid'], true);
        if (! is_array($uidArr)) {
            $return['error'] = 'uid格式错误';

            return $this->json = $return;
        }

        foreach ($uidArr as $userIn) {
            $uid  = array_keys($userIn)[0];
            $name = $userIn[$uid];
            $data = $model->findOne($pid, $uid);
            if (! empty($data)) {
                $return['errorUid'][] = [
                    'uid'   => $uid,
                    'name'  => $name,
                    'error' => '该用户已经拥有该项目的授权',
                ];
                continue;
            }

            $params = [
                ':user_id'     => $uid,
                ':project_id'  => $pid,
                ':create_user' => $create_id,
            ];

            $status = $model->insertOne($params);

            if (! $status['status']) {
                $return['errorUid'][] = [
                    'uid'   => $uid,
                    'name'  => $name,
                    'error' => $status['error'],
                ];
            }
        }


        $return['status'] = 200;

        return $this->json = $return;
    }

    /**
     * POST  取消指定用户的指定项目权限（暂不支持批量取消）
     *
     * @param  uid    用户ID
     * @param  pid    项目ID
     * @return array
     */
    public function deleteAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $model  = new AccreditModel;

        $create_id = $this->getUserId();

        $pid = $_POST['pid'] ?? null;
        $uid = $_POST['uid'] ?? null;

        if (empty($pid) || empty($uid)) {
            $return['error'] = 'uid和pid不能为空';

            return $this->json = $return;
        }

        $data = $model->findOne($pid, $uid);
        if (empty($data)) {
            $return['error'] = '该用户没有该项目的授权';

            return $this->json = $return;
        }

        $params = [
            ':user_id'    => $uid,
            ':project_id' => $pid,
        ];

        $status = $model->deleteOne($params);

        if (! $status['status']) {
            $return['error'] = $status['error'];

            return $this->json = $return;
        }

        $return['status'] = 200;
        $return['id']     = $data['id'];

        return $this->json = $return;
    }
}
