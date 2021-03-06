<?php
namespace php_active_record;
/* connector: [COL_data.php]
*/
class CoLDataAPI
{
    function __construct($folder = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->taxa_ref_ids = array();
        $this->page['download_page'] = "http://www.catalogueoflife.org/DCA_Export/archive.php";
        $this->download_options = array('resource_id' => 'CoL', 'expire_seconds' => 60*60*24, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1); //expires in 1 day
        /* you can delete old cache dump files in /cache/CoL/ folder -- one used in extract_archive_file below. That is to free up space */
        $this->debug = array();
        $this->extensions = array('taxa'            => "http://rs.tdwg.org/dwc/terms/Taxon",
                                  'distribution'    => "http://rs.gbif.org/terms/1.0/Distribution",
                                  'description'     => "http://rs.gbif.org/terms/1.0/Description",
                                  'reference'       => "http://rs.gbif.org/terms/1.0/Reference",
                                  'speciesprofile'  => "http://rs.gbif.org/terms/1.0/SpeciesProfile",
                                  'vernacular'      => "http://rs.gbif.org/terms/1.0/VernacularName");
    }
    function convert_archive()
    {
        /* tests
        $dwca = self::compute_for_dwca_file();
        echo "\n[$dwca]\n";
        exit("\n-end tests-\n");
        */
        
        $file = CONTENT_RESOURCE_LOCAL_PATH . "col_debug.txt";
        if(file_exists($file)) unlink($file);
        
        if(!($info = self::prepare_dwca())) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        echo "\nConverting COL archive to EOL DwCA...\n";
        /* this is memory-intensive: $harvester->process_row_type($table);
        foreach($tables as $table) {
            $records = $harvester->process_row_type($table);
        }*/
        foreach($tables as $key => $values) {
            $tbl = $values[0];
            $items[$tbl->row_type] = $tbl->file_uri;
        }
        print_r($items);
        self::process_file($items[$this->extensions['reference']], 'reference');
        self::process_file($items[$this->extensions['taxa']], 'taxa'); //taxa and measurements
        unset($this->taxon_reference_ids); //release memory

        //ends all measurements
        unset($this->occurrence_ids);
        unset($this->measurement_ids);

        $mappings = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        
        self::process_file($items[$this->extensions['distribution']], 'distribution'); //measurements
        unset($this->uris); //release memory

        //ends all measurements
        unset($this->occurrence_ids);
        unset($this->measurement_ids);
        
        self::process_file($items[$this->extensions['speciesprofile']], 'speciesprofile'); //measurements
        
        //ends all measurements
        unset($this->occurrence_ids);
        unset($this->measurement_ids);
        
        /* v1
        $taxa_desc_list = self::process_file($items[$this->extensions['description']], 'description'); //media
        self::create_media_archive($taxa_desc_list);
        unset($taxa_desc_list); //release memory
        */
        
        // /* v2
        self::process_file($items[$this->extensions['description']], 'description_v2'); //media
        self::create_media_archive_v2();
        unset($this->id_list); //release memory
        $temp_path = CONTENT_RESOURCE_LOCAL_PATH . "COL_temp/";
        if(is_dir($temp_path)) recursive_rmdir($temp_path);
        // */
        
        unset($this->taxon_info); //release memory
        
        $this->languages = self::get_languages();
        self::process_file($items[$this->extensions['vernacular']], 'vernacular');
        unset($this->languages);
        
        $this->archive_builder->finalize(TRUE);
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function process_file($txt_file, $extension)
    {
        if($extension == "description") $taxa_desc_list = array();
        if($extension == "description_v2") {
            $temp_path = CONTENT_RESOURCE_LOCAL_PATH . "COL_temp/";
            if(is_dir($temp_path)) recursive_rmdir($temp_path);
            mkdir($temp_path);
            $this->id_list = array();
        }
        
        $i = 0; echo "\nProcessing $extension...\n";
        foreach(new FileIterator($txt_file) as $line_number => $line) {
            $line = Functions::remove_utf8_bom($line);
            $i++; if(($i % 50000) == 0) echo "\n".number_format($i)." $extension ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
            }
            else {
                $k = -1;
                $rec = array();
                foreach($fields as $field) {
                    $k++;
                    $rec[$field] = @$row[$k];
                }
                $rec = array_map('trim', $rec);
                if($extension == "taxa") {
                    self::process_taxon($rec);
                    // if($rec['datasetID'] == "29") break; //debug
                }
                elseif($extension == "distribution")    self::process_distribution($rec);
                elseif($extension == "description")     $taxa_desc_list = self::process_description($rec, $taxa_desc_list);
                elseif($extension == "description_v2")  self::process_description_v2($rec, $temp_path);
                elseif($extension == "reference")       self::process_reference($rec);
                elseif($extension == "speciesprofile")  self::process_speciesprofile($rec);
                elseif($extension == "vernacular")      self::process_vernacular($rec);
                // if($i >= 5000) break; //debug
            }
        }
        echo "\ntotal records $extension: $i\n";
        if($extension == "description") return $taxa_desc_list;
        // if($extension == "description_v2") return $id_list; --- obsolete
    }
    private function process_vernacular($a)
    {   /*
        http://rs.tdwg.org/dwc/terms/vernacularName	vernacular	http://rs.tdwg.org/dwc/terms/vernacularName
        http://purl.org/dc/terms/language	vernacular	http://purl.org/dc/terms/language
        http://rs.tdwg.org/dwc/terms/locality	vernacular	http://rs.tdwg.org/dwc/terms/locality
        Array(
            [﻿taxonID] => 316443
            [vernacularName] => Chile eriococcin
            [language] => English
            [countryCode] => 
            [locality] => 
            [transliteration] => 
        )*/
        if($val = $a['vernacularName']) {
            $v = new \eol_schema\VernacularName();
            $v->taxonID         = $a['taxonID'];
            $v->vernacularName  = $val;
            $v->language        = @$this->languages[$a['language']];
            if($v->language == "omit vernacular record") return;
            $v->locality        = $a['locality'];
            $this->archive_builder->write_object_to_file($v);
            
            //for stats only
            if($language = $a['language']) {
                if(!@$this->languages[$language]) $this->debug['und lang'][$language] = '';
            }
            // else $this->debug['blank lang'][$v->vernacularName] = ''; No need to monitor this since partner didn't actually put any language values for these comnames
        }
    }
    private function process_description($a, $final)
    {   /*Array(
            [﻿taxonID] => 316423
            [description] => Brazil
        )*/
        if($val = $a['description']) $final[$a['taxonID']][$val] = '';
        return $final;
    }
    private function process_description_v2($a, $temp_path)
    {
        if($taxonID = $a['taxonID']) {
            /* ver.1
            $folder = substr($taxonID, -2); //But if taxonID is less than 2 characters, $folder will be empty...seems
            if(!$folder) exit("\nsomething wrong with taxonID [$taxonID] - ".$a['taxonID']."\n");
            $curr_folder = $temp_path.$folder;
            if(!is_dir($curr_folder)) mkdir($curr_folder);
            $file = $curr_folder."/".$taxonID.".txt";
            */
            // /* ver.2
            $md5 = md5($taxonID);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            if(!file_exists($temp_path . $cache1))           mkdir($temp_path . $cache1);
            if(!file_exists($temp_path . "$cache1/$cache2")) mkdir($temp_path . "$cache1/$cache2");
            $file = $temp_path . "$cache1/$cache2/".$taxonID.".txt";
            // */
            
            $WRITE = Functions::file_open($file, "a");
            fwrite($WRITE, $a['description']."\n");
            fclose($WRITE);
            $this->id_list[$taxonID] = '';
        }
    }
    private function create_media_archive_v2()
    {
        $temp_path = CONTENT_RESOURCE_LOCAL_PATH . "COL_temp/";
        foreach(array_keys($this->id_list) as $taxonID) {
            /* ver.1
            $folder = substr($taxonID, -2);
            $curr_folder = $temp_path.$folder;
            $file = $curr_folder."/".$taxonID.".txt";
            */
            // /* ver.2
            $md5 = md5($taxonID);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            $file = $temp_path . "$cache1/$cache2/".$taxonID.".txt";
            // */
            $contents = file_get_contents($file);
            $arr = explode("\n", $contents);
            $arr = array_map('trim', $arr);
            
            $final = array();
            foreach($arr as $country) {
                if($val = trim($country)) $final[$val] = '';
            }
            $desc = implode("; ", array_keys($final));
            //start exactly same as below:
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $taxonID;
            $mr->identifier     = md5($taxonID.$desc);
            $mr->description    = $desc;
            $mr->language       = 'eng';
            $mr->type           = 'http://purl.org/dc/dcmitype/Text';
            $mr->format         = 'text/html';
            $mr->CVterm         = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution';
            $mr->furtherInformationURL = @$this->taxon_info[$taxonID]['url'];
            $mr->audience       = 'Everyone';
            $mr->UsageTerms     = 'No known copyright restrictions';
            $mr->contributor    = @$this->taxon_info[$taxonID]['dsN'];
            // if(!isset($this->object_ids[$mr->identifier])) {
            //     $this->archive_builder->write_object_to_file($mr);
            //     $this->object_ids[$mr->identifier] = '';
            // }
            $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function create_media_archive($taxa_desc_list)
    {
        foreach($taxa_desc_list as $taxonID => $descriptions) {
            $desc = array_keys($descriptions);
            asort($desc);
            $desc = implode("; ", $desc);
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $taxonID;
            $mr->identifier     = md5($taxonID.$desc);
            $mr->description    = $desc;
            $mr->language       = 'eng';
            $mr->type           = 'http://purl.org/dc/dcmitype/Text';
            $mr->format         = 'text/html';
            $mr->CVterm         = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution';
            $mr->furtherInformationURL = @$this->taxon_info[$taxonID]['url'];
            $mr->audience       = 'Everyone';
            $mr->UsageTerms     = 'No known copyright restrictions';
            $mr->contributor    = @$this->taxon_info[$taxonID]['dsN'];
            // if(!isset($this->object_ids[$mr->identifier])) {
            //     $this->archive_builder->write_object_to_file($mr);
            //     $this->object_ids[$mr->identifier] = '';
            // }
            $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function process_speciesprofile($a)
    {   /*
        http://rs.tdwg.org/dwc/terms/measurementType	http://purl.obolibrary.org/obo/RO_0002303	if CoL value is terrestrial
        http://rs.tdwg.org/dwc/terms/measurementType	http://purl.obolibrary.org/obo/RO_0002303	if CoL value is freshwater
        http://rs.tdwg.org/dwc/terms/measurementType	http://purl.obolibrary.org/obo/RO_0002303	if CoL value is brackish
        http://rs.tdwg.org/dwc/terms/measurementType	http://purl.obolibrary.org/obo/RO_0002303	if CoL value is marine
        Array(
            [﻿taxonID] => 9237970
            [habitat] => terrestrial
        )
        */
        if($habitat = $a['habitat']) {
            $arr['terrestrial'] = "http://purl.obolibrary.org/obo/ENVO_00002009";
            $arr['freshwater']  = "http://purl.obolibrary.org/obo/ENVO_00002037";
            $arr['brackish']    = "http://purl.obolibrary.org/obo/ENVO_00000570";
            $arr['marine']      = "http://purl.obolibrary.org/obo/ENVO_00000569";
            if($val = @$arr[$habitat]) {
                $rec = array();
                $rec["taxon_id"]            = $a['taxonID'];
                $rec["catnum"]              = $a['taxonID']."Habitat";
                $rec['measurementOfTaxon']  = "true";
                $rec['measurementType']     = "http://purl.obolibrary.org/obo/RO_0002303";
                $rec["source"]              = @$this->taxon_info[$a['taxonID']]['url'];
                $rec['measurementValue']    = $val;
                self::add_string_types($rec);
            }
        }
    }
    private function process_distribution($a)
    {
        /* Array (
            [﻿taxonID] => 316424
            [locationID] => TDWG:GER-OO
            [locality] => Germany
            [occurrenceStatus] => 
            [establishmentMeans] => 
        )
        http://rs.tdwg.org/dwc/terms/locality	http://rs.tdwg.org/dwc/terms/measurementType	http://eol.org/schema/terms/Present
            We should translate their place names into URIs. Most of the countries & economic zones should be represented in the known uris (http://beta-repo.eol.org/terms/).  
            Let me know if there are any missing.	Use Present if establishmentMeans is empty
        http://rs.tdwg.org/dwc/terms/locality	http://rs.tdwg.org/dwc/terms/measurementType	http://eol.org/schema/terms/NativeRange
            Use NativeRange if establishmentMeans is native
        http://rs.tdwg.org/dwc/terms/locality	http://rs.tdwg.org/dwc/terms/measurementType	http://eol.org/schema/terms/IntroducedRange
            Use IntroducedRange if establishmentMeans is alien
        http://rs.tdwg.org/dwc/terms/locationID	http://rs.tdwg.org/dwc/terms/measurementType	http://rs.tdwg.org/dwc/terms/locationID	verbatim
            The locationID measurements should be child records of the Present /NativeRange/IntroducedRange measurements. Most of these are TDWG codes, 
            but there are also some others. I don't think they are widely used anymore, but we may as well expose them.
        */
        if($locality = $a['locality']) {
            if($locality_uri = @$this->uris[$locality]) {
                // echo "\n found URI [$locality][$locality_uri]";
                $locationID = $a['locationID'];
                if(!$a['establishmentMeans']) {
                    $rec = array();
                    $rec["taxon_id"]            = $a['taxonID'];
                    $rec["catnum"]              = $a['taxonID']."Present";
                    $rec['measurementOfTaxon']  = "true";
                    $rec['measurementType']     = "http://eol.org/schema/terms/Present";
                    $rec["source"]              = @$this->taxon_info[$a['taxonID']]['url'];
                    $rec['measurementValue']    = $locality_uri;
                    self::add_string_types($rec);
                    if($locationID) {
                        $rec = array();
                        $rec["taxon_id"]            = $a['taxonID'];
                        $rec["catnum"]              = $a['taxonID']."Present";
                        $rec['measurementOfTaxon']  = "false";
                        $rec['measurementType']     = "http://rs.tdwg.org/dwc/terms/locationID";
                        $rec['measurementValue']    = $locationID;
                        self::add_string_types($rec);
                    }
                }
                elseif($a['establishmentMeans'] == "native") {
                    $rec = array();
                    $rec["taxon_id"]            = $a['taxonID'];
                    $rec["catnum"]              = $a['taxonID']."NativeRange";
                    $rec['measurementOfTaxon']  = "true";
                    $rec['measurementType']     = "http://eol.org/schema/terms/NativeRange";
                    $rec["source"]              = @$this->taxon_info[$a['taxonID']]['url'];
                    $rec['measurementValue']    = $locality_uri;
                    self::add_string_types($rec);
                    if($locationID) {
                        $rec = array();
                        $rec["taxon_id"]            = $a['taxonID'];
                        $rec["catnum"]              = $a['taxonID']."NativeRange";
                        $rec['measurementOfTaxon']  = "false";
                        $rec['measurementType']     = "http://rs.tdwg.org/dwc/terms/locationID";
                        $rec['measurementValue']    = $locationID;
                        self::add_string_types($rec);
                    }
                }
                elseif($a['establishmentMeans'] == "alien") {
                    $rec = array();
                    $rec["taxon_id"]            = $a['taxonID'];
                    $rec["catnum"]              = $a['taxonID']."IntroducedRange";
                    $rec['measurementOfTaxon']  = "true";
                    $rec['measurementType']     = "http://eol.org/schema/terms/IntroducedRange";
                    $rec["source"]              = @$this->taxon_info[$a['taxonID']]['url'];
                    $rec['measurementValue']    = $locality_uri;
                    self::add_string_types($rec);
                    if($locationID) {
                        $rec = array();
                        $rec["taxon_id"]            = $a['taxonID'];
                        $rec["catnum"]              = $a['taxonID']."IntroducedRange";
                        $rec['measurementOfTaxon']  = "false";
                        $rec['measurementType']     = "http://rs.tdwg.org/dwc/terms/locationID";
                        $rec['measurementValue']    = $locationID;
                        self::add_string_types($rec);
                    }
                }
            }
            else $this->debug['undef locality'][$locality] = '';
        }
        
    }
    private function process_reference($a)
    {
        /*
        -	references	http://purl.org/dc/terms/identifier
        http://purl.org/dc/terms/creator	references	http://eol.org/schema/reference/full_reference
        http://purl.org/dc/terms/date	references	http://eol.org/schema/reference/full_reference
        http://purl.org/dc/terms/title	references	http://eol.org/schema/reference/full_reference
        http://purl.org/dc/terms/source	references	http://eol.org/schema/reference/full_reference
        Array(
            [﻿taxonID] => 316423
            [creator] => Lepage, H.S.
            [date] => 1938
            [title] => [Catalog of coccids from Brazil.] Catálogo dos coccídeos do Brasil.
            [description] => Revista do Museu Paulista. São Paulo
            [identifier] => 
            [type] => taxon
        )
        Give each reference a unique ID and link these IDs to relevant taxa through the referenceID field in the taxa file.
        Concatenate creator, date, title, and source (description) into the full-reference field.
        */
        $r = new \eol_schema\Reference();
        $r->full_reference = self::format_full_ref($a);
        if(!$r->full_reference) return; //discovered there is a record with blank 'creator', 'date', 'title', 'description'.
        $r->identifier = md5($r->full_reference);
        // $r->uri = ''
        
        $this->taxon_reference_ids[$a['taxonID']][$r->identifier] = ''; //it has to be here. Coz a sigle reference maybe assigned to multiple taxa.
        
        if(!isset($this->reference_ids[$r->identifier])) {
            $this->reference_ids[$r->identifier] = ''; 
            $this->archive_builder->write_object_to_file($r);
        }
    }
    private function format_full_ref($a)
    {
        $final = "";
        if($val = $a['creator'])     $final .= "$val. ";
        if($val = $a['date'])        $final .= "$val. ";
        if($val = $a['title'])       $final .= "$val. ";
        if($val = $a['description']) $final .= "$val. ";
        // $final .= " --- $a[taxonID] "; //debug only - will comment in normal operation
        return trim($final);
    }
    private function process_taxon($a)
    {
        if(!@$a['taxonID']) return;
        if(!@$a['scientificName']) return;
        
        $this->taxon_info[$a['taxonID']]['url'] = $a['references'];
        $this->taxon_info[$a['taxonID']]['dsN'] = $a['datasetName'];
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $a['taxonID'];
        $taxon->datasetID           = $a['datasetID'];
        $taxon->datasetName         = $a['datasetName'];
        $taxon->acceptedNameUsageID = $a['acceptedNameUsageID'];
        $taxon->parentNameUsageID   = $a['parentNameUsageID'];
        $taxon->taxonomicStatus     = $a['taxonomicStatus'];
        $taxon->taxonRank           = self::format_taxonRank($a);
        $taxon->scientificName      = $a['scientificName'];
        $taxon->kingdom             = $a['kingdom'];
        $taxon->phylum              = $a['phylum'];
        $taxon->class               = $a['class'];
        $taxon->order               = $a['order'];
        // $taxon->superfamily         = $a['superfamily'];
        $taxon->family              = $a['family'];
        // $taxon->genericName         = $a['genericName'];
        $taxon->genus               = $a['genus'];
        $taxon->subgenus            = $a['subgenus'];
        $taxon->specificEpithet     = $a['specificEpithet'];
        $taxon->infraspecificEpithet        = $a['infraspecificEpithet'];
        $taxon->scientificNameAuthorship    = $a['scientificNameAuthorship'];
        $taxon->nameAccordingTo     = $a['nameAccordingTo'];
        $taxon->modified            = $a['modified'];
        $taxon->taxonRemarks        = self::format_taxonRemarks($a);
        $taxon->scientificNameID    = $a['scientificNameID'];
        $taxon->furtherInformationURL   = $a['references'];
        if($reference_ids = @$this->taxon_reference_ids[$a['taxonID']]) $taxon->referenceID = implode("; ", array_keys($reference_ids));
        /*
        if(isset($this->taxon_ids[$taxon->taxonID])) return;
        $this->taxon_ids[$taxon->taxonID] = '';
        */
        $this->archive_builder->write_object_to_file($taxon);
        /* Processing taxa...
        Array(
            [﻿taxonID] => 316502
            [identifier] => 
            [datasetID] => 26
            [datasetName] => ScaleNet in Species 2000 & ITIS Catalogue of Life: 28th March 2018
            [acceptedNameUsageID] => 316423
            [parentNameUsageID] => 
            [taxonomicStatus] => synonym
            [taxonRank] => species
            [verbatimTaxonRank] => 
            [scientificName] => Canceraspis brasiliensis Hempel, 1934
            [kingdom] => Animalia
            [phylum] => 
            [class] => 
            [order] => 
            [superfamily] => 
            [family] => 
            [genericName] => Canceraspis
            [genus] => Limacoccus
            [subgenus] => 
            [specificEpithet] => brasiliensis
            [infraspecificEpithet] => 
            [scientificNameAuthorship] => Hempel, 1934
            [source] => 
            [namePublishedIn] => 
            [nameAccordingTo] => 
            [modified] => 
            [description] => 
            [taxonConceptID] => 
            [scientificNameID] => Coc-100-7
            [references] => http://www.catalogueoflife.org/annual-checklist/2015/details/species/id/6a3ba2fef8659ce9708106356d875285/synonym/3eb3b75ad13a5d0fbd1b22fa1074adc0
            [isExtinct] => 
        )*/
        
        if($isExtinct = $a['isExtinct']) {
            $rec = array();
            $rec["taxon_id"]            = $a['taxonID'];
            $rec["catnum"]              = $a['taxonID']."ExtinctionStatus";
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/ExtinctionStatus";
            $rec["source"]              = $a['references'];
            if($isExtinct == "true" || $isExtinct === true) $rec['measurementValue'] = 'http://eol.org/schema/terms/extinct';
            if($isExtinct == "false" || $isExtinct === false) $rec['measurementValue'] = 'http://eol.org/schema/terms/extant';
            self::add_string_types($rec);
        }
        if($a['datasetID'] == "29") {
            $rec = array();
            $rec["taxon_id"]            = $a['taxonID'];
            $rec["catnum"]              = $a['taxonID']."TaxonIdProvider";
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/TaxonIdProvider";
            $rec["source"]              = $a['references'];
            $rec['measurementValue']    = 'https://www.wikidata.org/wiki/Q3570011';
            self::add_string_types($rec);
            //child measurement:
            if($val = $a['taxonConceptID']) {
                $rec = array();
                $rec["taxon_id"]            = $a['taxonID'];
                $rec["catnum"]              = $a['taxonID']."TaxonIdProvider";
                $rec['measurementOfTaxon']  = "false";
                $rec['measurementType']     = "http://purl.org/dc/terms/identifier";
                // $rec["source"]              = $a['references'];
                $rec['measurementValue']    = $val;
                self::add_string_types($rec);
            }
        }
    }
    private function add_string_types($rec)
    {
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"], $rec);
        unset($rec['catnum']);
        unset($rec['taxon_id']);
        
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID = $occurrence_id;
        foreach($rec as $key => $value) $m->$key = $value;
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }

    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        // if($val = @$rec['lifestage']) $o->lifeStage = $val; -- nothing from COL, just copied from another resource
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }

    private function format_taxonRank($a)
    {
        /* if there is a value for verbatimTaxonRank, this should take precedence over the CoL taxonRank value, except in cases where verbatimTaxonRank=aberration.
        For aberrations keep the CoL TaxonRank value. */
        if($val = $a['verbatimTaxonRank']) {
            if($val != 'aberration' && $val) return $val;
        }
        return $a['taxonRank'];
    }
    private function format_taxonRemarks($a)
    {
        /* Omit remarks if datasetID is one of the following: 15,21,45,50,134,174,190,199; These are not really taxonomic remarks. */
        $datasetIDs_2omit = array(15,21,45,50,134,174,190,199);
        if(!in_array($a['datasetID'], $datasetIDs_2omit)) return $a['description'];
    }
    private function compute_for_dwca_file()
    {
        if(Functions::is_production()) {
            if($html = Functions::lookup_with_cache($this->page['download_page'], $this->download_options)) {
                if(preg_match("/Monthly editions(.*?)<\/ul>/ims", $html, $a)) {
                    if(preg_match("/href=\"(.*?)\"/ims", $a[1], $a2)) {
                        $final = "http://www.catalogueoflife.org/DCA_Export/".$a2[1];
                        echo "\nDownloading [$final] ...\n";
                        return $final;
                    }
                }
            }
        }
        else return "http://localhost/cp/COL/2018-03-28-archive-complete.zip";
        // return "http://www.catalogueoflife.org/DCA_Export/zip-fixed/2018-03-28-archive-complete.zip";
    }
    private function prepare_dwca()
    {
        $dwca = self::compute_for_dwca_file();
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca, "meta.xml", array('timeout' => 172800, 'expire_seconds' => false, 'cache' => 1)); //does not expire bec. these are dump files already - won't change
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
    private function get_languages() //sheet found here: https://eol-jira.bibalex.org/browse/DATA-1744
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '19nQkPuuCB9lhQEoOByfdP0-Uwwhn5Y_uTu4zs_SVANI';
        $params['range']         = 'languages!A2:B451'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = $item[1];
        return $final;
    }
}
?>
