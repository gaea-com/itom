<?php

/**
 * Class HubController
 * 镜像仓库相关控制器
 *
 * 目前需求是一个系统只有一个镜像仓库
 * 相关操作：
 *      1.创建镜像仓库地址 同时输入账号和密码
 *      2.测试镜像仓库和密码
 *      3.测试成功可以保存
 *      4.一旦保存成功则不能修改
 *      5.拉取镜像必须从仓库拉取
 *      6.获取镜像列表
 *      7.获取仓库列表
 * 仓库和账号信息保存在Redis中   hash_key:hubImageInfo
 *                          hub_url:
 *                          hub_account:
 *                          hub_password:
 */
class HubController extends BaseController
{
    const  KEY = 'hubImageInfo';
    private $redis;

    public function init()
    {
        parent::init();
        $this->redis = new Tools\RedisModel();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    //返回镜像仓库相关信息
    public function indexAction()
    {
        $url      = $this->redis->redis->hget(self::KEY, 'hub_url');
        $account  = $this->redis->redis->hget(self::KEY, 'hub_account');
        $password = $this->redis->redis->hget(self::KEY, 'hub_password');

        if ($url) {
            return $this->json = [
                'status' => 200,
                'data'   => [
                    'url'      => urldecode(base64_decode($url)),
                    'user'     => $account,
                    'password' => $password,
                ],
            ];
        }

        return $this->json = ['status' => 400, 'error' => '未获取数据信息'];
    }

    //创建镜像仓库信息相关内容
    public function createAction()
    {
        $url      = $_POST['hub_url'] ??
            null; //必须传入的是先urlencode，然后在base64encode的
        $account  = $_POST['hub_user'] ?? null;
        $password = $_POST['hub_password'] ?? null;
        if (! $url || ! $account || ! $password) {
            return $this->json = ['status' => 400, 'error' => '未获取到镜像仓库相关信息'];
        }
        $hubUrl = $this->redis->redis->hget(self::KEY, 'hub_url');
        if ($hubUrl) {
            return $this->json = ['status' => 400, 'errorMsg' => '仓库信息已存在'];
        }
        $resUrl     = $this->redis->redis->hSet(self::KEY, 'hub_url', $url);
        $resAccount = $this->redis->redis->hSet(
            self::KEY,
            'hub_account',
            $account
        );
        $resPasswd  = $this->redis->redis->hSet(
            self::KEY,
            'hub_password',
            $password
        );
        if ($resUrl && $resAccount && $resPasswd) {
            return $this->json = ['status' => 200];
        }

        return $this->json = ['status' => 400, 'error' => '保存失败,不能修改仓库配置'];
    }

    public function checkAction()
    {
        $url = $this->redis->redis->hget(self::KEY, 'hub_url');
        if ($url) {
            return $this->json = ['status' => 400, 'errorMsg' => '仓库信息已存在'];
        }

        return $this->json = ['status' => 200, 'msg' => '可以创建仓库信息'];
    }

    //测试请求
    public function connectAction()
    {
        //        $url      = 'https://harbor.gaeamobile-inc.net/v2/_catalog?n=100&last=common>';
        //        $account  = 'admin';
        //        $password = 'Harbor#gaea@1';
        $url = ! empty($_POST['hub_url']) ? urldecode(
            base64_decode($_POST['hub_url'])
        ) : null;
        if (! $url) {
            return $this->json = ['status' => 400, 'errorMsg' => '未获取到仓库URL'];
        }
        $url      .= '/v2/_catalog?n=5';
        $account  = $_POST['hub_user'] ?? null;
        $password = $_POST['hub_password'] ?? null;

        return $this->connectDockerRegistry($url, $account, $password);
    }

    private function connectDockerRegistry(
        $url,
        $account,
        $password,
        $type = "GET",
        $accept = false
    ) {
        if (! $url || ! $account || ! $password) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '未获取到镜像仓库相关信息',
            ];
        }
        $auth   = base64_encode($account.':'.$password);
        $header = ['Authorization: Basic '.$auth];
        if ($accept) {
            $header[] = 'Accept: application/vnd.docker.distribution.manifest.v2+json';
        }
        $arr    = Tools\FuncModel::curl($url, $type, null, $header);
        if (isset($arr['http'])) {
            if ($arr['http'] == 200) {
                if (isset($arr['repositories'])) {
                    return $this->json = [
                        'status'   => 200,
                        'data'     => $arr['repositories'],
                        'httpCode' => $arr['http'],
                    ];
                }

                return $this->json = [
                    'status'   => 200,
                    'data'     => $arr,
                    'httpCode' => $arr['http'],
                ];
            }
            if ($arr['http'] == 401) {
                return $this->json = [
                    'status'   => 200,
                    'errorMsg' => $arr['errors'][0]['code'].':'
                        .$arr['errors'][0]['message'],
                    'httpCode' => $arr['http'],
                ];
            }
            if ($arr['http'] == 202) {
                return $this->json = [
                    'status'   => 200,
                    'httpCode' => $arr['http'],
                ];
            }

            if ($arr['http'] == 404) {
                return $this->json = [
                    'status'   => 200,
                    'httpCode' => $arr['http'],
                    'msg'      => '已删除或资源不存在',
                ];
            }
        }

