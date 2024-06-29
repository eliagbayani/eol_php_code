<?php
namespace php_active_record;
/*
/extra/gnparser/gnparser/bin/gnparser -V
    version: v1.6.3
/extra/gnparser/gnparser172/gnparser -V
    version: v1.7.2
*/
class CheckListBankRules
{
    function __construct()
    {
        $this->can_compute_higherClassificationYN = false; //default is false
    }
    private function initialize()
    {   /* 1st:
        require_library('connectors/RetrieveOrRunAPI');
        $task2run = 'gnparser';
        $download_options['expire_seconds'] = false; //doesn't expire
        $main_path = 'gnparser_cmd';
        $this->RoR = new RetrieveOrRunAPI($task2run, $download_options, $main_path);
        */
        /* 2nd:
        require_library('connectors/DwCA_Utility');
        $this->HC = new DwCA_Utility(); // HC - higherClassification functions
        */
        // /* 3rd:
        $this->temp_dir = CONTENT_RESOURCE_LOCAL_PATH . '/CheckListBank_files/'.$this->resource_id."/";
        if(!is_dir($this->temp_dir)) mkdir($this->temp_dir);
        // $filenames = array('matchedNames', 'processed', 'unmatchedNames');
        // foreach($filenames as $filename) {
        //     $filename = $this->temp_dir.$filename.".txt";
        //     debug("\n[$filename]\n");
        //     $WRITE = Functions::file_open($filename, "w"); fclose($WRITE);
        // }
        // */
    }
    function start_CheckListBank_process()
    {
        self::initialize();
        self::parse_TSV_file($this->temp_folder . $this->arr_json['Taxon_file'], 'process Taxon.tsv');
        $a = self::sort_key_val_array($this->debug['namePublishedIn']);     self::write_array_2txt(array_keys($a), "namePublishedIn");      //print_r($a);
        $a = self::sort_key_val_array($this->debug['infragenericEpithet']); self::write_array_2txt(array_keys($a), "infragenericEpithet");  //print_r($a);
        $a = self::sort_key_val_array($this->debug['taxonomicStatus']);     self::write_array_2txt(array_keys($a), "taxonomicStatus");      //print_r($a);
        $a = $this->debug['taxonRank'];                                     self::write_array_2txt(array_keys($a), "taxonRank");            //print_r($a);
        $a = self::sort_key_val_array($this->debug['nomenclaturalStatus']); self::write_array_2txt(array_keys($a), "nomenclaturalStatus");  //print_r($a);
        $a = self::sort_key_val_array($this->debug['taxonRemarks']);        self::write_array_2txt(array_keys($a), "taxonRemarks");         //print_r($a);
        self::parse_TSV_file($this->temp_folder . $this->arr_json['Distribution_file'], 'process Distribution.tsv');
        $a = self::sort_key_val_array($this->debug['locality']);            self::write_array_2txt(array_keys($a), "locality");             //print_r($a);
        $a = self::sort_key_val_array($this->debug['occurrenceStatus']);    self::write_array_2txt(array_keys($a), "occurrenceStatus");     //print_r($a);

        self::parse_TSV_file($this->temp_folder . $this->arr_json['Taxon_file'], 'do main mapping');

        // self::summary_report();
        // self::prepare_download_link();
        // recursive_rmdir($this->temp_dir);
        exit("\n-stop muna-\n");
        return;
    }
    function parse_TSV_file($txtfile, $task)
    {   
        $modulo = self::get_modulo($txtfile);
        $i = 0; debug("\nProcessing: [$txtfile]\n"); //$syn = 0; for stats only        
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            if(!$line) continue;
            $i++; if(($i % $modulo) == 0) echo "\n".number_format($i)." ";
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
            $rec = array_map('trim', $rec); //print_r($rec); exit("\nstopx\n");
            //###############################################################################################
            if($task == "process Taxon.tsv") {
                /*Array(
                    [dwc:taxonID] => x4
                    [dwc:parentNameUsageID] => 
                    [dwc:acceptedNameUsageID] => 
                    [dwc:originalNameUsageID] => 
                    [dwc:scientificNameID] => x3
                    [dwc:datasetID] => 
                    [dwc:taxonomicStatus] => accepted
                    [dwc:taxonRank] => kingdom
                    [dwc:scientificName] => Animalia
                    [dwc:scientificNameAuthorship] => 
                    [col:notho] => 
                    [dwc:genericName] => 
                    [dwc:infragenericEpithet] => 
                    [dwc:specificEpithet] => 
                    [dwc:infraspecificEpithet] => 
                    [dwc:cultivarEpithet] => 
                    [dwc:nameAccordingTo] => 
                    [dwc:namePublishedIn] => 
                    [dwc:nomenclaturalCode] => 
                    [dwc:nomenclaturalStatus] => 
                    [dwc:taxonRemarks] => 
                    [dcterms:references] => 
                )*/
                // The columns to be mapped are:
                // -Taxon file: taxonomicStatus, taxonRank, nomenclaturalStatus (if populated), taxonRemarks
                // -Distribution file: locality and occurrenceStatus (if present/populated)
                $this->debug['namePublishedIn'][$rec['dwc:namePublishedIn']] = '';
                $this->debug['infragenericEpithet'][$rec['dwc:infragenericEpithet']] = '';
                $this->debug['taxonomicStatus'][$rec['dwc:taxonomicStatus']] = '';
                $this->debug['taxonRank'][$rec['dwc:taxonRank']] = '';
                $this->debug['nomenclaturalStatus'][$rec['dwc:nomenclaturalStatus']] = '';
                $this->debug['taxonRemarks'][$rec['dwc:taxonRemarks']] = '';
            }
            //###############################################################################################
            if($task == "process Distribution.tsv") {
                /*Array(
                    [dwc:taxonID] => ca4b69b0-40be-4eec-8cac-d883b8703cfd
                    [dwc:occurrenceStatus] => native
                    [dwc:locationID] => 
                    [dwc:locality] => Neotropical region
                    [dwc:countryCode] => 
                    [dcterms:source] => 
                )*/
                $this->debug['locality'][$rec['dwc:locality']] = '';
                $this->debug['occurrenceStatus'][$rec['dwc:occurrenceStatus']] = '';
                $taxonID = $rec['dwc:taxonID'];
                // $this->distribution_info_list[$taxonID] = array('o' => $rec['dwc:occurrenceStatus'], 'l' => $rec['dwc:locality']); //commented bec. it may have multiple values
                $this->distribution_info_list[$taxonID]['o'][] = $rec['dwc:occurrenceStatus'];
                $this->distribution_info_list[$taxonID]['l'][] = $rec['dwc:locality'];
            }
            //###############################################################################################
            if($task == "do main mapping") { //mapping here: https://github.com/EOL/ContentImport/issues/14#issuecomment-2168170536
                /*
                dwc field	TWB field
                dwc:taxonID	scientific_nameID                
                dwc:parentNameUsageID	parent_nameID
                dwc:acceptedNameUsageID	accepted_nameID
                dwc:taxonomicStatus	name_usage
                dwc:taxonRank	rank_name
                dwc:scientificNameAuthorship	taxon_author
                dwc:genericName	unit_name1
                dwc:infragenericEpithet	unit_name2
                dwc:specificEpithet	IF dwc:infragenericEpithet absent: unit_name2 | IF dwc:infragenericEpithet present: unit_name3
                dwc:infraspecificEpithet	IF dwc:infragenericEpithet absent: unit_name3 | IF dwc:infragenericEpithet present: unit_name4
                dwc:cultivarEpithet	 IF dwc:infragenericEpithet absent: unit_name3 | IF dwc:infragenericEpithet present: unit_name4
                dwc:namePublishedIn	PULL OUT INTO NEW TABLE
                dwc:locality	geographic_value
                dwc:occurrenceStatus	origin
                */
                $s = array(); //save array
                $s['scientific_nameID'] = $rec['dwc:taxonID'];
                $s['parent_nameID'] = $rec['dwc:parentNameUsageID'];
                $s['accepted_nameID'] = $rec['dwc:acceptedNameUsageID'];
                $s['name_usage'] = $rec['dwc:taxonomicStatus'];
                $s['rank_name'] = $rec['dwc:taxonRank'];
                $s['taxon_author'] = $rec['dwc:scientificNameAuthorship'];

                $s['unit_name1'] = '';  $s['unit_name2'] = ''; $s['unit_name3'] = '';  $s['unit_name4'] = ''; //initialize

                $s['unit_name1'] = $rec['dwc:genericName'];
                $s['unit_name2'] = $rec['dwc:infragenericEpithet'];

                $infragenericEpithet = $rec['dwc:infragenericEpithet'];
                if(!$infragenericEpithet) $s['unit_name2'] = $rec['dwc:specificEpithet'];
                else                      $s['unit_name3'] = $rec['dwc:specificEpithet'];
                if(!$infragenericEpithet) $s['unit_name3'] = $rec['dwc:infraspecificEpithet'];
                else                      $s['unit_name4'] = $rec['dwc:infraspecificEpithet'];
                if(!$infragenericEpithet) $s['unit_name3'] = $rec['dwc:cultivarEpithet'];
                else                      $s['unit_name4'] = $rec['dwc:cultivarEpithet'];

                // dwc:namePublishedIn	PULL OUT INTO NEW TABLE

                $occurrenceStatus = @$this->distribution_info_list[$taxonID]['o'];
                $occurrenceStatus = self::clean_array($occurrenceStatus);
                $occurrenceStatus = implode("|", $occurrenceStatus);
                $locality = @$this->distribution_info_list[$taxonID]['l'];
                $locality = self::clean_array($locality);
                $locality = implode("|", $locality);
                $s['geographic_value'] = $locality;
                $s['origin'] = $occurrenceStatus;
                write_output_rec_2txt($s, "Main_Table");

            }
            //###############################################################################################

        } //end foreach()
        if($task == "load DH file") {
            // echo "\nLoaded DH 2.1 DONE.";
            echo "\ntotal: ".count($this->DH_info)."\n"; //exit;
            // print_r($taxo_status); exit("\n[$syn]\n"); //good debug
        }
    }
    private function write_array_2txt($arr, $basename)
    {
        $arr = self::clean_array($arr);
        $filename = $this->temp_dir.$basename.".txt"; echo "\nfilename: [$filename]\n";
        $WRITE = Functions::file_open($filename, "w");
        foreach($arr as $row) fwrite($WRITE, $row . "\n");
        fclose($WRITE);
    }
    private function write_output_rec_2txt($rec, $basename)
    {   // print_r($rec);
        $filename = $this->temp_dir.$basename.".txt";
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
        // echo "\nSaved to [$basename]: "; print_r($save); //echo "\n".implode("\t", $save)."\n"; //exit("\nditox 9\n"); //good debug
        fclose($WRITE);
    }
    ///============================================== START Summary Report
    function prepare_download_link()
    {   // zip -r temp.zip Documents
        /* during dev - force assign
        $this->temp_dir = "/opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3//CheckListBank_tool/174/";
        $this->resource_id = 174;
        */
        // echo "\n".$this->temp_dir."\n"; echo "\n".$this->resource_id."\n";
        $destination = str_replace("/$this->resource_id/", "", $this->temp_dir);
        $destination .= "/".$this->resource_id.".zip";
        $source = $this->temp_dir;
        if($GLOBALS['ENV_DEBUG']) {
            echo "\nsource: [$source]\n";
            echo "\ndestination: [$destination]\n";    
        }
        $cmd = "zip -rj $destination $source";
        $out = shell_exec($cmd);
        echo "\n$out\n";
        return;
    }
    private function write_summary_report()
    {
        $filename = $this->temp_dir."summary_report.txt";
        $WRITE = Functions::file_open($filename, "w");
        fwrite($WRITE, "Number of taxa: ".$r['Number of taxa'] . "\n");
        fwrite($WRITE, "--------------------------------------------------"."\n");
        $spaces = " _____ ";
        fwrite($WRITE, "List of fields and their DwC-A mappings: "."\n");
        foreach($r['List of fields'] as $field) {
            $field2 = str_pad($field, 30, " ", STR_PAD_LEFT);
            if($val = @$this->taxon_fields[$field]) fwrite($WRITE, "$spaces $field2"." -> ".$val."\n");
            else                                    fwrite($WRITE, "$spaces $field2"." -> "."unmapped"."\n");
        }
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "Taxonomic status: "."\n");
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "Number of unmatched names: ".@$r['totals']['unmatchedNames']."\n");
        fwrite($WRITE, "--------------------------------------------------"."\n");
        fwrite($WRITE, "-end of report-"."\n");
        fclose($WRITE);
    }
    private function summary_report()
    {   
        $this->summary_report['Number of taxa 2'] = self::total_rows_on_file($this->summary_report['info']['user file']);
        $this->summary_report['No. of canonical duplicates'] = self::get_canonical_duplicates();
        $this->summary_report['Number of names with multiple matches'] = self::get_names_with_multiple_matches(); // user file taxon matches with DH taxon        
        if($GLOBALS['ENV_DEBUG']) print_r($this->summary_report); //exit("\nditox 20\n");
        self::write_summary_report();
    }
    private function total_rows_on_file($file)
    {
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
    function add_header_to_file($file, $string_tobe_added)
    {
        echo "<pre>\nuser file: [$file]\n";                             // [temp/1687337313.txt]
        $needle = pathinfo($file, PATHINFO_FILENAME);                   //       1687337313
        $tmp_file = str_replace("$needle.txt", "$needle.tmp", $file);   // [temp/1687337313.tmp]
        echo("\n[$file]\n[$needle]\n[$tmp_file]</pre>\n"); //good debug
        $WRITE = Functions::file_open($tmp_file, "w");
        fwrite($WRITE, $string_tobe_added . "\n");
        $contents = file_get_contents($file);
        fwrite($WRITE, $contents . "\n");
        fclose($WRITE);
        shell_exec("cp $tmp_file $file");
    }
    function sort_key_val_array($multi_array, $key_orientation = SORT_ASC, $value_orientation = SORT_ASC)
    {
        $data = array();
        foreach($multi_array as $key => $value) $data[] = array('language' => $key, 'count' => $value);
        // Obtain a list of columns
        /* before PHP 5.5.0
        foreach ($data as $key => $row) {
            $language[$key]  = $row['language'];
            $count[$key] = $row['count'];
        }
        */
        
        // as of PHP 5.5.0 you can use array_column() instead of the above code
        $language  = array_column($data, 'language');
        $count = array_column($data, 'count');

        // Sort the data with language descending, count ascending
        // Add $data as the last parameter, to sort by the common key
        // array_multisort($count, SORT_ASC, $language, SORT_ASC, $data); // an example run
        array_multisort($count, $value_orientation, $language, $key_orientation, $data);

        // echo "<pre>"; print_r($data); echo "</pre>"; exit;
        /* Array(
            [0] => Array(
                    [language] => infraspecies (inferred)
                    [count] => 42
                )
            [1] => Array(
                    [language] => family (inferred)
                    [count] => 240
                )
        */
        $final = array();
        foreach($data as $d) $final[$d['language']] = $d['count'];
        return $final;
    }
    private function get_modulo($txtfile)
    {
        $total = self::total_rows_on_file($txtfile);
        if($total <= 1000) $modulo = 200;
        elseif($total > 1000 && $total <= 50000) $modulo = 5000;
        elseif($total > 50000 && $total <= 100000) $modulo = 5000;
        elseif($total > 100000 && $total <= 500000) $modulo = 10000;
        elseif($total > 500000 && $total <= 1000000) $modulo = 10000;
        elseif($total > 1000000 && $total <= 2000000) $modulo = 10000;
        elseif($total > 2000000) $modulo = 10000;
        return $modulo;
    }
    private function clean_array($arr)
    {
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        return $arr;
    }
    // private function clean_string($str)
    // {
    //     $str = str_replace(array("\t"), " ", $str);
    //     return Functions::remove_whitespace($str);
    // }
}
?>