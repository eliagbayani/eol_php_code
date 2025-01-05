<?php
namespace php_active_record;
/*
works with: GBIF_SQL_DownloadsAPI.code-workspace

regular operation:
    php update_resources/connectors/waterbody_checklists_2024.php _ '{"task":"divide_into_waterbody_files"}'
    php update_resources/connectors/waterbody_checklists_2024.php _ '{"task":"generate_waterbody_checklists"}'
    php update_resources/connectors/waterbody_checklists_2024.php _ '{"task":"generate_waterbody_checklists", "sought_waterbdy":"Philippines"}'
    php update_resources/connectors/waterbody_checklists_2024.php _ '{"task":"major_deletion"}'
    OR 
    php update_resources/connectors/waterbody_checklists_2024.php _ '{"task":"generate_waterbody_checklists", "counter":"1"}'
    php update_resources/connectors/waterbody_checklists_2024.php _ '{"task":"generate_waterbody_checklists", "counter":"2"}'

when caching the species info: ran already
    php update_resources/connectors/waterbody_checklists_2024.php _ '{"counter":"1"}'
    php update_resources/connectors/waterbody_checklists_2024.php _ '{"counter":"2"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/WaterBodyChecklistsAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
// ini_set('memory_limit','7096M'); //required

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$fields = json_decode($params['json'], true);
// $counter = @$fields['counter'];
// $task = @$fields['task'];
// $sought_waterbdy = @$fields['sought_waterbdy'];

$what = 'WaterBody_checklists';
$func = new WaterBodyChecklistsAPI($what);
// $func->start($counter, $task, $sought_waterbdy); //main operation
$func->start($fields); //main operation

// /*
// $func->show_waterbodies_metadata(); //utility, generates https://editors.eol.org/other_files/GBIF_occurrence/WaterBody_checklists/waterbodies.tsv --- works OK | ran already
// */

/* copied template, not used here.
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param if false it will not remove working folder
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>