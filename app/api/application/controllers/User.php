<?php
/**
 * 用户相关控制器
 * 主要功能：
 *           1.用户信息的管理
 *           2.用户信息分组管理
 *
 */

use DAO\UserModel;

class UserController extends BaseController
{
    private $verifyGetPwd      = 'verifycode_getpassword_'; //找回密码验证码redis key
    private $verifyCodeTimeout = 600; //验证码失效时间10分钟

    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    //用户列表 根据类型搜索 type=stutas 返回用户都有哪些状态，没有type则返回所有用户
    public function indexAction()
    {
        $type = $_GET['type'] ?? null;
        if (! empty($type) && $type == 'status') {
            $data = $this->getStatus();
            if (! empty($data)) {
                return $this->json = ['status' => 200, 'data' => $data];
            }

            return $this->json = ['status' => 500, 'errorMsg' => '系统错误'];
        }

        $model = new UserModel();
        $users = $model->findAll();
        if ($users) {
            $usersinfo = array_map(
                function ($user) {
                    $user['login_ip_format'] = long2ip($user['login_ip']);

                    return $this->json = $user;
                },
                $users
            );

            return $this->json = ['status' => 200, 'data' => $usersinfo];
        }

        return $this->json = ['status' => 200, 'data' => ''];
    }

    //获取用户状态类型
    private function getStatus()
    {
        $constants = UserModel::getUserStatus();
        $data      = [];
        if ($constants) {
            foreach ($constants as $k => $v) {
                if (strpos($k, 'LOGIN_STATUS') !== false) {
                    $data[$k] = $v;
                }
            }
        }

        return $data;
    }

    //创建账户 创建成功发送随机密码

    /**
     * 修改账户信息
     * name是唯一，数据库做了限制，代码中不再做限制
     * 能够修改用户信息的只能是root
     * admin用户只能修改密码，在resetpasswd接口
     *
     * @return array
     */
    public function updateAction()
    {
        $uid = $this->getRequest()->getParam('id', null);
        if (! $uid) {
            return $this->json = ['status' => 400 ,'errorMsg' => '参数错误'];
        }
        $input  = file_get_contents('php://input');
        $params = [];
        parse_str($input, $params);
        $name     = $params['name'] ?? null;
        $status   = $params['status'] ?? null;
        $userType = $params['type'] ?? null;
        $model    = new UserModel;
        if (! $name && !$status && !$userType) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }

        $data = $this->getStatus();
        if (! empty($data)) {
            if (! in_array($status, $data)) {
                return $this->json = ['status' => 400, 'errorMsg' => '参数值错误'];
            }
        } else {
            return $this->json = ['status' => 500, 'errorMsg' => '系统错误'];
        }

        if (! $name && ! $userType) {
            $res = $model->updateStatus($uid, $status);
        } elseif (! $name && $userType) {
            $res = $model->updateType($uid, $userType);
        } elseif ($name && ! $userType) {
            $res = $model->updateName($uid, $name, $status);
        } else {
            $res = $model->updateUserInfo($uid, $name, $userType, $status);
        }

