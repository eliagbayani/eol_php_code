<?php
namespace php_active_record;
/* 
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DataHub_NCBI_API');
// ini_set('memory_limit','15096M'); //15096M
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$resource_id = 'NCBI';
$func = new DataHub_NCBI_API($resource_id);

/* --- test only ---
$options = array('resource_id' => "723_ncbi", 'expire_seconds' => 10, 'download_wait_time' => 2000000, 'timeout' => 10800*2, 'download_attempts' => 1); //3 months to expire
$url = "https://opendata.eol.org/dataset";
if(Functions::url_already_cached($url, $options)) exit("\ncached already\n");
else {
    echo "\nnot yet cached. caching now...\n";
    Functions::lookup_with_cache($url, $options);
}
exit("\n-end-\n");
*/

// /*
$func->start();
// */

// /*
// $func->parse_tsv_then_generate_dwca();
Functions::finalize_dwca_resource($resource_id, false, false); //false here means not a big file, true means delete working folder.
// */

// /* utility ========================== start
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();

$undefined_parents = $func->check_if_all_parents_have_entries($resource_id, false); //true means output will write to text file
echo "\nTotal undefined parents:" . count($undefined_parents)."\n"; unset($undefined_parents);

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH."$resource_id/"); //we can now delete folder after DWCADiagnoseAPI()
// /* utility ========================== end

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>