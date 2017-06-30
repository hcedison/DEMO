<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

define("APP_PATH",  realpath(dirname(__FILE__) . '/../')); /* 指向public的上一级 */
define("APPLICATION_COINFIG_FILE",APP_PATH . "/conf/application.ini");
define("APPLICATION_PATH",  realpath(dirname(__FILE__) . '/../'));

$app  = new Yaf_Application(APP_PATH . "/conf/application.ini");
$app->bootstrap();
$app->run();

?>