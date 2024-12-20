<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from fill_up_undefined_parents_real_GBIFChecklists.php for GBIF Checklists] */
class FillUpMissingParents_GBIFChecklistsAPI
{
    function __construct($archive_builder, $resource_id, $archive_path)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->archive_path = $archive_path;
        // $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*1, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->redirected_IDs = array();
        // /* for gnfinder
        if(Functions::is_production()) $this->json_path = '/var/www/html/gnfinder/'; //--- for terminal //'/html/gnfinder/'; --- for Jenkins
        else                           $this->json_path = '/Volumes/AKiTiO4/other_files/gnfinder/';
        // */
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start($info)
    {
        /* Steps:
        1. extract e.g.  SC_andorra.tar.gz to a temp
        2. read taxon.tab from temp and generate the undefined_parents list
        3. now add to archive the undefined_parents
        4. now add the original taxon.tab
        5. check again check_if_all_parents_have_entries() --- this must be zero records. If not repeat #2.
        */
        
        require_library('connectors/NationalChecklistsAPI');
        // $this->func = new WikiDataAPI(false, "en", "taxonomy", $langs_with_multiple_connectors, $debug_taxon, $this->archive_builder); //this was copied from wikidata.php
        $what = 'Country_checklists';
        $this->func = new NationalChecklistsAPI($what);
                
        // /*
        if($tables = @$info['harvester']->tables) print_r(array_keys($tables));
        else {
            echo "\nInvestigate: harvester-tables are not accessbile\n";
            return;
        }
        if($undefined_parents = self::get_undefined_parents_v2()) {
            /* or at this point you can add_2undefined_parents_their_parents(), if needed */
            $no_label_defined = self::append_undefined_parents($undefined_parents);
            self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'create_archive', $no_label_defined);
        }
        else { //no undefined parents
            self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'create_archive');
        }
        // */
        
        /* start customize */
        // if($this->resource_id == 'wikipedia-war') {
            // if($meta_doc = @$tables['http://eol.org/schema/media/document'][0]) self::carry_over($meta_doc, 'document');
        // }
        /* end customize */

        if($meta_doc = @$tables['http://rs.tdwg.org/dwc/terms/occurrence'][0]) self::carry_over($meta_doc, 'occurrence');
        if($meta_doc = @$tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]) self::carry_over($meta_doc, 'measurementorfact');
    }
    private function get_undefined_parents_v2() //working OK
    {
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        $url = $this->archive_path . "/taxon.tab";
        // echo "\n====Will read this path: [$url]\n";
        if($undefined = $func->check_if_all_parents_have_entries($this->resource_id, true, $url)) { //2nd param True means write to text file
            // print_r($undefined);
            echo "\n[$url] [$this->resource_id]";
            echo("\nUndefined v2: ".count($undefined)."\n"); //exit;
            return $undefined;
        }
        // exit("\ndid not detect undefined parents\n");
    }
    private function append_undefined_parents($undefined_parents)
    {
        $to_be_added = array('Q21032607', 'Q68334453', 'Q14476748', 'Q21032613', 'Q2116552'); //last remaining undefined parents. Added here to save one entire loop
        $to_be_added = array();
        $undefined_parents = array_merge($undefined_parents, $to_be_added);
        $no_label_defined = array();
        foreach($undefined_parents as $undefined_id) {
            $rec = Array('specieskey' => $undefined_id, 'countrycode' => false);
            $species_info = $this->func->assemble_species($rec); //print_r($species_info); //exit;
            $taxonID = self::write_taxon($species_info);
        }//end foreach()
        return array_keys($no_label_defined);
    }
    private function write_taxon($rek)
    {   
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                    = $rek['taxonID'];
        $taxon->scientificName             = $rek['scientificName'];
        $taxon->canonicalName              = $rek['canonicalName'];
        $taxon->scientificNameAuthorship   = $rek['scientificNameAuthorship'];
        $taxon->taxonRank                  = $rek['taxonRank'];
        $taxon->parentNameUsageID          = $rek['parentNameUsageID'];
        // $taxon->taxonomicStatus            = $rek['taxonomicStatus']; //commented because these taxa are for parents, where status is irrelevant in a way.
        // $taxon->furtherInformationURL      = $rek['furtherInformationURL'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
        return $taxon->taxonID;
    }
    /* working OK - an option to get a taxon.tab that is a "taxon_working.tab"
    private function get_undefined_parents()
    {
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        $url = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id."_working" . "/taxon_working.tab";
        $suggested_fields = explode("\t", "taxonID	source	parentNameUsageID	scientificName	taxonRank	scientificNameAuthorship");
        if($undefined = $func->check_if_all_parents_have_entries($this->resource_id, true, $url, $suggested_fields)) { //2nd param True means write to text file
            print_r($undefined);
            echo("\nUndefined: ".count($undefined)."\n");
            return $undefined;
        }
        // exit("\ndid not detect undefined parents\n");
    } */
    private function process_table($meta, $what, $no_label_defined = array())
    {   //print_r($meta);
        echo "\nprocess_table: [$what] [$meta->file_uri]...\n"; $i = 0;
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
            /*Array()*/
            if($what == 'create_archive') {
                
                // /* implement redirect ID
                $taxonID           = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                $parentNameUsageID = $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'];
                if($val = @$this->redirected_IDs[$taxonID])           $rec['http://rs.tdwg.org/dwc/terms/taxonID'] = $val;
                if($val = @$this->redirected_IDs[$parentNameUsageID]) $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'] = $val;
                // */
                
                // /* temporary fix until wikidata dump has reflected my edits in wikidata
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                // */
                                
                // /*
                if($no_label_defined) {
                    if(in_array($rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'], $no_label_defined)) $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'] = '';
                }
                // */
                
                $uris = array_keys($rec);
                $o = new \eol_schema\Taxon();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                if(!isset($this->taxon_ids[$o->taxonID])) {
                    $this->taxon_ids[$o->taxonID] = '';

                    // /* new: May 25, 2024 - Save only recognized ranks
                    $o = Functions::use_only_recognized_ranks($o);
                    // */
                    
                    $this->archive_builder->write_object_to_file($o);
                }
            }
            // if($i >= 10) break; //debug only
        }
    }    
    private function carry_over($meta, $class)
    {   //print_r($meta);
        echo "\ncarry_over...[$class][$meta->file_uri]\n"; $i = 0;
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
            $uris = array_keys($rec);
            
            if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
            elseif($class == "agent")               $o = new \eol_schema\Agent();
            elseif($class == "reference")           $o = new \eol_schema\Reference();
            elseif($class == "taxon")               $o = new \eol_schema\Taxon();
            elseif($class == "document")            $o = new \eol_schema\MediaResource();
            elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
            elseif($class == "occurrence_specific") $o = new \eol_schema\Occurrence_specific(); //1st client is 10088_5097_ENV
            elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
            else exit("\nUndefined class [$class]. Will terminate.\n");
            
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>