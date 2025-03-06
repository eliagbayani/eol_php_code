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
    function process_stats($zenodo_id)
    {
        self::initialize();
        // $obj_1st = $this->retrieve_dataset($zenodo_id); print_r($obj_1st); exit("\nstop muna 1a\n");
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //designed to expire monthly 30 days
        // $options['expire_seconds'] = 0;
        $options['expire_seconds'] = 60*60*24; //to expire in 1 day
        if($json = Functions::lookup_with_cache($this->api['record'].$zenodo_id, $options)) {
            $o = json_decode($json, true); //print_r($o);
            $rek = self::retrieve($o);     //print_r($rek);
            $rek = self::append_stats_today($rek, $o);
            ksort($rek);
            print_r($rek);
        }
    }
    private function retrieve($o)
    {
        $conceptrecid = $o['conceptrecid'];
        $md5_id = md5($conceptrecid);
        if($rek = $this->cache->retrieve_json_obj($md5_id, false)) {echo "\nretrieved...\n";} //2nd param false means returned value is an array()
        else {
            $date = date('Y-m-d');
            $o['stats']['conceptrecid'] = $conceptrecid;
            $stats[$date] = $o['stats']; // print_r($stats);
            $json = json_encode($stats);
            $this->cache->save_json($md5_id, $json);
            echo "\nsaved...\n";
            if($rek = $this->cache->retrieve_json_obj($md5_id, false)) {} //to check if file is really saved
            else exit("\nShould not go here [ZenodoFunctions].\n");
        }
        return $rek;
    }
    private function append_stats_today($rek, $o)
    {
        $date = date('Y-m-d');
        if(!isset($rek[$date])) {
            // step 1
            $conceptrecid = $o['conceptrecid'];
            $md5_id = md5($conceptrecid);
            $date = date('Y-m-d');
            $o['stats']['conceptrecid'] = $conceptrecid;
            // step 2
            $rek[$date] = $o['stats'];
            $json = json_encode($rek);
            $this->cache->save_json($md5_id, $json);
            echo "\nappend stats today...\n";
        }
        else echo "\nstats today already exists\n";
        return $rek;
    }

}
?>