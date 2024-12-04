<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
// ini_set('memory_limit','7096M');
$timestart = time_elapsed();

print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
$resource_id = $param['resource_id'];
$source_dwca = $param['source_dwca'];

if($term = @$_GET['term']) {}
else exit("Invalid parameter.<p>Pass a [term] parameter with a valid value e.g. <a href='?term=http://purl.obolibrary.org/obo/ENVO_00000002'>?term=http://purl.obolibrary.org/obo/ENVO_00000002</a>"."<br><br>");

/* during development --- or when investigating
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
*/

$GLOBALS['ENV_DEBUG'] = false;

require_library('connectors/CladeSpecificFilters4Habitats_API');
$func = new CladeSpecificFilters4Habitats_API(null, null);
// $term = 'http://purl.obolibrary.org/obo/ENVO_00000002';
$label = pathinfo($term, PATHINFO_BASENAME); //ENVO_00000002
$final = $func->get_all_descendants_of_a_term($term, $label, 'html');
// print_r($final);
?>