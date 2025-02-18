<?php
namespace php_active_record;
/* */
class Functions_Pensoft
{
    function __construct() {}
    function initialize_new_patterns()
    {   // exit("\n[$this->new_patterns_4textmined_resources]\nelix1\n");
        // $str = file_get_contents($this->new_patterns_4textmined_resources); //too long
        $str = Functions::lookup_with_cache($this->new_patterns_4textmined_resources, $this->download_options);
        $arr = explode("\n", $str);
        $arr = array_map('trim', $arr);
        // $arr = array_filter($arr); //remove null arrays
        // $arr = array_unique($arr); //make unique
        // $arr = array_values($arr); //reindex key
        // print_r($arr); //exit("\n".count($arr)."\n");
        $i = 0;
        foreach($arr as $row) { $i++;
            $cols = explode("\t", $row);
            if($i == 1) {
                $fields = $cols;
                continue;
            }
            else {
                $k = -1;
                foreach($fields as $fld) { $k++;
                    $rec[$fld] = $cols[$k];
                }
            }
            // print_r($rec); exit;
            /*Array(
                [string] => evergreen
                [measurementType] => http://purl.obolibrary.org/obo/FLOPO_0008548
                [measurementValue] => http://purl.obolibrary.org/obo/PATO_0001733
            )*/
            $this->new_patterns[$rec['string']] = array('mType' => $rec['measurementType'], 'mValue' => $rec['measurementValue']);
        }
    }
    function get_allowed_value_type_URIs_from_EOL_terms_file($download_options = false)
    {
        if($download_options) $options = $download_options;         //bec this func is also called from other libs
        else                  $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*1; //1 day expires
        if($yml = Functions::lookup_with_cache("https://raw.githubusercontent.com/EOL/eol_terms/main/resources/terms.yml", $options)) {
            /*  type: value
                uri: http://eol.org/schema/terms/neohapantotype
                parent_uris:        */
            if(preg_match_all("/type\: value(.*?)parent_uris\:/ims", $yml, $a)) {
                $arr = array_map('trim', $a[1]); // print_r($arr); exit;
                foreach($arr as $line) {
                    $uri = str_replace("uri: ", "", $line);
                    $final[$uri] = '';
                }
            }
            else exit("\nInvestigate: EOL terms file structure had changed.\n");
        }
        else exit("\nInvestigate: EOL terms file not accessible.\n");
        return $final;
    }
    function consolidate_with_EOL_Terms($mappings) # called from Functions.php
    {
        $download_options = array('expire_seconds' => 60*60*24); //expires 1 day
        $allowed_terms_URIs = self::get_allowed_value_type_URIs_from_EOL_terms_file($download_options);
        echo ("\nallowed_terms_URIs from EOL terms file: [".count($allowed_terms_URIs)."]\n");
        /*
        FROM Functions.php USED BY CONNECTORS:
        [Côte d'Ivoirereturn] => http://www.geonames.org/2287781
        [United States Virgin Islands] => http://www.wikidata.org/entity/Q11703
        [Netherlands Antillesreturn] => https://www.wikidata.org/entity/Q25227

        FROM EOL TERMS FILE:
        [http://www.geonames.org/5854968] => 
        [https://www.wikidata.org/entity/Q11703] => 
        [https://www.geonames.org/149148] => 
        */
        // step 1: create $info from eol terms file
        $tmp = array_keys($allowed_terms_URIs);
        unset($allowed_terms_URIs); # to clear memory
        foreach($tmp as $orig_uri) {
            $arr = explode(":", $orig_uri);
            $sub_uri = $arr[1];
            $info[$sub_uri] = $orig_uri; # $info['//www.wikidata.org/entity/Q11703'] = 'https://www.wikidata.org/entity/Q11703'
        }
        // step 2: loop $mappings, search each uri
        $ret = array();
        foreach($mappings as $string => $uri) {
            $arr = explode(":", $uri); // print_r($arr);
            $sub_uri = @$arr[1]; # '//www.wikidata.org/entity/Q11703'
            if($new_uri = @$info[$sub_uri]) $ret[$string] = $new_uri;
            else $ret[$string] = $uri;
        }
        return $ret;
    }
    function WoRMS_URL_format($path) # called from Pensoft2EOLAPI.php for now.
    {
        if(stripos($path, "marineregions.org/gazetteer.php?p=details&id=") !== false) { //string is found
            /* per: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=67177&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67177
            http://www.marineregions.org/gazetteer.php?p=details&id=3314
            to the equivalent in this form:
            http://www.marineregions.org/mrgid/3314
            */
            if(preg_match("/id=(.*?)elix/ims", $path."elix", $arr)) {
                $id = $arr[1];
                return "http://www.marineregions.org/mrgid/".$id;
            }
        }
        return $path;
    }
    function format_TreatmentBank_desc($desc)
    {   /* working Ok with our original text from extension: http://eol.org/schema/media/Document
        $desc = '\n'.$desc.'\n';
        $desc = str_replace('\n', ' elicha ', $desc);
        $desc = Functions::remove_whitespace($desc);
        $parts = explode("elicha", $desc); // print_r($parts); //exit;
        $final = array();
        foreach($parts as $part) {
            if($first_word = self::get_first_word_of_string($part)) {
                $this->debug['detected_first_words'][$first_word] = '';
                if(!isset($this->exclude_first_words[$first_word])) $final[] = $part;    
            }
        } 
        return implode("\n", $final);
        */

        /* utility, for Jen. Get "\nCommon names." OR "\nNames.". For decision making by Jen.
        we may not need this anymore...
        */

        /* debug only
        ksort($this->debug['detected_first_words']);
        echo "\ndetected_first_words: ";
        print_r($this->debug['detected_first_words']); 
        print_r($this->exclude_first_words);
        // exit("\n-stop muna-\n");
        */

        // /* now using the extension: http://rs.gbif.org/terms/1.0/Description
        if(preg_match("/deposited (.*?)\. /ims", $desc, $arr)) {
            $substr = $arr[1];
            $desc = str_replace($substr, "", $desc);
        }
        if(preg_match("/References: (.*?)elicha/ims", $desc."elicha", $arr)) { //no tests yet, to do:
            $substr = $arr[1];
            $desc = str_replace($substr, "", $desc);
        }
        return $desc;
        // */
    }
    private function get_first_word_of_string($str)
    {
        $arr = explode(' ', trim($str)); 
        return strtolower($arr[0]); 
    }
    function substri_count($haystack, $needle) //a case-insensitive substr_count()
    {
        return substr_count(strtoupper($haystack), strtoupper($needle));
    }
    function process_table_TreatmentBank_ENV($rec)
    {
        $pipe_delimited = $rec['http://rs.tdwg.org/ac/terms/additionalInformation'];
        $arr = explode("|", $pipe_delimited);
        $description_type = $arr[0];
        $zip_file = @$arr[1]; //seems not used anyway

        $title            = $rec['http://purl.org/dc/terms/title'];
        // $this->ontologies = "envo,eol-geonames"; //orig
        if($title == 'Title for eol-geonames')                                      {$this->ontologies = "eol-geonames"; @$this->debug['Title for eol-geonames']++;}
        elseif(in_array($description_type, array("distribution", "conservation")))  $this->ontologies = "envo,eol-geonames";
        elseif(in_array($description_type, array("description", "biology_ecology", "diagnosis", "materials_examined"))) $this->ontologies = "envo"; //the rest
        else { 
            // echo "R1 [$title] [$description_type]"; 
            $this->debug['unused data type'][$description_type] = ''; 
            return false;
        }

        /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67753&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67753
                    https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67763&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67763
        Actually, let's make one change; let's try them both in [distribution]. 
        Habitat does seem to be described pretty often in that section. 
        When I've mulled over the next draft, if coverage for geographic records seems thin, we might try geonames also in [materials examined], 
        but that section might also be a minefield of specimen-holding institution names, so it'll depend on whether we have succeeded in 
        dealing with those elsewhere. Anyway, in general locality text strings seem to appear many times, so it may not be necessary. 
            INCLUDED:
                [description]           "envo"
                [biology_ecology]       "envo"
                [diagnosis]             "envo"
                [materials_examined]    "envo"
                [distribution]          "envo,eol-geonames"
                [conservation] =>       "envo,eol-geonames"

            Additional text types: EXCLUDED
                [synonymic_list] => 
                [vernacular_names] =>
                [] => 
                [material] => 
                [ecology] => 
                [biology] => 

            Future considerations: EXCLUDED
                [food_feeding] => 
                [breeding] => 
                [activity] => 
                [use] => 
        */

        // /* current filters
        if    (in_array($description_type, array("synonymic_list", "vernacular_names", "", "material", "ecology", "biology"))) return false; //continue;
        elseif(in_array($description_type, array("food_feeding", "breeding", "activity", "use"))) return false; //continue; //Future considerations
        // */  

        return $rec;      
    }
    function load_github_dump($url) //another func parse_github_dump()
    {
        $local = Functions::save_remote_file_to_local($url, array('cache' => 1, 'expire_seconds' => 60*60*24)); //1 day expires
        $arr = explode("\n", file_get_contents($local));
        $arr = array_map('trim', $arr);
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        unlink($local);
        foreach($arr as $uri) $final[$uri] = ''; 
        // print_r($final); //debug only
        $filename = pathinfo($url, PATHINFO_FILENAME);
        echo "\n $filename: [".count($final)."] dump count\n";
        return array_keys($final);
    }
    function parse_github_dump($url, $what) //another func: load_github_dump()
    {
        $final = array();
        $local = Functions::save_remote_file_to_local($url, array('cache' => 1, 'expire_seconds' => 60*60*1)); //1 hr cache expires 60*60*1
        foreach(new FileIterator($local) as $line => $row) {
            if(!$row) continue;
            $tmp = explode("\t", $row); // print_r($tmp); exit;
            if($what == "key_value") {
                /*Array(
                    [0] => open waters
                    [1] => http://purl.obolibrary.org/obo/ENVO_00002030
                )*/
                if($val = @$tmp[1]) $final[$tmp[0]] = $val;
            }
        }
        unlink($local);
        // print_r($final); //debug only
        $filename = pathinfo($url, PATHINFO_FILENAME);
        echo "\n $filename: [".count($final)."] dump count\n";
        if($what == "key_value") return $final;        
    }
    function is_context_valid($context)
    {
        $parts = explode(" ", $context); //print_r($parts); exit;
        $i = -1;
        foreach($parts as $part) { $i++;
            $first_char = substr($part, 0, 1);
            $second_char = substr($part, 1, 1);

            // if word length is 1 char and uppercase, AND next word is uppercase e.g. "T Reyes" but not "G morhua"
            if(strlen($part) == 1 && ctype_upper($part)) {
                $next_word = @$parts[$i+1];
                $first_char_next_word = substr($next_word, 0, 1);
                if(ctype_upper($first_char_next_word)) {
                    // debug("\nINVALID: first_char_next_word: [$first_char_next_word]-false\n");
                    return false;
                }
            }

            // if word length is 2 and 1st char is upper and 2nd char is period (.) AND next word is uppercase e.g. "T. Reyes" but not "G. morhua"
            if(strlen($part) == 2 && ctype_upper($first_char) && in_array($second_char, array("."))) {
                $next_word = @$parts[$i+1];
                $first_char_next_word = substr($next_word, 0, 1);
                if(ctype_upper($first_char_next_word)) {
                    // debug("\nINVALID: [$part][$next_word]-false\n");
                    return false;
                }
            }
            
            // if word lenght is 2 and both upper e.g. "TA"
            // if(strlen($part) == 2 && ctype_upper($part)) return false;
        }
        return true;
    }
    function lbl_is_lowercase($rek)
    {
        if(strpos($rek['context'], "<b>$rek[lbl]</b>") !== false) return true; //but it has to be in lowercase. $rek['lbl'] is always in lowercase, from Pensoft.
        else return false;
    }
    function ontology_geonames_process($rek)
    {
        // /* words accepted if uppercase but excluded if lowercase
        $labels = array('malabar', 'antarctica'); //A. malabar OR C. antarctica - exclude | Off to Malabar - include
        $lbl = $rek['lbl'];
        if(in_array($lbl, $labels)) {
            if(strpos($rek['context'], "<b>$lbl</b>") !== false) {debug("\nExcluded: huli_5\n"); return false;} //continue;
        }
        // */

        // /* un-comment to allow just 4 terms. Comment to allow all terms under geonames with 'ENVO' uri. It was in the past totally disallowing terms in geonames that have ENVO uri.
        if(stripos($rek['id'], "ENVO_") !== false) { //string is found
            if(in_array($rek['lbl'], array('forest', 'woodland', 'grassland', 'savanna'))) { //accepts these terms, and maybe more once allowed by Jen.
                // /* NEW Jul 9: but they have to be in lower case not "Forest" but just "forest"
                if(strpos($rek['context'], "<b>".strtolower($rek['lbl'])."</b>") !== false) {}
                else return false;//continue;
                // */

                // /* Scopalina kuyamu (a marine sponge): forest - Ideally, this would have been matched to "kelp forest" not just forest, because kelp forests aren't really forests.
                if($rek['lbl'] == 'forest') { //is good if there is no 'kelp forest' in context
                    if(stripos($rek['context'], "kelp <b>$rek[lbl]</b>") !== false) return false;//continue; //string is found
                }
                // */
            }
            else return false;//continue;
        }
        // */
        // if commented there is error in tests for  "marine"

        // echo "\nGoes- 82\n";
        if(in_array($rek['lbl'], array('jordan', 'guinea', 'washington'))) return false;//continue; //always remove
        if(in_array($rek['id'], array('http://www.geonames.org/1327132',                //https://eol-jira.bibalex.org/browse/DATA-1887?focusedCommentId=66190&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66190
                                        'https://www.geonames.org/3463504'))) return false;//continue;   //https://eol-jira.bibalex.org/browse/DATA-1887?focusedCommentId=66197&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66197
        
        // /* exclude if context has certain strings that denote a literature reference - FOR ALL RESOURCES
        // vol. 8, p. 67. 1904.Tylobolus uncigerus, Brolemann, Ann. Soc. Ent. <b>France</b>, vol. 83, pp. 9, 22, fig.
        $parts_of_lit_ref = array(' vol.', ' p.', ' pp.', ' fig.', ' figs.', 'legit ', 'coll. ', ' ed. ', 'eds. '); 
        //, 'legit', 'coll.' from Katja
        //, ' ed. ', 'eds. ' from Eli 
        $cont = true;
        foreach($parts_of_lit_ref as $part) {
            if(stripos($rek['context'], $part) !== false) { 
                $cont = false;
                @$this->debug['Excluded: part:'][$part]++;
                // debug("\nExcluded: part: [$part]\n"); 
            } //string is found
        }
        if(!$cont) return false;//continue;
        // */
        return $rek;
    }
    function ontology_habitat_process($rek)
    {
        /* all legit combined below
        if(in_array($rek['lbl'], array('mesa', 'laguna'))) continue; //https://eol-jira.bibalex.org/browse/DATA-1877?focusedCommentId=65899&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65899
        if(in_array($rek['lbl'], array('rapids'))) continue; //118950_ENV https://eol-jira.bibalex.org/browse/DATA-1887?focusedCommentId=66259&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66259
        // remove 'ocean' (measurementValue = http://purl.obolibrary.org/obo/ENVO_00000447) for all resources. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1897?focusedCommentId=66613&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66613
        if(in_array($rek['lbl'], array('ocean', 'sea'))) continue;
        if($rek['id'] == 'http://purl.obolibrary.org/obo/ENVO_00000447') continue;                
        // per: https://eol-jira.bibalex.org/browse/DATA-1914 - as of Sep 20, 2022
        if(in_array($rek['lbl'], array('organ', 'field', 'well', 'adhesive', 'quarry', 'reservoir', 'umbrella', 'plantation', 'bar', 'planktonic material'))) continue;
        // exclude per: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67731&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67731
        // planktonic material
        */
        if($rek['id'] == 'http://purl.obolibrary.org/obo/ENVO_00000447') return false;//continue;                
        if(in_array($rek['lbl'], array('mesa', 'laguna', 'rapids', 'ocean', 'sea', 'organ', 'field', 'well', 'adhesive', 'quarry', 'reservoir', 'umbrella', 'plantation', 'bar', 'planktonic material'))) return false;//continue;
        if($rek['lbl'] == 'marsh') { //"marsh harrier" is a vernacular for a kind of bird
            if(stripos($rek['context'], 'harrier') !== false) return false;//continue; //string is found
        }

        // /* ---------- source text: "iceberg" -> marine iceberg (22Aug2024)
        // None of the taxa mapped to this term occur on marine icebergs. Most mismappings are due to place name matches or use of the "tip of the iceberg" metaphor.                
        if($rek['lbl'] == 'iceberg') { //
            if(stripos(strip_tags($rek['context']), 'tip of the') !== false) return false;//continue; //string is found
        }
        if(in_array($rek['lbl'], array('iceberg'))) { //accepts these terms, and maybe more...
            if(self::lbl_is_lowercase($rek)) {} //but lbl has to be in lowercase.
            else return false;//continue;
        }
        // OR we totally blacklist 'iceberg'. We will only know after we generate the latest DwCA. I sense we will eventually blacklist 'iceberg'.
        // ---------- */

        // print_r($rek);
        // /* ---------- source text: "canal" -> canal (22Aug2024)
        // - Lots of spiders, insects and other terrestrial taxa are mapped due to string matches in place names.
        // - There are also quite a few invalid matches due to descriptions of alimentary canals, e.g., Metaphire taiwanensis and many other earthworms & millipedes.
        if($rek['lbl'] == 'canal') { //"alimentary canal" is part of the body
            if(stripos($rek['context'], 'alimentary') !== false) return false;//continue; //string is found
            if(self::lbl_is_lowercase($rek)) {} //but lbl has to be in lowercase.
            else return false;//continue;
        }
        // ---------- */

        // /* ---------- source text: "mountain" -> mountain
        // There are a quite a few invalid mappings of marine taxa due to place name matches, e.g., Galapagomystides verenae, Sericosura dentatus, Allocareproctus unangas
        if(in_array($rek['lbl'], array('mountain', 'mountains'))) {
            if(self::lbl_is_lowercase($rek)) {} //but lbl has to be in lowercase.
            else return false;//continue;
        }
        // ---------- */

        // /* ---------- source text: "orchard" -> orchard
        // Some invalid mappings of marine taxa due to place name matches, e.g., Chone aurantiaca, Zelentia nepunicea
        if(in_array($rek['lbl'], array('orchard', 'orchards'))) {
            if(self::lbl_is_lowercase($rek)) {} //but lbl has to be in lowercase.
            else return false;//continue;
        }
        // ---------- */

        // /* ---------- source text: "bay" -> bay
        // Most of these trait records seem come from matches in place names, quite a few of them are for terrestrial taxa 
        // that don't actually occur in a bay or immediately next to a bay, e.g. Asphalidesmus golovatchi, Paracondeellum paradisum, Gossia vieillardii
        if(in_array($rek['lbl'], array('bay', 'bays'))) {
            if(self::lbl_is_lowercase($rek)) {} //but lbl has to be in lowercase.
            else return false;//continue;
        }
        // ---------- */

        // /* Oxydromus humesi (a marine polychaete): marsh - Ideally, this would have been matched to "salt marsh" not just marsh, because both mentions of marsh in the treatment actually refer to salt marsh.
        if($rek['lbl'] == 'marsh') { //is good if there is no 'salt marsh' in context
            if(stripos($rek['context'], "salt <b>$rek[lbl]</b>") !== false) return false;//continue; //string is found
        }
        // */
        return $rek;
    }
}
?>