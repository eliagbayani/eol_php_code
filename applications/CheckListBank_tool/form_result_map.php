<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
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
$form = $_POST;
// echo "<pre>"; print_r($form); echo "</pre>"; //exit("\neli 200\n");
/*Array(
    [resource_id] => 1720884786
    [temp_dir] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/CheckListBank_files/1720884786/
    [temp_folder] => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/
    [accepted_or_valid] => accepted
    [taxonRank] => Array(
            [0] => Kingdom|Kingdom
            [1] => Phylum|Phylum
            [2] => Class|Class
            [3] => Order|Order
            [4] => Superfamily|Superfamily
            [5] => Family|Family
            [6] => Genus|Genus
            [7] => Species|Species
        )
    [taxonomicStatus] => Array(
            [0] => accepted|accepted
            [1] => ambiguous synonym|unavailable, database artifact
            [2] => misapplied|misapplied
            [3] => synonym|homonym & junior synonym
        )
    [comment_unacceptability_reason] => Array(
            [ambiguous synonym] => remark for ambiguous
            [misapplied] => remark for misapplied
            [synonym] => remark for junior syn
        )            
    [locality] => Array(
            [0] => Afrotropical region|
            [1] => Australian and Pacific regions|
            [2] => East Palearctic region|
            [3] => East Palearctic region (fossil)|
            [4] => Nearctic region|
            [5] => Nearctic region, Neotropical region|
            [6] => Neotropical region|
            [7] => Neotropical region (fossil)|
            [8] => Oriental region|
            [9] => Oriental region, East Palearctic region|
            [10] => Oriental region, West Palearctic region|
            [11] => West Palearctic region|
            [12] => West Palearctic region (fossil)|
            [13] => West Palearctic region, Afrotropical region|
            [14] => West Palearctic region, East Palearctic region|
            [15] => West Palearctic region, East Palearctic region, Nearctic region|
            [16] => West Palearctic region, East Palearctic region, Oriental region|
            [17] => West Palearctic region, East Palearctic region, [Nearctic region, Australian and Pacific regions]|
            [18] => West Palearctic region, East Palearctic region, [Nearctic region]|
            [19] => West Palearctic region, Nearctic region|
            [20] => West Palearctic region, [Nearctic region]|
        )
    [occurrenceStatus] => Array(
            [0] => Native|Native
        )
)*/
$resource_id = @get_val_var('resource_id');
$temp_dir = @get_val_var('temp_dir');
$temp_folder = @get_val_var('temp_folder');
$accepted_or_valid = @get_val_var('accepted_or_valid');

if($accepted_or_valid == 'accepted') $var_unaccepted = 'unaccepted';
if($accepted_or_valid == 'valid') $var_unaccepted = 'invalid';

// echo("\n[$resource_id][$temp_dir][$temp_folder]\n");


$arr_2_save = generate_array_comment_UR($form);
// echo "<pre>"; print_r($arr_2_save); echo "</pre>";
$WRITE = Functions::file_open($temp_dir . 'comment_unacceptability_reason.txt', "w");
fwrite($WRITE, json_encode($arr_2_save));
fclose($WRITE);

echo "<pre>"; 
// echo "\n[".DOC_ROOT."]\n[$resource_id]\n[$temp_dir]\n[$temp_folder]";
$taxonRank_map          = generate_array_map($form, 'taxonRank');        //print_r($taxonRank_map); //exit;
// print_r($taxonRank_map);
/*Array(
    [Kingdom] => Kingdom
    [Phylum] => Phylum
    [Class] => Class
    [Order] => Order
    [Superfamily] => Superfamily
    [Family] => Family
    [Genus] => Genus
    [Species] => Species
)*/
$taxonomicStatus_map    = generate_array_map($form, 'taxonomicStatus');  //print_r($taxonomicStatus_map);
$locality_map           = generate_array_map($form, 'locality');         //print_r($locality_map); 
$occurrenceStatus_map   = generate_array_map($form, 'occurrenceStatus'); //print_r($occurrenceStatus_map); //exit;

$source      = $temp_dir . 'Main_Table.txt';
$destination = $temp_dir . 'Taxa.txt';
parse_TSV_file($source, $destination, $taxonRank_map, $taxonomicStatus_map, $locality_map, $occurrenceStatus_map, $var_unaccepted);

