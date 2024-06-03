<?php
namespace php_active_record;
/*  datahub_bolds_v2.php 
Status: this wasn't used as main operation.
*/
class DataHub_BOLDS_API_v2
{
    function __construct($folder = false)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }
        $this->debug = array();
        $this->download_options_BOLDS = array('resource_id' => 'BOLDS', 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800*2, 'download_attempts' => 1);
        $this->download_options_BOLDS['expire_seconds'] = false; //May 2024
        $this->taxon_page = 'https://v3.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=';

        // /*
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "BOLDS/";
        if(!is_dir($save_path)) mkdir($save_path);
        $this->tsv_files = $save_path.'Public_count_XGROUP_updated.tsv'; //e.g. Public_count_family_updated.tsv
        // */
        $this->Eli_cached_taxonomy = $save_path.'datahub_bolds_taxonomy_2024_06_03.txt';
        $this->Eli_cached_taxonomy = $save_path.'datahub_bolds_taxonomy_2024_06_04.txt';

    }
    function start()
    {   /* just a utility
        // echo "\nranks_php_code_base: ".count(self::$ranks_php_code_base)."\n";
        // echo "\nranks_eol_org: ".count(self::$ranks_eol_org)."\n";
        // $subset=array_diff(self::$ranks_eol_org, self::$ranks_php_code_base); echo "\nsubset: ".count($subset)."\n";
        // $subset=array_diff(self::$ranks_php_code_base, self::$ranks_eol_org); echo "\nsubset: ".count($subset)."\n";
        exit("\n");
        */

        $this->debug = array();
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);

        // step 1 Use Eli's cache to generate $var[taxid] = parent_id
        self::parse_tsv_file($this->Eli_cached_taxonomy, 'use cached taxonomy build taxon info list');

        // /* step 2
        // step 2.1: build taxon info list using Rebekah's spreadsheets   FOR SPECIES-LEVEL ONLY
        self::read_tsv_files_do_task("generate taxa info list");
        // print_r($this->taxa_info); exit;

        // step 2.2:                                                      FOR SPECIES-LEVEL ONLY
        self::read_tsv_files_do_task("read tsv write dwca");
        // */

        // step 3: process family-genus-level
        // /*
        $this->group = 'family';
        $url = str_replace("XGROUP", 'family', $this->tsv_files);
        self::parse_tsv_file($url, 'process family-genus-level'); //generates $this->totals
        // */

        // /*
        $this->group = 'genus';
        $url = str_replace("XGROUP", 'genus', $this->tsv_files);
        self::parse_tsv_file($url, 'process family-genus-level'); //generates $this->totals
        // */

        // print_r($this->totals); exit;

        foreach($this->totals as $sciname => $count) {
            if(!$sciname) continue;
            if($taxid = @$this->name_id[$sciname]) {
                echo "\n[$sciname] [$taxid] [$count]";

                $save = array();
                $save['taxonID'] = $taxid;
                $save['scientificName'] = $sciname;
                $save['taxonRank'] = $this->group;
                $save['parentNameUsageID'] = $this->taxa_info[$taxid]['p'];
                self::write_taxon($save);

                $rec = array();
                $rec['tax id'] = $taxid;
                $rec['count'] = $count;
                self::write_MoF($rec);
            }
            else $this->debug['sciname not found'][$sciname] = '';
        }

        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    private function read_tsv_files_do_task($task)
    {
        $groups = array('species'); //this is exclusive for species-level only
        foreach($groups as $group) {
            $this->group = $group;
            $url = str_replace("XGROUP", $group, $this->tsv_files);
            self::parse_tsv_file($url, $task);
        }
    }
    private function parse_tsv_file($file, $what)
    {   echo "\nReading file, task: [$what] [$file]\n";
        $i = 0; $final = array();
        $included_ranks = array("species", "form", "variety", "subspecies", "unranked");
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if(($i % 200000) == 0) echo "\n $i ";
            if($i == 1) { $fields = explode("\t", $row); continue; }
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = @$tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); //exit("\nstop muna\n");
            }

            if(in_array($what, array('read tsv write dwca', 'generate taxa info list'))) {
                $tax_id = $rec['tax id'];
                $parent_id = $rec['parent id'];
                $sciname = $rec[$this->group];    
            }

            if($what == 'read tsv write dwca') { //species-level only
                if(($i % 20000) == 0) echo "\n main $i ";

                /* Array(
                    [count] => 14
                    [family] => Rosaceae
                    [tax id] => 989646
                    [parent id] => 100947
                    [] => 
                )*/
                
                if(stripos($sciname, 'sp.') !== false) continue; //string is found

                $save = array();
                $save['taxonID'] = $tax_id;
                $save['scientificName'] = $sciname;
                $save['taxonRank'] = $this->group;
                $save['parentNameUsageID'] = $parent_id;
                self::write_taxon($save);
                self::write_MoF($rec);

                // break; //debug only
                // if($i >= 3000) break; //debug only
            }
            elseif($what == "generate taxa info list") { //species-level only
                $this->taxa_info[$tax_id]['p'] = $parent_id;
                $this->taxa_info[$tax_id]['n'] = $sciname;
                $this->taxa_info[$tax_id]['r'] = $this->group;
            }
            elseif($what == "use cached taxonomy build taxon info list") { // print_r($rec); exit;
                /*Array(
                    [taxid] => 11
                    [counts] => 3027
                    [sciname] => Acanthocephala
                    [rank] => phylum
                    [parentNameUsageID] => 1
                    [] => 
                )*/
                $taxid = $rec['taxid'];
                $this->taxa_info[$taxid]['p'] = $rec['parentNameUsageID'];
                $this->taxa_info[$taxid]['n'] = $rec['sciname'];
                $this->taxa_info[$taxid]['r'] = $rec['rank'];

                if($rec['rank'] == 'Families' || $rec['rank'] == 'Genera') $this->name_id[$rec['sciname']] = $taxid;
            }
            elseif($what == "process family-genus-level") {
                // print_r($rec); exit("\n fam exit muna \n");
                /* Array(
                    [count] => 14               - wrong assignment
                    [family] => Rosaceae
                    [tax id] => 989646          - wrong assignment
                    [parent id] => 100947       - wrong assignment
                    [] => 
                )*/
                if($family_genus_name = @$rec['family']) {}
                elseif($family_genus_name = @$rec['genus']) {}
                else  { //it actually can happen
                    // print_r($rec);
                    // exit("\nShould not go here. Investigate.\n");
                }
                $this->totals[$family_genus_name] = @$this->totals[$family_genus_name] + $rec['count'];
            }
        }
    }
    private function write_taxa_4_ancestry($ancestry)
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
    private function write_taxon($rec)
    {   //print_r($rec);
        $taxonID = $rec['taxonID'];
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $taxonID;
        $taxon->scientificName      = $rec['scientificName'];

        $transform['Genera'] = 'genus';
        $transform['Families'] = 'family';
        $transform['Orders'] = 'order';
        $transform['Classes'] = 'class';
        $transform['Tribes'] = 'tribe';
        $transform['Subfamilies'] = 'subfamily';
        
        if($val = @$transform[$rec['taxonRank']]) $final_rank = $val;
        else $final_rank = strtolower($rec['taxonRank']);

        $taxon->taxonRank           = $final_rank;
        $taxon->parentNameUsageID   = $rec['parentNameUsageID'];
        if(!isset($this->taxonIDs[$taxonID])) {
            $this->taxonIDs[$taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }

        // add ancestry to taxon.tab
        $ancestry = self::get_ancestry_for_taxonID($taxonID);
        if($ancestry) self::write_taxa_4_ancestry($ancestry);
    }
    private function get_ancestry_for_taxonID($taxonID)
    {
        $final = array();
        while(true) {
            if($val = @$this->taxa_info[$taxonID]['p']) {
                $final[] = $val;
                $taxonID = $val;
            }
            else {
                if($taxonID == 1) break;
                else {
                    if($val = self::get_ancestry_thru_api($taxonID)) {
                        echo " [$val] ";
                        $final[] = $val;
                        $taxonID = $val;
                    }
                    else break;
                }
            }
        }
        // echo "\nancestry: "; print_r($final); //exit;
        return $final;
    }
    private function get_ancestry_thru_api($taxonID)
    {
        $options = $this->download_options_BOLDS;
        $options['resource_id'] = 'BOLDS_ancestry';
        $url = "https://v3.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=basic&includeTree=true&taxId=";

        @$this->total_page_calls++; echo "\nx[$this->total_page_calls] $this->group batch\n";
        if($this->total_page_calls > 1) {
            if(($this->total_page_calls % 50) == 0) { echo "\nsleep 60 secs.\n"; sleep(60); }
        }

        if($json = Functions::lookup_with_cache($url.$taxonID, $this->download_options_BOLDS)) {
            self::bolds_API_result_still_validYN($json);
            $obj = json_decode($json); // print_r($obj); exit;
            // /* build taxa info list
            foreach($obj as $o) { 
                if(!@$o->taxid) { $this->debug['not found in api'][$taxonID] = ''; continue;}
                $tax_id = $o->taxid;
                $this->taxa_info[$tax_id]['p'] = $o->parentid;
                $this->taxa_info[$tax_id]['n'] = $o->taxon;
                $this->taxa_info[$tax_id]['r'] = $o->tax_rank;
            }
            // */
        }
        return @$this->taxa_info[$taxonID]['p'];
    }
    private function write_MoF($rec)
    {   //print_r($o); exit;
        $taxonID = $rec['tax id'];
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $this->taxon_page . $taxonID;
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 
        $mType = 'http://eol.org/schema/terms/NumberPublicRecordsInBOLD';
        $mValue = $rec['count'];
        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        $this->func->add_string_types($save, $mValue, $mType, "true");    
    }
    /*
    static $ranks_php_code_base = array(
        'species', 'superkingdom', 'kingdom', 'regnum', 'subkingdom', 'infrakingdom', 'subregnum', 'division',
        'superphylum', 'phylum', 'divisio', 'subdivision', 'subphylum', 'infraphylum', 'parvphylum', 'subdivisio',
        'superclass', 'class', 'classis', 'infraclass', 'subclass', 'subclassis', 'superorder', 'order',
        'ordo', 'infraorder', 'suborder', 'subordo', 'superfamily', 'family', 'familia', 'subfamily',
        'subfamilia', 'tribe', 'tribus', 'subtribe', 'subtribus', 'genus', 'subgenus', 'section',
        'sectio', 'subsection', 'subsectio', 'series', 'subseries', 'species', 'subspecies', 'infraspecies',
        'variety', 'varietas', 'subvariety', 'subvarietas', 'form', 'forma', 'subform', 'subforma');

    static $ranks_eol_org = array('class', 'class_group', 'cohort', 'cohort_group', 'division', 'division_group', 'domain', 'domain_group', 'epiclass', 'epicohort', 'epidivision', 'epidomain', 'epifamily', 'epiform', 'epigenus', 
        'epikingdom', 'epiorder', 'epiphylum', 'epispecies', 'epitribe', 'epivariety', 'family', 'family_group', 'form', 'form_group', 'genus', 'genus_group', 'infraclass', 'infracohort', 'infradivision', 
        'infradomain', 'infrafamily', 'infraform', 'infragenus', 'infrakingdom', 'infraorder', 'infraphylum', 'infraspecies', 'infratribe', 'infravariety', 'kingdom', 'kingdom_group', 'megaclass', 'megacohort', 
        'megadivision', 'megadomain', 'megafamily', 'megaform', 'megagenus', 'megakingdom', 'megaorder', 'megaphylum', 'megaspecies', 'megatribe', 'megavariety', 'order', 'order_group', 'phylum', 'phylum_group', 
        'species', 'species_group', 'subclass', 'subcohort', 'subdivision', 'subdomain', 'subfamily', 'subform', 'subgenus', 'subkingdom', 'suborder', 'subphylum', 'subspecies', 'subterclass', 'subtercohort', 
        'subterdivision', 'subterdomain', 'subterfamily', 'subterform', 'subtergenus', 'subterkingdom', 'subterorder', 'subterphylum', 'subterspecies', 'subtertribe', 'subtervariety', 'subtribe', 'subvariety', 
        'superclass', 'supercohort', 'superdivision', 'superdomain', 'superfamily', 'superform', 'supergenus', 'superkingdom', 'superorder', 'superphylum', 'superspecies', 'supertribe', 'supervariety', 'tribe', 
        'tribe_group', 'variety', 'variety_group');
    */
    private function bolds_API_result_still_validYN($str)
    {   // You have exceeded your allowed request quota. If you wish to download large volume of data, please contact support@boldsystems.org for instruction on the process. 
        if(stripos($str, 'have exceeded') !== false) { //string is found
            echo "\n[$str]\n";
            exit("\nProgram terminated by Eli.\n");
            // echo "\nBOLDS special error\n"; exit("\nexit muna, remove BOLDS from the list of dbases.\n");
            echo "\nExceeded quota\n"; sleep(60*10); //10 mins
            @$this->BOLDS_TooManyRequests++;
            if($this->BOLDS_TooManyRequests >= 3) exit("\nBOLDS should stop now.\n");
        }
    }
    // ======================================================================= copied template below
    private function z_build_taxonomy_list() //builds up the taxonomy list
    {
        if(is_file($this->dump_file)) unlink($this->dump_file);
        // /*
        $level_1 = self::assemble_kingdom(); //print_r($level_1); exit;
        $level_2 = self::assemble_level_2($level_1); //print_r($level_2); exit;
        $level_1 = '';
        $level_3 = self::assemble_level_2($level_2); //print_r($level_3);
        $level_2 = '';
        $level_4 = self::assemble_level_2($level_3); //print_r($level_4);
        $level_3 = '';
        $level_5 = self::assemble_level_2($level_4); //print_r($level_5);
        $level_4 = '';
        $level_6 = self::assemble_level_2($level_5); //print_r($level_6);
        $level_5 = '';
        $level_7 = self::assemble_level_2($level_6); //print_r($level_7);
        $level_6 = '';
        $level_8 = self::assemble_level_2($level_7); //print_r($level_8);
        $level_7 = '';
        // */

        /* testing only
        $test['xxx'][0] = array('taxid' => 285425, 'counts' => 173, 'sciname' => 'eli_name', 'rank' => 'eli_rank');
        $level_3 = self::assemble_level_2($test); print_r($level_3);
        */

        // https://v3.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=285425   //good test
    }
    private function z_assemble_data_from_html_then_write_dwca($html, $rec)
    {   /*<div id="subheader">
            <div class="box">
                <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td><h1 id="subHeaderH1">Oonopidae {family} -  <a title="phylum"href="/index.php/TaxBrowser_Taxonpage?taxid=20">Arthropoda</a>;  
                                                                   <a title="class"href="/index.php/TaxBrowser_Taxonpage?taxid=63">Arachnida</a>;  
                                                                   <a title="order"href="/index.php/TaxBrowser_Taxonpage?taxid=251">Araneae</a>; </h1></td>
                    <td class="printBtn"><button style="float:right" id="printBtn">Print</button></td></tr></table>
            </div>
        </div>*/
        // print_r($rec); exit;
        $html2 = self::get_string_between('<div id="subheader">', "</div>", $html);
        $save = array();
        $save['taxid'] = $rec['taxid'];
        $save['sciname'] = trim(self::get_string_between('d="subHeaderH1">', "{", $html2));
        $save['rank'] = trim(self::get_string_between('{', "}", $html2));
        $save['pubrec'] = self::get_public_records($html);

        // /* ----- ancestry -----
        $ancestry = array();
        $left = '<a title'; $right = '</a>';
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html2, $arr)) { 
            $a = array_reverse($arr[1]); //print_r($a);
            /*Array(
                [0] => ="order"href="/index.php/TaxBrowser_Taxonpage?taxid=251">Araneae
                [1] => ="class"href="/index.php/TaxBrowser_Taxonpage?taxid=63">Arachnida
                [2] => ="phylum"href="/index.php/TaxBrowser_Taxonpage?taxid=20">Arthropoda
            )*/
            foreach($a as $str) {
                $anc = array();
                if(preg_match("/=\"(.*?)\"/ims", $str, $arr2)) $anc['rank'] = $arr2[1];
                if(preg_match("/taxid=(.*?)\"/ims", $str, $arr2)) $anc['taxid'] = $arr2[1];
                if(preg_match("/\">(.*?)xxx/ims", $str."xxx", $arr2)) $anc['sciname'] = $arr2[1];
                $anc = array_map('trim', $anc);
                if($anc['taxid']) $ancestry[] = $anc;
            }
        }
        $save['parentID'] = @$ancestry[0]['taxid'];
        $save['ancestry'] = $ancestry;
        // ----- end ----- */
        // print_r($save);
        return $save;
        // exit("\nxxx\n");
    }
    private function z_get_public_records($html)
    {   /*<td width="29%">Public Records:</td>
          <td width="13%">942</td>*/
        $left = '>Public Records:</td>'; $right = '</td>';
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) return trim(strip_tags($arr[1]));          
    }
    private function z_get_list_items($html, $rank)
    {
        $final = array(); $left = '<li>'; $right = '</li>';
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr2)) { // print_r($arr2[1]);
            /*Array(
                [0] => <a href="/index.php/Taxbrowser_Taxonpage?taxid=316986">Chlorarachniophyta [67]</a>
                [1] => <a href="/index.php/Taxbrowser_Taxonpage?taxid=72834">Ciliophora [821]</a>
                [2] => <a href="/index.php/Taxbrowser_Taxonpage?taxid=53944">Heterokontophyta [8757]</a>
                [3] => <a href="/index.php/Taxbrowser_Taxonpage?taxid=317010">Pyrrophycophyta [2339]</a>
                [4] => <a href="/index.php/Taxbrowser_Taxonpage?taxid=48327">Rhodophyta [61640]</a>
            )*/
            foreach($arr2[1] as $t) {
                $r = array();
                if(preg_match("/taxid=(.*?)\"/ims", $t, $arr3)) $r['taxid'] = $arr3[1];
                if(preg_match("/\">(.*?)<\/a>/ims", $t, $arr3)) {
                    $ret = $arr3[1];
                    $r['counts'] = self::get_string_between("[", "]", $ret);
                    $ret = str_replace("[".$r['counts']."]", "", $ret);
                    $r['sciname'] = trim($ret);
                    $r['rank'] = $rank;
                }

                $r = array_map('trim', $r); // echo "\n$t\n"; print_r($r);
                self::save_to_dump($r, $this->dump_file);

                /*Array(
                    [taxid] => 24818
                    [counts] => 14053
                    [sciname] => Porifera
                )*/
                $final[] = $r;
            }                    
        }
        return $final;
    }
    private function z_get_rank($html)
    {   // <lh>Classes (4) </lh>...
        if(preg_match("/<lh>(.*?)<\/lh>/ims", $html, $arr)) {
            $new = trim(preg_replace('/\s*\([^)]*\)/', '', $arr[1])); //remove parenthesis OK
            return $new;
        }
        return "cannot parse rank";
    }
    private function z_get_string_between($left, $right, $str)
    {
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $str, $arr)) return trim($arr[1]);
    }
    private function z_save_to_dump($rec, $filename)
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
    private function z_get_curl_errors()
    {   /*
        Curl error (https://v3.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=basic,stats&includeTree=true&taxId=1144590): The requested URL returned error: 500
        Curl error (https://v3.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=basic,stats&includeTree=true&taxId=649487): Resolving timed
        [459] => https://v3.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=basic,stats&includeTree=true&taxId=649487): Resolving timed out after 20529 milliseconds :: [lib/Functions.php [148]]<br>
        */
        $str = file_get_contents($this->save_path . "/BOLDS_consoleText.txt");
        $left = 'Curl error ('; $right = '): The requested URL';
                                $right = '):';
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $str, $arr2)) { //print_r($arr2[1]); exit;
            foreach($arr2[1] as $url) {
                if(preg_match("/taxId=(.*?)xxx/ims", $url.'xxx', $arr)) $final[$arr[1]] = '';
            }
        }
        // print_r($final);
        echo "\ncount: ".count($final)."\n";
        echo "\ncount: ".count($final)." - should be less 1\n";
        $this->curl_error_taxIds = $final;
        // exit("\nstop muna\n");
    }
}
?>