<?php
namespace php_active_record;
/* this is for the Carnivora dataset only */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/SummaryDataResourcesAPI');

// $a = array(2,3,4); print_r($a);
// array_unshift($a, 1); print_r($a);
// exit;

/*
$a = array(5319, 1905, 2774383, 8814528, 1, 2910700, 2908256, 2913056);     
$a = array_reverse($a); print_r($a);
$temp = $a;
foreach($a as $id) {
    array_shift($temp);
    if(isset($children_of[$id])) $children_of[$id] = array_merge($children_of[$id], $temp);
    else                         $children_of[$id] = $temp;
    $children_of[$id] = array_unique($children_of[$id]);
}

// $a = Array(5110, 5083, 1905, 2774383, 8814528, 1, 2910700, 2908256, 2913056);
// $a = array_reverse($a);                                                     print_r($a);
// $temp = $a;
// foreach($a as $id) {
//     array_shift($temp);
//     if(isset($children_of[$id])) $children_of[$id] = array_merge($children_of[$id], $temp);
//     else                         $children_of[$id] = $temp;
//     $children_of[$id] = array_unique($children_of[$id]);
// }

print_r($children_of);
exit("\n");
*/

/*
$str = "http://purl.obolibrary.org/obo/ENVO_00000020, http://purl.obolibrary.org/obo/ENVO_00000043, http://purl.obolibrary.org/obo/ENVO_00000065, http://purl.obolibrary.org/obo/ENVO_00000067, 
http://purl.obolibrary.org/obo/ENVO_00000081, http://purl.obolibrary.org/obo/ENVO_00000086, http://purl.obolibrary.org/obo/ENVO_00000220, http://purl.obolibrary.org/obo/ENVO_00000264, 
http://purl.obolibrary.org/obo/ENVO_00000360, http://purl.obolibrary.org/obo/ENVO_00000446, http://purl.obolibrary.org/obo/ENVO_00001995, http://purl.obolibrary.org/obo/ENVO_00002000, 
http://purl.obolibrary.org/obo/ENVO_00002033, http://purl.obolibrary.org/obo/ENVO_01000206, http://purl.obolibrary.org/obo/ENVO_01001305, http://purl.obolibrary.org/obo/ENVO_00000078, 
http://purl.obolibrary.org/obo/ENVO_00000113, http://purl.obolibrary.org/obo/ENVO_00000144, http://purl.obolibrary.org/obo/ENVO_00000261, http://purl.obolibrary.org/obo/ENVO_00000316, 
http://purl.obolibrary.org/obo/ENVO_00000320, http://purl.obolibrary.org/obo/ENVO_00000358, http://purl.obolibrary.org/obo/ENVO_00000486, http://purl.obolibrary.org/obo/ENVO_00000572, 
http://purl.obolibrary.org/obo/ENVO_00000856, http://purl.obolibrary.org/obo/ENVO_00002030, http://purl.obolibrary.org/obo/ENVO_00002040, http://purl.obolibrary.org/obo/ENVO_01000204, 
http://purl.obolibrary.org/obo/ENVO_00000002, http://purl.obolibrary.org/obo/ENVO_00000016, http://eol.org/schema/terms/temperate_grasslands_savannas_and_shrublands, 
http://purl.obolibrary.org/obo/ENVO_01001125";

$arr = explode(",", $str);
$arr = array_map('trim', $arr);
asort($arr); print_r($arr); 

echo "\n rows: ".count($arr);
foreach($arr as $tip) echo "\n$tip";
exit("\ntotal: ".count($arr)."\n");
*/

/* //tests
$parents = array(1,2,3);
$preferred_terms = array(4,5);
$inclusive = array_merge($parents, $preferred_terms);
print_r($inclusive);
exit("\n-end tests'\n");
*/

/*
$arr = json_decode('["717136"]');
if(!is_array($arr) && is_null($arr)) {
    $arr = array();
    echo "\nwent here 01\n";
}
else {
    echo "\nwent here 02\n";
    print_r($arr);
}
exit("\n");
*/

/*
$a1 = array('45511473' => Array(46557930));
$a2 = array('308533' => Array(1642, 46557930));
$a3 = $a1 + $a2; print_r($a3);
exit("\n");
*/

// $json = "[]";
// $arr = json_decode($json, true);
// if(is_array($arr)) echo "\nis array\n";
// else               echo "\nnot array\n";
// if(is_null($arr)) echo "\nis null\n";
// else               echo "\nnot null\n";
// print_r($arr);
// // if(!is_array($arr) && is_null($arr)) $arr = array();
// exit("\n");

// $file = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/99/cd/R96-PK42697173.txt";
// $file = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/38/49/R344-PK19315117.txt";
// $json = file_get_contents($file);
// print_r(json_decode($json, true)); exit;

// $terms = array("Braunbär", " 繡球菌", "Eli");
// foreach($terms as $t){
//     echo "\n".$t."\n";
//     // $t = utf8_encode($t); echo "\n".$t."\n";
//     $t = Functions::conv_to_utf8($t); echo "\n".$t."\n";
// }
// exit("\nexit muna\n");

$timestart = time_elapsed();
$resource_id = 'SDR';
$func = new SummaryDataResourcesAPI($resource_id);

// $func->generate_page_id_txt_files();        return; //important initial step
// $func->generate_children_of_taxa_usingDH(); return; //the big long program                  _ids/56/97/10594877 - check this later  _ids/85/70/2634372_c.t
// $func->generate_refs_per_eol_pk();          return; //important step for counting refs per eol_pk

// $func->test_basal_values();          return;
// $func->print_basal_values();         return;
// $func->test_parent_basal_values();   return;
// $func->print_parent_basal_values();  return;

// $func->test_taxon_summary();         return;
// $func->print_taxon_summary();        return;
// $func->test_parent_taxon_summary();  return;        //[7665], http://purl.obolibrary.org/obo/RO_0002470
// $func->print_parent_taxon_summary(); return;

// $func->print_lifeStage_statMeth();   return;

$func->start();
// Functions::finalize_dwca_resource($resource_id);
$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>
