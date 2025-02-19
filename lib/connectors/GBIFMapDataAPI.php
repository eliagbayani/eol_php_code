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
    }
    function start($fields) //start($counter = false, $task, $sought_waterbdy = false) //$counter is only for caching
    {   
    }
}
?>