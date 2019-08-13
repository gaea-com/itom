<?php
/**
 * @name   Bootstrap
 * @author pi
 * @desc   所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see    http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf\Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf\Bootstrap_Abstract
{
    public function _initConfig()
    {
        //把配置保存起来
        $arrConfig = Yaf\Application::app()->getConfig();
        Yaf\Registry::set('config', $arrConfig);
    }

    public function _initPlugin(Yaf\Dispatcher $dispatcher)
    {
        //注册一个插件restf重写controller
        $restPlugin = new RestPlugin();
        $dispatcher->registerPlugin($restPlugin);
    }

    public function _initRoute(Yaf\Dispatcher $dispatcher)
    {
        $router = Yaf\Dispatcher::getInstance()->getRouter();
        //在这里注册自己的路由协议,默认使用简单路由

        /***********************************************************************
         ********************** 1.登录相关 已接入*************************************
         **********************************************************************/
        //登录认证 POST ok
        $route = new Yaf\Route\Rewrite('login', ['controller' => 'index', 'action' => 'login']);
        $router->addRoute('login', $route);
        $route = new Yaf\Route\Rewrite('codeimage', ['controller' => 'index', 'action' => 'image']);
        $router->addRoute('codeimagetest', $route);
        //图片验证码POST ok
        $route = new Yaf\Route\Rewrite('verifycode', ['controller' => 'index', 'action' => 'vcode']);
        $router->addRoute('verifycode', $route);
        //刷新jwt getjwt ok
        $route = new Yaf\Route\Rewrite('getjwt', ['controller' => 'user', 'action' => 'getjwt']);
        $router->addRoute('getjwt', $route);
        //退出登录
        $route = new Yaf\Route\Rewrite('logout', ['controller' => 'user', 'action' => 'loginOut']);
        $router->addRoute('logout', $route);
        /***********************************************************************
         *********************** 2.权限控制相关 未接入********************************
         **********************************************************************/
        // 获取权限列表
        $route = new Yaf\Route\Rewrite('getaccredit', ['controller' => 'accredit', 'action' => 'index']);
        $router->addRoute('getaccredit', $route);
        // 添加项目授权
        $route = new Yaf\Route\Rewrite('createaccredit', ['controller' => 'accredit', 'action' => 'create']);
        $router->addRoute('createaccredit', $route);
        // 删除项目授权
        $route = new Yaf\Route\Rewrite('deleteaccredit', ['controller' => 'accredit', 'action' => 'delete']);
        $router->addRoute('deleteaccredit', $route);
        /***********************************************************************
         ************************** 3.用户相关 未接入*********************************
         **********************************************************************/
        // 用户相关接口
        $route = new Yaf\Route\Rewrite('user/:id', ['controller' => 'user', 'action' => 'index']);
        $router->addRoute('user', $route);
        //用户修改密码 ok
        $route = new Yaf\Route\Rewrite('resetpasswd', ['controller' => 'user', 'action' => 'resetpasswd']);
        $router->addRoute('resetpasswd', $route);
        //rootpasswd
        $route = new Yaf\Route\Rewrite('rootreset', ['controller' => 'user', 'action' => 'rootpasswd']);
        $router->addRoute('rootreset', $route);
        /***********************************************************************
         ************************ 4.项目相关 ***********************************
         **********************************************************************/
        // 项目相关接口 ok
        $route = new Yaf\Route\Rewrite('project/:id', ['controller' => 'project', 'action' => 'index']);
        $router->addRoute('project', $route);
        // 业务拓扑相关接口 okproject
        $route = new Yaf\Route\Rewrite('group/:id', ['controller' => 'group', 'action' => 'index']);
        $router->addRoute('group', $route);
        /***********************************************************************
         ************************ 5.编排模板环境变量相关 *************************
         **********************************************************************/

        // 容器编排模板相关接口 ok
        $route = new Yaf\Route\Rewrite('dockercompose/:id', ['controller' => 'dockercompose', 'action' => 'index']);
        $router->addRoute('dockercompose', $route);
        //根据实例ID和保存在实例上的本地镜像ID获取镜像对应的ENV  ok
        $route = new Yaf\Route\Rewrite('getenv', ['controller' => 'env', 'action' => 'index']);
        $router->addRoute('getenv', $route);
        //创建或编辑 实例镜像的env  ok
        $route = new Yaf\Route\Rewrite('createenv', ['controller' => 'env', 'action' => 'create']);
        $router->addRoute('createenv', $route);
        /***********************************************************************
         ************************ 6.云实例操作相关 *************************
         **********************************************************************/

        //Cloud 云服务 实例rest ok
        $route = new Yaf\Route\Rewrite('cloud/:id', ['controller' => 'cloud', 'action' => 'index']);
        $router->addRoute('cloud', $route);
        //删除实例（表内容） ok
        $route = new Yaf\Route\Rewrite('deleteserver', ['controller' => 'server', 'action' => 'deleteInstance']);
        $router->addRoute('deleteserver', $route);
        //批量导入实例 ok
        $route = new Yaf\Route\Rewrite('instanceinclude', ['controller' => 'server', 'action' => 'instanceInclude']);
        $router->addRoute('instanceinclude', $route);
        //已导入实例绑定实例组  ok
        $route = new Yaf\Route\Rewrite('bindgroup', ['controller' => 'server', 'action' => 'bindGroup']);
        $router->addRoute('bindgroup', $route);
        //复制实例组 ok
        $route = new Yaf\Route\Rewrite('copygroup', ['controller' => 'server', 'action' => 'copyGroup']);
        $router->addRoute('copygroup', $route);
        /**********************************************************************
         ************************** 7. 镜像仓库 已接入************************************
         ********************************************************************/
        //列表  创建 镜像仓库url 账户  密码 ok
        $route = new Yaf\Route\Rewrite('hub', ['controller' => 'hub', 'action' => 'index']);
        $router->addRoute('hub', $route);
        //检查是否已经创建过镜像仓库 ok
        $route = new Yaf\Route\Rewrite('hubcheck', ['controller' => 'hub', 'action' => 'check']);
        $router->addRoute('hubcheck', $route);
        //测试 镜像仓库连接是否正常 ok
        $route = new Yaf\Route\Rewrite('hubconn', ['controller' => 'hub', 'action' => 'connect']);
        $router->addRoute('hubconn', $route);
        //获取仓库中的镜像列表 ok
        $route = new Yaf\Route\Rewrite('hublist', ['controller' => 'hub', 'action' => 'getList']);
        $router->addRoute('hublist', $route);
        //获取仓库中的镜像的标签列表 ok
        $route = new Yaf\Route\Rewrite('hubtaglist', ['controller' => 'hub', 'action' => 'getTags']);
        $router->addRoute('hubtaglist', $route);
        /***********************************************************************
         ************************* 8.Docker管理 已接入 ********************************
         **********************************************************************/
        //容器列表查询 ok
        $route = new Yaf\Route\Rewrite('containers', ['controller' => 'docker', 'action' => 'container']);
        $router->addRoute('containers', $route);
        //批量启动容器 ok
        $route = new Yaf\Route\Rewrite('batchcreatecans', ['controller' => 'docker', 'action' => 'batchCreateContainer']);
        $router->addRoute('batchcreatecans', $route);
        //停止容器 ok
        $route = new Yaf\Route\Rewrite('stopcan', ['controller' => 'docker', 'action' => 'stopContainer']);
        $router->addRoute('stopcan', $route);
        //按实例停止容器 ok
        $route = new Yaf\Route\Rewrite('stopcanforserver', ['controller' => 'docker', 'action' => 'stopContainers']);
        $router->addRoute('stopcanforserver', $route);
        //删除容器(表内容)  ？？
        $route = new Yaf\Route\Rewrite('delcan', ['controller' => 'docker', 'action' => 'delContainer']);
        $router->addRoute('delcan', $route);
        //同步容器列表 ok
        $route = new Yaf\Route\Rewrite('updatecans', ['controller' => 'docker', 'action' => 'containerList']);
        $router->addRoute('updatecans', $route);
        //向服务器发送命令 ok
        $route = new Yaf\Route\Rewrite('cmd', ['controller' => 'docker', 'action' => 'cmd']);
        $router->addRoute('cmd', $route);
        //指定服务器本地的imagelist ok
        $route = new Yaf\Route\Rewrite('serverimages', ['controller' => 'docker' , 'action' => 'getServerImageList']);
        $router->addRoute('serverimages', $route);
        //同步获取指定ip的镜像列表到本地  ok
        $route = new Yaf\Route\Rewrite('updateimages', ['controller' => 'docker', 'action' => 'updateImageList']);
        $router->addRoute('updateimages', $route);
        //向DOCKER发送命令 ok
        $route = new Yaf\Route\Rewrite('cancmd', ['controller' => 'docker', 'action' => 'exec']);
        $router->addRoute('cancmd', $route);
        //操作日志列表 ok
        $route = new Yaf\Route\Rewrite('operatelog', ['controller' => 'docker', 'action' => 'operateLog']);
        $router->addRoute('operatelog', $route);
        //获取已停止运行的容器列表 ok
        $route = new Yaf\Route\Rewrite('stopcans', ['controller' => 'docker', 'action' => 'stopcans']);
        $router->addRoute('stopcans', $route);
        //批量拉取镜像 ok
        $route = new Yaf\Route\Rewrite('batchpullimages', ['controller' => 'docker', 'action' => 'batchPullImage']);
        $router->addRoute('batchpullimages', $route);
        //批量启动容器 ok
        $route = new Yaf\Route\Rewrite('batchcreatecans', ['controller' => 'docker', 'action' => 'batchCreateContainer']);
        $router->addRoute('batchcreatecans', $route);
        /***********************************************************************
         ************************* 9.命令任务管理 已接入 ********************************
         **********************************************************************/

        //命令列表 ok
        $route = new Yaf\Route\Rewrite('orderinfo', ['controller' => 'orderinfo', 'action' => 'index']);
        $router->addRoute('orderinfo', $route);
        //添加命令 ok
        $route = new Yaf\Route\Rewrite('createorderinfo', ['controller' => 'orderinfo', 'action' => 'createOrderInfo']);
        $router->addRoute('createorderinfo', $route);
        //编辑命令 ok
        $route = new Yaf\Route\Rewrite('updateorderinfo', ['controller' => 'orderinfo', 'action' => 'updateOrderInfo']);
        $router->addRoute('updateorderinfo', $route);
        //根据ID获取命令信息
        $route = new Yaf\Route\Rewrite('getorderinfobyid', ['controller' => 'orderinfo', 'action' => 'getOrderInfoByID']);
        $router->addRoute('getorderinfobyid', $route);
        //删除命令 ok
        $route = new Yaf\Route\Rewrite('delorderinfo', ['controller' => 'orderinfo', 'action' => 'delOrderInfo']);
        $router->addRoute('delorderinfo', $route);

        //自定义组列表 ok
        $route = new Yaf\Route\Rewrite('customgroup', ['controller' => 'customgroup', 'action' => 'index']);
        $router->addRoute('customgroup', $route);
        //添加自定义组 ok
        $route = new Yaf\Route\Rewrite('createcustomgroup', ['controller' => 'customgroup', 'action' => 'create']);
        $router->addRoute('createcustomgroup', $route);
        //编辑自定义组 ok
        $route = new Yaf\Route\Rewrite('updatecustomgroup', ['controller' => 'customgroup', 'action' => 'update']);
        $router->addRoute('updatecustomgroup', $route);
        //删除自定义组 ok
        $route = new Yaf\Route\Rewrite('deletecustomgroup', ['controller' => 'customgroup', 'action' => 'delete']);
        $router->addRoute('deletecustomgroup', $route);

        //任务列表 ok
        $route = new Yaf\Route\Rewrite('gettaskinfo', ['controller' => 'taskinfo', 'action' => 'index']);
        $router->addRoute('gettaskinfo', $route);
        //根据任务ID获取命令详细信息 pk
        $route = new Yaf\Route\Rewrite('getorderbytask', ['controller' => 'taskinfo', 'action' => 'getOrderByTask']);
        $router->addRoute('getorderbytask', $route);
        //创建任务 ok
        $route = new Yaf\Route\Rewrite('createtaskinfo', ['controller' => 'taskinfo', 'action' => 'createTaskInfo']);
        $router->addRoute('createtaskinfo', $route);
        //编辑任务 ok
        $route = new Yaf\Route\Rewrite('updatetaskinfo', ['controller' => 'taskinfo', 'action' => 'updateTaskInfo']);
        $router->addRoute('updatetaskinfo', $route);
        //删除任务 ok
        $route = new Yaf\Route\Rewrite('deltaskinfo', ['controller' => 'taskinfo', 'action' => 'delTaskInfo']);
        $router->addRoute('deltaskinfo', $route);
        //执行任务 ok
        $route = new Yaf\Route\Rewrite('task', ['controller' => 'docker', 'action' => 'task']);
        $router->addRoute('task', $route);

        //定时任务列表 ok
        $route = new Yaf\Route\Rewrite('timedtask', ['controller' => 'timedtask', 'action' => 'index']);
        $router->addRoute('timedtask', $route);
        //创建定时任务 ok
        $route = new Yaf\Route\Rewrite('createtimedtask', ['controller' => 'timedtask', 'action' => 'create']);
        $router->addRoute('createtimedtask', $route);
        //编辑定时任务 ok
        $route = new Yaf\Route\Rewrite('updatetimedtask', ['controller' => 'timedtask', 'action' => 'update']);
        $router->addRoute('updatetimedtask', $route);
        //删除定时任务 ok
        $route = new Yaf\Route\Rewrite('deltimedtask', ['controller' => 'timedtask', 'action' => 'delete']);
        $router->addRoute('deltimedtask', $route);
        //crontab格式检测 ok
        $route = new Yaf\Route\Rewrite('crontabcheck', ['controller' => 'timedtask', 'action' => 'crontabCheck']);
        $router->addRoute('crontabcheck', $route);
    }

    public function _initView(Yaf\Dispatcher $dispatcher)
    {
        //在这里注册自己的view控制器，例如smarty,firekylin
        $dispatcher::getInstance()->disableView();
    }
}
