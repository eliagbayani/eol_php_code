<?php
namespace php_active_record;
/* This can be a generic connector for CSV DwCA resources - without meta.xml. (Another similar resource is 430.php) 

from eol-archive:
try_dbase	Wednesday 2018-10-03 05:22:45 AM	{"measurement_or_fact_specific.tab":548631,"occurrence.tab":164442,"reference.tab":182,"taxon.tab":103373}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TryDatabaseAPI');
$timestart = time_elapsed();

/* test
$val = array(1,5,2,3,1,5,100,50,5,2);
$val = array_unique($val);
sort($val);
print_r($val);
exit("-test end-");
*/

$resource_id = "try_dbase";
// $resource_id = 1;
$func = new TryDatabaseAPI($resource_id);
$func->convert_archive();
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete the working folder from /resources/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>
