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
$time_var = time();

$dir = DOC_ROOT.'/applications/CheckListBank_tool/temp/';
if(!is_dir($dir)) mkdir($dir);


// echo "<pre>"; print_r($_FILES); exit("</pre>");
$form = $_POST;
// echo "<pre>"; print_r($form); echo "</pre>"; exit("\neli 200\n");
/*Array(
    [form_url] => 
    [Filename_ID] => 111
)*/

// /* Filename_ID check if doesn't exist in OpenData. If doesn't exist, stop operation now.
if($Filename_ID = @get_val_var('Filename_ID')) {
    require_library('connectors/TraitDataImportAPI');
    $func = new TraitDataImportAPI('trait_data_import');
    if($resource_id = $func->get_ckan_resource_id_given_hash("hash-".$Filename_ID)) {} //continue;
    else exit("<hr>Upload ID [$Filename_ID] does not exist. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}
// */

/* 1st try --- problematic
if($val = @$form['Filename_ID']) $time_var = $val;
else                             $time_var = time();
*/


if($file_type = @$_FILES["file_upload2"]["type"]) {} // Darwin Core Archive
else {
    if($file_type = @$_FILES["file_upload"]["type"] && $file_type = @$_FILES["file_upload3"]["type"]) {} //Taxa File Reference file
    else exit("<hr>Please select a file to continue. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}


$form_url = @get_val_var('form_url');
if($form_url) { //URL is pasted.
    $orig_file = pathinfo($form_url, PATHINFO_BASENAME);
    $newfile = $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
    
    if(!in_array(pathinfo($form_url, PATHINFO_EXTENSION), array('xls', 'xlsx', 'zip'))) exit("\nERROR: Wrong file format.\n\n");
    // print_r(pathinfo($form_url)); exit;
    
    /* good debug
    echo "<hr>form_url: [$form_url]";
    echo "<hr>orig_file: [$orig_file]";
    echo "<hr>newfile: [$newfile]<hr>";
    // exit;
    */
}
elseif($file_type = @$_FILES["file_upload2"]["type"]) { // Darwin Core Archive
    echo "<br>File type is: [$file_type]<br>";
    debug("<br>orig_file: [".$_FILES["file_upload2"]["name"]."]<br>"); debug("<br>file type: [".$file_type."]<br>");
    $allowed_file_types = array("application/x-gzip", "application/zip", "application/x-zip-compressed", "application/x-zip"); //.tar.gz and .zip
    if(in_array($file_type, $allowed_file_types)) {
        if($_FILES["file_upload2"]["error"] > 0) {}
        else {
            $orig_file = $_FILES["file_upload2"]["name"];
            $url = "temp/" . $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload2"]["tmp_name"] , $url)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile = "temp/" . $time_var . "." . pathinfo($orig_file, PATHINFO_EXTENSION); //e.g. temp/1687711391.gz
        // exit("\n[$newfile]\n");

        // /* ---------- Added block:
        $dwca_full_path = DOC_ROOT."/applications/CheckListBank_tool/".$newfile;
        if($download_directory = ContentManager::download_temp_file_and_assign_extension($dwca_full_path, "")) { //added 2nd blank param to suffice: "Warning: Missing argument 2"
            debug("<br>newfile = [$newfile]<br>download_directory:[$download_directory]<br>"); //exit("\nstop 2\n");
            // $download_directory = '/Library/WebServer/Webroot/eol_php_code/applications/content_server/tmp/9f508e44e8038fb56bbc0c9b34eb3ac7';
            if(is_dir($download_directory) && file_exists($download_directory ."/meta.xml")) {
                // echo "\n[$download_directory]\n"; exit;
                $taxon_file        = get_file_from_DwCA($download_directory ."/meta.xml", "http://rs.tdwg.org/dwc/terms/Taxon"); //taxon.tab
                $distribution_file = get_file_from_DwCA($download_directory ."/meta.xml", "http://rs.gbif.org/terms/1.0/Distribution"); //taxon.tab

                copy_file_now($taxon_file, $download_directory, $time_var);
                copy_file_now($distribution_file, $download_directory, $time_var);

                // /* deleting temp folder in: eol_php_code/applications/content_server/tmp/
                $basename = pathinfo($download_directory, PATHINFO_BASENAME); //9f508e44e8038fb56bbc0c9b34eb3ac7
                if(strlen($basename) == 32 && is_dir($download_directory)) recursive_rmdir($download_directory);
                else exit("<br>ERROR: Cannot delete temporary folder in [/content_server/tmp/]<br>");
                // */

            }
            else exit("<hr>ERROR: Cannot proceed. DwCA doesn't have meta.xml [$download_directory]. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
        }
        else exit("<hr>ERROR: Cannot proceed. File is lost [$dwca_full_path]. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
        // ---------- */
    }
    else exit("<hr><i>$file_type</i><hr>Invalid file type. Please email this file type to eagbayani@eol.org<br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");    
}


if($file_type = @$_FILES["file_upload"]["type"]) { //Taxa File
    debug("<br>orig_file: [".$_FILES["file_upload"]["name"]."]<br>");
    debug("<br>file type: [".$file_type."]<br>"); 
    // echo "<pre>"; print_r($_FILES); echo "</pre>"; exit; //good debug
    /*
    [taxon.tab] [application/octet-stream]
    [taxon.tsv] [text/tab-separated-values]
    [taxon.txt] [text/plain]
    [taxon.csv] [text/csv]
    */
    $allowed_file_types = array("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/vnd.ms-excel", "application/zip");
    $allowed_file_types = array("application/octet-stream", "text/tab-separated-values", "text/plain", "text/csv", "application/zip"); //

    if(in_array($file_type, $allowed_file_types)) {
        if($_FILES["file_upload"]["error"] > 0) {}
        else {
            $orig_file = $_FILES["file_upload"]["name"];
            $url = "temp/" . $time_var . "_orig_taxa." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload"]["tmp_name"] , $url)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile = "temp/" . $time_var . "_orig_taxa." . pathinfo($orig_file, PATHINFO_EXTENSION);

        // echo "<hr>file_type: [$file_type]";
        // echo "<hr>orig_file: [$orig_file]";
        // echo "<hr>url: [$url]";
        // echo "<hr>newfile: [$newfile]<hr>"; //exit;

        // /* ---------- Added block: 
        if(pathinfo($newfile, PATHINFO_EXTENSION) == "zip") $newfile = local_unzip_file($newfile);
        // ---------- */
        // echo "<hr>newfile: [$newfile]<hr>"; //exit;
        $orig_taxa_file = pathinfo($newfile, PATHINFO_BASENAME); //will be used in jenkins_call.php


        /* copied template but not used in CheckListBank
        ---------- Added block:
        if(strtolower(pathinfo($newfile, PATHINFO_EXTENSION)) == 'csv') { // echo "<br>csv nga<br>";
            require_library('connectors/CheckListBankRules');
            require_library('connectors/CheckListBankAPI');
            $func = new CheckListBankAPI('CheckListBank_tool');
            $newfile = $func->convert_csv2tsv($newfile); // exit("\n[$newfile]\n");
        }
        // else echo "<br>hindi csv<br>";
        // print_r(pathinfo($newfile)); exit("<br>$newfile<br>stop 1<br>");
        ---------- */
    }
    else exit("<hr>$file_type<hr>Invalid file type. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");
}
if($file_type = @$_FILES["file_upload3"]["type"]) { // Reference file
    debug("<br>orig_file: [".$_FILES["file_upload3"]["name"]."]<br>"); debug("<br>file type: [".$file_type."]<br>");
    $allowed_file_types = array("text/plain", "application/zip");
    $allowed_file_types = array("application/octet-stream", "text/tab-separated-values", "text/plain", "text/csv", "application/zip"); //

    if(in_array($file_type, $allowed_file_types)) {
        if($_FILES["file_upload3"]["error"] > 0) {}
        else {
            $orig_file = $_FILES["file_upload3"]["name"];
            $url = "temp/" . $time_var . "_orig_reference." . pathinfo($orig_file, PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES["file_upload3"]["tmp_name"] , $url)) {
                debug("<br>file uploaded - OK<br>");
            }
            else echo "<br>uploading file - ERROR<br>";
        }
        $newfile = "temp/" . $time_var . "_orig_reference." . pathinfo($orig_file, PATHINFO_EXTENSION);

        // echo "<hr>file_type: [$file_type]";
        // echo "<hr>orig_file: [$orig_file]";
        // echo "<hr>url: [$url]";
        // echo "<hr>newfile: [$newfile]<hr>"; //exit;

        // /* ---------- Added block:
        if(pathinfo($newfile, PATHINFO_EXTENSION) == "zip") $newfile = local_unzip_file($newfile);
        // ---------- */
        // echo "<hr>newfile: [$newfile]<hr>"; //exit;
        $orig_reference_file = pathinfo($newfile, PATHINFO_BASENAME); //will be used in jenkins_call.php


        /* copied template but not used in CheckListBank
        ---------- Added block: 
        require_library('connectors/CheckListBankRules');
        require_library('connectors/CheckListBankAPI');
        if(pathinfo($newfile, PATHINFO_EXTENSION) == "zip") { //e.g. taxa_list.txt.zip
            $func1 = new CheckListBankAPI('CheckListBank_tool');
            $newfile = $func1->process_zip_file(str_replace("temp/", "", $newfile)); // exit("\n[$newfile]\n");

            $newfile = "temp/".$newfile;
            $func2 = new CheckListBankRules();
            $func2->add_header_to_file($newfile, "scientificName");
        }
        else { //e.g. taxa_list.txt
            $func2 = new CheckListBankRules();
            $func2->add_header_to_file($newfile, "scientificName");
        }
        ---------- */
    }
    else exit("<hr>$file_type<hr>Invalid file type. <br> <a href='javascript:history.go(-1)'> &lt;&lt; Go back</a><hr>");    
}

// exit("<br>stop muna<br>");

/* replaced by Jenkins call
print "<br><b>Processing, please wait...</b><br><hr>";
print"<META HTTP-EQUIV='Refresh' Content='0; URL=generate.php?file=$newfile&orig_file=$orig_file'>";
exit;
*/

if(Functions::is_production()) {
    $for_DOC_ROOT =  '/var/www/html/eol_php_code/'; //'/html/eol_php_code/';
    $true_DOC_ROOT = DOC_ROOT;
}
else {
    $for_DOC_ROOT = DOC_ROOT;
    $true_DOC_ROOT = $for_DOC_ROOT;
}

// if($form_url)   require_once("jenkins_call_4url.php");
// else            require_once("jenkins_call.php");

$form = $_POST;

require_once("jenkins_call.php");

function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
function get_file_from_DwCA($meta_xml, $rowType)
{   // echo "<br>meta.xml path: [$meta_xml]<br>";
    $xml = file_get_contents($meta_xml);
    /* e.g. meta.xml contents:
    <table encoding="UTF-8" fieldsTerminatedBy="\t" linesTerminatedBy="\n" ignoreHeaderLines="1" rowType="http://rs.tdwg.org/dwc/terms/Taxon">
    <files>
      <location>taxon.tab</location>
    </files>    
    */
    // $left = 'rowType="http://eol.org/schema/media/Document"'; //just testing, should get e.g. "media_resource.tab"
    $left = 'rowType="'.$rowType.'"';
    if(preg_match("/".preg_quote($left, '/')."(.*?)<\/files>/ims", $xml, $arr)) {
        if(preg_match("/<location>(.*?)<\/location>/ims", $arr[1], $arr2)) return $arr2[1]; //e.g. "taxon.tab"
    }
}
function copy_file_now($some_file, $download_directory, $time_var)
{
    debug("<br>file: [$some_file]<br>");                
    $source = $download_directory ."/".$some_file;
    // $destination = "temp/" . $time_var . "." . pathinfo($some_file, PATHINFO_EXTENSION); //obsolete
    $destination = "temp/" . $time_var . "_" . $some_file;
    if(copy($source, $destination)) {
        debug("<br>Copied OK<br>source: [$source]<br>destination: [$destination]<br>");
        $newfile = $destination; //success OK
    }
    else exit("<br>ERROR: Investigate, file copy failed [$source] [$destination]<br>");
}
function local_unzip_file($newfile)
{
    require_library('connectors/CheckListBankWeb');
    require_library('connectors/CheckListBankRules');
    require_library('connectors/CheckListBankAPI');    
    $func1 = new CheckListBankAPI('CheckListBank_tool');
    $newfile = $func1->process_zip_file(str_replace("temp/", "", $newfile)); // exit("\n[$newfile]\n");
    return $newfile;
}
?>