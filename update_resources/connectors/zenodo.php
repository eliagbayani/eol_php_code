<?php
namespace php_active_record;
/* */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ZenodoConnectorAPI');
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

// $str = "https://zenodo.org/search?q=metadata.subjects.subject%3A%22EOL%20Content%20Partners%22&f=subject%3AEOL%20Content%20Partners&l=list&p=1&s=10&sort=bestmatch";
// $str = "https://zenodo.org/search?q=metadata.subjects.subject%3A%22EOL%20Content%20Partners%3A%20EduLifeDesks%20Archive%22&l=list&p=1&s=10&sort=bestmatch";
// exit("\n".urldecode($str)."\n");

/*
$desc = "doi: eli< doi: cha] doi: isaiah|"; echo "\n$desc\n";
$left = 'doi:'; //$right = " ";
// if(preg_match_all("/".preg_quote($left, '/')."(.*?)([<]|])/ims", $desc, $arr)) { print_r($arr[1]);
if(preg_match_all("/".preg_quote($left, '/')."(.*?)(<|]|\|)/ims", $desc, $arr)) { print_r($arr[1]);
}    
else echo "\nnot found\n";
exit("\n -end test- \n");
*/
/*
$str = "http://doi.org/10.14344/IOC.ML.7.1)";
$str = "http://doi.org/10.14344/(IOC.ML.7.1)";
// $str = "http://doi.org/10.14344/IOC.ML.7.1]";
// $str = "http://doi.org/10.14344/[IOC.ML.7.1]";
$str = trim($str);
$last_char = substr($str, -1);
echo "\nstr: [$str]\n"; echo "\nlast: [$last_char]\n";
if(in_array($last_char, array(")", "]", "}"))) {
  if($last_char == ")") $start_char = "(";
  if($last_char == "]") $start_char = "[";
  if($last_char == "}") $start_char = "{";
  $tmp_str = substr($str,0,strlen($str)-1);
  echo "\ntmp str: [$tmp_str]\n";
  if(stripos($tmp_str, $start_char) !== false) { //string is found
    echo "\nfinal: [$str]\n";
    return $str;
  }
  else {
    echo "\nfinal: [$tmp_str]\n";
    return $tmp_str;
  }
}
exit("\n -end test- \n");
*/

$func = new ZenodoAPI();
// /*
// $func->rename_anne_thessen_to_2017(); //DONE Feb 10, 2025
// $func->investigate_diff_on_natl_checklists();
// $func->update_desc_national_2019_checklists(); //DONE Feb 8, 2015
// $func->rename_latest_GBIFsql_from_2019_to_blank();
$func->add_active_tag_2latest_national_checklists();
// */

// $func->jen_DOI_Works(); //https://github.com/EOL/ContentImport/issues/16#issuecomment-2501080414

/* all these four (4) done already:
$func->jen_Deprecated_Works(); //deprecated task... one-time only | DONE ?? ??, 2024 https://github.com/EOL/ContentImport/issues/16#issuecomment-2488617061
// $func->jen_Related_Works(); //one-time only | DONE Oct 27, 2024
// $func->latest_katja_changes_2(); //https://github.com/EOL/ContentImport/issues/16#issuecomment-2364296684
// $func->latest_katja_changes(); //https://github.com/EOL/ContentImport/issues/16#issuecomment-2364296684
// $func->start(); //main - this reads OpenData using its API and creates Zenodo records using the later's API.
*/

/*
$title = "National Checklists: Democratic Republic of the Congo Species List";
$title = "Water Body Checklists: Ceram Sea Species List";
$title = "Water Body Checklists 2019: Timor Sea Species List";
$title = "Water Body Checklists 2019: Aegean Sea Species List";
$obj = $func->get_deposition_by_title($title);
print_r($obj); echo "\n[".$obj['id']."]\n"; exit("\n-end test-\n");
*/

