<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DataHub_INAT_API_v2');
ini_set('memory_limit','15096M'); //15096M
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'iNat_metrics';
$resource_id = 'iNat';
$func = new DataHub_INAT_API_v2($resource_id);

$func->start();
// Functions::finalize_dwca_resource($resource_id, false, true); //false here means not a big file, true means delete working folder.

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>