<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
define('APPLICATION_PATH', dirname(__FILE__).'/api');
$app = new Yaf\Application(
    APPLICATION_PATH."/conf/application.ini",
    ini_get('yaf.environ')
);
if (empty($argv[1])) {
    die('Need Controller Params For argv');
}
if (empty($argv[2])) {
    die('Need Action Params For argv');
}
$paramsArr = [];
if (! empty($argv[3])) {
    parse_str($argv[3], $paramsArr);
}
$controller = ucfirst($argv[1]);
$action     = ucfirst($argv[2]);
$app->getDispatcher()->dispatch(
    new Yaf\Request\Simple("CLI", "Console", $argv[1], $argv[2], $paramsArr)
);
