<?php
namespace php_active_record;
/*  2025
    php update_resources/connectors/gbif_map_data.php _ '{"task":"xxx", "taxonGroup":"map_kingdom_not_animalia_nor_plantae"}' //Kingdoms not Animalia (1) nor Plantae (6)
    php update_resources/connectors/gbif_map_data.php _ '{"task":"xxx", "taxonGroup":"map_plantae_not_phylum_Tracheophyta"}' //Plantae 1
    php update_resources/connectors/gbif_map_data.php _ '{"task":"breakdown_GBIF_DwCA_file", "taxonGroup":"map_Gadiformes"}' //Gadiformes
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFMapDataAPI');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$p = json_decode($params['json'], true);

$taxonGroup = $p['taxonGroup'];
$func = new GBIFMapDataAPI($taxonGroup);
if($p['task'] == 'breakdown_GBIF_DwCA_file') $func->breakdown_GBIF_DwCA_file($taxonGroup);

/* testing functions
$key = 44; //Chordata
$key = 7707728; //Plantae - Tracheophyta
$func->prepare_taxa($key); //a utility
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
/* copied template
$resource_id = 'SC_ceramsea';
$command_line = "tar -czf " . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz --directory=" . CONTENT_RESOURCE_LOCAL_PATH . $resource_id . " .";
$output = shell_exec($command_line);
exit("\n-end utility-\n");
*/
?>