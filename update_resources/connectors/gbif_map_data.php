<?php
namespace php_active_record;
/*
    php update_resources/connectors/gbif_map_data.php _ '{"task":"generate_waterbody_checklists", "taxonGroup":"1"}' //Animalia
    php update_resources/connectors/gbif_map_data.php _ '{"task":"generate_waterbody_checklists", "taxonGroup":"6"}' //Plantae
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFMapDataAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
// ini_set('memory_limit','7096M'); //required

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$fields = json_decode($params['json'], true);

/* passed $fields instead of these 3
$counter = @$fields['counter'];
$task = @$fields['task'];
$sought_waterbdy = @$fields['sought_waterbdy'];
*/

/*
$resource_id = 'SC_ceramsea';
$command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz --directory=" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . " .";
$output = shell_exec($command_line);
exit("\n-end utility-\n");
It seems SC_ceramsea.tar.gz already exists in editors.eol.org all along.
*/

$what = 'WaterBody_checklists';
$func = new GBIFMapDataAPI($what);
$func->start($fields); //main operation

/*
$func->show_waterbodies_metadata(); //utility, generates https://editors.eol.org/other_files/GBIF_occurrence/WaterBody_checklists/waterbodies.tsv --- works OK | ran already
*/

/* copied template, not used here.
Functions::finalize_dwca_resource($resource_id, false, true, $timestart); //3rd param if false it will not remove working folder
*/
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>