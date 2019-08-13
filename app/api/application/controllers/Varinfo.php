<?php
/**
 * VarInfo Controller
 * 自定义变量相关控制器
 *
 * @author  hu.zhou <hu.zhou@gaeamobile.com>
 * @version 1.0
 */
use DAO\VarInfoModel;

class VarinfoController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    // 自定义变量列表
    public function indexAction()
    {
        $return = ['status' => 200, 'error' => ''];
        $projectId = $_GET['pid'] ?? null;
        $model = new \DAO\VarInfoModel;
        $data = empty($projectId) ? $model->findAll() : $model->findAll(['project_id'=>$projectId]);
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $proModel = new \DAO\ProjectModel;
                $proData = $proModel->findOne($value['project_id']);
                $data[$key]['project_name'] = empty($proData) ? '---' : $proData['name'];
            }
        }
        $return['data'] = $data;
        return $this->json = $return;
    }

    // 添加自定义变量
    public function createVarInfoAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $name = $_POST['name'] ?? null;
        $var_name = $_POST['var_name'] ?? null;
        $var_type = $_POST['var_type'] ?? null;
        $var_value = $_POST['var_value'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id = $_POST['pid'] ?? null;
        $create_at = date('Y-m-d H:i:s');
        $create_user = $this->getUserId();

        if (empty($name) || empty($var_name) || empty($var_type) || empty($var_value) || empty($description) || empty($project_id)) {
            $return['error'] = '参数不能为空';
            return $this->json = $return;
        }

        $paramsArr = [
            'name' => $name,
            'var_name' => $var_name,
            'var_type' => $var_type,
            'var_value' => $var_value,
            'description' => $description,
            'project_id' => $project_id,
            'create_at' => $create_at,
            'create_user' => $create_user,
            'type' => 200,
        ];

        $model = new \DAO\VarInfoModel;
        $data = $model->insertOne($paramsArr);
        if (!$data['status']) {
            $return['error'] = $data['error'];
            return $this->json = $return;
        }
        $return['status'] = 200;
        $return['data'] = ['id' => $data['id']];
        return $this->json = $return;
    }

    public function varNameCheckAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $var_name = $_POST['var_name'] ?? null;
        $id = $_POST['id'] ?? null;
        if (empty($var_name)) {
            $return['error'] = '参数不能为空';
            return $this->json = $return;
        }

        $model = new \DAO\VarInfoModel;
        $data = $model->findOneVar(['var_name' => $var_name]);
        if (!empty($data) && $data['id']!=$id) {
            $return['status'] = 200;
            $return['repeat'] = true;
        } else {
            $return['status'] = 200;
            $return['repeat'] = false;
        }

        return $this->json = $return;
    }

    public function getVarNameByIdAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $id = $_POST['id'] ?? null;
        if (empty($id)) {
            $return['error'] = '参数不能为空';
            return $this->json = $return;
        }

        $model = new \DAO\VarInfoModel;
        $data = $model->findOne(['id' => $id]);
        if (!empty($data)) {
            $proModel = new \DAO\ProjectModel;
            $proData = $proModel->findOne($data['project_id']);
            $data['project_name'] = empty($proData) ? '---' : $proData['name'];
        }

        $return['status'] = 200;
        $return['data'] = $data;
        return $this->json = $return;
    }

    public function updateVarInfoAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? null;
        $var_name = $_POST['var_name'] ?? null;
        $var_type = $_POST['var_type'] ?? null;
        $var_value = $_POST['var_value'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id = $_POST['pid'] ?? null;

        if (empty($id) || empty($name) || empty($var_name) || empty($var_type) || empty($var_value) || empty($description) || empty($project_id)) {
            $return['error'] = '参数不能为空';
            return $this->json = $return;
        }

        $paramsArr = [
            'id' => $id,
            'name' => $name,
            'var_name' => $var_name,
            'var_type' => $var_type,
            'var_value' => $var_value,
            'description' => $description,
            'project_id' => $project_id,
        ];

        $model = new \DAO\VarInfoModel;
        $data = $model->updateOne($paramsArr);
        if (!$data['status']) {
            $return['error'] = $data['error'];
            return $this->json = $return;
        }
        $return['status'] = 200;
        $return['data'] = ['id' => $data['id']];
        return $this->json = $return;
    }

    public function delVarInfoAction()
    {
        $return = ['status' => 400, 'errorMsg' => ''];
        $id = $_POST['id'] ?? null;
        if (empty($id)) {
            $return['errorMsg'] = '参数不能为空';
            return $this->json = $return;
        }

        $model = new \DAO\VarInfoModel;
        $varData = $model->findOne(['id' => $id]);
        if (empty($varData)) {
            $return['errorMsg'] = '未发现此ID下的自定义变量';
            return $this->json = $return;
        }

        $quoteModel = new \DAO\VarQuoteModel;
        $quoteData = $quoteModel->findAll(['var_name' => $varData['var_name']]);
        if (!empty($quoteData)) {
            $return['errorMsg'] = '此变量目前还有引用，无法删除';
            return $this->json = $return;
        }

        $data = $model->deleteOne($id);
        if (!$data['status']) {
            $return['errorMsg'] = $data['errorMsg'];
            return $this->json = $return;
        }
        $return['status'] = 200;
        $return['data'] = ['id' => $data['id']];
        return $this->json = $return;
    }

    public function getVarArrayAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $type = $_POST['type'] ?? null;
        $project_id = $_POST['pid'] ?? null;
        $model = new \DAO\VarInfoModel;

        if (empty($type)) {
            $arr = [];
        } elseif ($type == 'local') {
            $arr = ['type' => 100];
        } elseif ($type == 'remote') {
            $arr = ['type' => 200];
        } else {
            $return['error'] = 'type错误';
            return $this->json = $return;
        }

        $arr['project_id'] = $project_id;

        $data = $model->findAllArrInfo($arr);
        $varData = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $varData[] = [
                    'name' => $value['var_name'],
                ];
            }
        }

        $return['status'] = 200;
        $return['data'] = $varData;

        return $this->json = $return;
    }

    public function getGlobalVarAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $model = new \DAO\VarInfoModel;
        $data = $model->findAllArrInfo(['type' => 100]);
        $returnData = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $returnData[] = [
                    'var_name' => $value['var_name'],
                    'var_value' => $value['var_value'],
                ];
            }
        }

        $return['status'] = 200;
        $return['data'] = $returnData;

        return $this->json = $return;
    }
}
