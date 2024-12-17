<?php
namespace php_active_record;
/*
regular operation:
    php update_resources/connectors/national_checklists_2024.php
when caching:
    php update_resources/connectors/national_checklists_2024.php _ '{"counter":"1"}'
    php update_resources/connectors/national_checklists_2024.php _ '{"counter":"2"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NationalChecklistsAPI');
$timestart = time_elapsed();
// ini_set('memory_limit','7096M'); //required

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$fields = json_decode($params['json'], true);
$counter = @$fields['counter'];

// /* //main operation
$what = 'Country_checklists';
$func = new NationalChecklistsAPI($what);
$func->start($counter);
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