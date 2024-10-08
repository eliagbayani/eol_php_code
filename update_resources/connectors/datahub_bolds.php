<?php 
namespace php_active_record;
/* From Katja: https://github.com/EOL/ContentImport/issues/6#issuecomment-2091765126
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DataHub_BOLDS_API');
ini_set('memory_limit','15096M'); //15096M
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'BOLDS';
$func = new DataHub_BOLDS_API($resource_id);

// RUN THIS IN LOCAL MAC-STUDIO ONLY

// /*
$func->start(); 
// */

/* a utility
$func->search_families_not_found();
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>