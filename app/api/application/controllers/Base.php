<?php

/**
 * Controller基础类，所有已登陆账号请求的Controller都需要调用此类
 *
 */
class BaseController extends Yaf\Controller_Abstract
{
    protected $json = []; //输出信息
    private $jwt;

    public function init()
    {
        // 关闭模板渲染
        Yaf\Dispatcher::getInstance()->autoRender(false);
        //跨域
        if ($this->getRequest()->getMethod() == "OPTIONS") {
            header("HTTP/1.0 200 OK");
            exit;
        }
        // Json Web Token Validate
        $this->jwt = JsonWebTokenModel::validateJWT();
        $status    = $this->permissionCheck();
        if (! $status) {
            echo json_encode(['status' => 400, 'errorMsg' => '无权限操作']);
            exit;
        }
    }

    public function permissionCheck()
    {
        if ($this->isRoot()) {
            return true;
        }
        //root permission arr
        //等同rest中的设置 controller为KEY，且首字母大写，action必须全部小写  遵循 yaf route设定
        $permission = [
            'User'     => ['create', 'update', 'delete', 'rootpasswd'],
            'Hub'      => [
                'index',
                'create',
                'check',
                'connect',
                'getist',
                'gettags',
                'delete',
            ],
            'Accredit' => ['index', 'create', 'delete'],
        ];
        $controller = $this->getRequest()->getControllerName();
        $action     = $this->getRequest()->getActionName();

        if (isset($permission[$controller])
            && in_array(
                $action,
                $permission[$controller]
            )
        ) {
            return false;
        }

        if ($controller == 'Project') {
            return true;
        }
        if ($controller == 'user' && $action == 'loginout') {
            return true;
        }
        if ($controller == 'docker' && $action =='operatelog') {
            return true;
        }

        return $this->isProject();
    }

    public function isRoot($uid = null)
    {
        ! empty($uid) ?: $uid = $this->getUserId();
        $model = new DAO\UserModel();
        $info  = $model->findOne($uid);

        return empty($info) ? false : $info['type'] == 'root' ? true : false;
    }

    public function getUserId()
    {
        return $this->jwt->getClaim('uid');
    }

    //获取用户权限角色信息（弃用）

    public function isProject()
    {
        $uid     = $this->getUserId();
        $headers = Tools\FuncModel::getHeaders();
        if (!empty($headers['Access-Control-Allow-Project'])) {
            $projectId = $headers['Access-Control-Allow-Project'];
            if (! is_numeric($projectId)) {
                return false;
            }
            $model = new DAO\AccreditModel();
            $res   = $model->checkPermission($uid, $projectId);

            return empty($res) ? false : true;
        }

        return false;
    }

    //检查当前用户是否是root用户

    public function __destruct()
    {
        //允许跨域
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header(
            "Access-Control-Allow-Methods: POST, GET, OPTIONS,PUT,PATCH, DELETE"
        );
        header("Access-Control-Max-Age: 3600");
        header(
            "Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization, X-CSRF-TOKEN"
        );
        //$action = $this->getRequest()->getActionName();
        if (! empty($this->json)) {
            $this->getResponse()->setBody(json_encode($this->json));
        }
    }

    //检查用户controller

    public function getUserAccount()
    {
        return $this->jwt->getClaim('acc');
    }
}
