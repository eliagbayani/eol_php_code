<?php
namespace php_active_record;
/*
    php update_resources/connectors/gbif_map_data.php _ '{"task":"xxx", "taxonGroup":"1"}' //Animalia
    php update_resources/connectors/gbif_map_data.php _ '{"task":"xxx", "taxonGroup":"6"}' //Plantae
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFMapDataAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$fields = json_decode($params['json'], true);


/*
$resource_id = 'SC_ceramsea';
$command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz --directory=" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . " .";
$output = shell_exec($command_line);
exit("\n-end utility-\n");
*/

$what = 'yyy';
$func = new GBIFMapDataAPI($what);
// $func->start($fields); //main operation

// /* testing functions
$key = 44; //Chordata
$key = 7707728; //Plantae - Tracheophyta
$func->prepare_taxa($key);
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>