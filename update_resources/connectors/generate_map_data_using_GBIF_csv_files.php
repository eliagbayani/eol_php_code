<?php
namespace php_active_record;

/* utility to run multiple connectors:
cd /html/eol_php_code/update_resources/connectors
php5.6 generate_map_data_using_GBIF_csv_files.php jenkins '{"group":false,"range":[554050,554053],"ctr":1,"rank":""}'
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFoccurrenceAPI_DwCA');
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false; //should be false in eol-archive

print_r($argv);

$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['info']              = @$argv[2]; //useful here
// print_r($params);

$info = json_decode($params['info'], true);
$range = $info['range'];
$ctr = $info['ctr'];

print_r($range);
$range_from = $range[0];
$range_to = $range[1];

$func = new GBIFoccurrenceAPI_DwCA();
$func->generate_map_data_using_GBIF_csv_files(false, false, $range_from, $range_to);
unlink(CONTENT_RESOURCE_LOCAL_PATH . "map_generate_".$ctr.".txt");

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

if(are_all_indicator_files_deletedYN()) echo "\nCan now go to next step*...\n";
else {
    echo "\nCannot yet go to next step.\n";
    exit(1);
}

function are_all_indicator_files_deletedYN()
{
    $filename = CONTENT_RESOURCE_LOCAL_PATH . "map_generate_"."COUNTER".".txt";
    for($i = 1; $i <= 10; $i++) {
        $fn = str_replace('COUNTER', $i, $filename);
        if(file_exists($fn)) return false;
    }
    return true;
}
?>