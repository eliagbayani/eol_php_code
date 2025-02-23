<?php
namespace php_active_record;
/* connector: [gbif_map_data.php] */
class GBIFMapDataAPI
{
    public function __construct($what) //typically param $folder is passed here.
    {
        $this->download_options = array('resource_id' => "gbif_ctry_checklists", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000/2, 'timeout' => 10800*2, 'download_attempts' => 3, 'delay_in_minutes' => 5); //3 months to expire
        $this->download_options['expire_seconds'] = false; //doesn't expire
        $this->debug = array();
        $this->bibliographicCitation = "GBIF.org (23 January 2025) GBIF Occurrence Download https://doi.org/10.15468/dl.3vk32d";
        if(Functions::is_production())  $this->destination = "/extra/other_files/GBIF_occurrence/".$what."/";
        else                            $this->destination = "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/".$what."/";
        if(!is_dir($this->destination)) mkdir($this->destination);

        $this->service['species'] = "https://api.gbif.org/v1/species/"; //https://api.gbif.org/v1/species/1000148
        $this->service['children'] = "https://api.gbif.org/v1/species/TAXON_KEY/childrenAll"; //https://api.gbif.org/v1/species/44/childrenAll
        $this->service['occurrence_count'] = "https://api.gbif.org/v1/occurrence/count?taxonKey="; //https://api.gbif.org/v1/occurrence/count?taxonKey=44            
    }
    function start($fields) //start($counter = false, $task, $sought_waterbdy = false) //$counter is only for caching
    {   
    }

    function prepare_taxa($key) //seems to be run just for Chordata
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
}
?>