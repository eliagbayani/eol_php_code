<?php
namespace php_active_record;
/* 1st client: CheckListBankRules.php
*/
class AnystyleAPI
{
    function __construct($folder = null, $query = null)
    {
        $this->download_options = array(
            'resource_id'        => 'anytyle',  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->anystyle_parse_prog = "ruby ".DOC_ROOT. "update_resources/connectors/helpers/anystyle/run.rb";
        $this->wikidata_api['search string'] = "https://www.wikidata.org/w/api.php?action=wbsearchentities&language=en&format=json&search=MY_TITLE";
        $this->wikidata_api['search entity ID'] = "https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=ENTITY_ID";
        $this->crossref_api['search citation'] = "http://api.crossref.org/works?query.bibliographic=MY_CITATION&rows=2";
        $this->sourcemd_api['search DOI'] = "https://sourcemd.toolforge.org/index_old.php?id=MY_DOI&doit=Check+source";
        $this->debug = array();
        // $this->tmp_batch_export = DOC_ROOT . "/tmp/temp_export.qs"; //moved        
        
        // ========================================================================= start Anystyle cli
        /* IMPORTANT: https://github.com/inukshuk/anystyle-cli#anystyle-help-train
        inside: update_resources/connectors/helpers/anystyle/

        anystyle --overwrite train core.xml eli_core.mod
        anystyle             train core.xml eli_core.mod      
        anystyle --parser-model eli_core.mod  --stdout -f json parse input.txt
        anystyle                              --stdout -f json parse input.txt
        */

        $this->anystyle_parse_model_core = DOC_ROOT. "update_resources/connectors/helpers/anystyle/eli_core.mod";
        $this->anystyle_parse_model_gold = DOC_ROOT. "update_resources/connectors/helpers/anystyle/eli_gold.mod";

        $this->temp_path = DOC_ROOT. "update_resources/connectors/helpers/anystyle/";
        $this->anystyle_path = "/usr/local/bin/anystyle";
    }

    function parse_citation_using_anystyle_cli($citation, $input_file)
    {
        $WRITE = Functions::file_open($input_file, "w");
        fwrite($WRITE, $citation);
        fclose($WRITE);

        $cmd = "$this->anystyle_path --parser-model $this->anystyle_parse_model_core  --stdout -f json parse $input_file";
        // $cmd = "$this->anystyle_path --parser-model $this->anystyle_parse_model_gold  --stdout -f json parse $input_file";
        // $cmd = "$this->anystyle_path                                             --stdout -f json parse $input_file";

        $json = shell_exec($cmd);
        // /* seems not needed here, only in Ruby below
        $json = substr(trim($json), 1, -1); # remove first and last char
        // $json = str_replace("\\", "", $json); # remove "\" from converted json from Ruby
        // */
        $obj = json_decode($json); //print_r($obj);
        return $obj;
    }

    function parse_citation_using_anystyle($citation, $what, $series = false) //$series is optional
    {
        // echo("\n----------\nthis runs ruby...[$what][$series]\n----------\n"); //comment in real operation
        $json = shell_exec($this->anystyle_parse_prog . ' "'.$citation.'"');
        $json = substr(trim($json), 1, -1); # remove first and last char
        $json = str_replace("\\", "", $json); # remove "\" from converted json from Ruby
        $obj = json_decode($json); //print_r($obj);
        if($what == 'all') return $obj;
        elseif($what == 'title') {
            if($val = @$obj[0]->title[0]) return $val;
            else {
                echo "\n---------- no title -------------\n";
                // print_r($obj); 
                echo "\ncitation:[$citation]\ntitle:[$what]\n";
                echo "\n---------- end -------------\n";
                return "-no title-";
            }
        }
        echo ("\n-end muna-\n");
    }
    /* working func but not used, since Crossref is not used, unreliable.
    private function crossref_citation($citation)
    {
        $url = str_replace("MY_CITATION", urlencode($citation), $this->crossref_api['search citation']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) { // print("\n$json\n");
            $obj = json_decode($json);
            return $obj;
        }
    } */
}
?>