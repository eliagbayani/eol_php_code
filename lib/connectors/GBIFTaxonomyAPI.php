<?php
namespace php_active_record;
/* connector: [gbif_taxonomy.php]
This is about ways to access the GBIF taxonomy
Clients are:
    - WaterBodyChecklistsAPI.php
*/
class GBIFTaxonomyAPI
{
    function __construct($what = false) //$what can be: 
    {
        $this->download_options = array('resource_id' => "gbif_ctry_checklists", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000/2, 
        'timeout' => 10800*2, 'download_attempts' => 3, 'delay_in_minutes' => 5); //3 months to expire
        // https://docs.google.com/spreadsheets/d/1WB8nX4gaHv0naxg6tkXxMRGaLQIU4kYiuk57KXk9EAg/edit?gid=0#gid=0
        $this->service['species'] = "https://api.gbif.org/v1/species/"; //https://api.gbif.org/v1/species/1000148
        $this->fields = array('kingdomKey', 'phylumKey', 'classKey', 'orderKey', 'familyKey', 'genusKey', 'speciesKey');
        $this->GBIF_Filters_GoogleSheet_ID = '1WB8nX4gaHv0naxg6tkXxMRGaLQIU4kYiuk57KXk9EAg';
        if($what) self::GBIF_filters_from_GoogleSheet($what);
    }
    function is_id_valid_waterbody_taxon($id)
    {
        $info = self::get_taxon_info($id); //print_r($info); exit;
        // echo "\nIn question: [".$info['canonicalName']."] [$id]\n";
        if(self::has_anypart_of_ancestry_tobe_removed($info)) { //echo "\nPart of its ancestry is to be removed.\n";
            if(self::has_anypart_of_ancestry_tobe_retained($info)) {
                // echo "\nPart of its ancestry is to be retained.\n";
                return true;
            }
            else {
                // echo "\nNo part of its ancestry is to be retained.\n";
                return false;
            }
        }
        else {
            // echo "\nNo part of its ancestry is to be removed.\n";
            return true;
        }
    }
    private function has_anypart_of_ancestry_tobe_removed($info)
    {   /*Array(
            [key] => 11592253
            [scientificName] => Squamata
            [canonicalName] => Squamata
            [vernacularName] => squamates
            [nameType] => SCIENTIFIC
            [taxonomicStatus] => ACCEPTED
            [rank] => class
            [kingdomKey] => 1
            [phylumKey] => 44
            [classKey] => 11592253
            [orderKey] => 
            [familyKey] => 
            [genusKey] => 
            [speciesKey] => 
        )*/
        foreach($this->fields as $field) {
            $sought_id = $info[$field];
            if(isset($this->remove_retain_IDs['remove_ids'][$sought_id])) return true;
        }
        return false;
    }
    private function has_anypart_of_ancestry_tobe_retained($info)
    {
        foreach($this->fields as $field) {
            $sought_id = $info[$field];
            if(isset($this->remove_retain_IDs['retain_ids'][$sought_id])) return true;
        }
        return false;
    }
    function get_taxon_info($id)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //should not expire; false is the right value.
        if($json = Functions::lookup_with_cache($this->service['species'].$id, $options)) {
            $rek = json_decode($json, true); //print_r($rek); //exit;
            if(!@$rek['key']) return false;
            if($id != @$rek['key']) exit("\nIDs do not match. Should not go here. Investigate...\n");
            $info = array();
            $info['key'] = @$rek['key'];
            $info['scientificName'] = @$rek['scientificName'];
            $info['canonicalName'] = @$rek['canonicalName'];
            $info['vernacularName'] = @$rek['vernacularName'];
            $info['nameType'] = @$rek['nameType'];
            $info['taxonomicStatus'] = @$rek['taxonomicStatus'];
            $info['rank'] = strtolower(@$rek['rank']);
            $info['kingdomKey'] = @$rek['kingdomKey'];
            $info['phylumKey'] = @$rek['phylumKey'];
            $info['classKey'] = @$rek['classKey'];
            $info['orderKey'] = @$rek['orderKey'];
            $info['familyKey'] = @$rek['familyKey'];
            $info['genusKey'] = @$rek['genusKey'];
            $info['speciesKey'] = @$rek['speciesKey'];
            return $info;
        }
        exit("\nTaxon Key not found: [".$rec['specieskey']."]\n");
    }
    function GBIF_filters_from_GoogleSheet($what) //$what = 'Country_checklists' OR 'WaterBody_checklists' OR 'Continent_checklists'
    {
            if($what == 'WaterBody_checklists') self::waterbody_filters();
        elseif($what == 'Country_checklists')   self::country_filters();
        else exit("\nNo filters set. Will terminate.\n");
    }
    function country_filters()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = $this->GBIF_Filters_GoogleSheet_ID;
        $params['range']         = 'taxa from countries!A1:D10'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params, true); //2nd param false means it will NOT use cache but will get current data from spreadsheet //print_r($arr); exit;
        // print_r($arr); exit("\n-stop muna-\n");

        $i = 0;
        foreach($arr as $temp) { $i++;
            if($i == 1) {
                $fields = $temp;
                continue;
            }
            else {
                $rec = array();
                $k = 0;
                if(!$temp) continue;
                foreach($temp as $t) {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                // print_r($rec); exit("\n-stop muna-\n");
                /*Array(
                    [Country] => Canada
                    [uri] => http://www.geonames.org/6251999
                    [remove taxa] => Ambystoma mexicanum
                    [GBIF ID] => 2431950
                )*/
                $this->country_filters[] = $rec;
            }
        }
    }
    function waterbody_filters()
    {   
        // /* ----- working well but just static to I've hard-coded it below
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = $this->GBIF_Filters_GoogleSheet_ID;
        $params['range']         = 'taxa from water bodies!A2:D30'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params, true); //2nd param false means it will NOT use cache but will get current data from spreadsheet //print_r($arr); exit;
        foreach($arr as $rec) {
            if($val = @$rec[1]) $final['remove_ids'][$val] = '';
            if($val = @$rec[3]) $final['retain_ids'][$val] = '';
        }
        $this->remove_retain_IDs = $final; //this is the return value
        // ----- */

        // /* this is just checking if hard-coded value should be updated
        $hard_coded['remove_ids'] = Array(
            7707728 => '',
            1496 => '',
            216 => '',
            131 => '',
            212 => '',
            359 => '',
            11592253 => '',
            5 => ''
        );
        $hard_coded['retain_ids'] = Array(
            9640 => '',
            3725 => '',
            7680 => '',
            3086525 => '',
            4941589 => '',
            733 => '',
            2433669 => '', 
            2433737 => '',
            9251131 => '',
            2433451 => '',
            2459538 => ''
        );
        if($hard_coded == $final) echo "\nHard-coded value still good.\n";
        else                      exit("\nHard-coded value must be updated\n");
        // */
        // print_r($final); exit("\n-let us just have a look-see-\n"); //good debug
    }
    function load_taxon_keys_for_removal($taxon_keys)
    {
        $final = array();
        foreach($taxon_keys as $key) $final['remove_ids'][$key] = '';
        $this->remove_retain_IDs = $final;
    }
}
?>