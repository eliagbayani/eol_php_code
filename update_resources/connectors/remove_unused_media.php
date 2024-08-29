<?php
namespace php_active_record;
/* This is generic way of removing unused media records.
first client: Micro*scope
    php update_resources/connectors/remove_unused_media.php _ '{"resource_id": "microscope_2024_06_05", "resource": "remove_unused_media", "resource_name": "micro*scope"}'
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
print_r($param);

$dwca_file = tar_gz_OR_zip($resource_id);

// /* ---------- customize here ----------
    if($resource_id == 'microscope_2024_06_05')     $resource_id = "microscope_2024_08_29"; //destination resource_id
elseif($resource_id == 'the source')                $resource_id = "final dwca"; //add other resources here...
else exit("\nERROR: resource_id [$resource_id] not yet initialized. Will terminate.\n");
// ----------------------------------------*/
process_resource_url($dwca_file, $resource_id, $param);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function process_resource_url($dwca_file, $resource_id, $param)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $param);
    $preferred_rowtypes = array(); //best to set this to array() and just set $excluded_rowtypes to media extension.

    // /* main operation. If you can't run an extension in DwCA_Utility bec it has too many records (memory leak) then add it here. And just carry_over_extension() it.
    // Only the [media] will be updated.
    $excluded_rowtypes = array("http://eol.org/schema/media/document", "http://eol.org/schema/agent/agent", "http://rs.tdwg.org/dwc/terms/taxon");
    // These below will be processed in ResourceUtility.php which will be called from DwCA_Utility.php
    // http://eol.org/schema/media/document http://eol.org/schema/agent/agent http://rs.tdwg.org/dwc/terms/taxon
    // */

    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true); //3rd param false means don't delete working folder yet    
}
function tar_gz_OR_zip($resource_id)
{
    $path = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id";
        if(is_file($path . ".tar.gz"))  return $path . ".tar.gz";
    elseif(is_file($path . ".zip"))     return $path . ".zip";
    else exit("\nTerminated: File not found [$path .tar.gz or .zip]\n");
}
?>