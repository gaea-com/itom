<?php
/**
 * modules中的console模块的CLi基类
 */

class CliBase extends Yaf\Controller_Abstract
{
    public function init()
    {
        $method = $this->getRequest()->getMethod();
        if ($method != 'CLI') {
            throw new Exception("非法请求", 10000);
        }
        Yaf\Dispatcher::getInstance()->disableView();
    }
}
