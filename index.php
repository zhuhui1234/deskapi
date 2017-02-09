<?php
    require_once("config.inc.php");
    $file_name = basename(__FILE__,'.php');
    $controller = $_request->get('m',$file_name);
    $action = $_request->get('a','index');
    Controller::instance($controller)->{$action}();
?>