/* ====================================== working OK - postponed ======================================
echo "\nCompressing...\n";
$source1 = $temp_dir.'Taxa.txt';
$source2 = $temp_dir.'References.txt';
$target_dir = str_replace("$resource_id/", "", $temp_dir);
$target = $target_dir."ITIS_format_$resource_id.zip";
// $output = shell_exec("gzip -cv $source1, $source2 > ".$target);
$output = shell_exec("zip -j $target $source1 $source2");
echo "\n$output\n";

if(stripos($output, "error") !== false) exit("\nError encountered:\n[$output]\nGo back to Main.\n");
echo "\nCompressed OK\n";
if(Functions::is_production()) $domain = "https://editors.eol.org";
else                           $domain = "http://localhost";
$rec['url'] = $domain.'/eol_php_code/applications/content_server/resources/Trait_Data_Import/'.$resource_id.'.tar.gz';

$final_zip_url = str_replace(DOC_ROOT, WEB_ROOT, $target);
echo "<br>Download ITIS-formatted file: [<a href='$final_zip_url'>".pathinfo($final_zip_url, PATHINFO_BASENAME)."</a>]<br><br>";

shell_exec("chmod 777 ".$temp_dir);
recursive_rmdir($temp_dir); //un-comment in real operation

$tmp_file = $temp_folder . $resource_id."_Distribution.tsv";    if(is_file($tmp_file)) unlink($tmp_file);
$tmp_file = $temp_folder . $resource_id."_Taxon.tsv";           if(is_file($tmp_file)) unlink($tmp_file);
?>
You can save this file to your computer.<br>
This file will remain in our server for two (2) weeks.<br>
<!--- <a href='javascript:history.go(-1)'> &lt;&lt; Update mapping</a><br> --->
<a href='main.php'> &lt;&lt; Back to menu</a>
<?php
*/ // ====================================== end postponed ======================================

echo "</pre>";
ini_set('memory_limit','14096M');
require_library('connectors/CheckListBankWebReference');
$func = new CheckListBankWebReference();
$params = array(
    'resource_id' => $resource_id,
    'temp_dir' => $temp_dir,
    'temp_folder' => $temp_folder,
);
$func->start($params);



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
        $final[$parts[0]] = @$parts[1];
    }
    return $final;
}
function parse_TSV_file($txtfile, $destination, $taxonRank_map, $taxonomicStatus_map, $locality_map, $occurrenceStatus_map, $var_unaccepted)
{
    $i = 0; debug("\nLoading: [".pathinfo($txtfile, PATHINFO_BASENAME)."]...creating final Taxa.txt\n");
    $WRITE = Functions::file_open($destination, "w"); fclose($WRITE);
    foreach(new FileIterator($txtfile) as $line_number => $line) {
        if(!$line) continue;
        $i++; //if(($i % 1000) == 0) echo "\n".number_format($i)." ";
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
            $save['name_usage'] = $var_unaccepted; //'unaccepted' or 'invalid'
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
            else                             $save['geographic_value'] = ""; //new: https://github.com/EOL/ContentImport/issues/14#issuecomment-2231523013
        }
        if($val = $save['origin']) {
            if($save['geographic_value'] == "") $save['origin'] = "";
            else {
                // if($val2 = @$occurrenceStatus_map[$val]) $save['origin'] = $val2;    //orig
                $save['origin'] = @$occurrenceStatus_map[$val];                         //now same with geographic_value
            }
        }
        // ----- write -----
        // echo "<pre>"; print_r($save); echo "</pre>"; //exit;
        unset($save['pre_name_usage']);
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
// [taxonomicStatus] => Array(
//     [0] => accepted|accepted
//     [1] => ambiguous synonym|subsequent name/combination
//     [2] => misapplied|misapplied
//     [3] => synonym|junior synonym
// )
// [comment_unacceptability_reason] => Array(
//     [ambiguous synonym] => remark for ambiguous
//     [misapplied] => remark for misapplied
//     [synonym] => remark for junior syn
// )
function generate_array_comment_UR($form)
{
    $tS = $form['taxonomicStatus'];
    $CUR = $form['comment_unacceptability_reason'];
    foreach($tS as $pipe) {
        $parts = explode("|", $pipe);
        $left = $parts[0];
        $right = $parts[1];
        $final[$right] = @$CUR[$left];
    }
    return $final;
}
?>