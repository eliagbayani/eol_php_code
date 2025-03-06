<?php
namespace php_active_record;
/*
php update_resources/connectors/zenodo_stats.php _ '{"task":"generate"}'
php update_resources/connectors/zenodo_stats.php _ '{"task":"show_report"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ZenodoFunctions');
require_library('connectors/ZenodoConnectorAPI');
require_library('connectors/ZenodoAPI');
$timestart = time_elapsed();
/* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
$GLOBALS['ENV_DEBUG'] = false; //set to false in production
*/
// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$p = json_decode($params['json'], true);
$task = @$p['task'];

$func = new ZenodoAPI();
if($task == 'generate')         $func->generate_stats_for_views_downloads();  //Mar 6, 2025
elseif($task == 'show_report')  $func->generate_tsv_report();                 //Mar 6-7, 2025

// $obj = $func->retrieve_dataset(13136202); print_r($obj); exit("\n-end retrieve test-\n");

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>