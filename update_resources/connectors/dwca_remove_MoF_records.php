<?php
namespace php_active_record;
/* This is to remove records in MoF with a certain criteria
First client for DWCA_Remove_MoF_RecordsAPI is: NorthAmericanFlora_All.tar.gz

php update_resources/connectors/dwca_remove_MoF_records.php _ '{"resource_id":"NorthAmericanFlora_All", "destination_id":"NorthAmericanFlora_All_subset"}'
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;
require_library('connectors/DwCA_Utility');
// ini_set('memory_limit','9096M'); //required

// print_r($argv);
$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$params                     = json_decode(@$argv[2], true);
$resource_id = @$params['resource_id']; 

if(Functions::is_production())  $dwca = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
else                            $dwca = 'http://localhost/eol_php_code/applications/content_server/resources_3/'.$resource_id.'.tar.gz';

$resource_id = $params['destination_id'];

$func = new DwCA_Utility($resource_id, $dwca, $params);
$preferred_rowtypes = array();
$excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact');
$func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
Functions::finalize_dwca_resource($resource_id, true, false, $timestart); //3rd param true means delete folder
$ret = run_utility($resource_id); //check for orphan records in MoF
recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH.$resource_id."/"); //we can now delete folder after run_utility() - DWCADiagnoseAPI

function run_utility($resource_id)
{
    /* utility ==========================
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined_parents = $func->check_if_all_parents_have_entries($resource_id, true, false, false, 'parentMeasurementID', 'measurement_or_fact_specific.tab');
    echo "\nTotal undefined parents MoF:" . count($undefined_parents)."\n";
    ===================================== */
}
?>