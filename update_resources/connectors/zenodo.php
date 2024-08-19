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

$func->access_json_reports(); //this generates the HTML report


// $func->retrieve_dataset(13323232);

// $func->update_Zenodo_record(13273185);

// $func->list_depositions(); //worked OK

/*
// MainRep2: Title not found	[EduLifeDesks Archive: From so simple a beginning: 2010 (357) DwCA]	2024-08-19 09:05:05 AM
// MainRep2: Title not found	[National Checklists: São Tomé and Príncipe Species List]	2024-08-19 09:53:26 AM
// MainRep2: Title not found	[National Checklists 2019: São Tomé and Príncipe Species List]	2024-08-19 09:55:46 AM
// MainRep2: Title not found	[National Checklists 2019: Réunion Species List]	2024-08-19 09:56:26 AM
// MainRep2: Title not found	[National Checklists 2019: São Tomé and Príncipe Species List]	2024-08-19 09:57:00 AM
// MainRep2: Title not found	[National Checklists 2019: Réunion Species List]	2024-08-19 09:58:05 AM
// MainRep2: Title not found	[GBIF data summaries: GBIF nat'l node classification resource: Germany]	2024-08-19 09:58:38 AM
// MainRep2: Title not found	[Thomas J. Walker Sound Recordings from Macaulay Library of Natural Sounds: Thomas J. Walker's insect recordings]	2024-08-19 09:59:47 AM

$title = "National Checklists 2019: Réunion Species List";
$title = "National Checklists 2019: São Tomé and Príncipe Species List";
$title = "National Checklists: São Tomé and Príncipe Species List";
$title = "Moth Photographer's Group";
$title = "Trait Spreadsheet to DwCA: Fungi ecomorphological trait data";
$title = "National Checklists 2019: Réunion Species List";

$obj = $func->get_deposition_by_title($title);
print_r($obj); echo "\n[".$obj['id']."]\n";
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