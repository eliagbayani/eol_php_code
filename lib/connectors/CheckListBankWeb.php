<?php
namespace php_active_record;
class CheckListBankWeb
{
    function __construct()
    {
    }
    function create_web_form()
    {
        // echo "\ndito na siya...\n";
        $taxonRanks = self::get_text_contents('taxonRank');
        $taxonomicStatuses = self::get_text_contents('taxonomicStatus');
        if(in_array('accepted', $taxonomicStatuses) || 
           in_array('unaccepted', $taxonomicStatuses) ||
           in_array('not accepted', $taxonomicStatuses)) {
            $taxonomicStatus_map = $this->map['taxonomicStatus']['accepted_not_accepted'];
            $accepted_or_valid = 'accepted';
        }
        elseif(in_array('valid', $taxonomicStatuses) || 
               in_array('invalid', $taxonomicStatuses)) {
            $taxonomicStatus_map = $this->map['taxonomicStatus']['valid_invalid'];
            $accepted_or_valid = 'valid';
        }
        else echo "\nWarning: No mapping for taxonomicStatus.\n";
        
        $localities = self::get_text_contents('locality');
        $occurrenceStatuses = self::get_text_contents('occurrenceStatus');

        ?>
        <table border="1" cellpadding="15" cellspacing="1" align="center" width="40%">
        <tr align="center"><td><b>CheckListBank Tool</b></td></tr>
        <form action="form_result_map.php" method="post" enctype="multipart/form-data">
        <tr><td>
                <font size="3">Mapping Exercise</font>
                <?php echo "ID: $this->resource_id <br>
                <input type='hidden' name='resource_id' value='$this->resource_id'>
                <input type='hidden' name='temp_dir' value='$this->temp_dir'>
                <input type='hidden' name='temp_folder' value='$this->temp_folder'>
                <input type='hidden' name='accepted_or_valid' value='$accepted_or_valid'>
                "; 

                ?>
        </td></tr>
        <tr align="center">
            <td>
                <table><tr rowspan='2'><td align='center' colspan='2'><b>rank_name</b><br>&nbsp;</td></tr><?php
                $i = -1;
                foreach($taxonRanks as $r) { $i++;
                        ?><tr><?php
                        if(!$r) continue;
                        echo "<td>$r</td>";
                        echo "<td>
                            <select name='taxonRank[$i]' id='tR'>";
                            foreach($this->map['taxonRank'] as $tr) {
                                if($r == $tr) $selected = "selected";
                                else          $selected = "";
                                echo "<option value='$r|$tr' $selected >$tr</option>";
                            }
                            echo'</select>
                        </td>';                            
                        ?></tr><?php
                } //end foreach()
                ?></table>
            </td>
        </tr>

        <tr align="center">
            <td>
                <table><tr rowspan='2'><td align='center' colspan='2'><b>name_usage | unacceptability_reason</b><br>&nbsp;</td></tr><?php
                $i = -1;
                foreach($taxonomicStatuses as $r) { $i++;
                        ?><tr><?php
                        if(!$r) continue;
                        echo "<td>$r</td>";
                        echo "<td>
                            <select required name='taxonomicStatus[$i]' id='tS'>";?>
                            <option value=""></option><?php
                            if(in_array($r, array('accepted', 'not accepted', 'unaccepted', 'valid', 'invalid'))) {
                                foreach($taxonomicStatus_map as $tr) {
                                    if($r == $tr) $selected = "selected";
                                    else          $selected = "";
                                    echo "<option value='$r|$tr' $selected >$tr</option>";
                                }    
                            }
                            else {
                                foreach($this->map['unacceptability_reason'] as $tr) {
                                    if($r == $tr) $selected = "selected";
                                    else          $selected = "";
                                    echo "<option value='$r|$tr' $selected >$tr</option>";
                                }    
                            }
                            echo'</select>
                        </td>';                            
                        ?></tr><?php
                } //end foreach()
                ?></table>
            </td>
        </tr>

        <tr align="center">
            <td>
                <table><tr rowspan='2'><td align='center' colspan='2'><b>geographic_value</b><br>&nbsp;</td></tr><?php
                $i = -1;
                foreach($localities as $r) { $i++;
                        ?><tr><?php
                        if(!$r) continue;
                        echo "<td>$r</td>";
                        echo "<td>
                            <select name='locality[$i]' id='l'>";
                            foreach($this->map['locality'] as $tr) {
                                if($r == $tr) $selected = "selected";
                                else          $selected = "";
                                echo "<option value='$r|$tr' $selected >$tr</option>";
                            }
                            echo'</select>
                        </td>';                            
                        ?></tr><?php
                } //end foreach()
                ?></table>
            </td>
        </tr>

        <tr align="center">
            <td>
                <table><tr rowspan='2'><td align='center' colspan='2'><b>origin</b><br>&nbsp;</td></tr><?php
                $i = -1;
                foreach($occurrenceStatuses as $r) { $i++;
                        ?><tr><?php
                        if(!$r) continue;
                        echo "<td>$r</td>";
                        echo "<td>
                            <select name='occurrenceStatus[$i]' id='oS'>";
                            foreach($this->map['occurrenceStatus'] as $tr) {
                                if($r == $tr) $selected = "selected";
                                else          $selected = "";
                                echo "<option value='$r|$tr' $selected >$tr</option>";
                            }
                            echo'</select>
                        </td>';                            
                        ?></tr><?php
                } //end foreach()
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
        <?php
    }
    private function get_text_contents($basename)
    {
        $filename = $this->temp_dir.$basename.".txt"; //echo "\nfilename: [$filename]\n";
        $contents = file_get_contents($filename);
        return explode("\n", $contents);
    }
}
?>