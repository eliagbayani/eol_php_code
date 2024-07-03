<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// /* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
$GLOBALS['ENV_DEBUG'] = false; //set to false in production
// */
// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */
$form = $_POST;
echo "<pre>"; print_r($form); echo "</pre>"; //exit("\neli 200\n");
/*Array(
    [taxonRank] => Array(
            [0] => Kingdom
            [1] => Phylum
            [2] => Class
            [3] => Order
            [4] => Superfamily
            [5] => Family
            [6] => Genus
            [7] => Species
        )
    [taxonomicStatus] => Array(
            [0] => accepted
            [1] => 
            [2] => misapplied
            [3] => 
        )
    [locality] => Array(
            [0] => 
            [1] => Australia
            [2] => 
            [19] => Western Atlantic Ocean
            [20] => Western Atlantic Ocean
        )
    [occurrenceStatus] => Array(
            [0] => Native
        )
)*/
$resource_id = @get_val_var('resource_id');
$temp_dir = @get_val_var('temp_dir');
// debug("\n[$resource_id][$temp_dir]\n");

$taxonRank_map          = generate_array_map($form, 'taxonRank'); //print_r($taxonRank_map); exit;
$taxonomicStatus_map    = generate_array_map($form, 'taxonomicStatus');
$locality_map           = generate_array_map($form, 'locality');
$occurrenceStatus_map   = generate_array_map($form, 'occurrenceStatus');

parse_TSV_file($temp_dir . 'Main_Table.txt', "");

function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
function get_text_contents($basename)
{
    $filename = $temp_dir.$basename.".txt"; echo "\nfilename: [$filename]\n";
    $contents = file_get_contents($filename);
    return explode("\n", $contents);
}
function generate_array_map($form, $table)
{
    $final = array();
    foreach($form[$table] as $whole) {
        $parts = explode("|", $whole);
        $final[$parts[0]] = $parts[1];
    }
    return $final;
}
function parse_TSV_file($txtfile, $task)
{   
    $i = 0; debug("\nUpdating: [$txtfile]\n");
    foreach(new FileIterator($txtfile) as $line_number => $line) {
        if(!$line) continue;
        $i++; if(($i % 1000) == 0) echo "\n".number_format($i)." ";
        $row = explode("\t", $line); // print_r($row);
        if($i == 1) {
            $fields = $row;
            $fields = array_filter($fields); //print_r($fields);
            continue;
        }
        else {
            $k = 0; $rec = array();
            foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
        }
        $rec = array_map('trim', $rec); //echo "<pre>";print_r($rec); echo "</pre>"; exit("\nstopx\n");
        /*Array(
            [scientific_nameID] => x4
            [parent_nameID] => 
            [accepted_nameID] => 
            [pre_name_usage] => accepted
            [name_usage] => 
            [unacceptability_reason] => 
            [rank_name] => Kingdom
            [taxon_author] => 
            [unit_name1] => 
            [unit_name2] => 
            [unit_name3] => 
            [unit_name4] => 
            [geographic_value] => 
            [origin] => 
            [referenceID] => d41d8cd98f00b204e9800998ecf8427e
        )*/

    }
}
?>