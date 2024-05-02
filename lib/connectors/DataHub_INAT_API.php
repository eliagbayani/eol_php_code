<?php
namespace php_active_record;
/*
    datahub_inat.php or from: NCBIGGIqueryAPI.php
*/
class DataHub_INAT_API
{
    function __construct($folder = false)
    {
        $this->download_options_INAT = array('resource_id' => "723_inat", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1); //3 months to expire

        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }

        // - get all family and genus for iNat
        $this->inat_api['taxa'] = "https://api.inaturalist.org/v1/taxa?rank=XRANK&page=XPAGE&per_page=25";
        // https://api.inaturalist.org/v1/taxa?rank=family&page=1
        // https://api.inaturalist.org/v1/taxa?rank=genus&page=2&per_page=50

        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "iNat/";
        if(!is_dir($save_path)) mkdir($save_path);

        $this->dump_file = $save_path . "/datahub_inat.txt";
        if(is_file($this->dump_file)) unlink($this->dump_file);
        // ----------------------------------------------------------------- DwCA files from Ken-ichi: https://www.inaturalist.org/pages/developers
        $this->dwca['inaturalist-taxonomy'] = "https://www.inaturalist.org/taxa/inaturalist-taxonomy.dwca.zip";     //from Ken-ichi
        $this->dwca['gbif-observations'] = "/Volumes/Crucial_2TB/eol_php_code_tmp2/gbif-observations-dwca/observations.csv"; //"http://www.inaturalist.org/observations/gbif-observations-dwca.zip";    //from Ken-ichi, advised to read API Docs
        // $this->dwca['gbif-downloads'] = "/Volumes/Crucial_2TB/eol_php_code_tmp2/0007976-240425142415019.csv"; //https://doi.org/10.15468/dl.ky2k5v //not used

        $this->api['taxon_observation_count'] = "https://api.inaturalist.org/v2/observations?per_page=0&taxon_id="; //e.g. taxon_id=55533
        $this->TooManyRequests = 0;

