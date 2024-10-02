<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */

$cmdline_params['jenkins_or_cron'] = @$argv[1]; //irrelevant here
$EOL_resource_id = @$argv[2];                   //useful here

exit("\n$EOL_resource_id\n");

/* as of Sep 4, 2024: snippet to update corresponding Zenodo record */
// $EOL_resource_id = "200_meta_recoded"; // $EOL_resource_id = "24"; //force assign
require_library('connectors/ZenodoConnectorAPI');
require_library('connectors/ZenodoAPI');
$func = new ZenodoAPI();
$func->update_Zenodo_record_using_EOL_resourceID($EOL_resource_id);
?>