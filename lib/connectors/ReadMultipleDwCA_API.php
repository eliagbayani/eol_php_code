<?php
namespace php_active_record;
/* connector: [read_multiple_dwca.php] - first client 
This lib basically reads multiple DwCAs.
*/
class ReadMultipleDwCA_API extends DwCA_Aggregator_Functions
{
    function __construct($folder = NULL, $dwca_file = NULL, $DwCA_Type = 'wikipedia') //'wikipedia' is the first client of this lib.
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->DwCA_Type = $DwCA_Type;
        $this->debug = array();
        /* Please take note of some Meta XML entries have upper and lower case differences */
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",
                                  //start of other row_types: check for NOTICES or WARNINGS, add here those undefined URIs
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  "http://eol.org/schema/reference/reference"       => "reference",
                                  "http://eol.org/schema/association"               => "association");
        $this->report_file              = CONTENT_RESOURCE_LOCAL_PATH . '/reports/textmined_records.tsv';
        $this->textmined_resources_file = CONTENT_RESOURCE_LOCAL_PATH . '/reports/textmined_resources.json';
        /* copied template
        $this->attributions = array();
        $this->download_TB_options = array( //same value as in TreatmentBankAPI.php
            'resource_id'        => "TreatmentBank",
            'expire_seconds'     => false, //expires set to false for now
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1); */
    }
    function process_DwCAs($resource_ids, $preferred_rowtypes = array())
    {
        self::initialize_tsv();
        foreach($resource_ids as $resource_id) {
            $this->taxon_occurrences = array();
            $this->occurrence_MoFs = array();

            echo "\n---Processing: [$resource_id]---\n";
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.$resource_id.'.tar.gz';
            if(file_exists($dwca_file)) {
                self::convert_archive($preferred_rowtypes, $dwca_file);
            }
            else echo "\nDwCA file does not exist [$dwca_file]\n";
            break; //debug only
        }
        $this->archive_builder->finalize(TRUE);
    }
    private function convert_archive($preferred_rowtypes = false, $dwca_file, $download_options = array('timeout' => 172800, 'expire_seconds' => 0))
    {   /* param $preferred_rowtypes is the option to include-only those row_types you want on your final DwCA. */
        echo "\nConverting archive to EOL DwCA [$dwca_file]...\n";
        $info = self::start($dwca_file, $download_options); //1 day expire -> 60*60*24*1
        $temp_dir = $info['temp_dir'];
        $this->temp_dir = $temp_dir; //first client is TreatmentBank. Used in reading eml.xml from the source DwCA
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        /* e.g. $index -> these are the row_types
        Array
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.gbif.org/terms/1.0/vernacularname
            [2] => http://rs.tdwg.org/dwc/terms/occurrence
            [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        print_r($index); //exit("\nLet us investigate first.\n"); //good debug to see the all-lower case URIs
        $index = $this->let_media_document_go_first_over_description($index); //print_r($index); exit("\nstop muna\n"); //copied template
        
        /* Step 1: build-up */
        $row_types = array('http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        foreach($row_types as $row_type) {
            $params = array('row_type' => $row_type, 'meta' => $tables[$row_type][0], 'task' => 'build-up');
            self::process_row_type($params);
        }
        /* Step 2: write_tsv */
        $row_types = array('http://rs.tdwg.org/dwc/terms/taxon');
        foreach($row_types as $row_type) {
            $params = array('row_type' => $row_type, 'meta' => $tables[$row_type][0], 'task' => 'write-to-tsv');
            self::process_row_type($params);
        }

        // foreach($index as $row_type) {}
                
        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    } //end convert_archive()
    private function process_row_type($params)
    {   $row_type = $params['row_type'];
        if($extension_row_type = @$this->extensions[$row_type]) { //process only defined row_types
            $params['extension_row_type'] = $extension_row_type;
            echo "\nprocessing...: [$row_type]: ".$extension_row_type."...\n"; //exit;
            self::process_table($params);
        }
        else echo "\nun-initialized: [$row_type]: ".$extension_row_type."\n";
    }
    private function process_table($params)
    {   //print_r($params); exit;
        $meta = $params['meta'];
        $what = $params['extension_row_type'];
        $row_type = $params['row_type'];
        $task = $params['task'];
        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {            
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);            
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            if(!$tmp) continue;

            $excluded_terms = array();
            /* copied template: New: Sep 2, 2021 -> customization needed since some fields from partner's DwCA is not recognized by EOL
            // print_r($meta->fields); exit;
            if($this->resource_id == "TreatmentBank" && $what == 'taxon') {
                $excluded_terms = array('http://plazi.org/terms/1.0/basionymAuthors', 'http://plazi.org/terms/1.0/basionymYear', 'http://plazi.org/terms/1.0/combinationAuthors', 'http://plazi.org/terms/1.0/combinationYear', 'http://plazi.org/terms/1.0/verbatimScientificName');
            }
            $replaced_terms = array();
            if($this->resource_id == "TreatmentBank" && $what == 'document') {
                $replaced_terms["http://rs.tdwg.org/dwc/terms/additionalInformationURL"] = "http://rs.tdwg.org/ac/terms/furtherInformationURL";
            }
            */

            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                $term = $field['term'];
                if(!$term) continue;
                // /* New: Sep 2, 2021
                if(in_array($term, $excluded_terms)) { $k++; continue; }
                if($val = @$replaced_terms[$term]) $term = $val;
                // */
                $rec[$term] = @$tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstop muna...\n");

            if    ($task == 'build-up')     self::task_build_up($rec, $what);
            elseif($task == 'write-to-tsv') self::task_write_to_tsv($rec);

            // ====================================================== stops here...            
            continue; exit("\nshould not go here...\n");
            // ====================================================== stops here...

        } //end foreach()
    }
    private function task_build_up($rec, $what)
    {
        if($what == 'taxon') {
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 8c5b6e4b4fe26afbe7e2ca51a50ca35f
                [http://rs.tdwg.org/dwc/terms/scientificName] => Pelecotoma flavipes
            )*/
        }
        elseif($what == 'occurrence') {
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 3bbaa00ab3e7f0872c6f041e6c7fd6b9_119035_ENV
                [http://rs.tdwg.org/dwc/terms/taxonID] => 754d18eae95c48266764cce4fd2b3d32
            )*/
            $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $this->taxon_occurrences[$taxonID][] = $occurrenceID;
        }
        elseif($what == 'measurementorfact') {
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => d02a34c6a19ad91231d1ec638385f632_119035_ENV
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => e48b9f6fbde9bfc02d15f2e2477bca8c_119035_ENV
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.geonames.org/4155751
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "female Homotype from Monticello _Florida_ in the collection of"
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $this->occurrence_MoFs[$occurrenceID][] = $rec;
        }
    }
    private function initialize_tsv()
    {
        $fhandle = Functions::file_open($this->report_file, "w");
        $headers = array("resource ID", "scientificName", "kingdom", "phylum", "class", "order", "family", 
            "measurementID", "measurementType", "measurementValue", "measurementRemarks", "source", "bibliographicCitation", "measurementUnit", "statisticalMethod");
        fwrite($fhandle, implode("\t", $headers) . "\n");
        fclose($fhandle);
    }
    private function task_write_to_tsv($rec)
    {
        $fhandle = Functions::file_open($this->report_file, "a");
        // print_r($rec);         
        // [http://rs.tdwg.org/dwc/terms/taxonID] => 8c5b6e4b4fe26afbe7e2ca51a50ca35f
        // [http://rs.tdwg.org/dwc/terms/scientificName] => Pelecotoma flavipes
        $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];

        if($occurrenceIDs = @$this->taxon_occurrences[$taxonID]) { //print_r($occurrenceIDs);
            foreach($occurrenceIDs as $occurrenceID) {
                // print_r($this->occurrence_MoFs[$occurrenceID]);
                foreach($this->occurrence_MoFs[$occurrenceID] as $m) {
                    if(self::valid_record($m)) {
                        $save = array();
                        $save[] = 'res id';
                        $save[] = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
                        $save[] = @$rec['http://rs.tdwg.org/dwc/terms/kingdom'];
                        $save[] = @$rec['http://rs.tdwg.org/dwc/terms/phylum'];
                        $save[] = @$rec['http://rs.tdwg.org/dwc/terms/class'];
                        $save[] = @$rec['http://rs.tdwg.org/dwc/terms/order'];
                        $save[] = @$rec['http://rs.tdwg.org/dwc/terms/family'];    
                        $save[] = $m['http://rs.tdwg.org/dwc/terms/measurementID'];
                        $save[] = $m['http://rs.tdwg.org/dwc/terms/measurementType'];
                        $save[] = $m['http://rs.tdwg.org/dwc/terms/measurementValue'];
                        $save[] = @$m['http://rs.tdwg.org/dwc/terms/measurementRemarks'];
                        $save[] = @$m['http://purl.org/dc/terms/source'];
                        $save[] = @$m['http://purl.org/dc/terms/bibliographicCitation'];
                        $save[] = @$m['http://rs.tdwg.org/dwc/terms/measurementUnit'];
                        $save[] = @$m['http://eol.org/schema/terms/statisticalMethod'];
                        fwrite($fhandle, implode("\t", $save) . "\n");    
                    }
                }
            }
        }
        fclose($fhandle);
    }
    private function valid_record($m)
    {
        $mType = $m['http://rs.tdwg.org/dwc/terms/measurementType'];
        if(in_array($mType, array('http://eol.org/schema/terms/Present', 'http://purl.obolibrary.org/obo/RO_0002303'))) return true;
        $mRemarks = @$m['http://rs.tdwg.org/dwc/terms/measurementRemarks'];
        if(substr($mRemarks, 0, 12) == 'source text:') return true;
        return false;
        // source text: "species from Florida Georgia _Kansas_ and Texas. Specimens examined"        
    }
    private function start($dwca_file = false, $download_options = array('timeout' => 172800, 'expire_seconds' => false)) //probably default expires in a month 60*60*24*30. Not false.
    {
        if($dwca_file) $this->dwca_file = $dwca_file;
        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths);
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_05106/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_05106/'
        );
        */
        
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
    private function initialize_zenodo()
    {
        require_library('connectors/ZenodoFunctions');
        require_library('connectors/ZenodoConnectorAPI');
        require_library('connectors/ZenodoAPI');
        $this->zenodo = new ZenodoAPI();
    }
    function build_resources_list()
    {
        self::initialize_zenodo();
        $resources = self::get_all_textmining_resources(); print_r($resources);
        foreach($resources as $res_name => $rec) {
            $zenodo_id = pathinfo($rec['zenodo_uri'], PATHINFO_FILENAME);
            echo("\n$zenodo_id\n");
            $obj = $this->zenodo->retrieve_dataset($zenodo_id); //2nd param $versionLatestYN by default is true.
            // print_r($obj); exit("\nstop muna 1a\n");
            if($isSourceOf = self::get_relation($obj, 'isSourceOf'))         $resources[$res_name]['eol_resource_id'] = $isSourceOf;
            if($isSupplementTo = self::get_relation($obj, 'isSupplementTo')) $resources[$res_name]['eol_resource_url'] = $isSupplementTo;
            // break; //debug only
        }
        print_r($resources);
        // save to a json file
        if(!($file = Functions::file_open($this->textmined_resources_file, "w"))) return;
        fwrite($file, json_encode($resources));
        fclose($file);
    }
    private function get_relation($o, $relation)
    {
        foreach($o['metadata']['related_identifiers'] as $i) {
            if($i['relation'] == $relation) return $i['identifier'];
        }
        return false;
    }
    private function get_all_textmining_resources()
    {
        $a = array();
        $a['Wikipedia: Wikipedia English - traits (inferred records)']['zenodo_uri'] = 'https://zenodo.org/records/14437247';
        $a['TreatmentBank']['zenodo_uri'] = 'https://zenodo.org/records/13321535';
        $a['Smithsonian Contributions Series: Smithsonian Contributions to Botany']['zenodo_uri'] = 'https://zenodo.org/records/13321713';
        $a['Memoirs of the American Entomological Society']['zenodo_uri'] = 'https://zenodo.org/records/15039847';
        $a['North American Flora: North American Flora - ALL']['zenodo_uri'] = 'https://zenodo.org/records/15020541';
        $a['Nota Lepidopterologica: Nota Lepidopterologica (798)']['zenodo_uri'] = 'https://zenodo.org/records/13321662';
        $a['Zoosystematics and Evolution: Zoosystematics and Evolution (834)']['zenodo_uri'] = 'https://zenodo.org/records/13321654';
        $a['Deutsche Entomologische Zeitschrift: Deutsche Entomologische Zeitschrift (792)']['zenodo_uri'] = 'https://zenodo.org/records/13321642';
        $a['Zookeys: ZooKeys (20) DwCA']['zenodo_uri'] = 'https://zenodo.org/records/13316129';
        $a['AmphibiaWeb: AmphibiaWeb text w/traits based on Pensoft Annotator']['zenodo_uri'] = 'https://zenodo.org/records/13318110';
        $a['Zookeys: Zookeys (829)']['zenodo_uri'] = 'https://zenodo.org/records/14889995';
        $a['Mycokeys: Mycokeys (830)']['zenodo_uri'] = 'https://zenodo.org/records/14890008';
        $a['Phytokeys: Phytokeys (826)']['zenodo_uri'] = 'https://zenodo.org/records/14890085';
        $a['Journal of Hymenoptera Research: Journal of Hymenoptera Research (831)']['zenodo_uri'] = 'https://zenodo.org/records/14890097';
        return $a;
    }
}
?>