<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ZenodoAPI');
$timestart = time_elapsed();

/* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
$GLOBALS['ENV_DEBUG'] = false; //set to false in production
*/

// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */

$func = new ZenodoAPI();
// $func->start(); //main - this reads OpenData using its API and creates Zenodo records using the later's API.


$func->retrieve_dataset(13271534);

// $func->list_depositions(); //worked OK

/*
$title = "active: World Odonata List";
$title = "World Odonata List (ODO) - active: World Odonata List";
$title = "identifier map: current version";
$title = "EOL Dynamic Hierarchy: Dynamic Hierarchy Version 2.2";
$title = "World Odonata List";
$func->list_deposition_per_title($title);
*/

/* utility
$id = 13240089;
$id = 13239870; //manual
$id = 13239746;
$id = 13239604;
$id = 13239347;
$id = 13239226;
$id = 13239165;
$func->Zenodo_upload_publish($id);
*/

// $func->delete_dataset(13136202);
// $func->delete_dataset(12730072);

// $func->test_curl();

/*
$privateYN = 1; //meaning private datasets
// $privateYN = ''; //meaing public datasets
$func->list_all_datasets($privateYN); //works OK //utility - this updates CKAN resource, sets URL to local editors.eol.org file and generates the actual file.
*/

/* utility - licence
$func->get_all_Zenodo_licence_IDs(); //works OK
*/

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>