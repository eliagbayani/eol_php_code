<?php
namespace php_active_record;
/* 
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ZenodoAPI');
$timestart = time_elapsed();


$func = new ZenodoAPI();
$func->start(); //main - this reads OpenData using its API and creates Zenodo records using the later's API.

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