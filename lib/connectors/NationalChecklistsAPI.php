<?php
namespace php_active_record;
/* connector: [national_checklists_2024.php] */
class NationalChecklistsAPI
{
    public function __construct($what) //typically param $folder is passed here.
    {
        /* copied template
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */

        $this->download_options = array('resource_id' => "gbif_ctry_checklists", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800*2, 'download_attempts' => 1); //3 months to expire
        $this->download_options['expire_seconds'] = false; //doesn't expire

        $this->debug = array();
        $this->bibliographicCitation = "GBIF.org (16 December 2024) GBIF Occurrence Download https://doi.org/10.15468/dl.h62wur"; //"Accessed ".date("d F Y").".";

        if(Functions::is_production()) {
            $this->destination = "/extra/other_files/GBIF_occurrence/".$what."/";
            $this->zip_file    = $this->destination.$what."_DwCA.zip";
        }
        else {
            $this->destination = "/Volumes/AKiTiO4/other_files/GBIF_occurrence/";
            $this->zip_file    = $this->destination.$what."_DwCA.zip"; //manualy created, zip copied from editors.eol.org
        }
        $this->service['country'] = "https://api.gbif.org/v1/node/country/"; //'https://api.gbif.org/v1/node/country/JP';
        $this->service['species'] = "https://api.gbif.org/v1/species/"; //https://api.gbif.org/v1/species/1000148
    }
    function start()
    {
        /*
        require_library('connectors/GBIFdownloadRequestAPI');
        $func = new GBIFdownloadRequestAPI('Country_checklists');
        $key = $func->retrieve_key_for_taxon('Country_checklists');
        echo "\nkey is: [$key]\n";
        */
        $tsv_path = self::download_extract_gbif_zip_file();
        echo "\ncsv_path: [$tsv_path]\n";
        self::parse_tsv_file($tsv_path);

        exit("\n-stop muna-\n");
    }
    private function parse_tsv_file($file)
    {   echo "\nReading file: [$file]\n";
        $i = 0; $final = array();
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % 200000) == 0) echo "\n $i ";
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); print_r($rec); //exit("\nstop muna\n");
                /*Array(
                    [specieskey] => 1000148
                    [countrycode] => JP
                )*/
                $options = $this->download_options;
                $options['expire_seconds'] = false;
                if($json = Functions::lookup_with_cache($this->service['country'].$rec['countrycode'], $options)) {
                    print_r(json_decode($json, true));
                }
                if($json = Functions::lookup_with_cache($this->service['species'].$rec['specieskey'], $options)) {
                    print_r(json_decode($json, true));
                }
                break;
        

            }
            if($i >= 3) break;
        }
    }
    private function download_extract_gbif_zip_file()
    {
        echo "\ndownload_extract_gbif_zip_file...\n";
        // /* main operation - works OK
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $ret = $func->download_extract_zip_file($this->zip_file, $this->destination); // echo "\n[$ret]\n";
        if(preg_match("/inflating:(.*?)elix/ims", $ret.'elix', $arr)) {
            $csv_path = trim($arr[1]); echo "\n[$csv_path]\n";
            // [/Volumes/AKiTiO4/other_files/GBIF_occurrence/0036064-241126133413365.csv]
            return $csv_path;
        }
        return false;
        // */
        // return "/Volumes/AKiTiO4/other_files/GBIF_occurrence/0036064-241126133413365.csv"; //during dev only


        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        // $options = $this->download_options;
        // $options['expire_seconds'] = 60*60*24*30*3; //3 months cache
        // $paths = $func->extract_zip_file($this->zip_file, $options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit; //good debug
        */

        /* sample output:
        Array(
            [extracted_file] => /Volumes/AKiTiO4/other_files/GBIF_occurrence/Country_checklists_DwCA
            [temp_dir] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_87237/
            [temp_file_path] => /Volumes/AKiTiO4/other_files/GBIF_occurrence/Country_checklists_DwCA.zip
        )*/

        /* development only
        $paths = Array(
            // 'extracted_file' => '/Volumes/AKiTiO4/other_files/GBIF_occurrence/Country_checklists_DwCA', //not needed
            'temp_dir'       => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_87237/',
            // 'temp_file_path' => '/Volumes/AKiTiO4/other_files/GBIF_occurrence/Country_checklists_DwCA.zip' //not needed
        );
        */

        // $temp_dir = $paths['temp_dir'];
        // $this->local_csv = $paths['extracted_file'].".csv";     //orig
        // return $temp_dir;
    }
    // ======================================= below copied template
    function start_z()
    {        
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */        
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->page['all_taxa'], $options)) {
            $html = str_replace("&nbsp;", ' ', $html);
            if(preg_match_all("/<div class=\"sd_data\">(.*?)<div class=\"clear\"><\/div>/ims", $html, $arr)) {
                $eli = 0;
                foreach($arr[1] as $str) {
                    if(preg_match_all("/<div (.*?)<\/div>/ims", $str, $arr2)) {
                        $rec = array_map('trim', $arr2[1]);
                        if(stripos($rec[0], "Valid name") !== false) { //string is found
                            $rek = array();
                            if(preg_match("/allantwebants\">(.*?)<\/a>/ims", $rec[0], $arr3)) $rek['sciname'] = str_replace(array('&dagger;'), '', $arr3[1]);
                            $rek['rank'] = 'species';
                            if(preg_match("/description\.do\?(.*?)\">/ims", $rec[0], $arr3)) $rek['source_url'] = 'https://www.antweb.org/description.do?'.$arr3[1];
                            $eli++;
                            if(($eli % 1000) == 0) echo "\n".number_format($eli)." ";
                        }                        
                    }
                }
            }
        }
        $this->archive_builder->finalize(true);
        print_r($this->debug);
    }
    private function write_archive($rek)
    {
        $taxonID = self::write_taxon($rek);
        self::write_traits($rek, $taxonID);
    }
    private function write_taxon($rek)
    {   
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = strtolower(str_replace(' ','_',$rek['sciname']));
        $taxon->scientificName  = $rek['sciname'];
        $taxon->phylum  = $rek['ancestry']['phylum'];
        $taxon->class   = $rek['ancestry']['class'];
        $taxon->order   = $rek['ancestry']['order'];
        $taxon->family  = $rek['ancestry']['family'];
        $taxon->furtherInformationURL = $rek['source_url'];
        /* copied template
        $taxon->kingdom         = $t['dwc_Kingdom'];
        $taxon->genus           = $t['dwc_Genus'];
        if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        */
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
        return $taxon->taxonID;
    }
    private function write_traits($rek, $taxonID)
    {
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $rek['source_url'];
        $save['bibliographicCitation'] = $this->bibliographicCitation;        
        if($loop = @$rek['country_habitat']) {
            foreach($loop as $t) {
                if($country = @$t['country']) { $mType = 'http://eol.org/schema/terms/Present';
                    if($mValue = self::get_country_uri($country)) {
                        $save['measurementRemarks'] = $country;
                        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                        if(in_array($mValue, $this->investigate)) exit("\nhuli ka 2\n");
                        $this->func->add_string_types($save, $mValue, $mType, "true");
                    }
                    else $this->debug['undefined country'][$country] = '';
                }
            } //end loop
        }
    }
    private function get_country_uri($country)
    {
        // print_r($this->uri_values); exit("\ntotal: ".count($this->uri_values)."\n"); //debug only
        if($country_uri = @$this->uri_values[$country]) return $country_uri;
        else {
            /*
            switch ($country) { //put here customized mapping
                // case "Port of Entry":   return false; //"DO NOT USE";
                // just examples below. Real entries here were already added to /cp_new/GISD/mapped_location_strings.txt
                // case "United States of America":        return "http://www.wikidata.org/entity/Q30";
                // case "Dutch West Indies":               return "http://www.wikidata.org/entity/Q25227";
            }
            */
        }
    }
}
?>