<?php
namespace php_active_record;
/* This is about ways to access the GBIF taxonomy */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = "protisten";

print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
// print_r($param); exit;

// /* un-comment in real operation
require_library('connectors/GBIFTaxonomyAPI');
$func = new GBIFTaxonomyAPI($param);
$id = '11592253'; //Squamata
if($func->is_id_valid_waterbody_taxon($id)) echo "\nValid\n";
else echo "\nInvalid\n";
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>