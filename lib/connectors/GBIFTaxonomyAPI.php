<?php
namespace php_active_record;
/* connector: [gbif_taxonomy.php]
This is about ways to access the GBIF taxonomy
Clients are:
    - WaterBodyChecklistsAPI.php
*/
class GBIFTaxonomyAPI
{
    function __construct()
    {
        $this->download_options = array('resource_id' => "gbif_ctry_checklists", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000/2, 
        'timeout' => 10800*2, 'download_attempts' => 3, 'delay_in_minutes' => 5); //3 months to expire
        // https://docs.google.com/spreadsheets/d/1WB8nX4gaHv0naxg6tkXxMRGaLQIU4kYiuk57KXk9EAg/edit?gid=0#gid=0
        $this->service['species'] = "https://api.gbif.org/v1/species/"; //https://api.gbif.org/v1/species/1000148
        $this->fields = array('kingdomKey', 'phylumKey', 'classKey', 'orderKey', 'familyKey', 'genusKey', 'speciesKey');
        $this->GBIF_Filters_GoogleSheet_ID = '1WB8nX4gaHv0naxg6tkXxMRGaLQIU4kYiuk57KXk9EAg';
        self::taxon_mapping_from_GoogleSheet();
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
    private function taxon_mapping_from_GoogleSheet()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = $this->GBIF_Filters_GoogleSheet_ID;
        $params['range']         = 'taxa from water bodies!A2:D30'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params); //print_r($arr); exit;
        foreach($arr as $rec) {
            if($val = @$rec[1]) $final['remove_ids'][$val] = '';
            if($val = @$rec[3]) $final['retain_ids'][$val] = '';
        }
        $this->remove_retain_IDs = $final;
    }
}
?>