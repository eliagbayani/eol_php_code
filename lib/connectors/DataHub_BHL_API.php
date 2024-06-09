<?php
namespace php_active_record;
/*  datahub_bhl.php 
https://content.eol.org/resources/xxx
*/
class DataHub_BHL_API
{
    function __construct($folder = false)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }
        $this->debug = array();
        $this->download_options_BHL = array('resource_id' => 'BHL', 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800*2, 'download_attempts' => 1);
        $this->download_options_BHL['expire_seconds'] = false; //May 2024
        $this->taxon_page = 'https://www.biodiversitylibrary.org/name/';

        // /*
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "BHL/";           if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "BHL_hosted/";    if(!is_dir($save_path)) mkdir($save_path);
        $this->download_path = $save_path;
        $save_path = $save_path . "Data/";          if(!is_dir($save_path)) mkdir($save_path);
        $this->tsv_file = $save_path.'pagename.txt';

        $this->dump_file = 'https://www.biodiversitylibrary.org/data/hosted/data.zip';
        $this->dump_file = 'http://localhost/eol_php_code/tmp2/data.zip';
        // */
    }
    private function download_bhl_dump()
    {   /* from: https://about.biodiversitylibrary.org/tools-and-services/developer-and-data-tools/
        click: Data Exports
        click: TSV

        Here is the download URL for the best source of counts per taxa:
        wget -c https://www.biodiversitylibrary.org/data/hosted/data.zip

        Complete collection : https://www.biodiversitylibrary.org/data/data.zip
        BHL-hosted material only : https://www.biodiversitylibrary.org/data/hosted/data.zip 
        
        https://www.biodiversitylibrary.org/docs/api3.html
        -> latest API doc; Not used at all.
        */

        require_library('connectors/DataHub_GGBN'); 
        $func = new DataHub_GGBN(null, null);
        $func->save_dump_files($this->dump_file, $this->download_path."downloaded_data.zip");
    }
    function start()
    {
        $this->debug = array();
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /*
        // step 0: download dump
        self::download_bhl_dump(); exit("\ndownload done.\n");
        */

        // step 1:
        self::parse_tsv_file($this->tsv_file, 'compute_totals_per_taxon'); // exit;

        // step 2:
        foreach($this->totals as $sciname => $count) {
            if(!$sciname) continue;
            $taxid = @$this->name_id[$sciname];
            if(!$taxid) $taxid = strtolower(str_replace(" ", "_", $sciname));

            // echo "\n[$sciname] [$taxid] [$count]";

            $save = array();
            $save['taxonID'] = $taxid;
            $save['scientificName'] = $sciname;
            // $save['taxonRank'] = ;                                       //not provided in BHL
            // $save['parentNameUsageID'] = $this->taxa_info[$taxid]['p'];  //not provided in BHL    
            self::write_taxon($save);

            $rec = array();
            $rec['tax id'] = $taxid;
            $rec['count'] = $count;
            $rec['scientificName'] = $sciname;
            self::write_MoF($rec);
        }
        
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    private function parse_tsv_file($file, $what)
    {   echo "\nReading file, task: [$what] [$file]\n";
        $i = 0; $final = array();
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % 5000000) == 0) echo "\n $i ";
            $row = Functions::remove_utf8_bom($row);
            if(!Functions::is_utf8($row)) exit("\nmeron not utf8\n"); //continue;
                    
            if($i == 1) { $fields = explode("\t", $row); continue; }
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstop muna\n");
            }
            if($what == 'compute_totals_per_taxon') {
                /*Array
                (
                    [NameBankID] => 
                    [NameConfirmed] => × Acanthinopsis
                    [PageID] => 42403819
                    [CreationDate] => 2023-04-22 00:27
                )*/
                $PageID = $rec['PageID'];
                $NameConfirmed = $rec['NameConfirmed'];
                $NameBankID = $rec['NameBankID'];

                $NameConfirmed = self::format_sciname($NameConfirmed);
                if(!self::valid_string_name($NameConfirmed)) continue;

                if($PageID && $NameConfirmed) {
                    @$this->totals[$NameConfirmed]++;
                    if($NameBankID) $this->name_id[$NameConfirmed] = $NameBankID;
                }
            }
            // if($i >= 10000) break; //debug only
        } //end foreach()
    }
    private function format_sciname($sciname)
    {
        $sciname = trim(str_replace("Ã—", "", $sciname));
        // β fragile
        // Î² fragile
        // α acicola
        // Î± acicola
        $sciname = trim(str_replace("β", "", $sciname));
        $sciname = trim(str_replace("Î²", "", $sciname));
        $sciname = trim(str_replace("α", "", $sciname));
        $sciname = trim(str_replace("Î±", "", $sciname));
        return $sciname;
    }
    private function valid_string_name($sciname)
    {   // × Colmanara
        // if(stripos($sciname, "× ") !== false) return false; //string is found
        if(stripos($sciname, "×") !== false) return false; //string is found
        return true;
    }
    private function write_taxon($rec)
    {   //print_r($rec);
        $taxonID = $rec['taxonID'];
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $taxonID;
        $taxon->scientificName      = $rec['scientificName'];

        /* not provided in BHL
        $final_rank = strtolower($rec['taxonRank']);
        $taxon->taxonRank           = $final_rank;
        $taxon->parentNameUsageID   = $rec['parentNameUsageID'];
        */

        if(!isset($this->taxonIDs[$taxonID])) {
            $this->taxonIDs[$taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function write_MoF($rec)
    {   //print_r($o); exit;
        $taxonID = $rec['tax id'];
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $this->taxon_page . strtolower(str_replace(" ", "_", $rec['scientificName']));
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 
        $mType = 'http://eol.org/schema/terms/NumberReferencesInBHL';
        $mValue = $rec['count'];
        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        $this->func->add_string_types($save, $mValue, $mType, "true");    
    }

    // ======================================================================= copied template below
    function x_start()
    {   /* just a utility
        // echo "\nranks_php_code_base: ".count(self::$ranks_php_code_base)."\n";
        // echo "\nranks_eol_org: ".count(self::$ranks_eol_org)."\n";
        // $subset=array_diff(self::$ranks_eol_org, self::$ranks_php_code_base); echo "\nsubset: ".count($subset)."\n";
        // $subset=array_diff(self::$ranks_php_code_base, self::$ranks_eol_org); echo "\nsubset: ".count($subset)."\n";
        exit("\n");
        */


        // step 1 Use Eli's cache to generate $var[taxid] = parent_id
        self::parse_tsv_file($this->Eli_cached_taxonomy, 'use cached taxonomy build taxon info list');

        // /* step 2
        // step 2.1: build taxon info list using Rebekah's spreadsheets   FOR SPECIES-LEVEL ONLY
        self::read_tsv_files_do_task("generate taxa info list");
        // print_r($this->taxa_info); exit;

        // step 2.2:                                                      FOR SPECIES-LEVEL ONLY
        self::read_tsv_files_do_task("read tsv write dwca");
        // */

        // step 3: process family-genus-level
        // /*
        $this->group = 'family';
        $url = str_replace("XGROUP", 'family', $this->tsv_files);
        self::parse_tsv_file($url, 'process family-genus-level'); //generates $this->totals
        // */

        // /*
        $this->group = 'genus';
        $url = str_replace("XGROUP", 'genus', $this->tsv_files);
        self::parse_tsv_file($url, 'process family-genus-level'); //generates $this->totals
        // */

        // print_r($this->totals); exit;

        foreach($this->totals as $sciname => $count) {
            if(!$sciname) continue;
            if($taxid = @$this->name_id[$sciname]) {
                // echo "\n[$sciname] [$taxid] [$count]";

                $save = array();
                $save['taxonID'] = $taxid;
                $save['scientificName'] = $sciname;
                $save['taxonRank'] = $this->group;
                $save['parentNameUsageID'] = $this->taxa_info[$taxid]['p'];
                self::write_taxon($save);

                $rec = array();
                $rec['tax id'] = $taxid;
                $rec['count'] = $count;
                self::write_MoF($rec);
            }
            else $this->debug['sciname not found'][$sciname] = '';
        }

        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    private function read_tsv_files_do_task($task)
    {
        $groups = array('species'); //this is exclusive for species-level only
        foreach($groups as $group) {
            $this->group = $group;
            $url = str_replace("XGROUP", $group, $this->tsv_files);
            self::parse_tsv_file($url, $task);
        }
    }
    private function x_parse_tsv_file($file, $what)
    {   echo "\nReading file, task: [$what] [$file]\n";
        $i = 0; $final = array();
        $included_ranks = array("species", "form", "variety", "subspecies", "unranked");
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % 200000) == 0) echo "\n $i ";
            if($i == 1) { $fields = explode("\t", $row); continue; }
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstop muna\n");
            }

            if(in_array($what, array('read tsv write dwca', 'generate taxa info list'))) {
                $tax_id = $rec['tax id'];
                $parent_id = $rec['parent id'];
                $sciname = $rec[$this->group];    
            }

            if($what == 'read tsv write dwca') { //species-level only
                if(($i % 20000) == 0) echo "\n main $i ";

                /* Array(
                    [count] => 14
                    [family] => Rosaceae
                    [tax id] => 989646
                    [parent id] => 100947
                    [] => 
                )*/
                
                if(stripos($sciname, 'sp.') !== false) continue; //string is found

                $save = array();
                $save['taxonID'] = $tax_id;
                $save['scientificName'] = $sciname;
                $save['taxonRank'] = $this->group;
                $save['parentNameUsageID'] = $parent_id;
                self::write_taxon($save);
                self::write_MoF($rec);

                // break; //debug only
                // if($i >= 3000) break; //debug only
            }
            elseif($what == "generate taxa info list") { //species-level only
                $this->taxa_info[$tax_id]['p'] = $parent_id;
                $this->taxa_info[$tax_id]['n'] = $sciname;
                $this->taxa_info[$tax_id]['r'] = $this->group;
            }
            elseif($what == "use cached taxonomy build taxon info list") { // print_r($rec); exit;
                /*Array(
                    [taxid] => 11
                    [counts] => 3027
                    [sciname] => Acanthocephala
                    [rank] => phylum
                    [parentNameUsageID] => 1
                    [] => 
                )*/
                $taxid = $rec['taxid'];
                $this->taxa_info[$taxid]['p'] = $rec['parentNameUsageID'];
                $this->taxa_info[$taxid]['n'] = $rec['sciname'];
                $this->taxa_info[$taxid]['r'] = $rec['rank'];

                if($rec['rank'] == 'Families' || $rec['rank'] == 'Genera') $this->name_id[$rec['sciname']] = $taxid;
            }
            elseif($what == "process family-genus-level") {
                // print_r($rec); exit("\n fam exit muna \n");
                /* Array(
                    [count] => 14               - wrong assignment
                    [family] => Rosaceae
                    [tax id] => 989646          - wrong assignment
                    [parent id] => 100947       - wrong assignment
                    [] => 
                )*/
                if($family_genus_name = @$rec['family']) {}
                elseif($family_genus_name = @$rec['genus']) {}
                else  { //it actually can happen
                    // print_r($rec);
                    // exit("\nShould not go here. Investigate.\n");
                }
                $this->totals[$family_genus_name] = @$this->totals[$family_genus_name] + $rec['count'];
            }
        }
    }
    private function write_taxa_4_ancestry($ancestry)
    {
        // print_r($ancestry); exit("\nelix 1\n");
        foreach($ancestry as $tax_id) {
            if($tax_id == 1) { // the parentmost, the root
                $save = array();
                $save['taxonID'] = $tax_id;
                $save['scientificName'] = 'root';
                $save['taxonRank'] = '';
                $save['parentNameUsageID'] = '';
                self::write_taxon($save);    
                break;
            }
            else {
                if(!@$this->taxa_info[$tax_id]['n']) continue;
                $save = array();
                $save['taxonID'] = $tax_id;
                $save['scientificName'] = $this->taxa_info[$tax_id]['n'];
                $save['taxonRank'] = $this->taxa_info[$tax_id]['r'];
                $save['parentNameUsageID'] = $this->taxa_info[$tax_id]['p'];
                self::write_taxon($save);    
            }
        }
    }
    private function get_ancestry_for_taxonID($taxonID)
    {
        $final = array();
        while(true) {
            if($val = @$this->taxa_info[$taxonID]['p']) {
                $final[] = $val;
                $taxonID = $val;
            }
            else {
                if($taxonID == 1) break;
                else {
                    if($val = self::get_ancestry_thru_api($taxonID)) {
                        echo " [$val] ";
                        $final[] = $val;
                        $taxonID = $val;
                    }
                    else break;
                }
            }
        }
        // echo "\nancestry: "; print_r($final); //exit;
        return $final;
    }
    private function get_ancestry_thru_api($taxonID)
    {
        $options = $this->download_options_BHL;
        $options['resource_id'] = 'BHL_ancestry';
        $url = "https://v3.BHLystems.org/index.php/API_Tax/TaxonData?dataTypes=basic&includeTree=true&taxId=";

        @$this->total_page_calls++; echo "\nx[$this->total_page_calls] $this->group batch\n";
        if($this->total_page_calls > 1) {
            if(($this->total_page_calls % 50) == 0) { echo "\nsleep 60 secs.\n"; sleep(60); }
        }

        if($json = Functions::lookup_with_cache($url.$taxonID, $this->download_options_BHL)) {
            self::BHL_API_result_still_validYN($json);
            $obj = json_decode($json); // print_r($obj); exit;
            // /* build taxa info list
            foreach($obj as $o) { 
                if(!@$o->taxid) { $this->debug['not found in api'][$taxonID] = ''; continue;}
                $tax_id = $o->taxid;
                $this->taxa_info[$tax_id]['p'] = $o->parentid;
                $this->taxa_info[$tax_id]['n'] = $o->taxon;
                $this->taxa_info[$tax_id]['r'] = $o->tax_rank;
            }
            // */
        }
        return @$this->taxa_info[$taxonID]['p'];
    }
    private function BHL_API_result_still_validYN($str)
    {   // You have exceeded your allowed request quota. If you wish to download large volume of data, please contact support@BHLystems.org for instruction on the process. 
        if(stripos($str, 'have exceeded') !== false) { //string is found
            echo "\n[$str]\n";
            exit("\nProgram terminated by Eli.\n");
            // echo "\nBHL special error\n"; exit("\nexit muna, remove BHL from the list of dbases.\n");
            echo "\nExceeded quota\n"; sleep(60*10); //10 mins
            @$this->BHL_TooManyRequests++;
            if($this->BHL_TooManyRequests >= 3) exit("\nBHL should stop now.\n");
        }
    }
}
?>