<?php
namespace php_active_record;
/* BOLDS two interfaces:
https://www.boldsystems.org/index.php/TaxBrowser_Home
https://v3.boldsystems.org/index.php/TaxBrowser_Home
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DataHub_BOLDS_API_v2');
// ini_set('memory_limit','15096M'); //15096M
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'BOLDS';
$func = new DataHub_BOLDS_API_v2($resource_id);

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