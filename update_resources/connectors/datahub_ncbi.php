<?php
namespace php_active_record;
/* 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DataHub_NCBI_API');
ini_set('memory_limit','15096M'); //15096M
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'NCBI';
$func = new DataHub_NCBI_API($resource_id);

// /*
$func->start(); 
// */

/*
$func->parse_tsv_then_generate_dwca();
Functions::finalize_dwca_resource($resource_id, false, true); //false here means not a big file, true means delete working folder.
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>