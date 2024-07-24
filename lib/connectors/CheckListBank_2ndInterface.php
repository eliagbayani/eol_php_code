<?php
namespace php_active_record;
/* */
class CheckListBank_2ndInterface
{
    function __construct()
    {
    }
    private function initialize($resource_id)
    {   // /*
        $this->temp_dir = CONTENT_RESOURCE_LOCAL_PATH . 'CheckListBank_files/'.$resource_id."/";
        if(!is_dir($this->temp_dir)) {
            mkdir($this->temp_dir);
            shell_exec("chmod 777 ".$this->temp_dir);
        }
        // */        
    }
    function go_2ndInterface($params)
    {
        self::initialize($params['resource_id']);
        // print_r($params); echo "\n".$this->temp_dir."\n";
        /*Array(
            [resource_id]    => 1721818051
            [temp_folder]    => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/
            [orig_taxa]      => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/1721818051_orig_taxa.tsv
            [orig_reference] => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/1721818051_orig_reference.txt
        )*/
        $ref_info_list = self::generate_ref_info_list($params);
        $included_fields = array("reference_author", "title", "publication_name", "actual_pub_date", "listed_pub_date", "publisher", "pub_place", "pages", "isbn", "issn", "pub_comment");
        $source      = $params['orig_taxa'];
        $destination = $this->temp_dir . 'Taxa_final.txt';
        self::parse_TSV_file($source, $destination, $ref_info_list, $included_fields, $params);
        unset($ref_info_list);
        self::prep_download_link($params);
    }
    private function prep_download_link($params)
    {
        echo "\nCompressing...\n";
        $resource_id = $params['resource_id'];
        $source1 = $this->temp_dir.'Taxa_final.txt';
        $source2 = $params['orig_taxa'];
        $source3 = $params['orig_reference'];
        $target_dir = str_replace("$resource_id/", "", $this->temp_dir);
        $target = $target_dir."ITIS_format_$resource_id.zip";
        // $output = shell_exec("gzip -cv $source1, $source2 > ".$target);
        $output = shell_exec("zip -j $target $source1 $source2 $source3");
        echo "\n$output\n";
        
        if(stripos($output, "error") !== false) exit("\nError encountered:\n[$output]\nGo back to Main.\n");
        echo "\nCompressed OK\n";
        if(Functions::is_production()) $domain = "https://editors.eol.org";
        else                           $domain = "http://localhost";
        $rec['url'] = $domain.'/eol_php_code/applications/content_server/resources/Trait_Data_Import/'.$resource_id.'.tar.gz';
        
        $final_zip_url = str_replace(DOC_ROOT, WEB_ROOT, $target);
        echo "<br>Download ITIS-formatted file: [<a href='$final_zip_url'>".pathinfo($final_zip_url, PATHINFO_BASENAME)."</a>]<br><br>";
        
        shell_exec("chmod 777 ".$this->temp_dir);
        recursive_rmdir($this->temp_dir); //un-comment in real operation
        
        if(is_file($source2)) unlink($source2);
        if(is_file($source3)) unlink($source3);
        ?>
        You can save this file to your computer.<br>
        This file will remain in our server for two (2) weeks.<br>
        <a href='main.php'> &lt;&lt; Back to menu</a>
        <?php        
    }
    private function parse_TSV_file($txtfile, $destination, $ref_info_list, $included_fields, $params)
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
                if(!in_array('referenceID', $fields)) {
                    self::delete_transaction_files($params);
                    exit("\nTaxa file does not have a [referenceID] column. <a href='javascript:history.go(-2)'>Back to menu</a>\n");
                }
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
                [name_usage] => valid
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
                    @$eli_debug['referenceID found']++;
                    foreach($included_fields as $fld) $save[$fld] = @$ref_array[$fld];
                }
                else {
                    // print_r($ref_info_list); print_r($rec);
                    @$eli_debug['Should not go here. referenceID not found'][$referenceID]++;
                }
            }
            else {
                foreach($included_fields as $fld) $save[$fld] = '';
            }
            // */
    
            /* save comments from "name_usage | unacceptability_reason" section
            $unacceptability_reason = $save['unacceptability_reason'];
            // Array(
            //     [accepted] => 
            //     [homonym & junior synonym] => remark for ambiguous
            //     [misapplied] => remark for misapplied
            //     [junior synonym] => remark for junior syn
            // )
            if($val = @$comments_reason[$unacceptability_reason]) {
                if($save['pub_comment']) $save['pub_comment'] .= " | Comments for [$unacceptability_reason]: $val";
                else                     $save['pub_comment'] = "Comments for [$unacceptability_reason]: $val";
            }
            */
    
            // ----- write -----
            // echo "<pre>"; print_r($save); echo "</pre>"; //exit;
            self::write_output_rec_2txt($save, $destination);
        } //end foreach()
        if($eli_debug) print_r($eli_debug);
    }    
    private function generate_ref_info_list($params)
    {
        $txtfile = $params['orig_reference'];
        $i = 0; debug("\nLoading: [$txtfile]...\n");
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            if(!$line) continue;
            $i++; //if(($i % 1000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                if(!in_array('ID', $fields)) {
                    self::delete_transaction_files($params);
                    exit("\nReference file does not have an [ID] column. <a href='javascript:history.go(-2)'>Back to menu</a>\n");
                }
                continue;
            }
            else {
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec);
            // echo "<pre>";print_r($rec); echo "</pre>"; exit("\nstopx\n");
            /*Array(
                [Item_type] => Miscellaneous
                [ID] => fd4d02f442272f88f5123b18d0e66e69
                [dwc] => Amsel HG, Hering M. Beitrag zur Kenntnis der Minenfauna Palästinas. Deutsche Entomologische Zeitschrift 1931: 113-152, pls 111-112. doi: 10.1002/mmnd.193119310203. (1931).
                [reference_author] => 
                [Editors] => 
                [title] => Beitrag zur Kenntnis der Minenfauna Palästinas
                [publication_name] => 
                [actual_pub_date] => 1931
                [pages] => 113-152, 111-112
                [publisher] => 
                [pub_place] => 
                [URLs] => 
                [DOI] => 10.1002/mmnd.193119310203.
                [Language] => 
                [listed_pub_date] => 
                [isbn] => 
                [issn] => 
                [pub_comment] => 
            )*/
            if(!$rec['ID']) exit("\nReference file does not have an ID column.\n");

            $fields = array_keys($rec);
            $identifier = $rec['ID'];
            $a = array();
            foreach($fields as $fld) $a[$fld] = $rec[$fld];
            $final[$identifier] = $a; // print_r($final); exit;
            /*Array(
                [fd4d02f442272f88f5123b18d0e66e69] => Array(
                        [Item_type] => Miscellaneous
                        [ID] => fd4d02f442272f88f5123b18d0e66e69
                        [dwc] => Amsel HG, Hering M. Beitrag zur Kenntnis der Minenfauna Palästinas. Deutsche Entomologische Zeitschrift 1931: 113-152, pls 111-112. doi: 10.1002/mmnd.193119310203. (1931).
                        [reference_author] => 
                        [Editors] => 
                        [title] => Beitrag zur Kenntnis der Minenfauna Palästinas
                        [publication_name] => 
                        [actual_pub_date] => 1931
                        [pages] => 113-152, 111-112
                        [publisher] => 
                        [pub_place] => 
                        [URLs] => 
                        [DOI] => 10.1002/mmnd.193119310203.
                        [Language] => 
                        [listed_pub_date] => 
                        [isbn] => 
                        [issn] => 
                        [pub_comment] => 
                    )
            )*/
        }
        return $final;
    }
    private function write_output_rec_2txt($rec, $filename)
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
    private function delete_transaction_files($params)
    {
        recursive_rmdir($this->temp_dir); //un-comment in real operation
        /*Array(
            [resource_id]    => 1721818051
            [temp_folder]    => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/
            [orig_taxa]      => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/1721818051_orig_taxa.tsv
            [orig_reference] => /opt/homebrew/var/www/eol_php_code//applications/CheckListBank_tool/temp/1721818051_orig_reference.txt
        )
            1721835616_orig_taxa.zip
            1721835616_orig_reference.zip
        */
        unlink($params['orig_taxa']);
        unlink($params['orig_reference']);

        $file = $params['temp_folder'].$params['resource_id']."_orig_taxa.zip";
        if(file_exists($file)) unlink($file);
        $file = $params['temp_folder'].$params['resource_id']."_orig_reference.zip";
        if(file_exists($file)) unlink($file);
    }
}
?>