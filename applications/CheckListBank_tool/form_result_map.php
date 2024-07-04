<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// /* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
$GLOBALS['ENV_DEBUG'] = false; //set to false in production
// */
/* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
*/
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

echo "<pre>"; echo "\n[$resource_id]\n[$temp_dir]\n";
$taxonRank_map          = generate_array_map($form, 'taxonRank');        //print_r($taxonRank_map); //exit;
$taxonomicStatus_map    = generate_array_map($form, 'taxonomicStatus');  //print_r($taxonomicStatus_map);
$locality_map           = generate_array_map($form, 'locality');         //print_r($locality_map); 
$occurrenceStatus_map   = generate_array_map($form, 'occurrenceStatus'); //print_r($occurrenceStatus_map); //exit;
echo "</pre>";

$source      = $temp_dir . 'Main_Table.txt';
$destination = $temp_dir . 'Taxa.txt';
parse_TSV_file($source, $destination, $taxonRank_map, $taxonomicStatus_map, $locality_map, $occurrenceStatus_map);

echo "\nCompressing...\n";
$source1 = $temp_dir.'Taxa.txt';
$source2 = $temp_dir.'References.txt';
$target_dir = str_replace("$resource_id/", "", $temp_dir);
$target = $target_dir."ITIS_format_$resource_id.zip";
// $output = shell_exec("gzip -cv $source1, $source2 > ".$target);
$output = shell_exec("zip -j $target $source1 $source2");
echo "\noutput\nCompressed OK [".$target."]\n";


function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
function get_text_contents($basename)
{
    $filename = $temp_dir.$basename.".txt"; //echo "\nfilename: [$filename]\n";
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
function parse_TSV_file($txtfile, $destination, $taxonRank_map, $taxonomicStatus_map, $locality_map, $occurrenceStatus_map)
{
    $i = 0; debug("\nLoading: [$txtfile]...creating final Taxa.txt\n");
    $WRITE = Functions::file_open($destination, "w"); fclose($WRITE);
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
        // ===========================start saving
        $save = array();
        $fields = array_keys($rec); //print_r($fields);
        foreach($fields as $field) $save[$field] = $rec[$field];
        // ----- 1st -----
        if(in_array($save['pre_name_usage'], array('accepted', 'valid'))) {
            $save['name_usage'] = $save['pre_name_usage'];
            $save['unacceptability_reason'] = ''; //must be blank
        }    
        elseif(in_array($save['pre_name_usage'], array('unaccepted', 'not accepted', 'invalid'))) { //unacceptability_reason should be populated
            $save['name_usage'] = $save['pre_name_usage'];
            $save['unacceptability_reason'] = '';
        }
        elseif($val = $save['pre_name_usage']) {
            $save['name_usage'] = 'unaccepted';
            $save['unacceptability_reason'] = $val;
        }
        // ----- 2nd ----- name_usage | unacceptability_reason
        if($val = $save['name_usage']) {
            if($val2 = @$taxonomicStatus_map[$val]) $save['name_usage'] = $val2;
        }
        if($val = $save['unacceptability_reason']) {
            if($val2 = @$taxonomicStatus_map[$val]) $save['unacceptability_reason'] = $val2;
        }
        // ----- 3rd ----- rank_name | geographic_value | origin
        if($val = $save['rank_name']) {
            if($val2 = @$taxonRank_map[$val]) $save['rank_name'] = $val2;
        }
        if($val = $save['geographic_value']) {
            if($val2 = @$locality_map[$val]) $save['geographic_value'] = $val2;
        }
        if($val = $save['origin']) {
            if($val2 = @$occurrenceStatus_map[$val]) $save['origin'] = $val2;
        }
        // ----- write -----
        // echo "<pre>"; print_r($save); echo "</pre>"; //exit;
        write_output_rec_2txt($save, $destination);
    } //end foreach()
}
function write_output_rec_2txt($rec, $filename)
{   
    $fields = array_keys($rec);
    $WRITE = Functions::file_open($filename, "a");
    clearstatcache(); //important for filesize()
    if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
    $save = array();
    foreach($fields as $fld) {
        if(is_array($rec[$fld])) { //if value is array()
            $rec[$fld] = self::clean_array($rec[$fld]);
            $rec[$fld] = implode(", ", $rec[$fld]); //convert to string
            $save[] = trim($rec[$fld]);
        }
        else $save[] = $rec[$fld];
    }
    $tab_separated = (string) implode("\t", $save); 
    fwrite($WRITE, $tab_separated . "\n");
    fclose($WRITE);
}
?>