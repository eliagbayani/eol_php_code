<?php
namespace php_active_record;
/**/
class ZenodoFunctions
{
    function __construct($folder = null, $query = null)
    {
        // /*
        require_library('connectors/CacheMngtAPI');
        $this->func = new CacheMngtAPI($this->cache_path);
        // */        
    }
    function retrieve_save_stats($zenodo_id)
    {
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
        $stats = $o['stats']; print_r($stats);
    }

}
?>