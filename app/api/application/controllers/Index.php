<?php
/**
 * 登录/注册/找回密码控制器
 */

use DAO\UserModel;

class IndexController extends Yaf\Controller_Abstract
{
    private $json = []; //输出信息

    /**
     * 构造函数，整个controller中所有的action都会加载
     * 本方法要求禁用模板，所有请求必须是POST
     */
    public function init()
    {
        //除验证码请求外，所有请求均必须是POST请求
        $method = $this->getRequest()->getMethod();
        if ($this->getRequest()->getActionName() <> 'image') {
            if ($method != 'POST') {
                echo json_encode(['status' => 400, 'error' => '非法请求']);
                exit;
            }
        }
    }

    public function __destruct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE");
        header("Access-Control-Max-Age: 3600");
        header(
            "Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization, X-CSRF-TOKEN"
        );

        if (! empty($this->json)) {
            $this->getResponse()->setBody(json_encode($this->json));
        }
    }

    /**
     * 登录认证
     * 认证成功返回jwt和用户信息
     * 业务逻辑：
     * 1.自有账号登录：
     * 2.登录连续错误5次锁定账号
     *
     * @return json
     */
    public function loginAction()
    {
        if (empty($_POST['name']) || empty($_POST['passwd'])) {
            return $this->json = ['status' => 400, 'errorMsg' => '用户名或密码不能为空'];
        }
        if (empty($_POST['smscode'])) {
            return $this->json = ['status' => 400, 'errorMsg' => '验证码不能为空'];
        }

        $user     = new UserModel();
        $userInfo = $user->findName($_POST['name']);
        if ($userInfo) {
            switch ($userInfo['status']) {
            case UserModel::LOGIN_STATUS_SUCCESS:
                $image       = new Securimage\Securimage();
                $checkStatus = $image->check($_POST['smscode']);
                if ($checkStatus != true) {
                    return $this->json = [
                        'status'   => 400,
                        'errorMsg' => '验证码错误或过期，请刷新后重新输入',
                    ];
                }
                if (password_verify(
                    urldecode($_POST['passwd']),
                    $userInfo['password']
                )
                ) {
                    //保存登录IP和登录时间,清除登录错误次数
                    $ip = $this->getIp();
                    try {
                        $status = $user->updateSucessLogin(
                            $userInfo['id'],
                            $ip
                        );
                        if (! $status) {
                            throw new Exception("登录更新失败", 10001);
                        }
                        unset($userInfo['password'], $userInfo['login_err']);
                        $type = $userInfo['type'] == 'root' ? true : false;
                        $jwt = JsonWebTokenModel::getAccessToken(
                            $userInfo['id'],
                            $userInfo['name'],
                            $type
                        );

                        return $this->json = [
                            'status' => 200,
                            'data'   => $userInfo,
                            'jwt'    => $jwt,
                        ];
                    } catch (Exception $e) {
                        return $this->json = [
                            'status'   => 500,
                            'errorMsg' => '系统错误:'.$e->getMessage(),
                        ];
                    }
                } else {
                    //登录失败
                    try {
                        $loginErr = $userInfo['login_err'] + 1;
                        if ($loginErr >= UserModel::LOGIN_ERR_TIME) {
                            $status = $user->updateErrorLogin(
                                $userInfo['id'],
                                $loginErr,
                                UserModel::LOGIN_STATUS_ERR
                            );
                        } else {
                            $status = $user->updateErrorLogin(
                                $userInfo['id'],
                                $loginErr
                            );
                        }
                        if (! $status['status']) {
                            throw new Exception($status['error'], 10006);
                        }

                        //TODO::记录每次登录错误的时间和IP
                        return $this->json = [
                            'status'   => 400,
                            'errorMsg' => '密码错误',
                        ];
                    } catch (Exception $e) {
                        //TODO::报警
                        return $this->json = [
                            'status'   => 500,
                            'errorMsg' => '系统错误:'.$e->getMessage(),
                        ];
                    }
                }
                // no break
            case UserModel::LOGIN_STATUS_EXCEPTION:
                return $this->json = [
                        'status'   => 400,
                        'errorMsg' => '账户异常,已被冻结',
                    ];
            case UserModel::LOGIN_STATUS_ERR:
                return $this->json = [
                        'status'   => 400,
                        'errorMsg' => '登录失败次数过多,已被锁定',
                    ];
            default:
                return $this->json = [
                        'status'   => 400,
                        'errorMsg' => '未知状态',
                    ];
            }
        } else {
            return $this->json = ['status' => 400, 'errorMsg' => '账户不存在'];
        }
    }

    public function getIp()
    {
        $ip      = 'unknown';
        $unknown = 'unknown';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && $_SERVER['HTTP_X_FORWARDED_FOR']
            && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)
        ) {
            // 使用透明代理、欺骗性代理的情况
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']
            && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)
        ) {
            // 没有代理、使用普通匿名代理和高匿代理的情况
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // 处理多层代理的情况
        if (strpos($ip, ',') !== false) {
            // 输出第一个IP
            $arr = explode(',', $ip);
            $ip  = reset($arr);
        }

        return $ip;
    }

    /**
     * 生成验证码 gif图片
     *
     * @return gif
     */
    public function vcodeAction()
    {
        $type = $_POST['type'] ?? null;
        if (! $type) {
            return $this->json = ['status' => 400, 'error' => '参数错误'];
        }

        if ($type == 'login') {
            return $this->json = [
                'status' => 200,
                'data'   => [
                    'url' => $_SERVER['REQUEST_SCHEME'].'://'
                        .$_SERVER['HTTP_HOST'].'/api/codeimage',
                    'v'   => md5(
                        uniqid(
                            $_SERVER['REMOTE_PORT'],
                            true
                        )
                    ),
                ],
            ];
        }

        return $this->json = ['status' => 400, 'errorMsg' => '未知错误'];
    }

    //不能删除，此为验证码图片地址

    public function imageAction()
    {
        $img = new Securimage\Securimage();
        if (! empty($_GET['namespace'])) {
            $img->setNamespace($_GET['namespace']);
        }

        $img->show();
    }
}
