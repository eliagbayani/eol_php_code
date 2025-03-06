<?php
namespace php_active_record;
/**/
class ZenodoFunctions
{
    function __construct($folder = null, $query = null)
    {}
    private function initialize()
    {
        // /*
        if(!isset($this->cache)) {
            echo "\ncache not set\n";
            require_library('connectors/CacheMngtAPI');
            $this->cache = new CacheMngtAPI($this->cache_path);    
        }
        else echo "\ncache is set already\n";
        // */        
    }
    function retrieve_save_stats($zenodo_id)
    {
        self::initialize();
        // $obj_1st = $this->retrieve_dataset($zenodo_id); print_r($obj_1st); exit("\nstop muna 1a\n");
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //designed to expire monthly 30 days
        // $options['expire_seconds'] = 0;
        if($json = Functions::lookup_with_cache($this->api['record'].$zenodo_id, $options)) {
            $o = json_decode($json, true); //print_r($o);
            self::save_stats($o);
        }
    }
    private function save_stats($o)
    {
        $conceptrecid = $o['conceptrecid'];
        $date = date('Y-m-d');
        $stats[$date] = $o['stats'];
        print_r($stats);

        $md5_id = md5($conceptrecid);
        if($rek = $this->cache->retrieve_json_obj($md5_id, false)) {echo "\nretrieved...\n";} //2nd param false means returned value is an array()
        else {
            $json = json_encode($stats);
            $this->cache->save_json($md5_id, $json);
            echo "\nsaved...\n";
            if($rek = $this->cache->retrieve_json_obj($md5_id, false)) {} //to check if file is really saved
            else exit("\nShould not go here [ZenodoFunctions].\n");
        }
        print_r($rek);
    }

}
?>