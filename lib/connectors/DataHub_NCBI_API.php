<?php
namespace php_active_record;
/*  datahub_NCBI.php
*/
class DataHub_NCBI_API
{
    function __construct($folder = false)
    {
        $this->download_options_NCBI = array('resource_id' => "723_ncbi", 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 2000000, 'timeout' => 10800*2, 'download_attempts' => 1); //3 months to expire
        $this->download_options_NCBI['expire_seconds'] = false;
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }

        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "NCBI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $this->download_path = $save_path;
        $this->dump_file = $save_path . "/compiled_taxa.txt";

        $this->reports_path = $save_path;
        $this->taxon_page  = "https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=";                //e.g. 8045 -- safe choice
        $this->taxon_page2 = "https://www.ncbi.nlm.nih.gov/nucleotide/?term=txidXXTAXIDXX[Organism:exp]";   //e.g. 8045 -- suggested choice

        /* suggested source URL: taxon_page : Can't fully use it since the count from dump file doesn't tally
        https://www.ncbi.nlm.nih.gov/nucleotide/?term=txid11[Organism:exp]
        https://www.ncbi.nlm.nih.gov/nucleotide/?term=txid24[Organism:exp]
        suggested API call: been using it
        https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&term=txid11[Organism:exp]
        https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&term=txid24[Organism:exp]
        */

        $this->dwca['NCBI-taxonomy']    = "https://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip";
        $this->api_call                 = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&term=txidXXTAXIDXX[Organism:exp]';
        $this->debug = array();

        // /* for species-level using dumps to get totals:
        $this->big_file  = '/Volumes/Crucial_2TB/eol_php_code_tmp2/nucl_gb.accession2taxid';  //12.47 GB
        // $this->big_file2 = '/Volumes/Crucial_2TB/eol_php_code_tmp2/nucl_wgs.accession2taxid'; //32.62 GB --- NOT USED

        $this->dumpfile["prot.accession2taxid"] = "https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.INDEX-NUM.gz";
        $this->dumpfile["prot.accession2taxid"] = "http://localhost/other_files2/dumps_GGI/NCBI/prot/prot.accession2taxid.FULL.INDEX-NUM.gz"; //dev only
        // */

        /* will add this to jenkins. We need this if we want totals from dump files.
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.1.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.2.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.3.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.4.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.5.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.6.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.7.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.8.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.9.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.10.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.11.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.12.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.13.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.14.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.15.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.16.gz
        wget -c https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/prot.accession2taxid.FULL.17.gz
        */

