<?php
namespace php_active_record;
/* 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DataHub_BHL_API');
ini_set('memory_limit','15096M'); //15096M
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'BHL';
$func = new DataHub_BHL_API($resource_id);

/* test only
// header('Content-Type: text/html; charset=UTF-8');
$str = "abqwrešđčžsff Ã— × elix čž βbb β  Î²  α  Î± aAÂ cha";
$new = preg_replace('/[^\x20-\x7E]/', '', $str);
$new = Functions::remove_whitespace($new);
echo "\n($str)($new)\n"; exit;
*/

// /*
$func->start(); 
// */

// /* main part that generates the DwCA
Functions::finalize_dwca_resource($resource_id, false, true); //false here means not a big file, true means delete working folder.
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>