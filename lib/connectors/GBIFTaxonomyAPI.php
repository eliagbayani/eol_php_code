<?php
namespace php_active_record;
/* connector: [gbif_taxonomy.php]
This is about ways to access the GBIF taxonomy
Clients are:
    - WaterBodyChecklistsAPI.php
*/
/* Workspaces for GBIF map tasks:
- GBIFMapDataAPI
- GBIF_map_harvest
- GBIF_SQL_DownloadsAPI
- GBIFTaxonomy */
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
        // /* Long list taxa VS continent checklists
        $this->waterbody_taxa = "/Volumes/Crucial_2TB/resources_3/waterbody_compiled/taxon.tab";
        $this->country_taxa = "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Country_checklists/countries_unique_taxa v1.tsv";
        // */
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
        elseif($what == 'GBIF_checklists')   self::dataset_filters();        
        else exit("\nNo filters set. Will terminate.\n");
    }
    private function dataset_filters()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = $this->GBIF_Filters_GoogleSheet_ID;
        $params['range']         = 'datasets!A1:A20'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
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
                    [Dataset ID] => ebd01d3e-5e9a-4e80-8ae2-1dfe9a032bf7
                )*/
                $final[$rec['Dataset ID']] = '';
            }
        }
        $this->dataset_filters = array_keys($final);
    }
    private function country_filters()
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
    private function waterbody_filters()
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
    function long_list_vs_continent_checklists()
    {
        // /* generates $this->country_waterbody_taxa
        self::process_tsv($this->waterbody_taxa, 'save');
        self::process_tsv($this->country_taxa, 'save');
        asort($this->country_waterbody_taxa);
        // print_r($this->country_waterbody_taxa);
        // */

        // /* saving to tsv file
        if(Functions::is_production())  $destination = "/extra/other_files/GBIF_occurrence/";
        else                            $destination = "/Volumes/Crucial_4TB/other_files/GBIF_occurrence/";
        $destination = $destination . "long_list_country_waterbody_taxa.tsv";
        self::write_to_text($this->country_waterbody_taxa, $destination, array('taxonID', 'scientificName'));
        // */
        
        echo "\ncountry_waterbody_taxa total: ".count($this->country_waterbody_taxa)."\n";
        $paths['africa']        = '/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Continent_checklists/DwCA_continents/SC_africa/taxon.tab';
        $paths['antarctica']    = '/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Continent_checklists/DwCA_continents/SC_antarctica/taxon.tab';
        $paths['asia']          = '/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Continent_checklists/DwCA_continents/SC_asia/taxon.tab';
        $paths['europe']        = '/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Continent_checklists/DwCA_continents/SC_europe/taxon.tab';
        $paths['northamerica']  = '/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Continent_checklists/DwCA_continents/SC_northamerica/taxon.tab';
        $paths['oceania']       = '/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Continent_checklists/DwCA_continents/SC_oceania/taxon.tab';
        $paths['southamerica']  = '/Volumes/Crucial_4TB/other_files/GBIF_occurrence/Continent_checklists/DwCA_continents/SC_southamerica/taxon.tab';
        $continents = array('africa', 'antarctica', 'asia', 'europe', 'northamerica', 'oceania', 'southamerica');
        foreach($continents as $continent) {
            echo "\n[$continent]: Original taxa count: " . Functions::show_totals($paths[$continent]);
            $ret = self::process_tsv($paths[$continent], 'count');
            echo "\nTaxa not in any country or waterbody checklist: ".count($ret)."\n";
        }
    }
    private function process_tsv($file, $task)
    {   $i = 0;
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            // if(($i % 5000) == 0) echo "\n $i ";
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstop muna\n");
                if($task == 'save') {
                    $this->country_waterbody_taxa[$rec['taxonID']] = $rec['canonicalName'] ? $rec['canonicalName'] : $rec['scientificName'];
                }
                elseif($task == 'count') {
                    $taxonID = $rec['taxonID'];
                    if(!isset($this->country_waterbody_taxa[$taxonID])) $final[$taxonID] = '';
                }
            }
        } //end foreach()
        if($task == 'count') return $final;
    }
    private function write_to_text($arr_with_key_value, $destination, $headers = false)
    {
        $f = Functions::file_open($destination, "w");
        if($headers) fwrite($f, implode("\t", $headers)."\n");
        foreach($arr_with_key_value as $taxonID => $taxonName) fwrite($f, implode("\t", array($taxonID, $taxonName))."\n");
        fclose($f);
    }
}
?>