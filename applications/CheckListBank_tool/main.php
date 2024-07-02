<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$browser = Functions::getBrowser(); // echo "Browser: " . $browser;
if($browser == 'Firefox') $browser_comment = "Browse...";
else                      $browser_comment = "Choose File"; //Safari Chrome etc
?>
<table border="1" cellpadding="15" cellspacing="1" align="center" width="40%">
    <tr align="center"><td><b>CheckListBank Tool</b></td></tr>
    <form action="form_result.php" method="post" enctype="multipart/form-data">
    <!---
    <tr><td>
            <font size="3">
            <b>Taxa File</b> (plain text, tsv or csv): a taxa file in Darwin Core Archive format, but without a meta.xml file. 
            In this case, the file should have headers that we can use to infer the mapping for each column. <br><br>
            Upload user file: </font><input type="file" name="file_upload" id="file_upload" size="100">
            <br><br><small>(.tab or .tsv or .txt or .csv) OR (.tab.zip, .tsv.zip, .txt.zip, .csv.zip)</small>
    </td></tr>
    --->
    <tr><td>
            <font size="3">
            <b>Darwin Core Archive</b> <br><br>
            Upload user file: </font><input type="file" name="file_upload2" id="file_upload2" size="100">
            <br><br><small>(.tar.gz or .zip)</small>
    </td></tr>
    <!---
    <tr><td>
            <font size="3">
            <b>Taxa List</b> (plain text, txt): a simple, one column document, with one name per line. 
            We will treat the content of this column as a scientificName value. <br><br>
            Upload user file: </font><input type="file" name="file_upload3" id="file_upload3" size="100">
            <br><br><small>(.txt) OR (.txt.zip)</small>
    </td></tr>
    --->
    <tr align="center">
        <td>
            <input type='text' name='Filename_ID' hidden>
            <input type='text' name='Short_Desc' hidden>
            <input type="submit" value="Run">
            <input type="reset" value="Reset">
        </td>
    </tr>
    </form>
</table>
