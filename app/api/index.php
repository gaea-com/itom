<?php

ini_set("display_errors", "On");
error_reporting(E_ALL);
define('APPLICATION_PATH', __DIR__);
$application = new Yaf\Application(APPLICATION_PATH . "/conf/application.ini", ini_get('yaf.environ'));
$application->bootstrap()->run();
