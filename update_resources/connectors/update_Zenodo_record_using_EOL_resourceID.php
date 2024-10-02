<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
/*
php update_Zenodo_record_using_EOL_resourceID.php _ 'Bioimages'
*/
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


/* Jenkins entry:
cd /var/www/html/eol_php_code/update_resources/connectors

### this will generate 168.tar.gz
php 168.php jenkins

### this will generate 168_meta_recoded.tar.gz
php resource_utility.php jenkins '{"resource_id": "168_meta_recoded", "task": "metadata_recoding"}'

#LAST STEP: copy last transactional DwCA to Bioimages.tar.gz OK

cd /var/www/html/eol_php_code/applications/content_server/resources
cp 168_meta_recoded.tar.gz Bioimages.tar.gz
ls -lt 168_meta_recoded.tar.gz
ls -lt Bioimages.tar.gz
rm -f 168_meta_recoded.tar.gz

cd /var/www/html/eol_php_code/update_resources/connectors
php ckan_api_access.php jenkins "5b1ebec7-efd0-47b5-860e-5c841d88d366"

#NEXT STEP: Update respective Zenodo record
php update_Zenodo_record_using_EOL_resourceID.php _ 'Bioimages'
*/
?>
