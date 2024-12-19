<?php
namespace php_active_record;
/* This can be a template for any resource, a means to enter taxon rows for the undefined parentNameUsageIDs

The first 3 clients is in: fill_up_undefined_parents_real.php

------------------------------------ 4th client: GBIF checklists
php fill_up_undefined_parents_real_GBIFChecklists.php _ '{"resource_id": "SC_andorra", "source_dwca": "SC_andorra", "resource": "fillup_missing_parents_GBIFChecklists"}'
------------------------------------ end ------------------------------------
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
ini_set('memory_limit','7096M');

$timestart = time_elapsed();
echo "\n--------------------START: fillup missing parent entries--------------------\n";
// print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$source_dwca = $param['source_dwca'];


// /* during development --- or when investigating
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */

/* just a test
$status = chmod(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".tar.gz", 0775);
exit("\nFile permission update: [$status]\n");
*/

if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$source_dwca.'.tar.gz';
else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/'.$source_dwca.'.tar.gz';

$ctr = 1;
$undefined = process_resource_url($dwca_file, $resource_id, $timestart, $ctr, $param);
// exit("\n-exit muna-\n");

while($undefined) { $ctr++;
    if(Functions::is_production()) $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
    else                           $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources_3/'.$resource_id.'.tar.gz';
    $undefined = process_resource_url($dwca_file, $resource_id, $timestart, $ctr, $param);
}
echo "\n--------------------END: fillup missing parent entries--------------------\n";

/* new: Oct 29,2024 - final step where Zenodo record should be updated. Because all iterations above have not updated Zenodo as intended. */
/* as of Sep 4, 2024: snippet to update corresponding Zenodo record --- PART OF MAIN OPERATION --- uncomment in real operation
$EOL_resource_id = $resource_id;
require_library('connectors/ZenodoConnectorAPI');
require_library('connectors/ZenodoAPI');
$func = new ZenodoAPI();
$func->update_Zenodo_record_using_EOL_resourceID($EOL_resource_id);
*/

function process_resource_url($dwca_file, $resource_id, $timestart, $ctr, $param)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file, $param);

    /* Orig in meta.xml has capital letters. Just a note reminder. */
    $preferred_rowtypes = false;
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon');

    $excluded_rowtypes[] = 'http://rs.tdwg.org/dwc/terms/occurrence';
    $excluded_rowtypes[] = 'http://rs.tdwg.org/dwc/terms/measurementorfact';

    /* This will be processed in FillUpMissingParentsAPI.php which will be called from DwCA_Utility.php */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    // echo "\n===Ready to finalize...\n";
    Functions::finalize_dwca_resource($resource_id, false, false, $timestart, CONTENT_RESOURCE_LOCAL_PATH, array('go_zenodo' => false));
    
    $status = chmod(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".tar.gz", 0775);
    echo "\nFile permission update: [$status]\n";
    
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    echo "\nUndefined parents now [$ctr]: ".count($undefined)."\n";
    
    //now u can delete working dir
    recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id/");
    
    return $undefined;
}
?>