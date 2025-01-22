<?php
namespace php_active_record;
/* connector: [national_checklists_2024.php] */
class WaterBodyChecklistsAPI
{
    public function __construct($what) //typically param $folder is passed here.
    {
        /* copied template
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */

        $this->download_options = array('resource_id' => "gbif_ctry_checklists", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000/2, 
        'timeout' => 10800*2, 'download_attempts' => 3, 'delay_in_minutes' => 5); //3 months to expire
        $this->download_options['expire_seconds'] = false; //doesn't expire

        $this->debug = array();
        $this->bibliographicCitation = "GBIF.org (5 January 2025) GBIF Occurrence Download https://doi.org/10.15468/dl.7hzepx"; //todo: get it dynamically
            // https://api.gbif.org/v1/occurrence/download/0056704-241126133413365
            // GBIF.org (20 January 2025) GBIF Occurrence Download https://doi.org/10.15468/dl.f25h68 ---> with datasetKey filter

        if(Functions::is_production())  $this->destination = "/extra/other_files/GBIF_occurrence/".$what."/";
        else                            $this->destination = "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/".$what."/";
        
        $this->report_1 = $this->destination . "waterbodies.tsv";
        $this->report_2 = $this->destination . "run_waterbodies.tsv";

        if(!is_dir($this->destination)) mkdir($this->destination);
        $this->waterbody_path = $this->destination.'waterbodies';
        if(!is_dir($this->waterbody_path)) mkdir($this->waterbody_path);
        $this->zip_file    = $this->destination.$what."_DwCA.zip";  //for development it was manually put here, it was copied from editors.eol.org
                                                                    //for production it was downloaded from GBIF during "step: 03 Initialize and download dumps"

        $this->service['country'] = "https://api.gbif.org/v1/node/country/"; //'https://api.gbif.org/v1/node/country/JP';
        $this->service['species'] = "https://api.gbif.org/v1/species/"; //https://api.gbif.org/v1/species/1000148
        $this->service['country_codes'] = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/refs/heads/master/ISO_3166-1/country_codes_2letter.tsv";
        $arr1 = array('Kattegat', 'Skagerrak', 'Solomon Sea', 'Chukchi Sea', 'Red Sea', 'Molukka Sea', 'Halmahera Sea', 'Timor Sea', 'Bali Sea', 'Davis Strait', 'Hudson Strait', 'Alboran Sea', 'Labrador Sea', 'Greenland Sea', 'Beaufort Sea', 'Celtic Sea', 'Singapore Strait', 'Kara Sea', 'Sulu Sea', 'Flores Sea', 'North Atlantic', 'Java Sea', 'Mozambique Channel', 'Tasman Sea', 'Hudson Bay', 'Bering Sea', 'Laccadive Sea', 'Banda Sea', 'Norwegian Sea', 'North Sea', 'Arafura Sea', 'Ligurian Sea', 'Baffin Bay', 'Bismarck Sea', 'Ceram Sea', 'Arctic Ocean', 'Aegean Sea', 'Barents Sea', 'Northwestern Passages', 'Indian Ocean', 'Malacca Strait', 'Adriatic Sea', 'Ionian Sea', 'English Channel', 'Savu Sea', 'Laptev Sea', 'Bristol Channel', 'South Atlantic', 'Balearic Sea', 'Celebes Sea', 'Coral Sea', 'Tyrrhenian Sea', 'Yellow Sea', 'Lincoln Sea', 'White Sea', 'Makassar Strait', 'Black Sea', 'Southern Ocean', 'Caribbean Sea', 'Gulf of Riga', 'Gulf of Bothnia', 'Gulf of Finland', 'Seto Inland Sea', 'Eastern China Sea', 'Bay of Bengal', 'Gulf of Tomini', 'Great Australian Bight', 'South China Sea', 'Gulf of Oman', 'Strait of Gibraltar', 'Gulf of Boni', 'Gulf of Mexico', 'East Siberian Sea', 'Gulf of Alaska', 'Bay of Biscay', 'Sea of Marmara', 'Sea of Okhostk', 'Gulf of Guinea', 'Sea of Azov', 'Bay of Fundy', 'Sea of Japan', 'Gulf of Aden', 'Gulf of Thailand', 'Gulf of Aqaba', 'Gulf of California', 'Gulf of Suez', 'Gulf of St Lawrence', 'Rio de la Plata', 'Inner Seas off the West Coast of Scotland');
        $arr2 = array('South Pacific', 'North Pacific', 'Philippine Sea', 'Persian Gulf', 'Irish Sea', 'Bass Strait', 'Arabian Sea', 'Andaman Sea'); //'South America', 'North America'
        $this->AnneT_water_bodies = array_merge($arr1, $arr2);
        /* not applicable for waterbody checklist
        $this->waterbdy_map['Palestine, State of'] = "Palestine";
        */
        $this->proceed = false;
        $tmp = CONTENT_RESOURCE_LOCAL_PATH.'/metadata';
        if(!is_dir($tmp)) mkdir($tmp);
    }
    function generate_report($what) //'waterbodies' or 'countries'
    {
        $report = $this->destination . $what . "_report.tsv";
        if($what == 'countries') $report = str_replace('WaterBody_checklists', 'Country_checklists', $report);
        if(file_exists($report)) unlink($report);
        // /*
        require_library('connectors/DwCA_Utility');
        // */
        $file = $this->destination . "$what.tsv";
        if($what == 'countries') $file = str_replace('WaterBody_checklists', 'Country_checklists', $file);
        if(!file_exists($file)) exit("\nFile does not exist [$file]\n");
        $i = 0;
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(!trim($row)) continue;
            if(($i % 2000) == 0) echo "\n $i ";
            if($i == 1) { $fields = explode("\t", $row); continue; }
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); print_r($rec); //exit("\nstop muna\n");
            }
            /*Array(
                [orig] => Caribbean Sea
                [dwca] => SC_caribbeansea
            )
            Array(
                [lower_case] => unitedarabemirates
                [orig] => United Arab Emirates
                [abbrev] => AE
                [dwca] => SC_unitedarabemirates
            )*/
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH . $rec['dwca'] . '.tar.gz';
            $dwca_remote = WEB_ROOT . 'applications/content_server/resources/' . $rec['dwca'] . '.tar.gz';

            $resource_id = "";
            $params['row_type'] = 'http://rs.tdwg.org/dwc/terms/measurementorfact';
            $params['column'] = 'http://rs.tdwg.org/dwc/terms/measurementValue';
            $download_options = array("timeout" => 172800, 'expire_seconds' => 0); //0 is the ideal value      //60*60*24*1 = 1 day cache
            // 2 new params - for a new feature
            $params['sought_field']       = 'http://rs.tdwg.org/dwc/terms/measurementType';
            $params['sought_field_value'] = 'http://eol.org/schema/terms/Present';
            // */
                        
            $func = new DwCA_Utility($resource_id, $dwca_file);
            $rek = $func->lookup_values_in_dwca($download_options, $params); //get unique values of a column in any table in a DwCA
            if($what == 'countries') {
                $rek['Country'] = $rec['orig'];
                $rek['Abbreviation'] = $rec['abbrev'];
            }
            else {
                $rek['Waterbody'] = $rec['orig'];
            }
            $rek['DwCA'] = $dwca_remote;
            print_r($rek); //exit;

            if(!file_exists($report)) {
                $f = Functions::file_open($report, "w");
                fwrite($f, implode("\t", array_keys($rek))."\n");
                fwrite($f, implode("\t", $rek)."\n");    
            }
            else {
                $f = Functions::file_open($report, "a");
                fwrite($f, implode("\t", $rek)."\n");    
            }
            // if($i >= 3) break; //debug only
        } //foreach()
        fclose($f);
    }
    private function initialize()
    {
        /* not applicable
        $this->country_code_name_info = self::initialize_waterbodies_from_csv(); //print_r($this->country_code_name_info); exit;
        */
        self::assemble_terms_yml(); //generates $this->value_uris
        if(self::get_waterbody_uri('Adriatic Sea')  == 'http://www.marineregions.org/mrgid/3314') echo "\nAdriatic Sea: OK";        else exit("\nERROR: Investigate country URI.\n");
        if(self::get_waterbody_uri('Aegean Sea')    == 'http://www.marineregions.org/mrgid/3315') echo "\nAegean Sea: OK";          else exit("\nERROR: Investigate country URI.\n");
        // exit("\n--- stop muna...\n");

        // /*
        require_library('connectors/ZenodoConnectorAPI');
        require_library('connectors/ZenodoAPI');
        $this->zenodo = new ZenodoAPI();
        // */

        // /*
        require_library('connectors/GBIFTaxonomyAPI');
        $this->GBIFTaxonomy = new GBIFTaxonomyAPI('WaterBody_checklists');
        // */
    }
    function start($fields) //start($counter = false, $task, $sought_waterbdy = false) //$counter is only for caching
    {   //exit("\n[$counter]\n");
        /* may not need this anymore...
        require_library('connectors/GBIFdownloadRequestAPI');
        $func = new GBIFdownloadRequestAPI('WaterBody_checklists');
        $key = $func->retrieve_key_for_taxon('WaterBody_checklists');
        echo "\nkey is: [$key]\n";
        */
        $counter     = @$fields['counter'];
        $task        = @$fields['task'];
        $sought_waterbdy = @$fields['sought_waterbdy'];
        $report_name        = @$fields['report_name'];

        $this->task = $task;
        if($task == 'generate_report') {
            self::generate_report($report_name); //'waterbodies' or 'countries'
            return;
        }
        elseif($task == 'show_waterbodies_metadata') {
            self::show_waterbodies_metadata();
            return;
        }

        self::initialize();

        // /* main operation
        $tsv_path = self::download_extract_gbif_zip_file();
        echo "\ncsv_path: [$tsv_path]\n";

        /*
        self::parse_tsv_file_caching($tsv_path, $counter); //during caching only; not part of main operation
        */

        if($task == 'divide_into_waterbody_files') {
            // /* remove current /waterbodies/ folder
            recursive_rmdir($this->waterbody_path); echo ("\nFolder removed: " . $this->waterbody_path);
            if(!is_dir($this->waterbody_path)) mkdir($this->waterbody_path);
            // */
            self::parse_tsv_file($tsv_path, $task);

            // /* good debug: investigated: adriatic_sea.tsv AND indian_ocean.tsv
            echo "\n[2440447][Adriatic Sea]:9 ".$this->save['Adriatic Sea']['2440447']."\n";
            echo "\n[2440718][Adriatic Sea]:4 ".$this->save['Adriatic Sea']['2440718']."\n";
            echo "\n[2509716][Adriatic Sea]:15 ".$this->save['Adriatic Sea']['2509716']."\n";
            echo "\n[2333135][Indian Ocean]:5 ".$this->save['Indian Ocean']['2333135']."\n";
            echo "\n[2391929][Indian Ocean]:11 ".$this->save['Indian Ocean']['2391929']."\n";
            echo "\n[2391811][Indian Ocean]:2 ".$this->save['Indian Ocean']['2391811']."\n";
            echo "\n[2392074][Indian Ocean]:2 ".$this->save['Indian Ocean']['2392074']."\n"; //exit;
            // */
            // /* utility: check what waterbodies in AnneT that were not found in GBIF
            self::compare_waterbodies();
            // */
            self::write_waterbody_tsv_files();
            exit("\nstop 5\n");
        }
        elseif($task == 'generate_waterbody_checklists')  self::create_individual_waterbody_checklist_resource($counter, $task, $sought_waterbdy);
        elseif($task == 'major_deletion')                 self::create_individual_waterbody_checklist_resource($counter, $task);
        elseif($task == 'generate_waterbody_compiled')    self::proc_waterbody_compiled();


        else exit("\nNo task to do. Will terminate.\n");
        // */
        
        if(file_exists($tsv_path)) unlink($tsv_path);
        print_r($this->debug);
    }
    private function compare_waterbodies()
    {
        // print_r($this->debug['waterbody not in AnneT']);
        // $this->debug['waterbody in AnneT'][$waterbody] = '';
        // $this->AnneT_water_bodies
        foreach($this->AnneT_water_bodies as $wb) {
            if(!isset($this->debug['waterbody in AnneT'][$wb])) $not_found[$wb] = '';
        }
        // print_r($this->debug['GBIF waterbodies']); //good debug and if u want to investigate
        print_r($not_found); echo " - AnneT waterbodies not found in GBIF: [".count($not_found)."]";
        echo "\nTotal AnneT waterbodies: [".count($this->AnneT_water_bodies)."]";
        $diff = count($this->AnneT_water_bodies) - count($not_found);
        echo "\nTotal TSV generated should be: [".$diff."]\n";
        /*Array(
            [Lincoln Sea] => 
            [Gulf of Riga] => 
            [Sea of Okhostk] => 
        )
        - AnneT waterbodies not found in GBIF */

        // print_r($this->debug['waterbody in AnneT']); exit("\nelix 2\n");
        $f = Functions::file_open($this->destination."waterbodies_AnneThessen.tsv", "w");
        foreach(array_keys($this->debug['waterbody in AnneT']) as $waterbody) if($waterbody) fwrite($f, $waterbody."\n");
        fclose($f);

        unset($this->debug['GBIF waterbodies']);
        unset($this->debug['waterbody in AnneT']);
        unset($this->debug['waterbody not in AnneT']);
    }
    function show_waterbodies_metadata() //utility
    {   $cont = false; //debug only

        if(file_exists($this->report_1)) unlink($this->report_1);
        if(file_exists($this->report_2)) unlink($this->report_2);

        self::initialize();
        $files = $this->waterbody_path . "/*.tsv"; echo "\n[$files]\n"; $i = 0;
        // foreach(glob($files) as $file) { //echo "\n$file\n"; exit;
        foreach(new FileIterator($this->destination.'waterbodies_AnneThessen.tsv') as $line => $row) { $i++; //e.g. "Adriatic Sea"
            if(!trim($row)) continue;
            if($dwca_filename = self::get_dwca_filename($row)) echo "\ndwca_filename: [$dwca_filename]\n"; //SC_andorra
            else exit("\nTerminated: should not go here 02.\n");

            $ret = array();
            $ret['orig'] = $row;
            $ret['dwca'] = $dwca_filename;

            if(!file_exists($this->report_1)) {
                $f = Functions::file_open($this->report_1, "w");
                fwrite($f, implode("\t", array_keys($ret))."\n");
                fwrite($f, implode("\t", $ret)."\n");    
            }
            else {
                $f = Functions::file_open($this->report_1, "a");
                fwrite($f, implode("\t", $ret)."\n");    
            }

            $f2 = Functions::file_open($this->report_2, "a");
            fwrite($f2, "php fill_up_undefined_parents_real_GBIFChecklists.php _ '{\"resource_id\": \"$dwca_filename\", \"source_dwca\": \"$dwca_filename\", \"resource\": \"fillup_missing_parents_GBIFChecklists\"}'"."\n");

            // break; //debug only | process just 1 record
            // if($i > 5) break; //debug only
        } //end foreach()
        fclose($f);
        fclose($f2);
        print_r($this->debug);
    }

    private function evaluate_waterbody_file($file)
    {   
        exit("\nnot applicable 1\n");
        /* not applicable
        $ret = self::get_waterbody_name_from_file($file); //e.g. $file "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/WaterBody_checklists/waterbodies/AD.tsv"
        $waterbody_name_lower = $ret['lower_case'];
        $this->waterbody_name = $ret['orig'];
        print_r($ret); exit;
        if(!in_array($this->waterbody_name, $this->AnneT_water_bodies)) {
            if($val = @$this->waterbdy_map[$this->waterbody_name]) {
                $this->waterbody_name = $val;
                if(!in_array($this->waterbody_name, $this->AnneT_water_bodies)) {
                    echo "\nNot mapped* [$this->waterbody_name]";
                    $this->debug['Not mapped*'][$this->waterbody_name] = '';
                    return false; //not mapped to Anne's checklists    
                }
            }
            else {
                echo "\nNot mapped** [$this->waterbody_name]";
                $this->debug['Not mapped**'][$this->waterbody_name] = '';
                return false; //not mapped to Anne's checklists
            }
        }
        $ret['orig'] = $this->waterbody_name;
        return $ret;
        */
    }
    private function create_individual_waterbody_checklist_resource($counter = false, $task, $sought_waterbdy = false)
    {
        // /* caching
        $m = 252/6; //252 waterbodies
        $i = 0;
        // */

        // foreach(new FileIterator($this->destination.'waterbodies_AnneThessen.tsv') as $line => $row) { $i++;
        //     if(!$row) continue;
        //     echo "\n$row";
        // }//foreach()
        // exit("\nend 2\n");
        
        // $files = $this->waterbody_path . "/*.tsv"; echo "\n[$files]\n";
        // foreach(glob($files) as $file) { $i++; //echo "\n$file\n"; exit;
        foreach(new FileIterator($this->destination.'waterbodies_AnneThessen.tsv') as $line => $row) { $i++; //e.g. "Adriatic Sea"
            /* good debug: if u want to start processing at this record --- works OK
            if($row == 'Inner Seas off the West Coast of Scotland') $this->proceed = true;
            if(!$this->proceed) continue;
            */

            $waterbody_name_lower = str_replace(" ", "_", strtolower($row)); //e.g. "adriatic_sea"
            $file = $this->waterbody_path . "/".$waterbody_name_lower.".tsv";

            // /* breakdown when caching
            if($counter) {
                $cont = false;
                if($counter == 1)       {if($i >= 1    && $i < $m)    $cont = true;}
                elseif($counter == 2)   {if($i >= $m   && $i < $m*2)  $cont = true;}
                elseif($counter == 3)   {if($i >= $m*2 && $i < $m*3)  $cont = true;}
                elseif($counter == 4)   {if($i >= $m*3 && $i < $m*4)  $cont = true;}
                elseif($counter == 5)   {if($i >= $m*4 && $i < $m*5)  $cont = true;}
                elseif($counter == 6)   {if($i >= $m*5 && $i < $m*6)  $cont = true;}
                else exit("\ncounter not defined...\n");                
                if(!$cont) continue;    
            }
            // */

            if($this->waterbody_name = $row) {
                // $waterbody_name_lower = $ret['lower_case'];
                // $this->waterbody_name = $ret['orig'];

                /* manual filter, dev only
                if(in_array($this->waterbody_name, array('Philippines'))) continue;
                */

                if($sought_waterbdy) {
                    if(!in_array($this->waterbody_name, array($sought_waterbdy))) continue;
                }

                if($row == 'United States') $dwca_filename = 'SC_unitedstates';
                else {
                    if($dwca_filename = self::get_dwca_filename($row)) echo "\ndwca_filename: [$dwca_filename]\n"; //SC_andorra
                    else exit("\nTerminated: should not go here 02.\n");
                    // /* major file deletion
                    if($task == 'major_deletion') {
                        $delete_file = CONTENT_RESOURCE_LOCAL_PATH . $dwca_filename . ".tar.gz";
                        if(file_exists($delete_file)) {
                            if(unlink($delete_file)) echo "\nFile deleted OK [$delete_file]\n";
                            else                     echo "\nFile NOT deleted [$delete_file]\n";
                        }    
                    }
                    // */
                }                
            }
            else continue;

            if(file_exists($file)) echo("\nFile exists: [$file]\n");
            else exit("\nFile does not exist: [$file]\n");

            // exit("\nstop muna\n"); //dev only
            // break; //dev only
            // continue; //dev only

            // /* during major file deletion
            if($task == 'major_deletion') continue;
            // */

            // /* ----------- initialize country archive ----------- e.g. DwCA "SC_philippines.tar.gz"
            // if(substr($waterbody_name_lower,0,4) == "the ")                                               $waterbody_name_lower = str_ireplace("the ", "", $waterbody_name_lower); //The Bahamas => SC_bahamas.tar.gz
                if(strtolower($this->waterbody_name) == strtolower("Democratic Republic of the Congo"))   $waterbody_name_lower = "congo";
            elseif(strtolower($this->waterbody_name) == strtolower("Republic of the Congo"))              $waterbody_name_lower = "repubcongo";

            // $folder = "SC_".$waterbody_name_lower; //obsolete
            $folder = $dwca_filename;            //latest

            // /* main operation | uncomment in real operation
            if($sought_waterbdy) {}
            else {
                if(!self::is_this_DwCA_old_YN($folder.".tar.gz")) { echo "\nAlready recently generated ($folder)\n"; continue; }
                else                                                echo "\nHas not been generated in 2 months ($folder). Will proceed.\n";    
            }
            // */

            if(!$folder) exit("\nfolder not defined [$folder]\n");
            self::proc_waterbody($folder, $file);
            // break; //debug only | process just 1 country
            // if($i >= 3) break; //dev only
        }
    }
    private function proc_waterbody($folder, $file)
    {
        $this->taxon_ids = array(); //very important
        $resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));                
        // */ // ----------- end -----------

        // /*
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($resource_id, $this->archive_builder);
        // */

        self::parse_tsv_file($file, "process_waterbody_file");
        $this->archive_builder->finalize(TRUE);
        Functions::finalize_dwca_resource($resource_id, false, true, "", CONTENT_RESOURCE_LOCAL_PATH, array('go_zenodo' => false)); //designed not to go to Zenodo at this point.
    }
    private function proc_waterbody_compiled()
    {
        $file = $this->waterbody_path . "/waterbody_compiled.tsv";
        $folder = 'waterbody_compiled';

        $this->taxon_ids = array(); //very important
        $resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));                
        // */ // ----------- end -----------

        // /*
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($resource_id, $this->archive_builder);
        // */

        self::parse_tsv_file($file, "process_waterbody_file");
        $this->archive_builder->finalize(TRUE);
        Functions::finalize_dwca_resource($resource_id, false, true, "", CONTENT_RESOURCE_LOCAL_PATH, array('go_zenodo' => false)); //designed not to go to Zenodo at this point.
    }
    private function parse_tsv_file($file, $task)
    {   echo "\nTask: [$task] [$file]\n";
        $i = 0; $final = array();
        if($task == "divide_into_waterbody_files")      $mod = 100000;
        elseif($task == "process_waterbody_file")       $mod = 5000; //1000;
        else                                            $mod = 1000;
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % $mod) == 0) echo "\n $i ";
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");
                // ---------------------------------------start
                if($task == "divide_into_waterbody_files") {
                    /*Array(
                        [specieskey] => 1000607
                        [COUNT(specieskey)] => 2
                        [waterbody] => Pardo
                    )*/
                    self::save_to_different_waterbody_files_v1($rec); //for stats only
                    self::save_to_different_waterbody_files($rec);

                }
                // ---------------------------------------end
                if($task == "process_waterbody_file") { //print_r($rec); //exit("\nelix 1\n");
                    self::process_waterbody_file($rec);
                    // break; //debug only | process just 1 species
                }
            }
            // if($i > 1000) break; //debug only
        }
    }
    private function process_waterbody_file($rec)
    {   /*Array(
            [specieskey] => 1710962
            [SampleSize] => 16
            [countrycode] => AD
        )
        Array(
            [specieskey] => 5962668
            [SampleSize] => 41
            [waterbody] => Adriatic Sea
            [remark] => Adriatic Sea
        )*/
        if($species_info = self::assemble_species($rec)) { //print_r($species_info); //exit;
            if(!in_array($species_info['taxonomicStatus'], array('doubtful'))) {
                $taxonID = self::write_taxon($species_info);
                $species_info['SampleSize'] = $rec['SampleSize'];
                $species_info['measurementRemarks'] = $rec['waterbody']; //$rec['remark'];
                if(@$rec['waterbody']) self::write_traits($species_info, $taxonID);    
            }
        }
    }
    function assemble_species($rec)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //should not expire; false is the right value.
        if($json = Functions::lookup_with_cache($this->service['species'].$rec['specieskey'], $options)) {
            $rek = json_decode($json, true); //print_r($rek); exit;
            
            if(!@$rek['key']) return false;
            if(!$this->GBIFTaxonomy->is_id_valid_waterbody_taxon($rec['specieskey'])) return false;

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
        exit("\n--------------------\nSpecies Key not found: [".$rec['specieskey']."]\nProgram will terminate.\n--------------------\n");
    }
    private function save_to_different_waterbody_files($rec)
    {   /*Array(
            [specieskey] => 1000607
            [COUNT(specieskey)] => 2
            [waterbody] => Pardo, sssyy of sss, xxx, sss of yyy
        )*/
        $orig = $rec['waterbody'];
        $waterbodies = explode(",", $rec['waterbody']);
        $waterbodies = array_map('trim', $waterbodies);
        foreach($waterbodies as $waterbody) {
            $specieskey = $rec['specieskey'];
            $count = $rec['COUNT(specieskey)'];
            $waterbody = self::massage_waterbody($waterbody);
            @$this->save[$waterbody][$specieskey] += $count;
        }
    }
    private function write_waterbody_tsv_files()
    {
        // /* new: 1 big waterbody resource
        $file_compiled = $this->waterbody_path.'/waterbody_compiled.tsv';
        if(file_exists($file_compiled)) unlink($file_compiled);
        // */

        $exclude = Array('Lincoln Sea', 'Gulf of Riga', 'Sea of Okhostk');
        foreach($this->AnneT_water_bodies as $waterbody) {
            if(in_array($waterbody, $exclude)) continue;
            foreach($this->save[$waterbody] as $specieskey => $count) { // echo "\n[$waterbody] [$specieskey] [$count]";
                // /* start writing OK
                $waterbody_code = str_replace(" ", "_", strtolower($waterbody));
                $file = $this->waterbody_path.'/'.$waterbody_code.'.tsv';
                $rec = array();
                $rec['specieskey'] = $specieskey;
                $rec['SampleSize'] = $count;
                $rec['waterbody'] = $waterbody;
                if(!isset($this->waterbody['encountered'][$waterbody_code])) {
                    $this->waterbody['encountered'][$waterbody_code] = '';
                    $f = Functions::file_open($file, "w");
                    $headers = array_keys($rec);
                    $headers = self::use_label_SampleSize_forCount($headers);
                    fwrite($f, implode("\t", $headers)."\n");
                    fclose($f);
                }

                // /* for 1 big waterbody resource
                if(!file_exists($file_compiled)) {
                    $f2 = Functions::file_open($file_compiled, "w");
                    $headers = array_keys($rec);
                    $headers = self::use_label_SampleSize_forCount($headers);
                    fwrite($f2, implode("\t", $headers)."\n");
                    fwrite($f2, implode("\t", $rec)."\n");
                }
                else fwrite($f2, implode("\t", $rec)."\n");
                // */                

                $f = Functions::file_open($file, "a");
                fwrite($f, implode("\t", $rec)."\n");
                fclose($f);
                // */
            } //foreach()
            // break; //dev only | process just 1 waterbody
        } //foreach()
        fclose($f2);
    }
    private function save_to_different_waterbody_files_v1($rec)
    {   /*Array(
            [specieskey] => 1000607
            [COUNT(specieskey)] => 2
            [waterbody] => Pardo, sssyy of sss, xxx, sss of yyy
        )*/
        $orig = $rec['waterbody'];
        $waterbodies = explode(",", $rec['waterbody']);
        $waterbodies = array_map('trim', $waterbodies);
        foreach($waterbodies as $waterbody) {
            // /* massage
            $waterbody = self::massage_waterbody($waterbody);
            // */
            $this->debug['GBIF waterbodies'][$waterbody] = '';
            if(!in_array($waterbody, $this->AnneT_water_bodies)) {
                $this->debug['waterbody not in AnneT'][$waterbody] = '';
                continue;
            }
            else $this->debug['waterbody in AnneT'][$waterbody] = '';
            // print_r($rec);
            /* start writing OK
            $waterbody_code = str_replace(" ", "_", strtolower($waterbody));
            $file = $this->waterbody_path.'/'.$waterbody_code.'.tsv';
            $rec['remark'] = $orig;
            $rec['waterbody'] = $waterbody;
            if(!isset($this->waterbody['encountered'][$waterbody_code])) {
                $this->waterbody['encountered'][$waterbody_code] = '';
                $f = Functions::file_open($file, "w");
                $headers = array_keys($rec);
                $headers = self::use_label_SampleSize_forCount($headers);
                fwrite($f, implode("\t", $headers)."\n");
                fclose($f);
            }
            $f = Functions::file_open($file, "a");
            fwrite($f, implode("\t", $rec)."\n");
            fclose($f);
            */
        } //foreach()
    }
    private function massage_waterbody($waterbody)
    {
        $waterbody = str_replace(' Of ', ' of ', $waterbody);
        if($waterbody == 'Azov Sea')                    $waterbody = 'Sea of Azov';
        if($waterbody == 'The Northwestern Passages')   $waterbody = 'Northwestern Passages';
        if($waterbody == 'Northwest Passages')          $waterbody = 'Northwestern Passages';
        if($waterbody == 'East-Siberian Sea')           $waterbody = 'East Siberian Sea';
        if($waterbody == 'Gulf of St. Lawrence')        $waterbody = 'Gulf of St Lawrence';
        if($waterbody == 'Gulf of Saint Lawrence')      $waterbody = 'Gulf of St Lawrence';
        if($waterbody == 'Marmara Sea')                 $waterbody = 'Sea of Marmara';
        if($waterbody == 'Arabian Sea - Gulf of Aden')  $waterbody = 'Gulf of Aden';
        // ---------------------------
        if($waterbody == 'South Pacific Ocean')         $waterbody = 'South Pacific';
        if($waterbody == 'North Pacific Ocean')         $waterbody = 'North Pacific';
        if($waterbody == 'Arabian Sea - Gulf of Aden')  $waterbody = 'Arabian Sea';
        // ---------------------------
        // if($waterbody == 'SOUTH AMERICA {LakeID}')      $waterbody = 'South America'; //originally a national checklist | no longer used

        /* start special
        if(in_array($waterbody, array('Oceania', 'Asia', 'Europe', 'Africa'))) {
        }
        */
        return $waterbody;
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
                $options['expire_seconds'] = false; //false is the right value
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
        return "/Volumes/AKiTiO4/other_files/GBIF_occurrence/WaterBody_checklists/0036064-241126133413365.csv";
        */
    }
    /* not applicable
    private function initialize_waterbodies_from_csv()
    {
        $final = array();
        $options = $this->download_options;
        $options['expire_seconds'] = false; //false is the right value
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
                    // Array(
                    //     [Name] => Afghanistan
                    //     [Code] => AF
                    // )
                    $final[$rec['Code']] = str_replace('"', '', $rec['Name']);
                }    
            } //end foreach()
            unlink($filename);
        }
        return $final;
    }*/
    private function get_waterbody_name_from_file($file) //e.g. $file "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/WaterBody_checklists/waterbodies/AD.tsv"
    {
        $abbrev = pathinfo($file, PATHINFO_FILENAME); //e.g. "PH"
        if($waterbody_name = @$this->country_code_name_info[$abbrev]) {
            $lower = strtolower(str_replace(" ", "", $waterbody_name));
            echo "\nCountry: [$abbrev] [$waterbody_name] [$lower]\n";
            return array('lower_case' => $lower, 'orig' => $waterbody_name, 'abbrev' => $abbrev);
        }
        echo("\nCountry abbrev. not found [$abbrev]\n");
        $this->debug['Country abbrev. not found'][$abbrev] = '';
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
    {   //print_r($rek); exit("\nelix\n");
        /*Array(
            [taxonID] => 2418684
            [scientificName] => Scyliorhinus canicula (Linnaeus, 1758)
            [canonicalName] => Scyliorhinus canicula
            [scientificNameAuthorship] => (Linnaeus, 1758) 
            [taxonRank] => species
            [parentNameUsageID] => 9171444
            [taxonomicStatus] => accepted
            [furtherInformationURL] => https://www.gbif.org/species/2418684
            [SampleSize] => 1
            [measurementRemarks] => Kattegat
        )*/
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $rek['furtherInformationURL'];
        $save['bibliographicCitation'] = $this->bibliographicCitation;        

        $mType = 'http://eol.org/schema/terms/Present';

        if($this->task == 'generate_waterbody_compiled') $tmp_waterbody = $rek['measurementRemarks'];   //for 1 big resource
        else                                             $tmp_waterbody = $this->waterbody_name;        //orig 

        if($mValue = self::get_waterbody_uri($tmp_waterbody, 1)) {
            $save['measurementRemarks'] = $rek['measurementRemarks'];
            $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
            // if(in_array($mValue, $this->investigate)) exit("\nhuli ka 2\n");
            $ret = $this->func->add_string_types($save, $mValue, $mType, "true");
        }
        // ---------------- write child record in MoF: SampleSize
        /*
        child record in MoF:
            - doesn't have: occurrenceID | measurementOfTaxon
            - has parentMeasurementID
            - has also a unique measurementID, as expected.
        minimum cols on a child record in MoF
            - measurementID
            - measurementType
            - measurementValue
            - parentMeasurementID
        */
        if($measurementID = $ret['measurementID']) {
            if($measurementValue = @$rek['SampleSize']) {
                $measurementType = "http://eol.org/schema/terms/SampleSize";
                $parentMeasurementID = $measurementID;
                self::write_child($measurementType, $measurementValue, $parentMeasurementID);
            }    
        }
    }
    private function write_child($measurementType, $measurementValue, $parentMeasurementID) //func was copied from: Move_col_inMoF_2child_inMoF_API.php
    {
        $m2 = new \eol_schema\MeasurementOrFact_specific();
        $rek = array();
        $rek['http://rs.tdwg.org/dwc/terms/measurementID'] = md5("$measurementType|$measurementValue|$parentMeasurementID");
        $rek['http://rs.tdwg.org/dwc/terms/measurementType'] = $measurementType;
        $rek['http://rs.tdwg.org/dwc/terms/measurementValue'] = $measurementValue;
        $rek['http://eol.org/schema/parentMeasurementID'] = $parentMeasurementID;
        $uris = array_keys($rek);
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);
            $m2->$field = $rek[$uri];
        }
        if(!isset($this->measurementIDs[$m2->measurementID])) {
            $this->measurementIDs[$m2->measurementID] = '';
            $this->archive_builder->write_object_to_file($m2);
        }
    }
    private function get_waterbody_uri($waterbody, $what = 173)
    {   //Antigua and Barbuda; what is saved in EOL terms file is: "Antigua And Barbuda"
        $waterbody = str_replace(" and ", " And ", $waterbody);
        $waterbody = str_replace(" of ", " Of ", $waterbody);
        $waterbody = str_replace(" the ", " The ", $waterbody);
        $waterbody = str_replace(" off ", " Off ", $waterbody);


        // /* manual mapping
        if($waterbody == 'Cocos Islands') $waterbody = 'Cocos [Keeling] Islands';
        if($waterbody == 'Federated States Of Micronesia') $waterbody = 'Micronesia';
        if($waterbody == 'xxx') $waterbody = 'yyy';
        // */

        if($uris = @$this->value_uris[$waterbody]) {
            if(count($uris) == 1) return $uris[0];
            else {
                foreach($uris as $uri) {                    
                    if(stripos($uri, "marineregions.org") !== false) return $uri; //string is found
                }
                return $uris[0];
            }
        }
        else {
            /*
            [No URI for country] => Array(
                    [The Gambia] => 
                    [The Netherlands] => 
            */
            // /*
            switch ($waterbody) { //put here customized mapping
                case "Gulf Of St Lawrence":     return "http://www.marineregions.org/mrgid/4290";
                case "Rio de la Plata":         return "http://www.marineregions.org/mrgid/4325";
                case "Eastern China Sea":       return "http://www.marineregions.org/mrgid/4302";                

                /* copied template
                name: Bonaire, Saint Eustatius And Saba
                type: value
                uri: http://www.geonames.org/7626844                             
                */
            }
            // */
        }

        // /* next iteration e.g. "The Bahamas"
        if(substr($waterbody, 0, 4) == 'The ') {
            $waterbody = trim(substr($waterbody, 3, strlen($waterbody)));
            // echo "\n----------------------------try again ($waterbody)\n";
            if($uri = self::get_waterbody_uri($waterbody, 2)) return $uri;
        }
        // */


        // print_r($this->values_uri); //debug only
        echo ("\nNo URI for [$what] [$waterbody]"); //print_r($this->value_uris); print_r($this->value_uris[$waterbody]);  exit("\nstop munax\n");
        $this->debug['No URI for country'][$waterbody][$what] = '';
        return false;
    }
    private function assemble_terms_yml()
    {
        require_library('connectors/EOLterms_ymlAPI');
        $func = new EOLterms_ymlAPI(false, false);

        /* doesn't work well, it gets the http://marineregions.org/xxx
        $ret = $func->get_terms_yml('value'); //sought_type is 'value' --- REMINDER: labels can have the same value but different uri
        foreach($ret as $label => $uri) $this->uri_values[$label] = $uri;
        echo("\nEOL Terms: ".count($this->uri_values)."\n"); //debug only
        */

        // /* ideal for country nanes
        $this->value_uris = $func->get_terms_yml('ONE_TO_MANY'); // $ret[name][] = uri
        // */
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
    private function get_dwca_filename($str)
    {   
        /* good debug: if u want to start processing at this record
        if($str == 'Malacca Strait') $this->proceed = true;
        if(!$this->proceed) return true;
        */

        // /* manual adjustment
        if($str == "North Korea") $str = "North Korean";
        // */
        if(in_array($str, array('South America', 'North America'))) exit("\nNot intended to go here.\n"); //$q = '+title:"'.$str.'" +title:2019 +title:National +title:Checklists';
        else $q = '+title:"'.$str.'" +title:2019 +title:Water Body +title:Checklists'; //orig rest goes here

        if($obj = $this->zenodo->get_depositions_by_part_title($q)) { //print_r($obj[0]); 
            $f1 = $obj[0]['files'][0]['filename'];
            $path = $obj[0]['metadata']['related_identifiers'][0]['identifier'];
            $f2 = pathinfo($path, PATHINFO_BASENAME);
            
            // if(file_exists(CONTENT_RESOURCE_LOCAL_PATH.$f1)) echo "\nDwCA exists.\n";
            // else                                             exit("\nERROR: DwCA does not exist\n[$str]\n[$f1]\n[$f2]\n[$path]\n");

            if($f1 == $f2 && $f1) {
                $ext = pathinfo($f1, PATHINFO_EXTENSION);
                if($ext == 'gz') return str_ireplace(".tar.gz", "", $f1);
                else { //zip or whatever e.g. Adriatic Sea
                    // exit("\nInvestigate: [$str][$f1] wrong extension [$ext]\n");
                    $this->debug['wrong extension'][$str] = '';
                    // return true; //to be used with $this->proceed
                }
            }
            else {
                exit("\nERROR 1: Cannot find DwCA\n[$str]\n[$f1]\n[$f2]\n[$path]\n");
            }
        }
        else exit("\nERROR 2: Cannot find DwCA\n[$str]\n[$f1]\n[$f2]\n[$path]\n");
    }
    private function use_label_SampleSize_forCount($headers)
    {
        $final = array();
        foreach($headers as $h) {
            if(substr($h,0,5) == 'COUNT') $h = 'SampleSize';
            $final[] = $h;
        }
        return $final;
    }
}
?>