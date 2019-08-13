<?php

use DAO\EnvTemplateModel;
use DAO\ProjectServerModel;
use Tools\RedisModel;
use Yaf\Application;

/**
 * 手动清除redis中的缓存 控制器
 *
 * php cli.php cacheflush rediscache
 */

class CacheflushController extends CliBase
{
    private $cacheKeys = [];

    public function init()
    {
        parent::init();
        $config = Application::app()->getConfig();
        if (isset($config->redis->cache)) {
            foreach ($config->redis->cache as $key) {
                $this->cacheKeys[] = $key;
            }
        }
    }

    // 刷新redis中的缓存信息
    public function rediscacheAction()
    {
        $redisModel = new RedisModel();
        $this->delRedisKey($redisModel, $this->cacheKeys);
        // 环境变量缓存更新
        $this->EnvTemplateFlush($redisModel);
        // server缓存更新
        $this->ServerFlush($redisModel);
    }

    private function delRedisKey($redisModel, array $keys)
    {
        foreach ($keys as $key) {
            echo $key, ' flush runing', PHP_EOL;
            $redisModel->redis->del($key);
            echo $key, ' flush success!', PHP_EOL;
        }
    }

    /**
     * 环境变量缓存更新
     * 读库的方法自动写入缓存
     *
     * @param $redisModel
     */
    private function EnvTemplateFlush()
    {
        echo 'env_template cache input start'.PHP_EOL;
        // 重新写入缓存
        $model   = new EnvTemplateModel;
        $envArr  = [];
        $envData = $model->findAll();
        foreach ($envData as $value) {
            $envArr[] = $value['image_name'];
        }
        $successNum = 0;
        foreach ($envArr as $image_name) {
            $temData = $model->findOneByImage($image_name);
            if (! empty($temData)) {
                $data       = $model->getAllInstanceByTemplateId(
                    $temData['id']
                );
                $successNum += count($data);
                echo 'env_template cache flush success----'.$successNum.PHP_EOL;
            }
        }

        echo 'env_template cache flush success'.PHP_EOL;
        echo PHP_EOL;
    }

    // server缓存更新
    private function ServerFlush($redisModel)
    {
        echo 'server cache flush start'.PHP_EOL;

        $serverList = ProjectServerModel::$cloud;
        if (! empty($serverList)) {
            foreach ($serverList as $type => $serverName) {
                echo $type.'_server cache flush start'.PHP_EOL;

                $redisKey = $type.'_server_cache_key';

                $serverModel = ProjectServerModel::getServerModel($type);

                $num = 0;
                if (! empty($serverModel)) {
                    $serverDataArr = $serverModel->findAllByParams(
                        ['status' => 'success']
                    );
                    if (! empty($serverDataArr)) {
                        foreach ($serverDataArr as $value) {
                            $redisModel->redis->hset(
                                $redisKey,
                                $value['id'],
                                json_encode($value)
                            );
                            $num++;
                            if ($num % 200 == 0) {
                                echo $type.'_server cache flush success----'
                                    .$num.PHP_EOL;
                            }
                        }
                        echo $type.'_server cache flush success----'.$num
                            .PHP_EOL;
                    }
                }
                echo $type.'_server cache flush success'.PHP_EOL;
            }
        }
        echo 'server cache flush success'.PHP_EOL;
        echo PHP_EOL;
    }
}
