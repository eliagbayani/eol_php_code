<?php
namespace php_active_record;
/* connector: [africa_tree_db.php] 
Part of this connector was taken from CSV2DwCA_Utility.php
*/
class AfricaTreeDBAPI
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->debug = array();
        $this->for_mapping = array();
        
        $this->download_options = array(
            'expire_seconds'     => 60*60*24*30, //expires in 1 month
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        /* 'Use' mapping from Jen: https://opendata.eol.org/dataset/africa-tree-database/resource/5bce8f9a-933e-4f23-bb4d-e7260f0ba1cf */
        $this->use_mapping_from_jen = "https://opendata.eol.org/dataset/e31baa95-af6c-4539-a1d8-00f7364fadcd/resource/5bce8f9a-933e-4f23-bb4d-e7260f0ba1cf/download/use-mapping.csv";
        $this->addtl_mapping_from_jen = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/AfricaTreeDB/AfricaTreeLocalities.txt"; //based from Eli's un-mapped string report.
        $this->partner_bibliographicCitation = "Carrie Seltzer, William Wysocki, Melissa Palacios, Anna Eickhoff, Hannah Pilla, Jordan Aungst, Aaron Mercer, Jamie Quicho, Neil Voss, Man Xu, Henry J. Ndangalasi, Jon C. Lovett, Norbert J. Cordeiro. Posted October 21, 2015. The Africa Tree Database. Accessed at https://figshare.com/articles/SQL_of_Africa_Tree_Database/1526125, January 16, 2019.";
        $this->partner_source_url = "https://figshare.com/articles/SQL_of_Africa_Tree_Database/1526125";
    }
    private function addtl_mappings()
    {
        $options = $this->download_options;
        // $options['expire_seconds'] = true; //debug only
        $tmp_file = Functions::save_remote_file_to_local($this->addtl_mapping_from_jen, $options);
        $i = 0;
        foreach(new FileIterator($tmp_file) as $line => $row) {
            $row = Functions::conv_to_utf8($row);
            $i++; 
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); //exit;
                /*Array(
                    [distribution.csv] => southern Africa to Madagascar
                    [measurementType] => http://eol.org/schema/terms/Present
                    [measurementValue] => "http://www.geonames.org/9406051, http://www.geonames.org/1062947"
                    [measurementRemarks] => 
                )
                OR
                [measurementValue] => http://eol.org/schema/terms/TropicalAfrica
                */
                $str = str_replace('"', "", $rec['distribution.csv']);
                $str = strtoupper($str);
                $final[$str] = $rec;
            }
        }
        unlink($tmp_file);
        $this->addtl_mappings = $final;
        // print_r($final); exit;
    }
    function convert_archive()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        
        self::initialize_mapping(); //un-comment in real operation
        self::addtl_mappings(); //initialize addtl mappings from Jen, based on Eli's un-mapped string report
        if(!($info = self::prepare_archive_for_access())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        $locations = array("distribution.csv", "use.csv", "description.csv");
        echo "\nProcessing CSV archive...\n";
        // print_r($tables); exit;
        // print_r($tables['http://eol.org/schema/media/document']); exit;
        
        foreach($tables['http://eol.org/schema/reference/reference'] as $tbl) {
            echo "\n -- Processing [$tbl->location]...\n";
            self::process_extension($tbl->file_uri, $tbl, $tbl->location, 'reference');
        }
        
        foreach($tables['http://rs.gbif.org/terms/1.0/vernacularname'] as $tbl) {
            echo "\n -- Processing [$tbl->location]...\n";
            self::process_extension($tbl->file_uri, $tbl, $tbl->location, 'comnames');
        }

        foreach($tables['http://eol.org/schema/media/document'] as $tbl) {
            if(in_array($tbl->location, $locations)) {
                echo "\n -- Processing [$tbl->location]...\n";
                if($tbl->location == "description.csv") self::process_extension($tbl->file_uri, $tbl, $tbl->location, 'text_object');
                else                                    self::process_extension($tbl->file_uri, $tbl, $tbl->location, 'traitbank');
            }
        }

        foreach($tables['http://rs.tdwg.org/dwc/terms/taxon'] as $tbl) {
            echo "\n -- Processing [$tbl->location]...\n";
            self::process_extension($tbl->file_uri, $tbl, $tbl->location, 'taxon');
        }
        
        $this->archive_builder->finalize(true);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);

        //massage debug for printing
        $countries = array(); $territories = array();
        if($use_csv = @$this->debug['use.csv']) {
            if($countries = array_keys($use_csv)) asort($countries);
        }
        if($distribution_csv = @$this->debug['distribution.csv']) {
            if($territories = array_keys($distribution_csv)) asort($territories);
        }
        $this->debug = array();
        foreach($countries as $c) $this->debug['use.csv'][$c] = '';
        foreach($territories as $c) $this->debug['distribution.csv'][$c] = '';
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function prepare_archive_for_access()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => false)); //won't expire anymore
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    function get_unmapped_strings()
    {
        self::initialize_mapping(); //un-comment in real operation
        if(!($info = self::prepare_archive_for_access())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        $locations = array("distribution.csv", "use.csv");
        echo "\nProcessing CSV archive...\n";
        foreach($tables['http://eol.org/schema/media/document'] as $tbl) {
            if(in_array($tbl->location, $locations)) {
                echo "\n -- Processing [$tbl->location]...\n";
                self::process_extension($tbl->file_uri, $tbl, $tbl->location, 'utility');
            }
        }
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
        //massage debug for printing
        $countries = array_keys($this->for_mapping['use.csv']); asort($countries);
        $territories = array_keys($this->for_mapping['distribution.csv']); asort($territories);
        $this->for_mapping = array();
        foreach($countries as $c) $this->for_mapping['use.csv'][$c] = '';
        foreach($territories as $c) $this->for_mapping['distribution.csv'][$c] = '';
        Functions::start_print_debug($this->for_mapping, $this->resource_id);
    }
    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }
    private function process_extension($csv_file, $tbl, $group, $purpose = 'traitbank') //purpose = traitbank OR utility
    {
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row);
            // print_r($row);
            $i++; if(($i % 2000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                // print_r($fields); print_r($rec); exit;
                /*Array(
                    [id] => dist_99
                    [blank_1] => http://purl.org/dc/dcmitype/Text
                    [blank_2] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution
                    [Plant No] => 99
                    [Region] => Eastern Arc Mountains: Udzungwa Mts ; Eastern Arc Mountains: West Usambara Mts
                    [Ref] => 1
                    [blank_3] => http://creativecommons.org/licenses/by-sa/3.0/
                )
                Array(
                    [0] => id
                    [1] => blank_1
                    [2] => blank_2
                    [3] => Plant
                    [4] => Use
                    [5] => Ref
                    [6] => blank_3
                )
                */
                if($purpose == 'traitbank') self::create_trait($rec, $group);
                elseif($purpose == 'taxon') self::create_taxon($rec);
                elseif($purpose == 'comnames') self::create_vernaculars($rec);
                elseif($purpose == 'reference') self::create_reference($rec);
                elseif($purpose == 'text_object') self::create_text_object($rec);
                elseif($purpose == 'utility') {
                    if($val = @$rec['Region']) $this->for_mapping = self::separate_strings($val, $this->for_mapping, $group);
                    if($val = @$rec['Use'])    $this->for_mapping = self::separate_strings($val, $this->for_mapping, $group);
                }
            } //main records
        } //main loop
        fclose($file);
    }
    private function create_vernaculars($rec)
    {
        // print_r($rec); exit;
        /*Array(
            [REF|Plant|theplant] => 111
            [common] =>  Ol darakwa 
            [Language to Change ISO 639-3] => mas
            [country] => TZ
            [REF|Reference|ref] => 1
            [blank_1] => http://creativecommons.org/licenses/by-sa/3.0/
        )*/
        $this->taxa_with_trait[$rec['REF|Plant|theplant']] = ''; //to be used when creating taxon.tab
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec['REF|Plant|theplant'];
        $v->vernacularName  = $rec['common'];
        $v->language        = $rec['Language to Change ISO 639-3'];
        $v->countryCode     = $rec['country'];
        $this->archive_builder->write_object_to_file($v);
    }
    private function create_reference($rec)
    {
        // print_r($rec); exit;
        /*Array(
            [DEF_id] => 1
            [author] => Lovett; J.C. and Sorensen; L. and Lovett; J.
            [year] => 2006
            [title] => Field guide to the moist forest trees of Tanzania
            [journal] => 
            [volume] => 
            [number] => 
            [pages] => 
        )*/
        $r = new \eol_schema\Reference();
        $r->identifier = $rec['DEF_id'];
        $r->full_reference = $rec['author']." ".$rec['year'].". ".$rec['title'].".";
        $r->authorList = $rec['author'];
        $r->title = $rec['title'];
        // $r->uri = '';
        if(!isset($this->reference_ids[$r->identifier])) {
            $this->reference_ids[$r->identifier] = '';
            $this->archive_builder->write_object_to_file($r);
        }
    }
    private function create_text_object($rec)
    {
        // print_r($rec); //exit;
        /*Array(
            [DEF_id] => desc_1
            [type] => http://purl.org/dc/dcmitype/Text
            [Subject] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription
            [REF|Plant|theplant] => 1
            [description] => <b>Bole:</b>  Small/medium. To 24 m.  <b>Bark:</b>  Grey/pale green. Smooth.  <b>Slash:</b>  Yellow with white or yellow lines.  <b>Leaf:</b>  Simple. Alternate.  <b>Petiole:</b>  0.5 - 2.5 cm.  <b>Lamina:</b>  Medium. 4 - 19 × 2.5 - 10 cm (Juvenile up to 25 × 27 cm). Ovate/elliptic. Cuneate/cordate. Asymmetric. 5 - 7 nerved from base. Acuminate. Entire. Hairy/glabrous. Simple.  <b>Domatia:</b>  Present/absent. Small tufts of hairs.  <b>Glands:</b>   Absent.  <b>Stipules:</b>  Absent.  <b>Thorns & Spines:</b>  Absent.  <b>Flower:</b>  White/pale yellow. Fragrant. Infloresence 3 - 23 flowered axillary cymes. Hermaphrodite.  <b>Fruit:</b>  Globose 0.8 - 1.0 × 0.4 - 0.9 cm.
            [REF|Reference|ref] => 1
            [blank_1] => http://creativecommons.org/licenses/by-sa/3.0/
            [Title] => Botanical Description
        )
        Array(
            [DEF_id] => desc_659
            [type] => http://purl.org/dc/dcmitype/Text
            [Subject] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription
            [REF|Plant|theplant] => 654
            [description] => <b>Bole:</b>  Small. To 10 m.  <b>Bark:</b>  NR.  <b>Slash:</b>  NR.  <b>Leaf:</b>  Simple. Alternate.  <b>Petiole:</b>  0.5 - 3 cm. Bristly pubescent.  <b>Lamina:</b>  Medium. 7 - 18 × 3 - 7 cm. Ovate/oblong/oblong-lanceolate. Cuneate. Acuminate. Serrate. Glabrous above; slightly hairy beneath.  <b>Domatia:</b>  Absent.  <b>Glands:</b>   Brown dots underneath leaves.  <b>Stipules:</b>  Present.  <b>Thorns & Spines:</b>  Absent.  <b>Flower:</b>  Slender terminal thyrse.  <b>Fruit:</b>  Capsule 3-lobed. 0.1 - 1.7 cm long.
            [REF|Reference|ref] => 1
            [blank_1] => http://creativecommons.org/licenses/by-sa/3.0/
            [Title] => Botanical Description
        )*/
        $this->taxa_with_trait[$rec['REF|Plant|theplant']] = ''; //to be used when creating taxon.tab
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $rec['REF|Plant|theplant'];
        $mr->identifier     = $rec['DEF_id'];
        $mr->type           = $rec['type'];
        $mr->language       = 'en';
        $mr->format         = "text/html";
        $mr->CVterm         = $rec['Subject'];
        // $mr->Owner          = '';
        // $mr->rights         = '';
        $mr->title          = $rec['Title'];
        $mr->UsageTerms     = $rec['blank_1'];
        $mr->description    = $rec['description'];
        // $mr->LocationCreated = '';
        $mr->bibliographicCitation = $this->partner_bibliographicCitation;
        $mr->furtherInformationURL = $this->partner_source_url;
        $mr->referenceID = $rec['REF|Reference|ref'];
        if(!@$rec['REF|Reference|ref']) {
            print_r($rec);
            exit("\nNo reference!\n");
        }
        // if($agent_ids = )  $mr->agentID = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function create_taxon($rec)
    {
        if(!isset($this->taxa_with_trait[$rec['DEF_id']])) return;
        // print_r($rec); exit;
        /*Array(
            [DEF_id] => 1
            [family] => Alangiaceae 
            [genus] => Alangium
            [scientific name] => Alangium chinense 
            [species] => chinense
            [subspecies] => 
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['DEF_id'];
        $taxon->scientificName  = $rec['scientific name'];
        $taxon->family          = $rec['family'];
        $taxon->genus           = $rec['genus'];
        // $taxon->taxonRank             = '';
        // $taxon->furtherInformationURL = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function create_trait($rek, $group)
    {
        if($group == "distribution.csv") {
            $arr = explode(";", $rek['Region']);
            $taxon_id = $rek['Plant No'];
            $mtype = "http://eol.org/schema/terms/Present";
        }
        elseif($group == "use.csv") {
            $arr = explode(";", $rek['Use']);
            $taxon_id = $rek['Plant'];
            $mtype = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Use";
        }
        $arr = array_map('trim', $arr);
        // print_r($arr); exit;
        foreach($arr as $string_val) {
            if($string_val) {
                $string_val = Functions::conv_to_utf8($string_val);
                $rec = array();
                $rec["taxon_id"] = $taxon_id;
                $rec["catnum"] = $taxon_id.'_'.$rek['id'];
                if($string_uri = self::get_string_uri($string_val)) {
                    $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
                    $rec['measurementRemarks'] = $string_val;
                    $rec['bibliographicCitation'] = $this->partner_bibliographicCitation;
                    $rec['source'] = $this->partner_source_url;
                    $rec['referenceID'] = 1;
                    $this->func->add_string_types($rec, $string_uri, $mtype, "true");
                }
                elseif($val = @$this->addtl_mappings[strtoupper(str_replace('"', "", $string_val))]) {
                    $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
                    $rec['measurementRemarks'] = $string_val;
                    self::write_addtl_mappings($val, $rec);
                }
                else $this->debug[$group][$string_val] = '';
            }
        }
    }
    private function write_addtl_mappings($rek, $rec)
    {
        // print_r($rek); exit;
        /*Array(
            [distribution.csv] => Central Africa
            [measurementType] => http://eol.org/schema/terms/Present
            [measurementValue] => http://www.geonames.org/7729886
            [measurementRemarks] => 
        )*/
        if($rek['measurementType'] == "DISCARD") return;
        $rec['measurementRemarks'] = $rek['measurementRemarks'];
        // print_r($rec); exit;
        /*Array(
            [taxon_id] => 1
            [catnum] => 1_dist_1
            [measurementRemarks] => 
        )*/
        $tmp = str_replace('"', "", $rek['measurementValue']);
        $tmp = explode(",", $tmp);
        $tmp = array_map('trim', $tmp);
        // print_r($tmp); exit;
        /*Array(
            [0] => http://www.geonames.org/7729886
        )*/
        foreach($tmp as $string_uri) {
            $rec['bibliographicCitation'] = $this->partner_bibliographicCitation;
            $rec['source'] = $this->partner_source_url;
            $rec['referenceID'] = 1;
            $this->func->add_string_types($rec, $string_uri, $rek['measurementType'], "true");
        }
    }
    private function get_string_uri($string)
    {
        switch ($string) { //put here customized mapping
            case "NR":                return false; //"DO NOT USE";
            // case "United States of America":    return "http://www.wikidata.org/entity/Q30";
        }
        if($string_uri = @$this->uris[$string]) return $string_uri;
    }
    private function separate_strings($str, $ret, $group)
    {
        $arr = explode(";", $str);
        $arr = array_map('trim', $arr);
        foreach($arr as $item) {
            if(!isset($this->uris[$item])) $ret[$group][$item] = '';
                                        // $ret[$group][$item] = '';
        }
        return $ret;
    }
    private function fill_up_blank_fieldnames($fields)
    {
        $i = 0;
        foreach($fields as $field) {
            if($field) $final[$field] = '';
            else {
                $i++;
                $final['blank_'.$i] = '';
            } 
        }
        return array_keys($final);
    }
    private function initialize_mapping()
    {
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        self::use_mapping_from_jen();
        // print_r($this->uris);
    }
    private function use_mapping_from_jen()
    {
        $csv_file = Functions::save_remote_file_to_local($this->use_mapping_from_jen, $this->download_options);
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row);
            // print_r($row);
            $i++; if(($i % 2000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                // print_r($fields); print_r($rec); exit;
                /*Array(
                    [Use string] => timber
                    [URI] => http://purl.obolibrary.org/obo/EUPATH_0000001
                    [blank_1] => 
                    [blank_2] => 
                    [blank_3] => 
                    [blank_4] => 
                )*/
                $this->uris[$rec['Use string']] = $rec['URI'];
            } //main records
        } //main loop
        fclose($file);
        unlink($csv_file);
    }
}
?>
