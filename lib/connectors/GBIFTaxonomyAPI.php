<?php
namespace php_active_record;
/* connector: [gbif_taxonomy.php]
This is about ways to access the GBIF taxonomy
Clients are:
- WaterBodyChecklistsAPI.php
- CountryChecklistsAPI.php
*/
class GBIFTaxonomyAPI
{
    function __construct($param)
    {
        $this->download_options = array('resource_id' => "gbif_ctry_checklists", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000/2, 
        'timeout' => 10800*2, 'download_attempts' => 3, 'delay_in_minutes' => 5); //3 months to expire
        // https://docs.google.com/spreadsheets/d/1WB8nX4gaHv0naxg6tkXxMRGaLQIU4kYiuk57KXk9EAg/edit?gid=0#gid=0
        $this->service['species'] = "https://api.gbif.org/v1/species/"; //https://api.gbif.org/v1/species/1000148

        $this->GBIF_Filters_GoogleSheet_ID = '1WB8nX4gaHv0naxg6tkXxMRGaLQIU4kYiuk57KXk9EAg';
    }
    function test()
    {   
        self::taxon_mapping_from_GoogleSheet();
    }
    function is_id_valid_waterbody_taxon($id)
    {
    }
    function assemble_species($rec)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //should not expire; false is the right value.
        if($json = Functions::lookup_with_cache($this->service['species'].$rec['specieskey'], $options)) {
            $rek = json_decode($json, true); //print_r($rek); exit;
            if(!@$rek['key']) return false;
            $save = array();
            $save['taxonID']                    = $rek['key']; //same as $rec['specieskey']
            $save['scientificName']             = $rek['scientificName'];
            $save['canonicalName']              = @$rek['canonicalName'];
            $save['scientificNameAuthorship']   = $rek['authorship'];
            $save['taxonRank']                  = strtolower($rek['rank']);
            $save['parentNameUsageID']          = @$rek['parentKey'];
            $save['taxonomicStatus']            = strtolower($rek['taxonomicStatus']);
            $save['furtherInformationURL']      = "https://www.gbif.org/species/".$rek['key'];
            return $save;
        }
        exit("\nSpecies Key not found: [".$rec['specieskey']."]\n");
    }
    private function taxon_mapping_from_GoogleSheet()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = $this->GBIF_Filters_GoogleSheet_ID;
        $params['range']         = 'taxa from water bodies!A2:D30'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params); //print_r($arr); exit;
        foreach($arr as $rec) {
            if($val = @$rec[1]) $final['remove_id'][$val] = '';
            if($val = @$rec[3]) $final['retain_id'][$val] = '';
        }
        $final['remove_id'] = array_keys($final['remove_id']);
        $final['retain_id'] = array_keys($final['retain_id']);
        print_r($final);
    }
}
?>