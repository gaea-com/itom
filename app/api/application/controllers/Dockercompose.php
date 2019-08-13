<?php
/**
 * DockerCompose Controller
 * 容器编排相关控制器
 *
 */
use DAO\DockerComposeModel;

class DockercomposeController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }
    
    // 容器编排模板列表
    public function indexAction()
    {
        $model = new DockerComposeModel;

        $get_type = $_GET['get_type'] ?? null;
        if (empty($get_type)) {
            $pid = empty($_GET['pid']) ? false : $_GET['pid'];
            $uid = $this->getUserId();
            $isRoot = $this->isRoot($uid);
            $data = $model->findAllForList($pid, $uid, $isRoot);
            $return = ['status' => 200, 'data' => $data];
        } elseif ($get_type == 'compose_list') {
            $pid = $_GET['pid'] ?? null;
            $data = $model->getDockerComposeList($pid);
            $return = ['status' => 200, 'data' => $data];
        } elseif ($get_type == 'docker_compose') {
            if (empty($_GET['id']) || empty($_GET['sid']) || empty($_GET['type'])) {
                return $this->json = ['status' => 400, 'error' => 100005, 'errorMsg' => '参数不能为空'];
            }
            $id = $_GET['id'];
            $sid = $_GET['sid'];
            $type = $_GET['type'];
            $model = new DockerComposeModel;
            $data = $model->getDockerComposeById($id, $sid, $type);
            if (empty($data)) {
                return $this->json = ['status' => 400, 'error' => 100005, 'errorMsg' => '该模板ID数据为空、'];
            } else {
                $return = ['status' => 200, 'data' => $data];
            }
        }
        return $this->json = $return;
    }

    // 添加容器编排模板
    public function createAction()
    {
        $input  = file_get_contents('php://input');
        $params = [];
        parse_str($input, $params);

        if (empty($params['name'])) {
            return $this->json = ['status' => 400, 'error' => 100001, 'errorMsg' => '模板名称不能为空'];
        }

        if (empty($params['description'])) {
            return $this->json = ['status' => 400, 'error' => 100002, 'errorMsg' => '模板描述不能为空'];
        }

        if (empty($params['project_id'])) {
            return $this->json = ['status' => 400, 'error' => 100003, 'errorMsg' => '模板所属项目不能为空'];
        }

        if (empty($params['image_name'])) {
            return $this->json = ['status' => 400, 'error' => 100004, 'errorMsg' => '模板镜像名称不能为空'];
        }

        $imageNameArr = json_decode($params['image_name'], true);
        if (!is_array($imageNameArr)) {
            return $this->json = ['status' => 400, 'error' => 100005, 'errorMsg' => '模板镜像名称格式错误'];
        }

        $imageNameDataArr = [];
        $imageTimesStr = [];
        $num = 1;
        $sleep = 0;
        foreach ($imageNameArr as $key => $imageData) {
            if (!is_array($imageData)) {
                return $this->json = ['status' => 400, 'error' => 100005, 'errorMsg' => '模板镜像名称格式错误'];
            }
            if (!$this->checkImageName($imageData['image_name'])) {
                return $this->json = ['status' => 400 , 'errorMsg' => '镜像名称格式错误，需要name:tag方式，tag不能为空'];
            }
            $imageNameDataArr[] = $imageData['image_name'];
            $imageTimesStr[] = [
                'tid'        => (empty($imageData['tid'])||strlen($imageData['tid'])!=32)?md5($imageData['image_name'].$key.time().rand(1, 1000)):$imageData['tid'],
                'sid'        => $num,
                'image_name' => $imageData['image_name'],
                'sleep_time' => $imageData['sleep_time'],
            ];
            if (!is_numeric($imageData['sleep_time']) || $imageData['sleep_time'] < 0) {
                return $this->json = ['status' => 400 , 'errorMsg' => '暂定秒数不可以小于0,且必须是数字'];
            }
            $sleep += $imageData['sleep_time'];
            $num++;
        }
        if ($sleep > 800) {
            return $this->json = ['status' => 400 , 'errorMsg' => '设置的暂停间隔秒数过大，超过了设定范围，请累计小于800秒'];
        }
        $imageNameStr = json_encode($imageNameDataArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $imageTimesStr = json_encode($imageTimesStr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $data = [
            ':name' => $params['name'],
            ':description' => $params['description'],
            ':project_id' => $params['project_id'],
            // ':image_name' => json_encode(array_values($imageNameArr), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':image_name' => $imageNameStr,
            ':image_times' => $imageTimesStr,
        ];
        
        $model = new DockerComposeModel;
        $status = $model->insertOne($data);

        if ($status['status']) {
            return $this->json = ['status' => 200, 'id' => $status['id']];
        } else {
            return $this->json = ['status' => 400, 'errorMsg' => $status['error']];
        }

        return $this->json = $status;
    }


    /**
     * 编辑编排模板
     * 编辑的时候需要查询原来保存的image_name，对比变更的内容，然后找出：
     * 1.在server_env中使用此编排模板的所有服务器，如果存在非新内容的image_name则删除
     *   同时删除server_image_env中的保存的变量值
     * TODO::注：根据以上可以优化掉server_image_env表，数据结构为json保存到server_env的新字段即可
     * 2.如果是新增的，则用户应该主动去实例里配置环境变量 给用户此提示
     *
     * @return array
     */
    public function updateAction()
    {
        $input  = file_get_contents('php://input');
        $params = [];
        parse_str($input, $params);

        $id = $this->getRequest()->getParam('id', null);

        if (!$id) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $type = $params['type'] ?? null;
        if (!empty($type)) {
            switch ($type) {
            case 'disable':
                return $this->json = $this->disable($id);
                    break;

            case 'enabled':
                return $this->json = $this->enabled($id);
                    break;

            default:
                header('HTTP/1.1 403 Forbidden');
                exit;
            }
        }

        if (empty($id)) {
            return $this->json = ['status' => 400, 'error' => 100005, 'errorMsg' => '模板ID不能为空'];
        }

        if (empty($params['name'])) {
            return $this->json = ['status' => 400, 'error' => 100001, 'errorMsg' => '模板名称不能为空'];
        }

        if (empty($params['description'])) {
            return $this->json = ['status' => 400, 'error' => 100002, 'errorMsg' => '模板描述不能为空'];
        }

        if (empty($params['project_id'])) {
            return $this->json = ['status' => 400, 'error' => 100003, 'errorMsg' => '模板所属项目不能为空'];
        }

        if (empty($params['image_name'])) {
            return $this->json = ['status' => 400, 'error' => 100004, 'errorMsg' => '模板镜像名称不能为空'];
        }

        $imageNameArr = json_decode($params['image_name'], true);
        if (!is_array($imageNameArr)) {
            return $this->json = ['status' => 400, 'error' => 100005, 'errorMsg' => '模板镜像名称格式错误'];
        }

        $imageNameDataArr = [];
        $imageTimesStr = [];
        $num = 1;
        $sleep = 0 ;
        foreach ($imageNameArr as $key => $imageData) {
            if (!is_array($imageData)) {
                return $this->json = ['status' => 400, 'error' => 100005, 'errorMsg' => '模板镜像名称格式错误'];
            }
            if (!$this->checkImageName($imageData['image_name'])) {
                return $this->json = ['status' => 400 , 'errorMsg' => '镜像名称格式错误，需要name:tag方式，tag不能为空'];
            }
            $imageNameDataArr[] = $imageData['image_name'];
            $imageTimesStr[] = [
                'tid' => empty($imageData['tid'])?md5($imageData['image_name'].$key.time().rand(1, 1000)):$imageData['tid'],
                'sid' => $num,
                'image_name' => $imageData['image_name'],
                'sleep_time' => $imageData['sleep_time'],
            ];
            if (!is_numeric($imageData['sleep_time']) || $imageData['sleep_time'] < 0) {
                return $this->json = ['status' => 400 , 'errorMsg' => '暂定秒数不可以小于0,且必须是数字'];
            }
            $sleep += $imageData['sleep_time'];
            $num++;
        }
        if ($sleep > 800) {
            return $this->json = ['status' => 400 , 'errorMsg' => '设置的暂停间隔秒数过大，超过了设定范围，请累计小于800秒'];
        }
        $imageNameStr = json_encode($imageNameDataArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $imageTimesStr = json_encode($imageTimesStr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $data = [
            ':name' => $params['name'],
            ':description' => $params['description'],
            // ':project_id' => $params['project_id'],
            // ':image_name' => json_encode(array_values($imageNameArr), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':image_name' => $imageNameStr,
            ':image_times' => $imageTimesStr,
        ];
        
        $model = new DockerComposeModel;

        $info = $model->findOne($id);
        if (!$info) {
            return $this->json = ['status' => 400 , 'errorMsg' => '此编排模板不存在或者禁用状态，请解除禁用状态'];
        }
        //检查差异，执行注释1
        $infoImages = json_decode($info['image_name'], true);
        $newDiff = array_diff($imageNameDataArr, $infoImages);
        $delDiff = array_diff($infoImages, $imageNameDataArr);
        $msg = '';

        if (!empty($newDiff)) {
            $msg = '有新增镜像，启动容器前请配置环境变量';
        }
        if (!empty($delDiff)) {
            $serverModel = new DAO\ProjectServerModel();
            $serverRes = $serverModel->findAllByComposeId($id);
            if (!empty($serverRes)) {
                $envModel = new DAO\ServerEnvModel();
                foreach ($serverRes as $server) {
                    //删除serverenv中的sever_id和删除的image_name
                    $res = $envModel->deleteByServerImage($server['server_id'], $delDiff);
                    if (!$res['status']) {
                        return $this->json = ['status' => 400, 'errorMsg' => '清除去除的镜像相关信息失败，无法更新：'.$res['error']];
                    }
                }
            }
        }

        $status = $model->updateOne($id, $data);

        if ($status['status']) {
            return $this->json = ['status' => 200, 'id' => $status['id'] ,'msg' => $msg];
        } else {
            return $this->json = ['status' => 400, 'errorMsg' => $status['error']];
        }

        return $this->json = $status;
    }
    private function checkImageName($imageName)
    {
        $arr  = explode(':', $imageName);
        $diff = array_filter($arr);
        if (count($diff) == 2) {
            return true;
        }
        return false;
    }
    // 禁用容器编排模板
    public function disable($id)
    {
        if (empty($id)) {
            return ['status' => 400, 'error' => 100005, 'errorMsg' => '模板ID不能为空'];
        }

        $model = new DockerComposeModel;
        $status = $model->disableTem($id);

        if ($status['status']) {
            return ['status' => 200, 'id' => $status['id']];
        } else {
            return ['status' => 400, 'errorMsg' => $status['error']];
        }
        return $status;
    }

    // 启用容器编排模板
    public function enabled($id)
    {
        if (empty($id)) {
            return ['status' => 400, 'error' => 100005, 'errorMsg' => '模板ID不能为空'];
        }

        $model = new DockerComposeModel;
        $status = $model->enabledTem($id);

        if ($status['status']) {
            return ['status' => 200, 'id' => $status['id']];
        } else {
            return ['status' => 400, 'errorMsg' => $status['error']];
        }
        return $status;
    }
}
