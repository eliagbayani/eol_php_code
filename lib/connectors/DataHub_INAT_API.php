<?php
namespace php_active_record;
/*
*/
class DataHub_INAT_API
{
    function __construct($archive_builder = false, $resource_id = false)
    {
        $this->download_options_INAT = array('resource_id' => "723_inat", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); //3 months to expire
        // - get all family and genus for iNat
        $this->inat_api['taxa'] = "https://api.inaturalist.org/v1/taxa?rank=XRANK&page=XPAGE&per_page=10";
        // https://api.inaturalist.org/v1/taxa?rank=family&page=1
        // https://api.inaturalist.org/v1/taxa?rank=genus&page=2&per_page=50
    }
    function get_iNat_taxa($rank)
    {
        $page = 1;
        $url = str_replace("XPAGE", $page, $this->inat_api['taxa']);
        $url = str_replace("XRANK", $rank, $url);
        if($json = Functions::lookup_with_cache($url, $this->download_options_INAT)) {
            $obj = json_decode($json);
            print_r($obj); exit;
        }
        
    }
    /*
    function get_terms_yml($sought_type = 'ALL') //possible values: 'measurement', 'value', 'ALL', 'WoRMS value'
    {                                            //output structure: $final[label] = URI;
        $final = array();
        if($yml = Functions::lookup_with_cache($this->EOL_terms_yml_url, $this->download_options)) { //orig 1 day cache
            $yml .= "alias: ";
            if(preg_match_all("/name\:(.*?)alias\:/ims", $yml, $a)) {
                $arr = array_map('trim', $a[1]);
            }
            else exit("\nInvestigate: EOL terms file structure had changed.\n");
        }
        else exit("Remote EOL terms (.yml) file not accessible.");
        print_r($this->debug); //just for stats
        return $final;
    } //end get_terms_yml()
    */
}
?>