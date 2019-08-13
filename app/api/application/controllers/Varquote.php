<?php
/**
 * VarInfo Controller
 * 自定义变量相关控制器
 *
 * @author  hu.zhou <hu.zhou@gaeamobile.com>
 * @version 1.0
 */
use DAO\VarQuoteModel;

class VarquoteController extends BaseController
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
        $return     = ['status' => 200, 'error' => ''];
        $var_name   = $_GET['var_name'] ?? null;
        $model      = new \DAO\VarQuoteModel;
        $data       = empty($var_name) ? $model->findAll() : $model->findAll(['var_name' => $var_name]);
        $returnData = [];
        $envQuote   = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if ($value['type'] == 100) {
                    if (empty($envQuote[$value['var_name']])) {
                        $varArr = empty($returnData[$value['var_name']]) ? [] : $returnData[$value['var_name']];
                        $arr    = [
                            'type'       => '环境变量',
                            'order_name' => '---',
                        ];
                        array_unshift($varArr, $arr);
                        $returnData[$value['var_name']] = $varArr;
                        $envQuote[$value['var_name']]   = $value['var_name'];
                    }
                } else {
                    $orderModel                       = new \DAO\OrderInfoModel;
                    $orderData                        = $orderModel->findOne(['id' => $value['quote_id']]);
                    $returnData[$value['var_name']][] = [
                        'type'       => '命令',
                        'order_name' => empty($orderData) ? '---' : $orderData['name'],
                    ];
                }
            }
        }
        $return['data']    = $returnData;
        return $this->json = $return;
    }
}
