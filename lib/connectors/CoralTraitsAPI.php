<?php
namespace php_active_record;
/* connector: [coral_traits.php] */
class CoralTraitsAPI
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
            'expire_seconds'     => false, //expires set to false since update is as-needed basis. Partner will inform us when they have a new release (DATA-1793)
            'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        // $this->download_options['expire_seconds'] = 0; //debug only
        $this->partner_source_csv = "https://ndownloader.figshare.com/files/3678603";
        $this->download_version = "ctdb_1.1.1";
        $this->spreadsheet_for_mapping  = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Coraltraits/coraltraits_mapping.xlsx"; //from Jen (DATA-1793)
        $this->spreadsheet_for_mapping2 = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Coraltraits/coraltraits_mapping_Eli.xlsx";
        
        $this->source                = "https://coraltraits.org/species/";
        $this->bibliographicCitation = "Madin, Joshua (2016): Coral Trait Database 1.1.1. figshare. Dataset. https://doi.org/10.6084/m9.figshare.2067414.v1";
        //new
        $this->additional_mapping = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Coraltraits/18Apr2019/adjust_coraltraits_mapping.xlsx';
    }
    /* ========================================= START additional task ======================================= */
    private function get_addtl_mapping()
    {
        $sheet_no = 'Sheet1';
        $options = $this->download_options;
        $options['file_extension'] = 'xlsx';
        // $options['expire_seconds'] = 0; //debug only
        $local_xls = Functions::save_remote_file_to_local($this->additional_mapping, $options);
        require_library('XLSParser'); $parser = new XLSParser();
        debug("\n reading: " . $local_xls . "\n");
        $map = $parser->convert_sheet_to_array($local_xls);
        $fields = array_keys($map);
        // print_r($map); print_r($fields); exit;
        // foreach($fields as $field) echo "\n$field: ".count($map[$field]); //debug only
        $i = -1; $final = array();
        foreach($map[$fields[0]] as $var) {
            $i++; $rec = array();
            foreach($fields as $fld) $rec[$fld] = $map[$fld][$i];
            $final[$rec[$fields[0]]][] = $rec;
        }
        unlink($local_xls); // print_r($final); exit;
        return $final;
    }
    private function implement_addtl_mapping($rek)
    {
        // /* per Jen: https://eol-jira.bibalex.org/browse/DATA-1793?focusedCommentId=64852&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-64852
        if($rek['measurementValue'] == 'http://purl.obolibrary.org/obo/ENVO_01000049') $rek['measurementValue'] = 'http://purl.obolibrary.org/obo/ENVO_01000029';
        // */
        
        // print_r($rek);
        /* $rek e.g. Array(
            [taxon_id] => 1269
            [catnum] => 
            [occur] => Array(
                    [occurrenceID] => 
                )
            [parentMeasurementID] => 4e2ebd9e94d0375cc658222379528e0a_cotr
            [statisticalMethod] => http://www.ebi.ac.uk/efo/EFO_0001444
            [measurementUnit] => 
            [measurementRemarks] => 
            [measurementMethod] => (raw_value)
            [measurementType] => http://rs.tdwg.org/dwc/terms/eventDate
            [measurementValue] => Jan-May
        )
        */
        /* $rek e.g. Array(
            [occur] => Array(
                    [taxonID] => 274
                    [occurrenceID] => 17667
                    [locality] => Global estimate
                    [decimalLatitude] => 
                    [decimalLongitude] => 
                )
            [statisticalMethod] => 
            [lifeStage] => 
            [bodyPart] => 
            [measurementRemarks] => 
            [locality] => 
            [GO_0007626] => 
            [NCIT_C70589] => 
            [measurementUnit] => 
            [SampleSize] => 
            [taxon_id] => 274
            [catnum] => _http://www.wikidata.org/entity/Q50621879
            [measurementMethod] => Derived from textual description (expert_opinion)
            [referenceID] => 40
            [measurementType] => http://eol.org/schema/terms/TrophicGuild
            [measurementValue] => http://www.wikidata.org/entity/Q50621879
            [source] => https://coraltraits.org/species/274
            [bibliographicCitation] => Madin, Joshua (2016): Coral Trait Database 1.1.1. figshare. Dataset. https://doi.org/10.6084/m9.figshare.2067414.v1
        )
        e.g. $this->addtl[measurementType]
        Array(
            [http://eol.org/schema/terms/coralliteWidth] => Array(
                    [0] => Array(
                            [measurementType] => http://eol.org/schema/terms/coralliteWidth
                            [measurementMethod] => 
                            [measurementUnit] => 
                            [THEN] => THEN
                            [correct measurementType] => 
                            [correct statisticalMethod] => 
                            [add lifeStage] => 
                            [add bodyPart] => https://www.wikidata.org/entity/Q689884
                        )
                )
        */
        if($records = @$this->addtl[$rek['measurementType']]) {
            foreach($records as $record) {
                $record = array_map('trim', $record);
                /* blank means CRITERIA IS VALUE SHOULD BE BLANK AS WELL
                if($rek['measurementType'] == $record['measurementType'] && 
                   $rek['measurementMethod'] == $record['measurementMethod'] && 
                   $rek['measurementUnit'] == $record['measurementUnit']) {
                       if($val = $record['correct measurementType']) $rek['measurementType'] = $val;
                       if($val = $record['correct statisticalMethod']) $rek['statisticalMethod'] = $val;
                       if($val = $record['add lifeStage']) $rek['lifeStage'] = $val;
                       if($val = $record['add bodyPart']) $rek['bodyPart'] = $val;
                       @$this->debug['addtl event']++;
                       return $rek;
                }
                */
                if($rek['measurementType'] == $record['measurementType']) { //blank means CRITERIA IS ANY VALUE INCLUDING BLANK
                    if($val = $record['measurementMethod']) {
                        if($rek['measurementMethod'] != $val) continue;
                    }
                    if($val = $record['measurementUnit']) {
                        if($rek['measurementUnit'] != $val) continue;
                    }
                    if($val = $record['correct measurementType']) $rek['measurementType'] = $val;
                    if($val = $record['correct statisticalMethod']) $rek['statisticalMethod'] = $val;
                    if($val = $record['add lifeStage']) $rek['lifeStage'] = $val;
                    if($val = $record['add bodyPart']) $rek['bodyPart'] = $val;
                    @$this->debug['addtl event']++;
                    return $rek;
                }
            } //end foreach
        }
        return $rek;
    }
    /* ========================================= END additional task ======================================= */
    function start()
    {
        // /* From: https://eol-jira.bibalex.org/browse/DATA-1793?focusedCommentId=63392&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63392
        $this->addtl = self::get_addtl_mapping(); // print_r($addtl);
        // */
        
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        $expire_seconds = 1; //just to be sure it doesn't use cache. It will always re-download latest copy.
        $this->func->initialize_terms_remapping($expire_seconds);
        /* END DATA-1841 terms remapping */
        echo "\nFrom local: ".count($this->func->remapped_terms)."\n";
        
        self::load_zip_contents();
        $this->meta['trait_name'] = self::initialize_spreadsheet_mapping('trait_name'); // print_r($this->meta['trait_name']['Zooxanthellate']); exit;

        $temp1 = $this->meta['value'] = self::initialize_spreadsheet_mapping('value');           // print_r($this->meta['value']['Winter']); exit;
        $temp2 = $this->meta['value'] = self::initialize_spreadsheet_mapping('value', $this->spreadsheet_for_mapping2);
        $this->meta['value'] = array_merge($temp1, $temp2);
        echo "\n".count($temp1)."\n";
        echo "\n".count($temp2)."\n";
        echo "\ntotal [value]: ".count($this->meta['value'])."\n";
        
        $temp1 = self::initialize_spreadsheet_mapping('unit');
        $temp2 = self::initialize_spreadsheet_mapping('unit', $this->spreadsheet_for_mapping2);
        $this->meta['standard_unit'] = array_merge($temp1, $temp2);
        echo "\n".count($temp1)."\n";
        echo "\n".count($temp2)."\n";
        echo "\ntotal [standard_unit]: ".count($this->meta['standard_unit'])."\n";
        // print_r($this->meta['standard_unit']); exit("\n");

        self::process_csv('resources'); //this will initialize $this->refs
        
        $this->sought_trait_class = 'non contextual';   self::process_csv('data'); //this is the main csv file
        // print_r($this->OM_ids);
        $this->sought_trait_class = 'contextual';       self::process_csv('data'); //this is the main csv file
        
        //remove temp folder and file
        recursive_rmdir($this->TEMP_FILE_PATH); // remove temp dir
        echo ("\n temporary directory removed: [$this->TEMP_FILE_PATH]\n");
        
        $this->archive_builder->finalize(true);
        print_r($this->debug);
        Functions::start_print_debug($this->debug, $this->resource_id);
        // exit("\n-stop muna\n");
    }
    private function process_csv($type)
    {
        $i = 0;
        /* works ok if you don't need to format/clean the entire row.
        $file = Functions::file_open($this->text_path[$type], "r");
        while(!feof($file)) { $row = fgetcsv($file); }
        fclose($file);
        */
        foreach(new FileIterator($this->text_path[$type]) as $line_number => $line) {
            if(!$line) continue;
            $line = str_replace('\flume\"""', 'flume"', $line); //clean entire row. fix for 'data' csv
            $row = str_getcsv($line);
            /* good debug
            if(stripos($line, "Light extinction coefficient") !== false) { //string is found
                print_r($row);
            }
            */
            if(!$row) continue; //continue; or break; --- should work fine
            $row = self::clean_html($row); // print_r($row);
            $i++; if(($i % 10000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    echo "\n--------------\n"; print_r($values);
                    echo("\nWrong CSV format for this row.\n"); exit;
                    @$this->debug['wrong csv'][$type]++;
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                /* good debug
                if(stripos($line, "Light extinction coefficient") !== false) { //string is found
                    print_r($rec); $this->debug['monitor'] = true;
                }
                */
                // print_r($rec); exit;
                if($type == "data")          self::process_data_record($rec);
                elseif($type == "resources") self::initialize_resources_record($rec);
                
            } //main records
            // if($i > 5) break;
        } //main loop
        // fclose($file);
    }
    private function process_data_record($rec)
    {
        // $this->debug['trait_class'][$rec['trait_class']] = ''; //debug only
        
        // if($rec['trait_name'] == 'Flow rate') {
        //     $this->debug['trait_class'][$rec['trait_name']][$rec['value']] = ''; //debug only
        // }
        self::create_taxon($rec);
        self::create_trait($rec);
    }
    private function process_contextual($rec)
    {   /*wherever the trait_class=Contextual, that record will be a child measurement of all other (non-Contextual) records for the same occurrence. 
        location_name, latitude and longitude can be ignored for the child records, as they duplicate information that will be on the occurrence record. 
        resource_id and replicates can also be ignored on child records, as they duplicate information on the parent record. 
        You may need to make duplicates of these records for different parents, if multiple parent records exist. 
        
        I think these are the only source columns you'll need to use:
            observation_id: use to locate parent records
            trait_name, value, standard_unit: use as above for regular records
            value_type (only one use): http://eol.org/schema/terms/statisticalMethod
            mapping for values of value_type:
            raw value: http://www.ebi.ac.uk/efo/EFO_0001444
            median: http://semanticscience.org/resource/SIO_001110
            mean: http://semanticscience.org/resource/SIO_001109
            ANYTHING ELSE: discard
            
            methodology_name, precision, precision_type, notes: use as above for regular records. 
        */
        // [159744] => Array(
        //             [0a862c13307ff87d696e412be8e8b62d_coraltraits] => 
        //             [bd1968b80f100e4bc511004ba3355e45_coraltraits] => 
        //             [75b9c1a3d82865c03d66d632c7ac4fa9_coraltraits] => 
        //         )
        $rek = array(); $mType = ''; $mValue = '';
        $occurrence_id = $rec['observation_id'];
        if($parents = @$this->OM_ids[$occurrence_id]) {
            $parents = array_keys($parents);
            /*good debug
            if($occurrence_id == 159744) {
                print_r($parents);
                exit;
            }
            */
            $taxon_id = $rec['specie_id'];
            foreach($parents as $measurementID) { //let us now create child measurements
                $rek = array();
                $rek["taxon_id"] = $taxon_id;
                $rek["catnum"] = ''; //can be blank coz there'll be no occurrence for child measurements anyway.
                $rek['occur']['occurrenceID'] = ''; //child measurements don't have occurrenceID
                $rek['parentMeasurementID'] = $measurementID;
                if($trait_rec = @$this->meta['trait_name'][$rec['trait_name']]) {
                    $mType                     = $trait_rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                    $mValue                    = $trait_rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                    $rek['statisticalMethod']  = $trait_rec['http://eol.org/schema/terms/statisticalMethod'];
                    /* debug only
                    // if($rec['trait_name'] == "Depth lower") { //doesn't exist here, since this part only deals with child records
                    if($val = $rek['statisticalMethod']) { does not go here anyway...
                        print_r($trait_rec);
                        print_r($rec);
                        exit("\nelix2\n");
                    }*/
                }
                if(!$mValue) $mValue = @$this->meta['value'][$rec['value']]['uri'];
                if(!$mValue) $mValue = $rec['value']; //meaning get from source, not URI
                $rek['measurementUnit'] = @$this->meta['standard_unit'][$rec['standard_unit']]['uri'];
                $rek['measurementRemarks'] = $rec['notes'];
                /* http://rs.tdwg.org/dwc/terms/measurementMethod will be concatenated as "methodology_name (value_type)" */
                $rek['measurementMethod'] = self::format_methodology($rec);
                $rek['statisticalMethod'] = self::get_smethod($rec['value_type']);
                $rek = self::implement_precision_cols($rec, $rek);
                // if($ref_ids = self::write_references($rec)) $rek['referenceID'] = implode("; ", $ref_ids);
                $rek['measurementType']  = $mType;
                $rek['measurementValue'] = $mValue;
                $rek = self::run_special_cases($rec, $rek, true); //3rd param contextualYN
                if(!@$rek['measurementType']) return;
                // $rek['source'] = $this->source . $rec['specie_id'];
                // $rek['bibliographicCitation'] = $this->bibliographicCitation;
                
                //manual adjustment, probably data error
                if($rek['measurementType'] == "http://purl.obolibrary.org/obo/PATO_0002243" && !$rek['measurementValue']) return;
                
                // /* debug only
                if(!@$trait_rec)       $this->debug['undef trait']['C'][$rec['trait_name']] = '';     //debug only - Jen might add mappings here
                if(!is_numeric($rec['value'])) {
                    if(!@$this->meta['value'][$rec['value']])             $this->debug['undef value']['C'][$rec['trait_name']][$rec['value']] = '';          //debug only - Jen might add mappings here
                }
                if(!@$this->meta['standard_unit'][$rec['standard_unit']]) $this->debug['undef unit']['C'][$rec['standard_unit']] = '';   //debug only - all 4 found are expected to have blank units
                // */
                
                // /* NEW: additional mapping task from here: https://eol-jira.bibalex.org/browse/DATA-1793?focusedCommentId=63392&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63392
                $rek = self::implement_addtl_mapping($rek);
                // */
                
                $ret_MoT_true = $this->func->pre_add_string_types($rek, $rek['measurementValue'], $rek['measurementType'], "child"); //for child measurement
            }
        }
    }
    private function format_methodology($rec)
    {
        $tmp = '';
        if($val = $rec['methodology_name']) $tmp .= $val;
        if($val = $rec['value_type'])       $tmp .= " ($val)";
        return trim($tmp);
        // "$rec[methodology_name] ($rec[value_type])"
    }
    private function process_non_contextual($rec)
    {
        /* good debug
        // $this->debug['value_type'][$rec['value_type']] = ''; return;
        $this->debug['precision'][$rec['precision']] = ''; 
        $this->debug['precision_type'][$rec['precision_type']] = ''; return;
        */
        
        /*Occurrence file (you'll need to deduplicate):
        specie_id (or whatever): http://rs.tdwg.org/dwc/terms/taxonID
        observation_id: http://rs.tdwg.org/dwc/terms/occurrenceID
        location_name: http://rs.tdwg.org/dwc/terms/locality
        latitude: http://rs.tdwg.org/dwc/terms/decimalLatitude
        longitude: http://rs.tdwg.org/dwc/terms/decimalLongitude
        */
        $rek = array(); $mType = ''; $mValue = '';
        $rek['occur']['taxonID'] = $rec['specie_id'];
        $rek['occur']['occurrenceID'] = $rec['observation_id']; //will duplicate below, but its OK.
        $rek['occur']['locality'] = $rec['location_name'];
        $rek['occur']['decimalLatitude'] = $rec['latitude'];
        $rek['occur']['decimalLongitude'] = $rec['longitude'];
        
        /*
        from spreadsheet mapping for e.g. trait_name == 'Abundance GBR'. Need to assign these pre-defined values as prioritized values
        Array(
            [trait_name] => Abundance GBR
            [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
            [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.geonames.org/2164628
            [http://eol.org/schema/terms/statisticalMethod] => 
            [http://rs.tdwg.org/dwc/terms/lifeStage] => 
            [http://eol.org/schema/terms/bodyPart] => 
            [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
            [http://rs.tdwg.org/dwc/terms/locality] => http://www.geonames.org/2164628
            [http://purl.obolibrary.org/obo/GO_0007626] => 
            [http://purl.obolibrary.org/obo/NCIT_C70589] => SPECIAL CASE
        )*/
        if($trait_rec = @$this->meta['trait_name'][$rec['trait_name']]) {
            $mType                     = $trait_rec['http://rs.tdwg.org/dwc/terms/measurementType'];
            $mValue                    = $trait_rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            $rek['statisticalMethod']  = $trait_rec['http://eol.org/schema/terms/statisticalMethod'];
            $rek['lifeStage']          = $trait_rec['http://rs.tdwg.org/dwc/terms/lifeStage'];
            $rek['bodyPart']           = $trait_rec['http://eol.org/schema/terms/bodyPart'];
            $rek['measurementRemarks'] = $trait_rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'];
            $rek['locality']           = $trait_rec['http://rs.tdwg.org/dwc/terms/locality'];
            $rek['GO_0007626']         = $trait_rec['http://purl.obolibrary.org/obo/GO_0007626'];
            $rek['NCIT_C70589']        = $trait_rec['http://purl.obolibrary.org/obo/NCIT_C70589'];
            /* debug only
            if($rec['trait_name'] == "Depth lower") {
                print_r($trait_rec);
                print_r($rec);
                exit("\nelix1\n");
            }*/
            // /* debug purposes only - useful
            if($val = $rek['statisticalMethod']) $this->debug['trait_nameS with statMethod value'][$rec['trait_name']][$val] = '';
            // */
        }
        // else return;

        /*
        wherever trait_class is NOT "Contextual":
        observation_id: http://rs.tdwg.org/dwc/terms/occurrenceID
        trait_name: http://rs.tdwg.org/dwc/terms/measurementType
        value: http://rs.tdwg.org/dwc/terms/measurementValue
        standard_unit: http://rs.tdwg.org/dwc/terms/measurementUnit
        replicates: http://eol.org/schema/terms/SampleSize
        notes: http://rs.tdwg.org/dwc/terms/measurementRemarks
        */
        $rek['occur']['occurrenceID'] = $rec['observation_id'];
        if(!$mValue) $mValue = @$this->meta['value'][$rec['value']]['uri'];
        if(!$mValue) $mValue = $rec['value']; //meaning get from source, not URI
        $rek['measurementUnit'] = @$this->meta['standard_unit'][$rec['standard_unit']]['uri'];
        $rek['SampleSize'] = $rec['replicates'];
        $rek['measurementRemarks'] = $rec['notes'];

        $rek["taxon_id"] = $rec['specie_id'];
        // if(is_array($mValue)) print_r($mValue);
        $rek["catnum"] = "_".$mValue;
        $mOfTaxon = "true";

        /* http://rs.tdwg.org/dwc/terms/measurementMethod will be concatenated as "methodology_name (value_type)" */
        $rek['measurementMethod'] = self::format_methodology($rec);
        $rek['statisticalMethod'] = self::get_smethod($rec['value_type'], @$rek['statisticalMethod']);
        
        $rek = self::implement_precision_cols($rec, $rek);

        if($ref_ids = self::write_references($rec)) $rek['referenceID'] = implode("; ", $ref_ids);

        $rek['measurementType']  = $mType;
        $rek['measurementValue'] = $mValue;
        /* good debug
        if(@$this->debug['monitor']) {
            print_r($rek); print_r($rec); exit;
        }
        */
        
        /*debug only
        if($rec['trait_name'] == "Flow rate") {
            print_r($rec); print_r($rek);
        }
        */
        $rek = self::run_special_cases($rec, $rek);
        if(!@$rek['measurementType']) return;
        /*debug only
        if($rec['trait_name'] == "Flow rate") {
            // print_r($rec); print_r($rek); exit;
        }
        */

        // /* debug only
        if(!@$trait_rec)       $this->debug['undef trait']['NC'][$rec['trait_name']] = '';     //debug only - Jen might add mappings here
        if(!is_numeric($rec['value'])) {
            if(!@$this->meta['value'][$rec['value']])             $this->debug['undef value']['NC'][$rec['trait_name']][$rec['value']] = '';          //debug only - Jen might add mappings here
        }
        if(!@$this->meta['standard_unit'][$rec['standard_unit']]) $this->debug['undef unit']['NC'][$rec['standard_unit']] = '';   //debug only - all 4 found are expected to have blank units
        // */

        $rek['source'] = $this->source . $rec['specie_id'];
        $rek['bibliographicCitation'] = $this->bibliographicCitation;

        // /* NEW: additional mapping task from here: https://eol-jira.bibalex.org/browse/DATA-1793?focusedCommentId=63392&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63392
        $rek = self::implement_addtl_mapping($rek);
        // */

        if($rek['measurementType'] != 'http://eol.org/schema/terms/HomeRange') {
            $ret_MoT_true = $this->func->pre_add_string_types($rek, $rek['measurementValue'], $rek['measurementType'], $mOfTaxon); //main add trait
            $occurrenceID = $ret_MoT_true['occurrenceID'];
            $measurementID = $ret_MoT_true['measurementID'];
            $this->OM_ids[$occurrenceID][$measurementID] = '';
        }
        
        //Special Case #3: add the other mValue
        if($rec['value'] == 'caespitose_corymbose') {
            $rek['measurementValue'] = 'http://eol.org/schema/terms/caespitose';        //start add
            $ret_MoT_true = $this->func->pre_add_string_types($rek, $rek['measurementValue'], $rek['measurementType'], $mOfTaxon);
            $occurrenceID = $ret_MoT_true['occurrenceID'];
            $measurementID = $ret_MoT_true['measurementID'];
        }
        //Special Case #4: add the other mValue
        if($rec['value'] == 'massive and columnar') {
            $rek['measurementValue'] = 'http://purl.obolibrary.org/obo/PORO_0000389';   //start add
            $ret_MoT_true = $this->func->pre_add_string_types($rek, $rek['measurementValue'], $rek['measurementType'], $mOfTaxon);
            $occurrenceID = $ret_MoT_true['occurrenceID'];
            $measurementID = $ret_MoT_true['measurementID'];
        }
        //Special Case #5: add the other mValue
        if($rec['value'] == 'arborescent_tables') {
            $rek['measurementValue'] = 'http://eol.org/schema/terms/explanate';         //start add
            $ret_MoT_true = $this->func->pre_add_string_types($rek, $rek['measurementValue'], $rek['measurementType'], $mOfTaxon);
            $occurrenceID = $ret_MoT_true['occurrenceID'];
            $measurementID = $ret_MoT_true['measurementID'];
        }
    }
    private function create_trait($rec)
    {
        /*Array(
            [observation_id] => 25
            [access] => 1
            [user_id] => 2
            [specie_id] => 968
            [specie_name] => Micromussa amakusensis
            [location_id] => 1
            [location_name] => Global estimate
            [latitude] => 
            [longitude] => 
            [resource_id] => 40
            [resource_secondary_id] => 48
            [measurement_id] => 
            [trait_id] => 40
            [trait_name] => Ocean basin
            [trait_class] => Geographical
            [standard_id] => 10
            [standard_unit] => cat
            [methodology_id] => 9
            [methodology_name] => Derived from range map
            [value] => pacific
            [value_type] => expert_opinion
            [precision] => 
            [precision_type] => 
            [precision_upper] => 
            [replicates] => 
            [notes] => 
        )
        */
        /*[trait_class] => Array(
            [Geographical] [Physiological] [Ecological] [Conservation] [Reproductive] [Contextual] [Stoichiometric] [Morphological] [Biomechanical] [Phylogenetic]
        )*/
        //$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ start contextual
        if($this->sought_trait_class == 'contextual') {
            if($rec['trait_class'] == "Contextual") {
                self::process_contextual($rec);
                return;
            }
            else return;
        }
        //$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ end contextual
        //$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ start non contextual
        if($this->sought_trait_class == 'non contextual') {
            if($rec['trait_class'] != "Contextual") {
                self::process_non_contextual($rec);
                return;
            }
            else return;
        }
        //$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ end non contextual
    }
    private function run_special_cases($rec, $rek, $contextualYN = false)
    {
        if($contextualYN) {
            // ***this Special Case is for trait_class == Contextual
            //#2 Where trait_name= 'Light extinction coefficient' and units=m, map measurementType=http://eol.org/schema/terms/secchiDepth. 
            //                                                Where units=Kd, map measurementType=https://www.wikidata.org/entity/Q902086 and the record should have no units
            if($rec['trait_name'] == 'Light extinction coefficient') { //contextual
                if($rec['standard_unit'] == 'm') $rek['measurementType'] = 'http://eol.org/schema/terms/secchiDepth';
                elseif($rec['standard_unit'] == 'Kd') {
                    $rek['measurementType'] = 'https://www.wikidata.org/entity/Q902086';
                    $rek['measurementUnit'] = ''; //set to blank
                }
                return $rek;
            }
            return $rek;
        }
        
        /* ---All below here is for: non contextual--- */
        //#1 Where trait_name = Symbiodinium sp. in propagules: if value=no, map measurementValue to http://eol.org/schema/terms/no. If value=yes, map to http://eol.org/schema/terms/symbiontInheritance
        if($rec['trait_name'] == 'Symbiodinium sp. in propagules') { //non contextual
            if($rec['value'] == 'no') $rek['measurementValue'] = 'http://eol.org/schema/terms/no';
            if($rec['value'] == 'yes') $rek['measurementValue'] = 'http://eol.org/schema/terms/symbiontInheritance';
            return $rek;
        }
        //#3 where value= caespitose_corymbose, create two records sharing all metadata, one with value= http://eol.org/schema/terms/corymbose and one with 
        //                                                                                        value= http://eol.org/schema/terms/caespitose
        if($rec['value'] == 'caespitose_corymbose') { //non contextual
            $rek['measurementValue'] = 'http://eol.org/schema/terms/corymbose';
            return $rek;
        }
        //#4 where value= massive and columnar, create two records sharing all metadata, one with value= http://eol.org/schema/terms/columnar and one with 
        //                                                                                        value= http://purl.obolibrary.org/obo/PORO_0000389
        if($rec['value'] == 'massive and columnar') { //non contextual
            $rek['measurementValue'] = 'http://eol.org/schema/terms/columnar';
            return $rek;
        }
        // #5 where value= arborescent_tables, create two records sharing all metadata, one with value= http://eol.org/schema/terms/arborescent and one with 
        //                                                                                       value= http://eol.org/schema/terms/explanate
        if($rec['value'] == 'arborescent_tables') { //non contextual
            $rek['measurementValue'] = 'http://eol.org/schema/terms/arborescent';
            return $rek;
        }
        // #6 where trait_name=Abundance GBR, measurementValue is always the same, but source value determines the content of the http://purl.obolibrary.org/obo/NCIT_C70589 element. 
        // rare: https://www.wikidata.org/entity/Q3503448, 
        // common: https://www.wikidata.org/entity/Q5153621, 
        // uncommon: http://eol.org/schema/terms/uncommon
        if($rec['trait_name'] == 'Abundance GBR') { //non contextual
            // print_r($rec); print_r($rek); print_r($this->meta['trait_name'][$rec['trait_name']]); exit;
            if    ($rec['value'] == 'rare')     $rek['NCIT_C70589'] = 'https://www.wikidata.org/entity/Q3503448';
            elseif($rec['value'] == 'common')   $rek['NCIT_C70589'] = 'https://www.wikidata.org/entity/Q5153621';
            elseif($rec['value'] == 'uncommon') $rek['NCIT_C70589'] = 'http://eol.org/schema/terms/uncommon';
            else $this->debug['undefined Abundance GBR'][$rec['value']] = '';
            return $rek;
        }
        /*
        #7 where statisticalmethod is provided twice- once as a column in the trait_name mapping and once as a child measurement
            - the child measurement should be kept and the column record discarded
        */
        return $rek;
    }
    private function write_references($rec)
    {
        // [resource_id] => 40
        // [resource_secondary_id] => 48
        /*Array(
            [resource_id] => 543
            [author] => Szmant, A. M.
            [year] => 1986
            [title] => Reproductive ecology of Caribbean reef corals
            [doi_isbn] => 10.1007/bf00302170
            [journal] => Coral Reefs
            [volume_pages] => 5, 43-53
        )*/
        $indexes = array('resource_id', 'resource_secondary_id');
        $ref_ids = array();
        foreach($indexes as $index) {
            if($ref = @$this->refs[$rec[$index]]) {
                $ref_id = $ref['resource_id'];
                $r = new \eol_schema\Reference();
                $r->identifier = $ref_id;
                $r->full_reference = $ref['full_ref'];
                // $r->uri = '';
                $r->doi = $ref['doi_isbn'];
                // $r->publisher = '';
                $r->title = $ref['title'];
                $r->authorList = $ref['author'];
                if(!isset($this->reference_ids[$ref_id])) {
                    $this->reference_ids[$ref_id] = '';
                    $this->archive_builder->write_object_to_file($r);
                }
                $ref_ids[] = $ref_id;
            }
        }
        return $ref_ids;
    }
    private function implement_precision_cols($rec, $rek)
    {   /*precision, precision_type: precision_type will be the MoF column (this will generate a handful of columns) and precision will be the value for that record
        precision_type:
            standard_deviation: http://semanticscience.org/resource/SIO_000770
            range: http://purl.obolibrary.org/obo/STATO_0000035
            standard_error: http://purl.obolibrary.org/obo/OBI_0000235
            not_given: http://semanticscience.org/resource/SIO_000769
            95_ci: http://purl.obolibrary.org/obo/STATO_0000231
        precision: take numeric values as is. Discard non numeric values
        [precision_type] => Array( --- unique values
                    [] => 
                    [range] => 
                    [standard_deviation] => 
                    [standard_error] => 
                    [95_ci] => 
                    [not_given] => 
                )
        */
        if(!is_numeric($rec['precision'])) return $rek;
        if($rec['precision_type'] == "standard_deviation") $rek['SIO_000770'] = $rec['precision'];
        elseif($rec['precision_type'] == "range") $rek['STATO_0000035'] = $rec['precision'];
        elseif($rec['precision_type'] == "standard_error") $rek['OBI_0000235'] = $rec['precision'];
        elseif($rec['precision_type'] == "not_given") $rek['SIO_000769'] = $rec['precision'];
        elseif($rec['precision_type'] == "95_ci") $rek['STATO_0000231'] = $rec['precision'];
        return $rek;
    }
    private function create_taxon($rec)
    {   /*[specie_id] => 968
          [specie_name] => Micromussa amakusensis
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['specie_id'];
        $taxon->scientificName  = $rec['specie_name'];
        $taxon->kingdom = 'Animalia';
        $taxon->phylum = 'Cnidaria';
        $taxon->class = 'Anthozoa';
        $taxon->order = 'Scleractinia';
        // $taxon->taxonRank             = '';
        // $taxon->furtherInformationURL = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function initialize_resources_record($rec)
    {   /*Array(
        [resource_id] => 543
        [author] => Szmant, A. M.
        [year] => 1986
        [title] => Reproductive ecology of Caribbean reef corals
        [doi_isbn] => 10.1007/bf00302170
        [journal] => Coral Reefs
        [volume_pages] => 5, 43-53
        )*/
        // print_r($rec); exit;
        // Last, F. M. (Year, Month Date Published). Article title. Retrieved from URL
        // Last, F. M. (Year Published) Book. City, State: Publisher.
        $full_ref = "$rec[author]. ($rec[year]). $rec[title]. $rec[journal]. $rec[volume_pages].";
        $full_ref = trim(Functions::remove_whitespace($full_ref));
        $full_ref = str_replace(array('...','..'), ".", $full_ref);
        $rec['full_ref'] = $full_ref;
        $this->refs[$rec['resource_id']] = $rec;
    }
    private function initialize_spreadsheet_mapping($sheet, $file = false)
    {
        if(!$file) $file = $this->spreadsheet_for_mapping;
        $sheets['trait_name'] = 1;
        $sheets['value'] = 2;
        $sheets['unit'] = 3;
        $sheet_no = $sheets[$sheet];
        $final = array();
        $options = $this->download_options;
        $options['file_extension'] = 'xlsx';
        $options['expire_seconds'] = 60*60*24; //updated Nov 22, 2019
        $local_xls = Functions::save_remote_file_to_local($file, $options);
        require_library('XLSParser');
        $parser = new XLSParser();
        debug("\n reading: " . $local_xls . "\n");
        $map = $parser->convert_sheet_to_array($local_xls, $sheet_no);
        $fields = array_keys($map);
        // print_r($map); exit;
        print_r($fields); //exit;
        // foreach($fields as $field) echo "\n$field: ".count($map[$field]); //debug only
        $i = -1;
        foreach($map[$fields[0]] as $var) {
            $i++;
            $rec = array();
            foreach($fields as $fld) $rec[$fld] = $map[$fld][$i];
            $final[$rec[$fields[0]]] = $rec;
        }
        unlink($local_xls);
        // print_r($final); exit;
        return $final;
    }
    private function load_zip_contents()
    {
        $options = $this->download_options;
        $options['file_extension'] = 'zip';
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($local_zip_file = Functions::save_remote_file_to_local($this->partner_source_csv, $options)) {
            $output = shell_exec("unzip -o $local_zip_file -d $this->TEMP_FILE_PATH");
            if(file_exists($this->TEMP_FILE_PATH . "/".$this->download_version."/".$this->download_version."_data.csv")) {
                $this->text_path["data"] = $this->TEMP_FILE_PATH . "/$this->download_version/".$this->download_version."_data.csv";
                $this->text_path["resources"] = $this->TEMP_FILE_PATH . "/$this->download_version/".$this->download_version."_resources.csv";
                print_r($this->text_path);
                echo "\nlocal_zip_file: [$local_zip_file]\n";
                unlink($local_zip_file);
                return TRUE;
            }
            else return FALSE;
        }
        else {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return FALSE;
        }
    }
    private function get_smethod($value_type, $orig_value = '')
    {   /*value_type will get used again: http://eol.org/schema/terms/statisticalMethod
        mapping:
        raw_value: http://www.ebi.ac.uk/efo/EFO_0001444
        median: http://semanticscience.org/resource/SIO_001110
        mean: http://semanticscience.org/resource/SIO_001109
        ANYTHING ELSE: discard
        [value_type] => Array( --- all values for value_type
                    [expert_opinion] => 
                    [group_opinion] => 
                    [raw_value] => 
                    [] => 
                    [mean] => 
                    [median] => 
                    [maximum] => 
                    [model_derived] => 
                )*/
        if($value_type == 'raw_value') return "http://www.ebi.ac.uk/efo/EFO_0001444";
        elseif($value_type == 'median') return "http://semanticscience.org/resource/SIO_001110";
        elseif($value_type == 'mean') return "http://semanticscience.org/resource/SIO_001109";
        else return $orig_value;
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
}
?>
