<?php
namespace php_active_record;
/* connector 2025: [gbif_map_data.php] 
https://editors.eol.org/map_data2/1/4501.json
https://editors.eol.org/map_data2/final_taxon_concept_IDS.txt
*/
class GBIFMapDataAPI
{
    public function __construct($what) //eg. map_Gadiformes
    {
        $this->download_options = array('resource_id' => "gbif_ctry_checklists", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000/2, 'timeout' => 10800*2, 'download_attempts' => 3, 'delay_in_minutes' => 5); //3 months to expire
        $this->download_options['expire_seconds'] = false; //doesn't expire
        $this->debug = array();
        // $this->bibliographicCitation = "GBIF.org (23 January 2025) GBIF Occurrence Download https://doi.org/10.15468/dl.3vk32d";

        if($this->taxonGroup = $what) {
            if(Functions::is_production())  $this->work_dir = "/extra/other_files/GBIF_occurrence/".$what."/";
            else                            $this->work_dir = "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/".$what."/";    
        }
        
        $this->service['species'] = "https://api.gbif.org/v1/species/"; //https://api.gbif.org/v1/species/1000148
        $this->service['children'] = "https://api.gbif.org/v1/species/TAXON_KEY/childrenAll"; //https://api.gbif.org/v1/species/44/childrenAll
        $this->service['occurrence_count'] = "https://api.gbif.org/v1/occurrence/count?taxonKey="; //https://api.gbif.org/v1/occurrence/count?taxonKey=44            

        if(Functions::is_production()) {
            $this->save_path['taxa_csv_path']     = "/extra/other_files/GBIF_occurrence/GBIF_taxa_csv_dwca/";
            $this->save_path['multimedia_gbifID'] = "/extra/other_files/GBIF_occurrence/multimedia_gbifID/";
            $this->save_path['map_data']          = "/extra/map_data_dwca/";
            // $this->eol_taxon_concept_names_tab    = "/extra/eol_php_code_public_tmp/google_maps/taxon_concept_names.tab"; obsolete
            // $this->eol_taxon_concept_names_tab    = "/extra/other_files/DWH/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt"; //working but old DH ver.
            $this->eol_taxon_concept_names_tab    = "/extra/other_files/DWH/TRAM-809/DH_v1_1/taxon.tab";    //latest active DH ver.
            // to be updated to: https://editors.eol.org/uploaded_resources/1c3/b5f/dhv21.zip
    
            
            // $this->occurrence_txt_path['Animalia']     = "/extra/other_files/GBIF_occurrence/DwCA_Animalia/occurrence.txt";
            // $this->occurrence_txt_path['Plantae']      = "/extra/other_files/GBIF_occurrence/DwCA_Plantae/occurrence.txt";
            // $this->occurrence_txt_path['Other7Groups'] = "/extra/other_files/GBIF_occurrence/DwCA_Other7Groups/occurrence.txt";
        }
        else {
            $this->save_path['taxa_csv_path']     = "/Volumes/Crucial_4TB/google_maps/GBIF_taxa_csv_dwca/";
            $this->save_path['multimedia_gbifID'] = "/Volumes/Crucial_4TB/google_maps/multimedia_gbifID/";
            $this->save_path['map_data']          = "/Volumes/Crucial_4TB/google_maps/map_data_dwca/";
            // $this->eol_taxon_concept_names_tab    = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/JRice_tc_ids/taxon_concept_names.tab"; obsolete
            // $this->eol_taxon_concept_names_tab    = "/Volumes/AKiTiO4/other_files/from_OpenData/EOL_dynamic_hierarchyV1Revised/taxa.txt"; //working but old DH ver.
            $this->eol_taxon_concept_names_tab = "/Volumes/AKiTiO4/d_w_h/EOL Dynamic Hierarchy Active Version/DH_v1_1/taxon.tab"; //used for the longest time
            $this->eol_taxon_concept_names_tab = "/Volumes/AKiTiO4/d_w_h/history/dhv21/taxon.tab";

            // $this->occurrence_txt_path['Gadus morhua'] = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/occurrence_downloads/DwCA/Gadus morhua/occurrence.txt";
            // $this->occurrence_txt_path['Lates niloticus'] = "/Volumes/AKiTiO4/eol_pub_tmp/google_maps/occurrence_downloads/DwCA/Lates niloticus/occurrence.txt";
        }

        $this->csv_paths = array();
        $this->csv_paths[] = $this->save_path['taxa_csv_path'];
        
        $folders = array($this->save_path['taxa_csv_path'], $this->save_path['multimedia_gbifID'], $this->save_path['map_data']);
        foreach($folders as $folder) {
            if(!is_dir($folder)) mkdir($folder);
        }
        $this->listOf_taxa['all']    = CONTENT_RESOURCE_LOCAL_PATH . '/listOf_all_4maps.txt';



    }
    private function initialize()
    {
        require_library('connectors/GBIFoccurrenceAPI_DwCA');
        $this->func = new GBIFoccurrenceAPI_DwCA();
    }
    function start($params)
    {   // print_r($params);
        self::initialize();
        $source = $this->work_dir . $this->taxonGroup ."_DwCA.zip";
        $tsv_path = self::download_extract_gbif_zip_file($source, $this->work_dir); echo "\n$this->taxonGroup: $tsv_path\n";
        // self::process_big_csv_file($tsv_path, "");
    }
    function breakdown_GBIF_DwCA_file($taxonGroup)
    {   //IMPORTANT: run only once every harvest
        self::initialize();
        $source = $this->work_dir . $this->taxonGroup ."_DwCA.zip";
        $tsv_path = self::download_extract_gbif_zip_file($source, $this->work_dir); echo "\nRun once: $this->taxonGroup: $tsv_path\n";

        $path2 = $this->save_path['taxa_csv_path'];
        $paths[] = $tsv_path;
        /* copied template
        if(Functions::is_production()) {
            if($group) $paths[] = $this->occurrence_txt_path[$group];
            else { //this means a long run, several days. Not distributed.
                $paths[] = $this->occurrence_txt_path['Animalia'];        //~717 million - Took 3 days 15 hr (when API calls are not yet cached)
                $paths[] = $this->occurrence_txt_path['Plantae'];         //~183 million - Took 1 day 19 hr (when API calls are not yet cached)
                $paths[] = $this->occurrence_txt_path['Other7Groups'];    //~25 million - Took 5 hr 10 min (when API calls are not yet cached)
            }
        }
        else $paths[] = $this->occurrence_txt_path[$group];
        */
        foreach($paths as $path) { $i = 0;
            foreach(new FileIterator($path) as $line_number => $row) { $i++; // 'true' will auto delete temp_filepath
                if($i == 1) { $fields = explode("\t", $row); continue; }
                else {
                    if(!$row) continue;
                    $tmp = explode("\t", $row);
                    $rec = array(); $k = 0;
                    foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                    $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");
                }
                /*Array(
                    [catalognumber] => 
                    [scientificname] => Ciliata mustela (Linnaeus, 1758)
                    [publishingorgkey] => 1928bdf0-f5d2-11dc-8c12-b8a03c50a862
                    [institutioncode] => 
                    [datasetkey] => baa3340c-1c8b-46bf-9fc9-50554cb1cd01
                    [gbifid] => 4576498311
                    [decimallatitude] => 46.15224
                    [decimallongitude] => -1.35597
                    [recordedby] => JULUX (INDÉPENDANT)
                    [identifiedby] => 
                    [eventdate] => 2021-09-22
                    [kingdomkey] => 1
                    [phylumkey] => 44
                    [classkey] => 
                    [orderkey] => 549
                    [familykey] => 9639
                    [genuskey] => 9577782
                    [subgenuskey] => 
                    [specieskey] => 2415526
                )*/
                if(($i % 500000) == 0) echo "\n".number_format($i) . "[$path]\n";
                $taxonkey = $rec['specieskey'];
                // /* ----- can be postponed since not all records will eventually be used
                // $rec['publishingorgkey'] = $this->func->get_dataset_field($rec['datasetkey'], 'publishingOrganizationKey'); //orig but can be postponed
                $rec['publishingorgkey'] = 'nyc'; //not yet computed by Eli
                // ----- */
                $rek = array($rec['gbifid'], $rec['datasetkey'], $rec['scientificname'], $rec['publishingorgkey'], $rec['decimallatitude'], $rec['decimallongitude'], $rec['eventdate'], 
                $rec['institutioncode'], $rec['catalognumber'], $rec['identifiedby'], $rec['recordedby']);
                if($rec['decimallatitude'] && $rec['decimallongitude']) {
                    $path3 = $this->func->get_md5_path($path2, $taxonkey);
                    $csv_file = $path3 . $taxonkey . ".csv";
                    if(!file_exists($csv_file)) {
                        //order of fields here is IMPORTANT: will use it when accessing these generated individual taxon csv files
                        $str = 'gbifid,datasetkey,scientificname,publishingorgkey,decimallatitude,decimallongitude,eventdate,institutioncode,catalognumber,identifiedby,recordedby';
                        $fhandle = Functions::file_open($csv_file, "w");
                        fwrite($fhandle, implode("\t", explode(",", $str)) . "\n");
                        fclose($fhandle);
                    }
                    $fhandle = Functions::file_open($csv_file, "a");
                    fwrite($fhandle, implode("\t", $rek) . "\n");
                    fclose($fhandle);
                }
                // break; //debug only
            } //end foreach()
        } //end loop paths
    }
    function generate_map_data_using_GBIF_csv_files($sciname = false, $tc_id = false, $range_from = false, $range_to = false, $autoRefreshYN = false)
    {
        self::initialize();
        $paths = $this->csv_paths;
        // $eol_taxon_id_list["Gadus morhua"] = 206692;
        // $eol_taxon_id_list["Achillea millefolium L."] = 45850244;
        // $eol_taxon_id_list["Francolinus levaillantoides"] = 1; //5227890
        // $eol_taxon_id_list["Phylloscopus trochilus"] = 2; //2493052
        // $eol_taxon_id_list["Anthriscus sylvestris (L.) Hoffm."] = 584996; //from Plantae group
        // $eol_taxon_id_list["Xenidae"] = 8965;
        // $eol_taxon_id_list["Soleidae"] = 5169;
        // $eol_taxon_id_list["Plantae"] = 281;
        // $eol_taxon_id_list["Chaetoceros"] = 12010;
        // $eol_taxon_id_list["Chenonetta"] = 104248;

        /* for testing 1 taxon
        $eol_taxon_id_list = array();
        $eol_taxon_id_list["Gadus morhua"] = 206692;
        $eol_taxon_id_list["Gadidae"] = 5503;
        $eol_taxon_id_list["Gadiformes"] = 1180;
        // $eol_taxon_id_list["Decapoda"] = 1183;
        // $eol_taxon_id_list["Proterebia keymaea"] = 137680; //csv map data not available from DwCA download
        // $eol_taxon_id_list["Aichi virus"] = 540501;
        */

        // $sciname = 'Gadella imberbis';  $tc_id = '46564969';
        // $sciname = 'Gadiformes';        $tc_id = '5496';
        // $sciname = 'Gadus morhua';      $tc_id = '46564415';
        // $sciname = "Gadus chalcogrammus"; $tc_id = 216657;
        // $sciname = "Gadus macrocephalus"; $tc_id = 46564417;
        // $sciname = 'Stichastrella rosea'; $tc_id = '598446';
        

        if($sciname && $tc_id) {
            $eol_taxon_id_list[$sciname] = $tc_id; //print_r($eol_taxon_id_list);
            $this->func->create_map_data($sciname, $tc_id, $paths); //result of refactoring
            return;
        }

        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //1 month expires
        $local = Functions::save_remote_file_to_local($this->listOf_taxa['all'], $options);
        $i = 0;
        foreach(new FileIterator($local) as $line_number => $line) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // /* dev only
            if(substr($rec['canonicalName'],0,1) != "G") continue;
            // if(substr($rec['canonicalName'],0,1) == "G") continue;
            // */

            print_r($rec); //exit("\nstopx\n");
            /*Array(
                [canonicalName] => Oscillatoriales
                [EOLid] => 3255
                [taxonRank] => order
                [taxonomicStatus] => accepted
            )*/
            //  new ranges ---------------------------------------------
            if($range_from && $range_to) {
                $cont = false;
                if($i >= $range_from && $i < $range_to) $cont = true;
                if(!$cont) continue;
            }
            //  --------------------------------------------------------
            echo "\n$i of $range_to. [".$rec['canonicalName']."][".$rec['EOLid']."]";
            $this->func->create_map_data($rec['canonicalName'], $rec['EOLid'], $paths); //result of refactoring
            // break; //debug only
        }
        unlink($local);
    }
    function gen_map_data_forTaxa_with_children($p) //($sciname = false, $tc_id = false, $range_from = false, $range_to = false, $filter_rank = '')
    {
        self::initialize();
        $this->use_API_YN = false; //no more API calls at this point.
        require_library('connectors/DHConnLib'); $func = new DHConnLib('');
        $paths = $this->csv_paths; 
        
        $sciname = "Gadus";     $tc_id = "46564414";
        // $sciname = "Gadidae";   $tc_id = "5503";

        if($sciname && $tc_id) {
            $eol_taxon_id_list[$sciname] = $tc_id; print_r($eol_taxon_id_list); 
            $this->func->create_map_data_include_descendants($sciname, $tc_id, $paths, $func); //result of refactoring
            return;
        }
        
        /* used FileIterator below instead, to save on memory
        $i = 0;
        foreach($eol_taxon_id_list as $sciname => $taxon_concept_id) {
            $i++;
            //  new ranges ---------------------------------------------
            if($range_from && $range_to) {
                $cont = false;
                if($i >= $range_from && $i < $range_to) $cont = true;
                if(!$cont) continue;
            }
            //  --------------------------------------------------------
            echo "\n$i. [$sciname][$taxon_concept_id]";
            self::create_map_data_include_descendants($sciname, $taxon_concept_id, $paths, $func); //result of refactoring
        } //end main foreach()
        */
        
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //1 month expires
        $local = Functions::save_remote_file_to_local($this->listOf_taxa[$p['filter_rank']], $options);
        $i = 0; $found = 0;
        foreach(new FileIterator($local) as $line_number => $line) {
            $i++; if(($i % 500000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [canonicalName] => Oscillatoriales
                [EOLid] => 3255
                [taxonRank] => order
                [taxonomicStatus] => accepted
            )*/
            
            //  new ranges ---------------------------------------------
            if($range_from && $range_to) {
                $cont = false;
                if($i >= $range_from && $i < $range_to) $cont = true;
                if(!$cont) continue;
            }
            //  --------------------------------------------------------
            echo "\n$i of $range_to. [".$rec['canonicalName']."][".$rec['EOLid']."]";
            self::create_map_data_include_descendants($rec['canonicalName'], $rec['EOLid'], $paths, $func); //result of refactoring
            
        }
        unlink($local);
    }

    /*
    private function process_big_csv_file($file, $task)
    {   echo "\nTask: [$task] [$file]\n";
        $i = 0; $final = array();
        if($task == "divide_into_country_files") $mod = 100000;
        elseif($task == "process_country_file")  $mod = 10000;
        else                                     $mod = 10000;
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % $mod) == 0) echo "\n $i ";
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); print_r($rec); //exit("\nstop muna\n");
                Array(
                    [catalognumber] => 
                    [scientificname] => Ciliata mustela (Linnaeus, 1758)
                    [publishingorgkey] => 1928bdf0-f5d2-11dc-8c12-b8a03c50a862
                    [institutioncode] => 
                    [datasetkey] => baa3340c-1c8b-46bf-9fc9-50554cb1cd01
                    [gbifid] => 4576498311
                    [decimallatitude] => 46.15224
                    [decimallongitude] => -1.35597
                    [recordedby] => JULUX (INDÉPENDANT)
                    [identifiedby] => 
                    [eventdate] => 2021-09-22
                    [kingdomkey] => 1
                    [phylumkey] => 44
                    [classkey] => 
                    [orderkey] => 549
                    [familykey] => 9639
                    [genuskey] => 9577782
                    [subgenuskey] => 
                    [specieskey] => 2415526
                )
                self::save_to_json($rec);
                // break;
            }
        }
    } */
    private function write_taxon_csv()
    {
        // gbifid	datasetkey	scientificname	publishingorgkey	decimallatitude	decimallongitude	eventdate	institutioncode	catalognumber	identifiedby	recordedby
    }
    /* not used atm
    private function save_to_json($rek)
    {   
        $rec = array();
        $rec['a']   = $rek['catalognumber'];
        $rec['b']   = $rek['scientificname'];
        $rec['c']   = $this->func->get_org_name('publisher', @$rek['publishingorgkey']);
        $rec['d']   = @$rek['publishingorgkey'];
        if($val = @$rek['institutioncode']) $rec['c'] .= " ($val)";
        $rec['e']   = $this->func->get_dataset_field(@$rek['datasetkey'], 'title'); //self::get_org_name('dataset', @$rek['datasetkey']);
        $rec['f']   = @$rek['datasetkey'];
        $rec['g']   = $rek['gbifid'];
        $rec['h']   = $rek['decimallatitude'];
        $rec['i']   = $rek['decimallongitude'];
        $rec['j']   = @$rek['recordedby'];
        $rec['k']   = @$rek['identifiedby'];
        $rec['l']   = $this->func->get_media_by_gbifid($rek['gbifid']);
        $rec['m']   = @$rek['eventdate'];
        print_r($rec); exit("\nstop 1\n");
    } */
    function prepare_taxa($key) //a utility
    {
        $final['occurrences'] = 0; $batch_sum = 0;
        $sum = 0; $batch_total = 250000000; //250 million
        $taxon_key_batches = array(); $current_keys = array();
        $options = $this->download_options;
        $options['expire_seconds'] = false; //should not expire; false is the right value.
        $url = str_replace("TAXON_KEY", $key, $this->service['children']);
        if($json = Functions::lookup_with_cache($url, $options)) {
            $reks = json_decode($json, true); //print_r($reks);
            $i = -1;
            foreach($reks as $rek) { $i++;
                /*Array(
                    [key] => 131
                    [name] => Amphibia
                    [rank] => CLASS
                    [size] => 16476
                )*/
                $taxon_key = $rek['key'];
                $taxon_rank = $rek['rank'];
                $count = Functions::lookup_with_cache($this->service['occurrence_count'].$rek['key'], $options);
                if($count <= 0) continue;
                $final['occurrences'] = $final['occurrences'] + $count;
                $batch_sum += $count;
                $current_keys[] = array('key' => $taxon_key, 'rank' => $taxon_rank);
                if($batch_sum > $batch_total) {
                    $taxon_key_batches[] = array('batch_sum' => $batch_sum, 'current_keys' => $current_keys);
                    $batch_sum = 0;
                    $current_keys = array();
                }
                // print_r($rek); //exit;
                // break; //debug only get only 1 rec
            }
            // last batch
            $taxon_key_batches[] = array('batch_sum' => $batch_sum, 'current_keys' => $current_keys);
            print_r($final);
            echo "\n". number_format($final['occurrences']) ."\n";
            print_r($taxon_key_batches);
        }
        // $url = "https://www.gbif.org/species/44";
        // if($html = Functions::lookup_with_cache($url, $options)) {
        //     echo "\n$html\n";
        // }
    }
    private function download_extract_gbif_zip_file($source, $destination)
    {
        echo "\ndownload_extract_gbif_zip_file...\n";
        // /* main operation - works OK
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $ret = $func->download_extract_zip_file($source, $destination); // echo "\n[$ret]\n";
        if(preg_match("/inflating:(.*?)elix/ims", $ret.'elix', $arr)) {
            $csv_path = trim($arr[1]); echo "\n[$csv_path]\n";
            return $csv_path;
        }
        return false;
        // */
        /* during dev only
        return "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/map_Gadiformes/0000896-250225085111116.csv";
        */
    }
}
?>