        $this->reports_path = $save_path; //DOC_ROOT . "temp/GGI/reports/";
        $this->taxon_page = "https://www.inaturalist.org/taxa/"; //1240-Dendragapus or just 1240
        $this->inat['taxa_search'] = "https://api.inaturalist.org/v1/taxa?q="; //q=Gadidae
    }
    function explore_dwca()
    {
        // self::process_table(false, "explore", false, $this->dwca['gbif-downloads']);     //doesn't have the data we need
        //                         self::parse_tsv_file($this->dwca['gbif-downloads']);     //file is csv not tsv

        // /* --- main operation; works OK
        self::process_table(false, "explore gbif-observations", false, $this->dwca['gbif-observations'], false);
        self::write_tsv_file(); //generates 3 .tsv files: inat_species.tsv, inat_genus.tsv, inat_family.tsv
        self::create_dwca();
        // */

        $this->archive_builder->finalize(TRUE);
        print_r(@$this->debug['wala']); //taxa that were excluded since not found in inaturalist.org interface anyway.
    }
    private function create_dwca()
    {
        $files = array("genus" => "iNat_genus.tsv", "family" => "iNat_family.tsv", "ALL" => "iNat_species.tsv");
        // $files = array("genus" => "iNat_genus.tsv");
        // $files = array("family" => "iNat_family.tsv");
        // $files = array("genus" => "iNat_genus.tsv", "family" => "iNat_family.tsv");
        // $files = array("species" => "iNat_species.tsv");

        $this->taxa_info = array(); self::get_iNat_taxa_using_DwCA("ALL", true); //$rank can be 'genus' or 'family' or 'ALL'

        foreach($files as $rank => $file) {
            $this->rank_level = $rank;
            self::parse_tsv_file($this->reports_path . $file, $file);
        }
    }
    private function write_tsv_file()
    {
        $path = $this->reports_path;
        echo "\npath: [$path]\n";
        if(!is_dir($path)) mkdir($path);
        $filename = $path."iNaturalist_8.tsv";

        if(!($WRITE = Functions::file_open($filename, "w"))) return;

        fwrite($WRITE, "=====Institution=====" . "\n");
        $tmp = $this->debug['institutionCode'];
        foreach($tmp as $institution => $total) fwrite($WRITE, $institution . "\t" . "$total" . "\n");

        fwrite($WRITE, "\n");
        fwrite($WRITE, "=====Collection=====" . "\n");
        $tmp = $this->debug['collectionCode'];
        foreach($tmp as $collection => $total) fwrite($WRITE, $collection . "\t" . "$total" . "\n");

        fwrite($WRITE, "\n");
        fwrite($WRITE, "=====Dataset=====" . "\n");
        $tmp = $this->debug['datasetName'];
        foreach($tmp as $dataset => $total) fwrite($WRITE, $dataset . "\t" . "$total" . "\n");

        fwrite($WRITE, "\n");
        fwrite($WRITE, "=====Genus=====" . "\n");
        if(!($WRITE_2 = Functions::file_open($path."iNat_genus.tsv", "w"))) return;
        $headers = array("sciname", "count", "scientificName", "rank", "kingdom", "phylum", "class", "order", "family", "genus", "taxonID");
        fwrite($WRITE_2, implode("\t", $headers) . "\n");
        $tmp = $this->debug['genus'];
        foreach($tmp as $genus_name => $total) {
            $info = @$this->debug['genus_lookup'][$genus_name];
            if(!$info) $info = array();
            fwrite($WRITE, "genus\t" . $genus_name . "\t" . "$total" . "\t" . implode("\t", @$info) . "\n"); //orig
            fwrite($WRITE_2, $genus_name . "\t" . "$total" . "\t" . implode("\t", @$info) . "\n");
        }
        fclose($WRITE_2);

        fwrite($WRITE, "\n");
        fwrite($WRITE, "=====Family=====" . "\n");
        if(!($WRITE_2 = Functions::file_open($path."iNat_family.tsv", "w"))) return;
        $headers = array("sciname", "count", "rank");
        fwrite($WRITE_2, implode("\t", $headers) . "\n");
        $tmp = $this->debug['family'];
        foreach($tmp as $family_name => $total) {
            $info = @$this->debug['taxa'][$taxonID];
            fwrite($WRITE, "family\t" . $family_name . "\t" . "$total" . "\n");
            fwrite($WRITE_2, $family_name . "\t" . "$total" . "\t" . "family" . "\n");
        }
        fclose($WRITE_2);

        fwrite($WRITE, "\n");
        fwrite($WRITE, "=====Taxa ALL=====" . "\n");
        if(!($WRITE_2 = Functions::file_open($path."iNat_species.tsv", "w"))) return;
        $headers = array("taxonID", "count", "sciname", "rank", "kingdom", "phylum", "class", "order", "family", "genus");
        fwrite($WRITE_2, implode("\t", $headers) . "\n");
        $tmp = $this->debug['taxonID'];
        foreach($tmp as $taxonID => $total) {
            $info = $this->debug['taxa'][$taxonID];
            /* "Too Many Requests" error
            $rank = $info['r'];
            if($rank == 'genus') {
                $api_count = self::get_total_observations($taxonID);
                $info['api_count'] = $api_count;
            }*/
            /*
            @this->debug['taxa'][$taxon_id] = array('sn' => $rec['scientificName'], 'r' => $rec['taxonRank'], 
            'k' => $rec['kingdom'], 'p' => $rec['phylum'], 'c' => $rec['class'], 'o' => $rec['order'], 'f' => $rec['family'], 'g' => $rec['genus']);
            */
            fwrite($WRITE, "taxonID\t" . $taxonID . "\t" . "$total" . "\t" . implode("\t", $info) . "\n");
            fwrite($WRITE_2, $taxonID . "\t" . "$total" . "\t" . implode("\t", $info) . "\n");
        }
        fclose($WRITE_2);

        fwrite($WRITE, "\n");
        fwrite($WRITE, "=====Taxa per Collection=====" . "\n");
        $tmp = $this->debug['x']['collectionCode'];
        foreach($tmp as $collection => $arr_totals) {
            foreach($arr_totals as $taxonID => $total)
            fwrite($WRITE, $collection . "\t" . $taxonID . "\t" . "$total" . "\n");
        }

        fwrite($WRITE, "\n");
        fwrite($WRITE, "=====Taxa per Dataset=====" . "\n");
        $tmp = $this->debug['x']['datasetName'];
        foreach($tmp as $dataset => $arr_totals) {
            foreach($arr_totals as $taxonID => $total)
            fwrite($WRITE, $dataset . "\t" . $taxonID . "\t" . "$total" . "\n");
        }

        fclose($WRITE);
    }
    private function parse_tsv_file($file, $what)
    {   echo "\nReading file $what...\n";
        $i = 0; $final = array();
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");
                /*Array(
                    [sciname] => Ophion
                    [count] => 987
                    [scientificName] => Ophion
                    [rank] => genus
                    [kingdom] => Animalia
                    [phylum] => Arthropoda
                    [class] => Insecta
                    [order] => Hymenoptera
                    [family] => Ichneumonidae
                    [genus] => Ophion
                    [taxonID] => 47993
                )
                Array(
                    [sciname] => Ichneumonidae
                    [count] => 28837
                    [rank] => family
                )*/
                // if(!$rec['taxonID']) {
                //     print_r($rec); exit("\nelix 200\n");
                // }

                if($taxonID = self::write_taxon($rec)) {
                    if(!@$rec['taxonID']) $rec['taxonID'] = $taxonID;
                    $rec['source'] = $this->taxon_page . $rec['taxonID'];
                    self::write_MoF($taxonID, $rec);    
                }
            }
        }//end foreach()
    }
    private function get_taxon_meta_via_api($sciname, $rank, $rec)
    {
        $options = $this->download_options_INAT;
        $options['expire_seconds'] = false;
        $options['resource_id'] = 723;
        if($json = Functions::lookup_with_cache($this->inat['taxa_search'] . $sciname, $options)) {
            $obj = json_decode($json); //echo "<pre>";print_r($json); echo "</pre>"; exit;
            foreach($obj->results as $r) {

                if($rank == 'ALL') $condition = $r->name == $sciname;
                else               $condition = $r->name == $sciname && strtolower($r->rank) == strtolower($rank);

                if($condition) {
                    $rec['taxonID'] = $r->id;
                    $rec['sciname'] = $r->name;
                    $rec['rank'] = $r->rank;
                    $rec['observations_count'] = $r->observations_count;
                    return $rec;
                }
            }
        }
        $this->debug['wala']['wala talaga']["[$sciname] [$rank]"] = '';
        // echo "\ngoes here...[$sciname] [$] [$rank]\n"; print_r($rec); exit("\n");
        /*Array(
            [wala talaga] => Array(
                    [[Pontania] [genus]] => 
                    [[Gonostomidae] [family]] => 
                )
        )*/
        return false;

        $rec['taxonID'] = $sciname;
        $rec['sciname'] = $sciname;
        $rec['rank'] = $this->rank_level;
        return $rec;
    }
    private function write_taxon($rec)
    {
        if(!@$rec['taxonID']) {
            if($rek = @$this->taxa_info[$rec['sciname']]) { //echo "\ndwca lookup\n";
                $rec['taxonID'] = $rek['i'];
                $rec['sciname'] = $rek['s'];
                $rec['rank'] = $rek['r'];
                $rec['kingdom'] = $rek['k'];
                $rec['phylum'] = $rek['p'];
                $rec['class'] = $rek['c'];
                $rec['order'] = $rek['o'];
                $rec['family'] = $rek['f'];
                $rec['genus'] = $rek['r'];
            }
            else { //echo "\napi lookup\n";
                if($rec = self::get_taxon_meta_via_api($rec['sciname'], $this->rank_level, $rec)) {}
                else {
                    // print_r($rec);
                    // exit("\nCannot locate...\n");
                    return false;
                }
            }
        }
        if($rec['rank'] == 'family') {
            $rec['family'] = '';
            $rec['genus'] = '';
        }
        elseif($rec['rank'] == 'genus') {
            $rec['genus'] = '';
        }


        $taxonID = $rec['taxonID'];
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $taxonID;
        $taxon->scientificName  = $rec['sciname'];
        $taxon->taxonRank       = $rec['rank'];
        $taxon->kingdom         = @$rec['kingdom'];
        $taxon->phylum          = @$rec['phylum'];
        $taxon->class           = @$rec['class'];
        $taxon->order           = @$rec['order'];

        if($taxon->taxonRank == 'family') {
            $taxon->family = $rec['family'];
        }
        elseif($taxon->taxonRank == 'genus') {
            $taxon->family = $rec['family'];
            $taxon->genus  = $rec['genus'];
        }

        if(!isset($this->taxonIDs[$taxonID])) {
            $this->taxonIDs[$taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
            return $taxon->taxonID;    
        }
        else return false;

    }
    private function write_MoF($taxon_id, $rec)   //, $label, $value, $measurementType, $family)
    {
        $object_id = $taxon_id."_observations";
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $object_id);
        $m->occurrenceID        = $occurrence_id;
        $m->measurementOfTaxon  = 'true';
        $m->source              = $rec["source"];
        $m->measurementType = "http://eol.org/schema/terms/NumberOfiNaturalistObservations";
        $m->measurementValue = $rec['count'];
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }
    private function add_occurrence($taxon_id, $object_id)
    {
        $occurrence_id = $taxon_id . 'O' . $object_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    function get_iNat_taxa_using_DwCA($rank, $memoryYN = false)
    {        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $options = $this->download_options_INAT;
        $options['expire_seconds'] = 60*60*24*30*3; //3 months cache
        $paths = $func->extract_archive_file($this->dwca['inaturalist-taxonomy'], "meta.xml", $options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit; //debug only
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_30465/',
            'temp_dir'     => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_30465/'
        );
        */

        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        // $index = array_keys($tables); print_r($index); exit;

        self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon', $rank, false, $memoryYN);

        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    }
    private function process_table($meta, $what, $sought_rank = false, $local_dwca = false, $memoryYN)
    {
        if($meta)           $csv_file = $meta->file_uri;
        elseif($local_dwca) $csv_file = $local_dwca;

        $i = 0; $meron = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file); //print_r($row);

            if(!$row) continue; 
            $str = implode("\t", $row);
            // if(stripos($str, "Callisaurus	genus") !== false) {  //string found --- good debug
            //     echo("\n$str\n");
            // }

            if(!$row) break;
            $i++; if(($i % 100000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $count = count($fields);
                // print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                // print_r($rec); //exit;

                // if(stripos($str, "Callisaurus	genus") !== false) {  //string found --- good debug
                //     print_r($rec); //exit;
                // }
    
                /*Array(
                    [id] => 1
                    [taxonID] => https://www.inaturalist.org/taxa/1
                    [identifier] => https://www.inaturalist.org/taxa/1
                    [parentNameUsageID] => https://www.inaturalist.org/taxa/48460
                    [kingdom] => Animalia
                    [phylum] => 
                    [class] => 
                    [order] => 
                    [family] => 
                    [genus] => 
                    [specificEpithet] => 
                    [infraspecificEpithet] => 
                    [modified] => 2021-11-02T06:05:44Z
                    [scientificName] => Animalia
                    [taxonRank] => kingdom
                    [references] => http://www.catalogueoflife.org/annual-checklist/2013/browse/tree/id/13021388
                )*/
                // =======================================================================================
                if($what == 'taxon' && $memoryYN == false) {
                    if($sought_rank == $rec['taxonRank']) {
                        $rek = array();
                        $rek["id"]                  = $rec['id'];
                        $rek["rank"]                = $rec['taxonRank'];
                        $rek["sciname"]             = $rec['scientificName'];
                        $rek["parent_id"]           = pathinfo($rec['parentNameUsageID'], PATHINFO_FILENAME);
                        /* not scalable - Too Many Requests error
                        $rek["meta_observ_count"]   = self::get_total_observations($rec['id']);
                        if($rek["meta_observ_count"] === false) {
                            break;
                        }*/
                        self::save_to_dump($rek, $this->dump_file);
                        $meron++;
                        // if($meron >= 3) break; //dev only
                    }    
                }
                if($what == 'taxon' && $memoryYN) {
                    // if(stripos($str, "Callisaurus	genus") !== false) {  //string found --- good debug
                        // print_r($rec); exit("\nditox 100\n");
                    // }
                    $this->taxa_info[$rec['scientificName']] = array('i' => $rec['id'], 's' => $rec['scientificName'], 'r' => $rec['taxonRank'], 'k' => $rec['kingdom'], 'p' => $rec['phylum'], 'c' => $rec['class'], 'o' => $rec['order'], 'f' => $rec['family']);
                    $meron++;
                }

                // =======================================================================================
                if($what == "explore gbif-observations") { //print_r($rec);
                    /* Array(
                        [id] => 26124613
                        [occurrenceID] => https://www.inaturalist.org/observations/26124613
                        [basisOfRecord] => HumanObservation
                        [modified] => 2019-05-31T22:15:43Z
                        [institutionCode] => iNaturalist
                        [collectionCode] => Observations
                        [datasetName] => iNaturalist research-grade observations
                        [informationWithheld] => 
                        [catalogNumber] => 26124613
                        [references] => https://www.inaturalist.org/observations/26124613
                        [occurrenceRemarks] => 
                        [recordedBy] => Nick Tepper
                        [recordedByID] => 
                        [identifiedBy] => Nick Tepper
                        [identifiedByID] => 
                        [captive] => wild
                        [eventDate] => 2019-05-31T08:50:08-04:00
                        [eventTime] => 08:50:08-04:00
                        [verbatimEventDate] => Fri May 31 2019 08:50:08 GMT-0400 (EDT)
                        [verbatimLocality] => 01775, Stow, MA, US
                        [decimalLatitude] => 42.4175533333
                        [decimalLongitude] => -71.553025
                        [coordinateUncertaintyInMeters] => 12
                        [geodeticDatum] => EPSG:4326
                        [countryCode] => US
                        [stateProvince] => Massachusetts
                        [identificationID] => 57162675
                        [dateIdentified] => 2019-05-31T17:04:55Z
                        [identificationRemarks] => 
                        [taxonID] => 47699
                        [scientificName] => Geranium maculatum
                        [taxonRank] => species
                        [kingdom] => Plantae
                        [phylum] => Tracheophyta
                        [class] => Magnoliopsida
                        [order] => Geraniales
                        [family] => Geraniaceae
                        [genus] => Geranium
                        [license] => http://creativecommons.org/licenses/by-nc/4.0/
                        [rightsHolder] => Nick Tepper
                        [inaturalistLogin] => ntepper
                        [publishingCountry] => 
                        [sex] => 
                        [lifeStage] => 
                        [reproductiveCondition] => flowering
                    ) */
                    $taxonID = $rec['taxonID'];
                    @$this->debug['taxonID'][$taxonID]++;
                    @$this->debug['institutionCode'][$rec['institutionCode']]++;
                    @$this->debug['collectionCode'][$rec['collectionCode']]++;
                    @$this->debug['datasetName'][$rec['datasetName']]++;

                    @$this->debug["x"]['collectionCode'][$rec['collectionCode']][$taxonID]++;
                    @$this->debug["x"]['datasetName'][$rec['datasetName']][$taxonID]++;

                    $info = array('sn' => $rec['scientificName'], 'r' => $rec['taxonRank'], 'k' => $rec['kingdom'], 'p' => $rec['phylum'], 'c' => $rec['class'], 'o' => $rec['order'], 'f' => $rec['family'], 'g' => $rec['genus']);
                    @$this->debug['taxa'][$taxonID] = $info;

                    if($genus  = @$rec['genus'])  @$this->debug['genus'][$genus]++;
                    if($family = @$rec['family']) @$this->debug['family'][$family]++;

                    $taxonRank = $rec['taxonRank'];
                    $scientificName = $rec['scientificName'];

                    if($taxonRank == 'genus') {
                        $info['i'] = $rec['taxonID'];
                        $this->debug['genus_lookup'][$scientificName] = $info;
                    }

                    /* stats only - exploring contents of DwCA
                    // if($taxonRank == 'genus') {print_r($rec); exit;} //meron
                    // if($taxonRank == 'family') {print_r($rec); exit;} // wala
                    */
                }
                // =======================================================================================
                // =======================================================================================
                // =======================================================================================
                // =======================================================================================
            } //main records
            // if($i >= 200000) break; //debug only
        } //main loop
        fclose($file);
        // exit("\nelix 1\n");
    } //end process_table()
    function get_total_observations($taxon_id)
    {
        // @$this->run_query++;
        // if($this->run_query >= 5) {
        //     $this->run_query = 0;
        //     sleep(60*3); echo "\nsleep muna...";
        // }

        $options = $this->download_options_INAT;
        $options['download_wait_time'] = 6000000; //6 secs interval
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($this->api['taxon_observation_count'] . $taxon_id, $options)) {
            // echo "\n[$json]\n";
            // /* iNat special case
            if(stripos($json, '429') !== false) { //Too Many Requests           --- //string is found
                echo "\n[$json]\n";
                echo "\niNat special error: Too Many Requests\n"; exit("\nexit muna, remove iNat from the list of dbases.\n");
                sleep(60*10); //10 mins
                $this->TooManyRequests++;
                if($this->TooManyRequests >= 5) return false;
            }
            // */
            $obj = json_decode($json); //print_r($obj); exit;
            /*stdClass Object(
                [total_results] => 17113
                [page] => 1
                [per_page] => 0
                [results] => Array()
            )*/
            return @$obj->total_results;
        }
    }
    function get_iNat_taxa_using_API($rank) //not advisable to use, bec. of the 10,000 limit page coverage
    {
        $this->inat_api['taxa'] = str_replace("XRANK", $rank, $this->inat_api['taxa']);
        $page = 1;
        $url = str_replace("XPAGE", $page, $this->inat_api['taxa']);

        $json = Functions::lookup_with_cache($url, $this->download_options_INAT);
        $obj = json_decode($json); // print_r($obj); //exit;
        $total = $obj->total_results;
        $pages = ceil($total / 25); // exit("\n$total\n$pages\n");

        for($page = 1; $page <= $pages; $page++) {
            $url = str_replace("XPAGE", $page, $this->inat_api['taxa']);
            if($json = Functions::lookup_with_cache($url, $this->download_options_INAT)) {
                $obj = json_decode($json);
                /*[0] => stdClass Object(
                        [id] => 47851
                        [rank] => genus
                        [rank_level] => 20
                        [ancestor_ids] => Array(
                                [0] => 48460
                                [1] => 47126
                                [2] => 211194
                                [3] => 47125
                                [4] => 47124
                                [5] => 47853
                                [6] => 47852
                                [7] => 47851)
                        [is_active] => 1
                        [name] => Quercus
                        [parent_id] => 47852
                        [extinct] => 
                        [observations_count] => 927598
                        [complete_species_count] => 
                        [wikipedia_url] => http://en.wikipedia.org/wiki/Oak
                        [iconic_taxon_name] => Plantae
                        [preferred_common_name] => oaks
                )*/
                foreach($obj->results as $o) {
                    if($o->is_active == 1) {
                        $rek = array();
                        $rek["id"]                  = $o->id;
                        $rek["rank"]                = $o->rank;
                        $rek["sciname"]             = $o->name;
                        $rek["parent_id"]           = $o->parent_id;
                        $rek["meta_observ_count"]   = $o->observations_count;
                        self::save_to_dump($rek, $this->dump_file);    
                    }    
                }
            }
            // if($page >= 6) break; //dev only
        } //end for loop
    }
    private function save_to_dump($rec, $filename)
    {
        if(isset($rec["meta_observ_count"]) && is_array($rec)) {
            $fields = array_keys($rec);
            $data = "";

            if(!is_file($filename)) {
                foreach($fields as $field) $data .= $field . "\t";
                if(!($WRITE = Functions::file_open($filename, "a"))) return;
                fwrite($WRITE, $data . "\n");
                fclose($WRITE);    
            }
            $data = "";
            foreach($fields as $field) $data .= $rec[$field] . "\t";
            if(!($WRITE = Functions::file_open($filename, "a"))) return;
            fwrite($WRITE, $data . "\n");
            fclose($WRITE);    

        }
        // copied template
        // else {
        //     if(!($WRITE = Functions::file_open($filename, "a"))) return;
        //     if($rec && is_array($rec)) fwrite($WRITE, json_encode($rec) . "\n");
        //     else                       fwrite($WRITE, $rec . "\n");
        //     fclose($WRITE);
        // }
    }
}
?>