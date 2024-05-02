<?php
namespace php_active_record;
/*
http://rs.tdwg.org/dwc/terms/taxon:
Total: 481602
http://rs.tdwg.org/dwc/terms/measurementorfact:
Total: 481602
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DataHub_INAT_API');
ini_set('memory_limit','15096M'); //15096M
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'iNat_metrics';
$func = new DataHub_INAT_API($resource_id);

// $func->get_iNat_taxa_using_API('genus');     //not advisable to use, bec. of the 10,000 limit page coverage. Ken-ichi advised to use the DwCA instead.
// $func->get_iNat_taxa_using_DwCA('genus');    //works but may not be needed anymore

$func->explore_dwca(); //this uses the DwCA provided by iNat to GBIF with only research-grade type observations.
Functions::finalize_dwca_resource($resource_id, false, true); //false here means not a big file, true means delete working folder.

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>