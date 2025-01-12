<?php
namespace php_active_record;
/*
works with: GBIF_SQL_DownloadsAPI.code-workspace

regular operation:
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"divide_into_continent_files"}'
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"generate_continent_checklists"}'
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"generate_continent_checklists", "sought_continent":"Adriatic Sea"}'
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"major_deletion"}'
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"generate_report", "report_name":"waterbodies"}'
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"generate_report", "report_name":"countries"}'
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"show_waterbodies_metadata"}'

when caching the species info:
    'counter' series not used for continent
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"generate_continent_checklists", "counter":"1"}'
    php update_resources/connectors/continent_checklists_2024.php _ '{"task":"generate_continent_checklists", "counter":"2"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ContinentChecklistsAPI');
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
$sought_continent = @$fields['sought_continent'];
*/

/*
$resource_id = 'SC_ceramsea';
$command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz --directory=" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . " .";
$output = shell_exec($command_line);
exit("\n-end utility-\n");
It seems SC_ceramsea.tar.gz already exists in editors.eol.org all along.
*/

$what = 'Continent_checklists';
$func = new ContinentChecklistsAPI($what);
$func->start($fields); //main operation

/*
$func->show_waterbodies_metadata(); //utility, generates https://editors.eol.org/other_files/GBIF_occurrence/Continent_checklists/waterbodies.tsv --- works OK | ran already
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