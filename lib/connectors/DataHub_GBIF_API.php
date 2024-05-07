<?php
namespace php_active_record;
/*  datahub_gbif.php
*/
class DataHub_GBIF_API
{
    function __construct($folder = false)
    {
        $this->download_options_GBIF = array('resource_id' => "723_gbif", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 2000000, 'timeout' => 10800*2, 'download_attempts' => 1); //3 months to expire
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "GBIF/";
        if(!is_dir($save_path)) mkdir($save_path);
        $this->save_path = $save_path;
        $this->reports_path = $save_path;
        $this->debug = array();

        // $this->inat_api['taxa'] = "https://api.inaturalist.org/v1/observations/species_counts?taxon_is_active=true&hrank=XRANK&lrank=XRANK&iconic_taxa=XGROUP&quality_grade=XGRADE&page=XPAGE"; //defaults to per_page = 500
        // $this->taxon_page = "https://www.inaturalist.org/taxa/"; //1240-Dendragapus or just 1240
        // $this->dwca['inaturalist-taxonomy'] = "https://www.inaturalist.org/taxa/inaturalist-taxonomy.dwca.zip";

        $this->local_csv = "/Volumes/Crucial_2TB/eol_php_code_tmp2/0000495-240506114902167.csv";
    }

    function start()
    {
        self::parse_tsv_file($this->local_csv, "write DwCA");
        print_r($this->debug);
    }
    private function parse_tsv_file($file, $what)
    {   echo "\nReading file, task: [$what]\n";
        $i = 0; $final = array();
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % 100000) == 0) echo "\n $i ";
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");
                // print_r($rec); exit("\nelix 200\n");
                /*Array(
                    [taxonKey] => 359
                    [scientificName] => Mammalia
                    [acceptedTaxonKey] => 359
                    [acceptedScientificName] => Mammalia
                    [numberOfOccurrences] => 126524
                    [taxonRank] => CLASS
                    [taxonomicStatus] => ACCEPTED
                    [kingdom] => Animalia
                    [kingdomKey] => 1
                    [phylum] => Chordata
                    [phylumKey] => 44
                    [class] => Mammalia
                    [classKey] => 359
                    [order] => 
                    [orderKey] => 
                    [family] => 
                    [familyKey] => 
                    [genus] => 
                    [genusKey] => 
                    [species] => 
                    [speciesKey] => 
                    [iucnRedListCategory] => NE
                )*/
                if($what == 'write DwCA') {
                    $taxonomicStatus = $rec['taxonomicStatus'];
                    $this->debug['taxonomicStatus'][$taxonomicStatus] = '';
                    $rec['taxonRank'] = strtolower($rec['taxonRank']);
                    $taxonRank = $rec['taxonRank'];
                    $this->debug['taxonRank'][$taxonRank] = '';
                    /* [taxonomicStatus] => Array( [ACCEPTED] [SYNONYM] [DOUBTFUL] [] ) */

                    $rank_main[1] = 'kingdom';
                    $rank_main[1] = 'phylum';
                    $rank_main[1] = 'class';
                    $rank_main[1] = 'order';
                    $rank_main[1] = 'family';
                    $rank_main[1] = 'genus';
                    $rank_main[1] = 'species';
                    $rank_main[1] = 'form';
                    $rank_main[1] = 'variety';
                    $rank_main[1] = 'subspecies';
                    $rank_main[1] = 'unranked';

                    if($taxonRank == 'variety') {
                        print_r($rec);
                    }

                    if($taxonomicStatus == 'ACCEPTED') {
                        // print_r($rec);
                        // self::prep_write_taxon($rec);

                        // self::write_MoF($rec);
                        // if($taxonRank == 'form') exit;
                    }
                }
                else exit("\nNothing to do\n");
            }
        }
    }


    // =========================================================================== copied template below
    function parse_tsv_then_generate_dwca()
    {
        $this->debug = array();
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);

        foreach($this->quality_grades as $grade) $this->dump_file[$grade] = $this->save_path . "/datahub_inat_grade_".$grade.".txt"; //file variable assignment

        // step 1: reads iNat taxonomy and gen. info taxa list
        self::gen_iNat_info_taxa_using_DwCA(); //generates $this->inat_taxa_info

        // step 2: loop each tsv file ('research', 'needs_id', 'casual'), and create info_list for DwCA writing
        self::assemble_data_from_3TSVs();
        // print_r($this->assembled); exit;

        // step 3: write DwCA from the big assembled array $this->assembled
        self::write_dwca_from_assembled_array();

        /*
        // step ?: loop a tsv dump and write taxon and MoF archive --- if u want to gen. a DwCA from one dump
        self::parse_tsv_file($this->dump_file['research'], "process research grade tsv"); 
        */
        
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    private function gen_iNat_info_taxa_using_DwCA()
    {
        echo "\nGenerate taxon info list...\n";
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $options = $this->download_options_GBIF;
        $options['expire_seconds'] = 60*60*24*30*3; //3 months cache
        $paths = $func->extract_archive_file($this->dwca['inaturalist-taxonomy'], "meta.xml", $options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit; //debug only
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_54504/',
            'temp_dir'     => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_54504/'
        );
        */

        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;

        self::process_table($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'gen taxa info');

        /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
    }
    private function assemble_data_from_3TSVs()
    {
        $grades = array('research', 'needs_id', 'casual');
        foreach($grades as $grade) {
            self::parse_tsv_file($this->dump_file[$grade], "assemble data from 3 TSVs", $grade);
        }
    }
    private function write_dwca_from_assembled_array()
    {
        $total = count($this->assembled); $i = 0;
        foreach($this->assembled as $taxonID => $totals) { $i++; if(($i % 20000) == 0) echo "\n $i of $total ";
            // print_r($totals); print_r($this->assembled[$taxonID]); exit;
            if($rek = @$this->inat_taxa_info[$taxonID]) {
                /*Array( $rek
                    [s] => Apis mellifera
                    [r] => species
                    [p] => 578086
                )*/
                $rek['a'] = $totals['a'];
            }
            elseif($rek = @$this->assembled[$taxonID]) {} //print_r($rek); exit("\nelix 2\n");
            else {
                $this->debug['not in iNat taxonomy'][$taxonID] = '';
                continue;
            }
            $rec = array();
            $rec['id'] = $taxonID;
            $rec['rank'] = $rek['r'];
            $rec['name'] = $rek['s'];
            $rec['RG_count'] = @$totals['research'];
            $rec['all_counts'] = @$totals['research'] + @$totals['needs_id'] + @$totals['casual'];
            $rec['parent_id'] = $rek['p'];
            $rec['ancestry'] = $rek['a'];

            // print_r($rec); exit;
            self::prep_write_taxon($rec);
            self::write_MoF($rec);
        }
    }
    private function write_MoF($rec)
    {
        $taxonID = $rec['id'];
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $this->taxon_page . $taxonID;
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 
        // $mValue = $rec['observations_count'];

        $mType = 'http://eol.org/schema/terms/NumberOfRGiNatObservations';
        $mValue = $rec['RG_count'];
        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        $this->func->add_string_types($save, $mValue, $mType, "true");

        $mType = 'http://eol.org/schema/terms/NumberOfiNaturalistObservations';
        $mValue = $rec['all_counts'];
        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        $this->func->add_string_types($save, $mValue, $mType, "true");
    }
    private function prep_write_taxon($rec)
    {   /*Array(
            [id] => 47219
            [rank] => species
            [name] => Apis mellifera
            [RG_count] => 411499
            [all_counts] => 1234752
            [parent_id] => 578086
            [ancestry] => 48460/1/47120/372739/47158/184884/47201/124417/326777/47222/630955/47221/199939/538904/47220/578086
        )*/
        //step 1: 
        $save_taxon = array();
        $save_taxon = array('taxonID' => $rec['id'], 'scientificName' => $rec['name'], 'taxonRank' => $rec['rank'] , 'parentNameUsageID' => $rec['parent_id']);
        self::write_taxon($save_taxon);
        //step 2: write taxon for the ancestry
        $ancestry = explode("/", $rec['ancestry']); //print_r($ancestry); //48460/1/47120/372739/47158/184884/47201/124417/326777/47222/630955/47221/199939/538904/47220/578086
        $ancestry=array_reverse($ancestry); //print_r($ancestry);
        // exit("\nstop muna 1\n");
        $i = -1;
        foreach($ancestry as $taxon_id) { $i++;
            if($r = @$this->inat_taxa_info[$taxon_id]) {
                $save_taxon = array();
                $save_taxon = array('taxonID' => $taxon_id, 'scientificName' => $r['s'], 'taxonRank' => $r['r'] , 'parentNameUsageID' => @$ancestry[$i+1]);
                self::write_taxon($save_taxon);        
            }
        }
    }
    private function write_taxon($rec)
    {
        $taxonID = $rec['taxonID'];
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $taxonID;
        $taxon->scientificName      = $rec['scientificName'];
        $taxon->taxonRank           = $rec['taxonRank'];
        $taxon->parentNameUsageID   = $rec['parentNameUsageID'];
        if(!isset($this->taxonIDs[$taxonID])) {
            $this->taxonIDs[$taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function parse_csv_file($csv_file, $what)
    {
        $i = 0; $meron = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file); //print_r($row);
            if(!$row) continue; 
            $str = implode("\t", $row);
            // if(stripos($str, "Callisaurus	genus") !== false) {  //string found --- good debug
            //     echo("\n$str\n");
            // }
            if(!$row) break;
            $i++; if(($i % 100000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $count = count($fields); // print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    echo("\nWrong CSV format for this row.\n");
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                print_r($rec); exit;

                // if($what == "gen taxa info") $this->inat_taxa_info[$rec['id']] = array('s' => $rec['scientificName'], 'r' => $rec['taxonRank'], 'p' => pathinfo($rec['parentNameUsageID'], PATHINFO_FILENAME));
                // elseif($what == "xxx") {}
            }
        }
    }

}
?>