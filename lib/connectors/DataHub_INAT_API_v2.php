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

        $this->inat_api['taxa'] = "https://api.inaturalist.org/v1/observations/species_counts?taxon_is_active=true&hrank=species&lrank=species&iconic_taxa=XGROUP&quality_grade=XGRADE&page=XPAGE"; //defaults to per_page = 500
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "iNat/";
        if(!is_dir($save_path)) mkdir($save_path);

        $this->dump_file['research'] = $save_path . "/datahub_inat_grade_research.txt";
        if(is_file($this->dump_file['research'])) unlink($this->dump_file['research']);

        $this->dump_file['needs_id'] = $save_path . "/datahub_inat_grade_needs_id.txt";
        if(is_file($this->dump_file['needs_id'])) unlink($this->dump_file['needs_id']);

        $this->reports_path = $save_path;
        $this->taxon_page = "https://www.inaturalist.org/taxa/"; //1240-Dendragapus or just 1240
    }
    function start()
    {
        $quality_grades = array('research', 'needs_id');
        // $quality_grades = array('needs_id');

        $groups = array("Insecta", "Plantae", "Actinopterygii", "Amphibia", "Arachnida", "Aves", "Chromista", "Fungi", "Mammalia", "Mollusca", "Reptilia", "Protozoa", "unknown"); //orig
        // $groups = array("Actinopterygii", "Amphibia", "Arachnida", "Aves", "Chromista", "Fungi", "Mammalia", "Mollusca", "Reptilia", "Protozoa", "unknown");

        foreach($quality_grades as $grade) {
            foreach($groups as $group) {
                echo "\nProcessing [$group]...[$grade]...\n";
                self::get_iNat_taxa_using_API($group, $grade);
                echo "\nEvery group, sleep 1 min.\n";
                // sleep(60*1); //10 mins interval per group
            }    
        }
    }
    function get_iNat_taxa_using_API($group, $grade) //not advisable to use, bec. of the 10,000 limit page coverage
    {
        $main_url = str_replace("XGROUP", $group, $this->inat_api['taxa']);
        $main_url = str_replace("XGRADE", $grade, $main_url);

        $page = 1;
        $url = str_replace("XPAGE", $page, $main_url);

        $json = Functions::lookup_with_cache($url, $this->download_options_INAT);
        $obj = json_decode($json); // print_r($obj); //exit;
        $total = $obj->total_results;
        $pages = ceil($total / 500);  echo "\ntotal_results: [$total]\ntotal pages: [$pages]\n";

        for($page = 1; $page <= $pages; $page++) {

            if(($page % 50) == 0) {
                echo "\nEvery 50 calls, sleep 10 mins.\n";
                // sleep(60*1); //10 mins interval
            }

            $url = str_replace("XPAGE", $page, $main_url);
            if($json = Functions::lookup_with_cache($url, $this->download_options_INAT)) {

                /* iNat special case - not reliable needs more test
                if(stripos($json, 'error') !== false) { //Too Many Requests           --- //string is found
                    echo "\n[$json]\n";
                    echo "\niNat special error: Too Many Requests\n"; exit("\nexit muna, remove iNat from the list of dbases.\n");
                    sleep(60*10); //10 mins
                    @$this->TooManyRequests++;
                    if($this->TooManyRequests >= 3) exit("\nToo Many Requests error (429)!\n");
                }
                */


                $obj = json_decode($json); //print_r($obj); exit;
                /**/
                foreach($obj->results as $r) {
                    $t = $r->taxon;
                    $rek = array();
                    $rek["id"]                  = $t->id;
                    $rek["rank"]                = $t->rank;
                    $rek["name"]                = $t->name;
                    $rek["observations_count"]  = $t->observations_count;
                    $rek["iconic_taxon_name"]   = @$t->iconic_taxon_name;
                    $rek["parent_id"]           = $t->parent_id;
                    $rek["ancestry"]            = $t->ancestry;
                    self::save_to_dump($rek, $this->dump_file[$grade]);    
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