// /* -------------------------------------------------------------------------------------------- very good query results
$q = "+title:national +title:checklists -title:2019 -title:water"; //works splendidly - OK!
// $q = "-title:national +title:checklists -title:2019 title:water"; //works splendidly - OK!
// $q = "+title:checklists +title:2019"; //set 'geography', remove 'deprecated', add isDerivedFrom
// $q = "+title:FishBase";
// $q = "related.relation:isSourceOf";
// $q = "+related.relation:issourceof +keywords:deprecated"; //very accurate query - OK!
// $q = "+related.relation:issourceof +keywords:deprecated"; //very accurate query - OK!

// $q = "+related.relation:issupplementto = %LD_%.tar.gz"; //doesn't work
// $q = "+contributors.type:datamanager";
// $q = "+title:Life";
// $q = "+title:LifeDesk";
// $q = "+title:LD_";
// $q = "+related.relation:issupplementto";
// $q = "+title:Scratchpad";
$q = "+title:Democratic Republic of the Congo +title:2019";
$q = '+title:"Territory of the French Southern and Antarctic Lands" +title:2019 +title:Checklists';

if($obj = $func->get_depositions_by_part_title($q)) {
  print_r($obj); exit("\n-found-\n");
}
exit("\n-not found-\n");
// -------------------------------------------------------------------------------------------- */

// $func->access_json_reports(); //this generates the HTML report

// $func->retrieve_dataset(13240083); exit;

// $func->update_Zenodo_record(13273185);

// $func->list_depositions(); //worked OK //utility -- check if there are records in CKAN that are not in Zenodo yet.

/*
$json = '{
  "type": "equals",
  "key": "OCCURRENCE_STATUS",
  "value": "present",
  "matchCase": false
}';
$arr = json_decode($json, true); print_r($arr); exit;
*/


/* works OK
// $id = 13340241; //1 version only
$id = 13240083; //multiple versions
$path = '/Volumes/OWC_Express/other_files/test_upload12.txt'; exit;
$func->update_zenodo_record_of_eol_resource($id, $path); //https://zenodo.org/records/13240083 test record
// $func->update_Zenodo_record_v2($id);
*/

/* ok --- last routine to run
$eol_resource_id = "200_meta_recoded";
// $eol_resource_id = 24;
// $eol_resource_id = "42_meta_recoded"; //FishBase
$func->update_Zenodo_record_using_EOL_resourceID($eol_resource_id);
*/


/*
exit("\nwala lang...\n");
$func->new_description_for_zenodo = false; //important to initialize to false
require_library('connectors/CKAN_API_AccessAPI');
$ckan_func = new CKAN_API_AccessAPI('EOL resource', ""); //other values: "EOL dump" or "EOL file"
$EOL_resource_id = "200_meta_recoded";
$EOL_resource_id = "24";
$ckan_record = $ckan_func->get_ckan_record_using_EOL_resource_id($EOL_resource_id);
if($ckan_resource_id = @$ckan_record[1]) {
    $rec = $ckan_func->retrieve_ckan_resource_using_id($ckan_resource_id); // print_r($rec);
    $new_description = $ckan_func->format_description($rec['result']['description']);
    echo "\nold desc: [".$rec['result']['description']."]";
    echo "\nnew desc: [$new_description]\n";
    $func->new_description_for_zenodo = $new_description; //a global field in ZenodoAPI.php
}
$func->update_Zenodo_record_using_EOL_resourceID($EOL_resource_id);
$func->new_description_for_zenodo = ""; //initialize again
*/


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
// $title = "Moth Photographer's Group";
// $title = "Trait Spreadsheet to DwCA: Fungi ecomorphological trait data";
// $title = "National Checklists 2019: Réunion Species List";
$title = "FishBase";

$obj = $func->get_deposition_by_title($title);
print_r($obj); echo "\n[".$obj['id']."]\n"; exit("\n-end test-\n");
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