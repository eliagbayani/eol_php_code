<?php
namespace php_active_record;
/*
    datahub_inat.php or from: NCBIGGIqueryAPI.php
*/
class DataHub_INAT_API
{
    function __construct($archive_builder = false, $resource_id = false)
    {
        $this->download_options_INAT = array('resource_id' => "723_inat", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 3000000, 'timeout' => 10800, 'download_attempts' => 1); //3 months to expire
        // - get all family and genus for iNat
        $this->inat_api['taxa'] = "https://api.inaturalist.org/v1/taxa?rank=XRANK&page=XPAGE&per_page=25";
        // https://api.inaturalist.org/v1/taxa?rank=family&page=1
        // https://api.inaturalist.org/v1/taxa?rank=genus&page=2&per_page=50

        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "iNat";
        if(!is_dir($save_path)) mkdir($save_path);

        $this->dump_file = $save_path . "/datahub_inat.txt";
        if(is_file($this->dump_file)) unlink($this->dump_file);
        // ----------------------------------------------------------------- DwCA files from Ken-ichi: https://www.inaturalist.org/pages/developers
        $this->dwca_file = "https://www.inaturalist.org/taxa/inaturalist-taxonomy.dwca.zip";
                        //    "http://www.inaturalist.org/observations/gbif-observations-dwca.zip";
        $this->api['taxon_observation_count'] = "https://api.inaturalist.org/v2/observations?per_page=0&taxon_id="; //e.g. taxon_id=55533
        $this->TooManyRequests = 0;
    }
    function get_iNat_taxa_using_DwCA($rank)
    {        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", $this->download_options_INAT); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit; //debug only
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_08324/',
            'temp_dir'     => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_08324/'
        );
        */

        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        // $index = array_keys($tables); print_r($index); exit;

        self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon', $rank);

        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    }
    private function process_table($meta, $what, $sought_rank)
    {
        $csv_file = $meta->file_uri;
        $i = 0; $meron = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file); //print_r($row);
            if(!$row) break;
            $i++; if(($i % 50000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $count = count($fields);
                print_r($fields);
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
                // print_r($fields); print_r($rec); exit;
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
                if($sought_rank == $rec['taxonRank']) {
                    $rek = array();
                    $rek["id"]                  = $rec['id'];
                    $rek["rank"]                = $rec['taxonRank'];
                    $rek["sciname"]             = $rec['scientificName'];
                    $rek["parent_id"]           = pathinfo($rec['parentNameUsageID'], PATHINFO_FILENAME);
                    $rek["meta_observ_count"]   = self::get_total_observations($rec['id']);
                    if($rek["meta_observ_count"] === false) {
                        break;
                    }
                    self::save_to_dump($rek, $this->dump_file);
                    $meron++;
                    // if($meron >= 3) break; //dev only
                }
            } //main records
        } //main loop
        fclose($file);
    }
    function get_total_observations($taxon_id)
    {
        if($json = Functions::lookup_with_cache($this->api['taxon_observation_count'] . $taxon_id, $this->download_options_INAT)) {
            // echo "\n[$json]\n";
            // /* iNat special case
            if(stripos($json, '429') !== false) { //Too Many Requests           --- //string is found
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