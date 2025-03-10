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
    function show_dataset_stats($zenodo_id)
    {
        self::initialize();
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //designed to expire monthly 30 days
        // $options['expire_seconds'] = 0; //dev only
        $options['expire_seconds'] = 60*60*24; //to expire in 1 day
        if($json = Functions::lookup_with_cache($this->api['record'].$zenodo_id, $options)) {
            $o = json_decode($json, true); //print_r($o);
            $rek = self::retrieve($o);
            ksort($rek); print_r($rek);
        }
    }
    function process_stats($zenodo_id)
    {
        self::initialize();
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
    function generate_tsv_report()
    {   
        self::initialize();
        self::init_stats_file(14927926); //this is: Wikipedia: Wikipedia English - traits (inferred records) | https://zenodo.org/records/14927926
        // self::init_stats_file(13322937);
        // /*
        $objs = true;
        $q = "+keywords:active"; //n=
        if($objs = $this->get_depositions_by_part_title($q)) { //print_r($objs[0]); //exit;
            $i = 0; $total = count($objs); echo "\nTotal recs to process: [$total]\n"; //exit("\nStop muna\n");
            foreach($objs as $o) { $i++;
                echo "\n-----$i of $total. [".$o['id']."] ".$o['metadata']['title']."\n";
                if($zenodo_id = $o['id']) {
                    $rek = $this->prepare_to_write($zenodo_id);
                    $title = $o['metadata']['title'];
                    self::write_tsv($rek, $title);
                }
                // break; //debug only, run 1 only
                // if($i >= 5) break; //debug only
            }
        } //end if($objs)    
        // */
        /* dev only
        $zenodo_id = 14927926; //13321100
        $zenodo_id = 14437247;
        $this->process_stats($zenodo_id);
        // $this->process_stats($zenodo_id);
        */
        exit("\n-end generate_tsv_report-\n");
    }
    private function prepare_to_write($zenodo_id)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($this->api['record'].$zenodo_id, $options)) {
            $o = json_decode($json, true); //print_r($o); exit;
            $rek = self::retrieve($o);     
            print_r($rek); //exit("\nelix 1\n");
            return $rek;
        }
        exit("\nelix 2\n");
    }
    private function init_stats_file($zenodo_id)
    {
        $date = date("Y-m-d h:i:s A");
        $rek = $this->prepare_to_write($zenodo_id);
        $this->header_dates = array_keys($rek); sort($this->header_dates);
        $final_dates = array();
        foreach($this->header_dates as $d) {
            // /* better interface
            $final_dates[] = $d;
            $final_dates[] = "";
            $second_row[] = 'Views';
            $second_row[] = 'Downloads';
            // */
            /* 2nd option: interface
            $final_dates[] = "$d V";
            $final_dates[] = "$d D";
            */
        }
        $final_second_row = array("");
        $final_second_row = array_merge($final_second_row, $second_row);
        $headers = array("Dataset [$date]");
        $headers = array_merge($headers, $final_dates);
        if(!($file = Functions::file_open($this->stats_file, "w"))) return;
        fwrite($file, implode("\t", $headers)."\n");
        fwrite($file, implode("\t", $final_second_row)."\n");
        fclose($file);
    }
    private function write_tsv($rek, $title)
    {
        $cols = array();
        foreach($this->header_dates as $date) {
            if($rec = @$rek[$date]) $a = array($rec['unique_views'], $rec['unique_downloads']);
            else $a = array("","");
            $cols = array_merge($cols, $a);
        }
        $finals = array($title);
        $finals = array_merge($finals, $cols);
        if(!($file = Functions::file_open($this->stats_file, "a"))) return;
        fwrite($file, implode("\t", $finals)."\n");
        fclose($file);
    }
    function get_all_versions($zenodo_id, $IDs_only_YN = true)
    {
        // $this->api['versions'] => "https://zenodo.org/api/records/ZENODO_ID/versions?page=PAGE_NUM&size=25&sort=version";
        $final = array();
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24; //1 day expires
        $page_num = 0;
        while(true) { $page_num++;
            $url = str_replace("ZENODO_ID", $zenodo_id, $this->api['versions']);
            $url = str_replace("PAGE_NUM", $page_num, $url);
            if($json = Functions::lookup_with_cache($url, $options)) {
                $o = json_decode($json, true); //print_r($o); exit("\nstop 1\n");
                if($o['hits']['hits']) {
                    foreach($o['hits']['hits'] as $r) {
                        if($IDs_only_YN) $final[] = $r['id'];
                        else             $final[] = $r;
                    }    
                }
                else return $final;
            }
            else return $final;
        }
    }
}
?>