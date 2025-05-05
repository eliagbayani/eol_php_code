<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
/*
php update_resources/connectors/update_Zenodo_record_using_EOL_resourceID_directly.php _ '{"resource_id":"MAD_traits", "zenodo_id":"13321578"}'
*/
// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */

$params['jenkins_or_cron']  = @$argv[1]; //not needed here
$params                     = json_decode(@$argv[2], true);
$zenodo_id = @$params['zenodo_id']; 
$resource_id = @$params['resource_id']; 
print_r($params); //exit("\n$zenodo_id\n$resource_id\n");

/* as of Sep 4, 2024: snippet to update corresponding Zenodo record */
// $EOL_resource_id = "200_meta_recoded"; // $EOL_resource_id = "24"; //force assign
require_library('connectors/ZenodoFunctions');
require_library('connectors/ZenodoConnectorAPI');
require_library('connectors/ZenodoAPI');
$func = new ZenodoAPI();
$func->update_Zenodo_record_using_EOL_resourceID_directly($zenodo_id, $resource_id) //e.g. $resource_id is 'MAD_traits' for MAD_traits.tar.gz


/* Jenkins entry:

#NEXT STEP: Update respective Zenodo record
php update_Zenodo_record_using_EOL_resourceID_directly.php _ '{"resource_id":"MAD_traits", "zenodo_id":"13321578"}'

*/
?>