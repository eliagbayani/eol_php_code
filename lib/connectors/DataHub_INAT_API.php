<?php
namespace php_active_record;
/*
    datahub_inat.php or from: NCBIGGIqueryAPI.php
*/
class DataHub_INAT_API
{
    function __construct($archive_builder = false, $resource_id = false)
    {
        $this->download_options_INAT = array('resource_id' => "723_inat", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1); //3 months to expire
        // - get all family and genus for iNat
        $this->inat_api['taxa'] = "https://api.inaturalist.org/v1/taxa?rank=XRANK&page=XPAGE&per_page=100";
        // https://api.inaturalist.org/v1/taxa?rank=family&page=1
        // https://api.inaturalist.org/v1/taxa?rank=genus&page=2&per_page=50

        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "iNat";
        if(!is_dir($save_path)) mkdir($save_path);

        $this->dump_file = $save_path . "/datahub_inat.txt";
        if(is_file($this->dump_file)) unlink($this->dump_file);
    }
    function get_iNat_taxa($rank)
    {
        $this->inat_api['taxa'] = str_replace("XRANK", $rank, $this->inat_api['taxa']);
        $page = 1;
        $url = str_replace("XPAGE", $page, $this->inat_api['taxa']);

        $json = Functions::lookup_with_cache($url, $this->download_options_INAT);
        $obj = json_decode($json); // print_r($obj); //exit;
        $total = $obj->total_results;
        $pages = ceil($total / 100); // exit("\n$total\n$pages\n");

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
                    )
                */
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