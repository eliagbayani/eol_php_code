<?php
namespace php_active_record;
class CheckListBankWeb
{
    function __construct()
    {
    }
    function create_web_form()
    {
        echo "\ndito na siya...\n";
        $taxonRanks = self::get_text_contents('taxonRank');
        ?>
        <table border="1" cellpadding="15" cellspacing="1" align="center" width="40%">
        <tr align="center"><td><b>CheckListBank Tool</b></td></tr>
        <form action="form_result_map.php" method="post" enctype="multipart/form-data">
        <tr><td>
                <font size="3">Mapping Exercise</font>
        </td></tr>
        <tr align="center">
            <td>
                <?php
                echo '<input type="text" value="'.$this->resource_id.'"><br>';
                // print_r($taxonRanks);
                ?><table><?php
                $i = -1;
                foreach($taxonRanks as $r) { $i++;
                        ?><tr><?php
                        echo "<td>$r</td>";
                        echo "<td>
                            <select name='rank[$i]' id='cars'>";
                            foreach($this->map['taxonRank'] as $tr) {
                                if($r == $tr) $selected = "selected";
                                else          $selected = "";
                                echo "<option value='$tr' $selected >$tr</option>";
                            }
                            echo'</select>
                        </td>';

                            
                        ?></tr><?php


                } //end foreach()
                ?></table><?php
                // print_r($this->map['taxonRank']);

                ?>
                <input type="submit" value="Submit">
                <input type="reset" value="Reset">
            </td>
        </tr>
        </form>
        </table>
        <?php
    }
    private function get_text_contents($basename)
    {
        $filename = $this->temp_dir.$basename.".txt"; echo "\nfilename: [$filename]\n";
        $contents = file_get_contents($filename);
        return explode("\n", $contents);
    }
}
?>