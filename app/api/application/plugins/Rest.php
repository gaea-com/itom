<?php

/**
 * @name   RestPlugin
 * rest风格控制
 * @desc   Yaf定义了如下的6个Hook,插件之间的执行顺序是先进先Call
 * @see    http://www.php.net/manual/en/class.yaf-plugin-abstract.php
 * @author daqian.sun
 */
class RestPlugin extends Yaf\Plugin_Abstract
{
    const NO_REST
        = [
            'index'        => ['image','login', 'vcode'],
            'accredit'     => ['creaet','delete'],
            'user'         => [
                'getjwt',
                'resetpasswd',
                'rootpasswd',
                'loginout'
            ],
            'env'          => ['index', 'create'],
            'hub'          => ['check', 'connect', 'getlist', 'gettags'],
            'accredit'     => ['delete'],
            'docker'       => [
                'imagelistforsearch',
                'updateimagelist',
                'batchpullimage',
                'container',
                'batchcreatecontainer',
                'stopcans',
                'stopcontainer',
                'stopcontainers',
                'containerlist',
                'delcontainer',
                'exec',
                'cmd',
                'task',
                'operatelog',
                'getserverimagelist',
            ],
            'server'       => [
                'getenvbyimage',
                'instanceoption',
                'getenvbysid',
                'serverenvupdate',
                'instanceinclude',
                'deleteinstance',
                'bindgroup',
                'copygroup',
                'getenvbyimage',
            ],
            'varinfo'      => [
                'index',
                'createvarinfo',
                'varnamecheck',
                'updatevarinfo',
                'getvarnamebyid',
                'delvarinfo',
                'getvararray',
                'getglobalvar',
            ],
            'orderinfo'    => [
                'index',
                'createorderinfo',
                'updateorderinfo',
                'getorderinfobyid',
                'delorderinfo',
                'orderpreview',
                'updateorderstatus',
                'getorderbypid',
            ],
            'customgroup'  => [
                'index',
                'getserverbygroupid',
                'create',
                'update',
                'delete',
            ],
            'taskinfo'     => [
                'index',
                'getorderbytask',
                'createtaskinfo',
                'updatetaskinfo',
                'updatetaskstatus',
                'deltaskinfo',
                'getdockerbypid',
            ],
            'timedtask'    => [
                'index',
                'create',
                'update',
                'delete',
                'crontabcheck',
            ],
        ];

    //在路由之前触发
    public function routerStartup(
        Yaf\Request_Abstract $request,
        Yaf\Response_Abstract $response
    ) {
    }

    //路由结束之后触发
    public function routerShutdown(
        Yaf\Request_Abstract $request,
        Yaf\Response_Abstract $response
    ) {
        $res = $this->noRestCheck(
            $request->getControllerName(),
            $request->getActionName()
        );
        if (! $res) {
            switch ($request->getMethod()) {
            case 'GET':
                $request->setActionName('index');
                break;

            case 'POST':
                $request->setActionName('create');
                break;

            case 'PUT':
                $request->setActionName('update');
                $json = file_get_contents("php://input");
                $request->setParam('data', $json);
                break;

            case 'DELETE':
                $request->setActionName('delete');
                break;

            case 'OPTIONS':
                break;

            default:
                header('HTTP/1.1 403 Forbidden');
                exit;
            }
        }
    }

    //不走Rest的Controller和Action
    private function noRestCheck($controller, $action)
    {
        $controller = lcfirst($controller);
        if (array_key_exists($controller, self::NO_REST)
            && in_array(
                $action,
                self::NO_REST[$controller]
            )
        ) {
            return true;
        }

        return false;
    }

    //分发循环开始之前被触发
    public function dispatchLoopStartup(
        Yaf\Request_Abstract $request,
        Yaf\Response_Abstract $response
    ) {
    }

    //  分发之前触发
    public function preDispatch(
        Yaf\Request_Abstract $request,
        Yaf\Response_Abstract $response
    ) {
    }

    //分发结束之后触发
    public function postDispatch(
        Yaf\Request_Abstract $request,
        Yaf\Response_Abstract $response
    ) {
        if ($request->isPost()) {
            $res = $response->getBody('res');
            $response->clearBody('res');
            if ($res) {
                $response->setHeader(
                    $request->getServer('SERVER_PROTOCOL'),
                    '201 Created'
                );
            }
        }
    }

    //分发循环结束之后触发
    public function dispatchLoopShutdown(
        Yaf\Request_Abstract $request,
        Yaf\Response_Abstract $response
    ) {
    }
}
