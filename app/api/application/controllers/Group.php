<?php
/**
 * 项目分组 相关控制器----业务拓扑（实例组）
 *
 */
use DAO\ProjectModel;
use DAO\ServerGroupModel;

class GroupController extends BaseController
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
     * 项目下实例组列表
     *
     * @return [type] [description]
     */
    public function indexAction($id)
    {
        $projectId = $id;
        if (!$projectId) {
            return $this->json = ['status' => 400, 'error' => 100013, 'errorMsg' => '参数不能为空'];
        }
        $model  = new ServerGroupModel();
        $data   = $model->findAll($projectId);
        return $this->json = ['status' => 200, 'data' => $data];
    }

    public function createAction()
    {
        $return = $this->insertUpdate();
        return $this->json = $return;
    }

    public function updateAction()
    {
        $id = $this->getRequest()->getParam('id', null);
        if (!$id || !is_numeric($id)) {
            return $this->json = ['status' => 400, 'error' => 100013, 'errorMsg' => '参数不能为空'];
        }
        $return = $this->insertUpdate($id);
        return $this->json = $return;
    }

    //编辑创建公共方法
    private function insertUpdate($id = null)
    {
        $input  = file_get_contents('php://input');
        $params = [];
        parse_str($input, $params);

        $data                = [];
        $data[':name']       = $params['name'] ?? null;
        $data[':project_id'] = $params['pid'] ?? null;
        $data[':type']       = $params['type'] ?? null;
        if (!$data[':name'] || !$data[':project_id'] || !$data[':type']) {
            return ['status' => 400, 'error' => 100013, 'errorMsg' => '参数不能为空'];
        }
        if (strlen($data[':name']) > 100) {
            return ['status' => 400, 'error' => 100037, 'errorMsg' => '名称不能超过100个字符或20个汉字'];
        }
        $projectModel = new ProjectModel;
        $project      = $projectModel->findOne($data[':project_id']);
        if (!$project) {
            return ['status' => 400, 'error' => 100038, 'errorMsg' => '项目不存在'];
        }
        if (!in_array($data[':type'], ServerGroupModel::getType())) {
            return ['status' => 400, 'error' => 100028, 'errorMsg' => '参数错误'];
        }

        $model = new ServerGroupModel();
        if ($id) {
            //如果修改type为可复制，则必须验证其下绑定的实例中没有导入类型的实例
            if ($data[':type'] == $model::TYPE_COPY_CAN) {
                $pServerModel = new DAO\ProjectServerModel();
                $result       = $pServerModel->findOneByGroupId($id, 100);
                if ($result) {
                    return ['status' => 400, 'error' => 100073, 'errorMsg' => '此实例组下存在导入实例，不能更改为可复制类型'];
                }
            }
            $res = $model->updateOne($id, $data);
            if ($res['status']) {
                return ['status' => 200, 'data' => ['status' => $res['status']]];
            }
            return ['status' => 400, 'error' => 999999, 'errorMsg' => $res['error']];
        }
        $data[':create_at']   = date('Y-m-d H:i:s');
        $data[':create_user'] = $this->getUserId();
        $res                  = $model->InsertOne($data);
        if ($res['status']) {
            return ['status' => 200, 'data' => ['id' => $res['id']]];
        }
        return ['status' => 400, 'error' => 999999, 'errorMsg' => $res['error']];
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id', null);
        if (!$id || !is_numeric($id)) {
            return $this->json = ['status' => 400, 'error' => 100013, 'errorMsg' => '参数不能为空'];
        }
        $projectServerModel = new DAO\ProjectServerModel();
        $instanceBind       = $projectServerModel->findOneByGroupId($id);
        if ($instanceBind) {
            return $this->json = ['status' => 400, 'error' => 100059, 'errorMsg' => '实例组下存在实例，不能删除，解绑后可删除'];
        }
        $model = new ServerGroupModel();
        $res   = $model->deleteOne($id);
        if ($res['status']) {
            return $this->json = ['status' => 200, 'data' => ['status' => $res['status']]];
        }
        return $this->json = ['status' => 400, 'error' => 999999, 'errorMsg' => $res['error']];
    }
}