        date_default_timezone_set('America/New_York'); //used in main operation
        // date_default_timezone_set('Asia/Taipei');
    }
    /* works OK. A way to reference a function from: vendor/eol_content_schema_v2
    print_r(\eol_schema\Taxon::$ranks); exit;
    */
    function start()
    {
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);

        // /* step 1: assemble taxa: Was first run in MacStudio. Generates the taxonomy file (compiled_taxa.txt) and just scp it to eol-archive.
        self::gen_NCBI_taxonomy_using_ZIP_file();
        print_r($this->taxa_info[1]); //exit("\nelix 2\n");
                // testing only
                // $test_species = array(9913, 32630, 1423, 5076, 562);
                // foreach($test_species as $taxid) { echo "\n ----- processing [$taxid]\n";
                //     $ancestry = self::get_ancestry_for_taxonID($taxid);
                //     print_r($ancestry);
                //     echo "\n ----- end [$taxid]";
                // }        
                // exit("\nelix 3\n");
        // --- end step 1 --- */

        // /* step 2: process genus and family; loop tsv file and use API. For species-level use boolean mtype = "http://eol.org/schema/terms/SequenceInGenBank" (boolean)
        self::parse_tsv_file($this->dump_file, "process genus family from compiled taxonomy");
        // exit("\n-caching ends-\n"); //comment in real operation , dev only cache only
        // --- end step 2 --- */

        // [genus] => 109270
        // [species] => 2117681
        // [family] => 10403

        /* step 3: process the big file - for species-level taxa. No API call but just read the big dump file.
        self::parse_tsv_file($this->big_file, "proc big file gen. totals[taxid]");      //the correct tsv file to use --- generates $this->totals[taxid] = count
        */

        /* step 4: loop the many files dump to get the totals
        for($i = 1; $i <= 17; $i++) {
            $url = str_replace('INDEX-NUM', $i, $this->dumpfile["prot.accession2taxid"]);
            echo "\n$i. $url\n";
            self::process_dump_file($url, $i);
        }
        */

        /* check results: does not need this if species-level taxa is only boolean YN
        $tests_id_count['6'] = 6259;
        $tests_id_count['7'] = 926;
        $tests_id_count['9'] = 7770;
        $tests_id_count['10'] = 1512;
        $tests_id_count['11'] = 19;
        $tests_id_count['13'] = 417;
        $tests_id_count['14'] = 143;
        $tests_id_count['16'] = 1425;
        $tests_id_count['17'] = 303;
        $tests_id_count['18'] = 617;
        $tests_id_count['19'] = 28;
        $tests_id_count['22'] = 99857;
        $tests_id_count['23'] = 1763;
        $tests_id_count['24'] = 2974;
        $tests_id_count['8049'] = 0;
        $tests_id_count['454919'] = 0;
        $tests_id_count['21'] = 0;
        foreach($tests_id_count as $id => $count) {
            echo "\n $id: | dump: ".@$this->totals[$id]." | API: ".$count."\n";
        }
        exit("\n-end test-\n");
        */

        /* only for species-level counts based on dump files
        self::write_species_level_MoF();
        */

        // self::parse_tsv_file($this->big_file2, "proc big file gen. totals[taxid]");  //NOT to be used --- generates $this->taxid_accession
        // echo "\n 8049: ".$this->totals[8049]."\n";
        // echo "\n 454919: ".$this->totals[454919]."\n";
        // echo "\n 21: ".$this->totals[21]."\n";

        // below not used at all:
        // echo "\n 8049: ".count($this->taxid_accession[8049])."\n";
        // echo "\n 454919: ".count($this->taxid_accession[454919])."\n";
        // echo "\n 21: ".count($this->taxid_accession[21])."\n";
        // --- end step 3 --- */

        /*
        8049: 367455
        454919: 40
        21: 5
        */
        print_r($this->debug);
        // print_r($this->taxa_info); 
        echo "\ncount taxa_info: ".count(@$this->taxa_info)."\n";
        $this->archive_builder->finalize(TRUE);
    }
    private function process_dump_file($url, $index_num)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();

        $source_remote_url          = $url;
        $destination_file           = $this->download_path."downloaded_".$index_num.".gz";
        $check_file_or_folder_name  = "prot.accession2taxid.FULL." . $index_num;
        $check_file_or_folder_name  = "downloaded_" . $index_num;

        $paths = $func->download_general_dump($source_remote_url, $destination_file, $this->download_path, $check_file_or_folder_name); //exit("\ndownload done.\n");
        if($paths['archive_path'] && $paths['temp_dir']) {
            $this->tsv_file = $paths['archive_path']; //this is a file not a folder
            $temp_dir       = $paths['temp_dir']; //actually not used here. No files inside.
            self::parse_tsv_file($this->tsv_file, "proc big file gen. totals[taxid]");

            // "archive_path"  => "/Volumes/Crucial_2TB/other_files2/dumps_GGI/NCBI/downloaded_1", //this is a file not a folder.
            // "temp_dir"      => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_42492/"

            unlink($this->tsv_file);
            // remove temp dir
            recursive_rmdir($temp_dir);
            echo ("\n temporary directory removed: " . $temp_dir);
        }
        else exit("\nTerminated. Files are not ready.\n");
    }
    private function gen_NCBI_taxonomy_using_ZIP_file()
    {   echo "\nGenerate taxon info list...\n";
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $options = $this->download_options_NCBI;
        $options['expire_seconds'] = 60*60*24*30*3; //3 months cache
        // $options['expire_seconds'] = false; //dev only
        $paths = $func->extract_zip_file($this->dwca['NCBI-taxonomy'], $options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        print_r($paths); //exit; //debug only
        // */

        /* development only
        $paths = Array(
            'extracted_file'    => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_73458/taxdmp',
            'temp_dir'          => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_73458/',
            'temp_file_path'    => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_73458/taxdmp.zip'
        );
        */

        $temp_dir = $paths['temp_dir'];
        if(is_file($this->dump_file)) unlink($this->dump_file);
        self::parse_tsv_file($temp_dir . 'names.dmp', "process names.dmp");
        self::parse_tsv_file($temp_dir . 'nodes.dmp', "process nodes.dmp");

        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    }
    private function parse_tsv_file($file, $what, $quality_grade = false)
    {   echo "\nReading file, task: [$what]...[$quality_grade]\n";
        $i = 0; $final = array();
        $separator = "	|	";
        $separator = "	|";
        $modulo = 500000;
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
        elseif($what == 'proc big file gen. totals[taxid]') { //per: https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/README
            // $fields = array('accession', 'accession.version', 'taxid', 'gi');
            $separator = "\t";
            $modulo = 5000000;
        }
        elseif($what == 'process genus family from compiled taxonomy') {
            $separator = "\t";
            $modulo = 100000;
        }

        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % $modulo) == 0) echo "\n [$what] row: $i ";

            if(in_array($what, array('proc big file gen. totals[taxid]', 'process genus family from compiled taxonomy'))) {
                if($i == 1) {
                    $fields = explode($separator, $row); 
                    continue;
                }
            }

            /* during cache only, dev only
            if(in_array($what, array('process genus family from compiled taxonomy'))) {
                if($i <= 400000) continue;
            }
            */

            if(true) {
                if(!$row) continue;
                $tmp = explode($separator, $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");

                if($what == 'process names.dmp') {
                    /*Array(
                        [tax_id] => 1
                        [name_txt] => all
                        [unique name] => 
                        [name class] => synonym
                    )*/
                    $this->debug['name class values'][$rec['name class']] = '';
                    $taxid = $rec['tax_id'];
                    if($rec['name class'] == 'scientific name') {
                        $this->taxa_info[$taxid]['n'] = $rec['name_txt'];
                    }
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
                    $taxid = $rec['tax_id'];
                    if($taxid == 1 || $this->taxa_info[$taxid]['n'] == 'root') $rec['parent tax_id'] = ""; //important line
                    $rank = $rec['rank'];                    
                    @$this->debug['rank totals'][$rank]++;

                    $this->taxa_info[$taxid]['p'] = $rec['parent tax_id'];
                    $this->taxa_info[$taxid]['r'] = $rank;

                    // if(in_array($rank, array('family', 'genus', 'species'))) {
                        $rek = array();
                        $rek["id"]                  = $taxid;
                        $rek["rank"]                = $rank;
                        $rek["name"]                = $this->taxa_info[$taxid]['n'];
                        $rek["parent_id"]           = $this->taxa_info[$taxid]['p'];
                        if(self::valid_string_name($rek["name"])) self::save_to_dump($rek, $this->dump_file);
                    // }
                }
                elseif($what == 'proc big file gen. totals[taxid]') { //for species-level taxa using dumps
                    /*Array(
                        [accession] => A00001
                        [accession.version] => A00001.1
                        [taxid] => 10641
                        [gi] => 58418
                    )
                    Array(
                        [accession.version] => 0308206A
                        [taxid] => 8058
                    )*/
                    $taxid = $rec['taxid'];
                    $tmp_name = @$this->taxa_info[$taxid]['n'];
                    $tmp_rank = @$this->taxa_info[$taxid]['r'];
                    if($tmp_rank == 'species' && $tmp_name) {
                        if(self::valid_string_name($tmp_name)) @$this->totals[$taxid]++;
                    }

                    // $accession = $rec['accession'];
                    // $this->taxid_accession[$taxid][$accession] = ''; //dev only
                }
                elseif($what == 'process genus family from compiled taxonomy') { //for genus family (counts from API) and species (boolean mtype)
                    self::process_genus_family($rec);
                    // if($i > 15) break; //debug only
                }
            }
        }
    }
    private function valid_string_name($tmp_name)
    {
        if($tmp_name) {
            if(!ctype_upper(substr($tmp_name,0,1))) return false;
            if(stripos($tmp_name, ' sp.') !== false) return false; //string is found
            if(stripos($tmp_name, ' aff.') !== false) return false; //string is found
            if(stripos($tmp_name, ' cf.') !== false) return false; //string is found
            if(stripos($tmp_name, ' of ') !== false) return false; //string is found
            if(stripos($tmp_name, ' x ') !== false) return false; //string is found
            if(stripos($tmp_name, 'unidentified') !== false) return false; //string is found
            if(stripos($tmp_name, 'unclassified') !== false) return false; //string is found
            if(stripos($tmp_name, 'uncultured') !== false) return false; //string is found
            if(stripos($tmp_name, 'undetermined') !== false) return false; //string is found
            if(stripos($tmp_name, 'undescribed') !== false) return false; //string is found
            if(stripos($tmp_name, 'uncultivated') !== false) return false; //string is found
            if(stripos($tmp_name, 'unculturable') !== false) return false; //string is found
            if(stripos($tmp_name, 'unspecified') !== false) return false; //string is found
            if(stripos($tmp_name, 'unknown') !== false) return false; //string is found
            if(stripos($tmp_name, 'untyped') !== false) return false; //string is found
            if(stripos($tmp_name, 'vector') !== false) return false; //string is found
            if(stripos($tmp_name, 'bacterium') !== false) return false; //string is found
            if(stripos($tmp_name, 'phage') !== false) return false; //string is found
            if(stripos($tmp_name, 'virus') !== false) return false; //string is found
            if(stripos($tmp_name, 'Human ') !== false) return false; //string is found
            if(stripos($tmp_name, 'synthetic') !== false) return false; //string is found
            if(stripos($tmp_name, 'artificial') !== false) return false; //string is found
            if(stripos($tmp_name, 'construct') !== false) return false; //string is found
            if(stripos($tmp_name, 'sequence') !== false) return false; //string is found
            if(stripos($tmp_name, 'incertae') !== false) return false; //string is found
            if(stripos($tmp_name, 'modified') !== false) return false; //string is found
            if(stripos($tmp_name, 'other ') !== false) return false; //string is found
            if(stripos($tmp_name, 'strain ') !== false) return false; //string is found
            if(stripos($tmp_name, 'clone ') !== false) return false; //string is found
            return true;
        }
        return false;
    }
    private function process_genus_family($rec)
    {   /*Array(
            [id] => 6
            [rank] => genus
            [name] => Azorhizobium
            [parent_id] => 335928
            [] => 
        )*/
        $rank = $rec['rank'];
        if(!in_array($rank, array('genus', 'family', 'species'))) return; //orig main operation

        if(in_array($rank, array('genus', 'family'))) {
            if(self::correct_time_2call_api_YN()) {
                echo " [OK time to call API] ";
                self::proceed_call_api($rec);
            } 
            else {
                echo "\nNot correct time to call API\n";
                echo "\nsleep 1 hr.\n";
                sleep(60*60*1);
                return;
            }    
        }
        elseif($rank == 'species') {
            $save = array();
            $save['taxonID'] = $rec['id'];
            $save['scientificName'] = $rec['name'];
            $save['taxonRank'] = $rec['rank'];
            $save['parentNameUsageID'] = $rec['parent_id'];
            self::write_taxon($save);

            // add ancestry to taxon.tab
            $ancestry = self::get_ancestry_for_taxonID($save['taxonID']);
            if($ancestry) self::write_taxa_4_ancestry($ancestry);
            
            self::write_MoF($rec);
        }

    }
    private function proceed_call_api($rec)
    {
        //print_r($rec); exit("\nelix 1\n");
        $url = str_replace("XXTAXIDXX", $rec['id'], $this->api_call);
        $xml = Functions::lookup_with_cache($url, $this->download_options_NCBI);
        // <Count>367603</Count>1
        $save = array();
        if(preg_match("/<Count>(.*?)<\/Count>/ims", $xml, $arr)){
            $rec['count'] = trim($arr[1]);
            echo " -[".$rec['count']."] ".$rec['rank']." - ";
            // print_r($rec); exit;
        }

        // /* NCBI special case - will check it this works
        if(stripos($xml, 'exceed') !== false) { //string is found
            echo "\n[$xml]\n";
            echo "\nNCBI special error: Too Many Requests\n"; exit("\nExit muna, Investigate error.\n");
            sleep(60*10); //10 mins
            @$this->TooManyRequests++;
            if($this->TooManyRequests >= 3) exit("\nToo Many Requests error (429)!\n");
        }
        // */

        /* Array(
            [id] => 6
            [rank] => genus
            [name] => Azorhizobium
            [parent_id] => 335928
            [] => 
        )*/
        if($rec['count']) {
            $save = array();
            $save['taxonID'] = $rec['id'];
            $save['scientificName'] = $rec['name'];
            $save['taxonRank'] = $rec['rank'];
            $save['parentNameUsageID'] = $rec['parent_id'];
            self::write_taxon($save);

            // add ancestry to taxon.tab
            $ancestry = self::get_ancestry_for_taxonID($save['taxonID']);
            if($ancestry) self::write_taxa_4_ancestry($ancestry);

            self::write_MoF($rec);    
        }
    }
    private function write_species_level_MoF()
    {   echo "\nStart write species-level...\n";
        $total = count($this->totals); $i = 0;
        foreach($this->totals as $taxid => $count) { $i++;
            if(($i % 500000) == 0) echo "\n species-level $i of $total [$taxid] ";
            if(!$count) continue;
            if($sciname = @$this->taxa_info[$taxid]['n']) {
                $save = array();
                $save['taxonID'] = $taxid;
                $save['scientificName']     = $sciname;
                $save['taxonRank']          = @$this->taxa_info[$taxid]['r'];
                $save['parentNameUsageID']  = @$this->taxa_info[$taxid]['p'];
                self::write_taxon($save);

                // add ancestry to taxon.tab
                $ancestry = self::get_ancestry_for_taxonID($taxid);
                if($ancestry) self::write_taxa_4_ancestry($ancestry);
                
                $rec = array();
                $rec['id'] = $taxid;
                $rec['count'] = $count;
                self::write_MoF($rec);    
            }
            else $this->debug['species-level taxid not found'][$taxid] = '';
            // if($i >= 5) break; //debug only
        }
    }
    private function write_taxon($rec)
    {   //print_r($rec);
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
    public function get_ancestry_for_taxonID($taxonID)
    {
        $final = array(); $i = 0;
        while(true) { $i++; //echo "~[$i][$taxonID]~";
            if($taxonID == 1) break;
            elseif($taxonID == "") break;

            if($i >= 100) {
                $i = 0;
                $this->debug['reached 100: should not go here'][$taxonID] = ''; //should not go here
                break;
            }

            if($val = @$this->taxa_info[$taxonID]['p']) {
                $final[] = $val;
                $taxonID = $val;
                if($taxonID == 1) break; //newly added
                elseif($taxonID == "") break;
            }
            else {
                if($taxonID == 1) break;
                elseif($taxonID == "") break;
                else {
                    $this->debug['get_ancestry_thru_api'][$taxonID] = '';
                    echo "\nwent to get_ancestry_thru_api [$taxonID]\n"; break;
                    /*
                    if($val = self::get_ancestry_thru_api($taxonID)) { //normal operation
                        echo " [$val] ";
                        $final[] = $val;
                        $taxonID = $val;
                    }
                    else break;
                    */
                }
            }
        }
        // echo "\nancestry: "; print_r($final); //exit;
        return $final;
    }
    public function write_taxa_4_ancestry($ancestry)
    {
        // print_r($ancestry); exit("\nelix 1\n");
        foreach($ancestry as $tax_id) {
            if($tax_id == 1) { // the parentmost, the root
                $save = array();
                $save['taxonID'] = $tax_id;
                $save['scientificName'] = 'root';
                $save['taxonRank'] = '';
                $save['parentNameUsageID'] = '';
                self::write_taxon($save);    
                break;
            }
            else {
                if(!@$this->taxa_info[$tax_id]['n']) continue;
                $save = array();
                $save['taxonID'] = $tax_id;
                $save['scientificName'] = $this->taxa_info[$tax_id]['n'];
                $save['taxonRank'] = $this->taxa_info[$tax_id]['r'];
                $save['parentNameUsageID'] = $this->taxa_info[$tax_id]['p'];
                self::write_taxon($save);    
            }
        }
    }
    private function write_MoF($rec)
    {   //print_r($o); exit;
        $taxonID = $rec['id'];
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $this->taxon_page . $taxonID;                             //safe choice
        $save['source'] = str_replace("XXTAXIDXX", $taxonID, $this->taxon_page2);   //suggested choice
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 

        if($rec['rank'] == 'species') {
            $mType = 'http://eol.org/schema/terms/SequenceInGenBank';
            $mValue = "http://eol.org/schema/terms/yes";
        }
        else {
            $mType = 'http://eol.org/schema/terms/NumberOfSequencesInGenBank';
            $mValue = $rec['count'];    
        }
        
        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        $this->func->add_string_types($save, $mValue, $mType, "true");    
    }
    private function correct_time_2call_api_YN()
    {   //return true; //debug only
        /* good debug
        if($timezone_object = date_default_timezone_get()) echo 'date_default_timezone_set: ' . date_default_timezone_get();
        */

        $day_3_letter = date('D'); //e.g. Sun or Sat or Wed
        $date = date('Y-m-d H:i:s A');
        $am_pm = date('A');
        $time = date('H:i:s');
        echo " [date: $date] ";

        if(in_array($day_3_letter, array('Sat', 'Sun'))) return true; //echo "\nToday is Saturday or Sunday.";
        else {
            // echo "\nToday is not Saturday nor Sunday but ". date('D') .".\n";
            // should be between 9:00 PM and 5:00 AM
            // if PM should be > 21:00:00 and if AM should be < 05:00:00
            if($am_pm == 'AM') {
                if($time < '05:00:00') return true;
                else return false;
            }
            elseif($am_pm == 'PM') {
                if($time > '21:00:00') return true;
                else return false;
            }
            return false; //doesn't go here anyway
        }
    }
    private function save_to_dump($rec, $filename)
    {
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
?>