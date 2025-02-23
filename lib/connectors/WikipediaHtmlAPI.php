<?php 
namespace php_active_record;
/* connector: [wikipedia_html.php]
This generates a single HTML page for every wikipedia-xxx.tar.gz. It gets one text object and generates an HTML page for it.
The subject used is ---> CVterm == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description"
*/
class WikipediaHtmlAPI
{
    function __construct()
    {
        $this->debug = array();
        $this->download_options = array(
            'expire_seconds'     => 60*60*24*30, //expires in 1 month
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        
        $this->html_path = CONTENT_RESOURCE_LOCAL_PATH . "/reports/wikipedia_html/";
        if(!is_dir($this->html_path)) mkdir($this->html_path);

        //https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/taxon_wiki_per_language_count_2023_08.txt
        $this->source_languages = CONTENT_RESOURCE_LOCAL_PATH."reports/taxon_wiki_per_language_count_YYYY_MM.txt";
        // /* temporary until the above file with changing YYYY_MM is working again.
        $this->source_languages = CONTENT_RESOURCE_LOCAL_PATH."reports/taxon_wiki_per_language_count_2023_08.txt";
        // */
        // used as list of langs to generate HTML for
    }
    function start()
    {
        $txt_file = self::get_languages_list(); // exit("\n$txt_file\n");
        $i = 0;
        foreach(new FileIterator($txt_file) as $line => $row) { $i++; 
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //print_r($rec); exit;
                /* Array(
                    [language] => ceb
                    [count] => 1729986
                ) */
                if($rec['language'] == "en")        $filename = "80";
                elseif($rec['language'] == "de")    $filename = "957";
                else                                $filename = "wikipedia-".$rec['language']; //mostly goes here
                self::save_taxon_text_to_html($filename);
            }
        }
        echo "\nNo DwCA: ".count(@$this->debug['[No DwcA]'])."\n";
        print_r($this->debug);
        self::generate_main_html_page(); //uses ["reports/wikipedia_html/*.html"] in eol-archive to select HTML to be included in main.html.
        /* To do:
        self::generate_main_html_page2(); //uses [taxon_wiki_per_language_count_2023_08.txt] to select HTML to be included in main.html.
        That is to have a descending order of total taxa in main.html.
        */
    }
    private function generate_main_html_page()
    {
        $dir = CONTENT_RESOURCE_LOCAL_PATH."reports/wikipedia_html/";
        $files = glob($dir . "*.html");
        if($files) {
            $filecount = count($files); echo "\nHTML count: [$filecount]\n";
            print_r($files);
            /* Array(
                [0] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/wikipedia_html/80.html
                [1] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/wikipedia_html/ceb.html
                [2] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/wikipedia_html/nl.html
            )*/

            if(Functions::is_production())  $path = "https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/wikipedia_html/";
            else                            $path = "http://localhost/eol_php_code/applications/content_server/resources_3/reports/wikipedia_html/";

            $main_html = $dir."main.html";

            $OUT = fopen($main_html, "w");
            $first = self::get_first_part_of_html();
            fwrite($OUT, $first);

            foreach($files as $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME); //e.g. be-x-old for be-x-old.html
                if($filename == "main") continue;
                $href = $path.$filename.".html";
                $anchor = "<a href = '$href'>$filename</a> &nbsp;|&nbsp; ";
                fwrite($OUT, $anchor);
            }
            fwrite($OUT, "</body></html>");
            fclose($OUT);
        }
    }
    private function get_first_part_of_html()
    {
        return '<!DOCTYPE html>
        <html><head>
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
            <title>Wikipedia Languages Test HTML</title>
        </head><body>';
    }
    function save_taxon_text_to_html($filename)
    {
        $dwca = CONTENT_RESOURCE_LOCAL_PATH . "/$filename".".tar.gz";
        if(!file_exists($dwca)) {
            $this->debug['No DwcA'][$filename] = '';
            return;
        }
        // /* un-comment in real operation
        if(!($info = self::prepare_archive_for_access($dwca))) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        foreach($tables['http://eol.org/schema/media/document'] as $tbl) { //always just 1 record
            $tbl = (array) $tables['http://eol.org/schema/media/document'][0];
            // print_r($tbl); exit;
            // [location] => media_resource.tab
            // [file_uri] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_41336//media_resource.tab
            $media_tab = $tbl["file_uri"];
            if(file_exists($media_tab)) self::process_extension($media_tab, $filename);    
            else {
                $this->debug['media tab does not exist'][$filename] = '';
            }
        }
        // */

        /* during dev only
        $tbl = array();
        $tbl["location"] = "media_resource.tab";
        $tbl["file_uri"] = "/Volumes/AKiTiO4/eol_php_code_tmp/dir_41651";
        $media_tab = $tbl["file_uri"]."/".$tbl["location"];
        echo "\n -- Processing [$tbl[location]]...\n";
        self::process_extension($media_tab, $filename);
        */

        // remove temp dir
        // /* main operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
        if(isset($this->debug)) print_r($this->debug);
    }
    private function prepare_archive_for_access($dwca)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        // /* main operation
        $paths = $func->extract_archive_file($dwca, "meta.xml", array('timeout' => 172800, 'expire_seconds' => 60*60*24*30)); //1 month expires
        // print_r($paths); exit;
        // */

        /* during dev only
        // print_r($paths); exit;
        $paths = Array(
            "archive_path"  => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_03066/",
            "temp_dir"      => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_03066/"
        );
        */

        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields) ||
           !($tables["http://eol.org/schema/media/document"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate. [$dwca]");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }
    private function process_extension($media_tab, $filename)
    {   //echo "\ngoes here 1\n";
        $i = 0; $savedYN = false;
        foreach(new FileIterator($media_tab) as $line => $row) { $i++; 
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); // print_r($rec); exit;
            
                if($rec['CVterm'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description") { //echo "\ngoes here 2\n";
                    $desc = $rec['description'];
                    $lang = $rec['language'];
                    /* Designed when generating individual HTML pages for languages, NOT to remove sections.
                    And remove_start_ending_chars() should have been run already. --- Comment in real operation.
                    It was only added here during development of these 2 functions.
                    $desc = self::remove_start_ending_chars($desc, $lang);
                    $desc = self::remove_wiki_sections($desc, $rec); //$rec here is just for debug
                    */
                    self::save_to_html($desc, $filename);
                    $savedYN = true;
                }
                else continue;
                /* the shorter text object
                if($rec['CVterm'] == "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology") self::save_to_html($rec['description'], $filename);
                else continue;
                */
                break; //part of main operation, process only 1 record.
                // if($i >= 100) break; //debug only
            }
        }
        if(!$savedYN) $this->debug['no HTML page generated'][$filename];
    }
    private function save_to_html($desc, $filename)
    {
        $filename = str_replace("wikipedia-","",$filename);
        $html_file = $this->html_path.$filename.".html";
        $WRITE = fopen($html_file, "w");

        $first = self::get_first_part_of_html();
        fwrite($WRITE, $first);
        fwrite($WRITE, $desc);
        fwrite($WRITE, "</body></html>");
        fclose($WRITE);
    }
    private function get_languages_list()
    {
        $ym = date("Y_m"); //2023_08 for Aug 2023 --- current month
        $txt_file = str_replace("YYYY_MM", $ym, $this->source_languages);
        if(file_exists($txt_file)) return $txt_file;
        else {
            $minus_1_month = date("Y_m", strtotime("-1 months")); //minus 1 month --- 2023_07
            $txt_file = str_replace("YYYY_MM", $minus_1_month, $this->source_languages);
            if(file_exists($txt_file)) return $txt_file;
            else {
                $minus_2_month2 = date("Y_m", strtotime("-2 months")); //minus 2 month2 --- 2023_06
                $txt_file = str_replace("YYYY_MM", $minus_2_month2, $this->source_languages);
                if(file_exists($txt_file)) return $txt_file;
                elseif(file_exists($this->source_languages)) return $this->source_languages; //new, default to an old legacy file
                else exit("\nInvestigate: No source text file for list of languages [$txt_file].\n");
            }
        }
    }
    function remove_start_ending_chars($html, $language = false) //NEW: Dec 2024
    {
        // remove bad starting chars e.g. '>'
        /* other bad staring chars from other languages
        lang="ab" dir="ltr">
        lang="ang" dir="ltr">
        lang="atj" dir="ltr">
        lang="fj" dir="ltr">
        */
        $bad_start_strings = array(">");
        if($language) $bad_start_strings[] = 'lang="'.$language.'" dir="ltr">';
        foreach($bad_start_strings as $bad_starting_chars) {
            $starting_chars = substr($html, 0, strlen($bad_starting_chars));
            if($starting_chars == $bad_starting_chars) {
                $html = substr($html, strlen($bad_starting_chars), strlen($html));
                $html = trim($html);
            }    
        }

        // remove bad ending chars e.g. '<div class="'
        $html = trim($html);
        $bad_ending_chars = '<div class="';
        $ending_chars = substr($html, strlen($bad_ending_chars)*-1);
        if($ending_chars == $bad_ending_chars) {
            $html = substr($html, 0, strlen($html) - strlen($bad_ending_chars));
        }
        return $html;
    }
    function remove_wiki_sections($html, $rec = array()) //NEW: Dec 2024 | $rec here is just for debug
    {
        // below start remove wiki sections:
        $sections = array('<h2 id="See_also">See also</h2>', '<h2 id="Notes">Notes</h2>', '<h2 id="References">References</h2>', '<h2 id="External_links">External links</h2>');
        $sections[] = '<h2 id="Footnotes">Footnotes</h2>';
        $sections[] = '<h2 id="Notes_and_references">Notes and references</h2>';
        $sections[] = '<h2 id="Cited_references">Cited references</h2>';
        $sections[] = '<h2 id="_References"> References</h2>';
        $sections[] = '<h2 id="General_references">General references</h2>';
        $sections[] = '<h2 id="External_links_and_references">External links and references</h2>';
        $sections[] = '<h2 id="References_and_external_links">References and external links</h2>';
        $sections[] = '<h2 id="Sources">Sources</h2>';
        $sections[] = '<h2 id="Citations">Citations</h2>';
        $sections[] = '<h2 id="General_References">General References</h2>';
        $sections[] = '<h2 id="Bibliography_and_References">Bibliography and References</h2>';
        $sections[] = '<h2 id="External_links_and_reference">External links and reference</h2>';
        $sections[] = '<h2 id="Footnotes_and_references">Footnotes and references</h2>';
        $sections[] = '<h2 id="Footnotes_&_References"><span id="Footnotes_.26_References"></span>Footnotes & References</h2>';
        $sections[] = '<h2 id="References[6]"><span id="References.5B6.5D"></span>References<sup id="cite_ref-6"><a href="#cite_note-6"><span>[</span>6<span>]</span></a></sup></h2>';
        $sections[] = '<h2 id="Literature_cited">Literature cited</h2>';
        $sections[] = '<h2 id="References_and_links">References and links</h2>';
        $sections[] = '<h2 id="References_and_further_reading">References and further reading</h2>';
        $sections[] = '<h2 id="References====External_links"><span id="References.3D.3D.3D.3DExternal_links"></span>References====External links</h2>';
        $sections[] = '<h2 id="References_links">References links</h2>';
        $sections[] = '<h2 id="Reference">Reference</h2>';
        $sections[] = '<h2 id="References_and_notes">References and notes</h2>';
        $sections[] = '<h2 id="Reference_List">Reference List</h2>';
        $sections[] = '<h2 id="Reference_list">Reference list</h2>';
        $sections[] = '<h2 id="References.">References.</h2>';
        $sections[] = '<h2 id="References,"><span id="References.2C"></span>References,</h2>';
        $sections[] = '<h2 id="References"><a href="http://en.wikipedia.org/wiki/References" title="References">References</a></h2>';
        $sections[] = '<h2 id="Further_reading">Further reading</h2>';
        $sections[] = '<h2 id="References"><small>References</small></h2>';
        $sections[] = '<h2 id="Rreferences">Rreferences</h2>';
        $sections[] = '<h2 id="References"><i>References</i></h2>';
        $sections[] = '<h2 id="References"><big>References</big></h2>';
        $sections[] = '<h2 id="Cited_literature">Cited literature</h2>';
        $sections[] = '<h2 id="Bibliography">Bibliography</h2>';
        $sections[] = '<h2 id="Literature">Literature</h2>';
        $sections[] = '<h2 id="Refererence">Refererence</h2>';
        $sections[] = '<h2 id="Referernces">Referernces</h2>';
        $sections[] = '<h2 id="Identification">Identification</h2>';
        // $sections[] = '<h2 id="Taxonomy">Taxonomy</h2>';     //don't add this, it will remove many good information
        // $sections[] = '<h2 id="Subspecies">Subspecies</h2>'; //don't add this, it will remove many good information
        // $sections[] = '<h2 id="Species">Species</h2>';       //don't add this, it will remove many good information
        $sections[] = '<h2 id="Other_Information">Other Information</h2>';
        $sections[] = '<h2 id="Gallery">Gallery</h2>';
        // $sections[] = '';    
        // $sections[] = '';
        // $sections[] = '';
        // $sections[] = '';
        $sections[] = '<h2 id="References[3]"><span id="References.5B3.5D"></span>References<sup id="cite_ref-:0_3-1"><a href="#cite_note-:0-3"><span>[</span>3<span>]</span></a></sup></h2>';
        $sections[] = '<h2 id="References[3]"><span id="References.5B3.5D"></span>References<sup id="cite_ref-3"><a href="#cite_note-3"><span>[</span>3<span>]</span></a></sup></h2>';
        $sections[] = '<h2 id="External_resources">External resources</h2>';
        $sections[] = '<h2 id="References_and_External_Links">References and External Links</h2>';
        // print_r($sections); exit;
        
        // <h2 id="References">References</h2></div>
        if(preg_match_all("/<h2 id=(.*?)<\/h2>/ims", $html, $arr)) { // print_r($arr[1]);
            foreach($arr[1] as $str) $arr2[] = "<h2 id=" . $str . "</h2>";
            // print_r($arr2);
            $start_section = false;
            foreach($arr2 as $str) {
                if(in_array($str, $sections)) {
                    $start_section = $str;
                    break;
                }
            }
            if(!$start_section) {
                echo("\nCannot find start section.\n"); print_r(@$rec['furtherInformationURL']);
                print_r($arr2);
                @$this->debug['no start section']++;
            }
            else {
                // echo "\nstart_section: [$start_section]\n";
                $html = Functions::delete_all_between($start_section, "Retrieved from", $html, false, false); 
                // 4th param $inclusiveYN = false
                // 5th param $caseSensitiveYN = false
            }

        }
        // exit("\n-stop muna-\n");
        return $html;
    }
}
?>