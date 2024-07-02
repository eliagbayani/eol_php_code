<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
$GLOBALS['ENV_DEBUG'] = false; //set to false in production
// */

// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */
$time_var = time();

// echo "<pre>"; print_r($_FILES); exit("</pre>");
$form = $_POST;
echo "<pre>"; print_r($form); echo "</pre>"; //exit("\neli 200\n");

// /* Filename_ID check if doesn't exist in OpenData. If doesn't exist, stop operation now.
if($resource_id = @get_val_var('resource_id')) {
}
// */

// if($form_url) { //URL is pasted.
// }
// elseif($file_type = @$_FILES["file_upload"]["type"]) { //Taxa File
// }
// elseif($file_type = @$_FILES["file_upload2"]["type"]) { // Darwin Core Archive
// }
// elseif($file_type = @$_FILES["file_upload3"]["type"]) { // Taxa List
// }
// else exit("<hr>Please select a file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");




function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
?>