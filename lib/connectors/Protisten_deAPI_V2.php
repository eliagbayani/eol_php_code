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
        $this->desc_suffix = '<p>© Wolfgang Bettighofer,<br />images under Creative Commons License V 3.0 (CC BY-NC-SA).<br />For permission to use of (high resolution) images please contact <a href="mailto:postmaster@protisten.de">postmaster@protisten.de</a>.</p>';
        /* To edit 1 record search for "******" and un-comment line. */
        $this->RunTest = @$param['RunTest'];
    }
    function start()
    {   
        // /* access DH - part of main operation
        require_library('connectors/EOL_DH_API');
        $this->func = new EOL_DH_API();
        $this->func->parse_DH(); $landmark_only = false; $return_completeYN = true; //default value anyway is true
        // $page_id = 46564415; //Gadus morhua
        // $page_id = 46564414; //Gadus
        // $page_id = 60963261; //Cochliopodium vestitum (Archer 1871)
        // $page_id = 61003987;
        // $ancestry = $this->func->get_ancestry_via_DH($page_id, $landmark_only, $return_completeYN); print_r($ancestry); exit("\n-end test DH-\n");
        // exit("\nexit DH test\n"); //good test OK
        // print_r($this->func->DH_canonical_EOLid);
        // print_r($this->func->DH_canonical_EOLid['Endomyxa']);
        // exit("\nchaeli\n");
        // */

        // /* part of main operation
        self::taxon_mapping_from_GoogleSheet(); //print_r($this->taxon_EOLpageID);    exit("\ncount: ".count($this->taxon_EOLpageID)."\nstop 1\n");
        // echo "\n111\n";
        // print_r($this->taxon_EOLpageID['Heleopera petricola var. amethystea']); 
        // echo "\n222\n";
        // print_r($this->taxon_EOLpageID['Heleopera petricola amethystea']); 
        // exit("\n-end 1-\n");

        self::load_legacy_taxa_data();          //print_r($this->legacy);             exit("\nstop 2\n");
        self::write_agent();
        // */

        if($paths = self::get_main_paths()) {
            print_r($paths); //exit("\nstop 1\n");
            $i = 0;
            foreach($paths as $url) { $i++; $this->report_main_url = $url;
                echo "\nprocess [$url]\n";
                self::process_one_group($url);
                // break; //debug - process only 1 just 1 group | ******
                // if($i >= 5) break; //debug only
                if($this->RunTest) break;
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
        echo "\n[image no text]: ".count(@$this->debug['image no text'])."\n";
        if(!@$this->debug['does not exist']) echo "\n--No broken images!--\n";    
    }
    private function process_one_group($url)
    {
        $recs = self::get_records_per_group($url);
        foreach($recs as $rec) { //print_r($rec); exit("\nhuli 2\n");
            /*Array(
                [title] => Teleostei egg
                [data-href] => https://www.protisten.de/home-new/metazoa/chordata/teleostei-species/
                [target] => _blank
                [src] => https://www.protisten.de/wp-content/uploads/2024/08/Asset_Fischei-STEMI-4180361-HEL.jpg
                [data-lazy-src] => https://www.protisten.de/wp-content/uploads/2024/08/Asset_Fischei-STEMI-4180361-HEL.jpg
            )
            OR
            Array(
                [title] => Achromatium oxaliferum
                [data-href] => https://www.protisten.de/home-new/bac-proteo/achromatium-oxaliferum/
                [target] => _blank
                [src] => https://www.protisten.de/wp-content/uploads/2024/07/Asset_achromatium-oxaliferum-jwbw_144.jpg
                [data-lazy-src] => https://www.protisten.de/wp-content/uploads/2024/07/Asset_achromatium-oxaliferum-jwbw_144.jpg
            )*/

            /* force assign dev only --- works OK | ******
            $rec = array();
            $rec['title'] = 'Aphanizomenon flos-aquae';
            $rec['data-href'] = 'https://www.protisten.de/home-new/bac-cya-chlorobi/bac-cya/bac-nostocales/aphanizomenon-flos-aquae/';

            // $rec['title'] = 'Aphanothece stagnina';
            // $rec['data-href'] = 'https://www.protisten.de/home-new/bac-cya-chlorobi/bac-cya/bac-chroococcales/aphanothece-stagnina/';

            // $rec['title'] = 'Spongilla lacustris';
            // $rec['data-href'] = 'https://www.protisten.de/home-new/metazoa/porifera/spongilla-lacustris/';

            // $rec['title'] = 'Chloromonas spec.';
            // $rec['data-href'] = 'https://www.protisten.de/home-new/colored-flagellates/archaeplastida-colored-flagellates/chlamydomonadales-colored-flagellates/chloromonas-spec/';
            // */

            if($test = @$this->RunTest) {
                $rec = array();
                $rec['title'] = $test['title'];
                $rec['data-href'] = $test['data-href'];    
            }

            $ret = self::process_taxon_rec($rec, $url); //print_r($ret); //2nd param $url is just for debug
            
            // $this->image_text = array(); --- NEVER initialize this
            $this->image_text_current = array(); //IMPORTANT: this has to be initialized here
            self::parse_images_and_descriptions_from_elementors($ret); //for single images, no sliders
            self::parse_images_and_descriptions_from_elementors_v2($ret); //for slider images
            // print_r($this->image_text_current); print_r($this->image_text); 
            // exit("\nelix 3\n");

            if($val = $ret['images']) $images = $val;
            else                      $images = array();
            $images = self::array_filter_unique_values($images);
            $this->report[$url][$rec['title']]['url']       = $rec['data-href'];
            $title = self::clean_sciname($rec['title']);
            $this->report[$url][$rec['title']]['DH_EOLid']  = @$this->func->DH_canonical_EOLid[$title];  //EOLid from the Katjaj's DH file
            $this->report[$url][$rec['title']]['XLS_EOLid'] = @$this->taxon_EOLpageID[$title];           //EOLid from Wolfgang's Googlespreadsheet
            $this->report[$url][$rec['title']]['images']    = $images;
            $this->report[$url][$rec['title']]['images_v2'] = $this->image_text_current;
            // print_r($this->report); exit("\n-report exit-\n"); //good debug for single rec testing | ******
            // break; //dev only process only 1 just 1 rec | ******
            if(@$this->RunTest) {
                print_r($this->report);
                $test_sciname = $this->RunTest['title'];
                $pre = $this->report['https://www.protisten.de/home-new/bac-proteo/'][$test_sciname];
                self::run_local_tests($pre, $test_sciname);
                exit("\n-end tests-\n");
            }
        } //end foreach()
        // print_r($this->report); exit("\nstopx\n");
    }
    private function process_taxon_rec($rec, $url)
    {
        $url2 = $rec['data-href']; //e.g. https://www.protisten.de/home-new/bac-proteo/achromatium-oxaliferum/

        /* force assign during dev
        $url2 = 'https://www.protisten.de/home-new/colored-flagellates/archaeplastida-colored-flagellates/chlamydomonadales-colored-flagellates/chloromonas-spec/';
        $rec['title'] = 'Chloromonas spec.';

        $url2 = 'https://www.protisten.de/home-new/metazoa/porifera/spongilla-lacustris/';
        $rec['title'] = 'Spongilla lacustris';

        $url2 = 'https://www.protisten.de/home-new/bac-cya-chlorobi/bac-cya/bac-chroococcales/aphanothece-stagnina/';
        $rec['title'] = 'Aphanothece stagnina';
        */

        if($url2 == 'https://www.protisten.de/home-new/bacillariophyta/bacillariophyceae/cymbella-spec-2/') {
            return; print_r($rec); exit("\nbroken link\n[$url]\n");
        }

        // $url2 = 'https://www.protisten.de/home-new/heliozoic-amoeboids/haptista-heliozoic-amoeboids/panacanthocystida-acanthocystida/acanthocystis-penardi/';
        // $url2 = 'https://www.protisten.de/home-new/testatamoeboids-infra/amoebozoa-testate/glutinoconcha/excentrostoma/centropyxis-aculeata/';
        // $url2 = 'https://www.protisten.de/home-new/bacillariophyta/bacillariophyceae/achnanthes-armillaris/';
        // $url2 = 'https://www.protisten.de/home-new/metazoa/hydrozoa/hydra-viridissima/';
        // $url2 = 'https://www.protisten.de/home-new/bacillariophyta/coscinodiscophyceae/acanthoceras-zachariasii/';
        // $url2 = 'https://www.protisten.de/home-new/heliozoic-amoeboids/haptista-heliozoic-amoeboids/panacanthocystida-acanthocystida/raphidocystis-tubifera/';

        // if($url2 == 'https://www.protisten.de/home-new/colorless-flagellates/obazoa-choanoflagellata-colorless-flagellates/salpingoeca-ampulloides/') {
        //     $this->download_options['expire_seconds'] = 1;
        // }

        $options = $this->download_options;
        // $options['expire_seconds'] = 1; //debug only

        if($html = Functions::lookup_with_cache($url2, $options)) { //echo "\n$html\n";
            $sciname = self::clean_sciname($rec['title']);

            // to do: differentiate eol.org with tree and without tree but just a link to eol page
            self::get_EOLid_from_HTML($html, $sciname, $rec);

            if(preg_match_all("/<div class=\"elementor-widget-container\">(.*?)<\/div>/ims", $html, $arr)) { //for single image text descriptions. Not sliders.
                $rec['elementor'] = $arr[1];
                // print_r($arr[1]); exit;
            }
            if(preg_match_all("/data-id=(.*?)<\/div>/ims", $html, $arr)) { //for slider images text descriptions.
                $rec['elementor_v2'] = $arr[1];
                $rec['html'] = $html;
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

            // <div class="elementor-element elementor-element-4d03d177 elementor-widget elementor-widget-image" data-id="4d03d177" data-element_type="widget" data-widget_type="image.default">
			// 	<div class="elementor-widget-container">
			// 												<img loading="lazy" decoding="async" width="800" height="692" src="https://www.protisten.de/wp-content/uploads/2024/01/achromatium-oxaliferum-jwbw-1.jpg" class="attachment-full size-full wp-image-1231" alt="Achromatium oxaliferum" srcset="https://www.protisten.de/wp-content/uploads/2024/01/achromatium-oxaliferum-jwbw-1.jpg 800w, https://www.protisten.de/wp-content/uploads/2024/01/achromatium-oxaliferum-jwbw-1-300x260.jpg 300w, https://www.protisten.de/wp-content/uploads/2024/01/achromatium-oxaliferum-jwbw-1-768x664.jpg 768w" sizes="(max-width: 800px) 100vw, 800px">															
            //         </div>
			// 	</div>

            // <div class="elementor-element elementor-element-c657c5e elementor-widget elementor-widget-text-editor" data-id="c657c5e" data-element_type="widget" data-widget_type="text-editor.default">
			// 	<div class="elementor-widget-container">
			// 						<p>Sampling date 09/2009. Scale bar indicates 50 µm.</p><p>Multi-layer image shows cell in conjugation sharing genetic information.</p><p>Place name: Pond situated in the vicinity of Lake Constance (Germany).<br>Latitude: 47.734945 &nbsp;&nbsp;&nbsp; Longitude: 9.091097</p><p>Microscope Zeiss Universal, camera Olympus C7070.</p>								</div>
			// 	</div>

            $pre_tmp = array_merge($images1, $images2);
            $pre_tmp = self::array_filter_unique_values($pre_tmp);
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
                if(self::invalid_media_url($f)) continue;
                if(stripos($f, $genus_name.".jpg") !== false) continue; //string is found
                
                // if(stripos($f, "Asset_") !== false) continue; //string is found
                // // /* exclude taxonomic tree image e.g. https://www.protisten.de/wp-content/uploads/2024/01/Aphanothece-e1722003443558.jpg
                // if(stripos($f, "-e1") !== false) continue; //string is found 
                // // */
                
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
            echo "\n---elix---\n"; print_r($tmp); echo " ret - 111";
            if(count($tmp) > 0) { $rec['images'] = $tmp; return $rec; }

            if(count($tmp) == 0) {
                foreach($pre_tmp as $f) {
                    // if(stripos($f, "Asset_") !== false) continue; //string is found
                    if(self::invalid_media_url($f)) continue;
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
                        // if(stripos($str, "Asset_") !== false) continue; //string is found
                        if(self::invalid_media_url($str)) continue;
                        if(stripos($str, $genus_dash_species) !== false) $final[] = $str; //string is found
                        if($genus_dash) {
                            if(stripos($str, $genus_dash) !== false) $final[] = $str; //string is found
                        }                            
                    }
                }
                print_r($final); echo " ret - 333";
                if(count($final) > 0) { $rec['images'] = $final; return $rec; }

                if(count($final) == 0) { 
                    if($genus_name) {
                        foreach($arr[1] as $str) {
                            // if(stripos($str, "Asset_") !== false) continue; //string is found
                            if(self::invalid_media_url($str)) continue;
                            if(stripos($str, $genus_name.".jpg") !== false) continue; //string is found
                            if(stripos($str, $genus_name) !== false) $final[] = $str; //string is found    
                        }
                        if(count($final) == 0) { 
                            print_r($rec); print_r($this->debug); echo("\nhuli 3 [$genus_name]\n"); //un-comment in real operation. Let there be exit()
                        }
                        else { print_r($final); echo(" 111\n"); $rec['images'] = $final; return $rec; }        
                    }
                }
            }
        }
        print_r($rec); print_r($this->debug); echo("\nhuli 4 - should not go here.\n"); //return //un-comment in real operation. Let there be exit()
        // return
    }
    private function invalid_media_url($url, $scientificName = '')
    {
        if(stripos($url, "Asset_") !== false) return true; //string is found
        if(stripos($url, "button") !== false) return true; //string is found        e.g. https://www.protisten.de/wp-content/uploads/2024/11/Special-observation-button.jpg

        // /* exclude taxonomic tree image e.g. https://www.protisten.de/wp-content/uploads/2024/01/Aphanothece-e1722003443558.jpg
        if(stripos($url, "-e1") !== false) return true; //string is found 
        // */

        // /* e.g. https://www.protisten.de/wp-content/uploads/2024/08/Spongilla.jpg
        if($scientificName) {
            $tmp_arr = explode(" ", $scientificName);
            if($genus_name = $tmp_arr[0]) {
                if($scientificName != $genus_name) {
                    echo "\nsciname: [$scientificName] | genus_name: [$genus_name]";
                    if(stripos($url, $genus_name.".jpg") !== false) return true; //string is found            
                }
            }
        }
        // */
        return false;
    }
    private function get_EOLid_from_HTML($html, $sciname, $rec)
    {
        if(preg_match("/<a href=\"https:\/\/eol.org\/(.*?)<\/a>/ims", $html, $arr)) {
            if(stripos($arr[1], "EOL-Button.jpg") !== false) { //string is found        //No EOL tree, just link to EOL page
                $this->taxon_EOLpageID_HTML[$sciname]['FamAndOrder'] = self::parse_FamAndOrder($html);
            }
            else { //with EOL tree
            }
        }

        $html = str_replace('/names"', '"', $html);                       //<a href="https://eol.org/pages/11816/names" target="_blank">
        if(preg_match_all("/eol.org\/pages\/(.*?)\"/ims", $html, $arr)) { //<a href="https://eol.org/pages/11816" target="_blank">
            $ret_arr = array_filter($arr[1]); //remove null arrays
            $ret_arr = array_unique($ret_arr); //make unique
            $ret_arr = array_values($ret_arr); //reindex key
            if(count($ret_arr) > 1) { print_r($rec); echo("\nInvestigate more than 1 EOL ID.\n"); 
                $rec['EOLid'] = $ret_arr[0]; 
                $this->taxon_EOLpageID_HTML[$sciname]['EOLid'] = $rec['EOLid'];
            } //Raphidocystis tubifera 61003987
            if(count($ret_arr) < 1) { print_r($rec); exit("\nInvestigate no EOL ID found.\n"); }
            if(count($ret_arr) == 1) { 
                $rec['EOLid'] = $ret_arr[0]; 
                $this->taxon_EOLpageID_HTML[$sciname]['EOLid'] = $rec['EOLid'];
            }
        }
        else $this->taxon_EOLpageID_HTML[$sciname]['FamAndOrder'] = self::parse_FamAndOrder($html); //should not go here anymore. But just in case.
        // print_r(@$this->taxon_EOLpageID_HTML); exit("\nstop 1\n");
    } //$rec
    private function get_records_per_group($url)
    {
        $records = array();
        // $url = 'https://www.protisten.de/home-new/bac-cya-chlorobi/'; //force assign dev only debug only
        // $url = 'https://www.protisten.de/home-new/colorless-flagellates/';
        // $url = 'https://www.protisten.de/home-new/bac-proteo/';
        // $url = 'https://www.protisten.de/home-new/heliozoic-amoeboids/';
        $this->debug['url main groups'][] = $url;
        $options = $this->download_options;
        $options['expire_seconds']= 60*60*24; //ideally 1 day cache
        if($html = Functions::lookup_with_cache($url, $options)) { // echo "\n$html\n";
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
        $final = str_ireplace(" var.", "", $final);
        $final = str_ireplace(" spec.", "", $final);
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
                $sciname = self::clean_sciname($sciname);
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
                    $html_EOLid = @$this->taxon_EOLpageID_HTML[$sciname]['EOLid'];
                    if($taxon->EOLid != $html_EOLid) {
                        echo "\n---------------------------\n"; print_r($rek); print_r($rec);
                        $this->debug['for Wolfgang']['wrong EOL id'][$sciname][$rec['url']] = "Should be [$taxon->EOLid] and not [$html_EOLid]";
                        // exit("\nEOL IDs not equal [$sciname] XLS:[$taxon->EOLid] | HTML:[$html_EOLid]\n");
                    }
                }

                if($val = @$rec['XLS_EOLid'])                                   $taxon->EOLid = $val;   //from Google spreadsheet
                elseif($val = @$rec['DH_EOLid'])                                $taxon->EOLid = $val;   //from Katja's DH
                elseif($val = @$this->taxon_EOLpageID_HTML[$sciname]['EOLid'])  $taxon->EOLid = $val;   //from website scrape
                else                                                            $taxon->EOLid = '';
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

                // /* this block will negate above
                if(isset($this->taxon_EOLpageID_HTML[$sciname]['FamAndOrder'])) {
                    $taxon->parentNameUsageID       = '';
                    $taxon->higherClassification    = '';
                    $taxon->order = @$this->taxon_EOLpageID_HTML[$sciname]['FamAndOrder']['order'];
                    $taxon->family = @$this->taxon_EOLpageID_HTML[$sciname]['FamAndOrder']['family'];
                    $taxon->genus = @$this->taxon_EOLpageID_HTML[$sciname]['FamAndOrder']['genus'];
                }
                // */

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
                $rec['scientificName'] = $taxon->scientificName;
                if(@$rec['images_v2']) self::write_image_v2($rec);

                // $arr[] = $sciname;
                // $arr[] = $rec['url'];
                // if($val = @$rec['images']) {}
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
    private function parse_FamAndOrder($html)
    {
        $final = array();
        // Family <big><strong>Arcellidae</strong></big>
        if(preg_match("/Family <big>(.*?)<\/big>/ims", $html, $arr)) {
            $final['family'] = strip_tags($arr[1]);
        }
        if(preg_match("/Order <big>(.*?)<\/big>/ims", $html, $arr)) {
            $final['order'] = strip_tags($arr[1]);
        }
        if(preg_match("/Genus <big>(.*?)<\/big>/ims", $html, $arr)) {
            $final['genus'] = strip_tags($arr[1]);
        }
        return $final;
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
                    // print_r($arr[1]); //exit("\nstop 3\n");
                    $final = array();
                    foreach($arr[1] as $r) {
                        if(stripos($r, "usage") !== false) continue; //string is found
                        if(stripos($r, "home-new") !== false) $final[] = $r;; //string is found
                    }
                    // print_r($final); exit;
                    return $final;
                }    
            }
        }
        else echo "\nSite is unavailable: [".$this->page['main']."]\n";
        return false;
    }
    private function pick_the_EOLid($sciname)
    {
            if($val = @$this->func->DH_canonical_EOLid[$sciname])       return $val; //EOLid from the Katjaj's DH file
        elseif($val = @$this->taxon_EOLpageID[$sciname])                return $val; //EOLid from Wolfgang's Googlespreadsheet
        elseif($val = @$this->taxon_EOLpageID_HTML[$sciname]['EOLid'])  return $val; //from website scrape
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
    {   /*[images] => Array(
            [0] => https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-SZX16-1-125-8172344-SSW_NEW.jpg
            [1] => https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-SZX16-1-125-8172348-SSW_NEW.jpg
            [2] => https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-SZX16-2-25-8172352-SSW_NEW.jpg
            [3] => https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-SZX16-2-25-8172353-SSW_NEW.jpg
            [4] => https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-SZX16-2-115-8172361-SSW_NEW.jpg
            [5] => https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-SZX16-2-115-8172366-SSW_NEW.jpg
            [6] => https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-IMG-20210818-094755_NEW.jpg
        )*/
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

            if($val = @$this->image_text[$image]) {
                $str = '<p>For further information about the image, please click here: <a href="'.$rec['url'].'">Link to protisten.de page</a></p>';
                $mr->description = self::format_description($val . $this->desc_suffix . $str);
            }
            else $this->debug['image no text'][$image] = $mr->furtherInformationURL;

            if(!isset($this->obj_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->obj_ids[$mr->identifier] = '';
            }
        }
    }
    private function write_image_v2($rec)
    {   /*[images_v2] => Array(
            [https://www.protisten.de/wp-content/uploads/2024/07/Asset_Apanizomenon-flos-aquae-016-100-P6030438-ODB_144.jpg] => <p>Sampling date 08/2021. Scale bar indicates 2 cm.</p><p>Huge colonies, many of them were about 1cm long! The images below show details.</p><p>Place name: Wetland Seekamper Seewiesen in Schilksee (Kiel, Germany).<br />Latitude: 54.410447     Longitude: 10.159699</p><p>Camera Olympus OM-D M5 MKII.</p>
            [https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-e1722003560374.jpg] => <p>Sampling date 08/2021. Scale bar indicates 2 cm.</p><p>Huge colonies, many of them were about 1cm long! The images below show details.</p><p>Place name: Wetland Seekamper Seewiesen in Schilksee (Kiel, Germany).<br />Latitude: 54.410447     Longitude: 10.159699</p><p>Camera Olympus OM-D M5 MKII.</p>
            [https://www.protisten.de/wp-content/uploads/2024/01/Apanizomenon-flos-aquae-016-100-P6030438-ODB_NEW.jpg] => <p>Scale bars indicate 2 mm (1, 2), 0.5 mm (3, 4) and 100 µm (5, 6).</p><p>Six images of the huge colonies.<br />Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!</p><p>Place name: Wetland Seekamper Seewiesen in Schilksee (Kiel, Germany).<br />Latitude: 54.410447     Longitude: 10.159699</p><p>Stereomicroscope Olympus SZX16, camera Olympus OM-D M5 MKII.</p>
            [https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-IMG-20210818-094755_NEW.jpg] => <p>Scale bars indicate 2 mm (1, 2), 0.5 mm (3, 4) and 100 µm (5, 6).</p><p>Six images of the huge colonies.<br />Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!</p><p>Place name: Wetland Seekamper Seewiesen in Schilksee (Kiel, Germany).<br />Latitude: 54.410447     Longitude: 10.159699</p><p>Stereomicroscope Olympus SZX16, camera Olympus OM-D M5 MKII.</p>
        )*/
        foreach($rec['images_v2'] as $image => $text_desc) { //print_r($rec); exit;
            if(self::invalid_media_url($image, $rec['scientificName'])) continue;
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

            if($val = $text_desc) {
                $str = '<p>For further information about the image, please click here: <a href="'.$rec['url'].'">Link to protisten.de page</a></p>';
                $mr->description = self::format_description($val . $this->desc_suffix . $str);
            }
            else $this->debug['image no text'][$image] = $mr->furtherInformationURL;

            if(!isset($this->obj_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->obj_ids[$mr->identifier] = '';
            }
        }
    }
    private function format_description($desc)
    {   // remove this line: "Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!"
        $beginning = "<p>Please click on";      $end = "through the slides!</p>";
        $inclusiveYN = true;                    $caseSensitiveYN = false;
        $desc = Functions::delete_all_between($beginning, $end, $desc, $inclusiveYN, $caseSensitiveYN);
        $desc = Functions::remove_whitespace($desc);
        // 2nd try
        $beginning = "Please click on";      $end = "through the slides!";
        $desc = Functions::delete_all_between($beginning, $end, $desc, $inclusiveYN, $caseSensitiveYN);
        $desc = Functions::remove_whitespace($desc);
        return $desc;
    }
    private function image_exists_YN($image_url)
    {   
        // return true; //debug only dev only
        
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
        $arr = $func->access_google_sheet($params); //print_r($arr); exit;
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
    private function array_filter_unique_values($images)
    {
        $images = array_filter($images); //remove null arrays
        $images = array_unique($images); //make unique
        $images = array_values($images); //reindex key
        return $images;
    }
    private function parse_images_and_descriptions_from_elementors($ret) //for single images, no sliders
    {   // ----- step 1
        $tmp = array(); $i = -1;
        $elementors = $ret['elementor'];
        foreach($elementors as $e) { $i++;
            $old_e = $e;
            if(self::has_place_name_OR_dimension_strings($e)) {

                // gets images 5th and 6th https://www.protisten.de/home-new/ciliophora/oligohymenophorea/hymenostomatia/tetrahymena-pyriformis/
                if(self::valid_image_url($elementors[$i-6])) {
                    $tmp[] = $elementors[$i-6];
                    $tmp[] = $e;    
                }
                if(self::valid_image_url($elementors[$i-5])) {
                    $tmp[] = $elementors[$i-5];
                    $tmp[] = $e;    
                }

                if(self::valid_image_url($elementors[$i-4])) {
                    $tmp[] = $elementors[$i-4]; //gets the 4th image. e.g. https://www.protisten.de/home-new/bacillariophyta/coscinodiscophyceae/melosira-nummuloides/
                    $tmp[] = $e;    
                }

                if(self::valid_image_url($elementors[$i-3])) {
                    $tmp[] = $elementors[$i-3]; //gets the 3rd image upwards from the text desc. e.g. https://www.protisten.de/home-new/bac-cya-chlorobi/bac-cya/bac-oscillatoriales/lyngbya-nigra/
                    $tmp[] = $e;                //the 3rd image is: https://www.protisten.de/wp-content/uploads/2024/01/Lyngbya-nigra-040-125-2-1208386-393-AQU_NEW.jpg    
                }

                if(self::valid_image_url($elementors[$i-2])) {
                    $tmp[] = $elementors[$i-2];
                    $tmp[] = $e;    
                }
                
                if(self::valid_image_url($elementors[$i-1])) {
                    $tmp[] = $elementors[$i-1];
                    $tmp[] = $e;    
                }

                // echo "\n-----\n".$elementors[$i-1]."\n-----\n$e\n";
            }
        } //end foreach() // print_r($tmp);
        // ----- step 2
        $i = 0;
        foreach($tmp as $t) { $i++;
            if ($i % 2 == 0) { //even meaning text description
                if($image_is) {
                    $t = Functions::preg_delete_all_between('style="', '"', $t);
                    if(!isset($this->image_text[$image_is])) {
                        echo "\n($i)text:[$image_is] [".$t."]\n";
                        $this->image_text[$image_is] = $t; //for saving    
                        $this->image_text_current[$image_is] = $t; //for saving    
                    }
                }
            }
            else { //odd meaning image(s)
                $image_is = "";
                if(preg_match_all("/src=\"(.*?)\"/ims", $t, $arr)) {
                    $image_is = $arr[1][0];
                    echo "\nimages: "; echo $image_is; //there is only 1 image here
                    if(count($arr[1]) > 1) exit("\nhuli stop - does not go here.\n");
                }
            }
        } //end foreach()
        // print_r($this->image_text); exit("\nstop muna 1\n");
    }
    private function valid_image_url($html_block)
    {
        if(preg_match_all("/src=\"(.*?)\"/ims", $html_block, $arr)) return true;
        return false;
    }
    private function has_place_name_OR_dimension_strings($e)
    {
        if(stripos($e, "place name:") !== false || stripos($e, "dimension:") !== false 
                                                || stripos($e, "<strong>Place names") !== false
                                                || stripos($e, "<p>Place name") !== false
                                                || stripos($e, "Microscope Zeiss") !== false
                                                || stripos($e, "Scale bar indicate") !== false
                                                ) { //string is found
            return true;
        }
        return false;
    }
    private function parse_images_and_descriptions_from_elementors_v2($ret) //for slider images
    {   // ----- step 1
        $tmp = array(); $i = -1;
        $elementors = $ret['elementor_v2'];
        echo "\n-=-=-=-=-=\n";
        foreach($elementors as $e) { $i++;
            if(self::has_place_name_OR_dimension_strings($e)) $tmp[] = $e;
        }
        if($tmp) {
            print_r($tmp); //exit("\nstopx 1\n");
            /*Array(
                [0] => "59772381" data-element_type="widget" data-widget_type="text-editor.default">
                            <div class="elementor-widget-container">
                                                <p>Sampling date 04/2006. Scale bar indicates 10 µm.</p><p><i>Achromatium oxaliferum</i> is a large colorless sulpher bacterium containing large refractile structures of calcite spherulites.</p><p>Place name: Pond Demühlen, rain storage reservoir in Kiel-Russee (Schleswig-Holstein, Germany).<br />Latitude: 54.304095     Longitude: 10.086073</p><p>Microscope Zeiss Universal, camera Olympus C7070.</p>								
                [1] => "c657c5e" data-element_type="widget" data-widget_type="text-editor.default">
                            <div class="elementor-widget-container">
                                                <p>Sampling date 09/2009. Scale bar indicates 50 µm.</p><p>Multi-layer image shows cell in conjugation sharing genetic information.</p><p>Place name: Pond situated in the vicinity of Lake Constance (Germany).<br />Latitude: 47.734945     Longitude: 9.091097</p><p>Microscope Zeiss Universal, camera Olympus C7070.</p>								
            )*/
            /*Array(
                [0] => "dc99d5c" data-element_type="widget" data-widget_type="text-editor.default">
                            <div class="elementor-widget-container">
                                                <p>Sampling date 08/2012. </p><p>Two images. <br />Underwater photos taken with an Olympus Tough. The sponge branches in the first picture were about 3 cm long and about 5 mm thick at the base.</p><p>Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!</p><p>Place name: Lake Brahmsee near Kiel (Schleswig-Holstein, Germany)   <br />Latitude: 54.202309     Longitude: 9.906453</p>						
                [1] => "6d3877e" data-element_type="widget" data-widget_type="text-editor.default">
                            <div class="elementor-widget-container">
                                                <p>Two images. <br />Some branches of the sponge were broken off and placed in a Petri dish filled with local water in the laboratory. After a few days, the loose sponge tissue had formed into a new sponge.</p><p>Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!</p><p>Place name: Lake Brahmsee near Kiel (Schleswig-Holstein, Germany)   <br />Latitude: 54.202309     Longitude: 9.906453</p><p>Dissecting microscope Zeiss SV6, camera Olympus C7070WZ. DOF images.</p>								
            )*/
            // ----- step 2: get IDs
            $IDs = array();
            foreach($tmp as $t) {
                if(preg_match("/\"(.*?)\"/ims", $t, $arr)) $IDs[$arr[1]] = '';
            }
            print_r($IDs); //exit;
            /*Array(
                [59772381] => 
                [c657c5e] => 
            )*/
            // ----- step 3: do the preg_match()
            $i = 0; $prev_ID = ''; $saved_ID_images = array(); $gotImagesYN = false;
            foreach(array_keys($IDs) as $ID) { $i++;
                if($i == 1) {
                    $arr_images = self::proc_preg_match("background-image:url", $ID, $i, $ret['html']);
                    $saved_ID_images[$ID] = $arr_images;
                    if($arr_images) $gotImagesYN = true;
                }
                else {
                    $arr_images = self::proc_preg_match($prev_ID, $ID, $i, $ret['html']);
                    $saved_ID_images[$ID] = $arr_images;
                    if($arr_images) $gotImagesYN = true;
                }
                $prev_ID = $ID;
            }
            // /* ---------- NEW to accomodate: https://www.protisten.de/home-new/bac-cya-chlorobi/bac-cya/bac-nostocales/aphanizomenon-flos-aquae/
            if(!$gotImagesYN) {
                $arr_images = self::proc_preg_match("background-image:url", $ID, 1, $ret['html']);
                $saved_ID_images[$ID] = $arr_images;
                if($arr_images) $gotImagesYN = true;
            }
            // ---------- */
            print_r($saved_ID_images); //exit("\nHere is where image(s) is/are assigned.\n[$gotImagesYN]\n");
            /*Array(
                [dc99d5c] => Array(
                        [0] => https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-P8130028_NEW.jpg
                        [1] => https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-P8130023-_NEW.jpg
                    )
                [6d3877e] => Array(
                        [0] => https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-STEMI-P8135439-441-BRA_NEW.jpg
                        [1] => https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-STEMI-P8135445-448-BRA_NEW.jpg
                    )
            )*/
            // ----- step 4: assign ID to text desc.
            $saved_ID_texts = self::assign_ID_to_text($tmp);
            print_r($saved_ID_texts); //exit("\nhuli 4\n");
            /*Array(
                [dc99d5c] => <p>Sampling date 08/2012. </p><p>Two images. <br />Underwater photos taken with an Olympus Tough. The sponge branches in the first picture were about 3 cm long and about 5 mm thick at the base.</p><p>Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!</p><p>Place name: Lake Brahmsee near Kiel (Schleswig-Holstein, Germany)   <br />Latitude: 54.202309     Longitude: 9.906453</p>
                [6d3877e] => <p>Two images. <br />Some branches of the sponge were broken off and placed in a Petri dish filled with local water in the laboratory. After a few days, the loose sponge tissue had formed into a new sponge.</p><p>Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!</p><p>Place name: Lake Brahmsee near Kiel (Schleswig-Holstein, Germany)   <br />Latitude: 54.202309     Longitude: 9.906453</p><p>Dissecting microscope Zeiss SV6, camera Olympus C7070WZ. DOF images.</p>
            )*/
            // ----- step 5: saving to: $this->image_text[$image_is] = $t; //for saving
            foreach($saved_ID_images as $ID => $images) {
                if($images) {
                    foreach($images as $image_is) {
                        $this->image_text[$image_is] = $saved_ID_texts[$ID]; //for saving
                        $this->image_text_current[$image_is] = $saved_ID_texts[$ID]; //for saving
                        // $current[$image_is] = saved_ID_texts[$ID];
                    }    
                }
            }
            // print_r($current); exit("\ncurrent exit\n");
        }
    }
    private function proc_preg_match($left, $right, $i, $html)
    {
        if($i == 1) {}
        else $left = "element.elementor-element-".$left;
        $right = "element.elementor-element-".$right;
        echo "\nleft: [$left]";
        echo "\nright: [$right]\n";

        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            // [0] => (https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-P8130028_NEW.jpg);background-size:cover;}
            if(preg_match_all("/\(https(.*?)\)/ims", $arr[1], $arr2)) {
                // print_r($arr2[1]); exit("\nok huli 1\n");
                $final = array();
                foreach($arr2[1] as $s) $final[] = 'https'.$s;
                return $final;
            }
            // exit("\nok huli 2\n");
        }
        // exit("\ndito siya\n");
    }
    private function assign_ID_to_text($tmp)
    {
        /*Array(
            [0] => "dc99d5c" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                                            <p>Sampling date 08/2012. </p><p>Two images. <br />Underwater photos taken with an Olympus Tough. The sponge branches in the first picture were about 3 cm long and about 5 mm thick at the base.</p><p>Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!</p><p>Place name: Lake Brahmsee near Kiel (Schleswig-Holstein, Germany)   <br />Latitude: 54.202309     Longitude: 9.906453</p>						
            [1] => "6d3877e" data-element_type="widget" data-widget_type="text-editor.default">
                        <div class="elementor-widget-container">
                                            <p>Two images. <br />Some branches of the sponge were broken off and placed in a Petri dish filled with local water in the laboratory. After a few days, the loose sponge tissue had formed into a new sponge.</p><p>Please click on &lt; or &gt; on the image edges or on the dots at the bottom edge of the images to browse through the slides!</p><p>Place name: Lake Brahmsee near Kiel (Schleswig-Holstein, Germany)   <br />Latitude: 54.202309     Longitude: 9.906453</p><p>Dissecting microscope Zeiss SV6, camera Olympus C7070WZ. DOF images.</p>								
        )*/
        $final = array();
        foreach($tmp as $t) {
            if(preg_match("/\"(.*?)\"/ims", $t, $arr)) {
                $ID = $arr[1];
                if(preg_match("/<div class=\"elementor-widget-container\">(.*?)elix/ims", $t."elix", $arr)) $final[$ID] = Functions::remove_whitespace($arr[1]);
            }
        }
        return $final;
    }
    private function run_local_tests($pre, $test_sciname)
    {   
        if($test_sciname == 'Aphanizomenon flos-aquae') {
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/01/Apanizomenon-flos-aquae-016-100-P6030438-ODB_NEW.jpg']) {
                if(stripos($desc, "Sampling date 05/2011") !== false) echo "\nPass OK 1"; //string is found
                else echo "\nTest Error 1a\n";
            }
            else echo "\nTest Error 1b\n";
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-IMG-20210818-094755_NEW.jpg']) {
                if(stripos($desc, "Sampling date 08/2021") !== false) echo "\nPass OK 2"; //string is found
                else echo "\nTest Error 2a\n";
            }
            else echo "\nTest Error 2b\n";
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-SZX16-1-125-8172344-SSW_NEW.jpg']) {
                if(stripos($desc, "Scale bars indicate 2 mm") !== false) echo "\nPass OK 3"; //string is found
                else echo "\nTest Error 3a\n";
            }
            else echo "\nTest Error 3b\n";
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/01/Aphanizomenon-flos-aquae-SZX16-2-115-8172366-SSW_NEW.jpg']) {
                if(stripos($desc, "Scale bars indicate 2 mm") !== false) echo "\nPass OK 4"; //string is found
                else echo "\nTest Error 4a\n";
            }
            else echo "\nTest Error 4b\n";
            if(count($pre['images'] == 7)) echo "\nPass OK 5";
            else echo "\nTest Error 5\n";
        }
        elseif($test_sciname == 'Aphanothece stagnina') {
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/01/Aphanothece-stagnina-016-100-P9284680-SIM_NEW.jpg']) {
                if(stripos($desc, "Scale bars indicate 100 µm") !== false) echo "\nPass OK 1"; //string is found
                else echo "\nTest Error 1a\n";
            }
            else echo "\nTest Error 1b\n";
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/01/Aphanothece-stagnina-025-100-P9284676-BOD_NEW.jpg']) {
                if(stripos($desc, "Scale bars indicate 100 µm") !== false) echo "\nPass OK 2"; //string is found
                else echo "\nTest Error 2a\n";
            }
            else echo "\nTest Error 2b\n";            
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/01/Aphanothece-stagnina-Epithemia-adnata-040-100-P6101397-404-PIL_NEW.jpg']) {
                if(stripos($desc, "Sampling date 05/2011") !== false) echo "\nPass OK 3"; //string is found
                else echo "\nTest Error 3a\n";
            }
            else echo "\nTest Error 3b\n";
            if(count($pre['images'] == 3)) echo "\nPass OK 4";
            else echo "\nTest Error 4\n";    
        }  
        elseif($test_sciname == 'Spongilla lacustris') {
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-P8130028_NEW.jpg']) {
                if(stripos($desc, "Sampling date 08/2012") !== false) echo "\nPass OK 1"; //string is found
                else echo "\nTest Error 1a\n";
            }
            else echo "\nTest Error 1b\n";
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-P8130023-_NEW.jpg']) {
                if(stripos($desc, "Sampling date 08/2012") !== false) echo "\nPass OK 2"; //string is found
                else echo "\nTest Error 2a\n";
            }
            else echo "\nTest Error 2b\n";
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-STEMI-P8135439-441-BRA_NEW.jpg']) {
                if(stripos($desc, "Two images.") !== false) echo "\nPass OK 3"; //string is found
                else echo "\nTest Error 3a\n";
            }
            else echo "\nTest Error 3b\n";
            if($desc = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/08/Spongilla-lacustris-STEMI-P8135445-448-BRA_NEW.jpg']) {
                if(stripos($desc, "Two images.") !== false) echo "\nPass OK 4"; //string is found
                else echo "\nTest Error 4a\n";
            }
            else echo "\nTest Error 4b\n";
            if(count($pre['images'] == 4)) echo "\nPass OK 5";
            else echo "\nTest Error 5\n";
        }
        elseif($test_sciname == 'Chloromonas spec.') {
            $desc1 = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/10/Chloromonas-040-200-2-5171985-994-1.5kV-ASW_NEW.jpg'];
            $desc2 = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/10/Chloromonas-040-200-2-5171985-994-1kV-ASW_NEW.jpg'];
            $desc3 = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/10/Chloromonas-040-200-2-5171985-994-2kV-ASW_NEW.jpg'];
            $desc4 = $pre['images_v2']['https://www.protisten.de/wp-content/uploads/2024/10/Chloromonas-040-200-2-5171985-994-1.5kV2-ASW_NEW.jpg'];
            if($desc1 == $desc2 && $desc3 == $desc4 && $desc1 == $desc4) echo "\nPass OK 1";
            else echo "\nTest Error 1\n";
            if(count($pre['images'] == 4)) echo "\nPass OK 2";
            else echo "\nTest Error 2\n";
        }  
    }
}
?>