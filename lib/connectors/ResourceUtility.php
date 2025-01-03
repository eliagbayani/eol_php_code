<?php
namespace php_active_record;
/* This is a generic utility for DwCA post-processing.
first client: called from DwCA_Utility.php, which is called from remove_taxa_without_MoF.php
2nd client  : add canonical_name inside taxon.tab using gnparser command-line
            : called from DwCA_Utility.php, which is called from add_canonical_in_taxa.php
3rd client: report_4_Wikipedia_EN_traits()
4th client: remove_contradicting_traits_fromMoF()
5th client: remove_unused_references() -- called from DwCA_Utility.php, which is called from remove_unused_references.php
6th? client: remove_MoF_for_taxonID() -- called from DwCA_Utility.php, which is called from remove_MoF_for_taxonID.php
*/
class ResourceUtility
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        /* For task: add_canonical_in_taxa */
        $this->extracted_scinames = $GLOBALS['MAIN_TMP_PATH'] . $this->resource_id . "_scinames.txt";
        $this->gnparsed_scinames = $GLOBALS['MAIN_TMP_PATH'] . $this->resource_id . "_canonical.txt";
        
        /* For environments_names.tsv processing */
        $this->ontology['env_names'] = "https://github.com/eliagbayani/vangelis_tagger/raw/master/eol_tagger/environments_names.tsv";
    }
    /*============================================================ STARTS remove_MoF_for_taxonID =================================================*/
    function remove_MoF_for_taxonID($info, $resource_name) //Func #7
    {   // exit("\nthis resource id: $this->resource_id\n"); this resource id: try_dbase_2024

        $tables = $info['harvester']->tables; // print_r($tables); exit;

        // /* Customize here:
        if($this->resource_id == "try_dbase_2024") {
            // generate $this->taxonIDs_in_question -- e.g. array("Phymatodes sp")
            self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'get taxonIDs 2 delete for TRY dbase');
            echo "\ntaxonIDs to delete: [".count($this->taxonIDs_in_question)."]\n";
        }
        else exit("\nResourceUtility: not yet setup.\n");
        // */

        // step 1: get all occurrence ids with this taxon ID. Then write occurrence for those that are not of this taxon ID.
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'get occur recs for this taxonID');

        // /* NEW: to check URI's againt the EOL Terms file
        $this->uris = self::assemble_terms_yml();
        // */

        // step 2: write MoF not in the list of occur ids from step 1.
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'write MoF not of this taxonID');
        // step 3: generate taxon.tab without the said taxon rows
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'gen taxon.tab without the not-wanted taxonIDs');

        // step 4: start write for occurrences
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'write occurrences');


        if(isset($this->debug)) print_r($this->debug);
    }
    private function assemble_terms_yml()
    {
        require_library('connectors/EOLterms_ymlAPI');
        $func = new EOLterms_ymlAPI($this->resource_id, $this->archive_builder);
        $uris = $func->get_terms_yml('ALL_URI');
        // foreach($ret as $label => $uri) $uris[$uri] = $label;
        return $uris;
    }


    /*============================================================= ENDS remove_MoF_for_taxonID ==================================================*/
    /*============================================================ STARTS remove_unused_occurrences =================================================*/
    function remove_unused_occurrences($info, $resource_name) //Func #6
    {
        $tables = $info['harvester']->tables; // print_r($tables); exit;
        // step 1: get all occurrenceIDs & targetOccurrenceIDs from all extensions with occurrence IDs
        if(in_array($resource_name, array('GloBI'))) self::process_generic_table($tables['http://eol.org/schema/association'][0], 'build-up occur info');
        // step 2: create occurrence extension only for those used occurrence IDs
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'create_occurrence');
        // step 3: remaining carry over extensions:
        self::carry_over_extension($tables['http://eol.org/schema/reference/reference'][0], 'reference');
        self::carry_over_extension($tables['http://eol.org/schema/association'][0], 'association');
        self::carry_over_extension($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
    }
    /*============================================================= ENDS remove_unused_occurrences ==================================================*/
    
    /*============================================================ STARTS remove_unused_media =================================================*/
    function remove_unused_media($info, $resource_name) //Func #8
    {
        $tables = $info['harvester']->tables; // print_r($tables); exit;
        $needle = "Zeiss Universal with Olympus C7070 CCD camera";
        // step 1: write Media without the needle. Generate $this->taxonIDs_included and $this->agentIDs_included.
        self::process_generic_table($tables['http://eol.org/schema/media/document'][0], 'delete_if_desc_has_needle', $needle);
        // step 2; write agents
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write_taxa_if_used_in_Media');
        self::process_generic_table($tables['http://eol.org/schema/agent/agent'][0], 'write_agents_if_used_in_Media');

        /* copied template
        // step 2: remaining carry over extensions:
        self::carry_over_extension($tables['http://eol.org/schema/reference/reference'][0], 'reference');
        self::carry_over_extension($tables['http://eol.org/schema/association'][0], 'association');
        self::carry_over_extension($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
        */
    }
    /*============================================================= ENDS remove_unused_media ==================================================*/

    

    /*============================================================ STARTS remove_unused_references =================================================*/
    function remove_unused_references($info, $resource_name) //Func #5
    {
        $tables = $info['harvester']->tables; // print_r($tables); exit;
        // step 1: get all referenceIDs from all extensions with referenceID
        if(in_array($resource_name, array('GloBI'))) self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'build-up ref info');
        if(in_array($resource_name, array('GloBI'))) self::process_generic_table($tables['http://eol.org/schema/association'][0], 'build-up ref info');
        // step 2: create reference extension only for those used referenceIDs
        self::process_generic_table($tables['http://eol.org/schema/reference/reference'][0], 'create_reference');
        // step 3: remaining carry over extensions:
        self::carry_over_extension($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'occurrence');
        self::carry_over_extension($tables['http://eol.org/schema/association'][0], 'association');
        self::carry_over_extension($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
    }
    private function process_generic_table($meta, $what, $needle = false)
    {   //print_r($meta);
        echo "\nResourceUtility...process_generic_table ($what) $meta->row_type ...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");

            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            if($what === 'get taxonIDs 2 delete for TRY dbase') {
                $taxonID = @$rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $rank = @$rec['http://rs.tdwg.org/dwc/terms/taxonRank'];
                $status = @$rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'];
                $this->debug['taxonomicStatus'][$status] = '';
                // if(stripos($taxonID, " sp") !== false)  //string is found
                if(substr($taxonID, -3) == " sp" || substr($taxonID, -4) == " sp." || $taxonID == "sp") {
                    if($rank != "species") $this->taxonIDs_in_question[$taxonID] = '';
                }
                // ============================
                // [accepted] => 
                // [invalid name] => 
                // [] => 
                // [multiple synonyms] => 
                // [unrecognized] => 
                // [unresolved] => 
                if(in_array($status, array("invalid name", "multiple synonyms", "unrecognized", "unresolved"))) $this->taxonIDs_in_question[$taxonID] = '';
            }
            elseif($what == 'get occur recs for this taxonID') {
                /*  Array(
                        [http://rs.tdwg.org/dwc/terms/occurrenceID] => TRY_Adenogramma glomerata
                        [http://rs.tdwg.org/dwc/terms/taxonID] => Adenogramma glomerata
                    )*/
                $taxonID = @$rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(isset($this->taxonIDs_in_question[$taxonID])) {
                    $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                    $this->occurrence_IDs_2delete[$occurrenceID] = '';
                    continue;
                }
            }
            elseif($what == 'write occurrences') {

                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if(isset($this->occurrence_IDs_2delete[$occurrenceID])) continue;
                else {
                    $o = new \eol_schema\Occurrence_specific();
                    self::loop_write($o, $rec);
                }

            }

            elseif($what == 'write MoF not of this taxonID') {

                // /* ++++++++++++++++++++ NEW: log URI not found in EOL Terms file
                $measurementType    = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                $measurementValue   = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                $measurementMethod  = $rec['http://rs.tdwg.org/dwc/terms/measurementMethod'];
                $occurrenceID       = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];

                // /* ----- from Jen: https://github.com/EOL/ContentImport/issues/7#issuecomment-2072229132
                // For TRY database but can be global
                if($measurementType == "http://eol.org/schema/terms/Habitat") {
                    $rec['http://rs.tdwg.org/dwc/terms/measurementType'] = "http://purl.obolibrary.org/obo/RO_0002303";
                    $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                }
                if($measurementValue == "http://www.wikidata.org/entity/Q127498") {
                    $rec['http://rs.tdwg.org/dwc/terms/measurementValue'] = "https://www.wikidata.org/entity/Q12806437";
                    $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                }
                // ----- */

                if(!isset($this->uris[$measurementType])) {
                    @$this->debug["Missing measurementType"][$measurementType]++;
                    $this->occurrence_IDs_2delete[$occurrenceID] = '';
                    continue; //this can be commented actually
                }
                if(!is_numeric($measurementValue)) {
                    if(!isset($this->uris[$measurementValue])) {
                        @$this->debug["Missing measurementValue"][$measurementValue]++;
                        $this->occurrence_IDs_2delete[$occurrenceID] = '';
                        continue; //this can be commented actually
                    }
                }

                /* We don't need this anymore since EOL terms file is now updated to: is_text_only: true
                    // is_text_only: true
                    // name: measurement method
                    // type: metadata
                    // uri: http://rs.tdwg.org/dwc/terms/measurementMethod

                if($measurementMethod) {
                    if(!isset($this->uris[$measurementMethod])) {
                        @$this->debug["Missing measurementMethod"][$measurementMethod]++;
                        $rec['http://rs.tdwg.org/dwc/terms/measurementMethod'] = '';
                    }    
                }
                */

                // /* per Jen: a new work around for measurementMethod with URL values
                if(substr($measurementMethod, 0, 4) == "http") {
                    $measurementMethod = "Followed methods described in " . $measurementMethod;
                    $rec['http://rs.tdwg.org/dwc/terms/measurementMethod'] = $measurementMethod;
                }
                // */

                // ++++++++++++++++++++ */

                // start main write step:
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if(isset($this->occurrence_IDs_2delete[$occurrenceID])) continue;
                else {
                    $o = new \eol_schema\MeasurementOrFact_specific();
                    self::loop_write($o, $rec);
                }
            }
            elseif($what == 'gen taxon.tab without the not-wanted taxonIDs') { //for remove_MoF_for_taxonID()
                $taxonID = @$rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(!isset($this->taxonIDs_in_question[$taxonID])) {
                    $o = new \eol_schema\Taxon();
                    self::loop_write($o, $rec);
                }
            }
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            if($what == 'delete_if_desc_has_needle') { //for remove_unused_media()
                // print_r($rec); exit;
                $taxonID = @$rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $description = @$rec['http://purl.org/dc/terms/description'];    
                if(stripos($description, $needle) !== false) continue; //excluded or deleted  //string is found
                else {
                    // step 1: write media object
                    $this->taxonIDs_included[$taxonID] = '';
                    $o = new \eol_schema\MediaResource();
                    self::loop_write($o, $rec);
                    // step 2: save agent IDs
                    if($val = @$rec['http://eol.org/schema/agent/agentID']) {
                        $arr = explode(";", $val);
                        $arr = array_map('trim', $arr);
                        foreach($arr as $agentID) $this->agentIDs_included[$agentID] = '';
                    }
                }
            }
            elseif($what == 'write_taxa_if_used_in_Media') { //for remove_unused_media()
                $taxonID = @$rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(isset($this->taxonIDs_included[$taxonID])) {
                    $o = new \eol_schema\Taxon();
                    self::loop_write($o, $rec);
                }
            }
            elseif($what == 'write_agents_if_used_in_Media') { //for remove_unused_media()
                $agentID = $rec['http://purl.org/dc/terms/identifier'];
                if(isset($this->agentIDs_included[$agentID])) {
                    $o = new \eol_schema\Agent();
                    self::loop_write($o, $rec);
                }                    
            }
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


            if($what == 'build-up ref info') { //for remove_unused_references()
                if($val = @$rec['http://eol.org/schema/reference/referenceID']) $this->referenceIDs[$val] = '';
            }
            elseif($what == 'create_reference') { //for remove_unused_references()
                $full_reference = @$rec['http://eol.org/schema/reference/full_reference'];
                $primaryTitle   = @$rec['http://eol.org/schema/reference/primaryTitle'];
                $title          = @$rec['http://purl.org/dc/terms/title'];                
                if($full_reference || $primaryTitle || $title) {
                    $referenceID = $rec['http://purl.org/dc/terms/identifier'];
                    if(isset($this->referenceIDs[$referenceID])) { //start saving
                        $o = new \eol_schema\Reference();
                        self::loop_write($o, $rec);
                    }    
                }
            }

            if($what == 'build-up occur info') { //for remove_unused_occurrences()
                if($val = @$rec['http://rs.tdwg.org/dwc/terms/occurrenceID']) $this->all_occurrenced_IDs[$val] = '';
                if($val = @$rec['http://eol.org/schema/targetOccurrenceID']) $this->all_occurrenced_IDs[$val] = '';
            }
            elseif($what == 'create_occurrence') { //for remove_unused_occurrences()
                $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
                if(isset($this->all_occurrenced_IDs[$occurrenceID])) { //start saving
                    $o = new \eol_schema\Occurrence_specific();
                    self::loop_write($o, $rec);
                }
            }

        }
    }
    /*============================================================= ENDS remove_unused_references ==================================================*/

    /*============================================================ STARTS add_canonical_in_taxa =================================================*/
    function add_canonical_in_taxa($info) //Func #2
    {
        //step 1: build-up sciname-canonical info list
        $file_cnt = 0;
        while(true) { $file_cnt++;
            $destination = $this->gnparsed_scinames."_".$file_cnt;
            if(file_exists($destination)) {
                foreach(new FileIterator($destination) as $line => $row) {
                    if(!$row) continue;
                    // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
                    $rec = explode("\t", $row);
                    // print_r($rec); //exit("\ndebug1...\n");
                    /*Array(
                        [0] => d0f24211-8123-5397-8685-485dac20542c
                        [1] => Saccamminopsis camelopardalis Schallreuter, 1985
                        [2] => Saccamminopsis camelopardalis
                        [3] => Saccamminopsis camelopardalis
                        [4] => Schallreuter 1985
                        [5] => 1985
                        [6] => 1
                    )*/
                    $this->sciname_canonical_info[trim($rec[1])] = trim($rec[3]);
                }
            }
            else break;
        }
        //step 2: write to taxa the new column canonicalname
        $tables = $info['harvester']->tables;
        self::process_taxon_Func2($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write taxa');
        //step 3: write document extension - just copy
        /* working but not needed for DH purposes
        self::carry_over_extension($tables['http://eol.org/schema/media/document'][0], 'document');
        self::carry_over_extension($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'measurementorfact');
        */
        echo "\nTotal scinames no canonical generated: ".count($this->debug['sciname no canonical generated']);
    }
    function gen_canonical_list_from_taxa($info) //Func2
    {
        $tables = $info['harvester']->tables;
        self::process_taxon_Func2($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write scinames list for gnparser'); //generate WoRMS2EoL_zip_scinames.txt_1...
        self::insert_canonical_in_taxa();
    }
    private function insert_canonical_in_taxa()
    {   //step 1: run gnparser, generate WoRMS2EoL_zip_canonical.txt_1...
        $file_cnt = 0;
        while(true) { $file_cnt++;
            $source = $this->extracted_scinames."_".$file_cnt;
            $destination = $this->gnparsed_scinames."_".$file_cnt;
            if(file_exists($source)) {
                $cmd = "gnparser file -f simple --input $source --output $destination"; //'simple' or 'json-compact'
                $out = shell_exec($cmd); echo "\n$out\n";
            }
            else break;
        }
    }
    private function process_taxon_Func2($meta, $task)
    {   //print_r($meta);
        echo "\nResourceUtility...($task)...\n"; $i = 0;
        
        if($task == 'write scinames list for gnparser') {
            $file_cnt = 1;
            $WRITE = fopen($this->extracted_scinames."_".$file_cnt, "w"); $eli = 0;
        }
        
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug1...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => urn:lsid:marinespecies.org:taxname:1
                [http://rs.tdwg.org/dwc/terms/scientificName] => Biota
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] =>
                ...
            )*/
            if($task == 'write scinames list for gnparser') {
                if(($i % 400000) == 0) {
                    $file_cnt++;
                    fclose($WRITE);
                    $WRITE = fopen($this->extracted_scinames."_".$file_cnt, "w");
                }
                // /* for scientificName
                if($scientificName = trim($rec['http://rs.tdwg.org/dwc/terms/scientificName'])){}
                else $eli++;
                fwrite($WRITE, $scientificName . "\n");
                // */
                
                /* for genus - was never used though
                if($genus = trim($rec['http://rs.tdwg.org/dwc/terms/genus'])){}
                else $eli++;
                if(!isset($written_taxa[$genus])) {
                    fwrite($WRITE, $genus . "\n");
                    $written_taxa[$genus] = '';
                }
                */
                
            }
            elseif($task == 'write taxa') {
                // /* for scientificName
                $scientificName = trim($rec['http://rs.tdwg.org/dwc/terms/scientificName']);
                if($canonical = $this->sciname_canonical_info[$scientificName]) {
                    $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = $canonical; //deliberately used vernacularName for canonical values
                }
                else {
                    // print_r($rec); exit("\nsciname no canonical generated\n");
                    $this->debug['sciname no canonical generated'][$scientificName] = '';
                    $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = $scientificName;
                }
                // */

                /* for genus - was never used though
                $genus = trim($rec['http://rs.tdwg.org/dwc/terms/genus']);
                if($canonical = $this->sciname_canonical_info[$genus]) {
                    $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = $canonical; //deliberately used 'vernacularName' to store canonical values
                }
                else {
                    // print_r($rec); exit("\nsciname no canonical generated\n");
                    $this->debug['sciname no canonical generated'][$genus] = '';
                    $rec['http://rs.tdwg.org/dwc/terms/vernacularName'] = $genus;
                }
                */
                
                if($this->resource_id == 'WoRMS2EoL_zip') {
                    $rec['http://purl.org/dc/terms/accessRights'] = $rec['http://purl.org/dc/terms/rights'];
                    unset($rec['http://purl.org/dc/terms/rights']); //'rights' is undefined in WoRMS taxon dictionary
                }
                
                $uris = array_keys($rec);
                $o = new \eol_schema\Taxon();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
        if($task == 'write scinames list for gnparser') {
            fclose($WRITE);
            echo "\nNo. of records without scientificName (should be zero) = $eli\n";
        }
    }
    /*============================================================ ENDS add_canonical_in_taxa ===================================================*/
    
    /*============================================================ STARTS remove_taxa_without_MoF =================================================*/
    function remove_taxa_without_MoF($info) //Func #1
    {   
        $tables = $info['harvester']->tables;
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]);                //build $this->taxon_ids
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'build_info_taxon_ids');  //build $this->taxon_ids
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'write_taxa');            //write taxa
    }
    private function process_occurrence($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...read occurrences...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /**/
            //------------------------------------------------------------------------------
            if($taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID']) $this->taxon_ids[$taxonID] = '';
            if($parentNameUsageID = @$rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID']) $this->taxon_ids[$parentNameUsageID] = '';
            if($acceptedNameUsageID = @$rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']) $this->taxon_ids[$acceptedNameUsageID] = '';
            //------------------------------------------------------------------------------
        }
    }
    private function process_taxon($meta, $what)
    {   //print_r($meta);
        echo "\nResourceUtility...[$what]...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /**/
            if($what == 'build_info_taxon_ids') {
                if($parentNameUsageID = @$rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID']) $this->taxon_ids[$parentNameUsageID] = '';
                if($acceptedNameUsageID = @$rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID']) $this->taxon_ids[$acceptedNameUsageID] = '';
            }
            elseif($what == 'write_taxa') {
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(!isset($this->taxon_ids[$taxonID])) continue;
                //------------------------------------------------------------------------------
                $uris = array_keys($rec);
                $o = new \eol_schema\Taxon();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }

                // /* new: May 25, 2024 - Save only recognized ranks
                $o = self::use_only_recognized_ranks($o);
                // */

                $this->archive_builder->write_object_to_file($o);
            }
        }
    }
    private function use_only_recognized_ranks($o)
    {
        if($val = @$o->taxonRank) {
            if(!in_array($val, \eol_schema\Taxon::$ranks)) $o->taxonRank = '';
        }
        return $o;
    }
    /*============================================================ ENDS remove_taxa_without_MoF ==================================================*/
    /*================================================== STARTS report_4_Wikipedia_EN_traits ===============================================*/
    function report_4_Wikipedia_EN_traits($info) //Func #3
    {
        self::get_env_names_info_list(); // print_r($env_names_info_list); exit("\nstop munax\n");
        $tables = $info['harvester']->tables;
        self::process_MoF_Func3($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'report for Jen');
    }
    private function get_env_names_info_list()
    {
        $options = array('expire_seconds' => 60*60*24);
        $local_file = Functions::save_remote_file_to_local($this->ontology['env_names'], $options);
        /*
        1007000016	ocean
        1007000032	wadi
        1007000048	canopy
        1007000064	water body
        */
        foreach(new FileIterator($local_file) as $line => $row) {
            // $i++; if(($i % 100) == 0) echo "\n".number_format($i);
            if(!$row) continue;
            $arr = explode("\t", $row);
            $this->env_names_string_code[$arr[1]] = $arr[0];
            $this->env_names_joined[$arr[1]][$arr[0]] = '';
        }
        unlink($local_file);
    }
    private function process_MoF_Func3($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...read MoF...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => a68a8ae49e178d85af09d5682c52c60e_617_ENV
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => a3232ea9cf84b8c1aa2e2691441805c6_617_ENV
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/RO_0002303
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_01000206
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "temperate"
                [http://purl.org/dc/terms/source] => https://eol.org/search?q=Brentidae
            )*/
            //-------------------------------------
            $debug[$rec['http://rs.tdwg.org/dwc/terms/measurementRemarks']][$rec['http://rs.tdwg.org/dwc/terms/measurementValue']] = '';
            //-------------------------------------
            /* Sample B:
            Q1000266_-_c4c5f6b1da59da518a855dd311b66421.txt 1716 1720 coast ENVO:00000303
            Q1000266_-_c4c5f6b1da59da518a855dd311b66421.txt 1862 1867 coasts ENVO:00000303
            */
            $debug2[$rec['http://rs.tdwg.org/dwc/terms/measurementValue']][$rec['http://rs.tdwg.org/dwc/terms/measurementRemarks']] = '';
            //-------------------------------------
        }
        // print_r($debug); exit;
        
        // /* works OK report - multiple_terms_single_string.txt
        echo "\nmultiple_terms_single_string report";
        echo "\nLEGEND: ** To be deleted in environments_names.tsv\n";
        foreach($debug as $string => $terms) {
            if(count($terms) > 1) {
                echo "\n------------------------------------------------------\n[$string]"; print_r($terms);
                // get what is to be deleted in environments_names
                foreach($terms as $uri => $wala) {
                    $filename = pathinfo($uri, PATHINFO_FILENAME);
                    $filename = str_replace("_", ":", $filename);
                    // /* good debug
                    if($code1 = @$this->env_names_string_code[$filename]) echo "\nLookup [$filename] => ".$code1;
                    // */
                    if(preg_match("/\"(.*?)\"/ims", $string, $a)) {
                        $habitats = explode("|", $a[1]);
                        // echo "\nTo be deleted in environments_names.tsv:";
                        foreach($habitats as $habitat) {
                            if(isset($this->env_names_joined[$habitat][$code1])) {
                                $to_delete[$code1."\t".$habitat] = '';
                                echo "\n ** ".$code1." $habitat";
                            }
                        }
                    }
                }
            }
        }
        echo "\n------------------------------------------------------\n";
        echo "SUMMARY: To be deleted in environments_names.tsv:";
        $to_delete = array_keys($to_delete);
        print_r($to_delete);
        // */

        /*
        foreach($debug2 as $string => $terms) { //works OK report - Sample_B.txt
            if(count($terms) > 1) {
                echo "\n[$string]"; print_r($terms);
            }
        }
        */
    }
    /*================================================== ENDS report_4_Wikipedia_EN_traits =================================================*/

    /*=================================================== STARTS remove_contradicting_traits_fromMoF =======================================*/
    function remove_contradicting_traits_fromMoF($info) //Func #4
    {   
        $tables = $info['harvester']->tables;
        self::process_MoF_contradict($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], "step_1"); //generate $this->mIDs_2delete
        self::process_MoF_contradict($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], "step_2"); //delete respective MoF AND 
                                                                                                              //generate $this->oIDs_2delete
        self::process_occurrence_contradict($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]); //delete respective occurrence
    }
    private function process_MoF_contradict($meta, $step)
    {   //print_r($meta);
        echo "\nResourceUtility...process_MoF_contradict...$step\n"; $i = 0;
        $sought_mValues = array("http://purl.obolibrary.org/obo/ENVO_00000873", "http://purl.obolibrary.org/obo/ENVO_00000447");
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 246a627178d12a5fc0b9f5ea1b47a20b_617_ENV
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0f051e5376b5117e8defa1478892ac4f_617_ENV
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://purl.obolibrary.org/obo/RO_0002303
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_00000067
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "cave"
                [http://purl.org/dc/terms/source] => http://en.wikipedia.org/w/index.php?title=Lion&oldid=1034774842
            )*/
            $measurementID = $rec['http://rs.tdwg.org/dwc/terms/measurementID'];
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            $source = @$rec['http://purl.org/dc/terms/source'];
            $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
            if($step == "step_1") {
                if(in_array($measurementValue, $sought_mValues)) {
                    $this->source_mValue[$source][$measurementID] = $measurementValue;
                }
            } //end step_1
            if($step == "step_2") {
                if(isset($this->mIDs_2delete[$measurementID])) { //delete MoF
                    $this->oIDs_2delete[$occurrenceID] = '';
                }
                else { //start write
                    $uris = array_keys($rec);
                    $o = new \eol_schema\MeasurementOrFact();
                    foreach($uris as $uri) {
                        $field = pathinfo($uri, PATHINFO_BASENAME);
                        $o->$field = $rec[$uri];
                    }
                    $this->archive_builder->write_object_to_file($o);
                }
            } //end step_2
        } //end loop

        if($step == "step_1") {
            // print_r($this->source_mValue); exit;
            foreach($this->source_mValue as $source => $recs) {
                // if($source == 'http://en.wikipedia.org/w/index.php?title=Sea_otter&oldid=1036094281') print_r($recs);
                if(in_array("http://purl.obolibrary.org/obo/ENVO_00000447", $recs) &&
                   in_array("http://purl.obolibrary.org/obo/ENVO_00000873", $recs)) {
                    /* good debug
                    if($source == "http://en.wikipedia.org/w/index.php?title=Sea_otter&oldid=1036094281") {
                        echo "\n[$source]\n"; print_r($recs); exit("\n");
                        [http://en.wikipedia.org/w/index.php?title=Sea_otter&oldid=1036094281]
                        Array(
                            [d57481b8a295b8ab493b727cb8129fd9_617_ENV] => http://purl.obolibrary.org/obo/ENVO_00000447
                            [60f23c55b97b6d18c3bf5fc52b461c45_617_ENV] => http://purl.obolibrary.org/obo/ENVO_00000447
                            [01868074391f917e79b381c37ae8c1b3_617_ENV] => http://purl.obolibrary.org/obo/ENVO_00000873
                            [07fbfadf8339c2cd64caf157479463e8_617_ENV] => http://purl.obolibrary.org/obo/ENVO_00000447
                        )
                    }
                    */
                    foreach($recs as $mID => $mValue) $this->mIDs_2delete[$mID] = '';
                }
            }
            // print_r($this->mIDs_2delete); exit;
        } //end step_1
        
    }
    private function process_occurrence_contradict($meta)
    {   //print_r($meta);
        echo "\nResourceUtility...process_occurrence_contradict...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug...\n");
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            if(!isset($this->oIDs_2delete[$occurrenceID])) { //start write
                $uris = array_keys($rec);
                $o = new \eol_schema\Occurrence();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
    }
    
    /*=================================================== ENDS remove_contradicting_traits_fromMoF =======================================*/

    /* ######################################### Generic functions below: ######################################### */
    private function carry_over_extension($meta, $class)
    {   //print_r($meta);
        echo "\nResourceUtility...carry_over_extension ($class)...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit("\ndebug1...\n");
            /**/
            $uris = array_keys($rec);
            if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
            elseif($class == "agent")               $o = new \eol_schema\Agent();
            elseif($class == "reference")           $o = new \eol_schema\Reference();
            elseif($class == "taxon")               $o = new \eol_schema\Taxon();
            elseif($class == "document")            $o = new \eol_schema\MediaResource();
            // elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
            elseif($class == "occurrence")          $o = new \eol_schema\Occurrence_specific();
            elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
            elseif($class == "association")         $o = new \eol_schema\Association();

            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);

                /* not used anymore...
                if($class == "occurrence") {
                    $remove = array('bodyPart', 'basisOfRecord', 'physiologicalState'); //available in occurrence_specific schema
                    if(in_array($field, $remove)) continue;
                }    
                */

                // /* some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];
                // */

                // /* ignore certain fields for certain extensions: e.g. schema#localityName in Reference() schema
                if($class == "reference") {
                    if($field == "localityName") continue;
                }
                // */

                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }
    private function loop_write($o, $rec)
    {
        $uris = array_keys($rec); //print_r($uris); exit;
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);

            // /* some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
            $parts = explode("#", $field);
            if($parts[0]) $field = $parts[0];
            if(@$parts[1]) $field = $parts[1];
            // */

            // /* used when running Try DBase -- stats only
            if($this->resource_id == "try_dbase_2024") {
                if(in_array($field, array('lifeStage', 'bodyPart'))) {
                    @$this->debug['Try DBase'][$field][$rec[$uri]]++;
                    // continue; //Try database, we've adjusted MeasurementOrFact_specific to accomodate these fields
                }
                if(in_array($field, array('meanlog10', 'SDlog10', 'SampleSize'))) {
                    if (trim($rec[$uri])) @$this->debug['Try DBase'][$field]++;
                    // continue; //Try database, we've adjusted MeasurementOrFact_specific to accomodate these fields
                }    
            }
            // */

            $o->$field = $rec[$uri];
        }
        $this->archive_builder->write_object_to_file($o);        
    }

}
?>