        if ($res['status']) {
            JsonWebTokenModel::delJwt($uid);
            return $this->json = [
                'status' => 200,
                'data'   => ['status' => $res['status']],
            ];
        } else {
            return $this->json = ['status' => 400, 'errorMsg' => $res['error']];
        }
    }

    public function resetpasswdAction()
    {
        $uid       = $this->getUserId();
        $newPasswd = $_POST['new_passwd'] ?? null;
        $oldPasswd = $_POST['old_passwd'] ?? null;

        if (! $oldPasswd && ! $newPasswd) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        if (! Tools\FuncModel::isPassword($newPasswd)) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '密码格式 6-16位密码，必须包含数字和字母',
            ];
        }

        $userModel = new UserModel();
        $userInfo  = $userModel->findOne($uid);
        if ($userInfo) {
            if (! password_verify($oldPasswd, $userInfo['password'])) {
                return $this->json = ['status' => 400, 'errorMsg' => '原密码输入错误'];
            }
            if ($userInfo['status'] != UserModel::LOGIN_STATUS_SUCCESS) {
                return $this->json = ['status' => 400, 'errorMsg' => '账户非正常状态'];
            }

            $res = $userModel->updatePasswd(
                $uid,
                password_hash($newPasswd, PASSWORD_DEFAULT)
            );
            if ($res['status']) {
                return $this->json = [
                    'status' => 200,
                    'data'   => ['status' => $res['status']],
                ];
            }

            return $this->json = ['status' => 400, 'errorMsg' => $res['error']];
        }

        return $this->json = ['status' => 400, 'errorMsg' => '数据不存在'];
    }

    public function rootpasswdAction()
    {
        $resetUser = $_POST['uid'] ?? null;
        if (! $resetUser) {
            return ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $model = new UserModel;
        $root  = $this->getUserId();
        $res   = $model->findOne($root);
        if (! $res) {
            return $this->json = ['status' => 400, 'errorMsg' => '好灵异啊，自己不存在'];
        }
        if ($res['status'] <> UserModel::LOGIN_STATUS_SUCCESS) {
            return $this->json = ['status' => 400, 'errorMsg' => '你自己的状态正常'];
        }
        if ($res['type'] <> 'root') {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '你不是root账户，不能重置别人的密码',
            ];
        }
        $userInfo = $model->findOne($resetUser);
        if (! $userInfo) {
            return $this->json = ['status' => 400, 'errorMsg' => '重置账户不存在'];
        }
        if ($userInfo['status'] != UserModel::LOGIN_STATUS_SUCCESS) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '账户非正常状态，请先修改为正常状态',
            ];
        }
        $passwd = Tools\FuncModel::getSmsCode(8);
        //更新密码
        $res = $model->updatePasswd(
            $resetUser,
            password_hash($passwd, PASSWORD_DEFAULT),
            UserModel::LOGIN_STATUS_ERR
        );
        if ($res['status'] === true) {
            JsonWebTokenModel::delJwt($resetUser);
            return $this->json = ['status' => 200, 'code' => $passwd];
        }

        return $this->json = [
            'status'   => 400,
            'errorMsg' => ($res['error'] ?? null),
        ];
    }

    //批量删除或冻结状态控制

    /**
     * 刷新jwt
     *
     * @return [type] [description]
     */
    public function getjwtAction()
    {
        $uid     = $this->getUserId();
        $account = $this->getUserAccount();
        $type = $this->isRoot() ? true : false;
        $jwt     = JsonWebTokenModel::getAccessToken($uid, $account, $type);

        return $this->json = ['status' => 200, 'data' => ['jwt' => $jwt]];
    }

    //重置密码
    //（1）用户自己重置密码 需要手机号码和短信验证码

    private function createAction()
    {
        $name = $_POST['name'] ?? null;
        $type = $_POST['type'] ?? 'admin';

        return $this->json = $this->createItomUser($name, $type);
    }

    //最高管理员 可以为他人重置密码，产生随机密码发送到用户手机，用户用随机密码登录

    private function createItomUser($name, $userType)
    {
        if (! $name || ! $userType) {
            return ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $model = new UserModel;
        //检查账号唯一性 name是唯一
        if ($model->findName($name)) {
            return ['status' => 400, 'errorMsg' => '账户已存在'];
        }
        //发送随机密码
        $passwd  = Tools\FuncModel::getSmsCode(8);
        $message = 'ITOM系统为您创建账户为'.$name.',随机PWD为'.$passwd.',请登录第一时间修改密码！';
        //创建账户
        $uid = $model->insertOne(
            password_hash($passwd, PASSWORD_DEFAULT),
            $name,
            $userType
        );
        if ($uid['status'] === true && $uid['uid'] > 0) {
            return [
                'status' => 200,
                'data'   => ['uid' => $uid['uid']],
                'code'   => $passwd,
                'msg'    => $message,
            ];
        } else {
            return ['status' => 400, 'errorMsg' => ($uid['error'] ?? null)];
        }

        return ['status' => 400, 'errorMsg' => '创建账户失败'];
    }

    private function deleteAction()
    {
        $uid = $this->getRequest()->getParam('id', null);

        if (! $uid) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $status = $this->isRoot();
        if (! $status) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '无操作权限,需要root权限',
            ];
        }
        $model = new UserModel;
        $res   = $model->deleteOne($uid);
        if ($res['status']) {
            JsonWebTokenModel::delJwt($uid);
            return $this->json = [
                'status' => 200,
                'data'   => ['status' => $res['status']],
            ];
        }

        return $this->json = ['status' => 400, 'errorMsg' => $res['error']];
    }

    public function loginOutAction()
    {
        $return = ['status' => 200 , 'data' => 'done'];
        $uid = $this->getUserId();
        //删除jwt 删除websocket
        $del = JsonWebTokenModel::delJwt($uid);
        if (!$del) {
            $return = ['status' => 400 ,'errorMsg' => '登录退出失败'];
        }
        return $this->json = $return;
    }
}
