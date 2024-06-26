<?php
namespace php_active_record;
/* This is a generic way to remove habitat values that are descendants of marine and terrestrial.
As requested here: https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=66742&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66742
First client is: Environments EOL (708.tar.gz generated by 708_new.php)

php update_resources/connectors/rem_marine_terr_desc.php _ '{"resource_id":"708"}'
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// print_r($argv);
$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$params                     = json_decode(@$argv[2], true);
$resource_id = @$params['resource_id']; 

if(Functions::is_production())  $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';
else                            $dwca_file = 'http://localhost/eol_php_code/applications/content_server/resources/'.$resource_id.'.tar.gz';

$resource_id .= "_cleaned_habitat_values"; //remove all records for taxon with habitat value(s) that are descendants of both marine and terrestrial
process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    $preferred_rowtypes = array();
    $excluded_rowtypes = array('http://rs.tdwg.org/dwc/terms/measurementorfact', 'http://rs.tdwg.org/dwc/terms/occurrence');
    
    /* Whatever remain will be processed in DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/taxon
    http://eol.org/schema/reference/reference
    */
    $func->convert_archive($preferred_rowtypes, $excluded_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>