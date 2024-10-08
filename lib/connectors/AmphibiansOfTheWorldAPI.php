<?php
namespace php_active_record;
// connector: [amphibians_otw]
class AmphibiansOfTheWorldAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array("resource_id" => $this->resource_id, "download_wait_time" => 1000000, "timeout" => 1800, 
                                        "download_attempts" => 1, "cache" => 1, "expire_seconds" => 60*60*24*30*3); //expires in 3 months (quarterly)
        $this->debug = array();

        $this->export_file = 'http://prod-asw-001.amnh.org/vz/herpetology/amphibia/var/ezwebin_site/storage/export_itis.csv';
        $this->export_file = 'http://localhost/cp/Amphibian Species of the World/export_itis.csv';
        $this->export_file = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Amphibian Species of the World/export_itis.csv';

        $this->furtherInfoURL = 'http://research.amnh.org/herpetology/amphibia/index.html';
        

        /* abandoned - working OK but not needed so far.
        $this->fiURL = 'http://research.amnh.org/vz/herpetology/amphibia/index.php//content/search?taxon=';
        $this->domain        = 'http://research.amnh.org';
        $this->starting_page = $this->domain.'/vz/herpetology/amphibia/index.php';
        $this->next_page     = $this->domain.'/vz/herpetology/amphibia/index.php//Amphibia';
        */
        
        $this->aotw_undefined_acceptedName_ids = CONTENT_RESOURCE_LOCAL_PATH . 'aotw_undefined_acceptedName_ids.txt';
        /*
        //for stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->current_offline_urls_dump_file = $this->TEMP_DIR . "offline_urls.txt";
        $this->current_offline_urls_dump_file2 = $this->TEMP_DIR . "offline_urls_unique.txt";
        */
    }
    private function get_undefined_acceptedName_ids()
    {
        if(file_exists($this->aotw_undefined_acceptedName_ids)) {
            $arr = file($this->aotw_undefined_acceptedName_ids);
            $arr = array_map('trim', $arr);
            print_r($arr);
            return $arr;
        }
        return array();
    }
    function start()
    {
        /* working but abandoned for now. This involves caching taxon URLs from partner's site.
        self::cache_furtherInformationURLs();
        */
        $this->undefined_acceptedName_ids = self::get_undefined_acceptedName_ids();
        $csv_file = Functions::save_remote_file_to_local($this->export_file, $this->download_options);
        $i = 0;
        if(!$file = Functions::file_open($csv_file, "r")) return;
        while(!feof($file)) {
            $temp = fgetcsv($file, 0, "|", '"');
            if(!$temp) continue;
            $json = json_encode($temp);
            //--------------------------------- to have unique rows
            $json_md5 = md5($json);
            if(isset($unique_rows[$json_md5])) continue; //exit("\nencountered duplicate row\n");
            else $unique_rows[$json_md5] = '';
            //---------------------------------
            $json = Functions::conv_to_utf8($json);
            $temp = json_decode($json, true);
            
            $temp = array_map('trim', $temp);
            $i++;
            if(($i % 1000) == 0) echo "\nbatch $i";
            if($i == 1) {
                $temp = str_replace(array("[", "]"), "", $temp);
                $fields = $temp;
                print_r($fields);
                continue;
            }
            else {
                $rec = array();
                $k = 0;
                // 2 checks if valid record
                if(!$temp) continue;
                foreach($temp as $t) {
                    $rec[$fields[$k]] = Functions::remove_whitespace($t);
                    $k++;
                }
            }
            /* good debug
            if(stripos($rec['unit_name1'], "Hylarana") !== false) { //string is found
                if(stripos($rec['unit_name2'], "montivaga") !== false) { //string is found
                    print_r($rec); exit;
                }
            }
            */
            // print_r($rec); //exit;
            if(self::has_question_mark(array($rec['unit_name1'], $rec['unit_name2'], $rec['unit_name3']))) continue; //@$debug['has ?']++;
            if(self::species_but_not_binomials($rec)) continue; //print_r($rec); //continue;
            if($this->undefined_acceptedName_ids) {
                if(in_array($rec['accepted_name'], $this->undefined_acceptedName_ids)) continue;
                if(in_array(md5($rec['accepted_name']), $this->undefined_acceptedName_ids)) continue;
                
            }
            $dwca_rec = self::parse_rec($rec);
            self::write_dwca($dwca_rec);
            /* good debug
            @$debug['usage'][$rec['usage']]['count']++;
            @$debug['unacceptability_reason'][$rec['unacceptability_reason']]['count']++;
            */
        }
        unlink($csv_file);
        if(isset($debug)) print_r($debug);
        $this->archive_builder->finalize(TRUE);
    }
    private function parse_rec($rec)
    {
        /*Array(
            [usage] => Array
                    [valid] => [count] => 8751
                    [invalid] => [count] => 23162
            [unacceptability_reason] => Array
                    [] => [count] => 8751
                    [synonymous original name] => [count] => 9479
                    [new combination or misspelling] => [count] => 13683
        
        unit_name1, unit_name2, unit_name3, taxon_author --> scientificName; concatenate the values of these fields, separated by blank spaces. 
            Please check for whitespace irregularities, i.e., there should not be any leading or trailing spaces and there should always be only a single space between name components. 
            Also, some taxon_author values need to be pre-processed, see 4. below.
        taxon_author --> reference, see 4. below.
        rank_name --> taxonRank; use lowercase strings for the rank names, e.g., Class should be class; omit hyphens, e.g., Sub-species should be subspecies.
        usage --> taxonomicStatus, values verbatim
        parent_name --> convert to parentNameUsageID
        unacceptability_reason --> taxonRemarks, values verbatim
        accepted_name --> convert to acceptedNameUsageID

        Array(
            [unit_name1] => Amphibia
            [unit_name2] => 
            [unit_name3] => 
            [taxon_author] => 
            [rank_name] => Class
            [usage] => valid
            [parent_name] => 
            [unacceptability_reason] => 
            [accepted_name] => 
        )
        */
        $final = array();
        $info_taxon_author_and_ref_id = self::generate_taxon_author($rec);
        $taxon_author = $info_taxon_author_and_ref_id['taxon_author'];
        
        $sciname = self::concatenate_strings(array($rec['unit_name1'], $rec['unit_name2'], $rec['unit_name3']));
        $final['furtherInformationURL'] = $this->furtherInfoURL;
        $final['scientificName'] = self::concatenate_strings(array($rec['unit_name1'], $rec['unit_name2'], $rec['unit_name3'], $taxon_author));
        $final['referenceID'] = $info_taxon_author_and_ref_id['reference_id'];
        $final['taxonRank'] = self::format_rank($rec['rank_name']);
        $final['taxonomicStatus'] = $rec['usage'];
        $final['parentNameUsageID'] = $rec['parent_name'];
        $final['taxonRemarks'] = $rec['unacceptability_reason'];
        $final['acceptedNameUsageID'] = $rec['accepted_name'];
        // print_r($final); //exit;
        return $final;
    }
    private function format_rank($str)
    {
        return trim(strtolower(str_replace("-", "", $str)));
    }
    private function concatenate_strings($strings)
    {
        $final = '';
        $strings = array_map('trim', $strings);
        foreach($strings as $str) {
            if($str) $final .= " $str";
        }
        return Functions::remove_whitespace(trim($final));
    }
    private function generate_taxon_author($rec)
    {
        /*4. Clean up taxon_author data. The taxon_author values for valid taxa are fine and can be used verbatim, so you only need to worry about the ones that have usage:invalid. 
        Most invalid taxa have the whole reference citation in the taxon_author field. Also, the taxon_author values represent authority data only for invalid taxa 
        where the unacceptability_reason value is "synonymous original name." If the value is "new combination or misspelling" the author data are apparently problematic, 
        so we want to omit them. In those cases, the scientificName would then consist only of the "unit_name1 unit_name2 unit_name3" string. */
        
        $final = array('taxon_author' => '', 'reference_id' => ''); //initialize
        if($rec['usage'] == 'valid') $final['taxon_author'] = $rec['taxon_author'];
        else { //invalid
            if($val = $rec['taxon_author']) $final['reference_id'] = self::create_reference_from_invalid_taxa_using_taxon_author($val);
            
            if($rec['unacceptability_reason'] == 'new combination or misspelling') {}
            elseif($rec['unacceptability_reason'] == '') {}
            elseif($rec['unacceptability_reason'] == 'synonymous original name') { //will do the processing here...
                $final['taxon_author'] = self::get_authority_from_str($rec['taxon_author']);
            }
        }
        return $final;
        
        /* ELI: this block was solved by this func: get_authority_from_str()
        For the "synonymous original name" taxa, we need to extract the authors & year from the full taxon_author text so we append this as the authority to 
        the unit_name1 unit_name2 unit_name3 string. It looks like they never use parentheses around the year in the taxon_author strings for invalid taxa, 
        so we can extract the author & year simply by using the beginning of the taxon_author string up to and including the 4 digit year number. Examples:

        original taxon_author value >>> extracted for scientificName
        Bolkay, 1912 , Mitt. Jahrb. K. Ungar. Geol. Anst., 19 >>> Bolkay, 1912
        Pillai and Chanda, 1973, Proc. Indian Acad. Sci., Ser. B, 78 >>> Pillai and Chanda, 1973
        Sarkar and Ray, 2006, In Alfred (ed.), Fauna of Arunachal Pradesh, Part 1 >>> Sarkar and Ray, 2006
        Vijayakumar, Dinesh, Prabhu, and Shanker, 2014, Zootaxa, 3893 >>> Vijayakumar, Dinesh, Prabhu, and Shanker, 2014
        Boettger, 1880, Ber. Senckenb. Naturforsch. Ges., 1879–80 >>> Boettger, 1880
        */

        /* ELI: this block was solved by func: create_reference_from_invalid_taxa_using_taxon_author()
        Also, for all taxa with usage:invalid (regardless of unacceptability_reason value), please create are record for a reference and put the full value of taxon_author in the 
        full_reference field.
        */

        /* ELI: by default fgetcsv maintains the double quotes. So this one is also covered.
        Please note that they have some parts of names in quotes, e.g., "Hylarana" latouchii:
        """Hylarana"""|"latouchii"|""|"(Boulenger, 1899)"|"Species"|"valid"|"Ranidae Batsch, 1796"|""|""

        They use this to indicate names that are currently valid but that are known to be wrong and that will be changed with the next revision of the group. 
        For example, in the case of "Hylarana" latouchii, they know that this species does not really belong to the genus Hylarana, but it has not yet been formally moved to another genus. 
        Ideally, we would preserve the spelling with the quotes on EOL, but I don't know if we can get this to work. The harvester may choke on it. 
        I suggest that we try it and see what happens.
        */
    }
    private function get_authority_from_str($str)
    {
        $str = str_replace(" ", "_", $str);
        for($i = 0; $i <= strlen($str); $i++) {
            $sub = substr($str, $i, 4); // echo "\n[$sub]";
            if(is_numeric($sub)) { // echo "\nnumeric [$sub]\n";
                $str = str_replace("_", " ", $str);
                return substr($str, 0, $i+4);
            }
        }
        $str = str_replace("_", " ", $str);
        return $str;
    }
    private function create_reference_from_invalid_taxa_using_taxon_author($taxon_author)
    {
        $r = new \eol_schema\Reference();
        $r->full_reference = $taxon_author;
        $r->identifier = md5($r->full_reference);
        if(!isset($this->reference_ids[$r->identifier])) {
            $this->reference_ids[$r->identifier] = '';
            $this->archive_builder->write_object_to_file($r);
        }
        return $r->identifier;
    }
    private function has_question_mark($strings)
    {
        foreach($strings as $str) {
            if(stripos($str, "?") !== false) return true; //string is found
        }
        return false;
    }
    private function species_but_not_binomials($rec)
    {
        /*Array(
            [unit_name1] => Amphibia
            [unit_name2] => 
            [unit_name3] => 
            [taxon_author] => 
            [rank_name] => Class
            [usage] => valid
            [parent_name] => 
            [unacceptability_reason] => 
            [accepted_name] => 
        )
        3. Remove species that are not binomials:
        (a) taxa of rank species that dont have a unit_name2 entry
        (b) taxa of rank species where the unit_name2 entry has parentheses and there is no unit_name3 value
        */
        /* I've added this 1st block bec. there are invalid species with ' ' space in name1 but blank in name2 and name3
        e.g. Array
            [unit_name1] => Cornufer (Ceratobatrachus) guentheri
            [unit_name2] => 
            [unit_name3] => 
            [rank_name] => Species
            [usage] => invalid
        batch 31000Array
            [unit_name1] => Leptolalax mangshanensis
            [unit_name2] => 
            [unit_name3] => 
            [rank_name] => Species
            [usage] => invalid
        */
        if($rec['rank_name'] == 'Species' && $rec['usage'] == 'invalid') {
            if(stripos($rec['unit_name1'], " ") !== false) return false; //string is found
            if(!$rec['unit_name2']) return true;
            if(stripos($rec['unit_name2'], "(") !== false) { //string is found
                if(!$rec['unit_name3']) return true;
            }
        }

        /* this is the original block. Based on verbatim Katja's instructions. I just put in the distintion here where species is 'valid' */
        if($rec['rank_name'] == 'Species' && $rec['usage'] == 'valid') {
            if(!$rec['unit_name2']) return true;
            if(stripos($rec['unit_name2'], "(") !== false) { //string is found
                if(!$rec['unit_name3']) return true;
            }
        }
        
        return false;
    }
    private function write_dwca($rec)
    {   /*Array
            [scientificName] => Phyllonastes lynchi Duellman, 1991
            [referenceID] => f29b97a995fd216253295320f2c4670d
            [taxonRank] => species
            [taxonomicStatus] => invalid
            [parentNameUsageID] => 
            [taxonRemarks] => synonymous original name
            [acceptedNameUsageID] => Noblella lynchi (Duellman, 1991)
        */
        // print_r($rec);
        
        $rec['taxonID'] = $rec['scientificName'];
        
        // /* to md5 the IDs
        if($val = $rec['taxonID']) $rec['taxonID'] = md5($val);
        if($val = $rec['parentNameUsageID']) $rec['parentNameUsageID'] = md5($val);
        if($val = $rec['acceptedNameUsageID']) $rec['acceptedNameUsageID'] = md5($val);
        // */
        
        $taxon = new \eol_schema\Taxon();
        foreach($rec as $key => $value) {
            $taxon->$key = $value;
        }
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function cache_furtherInformationURLs()
    {
        if($html = Functions::lookup_with_cache($this->starting_page, $this->download_options)) {
             // <a href="/vz/herpetology/amphibia/index.php//Amphibia/Anura">
             if(preg_match_all("/\/vz\/herpetology\/amphibia\/index.php\/\/Amphibia(.*?)\"/ims", $html, $arr)) {

                 $arr[1] = array_filter($arr[1]);
                 // print_r($arr[1]); exit;
                 $i = 0;
                 foreach($arr[1] as $url1) { $i++; //exit("\n$url1\n");
                     echo "\n$i -> ";
                     if($info1 = self::search_more($url1)) {
                         /*Array(
                             [0] => Array(
                                     [url] => /vz/herpetology/amphibia/index.php//Amphibia/Anura/Allophrynidae
                                     [sci] => Allophrynidae Savage, 1973
                                     [count] => 3 sp.
                                 )
                         */
                         
                         foreach($info1 as $info) {
                             if(!@$info['count']) continue;
                             if($info2 = self::search_more($info['url'])) {
                                 foreach($info2 as $info) {
                                     if(!@$info['count']) continue;
                                     if($info3 = self::search_more($info['url'])) {
                                         foreach($info3 as $info) {
                                             if(!@$info['count']) continue;
                                             if($info4 = self::search_more($info['url'])) {
                                                 foreach($info4 as $info) {
                                                     if(!@$info['count']) continue;
                                                     if($info5 = self::search_more($info['url'])) {
                                                        foreach($info5 as $info) {
                                                            if(!@$info['count']) continue;
                                                            if($info6 = self::search_more($info['url'])) {
                                                                foreach($info6 as $info) {
                                                                    if(!@$info['count']) continue;
                                                                    if($info7 = self::search_more($info['url'])) {
                                                                        foreach($info7 as $info) {
                                                                            if(!@$info['count']) continue;
                                                                            if($info8 = self::search_more($info['url'])) {
                                                                                exit("\nreached level 8\n");

                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                     }
                                                 }
                                             }
                                         }
                                     }
                                 }
                             }
                         }
                     }
                     // exit("\nstop muna\n");
                 }
             }
        }
        exit("\nfinished caching\n");
    }
    private function search_more($url)
    {
        // /Anura
        // /vz/herpetology/amphibia/index.php//Amphibia/Anura/Allophrynidae
        if(stripos($url, "index.php") !== false) $url = $this->domain.$url; //string is found
        else                                     $url = $this->next_page.$url;
        
        
        /*
        <div class="taxa">
            <a href="/vz/herpetology/amphibia/index.php//Amphibia/Anura/Allophrynidae">Allophrynidae Savage, 1973</a>
            (3 sp.)</div>
        */
        $ret = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match_all("/<div class=\"taxa\">(.*?)<\/div>/ims", $html, $arr)) {
                $temp = array_map('trim', $arr[1]); // print_r($temp);
                /*Array(
                    [0] => <a href="/vz/herpetology/amphibia/index.php//Amphibia/Anura">Anura</a>
                    (7099 sp.)
                    [1] => <a href="/vz/herpetology/amphibia/index.php//Amphibia/Caudata">Caudata</a>
                    (738 sp.)
                    [2] => <a href="/vz/herpetology/amphibia/index.php//Amphibia/Gymnophiona">Gymnophiona</a>
                    (212 sp.)
                )*/
                $i = 0;
                foreach($temp as $a) { $i++;
                    echo " [$i]";
                    $final = array();
                    if(preg_match("/<a href=\"(.*?)\"/ims", $a, $arr)) $final['url'] = $arr[1];
                    if(preg_match("/\">(.*?)<\/a>/ims", $a, $arr)) $final['sci'] = $arr[1];
                    if(preg_match("/\((.*?) sp\.\)/ims", $a, $arr)) $final['count'] = $arr[1];
                    if(@$final['url']) $ret[] = $final;
                }
                // print_r($ret);
                // exit;
            }
        }
        return $ret;
    }
    /* =================== ends here =========================*/

    function get_all_taxa()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        
        $this->stored_offline_urls = $this->get_rows_from_dump_file($this->stored_offline_urls_dump_file);
        $this->create_reference();
        $urls = self::get_urls_to_process();
        $this->process_urls($urls);
        $this->manuall_add_taxon();
        $this->archive_builder->finalize(TRUE);
        recursive_rmdir($this->TEMP_DIR); // comment this line to check offline_urls.txt
        debug("\n temporary directory removed: " . $this->TEMP_DIR);
    }
    private function process_urls($urls)
    {
        $i = 0;
        $total = count($urls);
        foreach($urls as $url) {
            $i++;
            echo "\n - $i of $total [$url]\n";
            if(isset($this->stored_offline_urls[$url])) continue;
            
            /* breakdown when caching debug
            $cont = false;
            // if($i >= 1 && $i < 1000)     $cont = true;
            // if($i >= 1000 && $i < 2000)  $cont = true;
            // if($i >= 2000 && $i < 3000)  $cont = true;
            // if($i >= 3000 && $i < 4000)  $cont = true;
            if(!$cont) continue;
            */
            
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                $html = trim(str_ireplace(array(' align="center"', ' class="style1"', ' class="style2"'), "", $html));
                if(preg_match("/>Family: (.*?)xxx/ims", $html . "xxx", $arr)) {
                    $rec["source"] = $url;
                    if(preg_match("/<h1>(.*?)<\/h1>/ims", $html, $arr) ||
                       preg_match("/<FONT FACE=\"Arial\">(.*?)<\/FONT>/ims", $html, $arr) ||
                       preg_match("/<h2>(.*?)<\/h2>/ims", $html, $arr))
                    {
                        $sciname = Functions::remove_whitespace(strip_tags($arr[1]));
                        $sciname = trim(str_replace(array(chr(13), chr(10)), " ", $sciname));
                        //manual adjustments
                        if($sciname == "Small Winter Stoneflies") $sciname = "Allocapnia sp.";
                        if($sciname == "Embioptera: Family Oligotomidae") $sciname = "Oligotomidae";

                        $sciname = trim($sciname);
                        $to_exclude = array("cf.", "sp.", "Unidentified Stonefly", "Family");
                        $include = true;
                        foreach($to_exclude as $exclude) {
                            if(is_numeric(stripos($sciname, $exclude))) $include = false;
                        }
                        if(!$include) continue;

                        $sciname = Functions::canonical_form($sciname);
                        // only species-level
                        if(stripos($sciname, " ") === false) continue;

                        // $images = self::parse_images($html, $url); working... temporarily commented
                        $images = array();
                        $info = self::parse_texts($html, $url);
                        $lengths = @$info["lengths"];
                        $wingspan = @$info["wingspan"];
                        
                        if($images || $lengths) {
                            $r = array();
                            $r["sciname"] = $sciname;
                            $r["taxon_id"] = str_replace(" ", "_", $r["sciname"]);
                            $r["source"] = $url;
                            self::create_instances_from_taxon_object($r);
                            $r["images"] = $images;
                            $r["lengths"] = $lengths;
                            $r["wingspan"] = $wingspan;
                            if($lengths) self::prepare_length_structured_data($r);
                            if($images) self::prepare_image_objects($r);
                        }
                    }
                    else echo "\n investigate: no sciname [$url]";
                }
            }
            else self::save_to_dump($url, $this->current_offline_urls_dump_file);
        }
        // print_r($this->debug);
        print "\n count:" . count($this->debug) . "\n";
    }
    private function manuall_add_taxon()
    {
        $records[] = array("sciname" => "Cordulegaster diastatops", "length" => "60-65", "url" => "http://www.americaninsects.net/d/cordulegaster-diastatops.html");
        $records[] = array("sciname" => "Cordulegaster bilineata", "length" => "60-65", "url" => "http://www.americaninsects.net/d/cordulegaster-diastatops.html");
        $records[] = array("sciname" => "Enallagma cyathigerum", "length" => "29-40", "url" => "http://americaninsects.net//d/enallagma-cyathigerum.html");
        $records[] = array("sciname" => "Enallagma boreale", "length" => "28-36", "url" => "http://americaninsects.net//d/enallagma-cyathigerum.html");
        foreach($records as $rec) {
            $r = array();
            $r["sciname"] = Functions::canonical_form($rec["sciname"]);
            $r["taxon_id"] = str_replace(" ", "_", $r["sciname"]);
            $r["source"] = $rec["url"];
            self::create_instances_from_taxon_object($r);
            $r["lengths"] = array($rec["length"]);
            self::prepare_length_structured_data($r);
        }
    }
    private function parse_texts($html, $url)
    {
        $texts = array();
        $length = "";
        $wingspan = false;
        if(preg_match("/>Wingspan: (.*?)</ims", $html, $arr)) {
            $wingspan = true;
            $html = str_replace("Wingspan: ", "Length: ", $html);
        }
        if(preg_match("/>Length: (.*?)</ims", $html, $arr)) {
            if($length = $arr[1]) {
                $to_exclude = array("in image", "top photo", "first photo", "lower photo", "upper photo", "the photo", "in photo", "pictured", 
                "Cantharini", "the right");
                foreach($to_exclude as $exclude) {
                    if(is_numeric(stripos($length, $exclude))) return array();
                }
                
                $length = trim(str_ireplace(array("females are a little larger than males", "Males usually smaller than the females", 
                "males average smaller than females", "males tend  to be smaller than females", "Both sexes somewhat larger in  western North America",
                "some in the tribe are", "most in tribe", "most commonly", "adults are", "most in this genus", 
                 "many in the tribe are", "in subfamily", "in the subfamily",  
                "in this family", "in the family", 
                "typically", "beetle", "barklouse", "many species", "in the genus are", "insect in photo", " mm", "often", "in the genus", "most are", 
                "about", "fly", "Caddis", "insect", "under", "wasp", "around", "&nbsp;",  
                "most in genus", "usually", "adults", "most in family are", "in genus", "approximately"
                ), "", $length));
                
                //manual adjustment
                $length = str_replace("1.5 - 6.0 ;  3.3", "1.5 - 6.0", $length);
                $length = str_replace("7 - 9. In genus, 5 - 13", "7 - 9", $length);
                
                $chars = array(".", ",", ";");
                foreach($chars as $char) {
                    if(substr($length, -1) == $char) $length = substr($length, 0, strlen($length)-1);
                }
                echo "\n[$length]\n";
                $texts[] = $length;
            }
        }
        return array("lengths" => $texts, "wingspan" => $wingspan);
    }
    private function parse_images($html, $url)
    {
        $images = array();
        $to_exclude = array("button");
        if(preg_match_all("/<img (.*?)>/ims", $html, $arr)) {
            foreach($arr[1] as $img) {
                $include = true;
                foreach($to_exclude as $exclude) {
                    if(strpos($img, $exclude) === false) {}
                    else $include = false;
                }
                if($include) {
                    $src = "";
                    $caption = "";
                    $image_path = "";
                    if(preg_match("/src=\"(.*?)\"/ims", $img, $arr2)) $src = $arr2[1];
                    if(preg_match("/alt=\"(.*?)\"/ims", $img, $arr2)) $caption = $arr2[1];
                    if($src) {
                        if($val = self::get_directory_name($url)) $image_path = $val . "/" . $src;
                        if($image_path) $images[] = array("title" => $caption, "image" => $image_path);
                    }
                }
            }
        }
        return $images;
    }
    private function get_directory_name($url)
    {
        $info = pathinfo($url);
        if($info["dirname"] == "http:") return $this->domain;
        else                            return $info["dirname"];
        return false;
    }
    private function get_urls_to_process()
    {
        $urls = array();
        $urls[0] = self::get_urls_from_page($this->domain);
        $urls[1] = self::get_deep_level_urls($urls[0]);
        $urls[2] = self::get_deep_level_urls($urls[1]);
        $urls[3] = self::get_deep_level_urls($urls[2]);
        $urls[4] = self::get_deep_level_urls($urls[3]);
        $urls[5] = self::get_deep_level_urls($urls[4]);
        $temp = array_merge($urls[1], $urls[2], $urls[3], $urls[4], $urls[5]);
        $urls = array_values(array_unique($temp));
        $i = 0;
        foreach($urls as $url) {
            foreach($this->to_exclude as $exclude) {
                if(strpos($url, $exclude) === false) {}
                else unset($urls[$i]);
            }
            $i++;
        }
        $urls = array_map('trim', $urls);
        $urls = array_values(array_unique($urls));
        return $urls;
    }
    private function get_deep_level_urls($urls_to_process)
    {
        $temp = array();
        $total = count($urls_to_process);
        $i = 0;
        foreach($urls_to_process as $url) {
            $i++;
            echo "\n $i of $total - ";
            $temp = array_merge($temp, self::get_urls_from_page($url));
        }
        $temp = array_values(array_unique($temp));
        $final = array();
        foreach($temp as $url) {
            $info = pathinfo($url);
            $basename = $info["basename"];
            if(isset($this->basenames[$basename])) continue;
            else $this->basenames[$basename] = 1;
            $final[] = $url;
        }
        return $final;
    }
    private function get_urls_from_page($url)
    {
        if(isset($this->stored_offline_urls[$url])) return array();
        foreach($this->to_exclude as $exclude) {
            if(is_numeric(stripos($url, $exclude))) return array();
        }
        echo "\n processing [$url]\n";
        $temp = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_ireplace(array(' class="style1"', ' class="style2"', ' class="style3"', ' class="style4"', ' class="style5"', 
                                       ' class="style6"', ' class="style7"', ' class="style8"', ' class="style9"', ' class="style10"',
                                       ' class="navbar"', ' class="style20"', ' class="style13"'), "", $html);
            $html = str_ireplace("%20.htm", ".htm", $html);
            $html = str_ireplace(" .htm", ".htm", $html);
            if(preg_match_all("/<a href\=\"(.*?)\">/ims", $html, $arr)) $temp = array_values(array_unique($arr[1]));
            else echo " - no urls \n ";
        }
        else self::save_to_dump($url, $this->current_offline_urls_dump_file);
        // generate url
        $final = array();
        if($temp) {
            $dirname = self::get_directory_name($url);
            foreach($temp as $url) {
                $include = true;
                foreach($this->to_exclude as $exclude) {
                    if(strpos($url, $exclude) === false) {}
                    else $include = false;
                }
                if($include) $final[] = $dirname . "/" . $url;
            }
        }
        return $final;
    }
    private function save_to_dump($data, $filename)
    {
        if(!($WRITE = Functions::file_open($filename, "a"))) $return;
        if($data && is_array($data)) fwrite($WRITE, json_encode($data) . "\n");
        else                         fwrite($WRITE, $data . "\n");
        fclose($WRITE);
    }
    private function get_rows_from_dump_file($url) // utility
    {
        $path = Functions::save_remote_file_to_local($url, $this->download_options);
        $urls = array();
        foreach(new FileIterator($path) as $line_number => $line) {
            if($line) $urls[$line] = "";
        }
        unlink($path);
        return $urls;
    }
    // private function prepare_text_objects($rec)
    // {
    //     $articles = array();
    //     if(@$rec["texts"])
    //     {
    //         foreach($rec["texts"] as $type => $r) $articles[$type] = implode("<p>", $r);
    //         foreach($articles as $type => $description)
    //         {
    //             $description = trim($description);
    //             if(!$description) continue;
    //             $obj = array();
    //             $obj["description"] = "<p>" . $description;
    //             $obj["subject"] = self::get_subject($type);
    //             $obj["identifier"] = md5($rec["sciname"] . $description);
    //             $obj["type"] = "text";
    //             $obj["taxon_id"] = $rec["taxon_id"];
    //             $obj["source"] = $rec["source"];
    //             self::get_objects($obj);
    //         }
    //     }
    // }
    private function prepare_image_objects($rec)
    {
        if($imagez = @$rec["images"]) {
            foreach($imagez as $image) self::save_image_object($image, $rec);
        }
    }
    private function save_image_object($image, $rec)
    {
        $obj = array();
        $obj["description"] = $image["title"];
        $obj["title"]       = "";
        $obj["identifier"]  = md5($image["image"]);
        $obj["type"]        = "image";
        $obj["taxon_id"]    = $rec["taxon_id"];
        $obj["source"]      = $rec["source"];
        $obj["accessURI"]   = $image["image"];
        self::get_objects($obj);
    }
    private function get_objects($rec)
    {
        $mr = new \eol_schema\MediaResource();
        if($rec["type"] == "text") {
            $mr->type               = 'http://purl.org/dc/dcmitype/Text';
            $mr->format             = 'text/html';
            $mr->CVterm             = $rec["subject"];
        }
        elseif($rec["type"] == "image") {
            $mr->type               = 'http://purl.org/dc/dcmitype/StillImage';
            $mr->format             = 'image/jpeg';
            $mr->accessURI          = $rec["accessURI"];
            $mr->title              = $rec["title"];
        }
        $mr->taxonID                = $rec["taxon_id"];
        $mr->identifier             = $rec["identifier"];
        $mr->language               = 'en';
        $mr->furtherInformationURL  = $rec["source"];
        $mr->description            = $rec["description"];
        $mr->UsageTerms             = 'http://creativecommons.org/licenses/by/3.0/';
        $mr->Owner                  = '';
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->object_ids[$mr->identifier] = 1;
            $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function prepare_length_structured_data($rec)
    {
        foreach($rec["lengths"] as $len) {
            if($len) {
                $length = self::clean_length_value($len);
                $lengths = explode(";", $length);
                $lengths = array_map('trim', $lengths);
                $ctr = 0;
                foreach($lengths as $length) {
                    $rec["remark"] = $length;
                    $length_no = trim(str_replace(array("female", "male", "worker", "queen", "drone", "mm", "to apex of abdomen", "greater than"), "", $length));
                    if(!$length_no) continue;
                    if($val = self::is_range($length_no)) $length_no = $val; // "3.50 to 4.0"
                    $arr = explode("-", $length_no);
                    $arr = array_map('trim', $arr);
                    if(count($arr) == 1) {
                        $final = $length_no;
                        if(count(explode(" ", $length_no)) > 1) $rec["measurementRemarks"] = trim($length) . " (mm)";
                    }
                    else {
                        $final = self::get_average($arr[0], $arr[1]);
                        $rec["measurementRemarks"] = "Source data are expressed as a range: " . trim($length) . " mm.";
                    }
                    $final = trim(preg_replace('/\s*\([^)]*\)/', '', $final)); //remove parenthesis
                    if(preg_match('!\d+\.*\d*!', $final, $match)) $final = $match[0]; //remove letters
                    if(is_numeric(strpos(@$rec["measurementRemarks"], "Source data are expressed as a range:"))) $rec["measurementRemarks"] .= " ($final mm. average).";
                    $rec["catnum"] = "length";
                    if($ctr) $rec["catnum"] .= $ctr;
                    $rec["statistical_method"] = "http://eol.org/schema/terms/average";
                    $rec["measurementUnit"] = "http://purl.obolibrary.org/obo/UO_0000016";
                    $length_measurement = "http://purl.obolibrary.org/obo/CMO_0000013";
                    if(is_numeric(stripos($length, "wingspan")) || @$rec["wingspan"]) $length_measurement = "http://www.owl-ontologies.com/unnamed.owl#Wingspan";

                    // print_r($rec);
                    /*Array(
                        [sciname] => Euchroma gigantea
                        [taxon_id] => Euchroma_gigantea
                        [source] => http://americaninsects.net//b/euchroma-gigantea.html
                        [images] => Array()
                        [lengths] => Array(
                                [0] => 50 - 60
                            )
                        [wingspan] => 
                        [remark] => 50 - 60
                        [measurementRemarks] => Source data are expressed as a range: 50 - 60 mm. (55 mm. average).
                        [catnum] => length
                        [statistical_method] => http://eol.org/schema/terms/average
                        [measurementUnit] => http://purl.obolibrary.org/obo/UO_0000016
                    )
                    proposed: template from another resource
                    $rec = array();
                    *$rec["taxon_id"] = $taxon_id;
                    *$rec["catnum"] = $taxon_id.$d->id;
                    *$rec['measurementRemarks'] = $d->annotation;
                    *$rec['source'] = "https://www.speciesplus.net/#/taxon_concepts/$taxon_id/legal";
                    */
                    $rec['statisticalMethod'] = $rec['statistical_method'];
                    $rec['referenceID'] = $this->reference_id;
                    $mtype = $length_measurement;
                    $rec['lifeStage'] = 'http://www.ebi.ac.uk/efo/EFO_0001272'; //new DATA-1808 - Jul 2019
                    
                    /* orig. Not using TraitGeneric yet at this point.
                    self::add_string_types("true", $rec, "length", $final, $length_measurement);
                    */
                    $this->func->add_string_types($rec, $final, $mtype, "true"); //using TraitGeneric
                    
                    $this->debug[$final] = @$rec["measurementRemarks"];
                    $ctr++;
                }
            }
        }
    }
    /* we're using TraitGeneric now
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementOfTaxon = $measurementOfTaxon;
        $m->source = $rec["source"];
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        $m->referenceID = $this->reference_id;
        if($val = @$rec["statistical_method"]) $m->statisticalMethod = $val;
        if($val = @$rec["measurementUnit"]) $m->measurementUnit = $val;
        if($val = @$rec["measurementRemarks"]) $m->measurementRemarks = $val;
        $this->archive_builder->write_object_to_file($m);
    }
    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }
    */
    private function clean_length_value($length)
    {
        $length = strtolower($length);
        $length = str_replace(array("\t", "\n", "males are smaller than females", "midilinae"), "", $length);
        $length = str_replace("&gt;", "greater than", $length);
        $length = str_replace("workers", "worker", $length);
        $length = str_replace(". female", "; female", $length);
        $length = str_replace(". male", "; male", $length);
        $length = str_replace(". work", "; work", $length);
        return $length;
    }
    private function get_average($num1, $num2)
    {
        if(preg_match('!\d+\.*\d*!', $num1, $match)) $num1 = $match[0]; //remove letters
        if(preg_match('!\d+\.*\d*!', $num2, $match)) $num2 = $match[0];
        return round(($num1+$num2)/2, 1);
    }
    private function is_range($str)
    {
        $arr = explode(" to ", $str);
        if(count($arr) == 2) {
            if(is_numeric($arr[0]) && is_numeric($arr[1])) return str_replace(" to ", "-", $str);
        }
        return false;
    }
    private function make_offline_urls_unique()
    {
        $stored_offline_urls = self::get_rows_from_dump_file($this->stored_offline_urls_dump_file);
        $temp = array_keys($stored_offline_urls);
        foreach($temp as $url) self::save_to_dump($url, $this->current_offline_urls_dump_file2);
    }
}
?>