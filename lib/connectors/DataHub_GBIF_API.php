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

        $this->remote_csv = "https://api.gbif.org/v1/occurrence/download/request/0000495-240506114902167.zip";
        // $this->local_csv = "/Volumes/Crucial_2TB/eol_php_code_tmp2/0000495-240506114902167.csv"; //dev only
        $this->taxon_page = 'https://www.gbif.org/species/'; //e.g. 8084280
    }
    function start()
    {   //step 1
        $temp_dir = self::download_extract_gbif_zip_file();

        //step 2: initialize calling of external functions
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        require_library('connectors/NCBIGGIqueryAPI'); 
        $this->func_gbif = new NCBIGGIqueryAPI();

        //step 3
        self::parse_tsv_file($this->local_csv, "gen taxa info"); //generates $this->gbif_taxa_info
        self::parse_tsv_file($this->local_csv, "write DwCA");

        print_r($this->debug);
        $this->archive_builder->finalize(TRUE);

        // /* un-comment in real operation -- remove temp dir
        if(stripos($temp_dir, '/eol_php_code_tmp/') !== false) { //string found
            recursive_rmdir($temp_dir);
            echo ("\n temporary directory removed: " . $temp_dir);    
        }
        // */
    }
    /*  [taxonomicStatus] => Array(
            [ACCEPTED] => 
            [SYNONYM] => 
            [DOUBTFUL] => 
            [] => 
        )
    [taxonRank] => Array(
            [phylum] => 
            [kingdom] => 
            [class] => 
            [order] => 
            [family] => 
            [genus] => 

            [species] => 
            [form] => 
            [variety] => 
            [subspecies] => 
            [unranked] => 
    )*/
    private function parse_tsv_file($file, $what)
    {   echo "\nReading file, task: [$what] [$file]\n";
        $i = 0; $final = array();
        $included_ranks = array("species", "form", "variety", "subspecies", "unranked");
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % 200000) == 0) echo "\n $i ";
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");
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

                $taxonID = $rec['taxonKey'];
                $taxonomicStatus = $rec['taxonomicStatus'];
                $this->debug['taxonomicStatus'][$taxonomicStatus] = '';
                $rec['taxonRank'] = strtolower($rec['taxonRank']);
                $taxonRank = $rec['taxonRank'];
                $this->debug['taxonRank'][$taxonRank] = '';
                /* [taxonomicStatus] => Array( [ACCEPTED] [SYNONYM] [DOUBTFUL] [] ) */
                // if($taxonRank != 'variety') continue; //print_r($rec); //debug only

                if($what == 'write DwCA') {
                    if($taxonomicStatus == 'ACCEPTED') { //print_r($rec);
                        $t = array();
                        $t['id'] = $taxonID;
                        $t['rank'] = $rec['taxonRank'];
                        $t['name'] = $rec['scientificName'];

                        if(in_array($taxonRank, $included_ranks)) {
                            @$this->debug['counts']['species-level']++;
                            $t['numberOfOccurrences'] = $rec['numberOfOccurrences'];
                        }
                        elseif(in_array($taxonRank, array('family', 'genus'))) {
                            /*[counts] => Array(
                                [other-levels] => 1996
                                [family-genus-level] => 157895
                                [species-level] => 2572371
                            )
                            Since there is 157,895 API calls here, we will just make use of the NCBIGGIqueryAPI.php ver to run.
                            */
                            @$this->debug['counts']['family-genus-level']++;
                            continue; //
                            // /* main operation
                            $count = $this->func_gbif->get_gbif_taxon_record_count($taxonID);
                            if($count > 0) $t['numberOfOccurrences'] = $count;
                            else continue;
                            // */
                        }
                        else { //'kingdom', 'phylum', 'class', 'order',
                            @$this->debug['counts']['other-levels']++;
                            continue;
                        }
                        // continue; //debug only

                        $ret = self::get_parent_id($rec);
                        $t['parent_id'] = $ret['parent_id'];
                        $t['ancestry'] = $ret['ancestry']; //print_r($t);
                        self::prep_write_taxon($t);
                        self::write_MoF($t);                        
                    }
                }
                elseif($what == "gen taxa info") {
                    $ret = self::get_parent_id($rec);
                    $this->gbif_taxa_info[$taxonID] = array('s' => $rec['scientificName'], 'r' => $rec['taxonRank'], 'p' => $ret['parent_id']);
                }
                else exit("\nNothing to do\n");
            }
            // if($i >= 10) break; //debug only
        }
    }
    private function get_parent_id($rec)
    {
        $rank_main[1] = 'kingdom';
        $rank_main[2] = 'phylum';
        $rank_main[3] = 'class';
        $rank_main[4] = 'order';
        $rank_main[5] = 'family';
        $rank_main[6] = 'genus';
        $rank_main[7] = 'species';
        $rank_main[8] = 'form';
        $rank_main[8] = 'variety';
        $rank_main[8] = 'subspecies';
        $rank_main[9] = 'unranked';

        $rank_pos['kingdom'] = 1;
        $rank_pos['phylum'] = 2;
        $rank_pos['class'] = 3;
        $rank_pos['order'] = 4;
        $rank_pos['family'] = 5;
        $rank_pos['genus'] = 6;
        $rank_pos['species'] = 7;
        $rank_pos['form'] = 8;
        $rank_pos['variety'] = 8;
        $rank_pos['subspecies'] = 8;
        $rank_pos['unranked'] = 9;

        $ancestry = array();
        $taxonRank = $rec['taxonRank'];
        if(!$taxonRank) $pos = 7;
        else            $pos = $rank_pos[$taxonRank] - 1;

        if($pos >= 8) $pos = 7;

        for($i = 1; $i <= $pos; $i++) {
            $field = $rank_main[$i]."Key";
            if($value = $rec[$field]) $ancestry[] = $value;
        }

        $ancestry = array_filter($ancestry); //remove null arrays
        $ancestry = array_unique($ancestry); //make unique
        $ancestry = array_values($ancestry); //reindex key
        return array('ancestry' => implode("/", $ancestry), 'parent_id' => end($ancestry));
        /* [kingdom] => Animalia
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
        [speciesKey] => */
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
            if($r = @$this->gbif_taxa_info[$taxon_id]) {
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
    private function write_MoF($rec)
    {   //print_r($rec); exit;
        /*Array(
            [id] => 359
            [rank] => class
            [name] => Mammalia
            [numberOfOccurrences] => 126524
            [parent_id] => 44
            [ancestry] => 1/44
        )*/
        $taxonID = $rec['id'];
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $this->taxon_page . $taxonID;
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 

        $mType = 'http://eol.org/schema/terms/NumberRecordsInGBIF';
        if(isset($rec['numberOfOccurrences'])) {
            $mValue = $rec['numberOfOccurrences'];
            $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
            $this->func->add_string_types($save, $mValue, $mType, "true");    
        }
        else {
            echo "\nInvestigate, no numberOfOccurrences field. Should not go here."; print_r($rec);
        }
    }
    private function download_extract_gbif_zip_file()
    {
        echo "\ndownload_extract_gbif_zip_file...\n";
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $options = $this->download_options_GBIF;
        $options['expire_seconds'] = 60*60*24*30*3; //3 months cache
        $paths = $func->extract_zip_file($this->remote_csv, $options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit; //debug only
        // */

        /* sample output:
        Array(
            [extracted_file]    => /Volumes/AKiTiO4/eol_php_code_tmp/dir_44814/0000495-240506114902167
            [temp_dir]          => /Volumes/AKiTiO4/eol_php_code_tmp/dir_44814/
            [temp_file_path]    => /Volumes/AKiTiO4/eol_php_code_tmp/dir_44814/0000495-240506114902167.zip
        )*/

        /* development only
        $paths = Array(
            'extracted_file' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_09405/0000495-240506114902167',
            'temp_dir'       => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_09405/'
        );
        */

        $temp_dir = $paths['temp_dir'];
        $this->local_csv = $paths['extracted_file'].".csv";
        return $temp_dir;
        /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
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
    private function parse_csv_file($csv_file, $what)
    {
        $i = 0; $meron = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file); //print_r($row);
            if(!$row) continue; 
            $str = implode("\t", $row);
            // if(stripos($str, "Callisaurus	genus") !== false) { echo("\n$str\n"); }  //string found --- good debug
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
                $k = 0; $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                print_r($rec); exit;
                // if($what == "xxx") {}
                // elseif($what == "xxx") {}
            }
        }
    }
}
?>