        if (! isset($arr['http']) && isset($arr['status'])) {
            return $this->json = [
                'status'   => 200,
                'httpCode' => $arr['status'],
                'msg' => '操作成功'
            ];
        }

        return $this->json = [
            'status'   => 400,
            'errorMsg' => '无法访问镜像仓库URL或者请求超时',
        ];
    }

    /**
     * 获取镜像列表
     * 可以传入两个参数，一个参数是n，表示列表显示条数
     * 一个参数是last，表示从镜像名称中那个字母或者那个镜像名开始显示
     */
    public function getListAction()
    {
        $num     = $_GET['num'] ?? 20;
        $last    = $_GET['img_name'] ?? null;
        $include = $_GET['cover'] ?? false;
        $url     = $this->redis->redis->hget(self::KEY, 'hub_url');
        $url     = urldecode(base64_decode($url));
        $url     .= '/v2/_catalog?n='.$num;

        if ($last) {
            $url .= '&last='.$last;
            if (! $include) {
                $url .= '>';
            }
        }
        $account  = $this->redis->redis->hget(self::KEY, 'hub_account');
        $password = $this->redis->redis->hget(self::KEY, 'hub_password');

        return $this->connectDockerRegistry($url, $account, $password);
    }

    public function getTagsAction()
    {
        $img = $_GET['name'] ?? null;
        if (! $img) {
            return $this->json = ['status' => 400, 'errorMsg' => '缺少镜像名称'];
        }

        $res = strpos($img, '\\');

        if ($res !== false) {
            return $this->json = ['status' => 400, 'errorMsg' => '镜像名中有非法符号'];
        }

        $url      = $this->redis->redis->hget(self::KEY, 'hub_url');
        $url      = urldecode(base64_decode($url));
        $url      .= '/v2/'.$img.'/tags/list';
        $account  = $this->redis->redis->hget(self::KEY, 'hub_account');
        $password = $this->redis->redis->hget(self::KEY, 'hub_password');

        return $this->connectDockerRegistry($url, $account, $password);
    }

    public function updateAction()
    {
        header('HTTP/1.0 404 not found');
        exit;
    }

    //删除仓库中的镜像
    public function deleteAction()
    {
        $json = file_get_contents("php://input");
        $arr  = json_decode($json, true);

        if (! isset($arr['name']) || ! isset($arr['tag'])) {
            return $this->json = ['status' => 400, 'errorMsg' => '缺少必要参数'];
        }
        $url      = $this->redis->redis->hget(self::KEY, 'hub_url');
        $url      = urldecode(base64_decode($url));
        $tagUrl   = $url.'/v2/'.$arr['name'].'/tags/list';
        $account  = $this->redis->redis->hget(self::KEY, 'hub_account');
        $password = $this->redis->redis->hget(self::KEY, 'hub_password');

        $this->connectDockerRegistry($tagUrl, $account, $password);

        if ($this->json['status'] <> 200 || $this->json['httpCode'] <> 200) {
            return $this->json;
        }

        if (! empty($this->json['data']['tags'])
            && in_array(
                $arr['tag'],
                $this->json['data']['tags']
            )
        ) {
            $imgUrl = $url.'/v2/'.$arr['name'].'/manifests/'.$arr['tag'];
            $this->connectDockerRegistry($imgUrl, $account, $password, null, true);
            if ($this->json['status'] <> 200) {
                return $this->json;
            }
            if (! empty($this->json['data']['header'])) {
                $dockerHeaderTag = 'Docker-Content-Digest: ';
                $tagNum          = strpos(
                    $this->json['data']['header'],
                    $dockerHeaderTag
                );
                if ($tagNum !== false) {
                    $len    = 64 + strlen('sha256:');
                    $sublen = $tagNum + strlen($dockerHeaderTag);
                    $digest = substr(
                        $this->json['data']['header'],
                        $sublen,
                        $len
                    );

                    $delUrl = $url.'/v2/'.$arr['name'].'/manifests/'
                        .$digest;

                    return $this->connectDockerRegistry(
                        $delUrl,
                        $account,
                        $password,
                        'DELETE',
                        true
                    );
                }
            }

            return $this->json = [
                'status'   => 400,
                'errorMsg' => '无法获取'.$arr['name'].':'.$arr['tag']
                    .'的digest，删除镜像失败',
            ];
        } else {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '未查询到镜像标签，或者要删除的镜像tag不在标签列表中',
            ];
        }
    }
}
