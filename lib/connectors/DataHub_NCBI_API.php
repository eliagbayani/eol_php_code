<?php
namespace php_active_record;
/*  datahub_NCBI.php
*/
class DataHub_NCBI_API
{
    function __construct($folder = false)
    {
        $this->download_options_NCBI = array('resource_id' => "723_ncbi", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 2000000, 'timeout' => 10800*2, 'download_attempts' => 1); //3 months to expire
        // $this->download_options_NCBI['expire_seconds'] = 60*60*24;
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }

        $this->NCBI_api['taxa'] = "https://api.NCBI.org/v1/observations/species_counts?taxon_is_active=true&hrank=XRANK&lrank=XRANK&iconic_taxa=XGROUP&quality_grade=XGRADE&page=XPAGE"; //defaults to per_page = 500
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "NCBI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $this->save_path = $save_path;

        $this->reports_path = $save_path;
        $this->taxon_page = "https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id="; //e.g. 8045
        $this->dwca['NCBI-taxonomy'] = "https://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip";
        $this->debug = array();

        /* copied template
        $this->quality_grades = array('research', 'needs_id', 'casual'); //orig; obsolete
        $this->quality_grades = array('research'); //new orig

        $this->groups = array("Insecta", "Plantae", "Actinopterygii", "Amphibia", "Arachnida", "Aves", "Chromista", "Fungi", "Mammalia", "Mollusca", "Reptilia", "Protozoa", "unknown"); //orig

        $this->include_ranks = array('species', 'family', 'genus'); //orig;
        // $this->include_ranks = array('species'); //orig;

        $this->NCBI_api['family_genus'] = 'https://api.NCBI.org/v1/observations/species_counts?rank=XRANK&page=XPAGE';

        // from NCBI, not recognized by our harvest: May 20, 2024
        $this->with_breaks_YN = true; //true for periodic normal operation //false if cached already
        */
    }
    function start()
    {
        // step 1: assemble taxa
        self::gen_NCBI_info_taxa_using_ZIP_file();

        print_r($this->debug);
        print_r($this->taxID_name_info); echo "\ncount taxID_name_info: ".count($this->taxID_name_info)."\n";
    }
    private function gen_NCBI_info_taxa_using_ZIP_file()
    {   echo "\nGenerate taxon info list...\n";
        /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $options = $this->download_options_NCBI;
        $options['expire_seconds'] = 60*60*24*30*3; //3 months cache
        $options['expire_seconds'] = false; //dev only
        $paths = $func->extract_zip_file($this->dwca['NCBI-taxonomy'], $options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit; //debug only
        */

        // /* development only
        $paths = Array(
            'extracted_file'    => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_73458/taxdmp',
            'temp_dir'          => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_73458/',
            'temp_file_path'    => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_73458/taxdmp.zip'
        );
        // */

        $temp_dir = $paths['temp_dir'];
        // self::parse_tsv_file($temp_dir . 'names.dmp', "process names.dmp");
        self::parse_tsv_file($temp_dir . 'nodes.dmp', "process nodes.dmp");

        /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
    }
    private function parse_tsv_file($file, $what, $quality_grade = false)
    {   echo "\nReading file, task: [$what]...[$quality_grade]\n";
        $i = 0; $final = array();
        $separator = "	|	";
        $separator = "	|";

        if($what == 'process names.dmp') {
            $fields = array('tax_id', 'name_txt', 'unique name', 'name class');
            // tax_id		-- the id of node associated with this name
            // name_txt	    -- name itself
            // unique name  -- the unique variant of this name if name not unique
            // name class	-- (synonym, common name, ...)
            /*Array([name class values] => Array(
                        [synonym] => 
                        [scientific name] => 
                        [blast name] => 
                        [authority] => 
                        [genbank common name] => 
                        [in-part] => 
                        [type material] => 
                        [equivalent name] => 
                        [includes] => 
                        [common name] => 
                        [acronym] => 
                        [genbank acronym] => 
                    )
            )*/
        }
        elseif($what == 'process nodes.dmp') {
            /* nodes.dmp file consists of taxonomy nodes. The description for each node includes the following
            fields:
                tax_id					-- node id in GenBank taxonomy database
                parent tax_id				-- parent node id in GenBank taxonomy database
                rank					-- rank of this node (superkingdom, kingdom, ...) 
                embl code				-- locus-name prefix; not unique
                division id				-- see division.dmp file
                inherited div flag  (1 or 0)		-- 1 if node inherits division from parent
                genetic code id				-- see gencode.dmp file
                inherited GC  flag  (1 or 0)		-- 1 if node inherits genetic code from parent
                mitochondrial genetic code id		-- see gencode.dmp file
                inherited MGC flag  (1 or 0)		-- 1 if node inherits mitochondrial gencode from parent
                GenBank hidden flag (1 or 0)            -- 1 if name is suppressed in GenBank entry lineage
                hidden subtree root flag (1 or 0)       -- 1 if this subtree has no sequence data yet
                comments				-- free-text comments and citations
            */
            $fields = array('tax_id', 'parent tax_id', 'rank', 'embl code', 'division id', 'inherited div flag', 'genetic code id', 'inherited GC flag', 'mitochondrial genetic code id', 'inherited MGC flag', 'GenBank hidden flag', 'hidden subtree root flag', 'comments');
        }

        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % 500000) == 0) echo "\n $i ";
            // if($i == 1) $fields = explode($separator, $row);
            if(true) {
                if(!$row) continue;
                $tmp = explode($separator, $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstop muna\n");
                if($what == 'xxx write dwca') {
                    /* copied template
                    self::prep_write_taxon($rec);
                    self::write_MoF($rec);
                    */
                }
                elseif($what == 'process names.dmp') {
                    /*Array(
                        [tax_id] => 1
                        [name_txt] => all
                        [unique name] => 
                        [name class] => synonym
                    )*/
                    $this->debug['name class values'][$rec['name class']] = '';
                    $tax_id = $rec['tax_id'];
                    if($rec['name class'] == 'scientific name') $this->taxID_name_info[$tax_id]['sn'] = $rec['name_txt'];
                }
                elseif($what == 'process nodes.dmp') {
                    /*Array(
                        [tax_id] => 33434
                        [parent tax_id] => 33416
                        [rank] => species
                        [embl code] => HC
                        [division id] => 1
                        [inherited div flag] => 1
                        [genetic code id] => 1
                        [inherited GC flag] => 1
                        [mitochondrial genetic code id] => 5
                        [inherited MGC flag] => 1
                        [GenBank hidden flag] => 1
                        [hidden subtree root flag] => 0
                        [comments] => code compliant; specified
                    )*/
                    $tax_id = $rec['tax_id'];
                    $rank = $rec['rank'];
                    $this->debug['rank values'][$rank] = '';
                    $this->taxID_name_info[$tax_id]['p'] = $rec['parent tax_id'];
                    $this->taxID_name_info[$tax_id]['r'] = $rank;
                }
            }
        }
    }

    // ================================================================= below copied template
    function startx()
    {
        foreach($this->quality_grades as $grade) { //delete old files
            $this->dump_file[$grade] = $this->save_path . "/datahub_NCBI_grade_".$grade.".txt";
            if(is_file($this->dump_file[$grade])) unlink($this->dump_file[$grade]);    
        }
        foreach($this->quality_grades as $grade) { //start main operation: get observation via API and save into TSV dumps.
            foreach($this->include_ranks as $include_rank) {
                if($include_rank == 'species') {
                    foreach($this->groups as $group) {
                        echo "\nProcessing [$group]...[$grade]...[$include_rank]\n";
                        self::get_NCBI_taxa_observation_using_API($group, $grade, $include_rank);
                        if($this->with_breaks_YN) {
                            echo "\nEvery group, sleep 2 min.\n";
                            sleep(60*2); //mins interval per group    
                        }
                    }            
                }
                else { //family and genus
                    echo "\nProcessing [$grade]...[$include_rank]\n";
                    $group = false;
                    self::get_NCBI_taxa_observation_using_API($group, $grade, $include_rank);
                    if($this->with_breaks_YN) {
                        echo "\nEvery group, sleep 2 min.\n";
                        sleep(60*2); //mins interval per group    
                    }
                }
            }
        }
    }
    function get_NCBI_taxa_observation_using_API($group, $grade, $include_rank) //not advisable to use, bec. of the 10,000 limit page coverage
    {
        if($include_rank == 'species') {
            $main_url = str_replace("XGROUP", $group, $this->NCBI_api['taxa']);
            $main_url = str_replace("XGRADE", $grade, $main_url);
            $main_url = str_replace("XRANK", $include_rank, $main_url);
        }
        else $main_url = str_replace("XRANK", $include_rank, $this->NCBI_api['family_genus']); //for 'family' and 'genus'

        $page = 1;
        $url = str_replace("XPAGE", $page, $main_url);

        $json = Functions::lookup_with_cache($url, $this->download_options_NCBI);
        $obj = json_decode($json); // print_r($obj); //exit;
        $total = $obj->total_results;
        $pages = ceil($total / 500);  echo "\ntotal_results: [$total]\ntotal pages: [$pages]\n";

        for($page = 1; $page <= $pages; $page++) {

            if($this->with_breaks_YN) {
                if(($page % 50) == 0) {
                    echo "\nEvery 50 calls, sleep 2 min.\n";
                    sleep(60*2); //mins interval
                }    
            }

            $url = str_replace("XPAGE", $page, $main_url);
            if($json = Functions::lookup_with_cache($url, $this->download_options_NCBI)) {

                /* NCBI special case - not reliable needs more test
                if(stripos($json, 'error') !== false) { //Too Many Requests           --- //string is found
                    echo "\n[$json]\n";
                    echo "\nNCBI special error: Too Many Requests\n"; exit("\nexit muna, remove NCBI from the list of dbases.\n");
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
                    $rek["count"]               = $r->count;
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
        $this->debug = array();
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);

        foreach($this->quality_grades as $grade) $this->dump_file[$grade] = $this->save_path . "/datahub_NCBI_grade_".$grade.".txt"; //file variable assignment

        // step 1: reads NCBI taxonomy and gen. info taxa list
        self::gen_NCBI_info_taxa_using_DwCA(); //generates $this->NCBI_taxa_info

        // step 2: loop a tsv dump and write taxon and MoF archive --- if u want to gen. a DwCA from one dump
        self::parse_tsv_file($this->dump_file['research'], "process research grade tsv"); 

        /* OBSOLETE
        // step 2: loop each tsv file ('research', 'needs_id', 'casual'), and create info_list for DwCA writing
        self::assemble_data_from_3TSVs();
        // print_r($this->assembled); exit;

        // step 3: write DwCA from the big assembled array $this->assembled
        self::write_dwca_from_assembled_array();
        */
        
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
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
            // if(stripos($str, "Callisaurus	genus") !== false) {echo("\n$str\n");}  //string found --- good debug                
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
                // print_r($rec); //exit;
                /*Array(
                    [id] => 27459
                    [taxonID] => https://www.NCBI.org/taxa/27459
                    [identifier] => https://www.NCBI.org/taxa/27459
                    [parentNameUsageID] => https://www.NCBI.org/taxa/27444
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
                if($what == "gen taxa info") $this->NCBI_taxa_info[$rec['id']] = array('s' => $rec['scientificName'], 'r' => $rec['taxonRank'], 'p' => pathinfo($rec['parentNameUsageID'], PATHINFO_FILENAME));
                elseif($what == "xxx") {}
            }
        }
    }
    private function write_MoF($rec)
    {   //print_r($rec); exit;
        /*Array(
            [id] => 47219
            [rank] => species
            [name] => Apis mellifera
            [observations_count] => 411499
            [count] => 393719
            [iconic_taxon_name] => Insecta
            [parent_id] => 578086
            [ancestry] => 48460/1/47120/372739/47158/184884/47201/124417/326777/47222/630955/47221/199939/538904/47220/578086
            [] => 
        )*/
        $taxonID = $rec['id'];
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $this->taxon_page . $taxonID;
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 

        if($rec['rank'] == 'species') {
            $mType = 'http://eol.org/schema/terms/NumberOfSequencesInGenBank';
            $mValue = $rec['count']; //
            $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
            $this->func->add_string_types($save, $mValue, $mType, "true");    
        }

        $mType = 'http://eol.org/schema/terms/NumberOfSequencesInGenBank';
        $mValue = $rec['observations_count'];
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
            if($r = @$this->NCBI_taxa_info[$taxon_id]) {
                $save_taxon = array();
                $save_taxon = array('taxonID' => $taxon_id, 'scientificName' => $r['s'], 'taxonRank' => $r['r'] , 'parentNameUsageID' => @$ancestry[$i+1]);
                self::write_taxon($save_taxon);        
            }
        }
    }
    private function write_taxon($rec)
    {
        $rank = in_array($rec['taxonRank'], $this->rank_set_2_blank) ? "" : $rec['taxonRank'];

        $taxonID = $rec['taxonID'];
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $taxonID;
        $taxon->scientificName      = $rec['scientificName'];
        $taxon->taxonRank           = $rank;
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