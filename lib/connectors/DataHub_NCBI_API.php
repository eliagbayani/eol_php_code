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

        $this->NCBI_api['taxa'] = "https://api.NCBI.org/v1/observations/species_counts?taxon_is_active=true&hrank=XRANK&lrank=XRANK&iconic_taxa=XGROUP&quality_grade=XGRADE&page=XPAGE"; //defaults to per_page = 500
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "NCBI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $this->save_path = $save_path;
        $this->dump_file = $this->save_path . "/compiled_taxa.txt";

        $this->reports_path = $save_path;
        $this->taxon_page = "https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id="; //e.g. 8045
        $this->dwca['NCBI-taxonomy'] = "https://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip";
        $this->api_call = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&term=txidXXTAXIDXX[Organism:exp]';
        $this->debug = array();

        $this->big_file  = '/Volumes/Crucial_2TB/eol_php_code_tmp2/nucl_gb.accession2taxid';  //12.47 GB
        // $this->big_file2 = '/Volumes/Crucial_2TB/eol_php_code_tmp2/nucl_wgs.accession2taxid'; //32.62 GB --- NOT USED

        date_default_timezone_set('America/New_York');
        // date_default_timezone_set('Asia/Taipei');
    }
    function start()
    {
        /* works OK. A way to reference a function from: vendor/eol_content_schema_v2
        print_r(\eol_schema\Taxon::$ranks); exit;
        */

        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);

        // /* step 1: assemble taxa
        // Was first run in MacStudio. Generates the taxonomy file (compiled_taxa.txt) and just scp it to eol-archive.
        self::gen_NCBI_taxonomy_using_ZIP_file();
        print_r($this->taxa_info[1]); //exit("\nelix 2\n");

        /* testing only
        $test_species = array(9913, 32630, 1423, 5076, 562);
        // $test_species = array(9913);
        foreach($test_species as $taxid) { echo "\n ----- processing [$taxid]\n";
            $ancestry = self::get_ancestry_for_taxonID($taxid);
            print_r($ancestry);
            echo "\n ----- end [$taxid]";
        }        
        exit("\nelix 3\n");
        */

        // --- end step 1 --- */

        /* step 2: process genus and family; loop tsv file and use API        
        self::parse_tsv_file($this->dump_file, "process genus family from compiled taxonomy");
        --- end step 2 --- */

        // [genus] => 109270
        // [species] => 2117681
        // [family] => 10403

        // /* step 3: process the big file - for species-level taxa
        self::parse_tsv_file($this->big_file, "process big file");      //the correct tsv file to use --- generates $this->totals[taxid] = count
        echo "\n 8049: ".$this->totals[8049]."\n";
        echo "\n 454919: ".$this->totals[454919]."\n";
        echo "\n 21: ".$this->totals[21]."\n";
        self::write_species_level_MoF();

        // self::parse_tsv_file($this->big_file2, "process big file");  //NOT to be used --- generates $this->taxid_accession
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
    private function gen_NCBI_taxonomy_using_ZIP_file()
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
        if(is_file($this->dump_file)) unlink($this->dump_file);
        self::parse_tsv_file($temp_dir . 'names.dmp', "process names.dmp");
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
        elseif($what == 'process big file') { //per: https://ftp.ncbi.nih.gov/pub/taxonomy/accession2taxid/README
            // $fields = array('accession', 'accession.version', 'taxid', 'gi');
            $separator = "\t";
            $modulo = 5000000;
        }
        elseif($what == 'process genus family from compiled taxonomy') {
            $separator = "\t";
            $modulo = 100000;
        }

        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % $modulo) == 0) echo "\n [$what] $i ";

            if(in_array($what, array('process big file', 'process genus family from compiled taxonomy'))) {
                if($i == 1) {
                    $fields = explode($separator, $row); 
                    continue;
                }
            }
            
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
                        self::save_to_dump($rek, $this->dump_file);
                    // }
                }
                elseif($what == 'process big file') { //for species-level taxa
                    /*Array(
                        [accession] => A00001
                        [accession.version] => A00001.1
                        [taxid] => 10641
                        [gi] => 58418
                    )*/
                    $taxid = $rec['taxid'];
                    $accession = $rec['accession'];
                    if($val = @$this->taxa_info[$taxid]['r']) {
                        if(in_array($val, array('species'))) @$this->totals[$taxid]++;
                    }
                    // $this->taxid_accession[$taxid][$accession] = ''; //dev only
                }
                elseif($what == 'process genus family from compiled taxonomy') {                    
                    self::process_genus_family($rec);
                    if($i > 15) break;
                }
            }
        }
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
        if(!in_array($rank, array('genus', 'family'))) return; //orig main operation
        // if(!in_array($rank, array('genus'))) return; //during caching only

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
        $save = array();
        $save['taxonID'] = $rec['id'];
        $save['scientificName'] = $rec['name'];
        $save['taxonRank'] = $rec['rank'];
        $save['parentNameUsageID'] = $rec['parent_id'];
        self::write_taxon($save);
        self::write_MoF($rec);
    }
    private function write_species_level_MoF()
    {   echo "\nStart write species-level...\n";
        $total = count($this->totals); $i = 0;
        foreach($this->totals as $taxid => $count) { $i++;
            // if(($i % 1000) == 0) 
            echo "\n species-level $i of $total [$taxid] ";
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
                $this->debug['reached 100'][$taxonID] = '';
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
        $save['source'] = $this->taxon_page . $taxonID;
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 
        $mType = 'http://eol.org/schema/terms/NumberOfSequencesInGenBank';
        $mValue = $rec['count'];
        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        $this->func->add_string_types($save, $mValue, $mType, "true");    
    }
    private function correct_time_2call_api_YN()
    {   return true; //debug only
        /* good debug
        if($timezone_object = date_default_timezone_get()) echo 'date_default_timezone_set: ' . date_default_timezone_get();
        */
        if(date('D') == 'Sat' || date('D') == 'Sun') return true; //echo "\nToday is Saturday or Sunday.";
        else {
            // echo "\nToday is not Saturday or Sunday but ". date('D') .".\n";
            // should be between 9:00 PM and 5:00 AM
            // if PM should be > 21:00:00 and if AM should be < 05:00:00
            $date = date('Y-m-d H:i:s A');
            $am_pm = date('A');
            $time = date('H:i:s');
            echo " [date: $date] ";
            // echo "\n am_pm: $am_pm";
            // echo "\n time: $time\n";
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