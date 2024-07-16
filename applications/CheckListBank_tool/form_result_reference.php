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

/* test
$arr = array("Name" => "Eli", "Age" => 52);
$json = json_encode($arr);
echo "\n$json\n";
exit("\n-end test-\n");
*/

$form = $_POST;
// echo "<pre>"; print_r($form); echo "</pre>"; //exit("\neli 200\n");
/*Array(
    [resource_id] => 1720970264
    [temp_dir] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/CheckListBank_files/1720970264/
    [temp_folder] => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/
    [Item_type] => Array(
            [0] => Journal Article
            [1] => Conference Paper
        )
    [ID] => Array(
            [0] => fd4d02f442272f88f5123b18d0e66e69
            [1] => 0556739d27e90cae8ac4efe76464af95
        )
    [dwc] => Array(
            [0] => Amsel HG, Hering M. Beitrag zur Kenntnis der Minenfauna Palästinas. Deutsche Entomologische Zeitschrift 1931: 113-152, pls 111-112. doi: 10.1002/mmnd.193119310203. (1931).
            [1] => Bedell G. Description of Microsetia quinquella, a new species of moth of the family Tineidae. Zoologist 6: 1986. (1848).
        )
    [reference_author] => Array(
            [0] => Amsel H.G.|Hering M.
            [1] => Bedell G.
        )
    [Editors] => Array(
            [0] => 
            [1] => 
        )
    [title] => Array(
            [0] => Beitrag zur Kenntnis der Minenfauna Palästinas
            [1] => Description of Microsetia quinquella, a new species of moth of the family Tineidae
        )
    [publication_name] => Array(
            [0] => Deutsche Entomologische Zeitschrift
            [1] => Zoologist
        )
    [actual_pub_date] => Array(
            [0] => 1931
            [1] => 1848
        )
    [pages] => Array(
            [0] => 113–152, 111–112
            [1] => 1986
        )
    [publisher] => Array(
            [0] => 
            [1] => 
        )
    [pub_place] => Array(
            [0] => 
            [1] => 
        )
    [URLs] => Array(
            [0] => http://www.jstor.org/pss/20490833.
            [1] => http://dx.doi.org/10.5962/bhl.title.35818.
        )
    [DOI] => Array(
            [0] => 10.1002/mmnd.193119310203.
            [1] => 10.5962/bhl.title.35818.
        )
    [Language] => Array(
            [0] => 
            [1] => 
        )
    [listed_pub_date] => Array(
            [0] => 
            [1] => 
        )
    [isbn] => Array(
            [0] => 
            [1] => 
        )
    [issn] => Array(
            [0] => 
            [1] => 
        )
    [pub_comment] => Array(
            [0] => 
            [1] => 
        )
)*/
$resource_id = @get_val_var('resource_id');
$temp_dir = @get_val_var('temp_dir');
$temp_folder = @get_val_var('temp_folder');
$included_fields = json_decode(@get_val_var('included_fields'), true);
// print_r($included_fields); exit;
unset($form['resource_id']);
unset($form['temp_dir']);
unset($form['temp_folder']);
unset($form['included_fields']);

$ref_info_list = generate_ref_info_list($form); //to be used in generating the Taxa_final.txt

echo "<pre>"; 
// echo "\n[".DOC_ROOT."]\n[$resource_id]\n[$temp_dir]\n[$temp_folder]";

//====================================== generate Taxa_final.txt
$source      = $temp_dir . 'Taxa.txt';
$destination = $temp_dir . 'Taxa_final.txt';
parse_TSV_file($source, $destination, $ref_info_list, $included_fields);
unset($ref_info_list);

//====================================== generate References_final.txt
$destination = $temp_dir . 'References_final.txt';
generate_References_final($form, $destination);

// /* ====================================== working OK - postponed ======================================
echo "\nCompressing...\n";
$source1 = $temp_dir.'Taxa_final.txt';
$source2 = $temp_dir.'References_final.txt';
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
// /* ====================================== end postponed ======================================

echo "</pre>";

// ====================================== START FUNCTIONS ======================================
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
function generate_ref_info_list($form)
{
    $fields = array_keys($form); //print_r($fields); exit;
    $i = -1;
    foreach($form['ID'] as $identifier) { $i++; //we try to generate json: e.g. {"Name":"Eli","Age":52}
        $tmp = "{";
        foreach($fields as $fld) { // ID dwc etc.
            $val = $form[$fld][$i];
            $tmp .= '"'.$fld.'":"'.$val.'",';
        }
        $tmp = substr($tmp, 0, -1); //very important to remove the "," ending comma.
        $tmp .= "}";
        $arr = json_decode($tmp, true); //print_r($arr); exit("\n[$tmp]\nstop 01\n");
        $final[$identifier] = $arr;
    }
    // echo "<pre>"; print_r($final); echo "</pre>"; exit("\nstopx\n");
    return $final;
}
function generate_References_final($form, $destination)
{
    $fields = array_keys($form); //print_r($fields); exit;
    $i = -1;
    foreach($form['ID'] as $identifier) { $i++; //we try to generate json: e.g. {"Name":"Eli","Age":52}
        $save = array();
        foreach($fields as $fld) { // ID dwc etc.
            $save[$fld] = $form[$fld][$i];
        }
        // ----- write -----
        // echo "<pre>"; print_r($save); echo "</pre>"; //exit;
        write_output_rec_2txt($save, $destination);
    }
    // echo "<pre>"; print_r($final); echo "</pre>"; exit("\nstopx\n");
}
function parse_TSV_file($txtfile, $destination, $ref_info_list, $included_fields)
{
    $i = 0; debug("\nLoading: [$txtfile]...creating final Taxa_final.txt\n");
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
            [name_usage] => accepted
            [unacceptability_reason] => 
            [rank_name] => Kingdom
            [taxon_author] => 
            [unit_name1] => 
            [unit_name2] => 
            [unit_name3] => 
            [unit_name4] => 
            [geographic_value] => 
            [origin] => 
            [referenceID] => 
        )*/
        // ===========================start saving
        $save = array();
        $fields = array_keys($rec); //print_r($fields);
        foreach($fields as $field) $save[$field] = $rec[$field];

        // /* append reference fields
        if($referenceID = $rec['referenceID']) {
            if($ref_array = @$ref_info_list[$referenceID]) {
                foreach($included_fields as $fld) $save[$fld] = $ref_array[$fld];
            }
            else {
                print_r($ref_info_list);
                print_r($rec);
                exit("\nShould not go here. referenceID not found in ref_info_list.\n");
            }
        }
        else {
            foreach($included_fields as $fld) $save[$fld] = '';
        }
        // */

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
?>