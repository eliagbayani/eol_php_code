<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = false;

// $resource_id = @$_GET["resource_id"];
// if(!$resource_id) $function = @$_POST["resource_id"]; //not needed yet
// print_r(@$_GET);

$ret = $_SERVER; // echo "<pre>"; print_r($ret); echo "</pre>";
if($GLOBALS['ENV_DEBUG'] == false) header('Content-Type: application/json');
require_library('OpenData');
$func = new OpenData();
$info = $func->get_id_from_REQUEST_URI($ret['REQUEST_URI']);
$func->get_resource_by_id($info['id']);
// $func->get_resource_by_id_v2($info['id']); //test for sql injection
?>
