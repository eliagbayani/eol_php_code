<?php
namespace php_active_record;
/*  datahub_inat_v2.php
*/
class DataHub_INAT_API_v2
{
    function __construct($folder = false)
    {
        $this->download_options_INAT = array('resource_id' => "723_inat", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 2000000, 'timeout' => 10800*2, 'download_attempts' => 1); //3 months to expire

        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }

        $this->inat_api['taxa'] = "https://api.inaturalist.org/v1/observations/species_counts?taxon_is_active=true&hrank=XRANK&lrank=XRANK&iconic_taxa=XGROUP&quality_grade=XGRADE&page=XPAGE"; //defaults to per_page = 500
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "iNat/";
        if(!is_dir($save_path)) mkdir($save_path);
        $this->save_path = $save_path;

        $this->quality_grades = array('research', 'needs_id', 'casual'); //orig

        $this->groups = array("Insecta", "Plantae", "Actinopterygii", "Amphibia", "Arachnida", "Aves", "Chromista", "Fungi", "Mammalia", "Mollusca", "Reptilia", "Protozoa", "unknown"); //orig
        // $groups[] = "Animalia"; //excluded since it is a superset of the groups above.

        $this->reports_path = $save_path;
        $this->taxon_page = "https://www.inaturalist.org/taxa/"; //1240-Dendragapus or just 1240
        $this->dwca['inaturalist-taxonomy'] = "https://www.inaturalist.org/taxa/inaturalist-taxonomy.dwca.zip";     //from Ken-ichi
        $this->include_ranks = array('species', 'family', 'genus');
    }
    function start()
    {
        foreach($this->quality_grades as $grade) {
            $this->dump_file[$grade] = $this->save_path . "/datahub_inat_grade_".$grade.".txt";
            if(is_file($this->dump_file[$grade])) unlink($this->dump_file[$grade]);    
        }
        foreach($this->quality_grades as $grade) {
            foreach($this->include_ranks as $include_rank) {
                foreach($this->groups as $group) {
                    echo "\nProcessing [$group]...[$grade]...[$include_rank]\n";
                    self::get_iNat_taxa_observation_using_API($group, $grade, $include_rank);
                    echo "\nEvery group, sleep 5 min.\n";
                    sleep(60*5); //mins interval per group
                }        
            }
        }
    }
    function get_iNat_taxa_observation_using_API($group, $grade, $include_rank) //not advisable to use, bec. of the 10,000 limit page coverage
    {
        $main_url = str_replace("XGROUP", $group, $this->inat_api['taxa']);
        $main_url = str_replace("XGRADE", $grade, $main_url);
        $main_url = str_replace("XRANK", $include_rank, $main_url);

        $page = 1;
        $url = str_replace("XPAGE", $page, $main_url);

        $json = Functions::lookup_with_cache($url, $this->download_options_INAT);
        $obj = json_decode($json); // print_r($obj); //exit;
        $total = $obj->total_results;
        $pages = ceil($total / 500);  echo "\ntotal_results: [$total]\ntotal pages: [$pages]\n";

        for($page = 1; $page <= $pages; $page++) {

            if(($page % 50) == 0) {
                echo "\nEvery 50 calls, sleep 5 min.\n";
                sleep(60*5); //mins interval
            }

            $url = str_replace("XPAGE", $page, $main_url);
            if($json = Functions::lookup_with_cache($url, $this->download_options_INAT)) {

                /* iNat special case - not reliable needs more test
                if(stripos($json, 'error') !== false) { //Too Many Requests           --- //string is found
                    echo "\n[$json]\n";
                    echo "\niNat special error: Too Many Requests\n"; exit("\nexit muna, remove iNat from the list of dbases.\n");
                    sleep(60*10); //10 mins
                    @$this->TooManyRequests++;
                    if($this->TooManyRequests >= 3) exit("\nToo Many Requests error (429)!\n");
                }
                */

                $obj = json_decode($json); //print_r($obj); exit;
                /**/
                foreach($obj->results as $r) {
                    $t = $r->taxon;
                    $rek = array();
                    $rek["id"]                  = $t->id;
                    $rek["rank"]                = $t->rank;
                    $rek["name"]                = $t->name;
                    $rek["observations_count"]  = $t->observations_count;
                    $rek["species_count"]       = $r->count;
                    $rek["iconic_taxon_name"]   = @$t->iconic_taxon_name ? $t->iconic_taxon_name : "unknown";
                    $rek["parent_id"]           = $t->parent_id;
                    $rek["ancestry"]            = $t->ancestry;
                    self::save_to_dump($rek, $this->dump_file[$grade]);    
                }
            }
            // if($page >= 6) break; //dev only
        } //end for loop
    }
    private function save_to_dump($rec, $filename)
    {
        if(isset($rec["observations_count"]) && is_array($rec)) {
            $fields = array_keys($rec);
            $data = "";
            if(!is_file($filename)) {
                foreach($fields as $field) $data .= $field . "\t";
                if(!($WRITE = Functions::file_open($filename, "a"))) return;
                fwrite($WRITE, $data . "\n");
                fclose($WRITE);    
            }
            $data = "";
            foreach($fields as $field) $data .= $rec[$field] . "\t";
            if(!($WRITE = Functions::file_open($filename, "a"))) return;
            fwrite($WRITE, $data . "\n");
            fclose($WRITE);
        }
    }
    // =========================================================================== start 2nd part
    function parse_tsv_then_generate_dwca()
    {
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

        // $save = array();
        // $save['taxon_id'] = $taxonID;
        // $save['source'] = $rek['source_url'];
        // $save['bibliographicCitation'] = $this->bibliographicCitation;
        // $mType = 'http://purl.obolibrary.org/obo/RO_0002303';
        // $mValue
        // $save['measurementRemarks'] = ""; //No need to put measurementRemarks coming from Biology. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65452&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65452
        // $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        // $this->func->add_string_types($save, $mValue, $mType, "true");
    }
    private function gen_iNat_info_taxa_using_DwCA()
    {
        echo "\nGenerate taxon info list...\n";
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $options = $this->download_options_INAT;
        $options['expire_seconds'] = 60*60*24*30*3; //3 months cache
        $paths = $func->extract_archive_file($this->dwca['inaturalist-taxonomy'], "meta.xml", $options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit; //debug only
        */

        // /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_54504/',
            'temp_dir'     => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_54504/'
        );
        // */

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
    private function process_table($meta, $what, $local_dwca = false)
    {
        if($meta)           $csv_file = $meta->file_uri;
        elseif($local_dwca) $csv_file = $local_dwca;
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
                // print_r($rec); //exit;
                /*Array(
                    [id] => 27459
                    [taxonID] => https://www.inaturalist.org/taxa/27459
                    [identifier] => https://www.inaturalist.org/taxa/27459
                    [parentNameUsageID] => https://www.inaturalist.org/taxa/27444
                    [kingdom] => Animalia
                    [phylum] => Chordata
                    [class] => Amphibia
                    [order] => Caudata
                    [family] => Plethodontidae
                    [genus] => Batrachoseps
                    [specificEpithet] => attenuatus
                    [infraspecificEpithet] => 
                    [modified] => 2019-11-23T09:42:54Z
                    [scientificName] => Batrachoseps attenuatus
                    [taxonRank] => species
                    [references] => http://research.amnh.org/vz/herpetology/amphibia/?action=names&taxon=Batrachoseps+attenuatus
                )*/
                if($what == "gen taxa info") $this->inat_taxa_info[$rec['id']] = array('s' => $rec['scientificName'], 'r' => $rec['taxonRank'], 'p' => pathinfo($rec['parentNameUsageID'], PATHINFO_FILENAME));
                elseif($what == "xxx") {}
            }
        }
    }
    private function write_dwca_from_assembled_array()
    {
        foreach($this->assembled as $taxonID => $totals) {
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
    private function parse_tsv_file($file, $what, $quality_grade = false)
    {   echo "\nReading file, task: [$what]...\n";
        $i = 0; $final = array();
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");
                // print_r($rec); exit("\nelix 200\n");
                if($what == 'process research grade tsv') {
                    /*Array(
                        [id] => 47219
                        [rank] => species
                        [name] => Apis mellifera
                        [observations_count] => 411499
                        [species_count] => 393719
                        [iconic_taxon_name] => Insecta
                        [parent_id] => 578086
                        [ancestry] => 48460/1/47120/372739/47158/184884/47201/124417/326777/47222/630955/47221/199939/538904/47220/578086
                        [] => 
                    )*/
                    self::prep_write_taxon($rec);
                    self::write_MoF($rec);
                }
                elseif($what == 'assemble data from 3 TSVs' && $quality_grade) {
                    $this->assembled[$rec['id']][$quality_grade] = @$this->assembled[$rec['id']][$quality_grade] + $rec['observations_count'];
                    $this->assembled[$rec['id']]['r'] = $rec['rank'];
                    $this->assembled[$rec['id']]['s'] = $rec['name'];
                    $this->assembled[$rec['id']]['p'] = $rec['parent_id'];
                    $this->assembled[$rec['id']]['a'] = $rec['ancestry'];
                }
            }
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
    // private function process_table($meta, $task)
    // {   //print_r($meta);
    //     echo "\n\nRunning $task..."; $i = 0;
    //     foreach(new FileIterator($meta->file_uri) as $line => $row) {
    //         $i++; if(($i % 300000) == 0) echo "\n".number_format($i);
    //         if($meta->ignore_header_lines && $i == 1) continue;
    //         if(!$row) continue;
    //         // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
    //         $tmp = explode("\t", $row);
    //         $rec = array(); $k = 0;
    //         foreach($meta->fields as $field) {
    //             if(!$field['term']) continue;
    //             $rec[$field['term']] = $tmp[$k];
    //             $k++;
    //         }
    //         print_r($rec); exit;
    //     }
    // }
}
?>