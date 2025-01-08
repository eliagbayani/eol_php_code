<?php
namespace php_active_record;
/* a utility that can access parts of a DwCA
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DwCA_Utility');
$timestart = time_elapsed();
ini_set("memory_limit","4000M"); // trying for the dwh_try3.zip, didn't work yet
$GLOBALS['ENV_DEBUG'] = true;
//===========================================================================================new - start -- handles cmdline params

// /* 1. First client
$dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/Polytraits.tar.gz";
$resource_id = "nothing here";
$params['row_type'] = 'http://rs.tdwg.org/dwc/terms/measurementorfact';
$params['column'] = 'http://rs.tdwg.org/dwc/terms/measurementType';
$download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1); //1 day cache
// */

// /* 2. 2nd client
$dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_andorra.tar.gz";
$dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_unitedarabemirates.tar.gz";

// $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . 'SC_afganistan.tar.gz';
// $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . 'SC_unitedarabemirates.tar.gz';
// $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . 'SC_andorra.tar.gz';

// $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_southernocean.tar.gz";
// $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_southpacific.tar.gz";
// $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_caribbeansea.tar.gz";

// $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . 'SC_southernocean.tar.gz';
// $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . 'SC_southpacific.tar.gz';
// $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . 'SC_caribbeansea.tar.gz';

$resource_id = "";
$params['row_type'] = 'http://rs.tdwg.org/dwc/terms/measurementorfact';
$params['column'] = 'http://rs.tdwg.org/dwc/terms/measurementValue';
$download_options = array("timeout" => 172800, 'expire_seconds' => 0); //60*60*24*1 = 1 day cache
// 2 new params - for a new feature
$params['sought_field']       = 'http://rs.tdwg.org/dwc/terms/measurementType';
$params['sought_field_value'] = 'http://eol.org/schema/terms/Present';
// */


$func = new DwCA_Utility($resource_id, $dwca_file);
/* a utility - works OK
$func->count_records_in_dwca($download_options); //works OK
*/
$unique_values = $func->lookup_values_in_dwca($download_options, $params); //get unique values of a column in any table in a DwCA
print_r($unique_values);
unset($func);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>