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

        $this->download_options = array('resource_id' => "gbif_ctry_checklists", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000/2, 'timeout' => 10800*2, 'download_attempts' => 2); //3 months to expire
        $this->download_options['expire_seconds'] = false; //doesn't expire

        $this->debug = array();
        $this->bibliographicCitation = "GBIF.org (16 December 2024) GBIF Occurrence Download https://doi.org/10.15468/dl.h62wur"; //"Accessed ".date("d F Y").".";

        if(Functions::is_production())  $this->destination = "/extra/other_files/GBIF_occurrence/".$what."/";
        else                            $this->destination = "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/".$what."/";
        
        if(!is_dir($this->destination)) mkdir($this->destination);
        $this->country_path = $this->destination.'countries';
        if(!is_dir($this->country_path)) mkdir($this->country_path);
        $this->zip_file    = $this->destination.$what."_DwCA.zip";  //for development it was manually put here, it was copied from editors.eol.org
                                                                    //for production it was downloaded from GBIF during "step: 03 Initialize and download dumps"

        $this->service['country'] = "https://api.gbif.org/v1/node/country/"; //'https://api.gbif.org/v1/node/country/JP';
        $this->service['species'] = "https://api.gbif.org/v1/species/"; //https://api.gbif.org/v1/species/1000148
        $this->service['country_codes'] = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/refs/heads/master/ISO_3166-1/country_codes_2letter.tsv";
        $this->accepted_water_bodies = array("Kattegat", "Skagerrak", "Solomon Sea", "Chukchi Sea", "Red Sea", "Molukka Sea", "Halmahera Sea", "Timor Sea", "Bali Sea", "Davis Strait", "Hudson Strait", "Alboran Sea", "Labrador Sea", "Greenland Sea", "Beaufort Sea", "Celtic Sea", "Singapore Strait", "Kara Sea", "Sulu Sea", "Flores Sea", "North Atlantic", "Java Sea", "Mozambique Channel", "Tasman Sea", "Hudson Bay", "Bering Sea", "Laccadive Sea", "Banda Sea", "Norwegian Sea", "North Sea", "Arafura Sea", "Ligurian Sea", "Baffin Bay", "Bismarck Sea", "Java Sea", "Ceram Sea", "Tasman Sea", "Arctic Ocean", "North Atlantic", "Mozambique Channel", "Hudson Bay", "Aegean Sea", "Barents Sea", "Northwestern Passages", "Indian Ocean", "Malacca Strait", "Adriatic Sea", "Ionian Sea", "English Channel", "Savu Sea", "Laptev Sea", "Bristol Channel", "South Atlantic", "Balearic Sea", "Celebes Sea", "Coral Sea", "Tyrrhenian Sea", "Yellow Sea", "Lincoln Sea", "White Sea", "Aegean Sea", "Makassar Strait", "Barents Sea", "Black Sea", "Northwestern Passages", "Southern Ocean", "Caribbean Sea", "Gulf of Riga", "Gulf of Bothnia", "Gulf of Finland", "Seto Inland Sea", "Eastern China Sea", "Bay of Bengal", "Gulf of Tomini", "Great Australian Bight", "South China Sea", "Gulf of Oman", "Strait of Gibraltar", "Gulf of Boni", "Gulf of Mexico", "East Siberian Sea", "Gulf of Alaska", "Bay of Biscay", "Sea of Marmara", "Sea of Okhostk", "Gulf of Guinea", "Sea of Azov", "Bay of Fundy", "Sea of Japan", "Gulf of Aden", "Gulf of Thailand", "Gulf of Aqaba", "Gulf of California", "Gulf of Suez", "Gulf of St Lawrence", "Rio de la Plata", "Inner Seas off the West Coast of Scotland");
    }
    private function initialize()
    {
        $this->country_code_name_info = self::initialize_countries_from_csv(); //print_r($this->country_code_name_info); exit;
        self::assemble_terms_yml(); //generates $this->uri_values
    }
    function start($counter = false, $task) //$counter is only for caching
    {   //exit("\n[$counter]\n");
        /* may not need this anymore...
        require_library('connectors/GBIFdownloadRequestAPI');
        $func = new GBIFdownloadRequestAPI('Country_checklists');
        $key = $func->retrieve_key_for_taxon('Country_checklists');
        echo "\nkey is: [$key]\n";
        */

        self::initialize();

        // /* main operation
        $tsv_path = self::download_extract_gbif_zip_file();
        echo "\ncsv_path: [$tsv_path]\n";
        // self::parse_tsv_file_caching($tsv_path, $counter); //during caching only; not part of main operation
            if($task == 'divide_into_country_files')    self::parse_tsv_file($tsv_path, $task);
        elseif($task == 'generate_country_checklists')  self::create_individual_country_checklist_resource();
        else exit("\nNo task to do. Will terminate.\n");
        // */
        
        unlink($tsv_path);
        print_r($this->debug);
    }
    private function create_individual_country_checklist_resource()
    {
        $files = $this->country_path . "/*.tsv"; echo "\n[$files]\n";
        foreach(glob($files) as $file) { //echo "\n$file\n"; exit;

            // /*
            $ret = self::get_country_name_from_file($file); //e.g. $file "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Country_checklists/countries/AD.tsv"
            $country_name_lower = $ret['lower_case'];
            $this->country_name = $ret['orig'];
            // */

            /* manual filter - not needed anymore
            if(in_array($country_name_lower, array('andorra'))) continue; //already processed, no need to repeat again.
            */

            // /* ----------- initialize country archive ----------- e.g. DwCA "SC_philippines.tar.gz"
            $folder = "SC_".$country_name_lower;

            if(!self::is_this_DwCA_old_YN($folder.".tar.gz")) { echo "\nAlready recently generated ($folder)\n"; continue; }
            else                                                echo "\nHas not been generated in 2 months ($folder). Will proceed.\n";

            $resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));                
            // */ // ----------- end -----------

            // /*
            require_library('connectors/TraitGeneric');
            $this->func = new TraitGeneric($resource_id, $this->archive_builder);
            // */

            self::parse_tsv_file($file, "process_country_file");
            $this->archive_builder->finalize(TRUE);
            Functions::finalize_dwca_resource($resource_id, false, true, "", CONTENT_RESOURCE_LOCAL_PATH, array('go_zenodo' => false)); //designed not to go to Zenodo at this point.

            // break; //debug only | process just 1 country
        }
    }
    private function parse_tsv_file($file, $task)
    {   echo "\nTask: [$task] [$file]\n";
        $i = 0; $final = array();
        if($task == "divide_into_country_files") $mod = 100000;
        elseif($task == "process_country_file")  $mod = 1000;
        else                                     $mod = 1000;
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % $mod) == 0) echo "\n $i ";
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstop muna\n");
                // ---------------------------------------start
                if($task == "divide_into_country_files") {
                    self::save_to_different_country_files($rec);
                }
                // ---------------------------------------end
                if($task == "process_country_file") {
                    self::process_country_file($rec);
                    // break; //debug only | process just 1 species
                }
            }
            // if($i > 1000) break; //debug only
        }
    }
    private function process_country_file($rec)
    {   /*Array(
            [specieskey] => 1710962
            [countrycode] => AD
        )*/
        $species_info = self::assemble_species($rec); //print_r($species_info); //exit;
        $taxonID = self::write_taxon($species_info);
        if(@$rec['countrycode']) self::write_traits($species_info, $taxonID);
    }
    function assemble_species($rec)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($this->service['species'].$rec['specieskey'], $options)) {
            $rek = json_decode($json, true); //print_r($rek); exit;
            $save = array();
            $save['taxonID']                    = $rek['key']; //same as $rec['specieskey']
            $save['scientificName']             = $rek['scientificName'];
            $save['canonicalName']              = @$rek['canonicalName'];
            $save['scientificNameAuthorship']   = $rek['authorship'];
            $save['taxonRank']                  = strtolower($rek['rank']);
            $save['parentNameUsageID']          = @$rek['parentKey'];
            $save['taxonomicStatus']            = strtolower($rek['taxonomicStatus']);
            $save['furtherInformationURL']      = "https://www.gbif.org/species/".$rek['key'];
            return $save;
        }
        exit("\nSpecies Key not found: [".$rec['specieskey']."]\n");
}
    private function save_to_different_country_files($rec)
    {   /*Array(
            [specieskey] => 2508277
            [countrycode] => FR
        )*/
        $country_code = $rec['countrycode'];
        $file = $this->country_path.'/'.$country_code.'.tsv';
        if(!isset($this->country['encountered'][$country_code])) {
            $this->country['encountered'][$country_code] = '';
            $f = Functions::file_open($file, "w");
            $headers = array_keys($rec);
            fwrite($f, implode("\t", $headers)."\n");
            fclose($f);
        }
        $f = Functions::file_open($file, "a");
        fwrite($f, implode("\t", $rec)."\n");
        fclose($f);
    }
    private function parse_tsv_file_caching($file, $counter = false)
    {   echo "\nReading file: [$file]\n";
        $i = 0; $final = array();

        // /* caching
        $m = 2634653/6;
        // */

        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            // if(($i % 1000) == 0) sleep(30);
            // if(($i % 1000) == 0) echo "\n $i ";
            // echo " [$i $counter]";
            if($i == 1) $fields = explode("\t", $row);
            else {

                // /* breakdown when caching
                $cont = false;
                if($counter == 1)       {if($i >= 1    && $i < $m)    $cont = true;}
                elseif($counter == 2)   {if($i >= $m   && $i < $m*2)  $cont = true;}
                elseif($counter == 3)   {if($i >= $m*2 && $i < $m*3)  $cont = true;}
                elseif($counter == 4)   {if($i >= $m*3 && $i < $m*4)  $cont = true;}
                elseif($counter == 5)   {if($i >= $m*4 && $i < $m*5)  $cont = true;}
                elseif($counter == 6)   {if($i >= $m*5 && $i < $m*6)  $cont = true;}
                else exit("\ncounter not defined...\n");                
                if(!$cont) continue;
                // */

                if(!$row) continue;
                echo " [$i $counter]";
                if(($i % 2000) == 0) sleep(5);

                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstop muna\n");
                /*Array(
                    [specieskey] => 1000148
                    [countrycode] => JP
                )*/
                $options = $this->download_options;
                $options['expire_seconds'] = false;
                // if($json = Functions::lookup_with_cache($this->service['country'].$rec['countrycode'], $options)) {
                    // print_r(json_decode($json, true));
                // }
                if($json = Functions::lookup_with_cache($this->service['species'].$rec['specieskey'], $options)) {
                    // print_r(json_decode($json, true));
                }
                // break;
            }
            // if($i >= 25) break;
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

        /* during dev only
        return "/Volumes/AKiTiO4/other_files/GBIF_occurrence/Country_checklists/0036064-241126133413365.csv";
        */
    }
    private function initialize_countries_from_csv()
    {
        $final = array();
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        $options['cache'] = 1;
        if($filename = Functions::save_remote_file_to_local($this->service['country_codes'], $options)) {
            $i = 0;
            foreach(new FileIterator($filename) as $line_number => $row) { $i++;
                if($i == 1) $fields = explode("\t", $row);
                else {
                    if(!$row) continue;
                    $tmp = explode("\t", $row);
                    $rec = array(); $k = 0;
                    foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                    $rec = array_map('trim', $rec); //print_r($rec); exit("\nstopx\n");
                    /*Array(
                        [Name] => Afghanistan
                        [Code] => AF
                    )*/
                    $final[$rec['Code']] = str_replace('"', '', $rec['Name']);
                }    
            } //end foreach()
            unlink($filename);
        }
        return $final;
    }
    private function get_country_name_from_file($file) //e.g. $file "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Country_checklists/countries/AD.tsv"
    {
        $abbrev = pathinfo($file, PATHINFO_FILENAME); //e.g. "PH"
        if($country_name = @$this->country_code_name_info[$abbrev]) {
            $lower = strtolower(str_replace(" ", "", $country_name));
            echo "\nCountry: [$abbrev] [$country_name] [$lower]\n";
            return array('lower_case' => $lower, 'orig' => $country_name);
        }
        exit("\nCountry abbrev. not found [$abbrev]\n");
    }
    // ======================================= below copied template    
    private function write_taxon($rek)
    {   
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                    = $rek['taxonID'];
        $taxon->scientificName             = $rek['scientificName'];
        $taxon->canonicalName              = $rek['canonicalName'];
        $taxon->scientificNameAuthorship   = $rek['scientificNameAuthorship'];
        $taxon->taxonRank                  = $rek['taxonRank'];
        $taxon->parentNameUsageID          = $rek['parentNameUsageID'];
        $taxon->taxonomicStatus            = $rek['taxonomicStatus'];
        $taxon->furtherInformationURL      = $rek['furtherInformationURL'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
        return $taxon->taxonID;
    }
    private function write_traits($rek, $taxonID)
    {
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $rek['furtherInformationURL'];
        $save['bibliographicCitation'] = $this->bibliographicCitation;        

        $mType = 'http://eol.org/schema/terms/Present';
        if($mValue = self::get_country_uri($this->country_name)) {
            $save['measurementRemarks'] = $this->country_name;
            $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
            // if(in_array($mValue, $this->investigate)) exit("\nhuli ka 2\n");
            $this->func->add_string_types($save, $mValue, $mType, "true");
        }
        else $this->debug['undefined country'][$this->country_name] = '';
    }
    private function get_country_uri($country)
    {   //Antigua and Barbuda; what is saved in EOL terms file is: "Antigua And Barbuda"
        $country = str_replace(" and ", " And ", $country);

        if($country_uri = @$this->uri_values[$country]) return $country_uri;
        else {
            // /*
            if($country == "ÅLand Islands") return "https://www.geonames.org/661883";
            switch ($country) { //put here customized mapping
                case "ÅLand Islands":        return "https://www.geonames.org/661883";
                /* copied template
                case "United States of America":        return "http://www.wikidata.org/entity/Q30";
                case "Dutch West Indies":               return "http://www.wikidata.org/entity/Q25227";
                */
            }
            // */
        }
        // print_r($this->uri_values); //debug only
        echo ("\nNo URI for [$country]");
        return false;
    }
    private function assemble_terms_yml()
    {
        require_library('connectors/EOLterms_ymlAPI');
        $func = new EOLterms_ymlAPI(false, false);
        $ret = $func->get_terms_yml('value'); //sought_type is 'value' --- REMINDER: labels can have the same value but different uri
        foreach($ret as $label => $uri) $this->uri_values[$label] = $uri;
        // print_r($this->uri_values); 
        echo("\nEOL Terms: ".count($this->uri_values)."\n"); //debug only
    }
    function is_this_DwCA_old_YN($filename) //SC_andorra.tar.gz
    {
        $filename_date = self::get_date_of_this_DwCA($filename);
        echo "\ndate of $filename: $filename_date\n";
        // get date today minus 2 months
        $date = date("Y-m-d");
        $today = date_create($date);
        echo "\n-------new...\ntoday: ".date_format($today, 'Y-m-d')."\n";
        date_sub($today, date_interval_create_from_date_string('2 month')); //previously '2 months'
        $minus_2_months = date_format($today, 'Y-m-d');
        // compare
        echo "minus 1 month: " .$minus_2_months. "\n";
        echo "\n$filename_date < $minus_2_months \n";
        if($filename_date < $minus_2_months) return true;
        else return false;
    }
    private function get_date_of_this_DwCA($filename)
    {
        // /* NEW:
        $file = CONTENT_RESOURCE_LOCAL_PATH . $filename;
        if(file_exists($file)) return date("Y-m-d", filemtime($file));
        else                   return date("Y-m-d", false);
        // */
        /* OLD:
        $file = CONTENT_RESOURCE_LOCAL_PATH.'wikipedia-'.$filename.'.tar.gz';
        if(file_exists($file)) return date("Y-m-d", filemtime($file));
        else                   return date("Y-m-d", false);
        */
    }

}
?>