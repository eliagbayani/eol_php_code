<?php
namespace php_active_record;
/*  datahub_inat_v2.php
*/
class DataHub_INAT_API_v2
{
    function __construct($folder = false)
    {
        $this->download_options_INAT = array('resource_id' => "723_inat", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); //3 months to expire

        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }

        $this->inat_api['taxa'] = "https://api.inaturalist.org/v1/observations/species_counts?taxon_is_active=true&hrank=species&lrank=species&iconic_taxa=XGROUP&quality_grade=research&page=XPAGE"; //defaults to per_page = 500
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "iNat/";
        if(!is_dir($save_path)) mkdir($save_path);

        $this->dump_file = $save_path . "/datahub_inat_v2.txt";
        if(is_file($this->dump_file)) unlink($this->dump_file);

        $this->reports_path = $save_path;
        $this->taxon_page = "https://www.inaturalist.org/taxa/"; //1240-Dendragapus or just 1240
    }
    function start()
    {
        $groups = array("Insecta", "Plantae", "Actinopterygii", "Amphibia", "Arachnida", "Aves", "Chromista", "Fungi", "Mammalia", "Mollusca", "Reptilia", "Protozoa", "unknown");
        $groups = array("Insecta");
        foreach($groups as $group) {
            self::get_iNat_taxa_using_API($group);
        }
    }
    function get_iNat_taxa_using_API($group) //not advisable to use, bec. of the 10,000 limit page coverage
    {
        $main_url = str_replace("XGROUP", $group, $this->inat_api['taxa']);
        $page = 1;
        $url = str_replace("XPAGE", $page, $main_url);

        $json = Functions::lookup_with_cache($url, $this->download_options_INAT);
        $obj = json_decode($json); // print_r($obj); //exit;
        $total = $obj->total_results;
        $pages = ceil($total / 500);  echo "\ntotal_results: [$total]\ntotal pages: [$pages]\n";

        for($page = 1; $page <= $pages; $page++) {
            $url = str_replace("XPAGE", $page, $main_url);
            if($json = Functions::lookup_with_cache($url, $this->download_options_INAT)) {
                $obj = json_decode($json); //print_r($obj); exit;
                /**/
                foreach($obj->results as $r) {
                    $t = $r->taxon;
                    $rek = array();
                    $rek["id"]                  = $t->id;
                    $rek["rank"]                = $t->rank;
                    $rek["sciname"]             = $t->name;
                    $rek["parent_id"]           = $t->parent_id;
                    $rek["observations_count"]   = $t->observations_count;
                    self::save_to_dump($rek, $this->dump_file);    
                }
            }
            // if($page >= 6) break; //dev only
        } //end for loop
    }
    private function save_to_dump($rec, $filename)
    {
        if(isset($rec["observations_count"]) && is_array($rec)) {
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
    }
}
?>