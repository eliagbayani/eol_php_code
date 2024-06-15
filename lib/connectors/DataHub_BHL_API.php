<?php
namespace php_active_record;
/*  datahub_bhl.php 
https://content.eol.org/resources/1223
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

        $this->dump_file = 'https://www.biodiversitylibrary.org/data/hosted/data.zip';
        // $this->dump_file = 'http://localhost/eol_php_code/tmp2/data.zip'; //dev only
        // */

        // with special chars:
        // Sphaerophoron coralloides
        // Peridermium pini        
        // Sphaerophoron coralloides β fragile
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

        // /*
        $destination = $this->download_path."downloaded_data.zip";
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        // /* un-comment in real operation
        //1. download remote file
        $func->save_dump_files($this->dump_file, $destination);
        //2. extract downloaded local file
        $paths = $func->extract_local_file($destination, $this->download_path, 'creatoridentifier.txt');
        print_r($paths); //exit;
        // */

        /* dev only
        $paths = Array(
            "archive_path" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_97485/Data/", 
            "temp_dir" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_97485/"
        );
        */
        return $paths;
    }
    function start()
    {
        $this->debug = array();
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        
        // /* step 0: download dump
        $paths = self::download_bhl_dump(); //exit("\ndownload done.\n");
        if($paths['archive_path'] && $paths['temp_dir']) {
            $this->tsv_file = $paths['archive_path'].'pagename.txt';
            $temp_dir       = $paths['temp_dir'];
        }
        else exit("\nTerminated. Files are not ready.\n");
        // */

        // step 1: generate totals info
        self::parse_tsv_file($this->tsv_file, 'compute_totals_per_taxon'); // exit;

        // step 2: write DwCA
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
        // remove temp dir
        recursive_rmdir($temp_dir);
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
                /*Array(
                    [NameBankID] => 
                    [NameConfirmed] => × Acanthinopsis
                    [PageID] => 42403819
                    [CreationDate] => 2023-04-22 00:27
                )*/
                $PageID = $rec['PageID'];
                $NameConfirmed = $rec['NameConfirmed'];
                $NameBankID = $rec['NameBankID'];

                if(!self::valid_string_name($NameConfirmed)) continue;
                $NameConfirmed = self::format_sciname($NameConfirmed);

                if($PageID && $NameConfirmed) {
                    @$this->totals[$NameConfirmed]++;
                    // if($NameBankID) $this->name_id[$NameConfirmed] = $NameBankID; //did not use it.
                }
            }
            // if($i >= 10000) break; //debug only
        } //end foreach()
    }
    private function format_sciname($sciname)
    {   /*  "× "        "Ã— "
            β fragile   Î² fragile
            α acicola   Î± acicola  */
        $sciname = preg_replace('/[^\x20-\x7E]/', '', $sciname); //very important: removes chars with diacritical markings and others. Per: https://stackoverflow.com/questions/8781911/remove-non-ascii-characters-from-string
        return Functions::remove_whitespace($sciname);
    }
    private function valid_string_name($sciname)
    {   
        $sciname = Functions::remove_whitespace($sciname);

        // case 0: remove monomials: per https://github.com/EOL/ContentImport/issues/6#issuecomment-2164163467
        $words = explode(" ", $sciname);
        if(count($words) == 1) return false; //exclude monomials

        // case 1:
        $new_name = preg_replace('/[^\x20-\x7E]/', '', $sciname); //very important: removes chars with diacritical markings and others. Per: https://stackoverflow.com/questions/8781911/remove-non-ascii-characters-from-string
        $new_name = Functions::remove_whitespace($new_name);
        if($sciname != $new_name) return false;

        // case 2: A. nyassensis
        $parts = explode(" ", $sciname);
        $first = @$parts[0];
        if(strlen($first) == 2) { if(substr($first,1,1) == ".") return false; } //A. longifilis
        if(strlen($first) == 3) { if(substr($first,2,1) == ".") return false; } //Ab. malmundariense
        if(strlen($first) == 4) { if(substr($first,3,1) == ".") return false; } //Abx. xxxxxx

        // therefore return true;
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

        $sciname = strtolower(str_replace(" ", "_", $rec['scientificName']));
        $sciname = str_replace(".", "%24", $sciname);
        $sciname = str_replace(",", "%2c", $sciname);
        // $sciname = str_replace("&", "%26", $sciname); //NOT the correct assignment. Don't use this.
        $sciname = str_replace("&", "%7e", $sciname);    //weird assignment but that is what BHL do: e.g. https://www.biodiversitylibrary.org/name/heuchera_rubescens_var%24_rydbergiana_rosendahl%2c_butters_%7e_lakela

        $save['source'] = $this->taxon_page . $sciname;
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 
        $mType = 'http://eol.org/schema/terms/NumberReferencesInBHL';
        $mValue = $rec['count'];
        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        $this->func->add_string_types($save, $mValue, $mType, "true");    
    }
    // ======================================================================= copied template below
}
?>