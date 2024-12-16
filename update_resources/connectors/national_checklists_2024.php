<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");

/* e.g. php national_checklists_2024.php jenkins */
$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here

require_library('connectors/NationalChecklistsAPI');
$timestart = time_elapsed();
// ini_set('memory_limit','7096M'); //required

// /* //main operation
$what = 'Country_checklists';
$func = new NationalChecklistsAPI($what);
$func->start();
exit("\nstop elix\n");
/* copied template
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param if false it will not remove working folder
*/
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>