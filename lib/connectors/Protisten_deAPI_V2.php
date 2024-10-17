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
        $this->page['main'] = 'https://www.protisten.de';
        $this->report = array(); //report for Wolfgang
        if(Functions::is_production()) $path = '/extra/other_files/protisten_de/';
        else                           $path = '/Volumes/OWC_Express/other_files/protisten_de/';
        if(!is_dir($path)) mkdir($path);
        $this->report_file = $path.'protistenDE_images.tsv';
        $this->protisten_de_legacy_taxa = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/refs/heads/master/protisten_de/protisten_2024_07_10/taxon.tab';
    }
    function start()
    {   
        // /* access DH
        require_library('connectors/EOL_DH_API');
        $this->func = new EOL_DH_API();
        $this->func->parse_DH(); $landmark_only = false; $return_completeYN = true; //default value anyway is true
        // $page_id = 46564415; //Gadus morhua
        // $page_id = 46564414; //Gadus
        // $page_id = 60963261; //Cochliopodium vestitum (Archer 1871)
        // $page_id = 52253740;
        // $ancestry = $this->func->get_ancestry_via_DH($page_id, $landmark_only, $return_completeYN); print_r($ancestry); exit("\n-end test DH-\n");
        // exit("\nexit DH test\n"); //good test OK
        // print_r($this->func->DH_canonical_EOLid);
        // print_r($this->func->DH_canonical_EOLid['Endomyxa']);
        // exit("\nchaeli\n");
        // */

        self::taxon_mapping_from_GoogleSheet(); //print_r($this->taxon_EOLpageID);    exit("\ncount: ".count($this->taxon_EOLpageID)."\nstop 1\n");
        // echo "\n111\n";
        // print_r($this->taxon_EOLpageID['Heleopera petricola var. amethystea']); 
        // echo "\n222\n";
        // print_r($this->taxon_EOLpageID['Heleopera petricola amethystea']); 
        // exit("\n-end 1-\n");

        self::load_legacy_taxa_data();          //print_r($this->legacy);             exit("\nstop 2\n");
        self::write_agent();

        if($paths = self::get_main_paths()) {
            print_r($paths); //exit("\nstop 1\n");
            $i = 0;
            foreach($paths as $url) { $i++; $this->report_main_url = $url;
                echo "\nprocess [$url]\n";
                self::process_one_group($url);
                // break; //debug - process only 1
                // if($i >= 3) break; //debug only
            }
        }
        else exit("\nStructure changed. Investigate.\n");
        self::write_dwca();
        // print_r($this->report); exit("\nprint report\n");
        /* main operation
        self::write_tsv_report();
        */
        // exit("\nstop muna\n");
        $this->archive_builder->finalize(true);
        if(isset($this->debug)) print_r($this->debug);
        if(!@$this->debug['does not exist']) echo "\n--No broken images!--\n";    
    }
    private function process_one_group($url)
    {
        $recs = self::get_records_per_group($url);
        foreach($recs as $rec) { //print_r($rec); //exit("\nhuli 2\n");
            /*Array(
                [title] => Teleostei egg
                [data-href] => https://www.protisten.de/home-new/metazoa/chordata/teleostei-species/
                [target] => _blank
                [src] => https://www.protisten.de/wp-content/uploads/2024/08/Asset_Fischei-STEMI-4180361-HEL.jpg
                [data-lazy-src] => https://www.protisten.de/wp-content/uploads/2024/08/Asset_Fischei-STEMI-4180361-HEL.jpg
            )*/

            $ret = self::process_taxon_rec($rec); //print_r($ret);
            if($val = $ret['images']) $images = $val;
            else                      $images = array();
            $images = array_filter($images); //remove null arrays
            $images = array_unique($images); //make unique
            $images = array_values($images); //reindex key
            $this->report[$url][$rec['title']]['url']       = $rec['data-href'];
            $title = self::clean_sciname($rec['title']);
            $this->report[$url][$rec['title']]['DH_EOLid']  = @$this->func->DH_canonical_EOLid[$title];  //EOLid from the Katjaj's DH file
            $this->report[$url][$rec['title']]['XLS_EOLid'] = @$this->taxon_EOLpageID[$title];           //EOLid from Wolfgang's Googlespreadsheet
            $this->report[$url][$rec['title']]['images']    = $images;

        } //end foreach()
        // print_r($this->report); exit("\nstopx\n");
    }
    private function process_taxon_rec($rec)
    {
        $url2 = $rec['data-href'];
        if($url2 == 'https://www.protisten.de/home-new/bacillariophyta/bacillariophyceae/cymbella-spec-2/') return; //page not found
        // $url2 = 'https://www.protisten.de/home-new/heliozoic-amoeboids/haptista-heliozoic-amoeboids/panacanthocystida-acanthocystida/acanthocystis-penardi/';
        // $url2 = 'https://www.protisten.de/home-new/testatamoeboids-infra/amoebozoa-testate/glutinoconcha/excentrostoma/centropyxis-aculeata/';
        // $url2 = 'https://www.protisten.de/home-new/bacillariophyta/bacillariophyceae/achnanthes-armillaris/';
        // $url2 = 'https://www.protisten.de/home-new/metazoa/hydrozoa/hydra-viridissima/';
        // $url2 = 'https://www.protisten.de/home-new/bacillariophyta/coscinodiscophyceae/acanthoceras-zachariasii/';
        // $url2 = 'https://www.protisten.de/home-new/bac-proteo/zoogloea-ramigera/';
        // $url2 = 'https://www.protisten.de/home-new/bac-proteo/macromonas-fusiformis/';
        // $url2 = 'https://www.protisten.de/home-new/bac-cya-chlorobi/bac-chlorobi/chlorobium-luteolum/';
        // $url2 = 'https://www.protisten.de/home-new/testatamoeboids-infra/amoebozoa-testate/organoconcha/pyxidicula-spec/';
        // $url2 = 'https://www.protisten.de/home-new/testatamoeboids-infra/foraminifera/foraminifera-spec/';
        if($html = Functions::lookup_with_cache($url2, $this->download_options)) { //echo "\n$html\n";

            if(preg_match_all("/eol.org\/pages\/(.*?)\/names\"/ims", $html, $arr)) {
                $ret_arr = array_filter($arr[1]); //remove null arrays
                $ret_arr = array_unique($ret_arr); //make unique
                $ret_arr = array_values($ret_arr); //reindex key
                if(count($ret_arr) > 1) { print_r($rec); echo("\nInvestigate more than 1 EOL ID.\n"); $rec['EOLid'] = $ret_arr[0]; } //Raphidocystis tubifera 61003987
                if(count($ret_arr) < 1) { print_r($rec); exit("\nInvestigate no EOL ID found.\n"); }
                if(count($ret_arr) == 1) { 
                    $rec['EOLid'] = $ret_arr[0]; 
                    // print_r($rec); print_r($ret_arr); exit("\nhuli ka\n");
                    $this->taxon_EOLpageID_HTML[$rec['title']] = $rec['EOLid'];
                }
            }

            if(preg_match_all("/<div class=\"elementor-widget-container\">(.*?)<\/div>/ims", $html, $arr)) {
                // print_r($arr[1]); //exit("\nhuli 5\n");
            }

            $images1 = array();
            // background-image:url(https://www.protisten.de/wp-content/uploads/2024/06/Centropyxis-aculeata-Matrix-063-200-Mipro-P3224293-302-HID_NEW.jpg)
            if(preg_match_all("/background-image\:url\((.*?)\)/ims", $html, $arr)) {
                print_r($arr[1]); echo " zzz\n"; //exit("\nhuli 6\n");
                $images1 = $arr[1];
            }

            $images2 = array();
            // if(preg_match_all("/decoding=\"async\" width=\"800\"(.*?)<\/div>/ims", $html, $arr)) {      //switching during dev
            if(preg_match_all("/decoding=\"async\"(.*?)<\/div>/ims", $html, $arr)) {                 //switching during dev
                // print_r($arr[1]); echo " yyy\n";
                foreach($arr[1] as $h) {
                    if(preg_match_all("/src=\"(.*?)\"/ims", $h, $arr2)) { // print_r($arr2[1]);
                        $images2 = array_merge($images2, $arr2[1]);
                    }
                }
            }

            $pre_tmp = array_merge($images1, $images2);
            $pre_tmp = array_filter($pre_tmp); //remove null arrays
            $pre_tmp = array_unique($pre_tmp); //make unique
            $pre_tmp = array_values($pre_tmp); //reindex key
            // print_r($pre_tmp);

            $tmp = array();
            // ------------------------------------------------------------------------------
            $genus_dash_species = pathinfo($url2, PATHINFO_BASENAME); //e.g. zoogloea-ramigera
            echo "\ngenus_dash_species: [$genus_dash_species]";
            // ------------------------------------------------------------------------------
            $genus_species = self::get_genus_species($rec['title']); // Aspidisca pulcherrima var. baltica
            $genus_dash_species2 = str_replace(" ", "-", $genus_species); // Gadus-morhua
            echo "\ngenus_dash_species2: [$genus_dash_species2]";
            // ------------------------------------------------------------------------------
            $genus_dash_species3 = str_replace(" ", "", $genus_species); //Gadusmorhua
            echo "\ngenus_dash_species3: [$genus_dash_species3]";
            // ------------------------------------------------------------------------------
            $addtl_synonym = self::get_addtl_synonym($html);
            $genus_dash_synonym = str_replace(" ", "-", $addtl_synonym);
            echo "\ngenus_dash_synonym: [$genus_dash_synonym]";
            // ------------------------------------------------------------------------------
            $genus_dash = false;
            if($val = self::get_genus_if_spec($rec['title'])) $genus_dash = $val;
            echo "\ngenus_dash: [$genus_dash]";

            $genus_dash2 = false; //manual assigned
            if($genus_dash == 'Foraminifera') $genus_dash2 = 'Foraminifere';

            $tmp_arr = explode(" ", $rec['title']);
            $genus_name = $tmp_arr[0];  // "Gadus"
            echo "\ngenus_name: [$genus_name]";
            // ------------------------------------------------------------------------------
            foreach($pre_tmp as $f) {
                if(stripos($f, "Asset_") !== false) continue; //string is found
                if(stripos($f, $genus_name.".jpg") !== false) continue; //string is found
                if(stripos($f, $genus_dash_species) !== false) $tmp[] = $f; //string is found
                if(stripos($f, $genus_dash_species2) !== false) $tmp[] = $f; //string is found
                if(stripos($f, $genus_dash_species3) !== false) $tmp[] = $f; //string is found
                if(stripos($f, $genus_dash_synonym) !== false) $tmp[] = $f; //string is found
                if($genus_dash) {
                    if(stripos($f, $genus_dash) !== false) $tmp[] = $f; //string is found
                }
                if($genus_dash2) {
                    if(stripos($f, $genus_dash2) !== false) $tmp[] = $f; //string is found
                }
            }
            print_r($tmp); echo " ret - 111";
            if(count($tmp) > 0) { $rec['images'] = $tmp; return $rec; }

            if(count($tmp) == 0) {
                foreach($pre_tmp as $f) {
                    if(stripos($f, "Asset_") !== false) continue; //string is found
                    if(stripos($f, $genus_name.".jpg") !== false) continue; //string is found
                    if(stripos($f, $genus_name) !== false) $tmp[] = $f; //string is found    
                }
                print_r($tmp); echo " ret - 222";
            }
            if(count($tmp) > 0) { $rec['images'] = $tmp; return $rec; }

            // last chance
            $final = array();
            if(count($tmp) == 0) {
                if(preg_match_all("/src=\"(.*?)\"/ims", $html, $arr)) {
                    foreach($arr[1] as $str) {
                        if(stripos($str, "Asset_") !== false) continue; //string is found
                        if(stripos($str, $genus_dash_species) !== false) $final[] = $str; //string is found
                        if($genus_dash) {
                            if(stripos($str, $genus_dash) !== false) $final[] = $str; //string is found
                        }                            
                    }
                }
                print_r($final); echo " ret - 222";
                if(count($final) > 0) { $rec['images'] = $final; return $rec; }

                if(count($final) == 0) { 
                    if($genus_name) {
                        foreach($arr[1] as $str) {
                            if(stripos($str, "Asset_") !== false) continue; //string is found
                            if(stripos($str, $genus_name.".jpg") !== false) continue; //string is found
                            if(stripos($str, $genus_name) !== false) $final[] = $str; //string is found    
                        }
                        if(count($final) == 0) { 
                            print_r($rec); print_r($this->debug); exit("\nhuli 3 [$genus_name]\n"); 
                        }
                        else { print_r($final); echo(" 111\n"); $rec['images'] = $final; return $rec; }        
                    }
                }
            }
        }
        print_r($rec); print_r($this->debug); exit("\nhuli 4 - should not go here.\n"); //return
    }
    private function get_records_per_group($url)
    {
        $records = array();
        // $url = 'https://www.protisten.de/home-new/bac-cya-chlorobi/'; //force assign dev only debug only
        // $url = 'https://www.protisten.de/home-new/colorless-flagellates/';
        // $url = 'https://www.protisten.de/home-new/bac-proteo/';
        // $url = 'https://www.protisten.de/home-new/heliozoic-amoeboids/';
        $this->debug['url in question'][] = $url;
        if($html = Functions::lookup_with_cache($url, $this->download_options)) { // echo "\n$html\n";
            if(preg_match_all("/<figure class=\"wpmf-gallery-item\"(.*?)<\/figure>/ims", $html, $arr)) { //this gives 2 records, we use the 2nd one
                // print_r($arr[1]); echo " - ito siya\n";
                $records = array(); $taken_already = array();
                foreach($arr[1] as $str) { $save = array();
                    if(preg_match("/title=\"(.*?)\"/ims", $str, $arr2)) $save['title'] = $arr2[1];
                    if(preg_match("/data-href=\"(.*?)\"/ims", $str, $arr2)) {
                        $save['data-href'] = $arr2[1];
                        if(substr($save['data-href'], -4) == '.jpg') continue;
                    }

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
    private function get_addtl_synonym($html)
    {   // <p><strong>Add&#8217;l Synonyms: </strong><em>Pelodictyon luteolum</em> (Schmidle) Pfennig and Trüper 1971</p>
        if(preg_match("/Add&#8217;l Synonyms:(.*?)\"/ims", $html, $arr)) {
            if(preg_match("/<em>(.*?)<\/em>/ims", $arr[1], $arr)) return trim($arr[1]);
        }
        echo "\nNo synonyms...\n";
    }
    private function get_genus_if_spec($title)
    {
        if($title == 'Echinodermata larva') return 'Echinodermenlarve';
        elseif($title == 'Teleostei egg') return 'Fischei-STEMI-4180361-HEL_NEW'; // https://www.protisten.de/wp-content/uploads/2024/08/Fischei-STEMI-4180361-HEL_NEW.jpg

        if(stripos($title, " spec.") !== false) { //string is found
            $arr = explode(" ", $title); // "Pyxidicula spec."
            return $arr[0]; // "Pyxidicula"
        }
        if(stripos($title, " species") !== false) { //string is found
            $arr = explode(" ", $title); //Foraminifera species
            return $arr[0]; // "Foraminifera"
        }
    }
    private function clean_sciname($name)
    {
        $final = trim($name);
        if(stripos($name, " spec.") !== false) { //string is found
            $arr = explode(" ", $name); // "Pyxidicula spec."
            $final = $arr[0]; // "Pyxidicula"
        }
        if(stripos($name, " species") !== false) { //string is found
            $arr = explode(" ", $name); //Foraminifera species
            $final = $arr[0]; // "Foraminifera"
        }

        $final = str_ireplace(" var. ", " ", $final);
        return trim($final);
    }
    private function get_genus_species($title)
    {   // "Cyphoderia ampulla (Ichthyosquama loricaria)"
        // "Aspidisca pulcherrima var. baltica"
        $string = trim(preg_replace('/\s*\([^)]*\)/', '', $title)); //remove parenthesis OK
        $string = str_replace(" var. ", " ", $string);
        return $string;
    }
    private function write_dwca()
    {   echo "\nlegacy taxa count: ".count($this->legacy)."\n";
        foreach($this->report as $url_group => $rek) {
            foreach($rek as $sciname => $rec) { //print_r($rec); exit;
                /*Array(
                    [url] => https://www.protisten.de/home-new/bac-proteo/achromatium-oxaliferum/
                    [DH_EOLid] => 898974
                    [XLS_EOLid] => 
                    [images] => Array(
                            [0] => https://www.protisten.de/wp-content/uploads/2024/01/achromatium-oxaliferum-jwbw-1.jpg
                            [1] => https://www.protisten.de/wp-content/uploads/2024/01/achromatium-oxaliferum-tandem-w.jpg
                        )
                )*/

                // /* format massage names
                $sciname = str_ireplace(" spec.", "", $sciname);
                $sciname = trim(preg_replace('/\s*\([^)]*\)/', '', $sciname)); //remove parenthesis OK // Cyphoderia ampulla (Ichthyosquama loricaria)
                // */

                if($legacy = @$this->legacy[$sciname]) {
                    print_r($legacy); echo "$sciname - found in legacy\n";
                    /*Array(
                        [taxonID] => 592e6d83ad97bbdf341d1f4fb4141fd8
                        [parentNameUsageID] => euglyphida-cyphoderiidae
                        [higherClassification] => Eucaryota|SAR (Stramenopiles, Alveolates, Rhizaria)|Rhizaria|Cercozoa|Imbricatea|Silicofilosea|Euglyphida|Cyphoderiidae
                    )*/
                }
                else { echo "\n-------------\n[$sciname]\n"; echo(" - not found in legacy\n"); }

                $taxon = new \eol_schema\Taxon();
                $taxon->scientificName = $sciname;

                /*
                [Cochliopodium vestitum] => Array
                                (
                                    [url] => https://www.protisten.de/home-new/naked-amoeboids/amoebozoa-naked/discosea/himantismenida/cochliopodiidae/cochliopodium-vestitum/
                                    [DH_EOLid] => 
                                    [XLS_EOLid] => 60963261
                [Mastigamoeba aspera] => Array
                    (
                        [url] => https://www.protisten.de/home-new/naked-amoeboids/amoebozoa-naked/evosea/archamoebae/mastigamoeba-aspera/
                        [DH_EOLid] => 
                        [XLS_EOLid] => 
                [Pelomyxa palustris] => Array
                    (
                        [url] => https://www.protisten.de/home-new/naked-amoeboids/amoebozoa-naked/evosea/archamoebae/pelomyxa-palustris/
                        [DH_EOLid] => 491173
                        [XLS_EOLid] => 
                */

                // /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                if($taxon->EOLid = @$this->taxon_EOLpageID[$sciname]) {
                    $html_EOLid = @$this->taxon_EOLpageID_HTML[$sciname];
                    if($taxon->EOLid != $html_EOLid) {
                        echo "\n---------------------------\n"; print_r($rek); print_r($rec);
                        $this->debug['for Wolfgang']['wrong EOL id'][$sciname][$rec['url']] = "Should be [$taxon->EOLid] and not [$html_EOLid]";
                        // exit("\nEOL IDs not equal [$sciname] XLS:[$taxon->EOLid] | HTML:[$html_EOLid]\n");
                    }
                }

                if($val = @$rec['XLS_EOLid'])                           $taxon->EOLid = $val;   //from Google spreadsheet
                elseif($val = @$rec['DH_EOLid'])                        $taxon->EOLid = $val;   //from Katja's DH
                elseif($val = @$this->taxon_EOLpageID_HTML[$sciname])   $taxon->EOLid = $val;   //from website scrape
                else                                                    $taxon->EOLid = '';
                // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */


                if($val = $taxon->EOLid) {
                    $taxon->taxonID = $val;
                    if($ancestry = $this->func->get_ancestry_via_DH($val, false, true)) { //2nd param: landmark_only | 3rd param: return_completeYN
                        /*Array(
                            [0] => Array(
                                    [EOLid] => 6865
                                    [canonical] => Brachionidae
                            [1] => Array(
                                    [EOLid] => 6851
                                    [canonical] => Rotifera
                        */
                        $taxon->parentNameUsageID = $ancestry[0]['EOLid'];
                        $taxon->higherClassification    = self::get_higherClassification($ancestry);
                        self::create_taxa_for_ancestry($ancestry);

                    }
                }
                else $taxon->taxonID = md5($sciname);

                /*
                if($legacy) {
                    $taxon->taxonID = $legacy['taxonID'];
                    $taxon->parentNameUsageID = $legacy['parentNameUsageID'];
                    $taxon->higherClassification = $legacy['higherClassification'];
                }
                else {
                    $taxon->taxonID = md5($sciname);
                    @$this->debug['no ID']++;
                }
                */

                $rec['taxonID'] = $taxon->taxonID;
                $taxon->furtherInformationURL = $rec['url'];

                // if(isset($this->remove_scinames[$r['sciname']])) continue; //will need to confirm if still going to be used        
                // if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids); //copied template
                if(!isset($this->taxon_ids[$taxon->taxonID])) {
                    $this->archive_builder->write_object_to_file($taxon);
                    $this->taxon_ids[$taxon->taxonID] = '';
                }
                if(@$rec['images']) self::write_image($rec);

                // $arr[] = $sciname;
                // $arr[] = $rec['url'];
                // if($val = @$rec['images']) {
                // }
            }
        }
        // exit("\nstop muna a\n");
    }
    private function load_legacy_taxa_data()
    {   $options = $this->download_options;
        $options['expire_seconds'] = false;
        $local_tsv = Functions::save_remote_file_to_local($this->protisten_de_legacy_taxa, $options);
        $i = 0;
        foreach(new FileIterator($local_tsv) as $line_number => $line) { $i++;
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
            }
            /*Array(
                [taxonID] => cca346e3c523a12c1532c45d6de2ad98
                [furtherInformationURL] => http://www.protisten.de/gallery-ALL/2-Acanthoceras-spec.html
                [parentNameUsageID] => chaetocerotales-acanthocerotaceae
                [scientificName] => Acanthoceras zachariasii
                [higherClassification] => Eucaryota|SAR (Stramenopiles, Alveolates, Rhizaria)|Stramenopiles|Ochrophyta|Bacillariophyta|Coscinodiscophyceae|Coscinodiscophycidae|Chaetocerotales|Acanthocerotaceae
                [EOLid] => 
            )*/
            if($sciname = @$rec['scientificName']) {
                $this->legacy[$sciname] = array('taxonID' => $rec['taxonID'], 'parentNameUsageID' => $rec['parentNameUsageID'], 'higherClassification' => $rec['higherClassification']);
            }
        }
        unlink($local_tsv);
    }
    private function write_tsv_report()
    {
        $WRITE = Functions::file_open($this->report_file, "w");
        foreach($this->report as $url_group => $rek) {
            $arr = array();
            $arr[] = $url_group;
            fwrite($WRITE, implode("\t", $arr)."\n");
            // ----------------------------------------------
            foreach($rek as $sciname => $rec) {
                $arr = array();
                $arr[] = "";
                $arr[] = $sciname;
                $arr[] = $rec['url'];
                fwrite($WRITE, implode("\t", $arr)."\n");
                // ----------------------------------------------
                if($val = @$rec['images']) {
                    foreach($val as $img) {
                        $arr = array();
                        $arr[] = "";
                        $arr[] = "";
                        $arr[] = $img;
                        $arr[] = '-enter description here-';
                        fwrite($WRITE, implode("\t", $arr)."\n");
                    }    
                }
                // ----------------------------------------------
            }
        }
        fclose($WRITE);
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
    private function pick_the_EOLid($sciname)
    {
            if($val = @$this->func->DH_canonical_EOLid[$sciname])   return $val; //EOLid from the Katjaj's DH file
        elseif($val = @$this->taxon_EOLpageID[$sciname])            return $val; //EOLid from Wolfgang's Googlespreadsheet
        elseif($val = @$this->taxon_EOLpageID_HTML[$sciname])       return $val; //from website scrape
        else return "";
    }
    private function create_taxa_for_ancestry($ancestry)
    {   /*Array(
            [0] => Array(
                    [EOLid] => 6865
                    [canonical] => Brachionidae
            [1] => Array(
                    [EOLid] => 6851
                    [canonical] => Rotifera
        */
        $i = -1;
        foreach($ancestry as $a) { $i++;
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                 = $a['EOLid'];
            $taxon->scientificName          = $a['canonical'];
            $taxon->EOLid = self::pick_the_EOLid($taxon->scientificName); // http://eol.org/schema/EOLid
            $taxon->parentNameUsageID       = @$ancestry[$i+1]['EOLid'];

            /* decided not to add higherClassification for ancestry taxa
            if($val = $taxon->EOLid) {
                if($ancestry2 = $this->func->get_ancestry_via_DH($val, false, true)) { //2nd param: landmark_only | 3rd param: return_completeYN
                    // Array(
                    //     [0] => Array(
                    //             [EOLid] => 6865
                    //             [canonical] => Brachionidae
                    //     [1] => Array(
                    //             [EOLid] => 6851
                    //             [canonical] => Rotifera
                    $taxon->higherClassification    = self::get_higherClassification($ancestry2);
                }
            }
            */

            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
        }
    }
    private function get_higherClassification($ancestry)
    {   /*Array(
            [0] => Array(
                    [EOLid] => 6865
                    [canonical] => Brachionidae
            [1] => Array(
                    [EOLid] => 6851
                    [canonical] => Rotifera
        */
        $final = array();
        foreach($ancestry as $a) $final[] = $a['canonical'];
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
        foreach($rec['images'] as $image) {
            $mr = new \eol_schema\MediaResource();
            $mr->agentID                = implode("; ", $this->agent_id);
            $mr->taxonID                = $rec["taxonID"];
            $mr->identifier             = md5($image);
            $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
            $mr->language               = 'en';
            $mr->format                 = Functions::get_mimetype($image);
            $this->debug['mimetype'][$mr->format] = '';
            $mr->accessURI              = $image;
            
            // /* New: Jun 13,2023
            if(!self::image_exists_YN($mr->accessURI)) {
                $this->debug['does not exist'][$mr->accessURI] = '';
                continue;
            }
            // */
            
            $mr->furtherInformationURL  = $rec['url'];
            $mr->Owner                  = "Wolfgang Bettighofer";
            $mr->UsageTerms             = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $mr->description            = ''; //waiting on Wolfgang
            if(!isset($this->obj_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->obj_ids[$mr->identifier] = '';
            }
        }
    }
    private function image_exists_YN($image_url)
    {   
        return true; //debug only dev only
        /* curl didn't work
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
    {   /* Google sheet used: This is sciname mapping to EOL PageID. Initiated by Wolfgang Bettighofer.
        https://docs.google.com/spreadsheets/d/1QnT-o-t4bVp-BP4jFFA-Alr4PlIj7fAD6RRb5iC6BYA/edit#gid=0
        */
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1QnT-o-t4bVp-BP4jFFA-Alr4PlIj7fAD6RRb5iC6BYA';
        $params['range']         = 'Sheet1!A2:D200'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params, false); //print_r($arr); exit;
        /* IMPORTANT: 2nd parm boolean if false it will expire cache. It true (default) it will use cache. */
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
            if($val = @$rec[1]) { //https://eol.org/pages/38982105/names
                if(preg_match("/\/pages\/(.*?)\/names/ims", $val, $arr)) { //e.g. https://eol.org/pages/52309510/names
                    $this->taxon_EOLpageID[$rec[0]] = $arr[1];
                }
                elseif(preg_match("/\/pages\/(.*?)elicha/ims", $val."elicha", $arr)) { //e.g. https://eol.org/pages/90436
                    $this->taxon_EOLpageID[$rec[0]] = $arr[1];
                }
            }
            // if($val = @$rec[2]) $this->remove_scinames[$val] = ''; //seems obsolete already
        }
        // print_r($this->taxon_EOLpageID['Heleopera petricola var. amethystea']); exit("\nend 2\n");
        // print_r($this->remove_scinames); exit; //seems obsolete already
    }
}
?>