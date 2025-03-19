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
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/reports/compiled_trait.tsv';
        /* copied template
        $this->attributions = array();
        $this->download_TB_options = array( //same value as in TreatmentBankAPI.php
            'resource_id'        => "TreatmentBank",
            'expire_seconds'     => false, //expires set to false for now
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1); */
    }
    function process_DwCAs($resource_ids, $preferred_rowtypes = array())
    {
        foreach($resource_ids as $resource_id) {
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
        
        $row_types = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://rs.tdwg.org/dwc/terms/occurrence', 'http://rs.tdwg.org/dwc/terms/measurementorfact');
        foreach($row_types as $row_type) {
            $params = array('row_type' => $row_type, 'meta' => $tables[$row_type][0], 'task' => 'build-up');
            self::process_row_type($params);
            // break; //debug only
        }

        // foreach($index as $row_type) {}
        
        // /* ================================= start of customization =================================
        /* copied template
        if(in_array($this->resource_id, array('wikipedia_combined_languages', 'wikipedia_combined_languages_batch2'))) {
            $tables = $info['harvester']->tables;
            // print_r($tables); exit;
            // Array(
            //     [0] => http://rs.tdwg.org/dwc/terms/taxon
            //     [1] => http://eol.org/schema/media/document
            // )
            self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
            self::process_table($tables['http://eol.org/schema/media/document'][0], 'document');
        } */
        // ================================= end of customization ================================= */ 
        
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
    {   
        $meta = $params['meta'];
        $what = $params['extension_row_type'];
        $row_type = $params['row_type'];
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
            $rec = array_map('trim', $rec);
            print_r($rec); //exit("\nstop muna...\n");

            if($what == "measurementorfact") {}

            // ====================================================== stops here...            
            continue; exit("\nshould not go here...\n");
            // ====================================================== stops here...

            $uris = array_keys($rec);
            if($what == "taxon")                    $o = new \eol_schema\Taxon();
            elseif($what == "document")             $o = new \eol_schema\MediaResource();
            elseif($what == "occurrence")           $o = new \eol_schema\Occurrence_specific();
            elseif($what == "measurementorfact")    $o = new \eol_schema\MeasurementOrFact_specific();
            elseif($what == "association")          $o = new \eol_schema\Association();
            elseif($what == "vernacular")           $o = new \eol_schema\VernacularName();
            elseif($what == "agent")                $o = new \eol_schema\Agent();
            else exit("\nERROR: Undefined rowtype[$what].\n");
            
            if($what == "taxon") {
                //----------taxonID must be unique
                $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                
                //----------sciname must not be blank
                if(!$rec['http://rs.tdwg.org/dwc/terms/scientificName']) continue;
                // ====================================== customize per resource:
                if($this->DwCA_Type == 'wikipedia') {
                    if(stripos($rec['http://purl.org/dc/terms/source'], "wikipedia.org") !== false) $rec['http://purl.org/dc/terms/source'] = 'https://www.wikidata.org/wiki/'.$taxon_id; //string is found
                }
                elseif($this->DwCA_Type == 'regular') {} //the rest goes here        
                // ======================================
                if($this->resource_id == "TreatmentBank") {
                    $rec = $this->process_table_TreatmentBank_taxon($rec); //new Dec 12, 2023
                    if(!$rec) continue;

                    // /* Missing taxa GitHub #13
                    // There are 7393 taxa with trait data that are not represented in the taxon file. See missingTaxonIDs.txt attached. 
                    // If we cannot get the taxonomic data for these records, we should remove them.
                    $this->TB_taxon_ids[$taxon_id] = '';
                    // */
                }
                // ======================================    
            } //end $what == 'taxon'

            if($what == "document") {
                $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];

                if($this->resource_id == "TreatmentBank") {
                    if(!isset($this->TB_taxon_ids[$taxon_id])) continue; //to avoid creating media objects without taxon entry. per Github #13
                    $rec = $this->process_table_TreatmentBank_document($rec, $row_type, $meta, $this->zip_file); //new Dec 12, 2023
                    if(!$rec) continue;
                }

                //identifier must be unique
                $identifier = $rec['http://purl.org/dc/terms/identifier'];
                if(!isset($this->object_ids[$identifier])) $this->object_ids[$identifier] = '';
                else continue;
            }
            elseif($what == "taxon") {
                //identifier must be unique
                $identifier = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(!isset($this->taxon_ids[$identifier])) $this->taxon_ids[$identifier] = '';
                else continue;
            }
            elseif($what == "agent") {
                //identifier must be unique
                $identifier = $rec['http://purl.org/dc/terms/identifier'];
                if(!isset($this->agent_ids[$identifier])) $this->agent_ids[$identifier] = '';
                else continue;
            }
            elseif($what == "vernacular") {
                //row must be unique
                $identifier = $rec['http://rs.tdwg.org/dwc/terms/vernacularName']."|".$rec['http://purl.org/dc/terms/language']."|".$rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $identifier = md5($identifier);
                if(!isset($this->vernacular_ids[$identifier])) $this->vernacular_ids[$identifier] = '';
                else continue;
            }

            /* Investigation only --- works OK
            if($what == "taxon") {
                if($rec['http://rs.tdwg.org/dwc/terms/scientificName'] == "Plicatura faginea") {
                    echo "\n--- START Investigate ---\n";
                    print_r($rec); print_r($meta);
                    echo "\n--- END Investigate ---\n";
                }
            }
            */
            
                        
            $uris = array_keys($rec); // print_r($uris);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                /* good debug
                echo "\n[$field][$uri]\n";
                if($field == "vernacularName" && $uri == "http://rs.tdwg.org/dwc/terms/vernacularName") {
                    if(!$rec[$uri]) continue;
                }
                */
                
                // /* some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];
                // */
                
                $o->$field = $rec[$uri];
            }
            
            $this->archive_builder->write_object_to_file($o);

            if($i >= 2) break; //debug only
        } //end foreach()
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
}
?>