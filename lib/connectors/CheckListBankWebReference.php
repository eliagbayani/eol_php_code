<?php
namespace php_active_record;
/* Warning: Unknown: Multipart body parts limit exceeded 50000. To increase the limit change max_multipart_body_parts in php.ini. in Unknown on line 0
   Warning: Unknown: Input variables exceeded 50000. To increase the limit change max_input_vars in php.ini. in Unknown on line 0
   Both were set to 100000. */
class CheckListBankWebReference
{
    function __construct()
    {
    }
    function start($params)
    {   // echo "<pre>"; print_r($params); echo "</pre>";
        /*Array(
            [resource_id] => 1720883775
            [temp_resource_dir] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/CheckListBank_files/1720883775/
            [temp_tool_folder] => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/
        )*/
        $this->resource_id = $params['resource_id'];
        $this->temp_dir = $params['temp_dir'];
        $this->temp_folder = $params['temp_folder'];
        self::create_web_form_reference();

        // -listed_pub_date
        // -isbn
        // -issn
        // -pub_comment                
    }
    function create_web_form_reference()
    {
        // echo "\ndito na siya...\n";
        $references = self::get_text_contents('References');
        $fields = explode("\t", $references[0]);
        // additional fields:
        $fields[] = 'listed_pub_date';
        $fields[] = 'isbn';
        $fields[] = 'issn';
        $fields[] = 'pub_comment';

        array_shift($references);
        $included_fields = array("reference_author", "title", "publication_name", "actual_pub_date", "listed_pub_date", "publisher", "pub_place", "pages", "isbn", "issn", "pub_comment");
        // included_fields: the 11 Jen fields

        echo "<pre>";
        // $fields[0] = "Item_type";
        print_r($fields); //echo"<hr>"; print_r($references);
        ?>
        <table border="1" cellpadding="15" cellspacing="1" align="center" width="40%">
        <tr align="center"><td><b>CheckListBank Tool</b></td></tr>
        <form action="form_result_reference.php" method="post" enctype="multipart/form-data">
        <tr><td>
                <font size="3">Mapping Exercise</font>
                <?php echo "ID: $this->resource_id <br>
                <input type='hidden' name='resource_id' value='$this->resource_id'>
                <input type='hidden' name='temp_dir' value='$this->temp_dir'>
                <input type='hidden' name='temp_folder' value='$this->temp_folder'>
                "; 
                ?>
        </td></tr>
        <tr align="center">
            <td><!--- Array(
                    [0] => Item type
                    [1] => ID
                    [2] => dwc
                    [3] => reference_author
                    [4] => Editors
                    [5] => title
                    [6] => publication_name
                    [7] => actual_pub_date
                    [8] => pages
                    [9] => publisher
                    [10] => pub_place
                    [11] => URLs
                    [12] => DOI
                    [13] => Language
                ) --->
                <table border='0'><tr rowspan='2'><td align='center' colspan='2'><b>References</b><br>&nbsp;</td></tr><?php
                $rows = -1;
                foreach($references as $r) { $rows++;
                    $ref = explode("\t", $r); //exit;
                    $i = -1; foreach($fields as $fld) { $i++; $rek[$fld] = @$ref[$i]; } //assignment

                    // echo "<tr><td colspan='2'>";print_r($rek); echo "</td><tr>"; //debug only

                    echo "<tr><td colspan='2'>$rek[dwc]</td><tr>";                    
                    foreach($rek as $fld => $val) {
                        $type = 'text';
                        if(in_array($fld, array('ID', 'dwc'))) {
                            echo "<input type='hidden' name='".$fld."[$rows]' value='$val'>"; continue;
                        }
                        $bgcolor = 'white';
                        if(in_array($fld, $included_fields)) $bgcolor = 'yellow';
                        echo "<tr bgcolor='$bgcolor'>";
                        echo "<td>$fld</td><td width='85%'><input type='$type' name='".$fld."[$rows]' value='$val' size='100'></td>";
                        echo "</tr>";
                    }
                    echo "<tr><td colspan='2'><hr></td><tr>";
                    // if($rows >= 3) break; //debug only
                }


                // foreach($references as $r) { $i++;
                //         ?><tr><?php
                //         if(!$r) continue;
                //         echo "<td>$r</td>";
                //         echo "<td>
                //             <select name='taxonRank[$i]' id='tR'>";
                //             foreach($this->map['taxonRank'] as $tr) {
                //                 if($r == $tr) $selected = "selected";
                //                 else          $selected = "";
                //                 echo "<option value='$r|$tr' $selected >$tr</option>";
                //             }
                //             echo'</select>
                //         </td>';                            
                //         ?></tr><?php
                // } //end foreach()
                ?></table>
            </td>
        </tr>

        <tr><td align='center' colspan="2">
                <input type="submit" value=" Submit ">
                <input type="reset" value=" Reset ">
                <a href='javascript:history.go(-2)'> &lt;&lt; Back to menu</a>
        </td></tr>
        </form>
        </table>
        <?php echo "</pre>";
    }
    private function get_text_contents($basename)
    {
        $filename = $this->temp_dir.$basename.".txt"; //echo "\nfilename: [$filename]\n";
        $contents = file_get_contents($filename);
        return explode("\n", $contents);
    }
}
?>