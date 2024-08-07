<?php
namespace php_active_record;
/* a utility to diagnose EOL DWC-A */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DWCADiagnoseAPI');
$timestart = time_elapsed();
ini_set('memory_limit','7096M');

// $source      = DOC_ROOT . "temp/" . "folder2/yyy";
// $destination = DOC_ROOT . "temp/" . "folder2/zzz";
// 

/*
$source      = DOC_ROOT . "temp/";
$destination = DOC_ROOT . "temp2/";
Functions::recursive_copy($source, $destination);
exit("\n\n");
*/

/*
$source      = DOC_ROOT . "app/";
$destination = DOC_ROOT . "app/app2";
echo "\n" . filetype($source) . "\n";
echo "\n" . filetype($destination) . "\n";
exit;
*/

/*
$folder = "/";
$folder = DOC_ROOT . "/temp" . "/folder2";
$folder = CONTENT_RESOURCE_LOCAL_PATH;
Functions::file_rename($source, $destination);
exit("\n\n");
*/

/*
$id = "1256558";
$url = "http://www.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=basic&includeTree=true&taxId=$id";
if($json = Functions::lookup_with_cache($url)) {
    $rec = json_decode($json, true);
    print_r($rec); //exit; //good debug
    if($val = @$rec[$id]['parentid']) {
        print_r($rec[$val]); //$val is parent ID
    }
}
exit("\n-end-\n");
*/

// /* this test assumes that this folder exists: /resources/$resource_id
$resource_id = 81;
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
if($undefined = $func->check_if_all_parents_have_entries($resource_id, true)) { //2nd param True means write to text file
    $arr['parents without entries'] = $undefined;
    print_r($arr);
}
else echo "\nAll parents have entries OK\n";
exit("\n-end-\n");
// */

// /*
$func = new DWCADiagnoseAPI();
$resource_id = "71_big"; 
$tab_file = "media_resource.tab";
$tab_file = "agent.tab";

$func->investigate_extension($resource_id, $tab_file);
exit("\n-end-\n");
// */


// /* wikipedia in diff. languages from eol-archive:
$resource_id = "es"; $resource_id = "957"; $resource_id = "wikipedia-it"; $resource_id = "wikipedia-es"; $resource_id = "80";
$resource_id = "wikipedia-fr";
$func = new DWCADiagnoseAPI();
$func->check_unique_ids($resource_id);
exit;
// */

// /*
$resource_id = "EOL_12_multimedia";//355; //EOL_12_multimedia.tar.gz
$func = new DWCADiagnoseAPI();
$resource_id = 'EOL_12_multimedia'; //435609 26
Functions::count_resource_tab_files($resource_id);
names_breakdown('EOL_12_multimedia'); //26
exit;
// */

// /*
$resource_id = "primate-measurements";
$func = new DWCADiagnoseAPI();
Functions::count_resource_tab_files($resource_id, ".txt");
$result['undefined_uris'] = Functions::get_undefined_uris_from_resource($resource_id);
print_r($result);
exit("\n-end-\n");
// */

// /*
$resource_id = "991";
$func = new DWCADiagnoseAPI();
$arr = Functions::count_resource_tab_files($resource_id, ".txt");                       print_r($arr);
$result['undefined_uris'] = Functions::get_undefined_uris_from_resource($resource_id);  print_r($result);
exit("\n-end-\n");
// */


/* this is for 891.php NMNH media extension | https://eol-jira.bibalex.org/browse/DATA-1711
$func = new DWCADiagnoseAPI();
$resource_id = "891";
$irns = $func->get_irn_from_media_extension($resource_id);
echo "\ntotal $resource_id: ". count($irns);

// NMNH Botany (346) - not yet processed
$resources = array(120=>'NMNH IZ',176=>'NMNH Entomology',341=>'NMNH Birds',342=>'NMNH fishes',343=>'NMNH herpetology',344=>'NMNH mammals');

$debug = array();
$resource_ids = array_keys($resources);

foreach($resource_ids as $resource_id) {
    $name = $resources[$resource_id];
    $irns2 = $func->get_irn_from_media_extension($resource_id);
    echo "\ntotal $name: ". count($irns2);
    foreach($irns2 as $irn) {
        if(in_array($irn, $irns)) @$debug[$name]["found"]++;
        else                      @$debug[$name]["not found"]++;
    }
}
print_r($debug);
exit("\n-end-\n");
*/

// /*
$func = new DWCADiagnoseAPI();
$resource_id = "176";
$irns = $func->get_irn_from_media_extension($resource_id);
echo "\ntotal $resource_id: ". count($irns);

// NMNH Botany (346) - not yet processed
$resources = array(891=>'NMNH specimen resource');

$debug = array();
$resource_ids = array_keys($resources);

foreach($resource_ids as $resource_id) {
    $name = $resources[$resource_id];
    $irns2 = $func->get_irn_from_media_extension($resource_id);
    echo "\ntotal $name: ". count($irns2);
    foreach($irns2 as $irn) {
        if(in_array($irn, $irns)) @$debug[$name]["found"]++;
        else                      @$debug[$name]["not found"]++;
    }
}

print_r($debug);
exit("\n-end-\n");
// */

//=========================================================
// $func->check_unique_ids($resource_id); return;
//=========================================================
// $func->cannot_delete(); return;
//=========================================================
// $func->get_undefined_uris(); return;
//=========================================================
// $func->list_unique_taxa_from_XML_resource(544);
//=========================================================
// $func->count_rows_in_text_file(DOC_ROOT."applications/genHigherClass/temp/GBIF_Taxa_accepted_pruned_final.tsv"); //2133912 total recs
// $func->count_rows_in_text_file(DOC_ROOT."applications/genHigherClass/temp/GBIF_Taxa_accepted.tsv"); //3111830 total recs
//=========================================================

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function names_breakdown($resource_id)
{
    $filename = CONTENT_RESOURCE_LOCAL_PATH . "/$resource_id/taxon.tab";
    $i = 0;
    foreach(new FileIterator($filename) as $line_number => $line) {
        $i++;
        $arr = explode("\t", $line);
        if($i == 1) {
            $fields = $arr;
            // print_r($fields);
        }
        else {
            $k = 0;
            $rec = array();
            foreach($fields as $field) {
                if($val = @$arr[$k]) $rec[$field] = $val;
                $k++;
            }
            // print_r($rec); //exit;
            //start investigation here
            if($val = @$rec['taxonomicStatus']) $debug[$val]++;
        }
    }
    if(isset($debug)) print_r($debug);
}
?>