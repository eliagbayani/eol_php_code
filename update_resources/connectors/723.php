<?php
namespace php_active_record;
/* 
NCBI, GGBN, GBIF, BHL, BOLDS, INAT (6) database coverages
estimated execution time: ~3 days

php update_resources/connectors/723.php _ '{"rank": "family"}'          for "ncbi", "ggbn", "gbif", "bhl", "bolds", "inat" BUT inat here may not last long.
php update_resources/connectors/723.php _ '{"rank": "genus"}'           for "ncbi", "ggbn", "gbif", "bhl", "bolds"   --- No "inat" here.

723	Wednesday 2018-03-28 10:17:55 PM	{"measurement_or_fact.tab":116434,"occurrence.tab":116434,"taxon.tab":9913} - MacMini
723	Wednesday 2018-03-28 10:54:54 PM	{"measurement_or_fact.tab":116434,"occurrence.tab":116434,"taxon.tab":9913}
723	Sunday 2018-05-13 03:44:33 PM	    {"measurement_or_fact.tab":116456,"occurrence.tab":116360,"taxon.tab":9917}
723	Saturday 2018-08-04 07:40:23 AM	    {"measurement_or_fact.tab":116390,"occurrence.tab":116390,"taxon.tab":9904} - eol-archive (last run)
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/NCBIGGIqueryAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

exit("\nThis is now obsolete: [NCBIGGIqueryAPI.php]\n");

// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$rank = $param['rank'];
print_r($param);

$resource_id = "723_".$rank;
$func = new NCBIGGIqueryAPI($resource_id, $rank);

$func->start();
Functions::finalize_dwca_resource($resource_id, false, true); //false here means not a big file, true means delete working folder.

/* not yet implemented
sleep(60);
$func->generate_spreadsheet($resource_id);
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";

function count_subfamily_per_database() // call this function above to run the report
{
    $databases = array("bhl", "ncbi", "gbif", "bolds"); // nothing for ggbn
    foreach($databases as $database) {
        $func->count_subfamily_per_database(DOC_ROOT . "/tmp/dir_" . $database . "/" . $database . ".txt", $database);
    }
}
?>