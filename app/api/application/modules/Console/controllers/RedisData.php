<?php

/**
 * Class RedisDataController
 * redis数据备份与恢复控制器
 */
class RedisDataController extends Yaf\Controller_Abstract
{
    public function init()
    {
        $method = $this->getRequest()->getMethod();
        if ($method != 'CLI') {
            throw new \Exception("非法请求", 10000);
        }
        Yaf\Dispatcher::getInstance()->disableView();
    }

    public function exportAction()
    {
        $RedisModel  = new Tools\RedisModel();
        $keys = $RedisModel->redis->keys('*');
        $model = new \DAO\RedisDataModel();
        foreach ($keys as $key) {
            $type = $RedisModel->redis->type($key);
            $res = $model->insertOneKey([':key'=>$key,':type'=>$type]);
            if (!$res['status']) {
                echo 'save key is error: '.$res['error'].PHP_EOL;
            }
            $insertData = [':key_id'=>$res['id']];
            if ($type==1) {
                $value = $RedisModel->redis->get($key);
                $insertData[':field'] = '';
                $insertData[':value'] = $value;
                $res2 = $model->insertOneData($insertData);
                if (!$res2['status']) {
                    echo 'key: '.$key.', save data is error: '.$res2['error'].PHP_EOL;
                    continue;
                }
            }
            if ($type==5) {
                $values = $RedisModel->redis->hgetall($key);
                foreach ($values as $field => $value) {
                    $insertData[':field'] = $field;
                    $insertData[':value'] = $value;
                    $res2 = $model->insertOneData($insertData);
                    if (!$res2['status']) {
                        echo 'key: '.$key.', save data is error: '.$res2['error'].PHP_EOL;
                        continue;
                    }
                }
            }
            echo 'key data is export success: '.$key.PHP_EOL;
        }
        unset($RedisModel, $model);
    }

    public function importAction()
    {
        $model = new \DAO\RedisDataModel();
        $RedisModel  = new Tools\RedisModel();
        $keys = $model->findAllKey();
        foreach ($keys as $value) {
            $data = $model->findAllDataBykey($value['id']);
            if ($value['type']==1) {
                $res = $RedisModel->redis->set($value['key'], $data[0]);
                if (!$res) {
                    echo 'key: '.$value['key'].', import data is error'.PHP_EOL;
                    continue;
                }
            }
            if ($value['type']==5) {
                foreach ($data as $v) {
                    $res = $RedisModel->redis->hset($value['key'], $v['field'], $v['value']);
                    if (!$res) {
                        echo 'key: '.$value['key'].', import data is error'.PHP_EOL;
                        continue;
                    }
                }
            }
            echo 'key data is import success: '.$value['key'].PHP_EOL;
        }
        unset($RedisModel, $model);
    }
}
