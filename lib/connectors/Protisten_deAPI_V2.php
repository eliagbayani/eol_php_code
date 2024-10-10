<?php
namespace php_active_record;
// connector: [protisten.php]
// http://content.eol.org/resources/791
class Protisten_deAPI_V2
{
    function __construct($folder, $param)
    {
        $this->resource_id = $folder;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->do_ids = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'download_wait_time' => 1000000, 'timeout' => 1200); //jenkins harvest monthly
        // 'download_attempts' => 1, 'delay_in_minutes' => 2, 
        if($val = @$param['expire_seconds']) $this->download_options['expire_seconds'] = $val;
        else                                 $this->download_options['expire_seconds'] = 60*60*12; //half day
        print_r($this->download_options); //exit;

        // $this->download_options['user_agent'] = 'User-Agent: curl/7.39.0'; // did not work here, but worked OK in USDAfsfeisAPI.php
        $this->download_options['user_agent'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)'; //worked OK!!!
        
        /* Google sheet used: This is sciname mapping to EOL PageID. Initiated by Wolfgang Bettighofer.
        https://docs.google.com/spreadsheets/d/1QnT-o-t4bVp-BP4jFFA-Alr4PlIj7fAD6RRb5iC6BYA/edit#gid=0
        */
        $this->page['main']           = 'https://www.protisten.de';
    }
    function start()
    {   
        // self::taxon_mapping_from_GoogleSheet();
        // self::write_agent();

        if($paths = self::get_main_paths()) {
            print_r($paths); //exit("\nstop 1\n");
            foreach($paths as $url) {
                echo "\nprocess [$url]\n";
                self::process_one_group($url);
                break; //debug - process only 1
            }
        }
        else exit("\nStructure changed. Investigate.\n");
        exit("\nstop 3\n");
        $this->archive_builder->finalize(true);
        if(isset($this->debug)) print_r($this->debug);
        if(!@$this->debug['does not exist']) echo "\n--No broken images!--\n";    
    }
    private function process_one_group($url)
    {
        $recs = self::get_records_per_group($url);
        foreach($recs as $rec) { print_r($rec); //exit("\nhuli 2\n");
            $url2 = $rec['data-href'];
            // $url2 = 'https://www.protisten.de/home-new/heliozoic-amoeboids/haptista-heliozoic-amoeboids/panacanthocystida-acanthocystida/acanthocystis-penardi/';
            // $url2 = 'https://www.protisten.de/home-new/testatamoeboids-infra/amoebozoa-testate/glutinoconcha/excentrostoma/centropyxis-aculeata/';
            // $url2 = 'https://www.protisten.de/home-new/bacillariophyta/bacillariophyceae/achnanthes-armillaris/';
            // $url2 = 'https://www.protisten.de/home-new/metazoa/hydrozoa/hydra-viridissima/';
            // $url2 = 'https://www.protisten.de/home-new/bacillariophyta/coscinodiscophyceae/acanthoceras-zachariasii/';
            $url2 = 'https://www.protisten.de/home-new/bac-proteo/zoogloea-ramigera/';
            $url2 = 'https://www.protisten.de/home-new/bac-proteo/macromonas-fusiformis/';
            if($html = Functions::lookup_with_cache($url2, $this->download_options)) { //echo "\n$html\n";
                if(preg_match_all("/<div class=\"elementor-widget-container\">(.*?)<\/div>/ims", $html, $arr)) {
                    // print_r($arr[1]); //exit("\nhuli 3\n");
                }

                $images1 = array();
                // background-image:url(https://www.protisten.de/wp-content/uploads/2024/06/Centropyxis-aculeata-Matrix-063-200-Mipro-P3224293-302-HID_NEW.jpg)
                if(preg_match_all("/background-image\:url\((.*?)\)/ims", $html, $arr)) {
                    print_r($arr[1]); //exit("\nhuli 3\n");
                    $images1 = $arr[1];
                }

                $images2 = array();
                if(preg_match_all("/decoding=\"async\" width=\"800\"(.*?)<\/div>/ims", $html, $arr)) {
                    foreach($arr[1] as $h) {
                        if(preg_match_all("/src=\"(.*?)\"/ims", $h, $arr2)) {
                            print_r($arr2[1]);
                            $images2 = array_merge($images2, $arr2[1]);
                        }
                    }
                }

                $final = array_merge($images1, $images2);
                $final = array_filter($final); //remove null arrays
                $final = array_unique($final); //make unique
                $final = array_values($final); //reindex key
                print_r($final);

                $tmp = array();
                $genus_dash_species = pathinfo($url2, PATHINFO_BASENAME); //e.g. zoogloea-ramigera
                foreach($final as $f) {
                    if(stripos($f, $genus_dash_species) !== false) $tmp[] = $f; //string is found
                }
                print_r($tmp); echo " return - 111";
                if(count($tmp) > 0) return $tmp;

                // last chance
                $final = array();
                if(count($tmp) == 0) {
                    if(preg_match_all("/src=\"(.*?)\"/ims", $html, $arr)) {
                        foreach($arr[1] as $str) {
                            if(stripos($str, "Asset_") !== false) continue; //string is found
                            if(stripos($str, $genus_dash_species) !== false) $final[] = $str; //string is found
                        }
                    }
                    print_r($final); echo " return - 222";
                    return $final;
                }
            }
        }
        exit("\nhuli 4\n");
    }
    private function get_records_per_group($url)
    {
        $records = array();
        // $url = 'https://www.protisten.de/home-new/bac-cya-chlorobi/'; //force assign dev only debug only
        // $url = 'https://www.protisten.de/home-new/colorless-flagellates/';
        // $url = 'https://www.protisten.de/home-new/bac-proteo/';
        // $url = 'https://www.protisten.de/home-new/heliozoic-amoeboids/';
        if($html = Functions::lookup_with_cache($url, $this->download_options)) { // echo "\n$html\n";
            if(preg_match_all("/<figure class=\"wpmf-gallery-item\"(.*?)<\/figure>/ims", $html, $arr)) { //this gives 2 records, we use the 2nd one
                print_r($arr[1]);
                $records = array(); $taken_already = array();
                foreach($arr[1] as $str) { $save = array();
                    if(preg_match("/title=\"(.*?)\"/ims", $str, $arr2)) $save['title'] = $arr2[1];
                    if(preg_match("/data-href=\"(.*?)\"/ims", $str, $arr2)) $save['data-href'] = $arr2[1];
                    if(preg_match("/target=\"(.*?)\"/ims", $str, $arr2)) $save['target'] = $arr2[1];
                    if(preg_match("/src=\"(.*?)\"/ims", $str, $arr2)) $save['src'] = $arr2[1];
                    if(preg_match("/data-lazy-src=\"(.*?)\"/ims", $str, $arr2)) $save['data-lazy-src'] = $arr2[1];

                    if($save['title'] && $save['target'] == '_blank') {
                        if(!isset($taken_already[$save['title'].$save['data-href']])) {
                            $taken_already[$save['title'].$save['data-href']] = '';
                            $records[] = $save;
                        }
                    }
                    // [66] =>  data-index="0"><div class="wpmf-gallery-icon"><div class="square_thumbnail"><div class="img_centered"><a class=" not_video noLightbox" data-lightbox="0" data-href="https://www.protisten.de/home-new/bac-cya-chlorobi/bac-chlorobi/chlorobium-luteolum/"                                       title="Chlorobium luteolum" target="_blank" data-index="0"><img decoding="async" class="wpmf_img" alt="Chlorobium luteolum" src="https://www.protisten.de/wp-content/uploads/2024/07/Asset_Pelodictyon-luteolum-025-100-P6051208-ODB_144.jpg" data-type="wpmfgalleryimg" data-lazy-src="https://www.protisten.de/wp-content/uploads/2024/07/Asset_Pelodictyon-luteolum-025-100-P6051208-ODB_144.jpg"></a></div></div></div><figcaption class="wp-caption-text gallery-caption"><i>Chlorobium<br>luteolum</i></figcaption>
                    // [76] =>  data-index="8"><div class="wpmf-gallery-icon"><div class="square_thumbnail"><div class="img_centered"><a class=" not_video noLightbox" data-lightbox="0" data-href="https://www.protisten.de/home-new/colorless-flagellates/obazoa-choanoflagellata-colorless-flagellates/salpingoeca-clarkii/" title="Salpingoeca clarkii" target="_self" data-index="8"><img decoding="async" class="wpmf_img" alt="Salpingoeca clarkii" src="https://www.protisten.de/wp-content/uploads/2024/09/Asset_Salpingoeca-clarkii-063-200-2-IMG-643-497-AQU-1.jpg" data-type="wpmfgalleryimg" data-lazy-src="https://www.protisten.de/wp-content/uploads/2024/09/Asset_Salpingoeca-clarkii-063-200-2-IMG-643-497-AQU-1.jpg"></a></div></div></div><figcaption class="wp-caption-text gallery-caption"><i>Salpingoeca<br>clarkii</i></figcaption>
                }
                print_r($records);
                // exit("\nhuli 1\n");
            }
        }
        // exit("\nstop 5\n");
        return $records;
    }
    // ================================================================================ end

    private function process_one_batch($filename)
    {   
        // $url = 'http://www.protisten.de/gallery-ALL/Galerie022.html'; //debug only - force
                // http://www.protisten.de/gallery-ALL/Galerie001.html
        $url = $this->page['pre_url'].$filename;
        echo "\nProcessing ".$url."\n";
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            if(preg_match_all("/<table border=\'0\'(.*?)<\/table>/ims", $html, $arr)) { //this gives 2 records, we use the 2nd one
                $cont = $arr[1];
                $cont = $cont[1]; // 2nd record
                if(preg_match_all("/<td align=\'center\'(.*?)<\/td>/ims", $cont, $arr2)) {
                    $rows = $arr2[1];
                    foreach($rows as $row) { //exit("\nditox 5\n");
                        /*[0] =>  width='130' bgcolor='#A5A59B'><a href='2_Acanthoceras-spec.html'>2 images<br><img  width='100' height='100'  border='0'  
                        src='thumbs/Acanthoceras_040-125_P6020240-251_ODB.jpg'><br><i>Acanthoceras</i> spec.</a>
                        */
                        // print_r($rows); exit;

                        $rec = array();
                        if(preg_match("/href=\'(.*?)\'/ims", $row, $arr)) $rec['image_page'] = $arr[1];
                        if(preg_match("/\.jpg\'>(.*?)<\/a>/ims", $row, $arr)) $rec['taxon'] = strip_tags($arr[1]);
                        // print_r($rec); exit;
                        if(@$rec['image_page'] && @$rec['taxon']) {
                            self::parse_image_page($rec);
                        }
                    }
                }
            }
        }
    }
    private function parse_image_page($rec)
    {
        $html_filename = $rec['image_page'];
        // echo "\n".$html_filename." -- ";

        $this->filenames = array();
        $this->filenames[] = $html_filename;
        
        $rec['next_pages'] = self::get_all_next_pages($this->page['image_page_url'].$html_filename);
        $rec['media_info'] = self::get_media_info($rec);
        if($rec['media_info']) self::write_archive($rec);
    }
    private function get_media_info($rec)
    {
        $media_info = array();
        if($pages = @$rec['next_pages']) {
            foreach($pages as $html_filename) {
                if($val = self::parse_media($this->page['image_page_url'].$html_filename)) $media_info[] = $val;
            }
        }
        return $media_info;
    }
    private function parse_media($url)
    {
        $m = array();
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = utf8_encode($html); //needed for this resource. May not be needed for other resources.
            $html = Functions::conv_to_utf8($html);
            $html = self::clean_str($html);
            if(preg_match("/MARK 14\:(.*?)<\/td>/ims", $html, $arr)) {
                $tmp = str_replace("&nbsp;", " ", strip_tags($arr[1]));
                if(preg_match("/\-\-\>(.*?)~~~/ims", $tmp."~~~", $arr)) $tmp = $arr[1];
                $tmp = Functions::remove_whitespace(trim($tmp));
                $m['desc'] = $tmp;
            }
            if(preg_match("/MARK 12\:(.*?)<\/td>/ims", $html, $arr)) {
                if(preg_match("/<img src=\"(.*?)\"/ims", $arr[1], $arr2)) $m['image'] = $arr2[1];
                /*
                e.g. value is:                     "pics/Acanthoceras_040-125_P6020240-251-totale_ODB.jpg"
                http://www.protisten.de/gallery-ALL/pics/Acanthoceras_040-125_P6020240-251-totale_ODB.jpg
                */
            }
            if(preg_match("/MARK 13\:(.*?)<\/td>/ims", $html, $arr)) {
                $tmp = str_replace("&nbsp;", " ", strip_tags($arr[1]));
                if(preg_match("/\-\-\>(.*?)~~~/ims", $tmp."~~~", $arr)) $tmp = $arr[1];
                $tmp = str_ireplace(' spec.', '', $tmp);
                $tmp = Functions::remove_whitespace(trim($tmp));
                $m['sciname'] = $tmp;
            }
            if(preg_match("/MARK 10\:(.*?)<\/td>/ims", $html, $arr)) {
                $tmp = str_replace("&nbsp;", " ", strip_tags($arr[1]));
                if(preg_match("/\-\-\>(.*?)~~~/ims", $tmp."~~~", $arr)) $tmp = Functions::remove_whitespace($arr[1]);
                // echo "\n[".$tmp."]\n";
                $arr = explode(":",$tmp);
                $arr = array_map('trim', $arr);
                // print_r($arr);
                $m['ancestry'] = $arr;
                
                $tmp = array_pop($arr); //last element
                $m['parent_id'] = self::format_id($arr[count($arr)-1])."-".self::format_id($tmp); //combination of last 2 immediate parents
                // echo "\n$parent\n"; exit;
            }
            // print_r($m); exit;
        }
        if(@$m['sciname'] && @$m['image']) return $m;
        else return array();
    }
    private function format_id($id)
    {
        return strtolower(str_replace(" ", "_", $id));
    }
    private function clean_str($str)
    {
        $str = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011", "", ""), " ", trim($str));
        return trim($str);
    }
    private function get_all_next_pages($url)
    {
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_replace('href="yyy.html"', "", $html);
            /*MARK 6: a href="yyy.html" (Nachfolger) -->
            	   <a href="3_Acanthoceras-spec.html"
            */
            if(preg_match("/MARK 6\:(.*?)target/ims", $html, $arr)) {
                $tmp = $arr[1];
                if(preg_match("/href=\"(.*?)\"/ims", $tmp, $arr2)) {
                    if($html_filename = $arr2[1]) {
                        if(!in_array($html_filename, $this->filenames)) {
                            $this->filenames[] = $html_filename;
                            self::get_all_next_pages($this->page['image_page_url'].$html_filename);
                        }
                        // else return $this->filenames;
                    }
                    // else return $this->filenames;
                }
            }
            // else return $this->filenames;
        }
        return $this->filenames;
    }
    private function get_main_paths()
    {
        if($html = Functions::lookup_with_cache($this->page['main'], $this->download_options)) {            
            if(preg_match('/<nav id=\"ubermenu-main-40\"(.*?)<\/nav>/ims', $html, $arr)) {
                $html = $arr[1];
                if(preg_match_all('/href=\"(.*?)\"/ims', $html, $arr)) {
                    print_r($arr[1]); //exit("\nstop 3\n");
                    $final = array();
                    foreach($arr[1] as $r) {
                        if(stripos($r, "usage") !== false) continue; //string is found
                        if(stripos($r, "home-new") !== false) $final[] = $r;; //string is found
                    }
                    print_r($final);
                    return $final;
                }    
            }
        }
        else echo "\nSite is unavailable: [".$this->page['main']."]\n";
        return false;
    }
    private function write_archive($rec)
    {
        // print_r($rec); exit;
        /* [media_info] => Array(
            [0] => Array(
                    [desc] => Scale bar indicates 50 µm. The specimen was gathered in the wetlands of national park Unteres Odertal (100 km north east of Berlin). The image was built up using several photomicrographic frames with manual stacking technique. Images were taken using Zeiss Universal with Olympus C7070 CCD camera. Image under Creative Commons License V 3.0 (CC BY-NC-SA). Der Messbalken markiert eine Länge von 50 µm. Die Probe wurde in den Feuchtgebieten des Nationalpark Unteres Odertal (100 km nordöstlich von Berlin) gesammelt. Mikrotechnik: Zeiss Universal, Kamera: Olympus C7070. Creative Commons License V 3.0 (CC BY-NC-SA). For permission to use of (high-resolution) images please contact postmaster@protisten.de.
                    [image] => pics/Acanthoceras_040-125_P6020240-251-totale_ODB.jpg
                    [sciname] => Acanthoceras spec.
                )
        */
        $i = -1;
        foreach($rec['media_info'] as $r) { $i++;
            $taxon = new \eol_schema\Taxon();
            $r['taxon_id'] = md5($r['sciname']);
            $r['source_url'] = $this->page['image_page_url'].@$rec['next_pages'][$i];
            $taxon->taxonID                 = $r['taxon_id'];
            $taxon->scientificName          = $r['sciname'];
            
            if($EOLid = @$this->taxon_EOLpageID[$r['sciname']]) $taxon->EOLid = $EOLid; // http://eol.org/schema/EOLid
            if(isset($this->remove_scinames[$r['sciname']])) continue;
            
            $taxon->parentNameUsageID       = $r['parent_id'];
            $taxon->furtherInformationURL   = $r['source_url'];
            // $taxon->taxonRank                = '';
            $taxon->higherClassification    = implode("|", $r['ancestry']);
            // echo "\n$taxon->higherClassification\n";
            // if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
            if($val = @$r['ancestry']) self::create_taxa_for_ancestry($val, $taxon->parentNameUsageID);
            if(@$r['image']) self::write_image($r);
        }
    }
    private function create_taxa_for_ancestry($ancestry, $parent_id)
    {
        // echo "\n$parent_id\n";
        // print_r($ancestry);
        //store taxon_id and parent_id
        $i = -1; $store = array();
        foreach($ancestry as $sci) {
            $i++;
            if($i == 0) $taxon_id = self::format_id($sci);
            else        $taxon_id = self::format_id($ancestry[$i-1])."-".self::format_id($sci);
            $store[] = $taxon_id;
        }
        // print_r($store);
        //write to dwc
        $i = -1;
        foreach($ancestry as $sci) {
            $i++;
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                 = $store[$i];
            $taxon->scientificName          = $sci;
            
            if($EOLid = @$this->taxon_EOLpageID[$sci]) $taxon->EOLid = $EOLid; // http://eol.org/schema/EOLid
            
            $taxon->parentNameUsageID       = @$store[$i-1];
            $taxon->higherClassification    = self::get_higherClassification($ancestry, $i);
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
        }
    }
    private function get_higherClassification($ancestry, $i)
    {
        $j = -1; $final = array();
        foreach($ancestry as $sci) {
            $j++;
            if($j < $i) $final[] = $sci;
        }
        return implode("|", $final);
    }
    private function write_agent()
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = 'Wolfgang Bettighofer';
        $r->agentRole       = 'creator';
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = 'https://www.protisten.de/';
        $r->term_mbox       = 'Wolfgang.Bettighofer@gmx.de';
        $this->archive_builder->write_object_to_file($r);
        $this->agent_id = array($r->identifier);
    }
    private function write_image($rec)
    {
        $mr = new \eol_schema\MediaResource();
        $mr->agentID                = implode("; ", $this->agent_id);
        $mr->taxonID                = $rec["taxon_id"];
        $mr->identifier             = md5($rec['image']);
        $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
        $mr->language               = 'en';
        $mr->format                 = Functions::get_mimetype($rec['image']);
        $this->debug['mimetype'][$mr->format] = '';

        $mr->accessURI              = self::format_accessURI($this->page['image_page_url'].$rec['image']);
        
        // /* New: Jun 13,2023
        if(!self::image_exists_YN($mr->accessURI)) {
            $this->debug['does not exist'][$mr->accessURI] = '';
            return;
        }
        // */
        
        $mr->furtherInformationURL  = self::format_furtherInfoURL($rec['source_url'], $mr->accessURI, $mr);
        $mr->Owner                  = "Wolfgang Bettighofer";
        $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $mr->description            = @$rec["desc"];
        if(!isset($this->obj_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->obj_ids[$mr->identifier] = '';
        }
    }
    private function image_exists_YN($image_url)
    {   /* curl didn't work
        // Initialize cURL
        $ch = curl_init($image_url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Check the response code
        if($responseCode == 200) return true;  //echo 'File exists';
        else                     return false; //echo 'File not found';
        */

        // /* fopen worked spledidly OK
        // Open file
        $handle = @fopen($image_url, 'r');
        // Check if file exists
        if(!$handle) return false; //echo 'File not found';
        else         return true; //echo 'File exists';
        // */
    }
    private function format_furtherInfoURL($source_url, $accessURI, $mr) //3rd param for debug only
    {
        /*
        Your column D e.g.
        https://www.protisten.de/gallery-ARCHIVE/gallery-ARCHIVE/pics/Zivkovicia-spectabilis-010-200-0-7054665-683-HHW.jpg.html
        My media URL e.g.
        https://www.protisten.de/gallery-ARCHIVE/pics/Zivkovicia-spectabilis-010-200-0-7054665-683-HHW.jpg
        */

        /* obsolete
        if($final = @$this->stable_urls_info[$accessURI]) return $final;
        else {
            // echo "\n----------not found in Wolfgang's spreadsheet\n";
            // print_r($mr);
            // print_r($this->stable_urls_info); //good debug
            // echo "\n[".$accessURI."]\n";
            // echo "\n[".$mr->accessURI."]\n";
            // echo "\n----------\n"; //exit;

            $this->debug['not found in Wolfgang spreadsheet'][$accessURI] = '';
            $this->debug['not found in Wolfgang spreadsheet'][$mr->accessURI] = '';

            return $source_url; //return the non-stable URL but currently working
        }
        */
        return $source_url; //return the non-stable URL but currently working
    }
    private function format_accessURI($url)
    {   /*
        https://www.protisten.de/gallery-ALL/pics/Penium-polymorphum-var-polymorphum-040-200-2-B090576-593-transversal4-WPT.jpg
        https://www.protisten.de/gallery-ARCHIVE/pics/Penium-polymorphum-var-polymorphum-040-200-2-B090576-593-transversal4-WPT.jpg
        */
        $url = str_replace("/gallery-ALL/pics/", "/gallery-ARCHIVE/pics/", $url);

        // http://www.protisten.de/gallery-ALL/../gallery-ARCHIVE/pics/Cocconeis-pediculus-040-200-2-2088285-303-transversal-FUS.jpg
        $url = str_replace("/gallery-ALL/..", "", $url);
        $url = str_replace("http://", "https://", $url);
        $url = str_replace(" ", "", $url);
        // return $url;
        // at this point $url is: https://www.protisten.de/gallery-ARCHIVE/pics/micrasterias-truncata3-jwbw.jpg

        /* as of Dec 29, 2023
        Hi Eli, 
        (only) in the part "gallery-ARCHIVE/pics/" all images now have the trailer "_NEW", e.g.
        https://www.protisten.de/gallery-ARCHIVE/pics/Acineta-flava-025-100-5308794-812-ODB_NEW.jpg
        and all the html pages in https://www.protisten.de/gallery-ARCHIVE/ address the new filenames:
        <td colspan="2"  align="left" width="400"><img src="../gallery-ARCHIVE/pics/Acineta-flava-025-100-5308794-812-ODB_NEW.jpg"></td>
        Can you work with this?
        Wolfgang */
        $url = self::add_NEW_if_needed($url);
        return $url;
    }
    private function add_NEW_if_needed($url)
    {
        if(stripos($url, "gallery-ARCHIVE/pics/") !== false) { //string is found
            $filename = pathinfo($url, PATHINFO_FILENAME);
            $last_4chars = substr($filename, -4);
            if($last_4chars != "_NEW") {
                $new_filename = $filename."_NEW";
                $url = str_replace($filename, $new_filename, $url);
            }
        }
        return $url;
    }
    /* obsolete
    function get_stable_urls_info()
    {
        $local_tsv = Functions::save_remote_file_to_local($this->stable_urls, $this->download_options);
        $i = 0;
        foreach(new FileIterator($local_tsv) as $line_number => $line) {
            $i++;
            $row = explode("\t", $line);
            if($i == 1) $fields = $row;
            else {
                $k = -1;
                $rec = array();
                foreach($fields as $field) { $k++;
                    $rec[$field] = @$row[$k];
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); break; exit;
                // Array(
                //     [Taxon] => Acanthoceras spec.
                //     [EOL page] => https://eol.org/pages/92738
                //     [furtherInfoURL] => https://www.protisten.de/gallery-ARCHIVE/Acanthoceras-spec-parch2022-2.html
                //     [mediaURL] => https://www.protisten.de/gallery-ARCHIVE/pics/Acanthoceras-040-125-P6020240-251-totale-ODB.jpg
                //     [] => 
                // )
                $furtherInfoURL = $rec['furtherInfoURL'];
                $furtherInfoURL = str_replace('"', '', $furtherInfoURL);

                $mediaURL = $rec['mediaURL'];
                $mediaURL = str_replace('"', '', $mediaURL);

                // wrong entry from Wolfgang's spreadsheet
                // https://www.protisten.de/gallery-ARCHIVE/gallery-ALL/pics/Vorticella-040-125-2-3189016-017-AQU.jpg
                $mediaURL = str_replace("gallery-ALL/", "", $mediaURL);
                // https://www.protisten.de/gallery-ARCHIVE/pics/Trachelomonas- granulosa -040-200-2-06157084-100-5-Augenfleck-ASW.jpg
                $mediaURL = str_replace(" ", "", $mediaURL);

                $this->stable_urls_info[$mediaURL] = $furtherInfoURL;
            }
        }
        unlink($local_tsv);
        // print_r($this->stable_urls_info); echo "\n".count($this->stable_urls_info)."\n";
    } */
    private function taxon_mapping_from_GoogleSheet()
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1QnT-o-t4bVp-BP4jFFA-Alr4PlIj7fAD6RRb5iC6BYA';
        $params['range']         = 'Sheet1!A2:D70'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params); // print_r($arr); exit;
        /*Array(
            [0] => Array(
                    [0] => Actinotaenium clevei
                    [1] => https://eol.org/pages/913594
                )
            [1] => Array(
                    [0] => Ankistrodesmus gracilis
                    [1] => https://eol.org/pages/6051692
            [37] => Array(
                    [0] => Edaphoallogromia australica
                    [1] => https://eol.org/pages/12155574
                    [2] => Lieberkuehnia wageneri
                    [3] => https://eol.org/pages/39306525
                )
        */
        foreach($arr as $rec) {
            $this->taxon_EOLpageID[$rec[0]] = pathinfo($rec[1], PATHINFO_BASENAME);
            if($val = @$rec[2]) $this->remove_scinames[$val] = '';
        }
        print_r($this->taxon_EOLpageID);
        print_r($this->remove_scinames); //exit;
    }
}
?>