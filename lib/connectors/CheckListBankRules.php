<?php
namespace php_active_record;
/*
/extra/gnparser/gnparser/bin/gnparser -V
    version: v1.6.3
/extra/gnparser/gnparser172/gnparser -V
    version: v1.7.2
*/
class CheckListBankRules extends CheckListBankWeb
{
    function __construct()
    {
    }
    private function initialize()
    {   /* 1st:
        require_library('connectors/RetrieveOrRunAPI');
        $task2run = 'gnparser';
        $download_options['expire_seconds'] = false; //doesn't expire
        $main_path = 'gnparser_cmd';
        $this->RoR = new RetrieveOrRunAPI($task2run, $download_options, $main_path);
        */
        /* 2nd:
        require_library('connectors/DwCA_Utility');
        $this->HC = new DwCA_Utility(); // HC - higherClassification functions
        */
        // /* 3rd:
        $this->temp_dir = CONTENT_RESOURCE_LOCAL_PATH . 'CheckListBank_files/'.$this->resource_id."/";
        if(!is_dir($this->temp_dir)) {
            mkdir($this->temp_dir);
            shell_exec("chmod 777 ".$this->temp_dir);
        }
        // */

        // /*
        require_library('connectors/AnystyleAPI');
        $this->input_file = $this->temp_dir . "input_" . $this->resource_id . ".txt";
        $this->other_funcs = new AnystyleAPI(); //func is: parse_citation_using_anystyle_cli(citation, input_file)

        if(Functions::is_production()) {
            // shell_exec("su -");
            // $out = shell_exec("source scl_source enable rh-ruby25");

            // // $out = shell_exec("/bin/scl enable rh-ruby25 'ruby -v'");
            // echo "\nTerminal: [$out]\n";

            // $out = shell_exec("gem install anystyle-cli");
            // echo "\nTerminal: [$out]\n";

            // $out = shell_exec("which anystyle");
            // echo "\nTerminal: [$out]\n";

            $out = shell_exec("ruby -v");
            echo "\nTerminal: [".trim($out)."]\n";
        }
        // */
    }
    function start_CheckListBank_process()
    {
        self::initialize();
        self::parse_TSV_file($this->temp_folder . $this->arr_json['Taxon_file'], 'process Taxon.tsv'); //generate unique lists from Taxon.tsv
        $a = self::sort_key_val_array($this->debug['namePublishedIn']);     self::write_array_2txt(array_keys($a), "namePublishedIn");      //print_r($a);
        // /* main operation
        $a = self::sort_key_val_array($this->debug['infragenericEpithet']); self::write_array_2txt(array_keys($a), "infragenericEpithet");  //print_r($a);
        $a = self::sort_key_val_array($this->debug['taxonomicStatus']);     self::write_array_2txt(array_keys($a), "taxonomicStatus");      //print_r($a);
        $a = $this->debug['taxonRank'];                                     self::write_array_2txt(array_keys($a), "taxonRank");            //print_r($a);
        $a = self::sort_key_val_array($this->debug['nomenclaturalStatus']); self::write_array_2txt(array_keys($a), "nomenclaturalStatus");  //print_r($a);
        $a = self::sort_key_val_array($this->debug['taxonRemarks']);        self::write_array_2txt(array_keys($a), "taxonRemarks");         //print_r($a);
        self::parse_TSV_file($this->temp_folder . $this->arr_json['Distribution_file'], 'process Distribution.tsv'); //generate unique lists from Distribution.tsv
        $a = self::sort_key_val_array($this->debug['locality']);            self::write_array_2txt(array_keys($a), "locality");             //print_r($a);
        $a = self::sort_key_val_array($this->debug['occurrenceStatus']);    self::write_array_2txt(array_keys($a), "occurrenceStatus");     //print_r($a);
        self::parse_TSV_file($this->temp_folder . $this->arr_json['Taxon_file'], 'do main mapping');
        self::parse_references_with_anystyle();

        $WRITE = Functions::file_open($this->temp_dir."Taxa.txt", "w"); fclose($WRITE); //created here due to permission in form_result_map.php
        shell_exec("chmod 777 ".$this->temp_dir."Taxa.txt");
        // */

        $this->create_web_form();
        // print_r($this->debug);

        // self::prepare_download_link();
        // recursive_rmdir($this->temp_dir);
        exit;
        exit("\n-stop muna-\n");
        return;
    }
    function parse_TSV_file($txtfile, $task)
    {   
        // $modulo = self::get_modulo($txtfile);
        $modulo = 10000;
        $i = 0; debug("\nProcessing: [$txtfile]\n"); //$syn = 0; for stats only        
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            if(!$line) continue;
            $i++; //if(($i % $modulo) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                $k = 0; $rec = array();
                foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
            }
            $rec = array_map('trim', $rec); //print_r($rec); exit("\nstopx\n");
            //###############################################################################################
            if($task == "process Taxon.tsv") {
                /*Array(
                    [dwc:taxonID] => x4
                    [dwc:parentNameUsageID] => 
                    [dwc:acceptedNameUsageID] => 
                    [dwc:originalNameUsageID] => 
                    [dwc:scientificNameID] => x3
                    [dwc:datasetID] => 
                    [dwc:taxonomicStatus] => accepted
                    [dwc:taxonRank] => kingdom
                    [dwc:scientificName] => Animalia
                    [dwc:scientificNameAuthorship] => 
                    [col:notho] => 
                    [dwc:genericName] => 
                    [dwc:infragenericEpithet] => 
                    [dwc:specificEpithet] => 
                    [dwc:infraspecificEpithet] => 
                    [dwc:cultivarEpithet] => 
                    [dwc:nameAccordingTo] => 
                    [dwc:namePublishedIn] => 
                    [dwc:nomenclaturalCode] => 
                    [dwc:nomenclaturalStatus] => 
                    [dwc:taxonRemarks] => 
                    [dcterms:references] => 
                )*/
                // The columns to be mapped are:
                // -Taxon file: taxonomicStatus, taxonRank, nomenclaturalStatus (if populated), taxonRemarks
                // -Distribution file: locality and occurrenceStatus (if present/populated)
                $this->debug['namePublishedIn'][$rec['dwc:namePublishedIn']] = '';
                $this->debug['infragenericEpithet'][$rec['dwc:infragenericEpithet']] = '';
                $this->debug['taxonRank'][$rec['dwc:taxonRank']] = '';
                $this->debug['nomenclaturalStatus'][$rec['dwc:nomenclaturalStatus']] = '';
                $this->debug['taxonRemarks'][$rec['dwc:taxonRemarks']] = '';

                $tS = $rec['dwc:taxonomicStatus'];
                $nS = $rec['dwc:nomenclaturalStatus'];
                $tR = $rec['dwc:taxonRemarks'];
                // $val = $tS ? $tS : $nS ? $nS : $tR; //ternary didn't work
                if($tS) $val = $tS;
                elseif($nS) $val = $nS;
                elseif($tR) $val = $tR;
                else $val = "-something wrong-";
                $this->debug['taxonomicStatus'][$val] = ''; //pre_name_usage
            }
            //###############################################################################################
            if($task == "process Distribution.tsv") {
                /*Array(
                    [dwc:taxonID] => ca4b69b0-40be-4eec-8cac-d883b8703cfd
                    [dwc:occurrenceStatus] => native
                    [dwc:locationID] => 
                    [dwc:locality] => Neotropical region
                    [dwc:countryCode] => 
                    [dcterms:source] => 
                )*/
                $this->debug['locality'][$rec['dwc:locality']] = '';
                $this->debug['occurrenceStatus'][$rec['dwc:occurrenceStatus']] = '';
                $taxonID = $rec['dwc:taxonID'];
                // $this->distribution_info_list[$taxonID] = array('o' => $rec['dwc:occurrenceStatus'], 'l' => $rec['dwc:locality']); //commented bec. it may have multiple values
                $this->distribution_info_list[$taxonID]['o'][] = $rec['dwc:occurrenceStatus'];
                $this->distribution_info_list[$taxonID]['l'][] = $rec['dwc:locality'];
            }
            //###############################################################################################
            if($task == "do main mapping") { //mapping here: https://github.com/EOL/ContentImport/issues/14#issuecomment-2168170536
                /*
                dwc field	TWB field
                dwc:taxonID	scientific_nameID                
                dwc:parentNameUsageID	parent_nameID
                dwc:acceptedNameUsageID	accepted_nameID
                dwc:taxonomicStatus	name_usage
                dwc:taxonRank	rank_name
                dwc:scientificNameAuthorship	taxon_author
                dwc:genericName	unit_name1
                dwc:infragenericEpithet	unit_name2
                dwc:specificEpithet	IF dwc:infragenericEpithet absent: unit_name2 | IF dwc:infragenericEpithet present: unit_name3
                dwc:infraspecificEpithet	IF dwc:infragenericEpithet absent: unit_name3 | IF dwc:infragenericEpithet present: unit_name4
                dwc:cultivarEpithet	 IF dwc:infragenericEpithet absent: unit_name3 | IF dwc:infragenericEpithet present: unit_name4
                dwc:namePublishedIn	PULL OUT INTO NEW TABLE
                dwc:locality	geographic_value
                dwc:occurrenceStatus	origin
                */
                $taxonID = $rec['dwc:taxonID'];
                $s = array(); //save array
                $s['scientific_nameID'] = $rec['dwc:taxonID'];
                $s['parent_nameID'] = $rec['dwc:parentNameUsageID'];
                $s['accepted_nameID'] = $rec['dwc:acceptedNameUsageID'];
                
                // Use taxonomicStatus if populated, if not try nomenclaturalStatus, if not try taxonRemarks
                $tS = $rec['dwc:taxonomicStatus'];
                $nS = $rec['dwc:nomenclaturalStatus'];
                $tR = $rec['dwc:taxonRemarks'];
                // $val = $tS ? $tS : $nS ? $nS : $tR; //ternary didn't work
                if($tS) $val = $tS;
                elseif($nS) $val = $nS;
                elseif($tR) $val = $tR;
                else $val = "-something wrong-";

                $s['pre_name_usage'] = $val;
                $s['name_usage'] = '';
                $s['unacceptability_reason'] = '';
                
                $s['rank_name'] = ucfirst(strtolower($rec['dwc:taxonRank']));
                $s['taxon_author'] = $rec['dwc:scientificNameAuthorship'];

                $s['unit_name1'] = '';  $s['unit_name2'] = ''; $s['unit_name3'] = '';  $s['unit_name4'] = ''; //initialize

                $s['unit_name1'] = $rec['dwc:genericName'];
                $s['unit_name2'] = $rec['dwc:infragenericEpithet'];

                $infragenericEpithet = $rec['dwc:infragenericEpithet'];
                if(!$infragenericEpithet) $s['unit_name2'] = $rec['dwc:specificEpithet'];
                else                      $s['unit_name3'] = $rec['dwc:specificEpithet'];
                if(!$infragenericEpithet) $s['unit_name3'] = $rec['dwc:infraspecificEpithet'];
                else                      $s['unit_name4'] = $rec['dwc:infraspecificEpithet'];
                if(!$infragenericEpithet) $s['unit_name3'] = $rec['dwc:cultivarEpithet'];
                else                      $s['unit_name4'] = $rec['dwc:cultivarEpithet'];

                // dwc:namePublishedIn	PULL OUT INTO NEW TABLE

                $occurrenceStatus = @$this->distribution_info_list[$taxonID]['o'];
                $occurrenceStatus = self::clean_array($occurrenceStatus);
                $occurrenceStatus = self::ucfirst_array_values($occurrenceStatus);
                $occurrenceStatus = implode("|", $occurrenceStatus);

                $locality = @$this->distribution_info_list[$taxonID]['l'];
                $locality = self::clean_array($locality);
                $locality = implode("|", $locality);
                
                $s['geographic_value'] = $locality;
                $s['origin'] = $occurrenceStatus;
                if($val = $rec['dwc:namePublishedIn']) $s['referenceID'] = md5($val);
                else                                   $s['referenceID'] = '';
                self::write_output_rec_2txt($s, "Main_Table");

            }
            //###############################################################################################

        } //end foreach()
        if($task == "load DH file") {
            // echo "\nLoaded DH 2.1 DONE.";
            echo "\ntotal: ".count($this->DH_info)."\n"; //exit;
            // print_r($taxo_status); exit("\n[$syn]\n"); //good debug
        }
    }
    private function write_array_2txt($arr, $basename)
    {
        // if($basename == 'taxonRank') {
        //     print_r($arr); exit("\nstop 3\n");
        // }

        $arr = self::clean_array($arr);
        $filename = $this->temp_dir.$basename.".txt"; //echo "\nfilename: [$filename]\n";
        $WRITE = Functions::file_open($filename, "w");
        foreach($arr as $row) {
            if(!$row) continue;
            if($basename == 'taxonRank') $row = ucfirst(strtolower($row));
            if($basename == 'occurrenceStatus') $row = ucfirst(strtolower($row));

            fwrite($WRITE, $row . "\n");
        }
        fclose($WRITE);
    }
    private function write_output_rec_2txt($rec, $basename)
    {   // print_r($rec);
        $filename = $this->temp_dir.$basename.".txt";
        $fields = array_keys($rec);
        $WRITE = Functions::file_open($filename, "a");
        clearstatcache(); //important for filesize()
        if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
        $save = array();
        foreach($fields as $fld) {
            if(is_array($rec[$fld])) { //if value is array()
                $rec[$fld] = self::clean_array($rec[$fld]);
                $rec[$fld] = implode(", ", $rec[$fld]); //convert to string
                $save[] = trim($rec[$fld]);
            }
            else $save[] = $rec[$fld];
        }
        $tab_separated = (string) implode("\t", $save); 
        fwrite($WRITE, $tab_separated . "\n");
        // echo "\nSaved to [$basename]: "; print_r($save); //echo "\n".implode("\t", $save)."\n"; //exit("\nditox 9\n"); //good debug
        fclose($WRITE);
    }
    ///============================================== START Summary Report
    function prepare_download_link()
    {   // zip -r temp.zip Documents
        /* during dev - force assign
        $this->temp_dir = "/opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3//CheckListBank_tool/174/";
        $this->resource_id = 174;
        */
        // echo "\n".$this->temp_dir."\n"; echo "\n".$this->resource_id."\n";
        $destination = str_replace("/$this->resource_id/", "", $this->temp_dir);
        $destination .= "/".$this->resource_id.".zip";
        $source = $this->temp_dir;
        if($GLOBALS['ENV_DEBUG']) {
            echo "\nsource: [$source]\n";
            echo "\ndestination: [$destination]\n";    
        }
        $cmd = "zip -rj $destination $source";
        $out = shell_exec($cmd);
        echo "\n$out\n";
        return;
    }
    private function total_rows_on_file($file)
    {
        $total = shell_exec("wc -l < ".escapeshellarg($file));
        $total = trim($total);
        return $total;
    }
    function add_header_to_file($file, $string_tobe_added)
    {
        echo "<pre>\nuser file: [$file]\n";                             // [temp/1687337313.txt]
        $needle = pathinfo($file, PATHINFO_FILENAME);                   //       1687337313
        $tmp_file = str_replace("$needle.txt", "$needle.tmp", $file);   // [temp/1687337313.tmp]
        echo("\n[$file]\n[$needle]\n[$tmp_file]</pre>\n"); //good debug
        $WRITE = Functions::file_open($tmp_file, "w");
        fwrite($WRITE, $string_tobe_added . "\n");
        $contents = file_get_contents($file);
        fwrite($WRITE, $contents . "\n");
        fclose($WRITE);
        shell_exec("cp $tmp_file $file");
    }
    function sort_key_val_array($multi_array, $key_orientation = SORT_ASC, $value_orientation = SORT_ASC)
    {
        $data = array();
        foreach($multi_array as $key => $value) $data[] = array('language' => $key, 'count' => $value);
        // Obtain a list of columns
        /* before PHP 5.5.0
        foreach ($data as $key => $row) {
            $language[$key]  = $row['language'];
            $count[$key] = $row['count'];
        }
        */
        
        // as of PHP 5.5.0 you can use array_column() instead of the above code
        $language  = array_column($data, 'language');
        $count = array_column($data, 'count');

        // Sort the data with language descending, count ascending
        // Add $data as the last parameter, to sort by the common key
        // array_multisort($count, SORT_ASC, $language, SORT_ASC, $data); // an example run
        array_multisort($count, $value_orientation, $language, $key_orientation, $data);

        // echo "<pre>"; print_r($data); echo "</pre>"; exit;
        /* Array(
            [0] => Array(
                    [language] => infraspecies (inferred)
                    [count] => 42
                )
            [1] => Array(
                    [language] => family (inferred)
                    [count] => 240
                )
        */
        $final = array();
        foreach($data as $d) $final[$d['language']] = $d['count'];
        return $final;
    }
    private function get_modulo($txtfile)
    {
        $total = self::total_rows_on_file($txtfile);
        if($total <= 1000) $modulo = 200;
        elseif($total > 1000 && $total <= 50000) $modulo = 5000;
        elseif($total > 50000 && $total <= 100000) $modulo = 5000;
        elseif($total > 100000 && $total <= 500000) $modulo = 10000;
        elseif($total > 500000 && $total <= 1000000) $modulo = 10000;
        elseif($total > 1000000 && $total <= 2000000) $modulo = 10000;
        elseif($total > 2000000) $modulo = 10000;
        return $modulo;
    }
    private function clean_array($arr)
    {
        if(is_array($arr)) {
            $arr = array_map('trim', $arr);
            $arr = array_filter($arr); //remove null arrays
            $arr = array_unique($arr); //make unique
            $arr = array_values($arr); //reindex key
            return $arr;    
        }
        else return array();
    }
    private function parse_references_with_anystyle()
    {
        $filename = $this->temp_dir."namePublishedIn.txt"; // echo "\nnamePublishedIn.txt: [$filename]\n";
        // $filename = DOC_ROOT. "update_resources/connectors/helpers/anystyle/For_Testing.txt"; //used for testing only
        $i = 0;
        foreach(new FileIterator($filename) as $line_number => $line) { $orig_line = trim($line);
            if(!$line) continue;
            $i++; if(($i % 10000) == 0) echo "\n".number_format($i)." ";
            // force assign
            // $line = "Clemens B. Letters received from Dr. Brackenridge Clemens. 9. Letter of October 29th, 1860. In: Stainton HT (Ed) The Tineina of North America by (the late) Dr Brackenridge Clemens (being a collected edition of his writing on that group of insects). John van Voorst, London, XV, 282. (1872).";
            // $line = "Davis DR, Wilkinson C. Nepticulidae. In: Hodges RW, Dominick T, Davis DR, Ferguson DC, Franclemont JG, Munroe EG, Powell JA (Eds) Check list of the Lepidoptera of America north of Mexico : including Greenland. Classey, London, 2-3. (1983).";
            // $line = "Bradley JD. Microlepidoptera. Ruwenzori Expedition 1952 2: 81-148. (1965).";
            // $line = "Donner H, Wilkinson C. Fauna of New Zealand. Nepticulidae (Insecta: Lepidoptera). Fauna of New Zealand 16: 1-88. (1989).";
            // $line = "Gregor F, Povolny D. Description of Ectoedemia spiraeae (Gregor &amp; Povolny, 1955) and designation of type specimens of Lithocolletis desertella Gregor &amp; Povolny, 1949. Casopis Moravsk&eacute;ho Musea v Brne (Vedy pr&iacute;rodn&iacute;) 68: 173-180. (1983).";
            // $line = "Meyrick E. Descriptions of Australian Microlepidoptera, XVI. Tineidae. Proceedings of the Linnean Society of New South Wales (2) 7: 477-612. doi: http://dx.doi.org/10.5962/bhl.part.26071. (1893).";
            // $line = "Peyerimhoff Hd. Mitteilungen der Schweizerischen Entomologischen Gesellschaft 3: 409-415. (1871).";
            // $line = "Chrétien P. Lés Lépidoptères du Maroc. Galleriinae - Micropterygidae. In: Oberthür C (Ed) Études de Lépidoptérologie comparée. Oberthür, Rennes, 324-379. (1922).";
            // $line = "Chrétien P. Contribution à la connaissance des Lépidoptères du Nord de l'Afrique. Annales de la Société Entomologique de France 84: 289-374, figs. 281-211. (1915).";
            // $line = "Amsel HG, Hering M. Beitrag zur Kenntnis der Minenfauna Palästinas. Deutsche Entomologische Zeitschrift 1931: 113-152, pls 111-112. doi: 10.1002/mmnd.193119310203. (1931).";
            // $line = "Boheman CH. Entomologiska anteckningar under en resa i Södra Sverige 1851. Kongliga Svenska Vetenskaps-Akademiens Handlingar 1851: 55-210. doi: http://dx.doi.org/10.5962/bhl.title.35818. (1853).";
            // $line = "Klimesch J. Nepticula preisseckeri spec. nov. (Lep., Nepticulidae). Zeitschrift des Wiener Entomologen-Vereines 26: 162-168, pl. 116. (1941).";

            $line = self::format_citation_for_anystyle($line);

            /* commented for now
            $line = htmlentities($line); //worked perfectly for special chars | htmlspecialchars_decode() and others didn't work
            */

            // if(true) $obj = (object) array('full_reference' => $orig_line); //dev only debug only
            if(Functions::is_production()) $obj = (object) array('full_reference' => $orig_line); //temporary sol'n until anystyle is working
            else {
                // /* main operation
                $obj = $this->other_funcs->parse_citation_using_anystyle_cli($line, $this->input_file); // print_r($obj); exit;
                $obj->full_reference = $orig_line; // print_r($obj); //good debug
                // */
            }

            /* dev only --- used in searching for a reference, happened to be blank namepublishedIN
            if(isset($this->debug['namePublishedIn'][$orig_line])) {}
            else echo "\nhuli ref ka...[$orig_line]\n";
            */
            
            $reks = self::convert_anystyle_obj_2save($obj); // print_r($reks); exit;
            /*Array(
                [0] => Array(
                        [author] => Amsel H.G.|Hering M.
                        [title] => Beitrag zur Kenntnis der Minenfauna Palästinas
                        [volume] => 1931
                        [pages] => 113–152, 111–112
                        [doi] => 10.1002/mmnd.193119310203.
                        [date] => 1931
                        [type] => article-journal
                        [container-title] => Deutsche Entomologische Zeitschrift
                        [full_reference] => Amsel HG, Hering M. Beitrag zur Kenntnis der Minenfauna Palästinas. Deutsche Entomologische Zeitschrift 1931: 113-152, pls 111-112. doi: 10.1002/mmnd.193119310203. (1931).
                    )
            )*/
            
            // /* write to References
            $fields = array("identifier", "full_reference", "author", "title", "type", "container-title", "volume", "pages", "doi", "date", "url", "editor", "location", "publisher", "edition");
            // "issue" 
            $ref_map = array( //DwCA counterpart: https://editors.eol.org/other_files/ontology/reference_extension.xml
                'identifier'        => 'identifier',
                'full_reference'    => 'full_reference',
                'author'    => 'authorList',
                'title'     => 'primaryTitle',
                'volume'    => 'volume',
                'pages'     => 'pages',
                'doi'       => 'doi',
                'date'      => 'created',
                'type'      => 'publicationType',
                'container-title'   => 'title',
                'url'       => 'uri',
                'editor'    => 'editorList',
                // 'issue'  => No counterpart in DwCA. Let us ignore for now.
                'location'  => 'localityName',
                'publisher' => 'publisher',
                'edition'   => 'edition');

                /* ITIS References fields
                reference_author
                title
                publication_name
                actual_pub_date
                publisher
                pub_place
                pages
                -listed_pub_date
                -isbn
                -issn
                -pub_comment                
                */
                													
            $ref_type_values_map = array( //anystyle vs ITIS Jen values
                'article-journal'   => 'Journal Article',
                'book'              => 'Book',
                'chapter'           => 'Book Chapter',
                'paper-conference'  => 'Conference Paper',
                ''                  => 'Miscellaneous'
            );

            $fields = array("type", "identifier", "full_reference", "author", "editor", "title",  "container-title", "date", "pages", "publisher", "location", "url", "doi", "language");
            $ref_map_ITIS = array( //ITIS counterpart
                'type'              => 'Item type',
                'identifier'        => 'ID',
                'full_reference'    => 'dwc',
                'author'            => 'reference_author',
                'editor'            => 'Editors',
                'title'             => 'title',
                'container-title'   => 'publication_name',
                'date'              => 'actual_pub_date',
                'pages'             => 'pages',
                'publisher'         => 'publisher',
                'location'          => 'pub_place',
                'url'               => 'URLs',
                'doi'               => 'DOI',
                'language'          => 'Language'
                // 'volume'     => No counterpart in ITIS. Let us ignore for now.
                // 'issue'      => No counterpart in ITIS. Let us ignore for now.
                // 'edition'    => No counterpart in ITIS. Let us ignore for now.
            );
    
            foreach($reks as $rec) {
                $save = array();
                foreach($fields as $field)  {
                    $save_field = $ref_map_ITIS[$field];

                    if($field == 'type') {
                        $val = @$rec[$field];
                        $rec[$field] = $ref_type_values_map[$val];
                    }

                    if($field == 'identifier') $save[$save_field] = md5($rec['full_reference']);
                    else                       $save[$save_field] = @$rec[$field];
                }
                self::write_output_rec_2txt($save, "References");
            }
            // */

            // if($i > 2) break; //debug only
            // break; //debug only
        }

        /* [anystyle labels] => Array(
            [author] => authorList
            [title] => primaryTitle
            [volume] => volume
            [pages] => pages
            [doi] => doi
            [date] => created
            [type] => publicationType
            [container-title] => title
            [full_reference] => full_reference
            [url] => uri
            [editor] => editorList
            [issue] => {can be ignored...}
            [location] => localityName
            [publisher] => publisher
            [edition] => edition
        ) 
        identifier
        pageStart
        pageEnd
        language    
        */
    }
    private function convert_anystyle_obj_2save($obj)
    {   /*Array(
            [0] => stdClass Object(
                [author] => Array(
                        [0] => stdClass Object(
                                [family] => Borkowski
                                [given] => A.
                            )
                    )
                [title] => Array(
                        [0] => Studien an Nepticuliden (Lepidoptera). Teil V. Die europäischen Arten der Gattung Nepticula Heyden von Eichen
                    )
                [volume] => Array(
                        [0] => 42
                    )
                [pages] => Array(
                        [0] => 767u2013799
                    )

                [date] => Array(
                        [0] => 1972
                    )
                [type] => article-journal
                [container-title] => Array(
                        [0] => Polskie Pismo Entomologiczne
                    )
                [full_reference] => Borkowski A. Studien an Nepticuliden (Lepidoptera). Teil V. Die europäischen Arten der Gattung Nepticula Heyden von Eichen. Polskie Pismo Entomologiczne 42: 767-799. (1972).
            )
        )*/
        $reks = array();
        // foreach($obj as $o) {
            $rek = array();
            foreach($obj as $field => $values_or_value) { $this->debug['anystyle labels'][$field] = '';
                // if(in_array($field, array('type', 'full_reference'))) $rek[$field] = $values_or_value;
                if(!is_array($values_or_value)) $rek[$field] = $values_or_value;
                else {
                    $tmp = array();
                    // if(in_array($field, array('author', 'editor'))) continue; //'container-title'
                    if(is_object(@$values_or_value[0])) { //an array of objects
                        $rek2 = array();
                        foreach($values_or_value as $object) {
                            $auth = "";
                            foreach($object as $field2 => $val2) $auth[] = $val2;
                            // print_r($auth);
                            $rek2[] = implode(" ", $auth);
                        }
                        /*Array(
                            [0] => Amsel
                            [1] => H.G.
                        )
                        Array(
                            [0] => Hering
                            [1] => M.
                        )*/
                        $rek[$field] = implode("|", $rek2);
                    }
                    else { //an array of strings
                        $tmp = array();
                        foreach($values_or_value as $val) {
                            // /* customize formatting
                            if(!self::hasMatchedParentheses($val)) $val .= ")";
                            $val = str_replace("there_is_a_cat", ".", $val);
                            // */

                            $tmp[] = $val;
                        }
                        $rek[$field] = implode("|", $tmp);    
                    }
                }
            }
            $reks[] = $rek;
        // }
        //echo "\nreks start\n"; 
        // print_r($reks); //echo "\nreks end\n";
        return $reks;
    }
    private function format_citation_for_anystyle($orig_str)
    {
        // $orig_str = "Beirne BP. The male genitalia of the British Stigmellidae (Nepticulidae) (Lep.). Proceedings of the Royal Irish Academy Section B 50: 191-218. doi: http://www.jstor.org/pss/20490833. (1945).";
        $str = $orig_str;
        // $str = str_replace(")", " ) ", $str);
        // $str = str_replace("(", " ( ", $str);

        // $str = str_replace(" JD. ", " J.D. ", $str);
        // . Contributions
        // $str = str_replace(". Contributions", ". The Contributions", $str);
        // B. Letters
        // $str = str_replace("B. Letters", "B. The Letters", $str);
        
        if(preg_match_all("/\((.*?)\)/ims", $str, $arr)) {
            // print_r($arr[1]);
            $words = array();
            foreach($arr[1] as $s) {
                if(stripos($s, ".") !== false) { //string is found
                    $temp = str_replace(".", "there_is_a_cat", $s);
                    $str = str_replace("($s)", "($temp)", $str);
                }
            }
            // echo "\norig: [$orig_str]\n";
            // echo "\n new: [$str]\n";
            return $str;
        }
        return $orig_str;
    }
    function hasMatchedParentheses( $string ) {
        $counter = 0;
        $length = strlen( $string );
        for( $i = 0; $i < $length; $i++ ) {
            $char = $string[ $i ];
            if( $char == '(' ) $counter++;
            elseif( $char == ')' ) $counter--;
            if( $counter < 0 ) return false;
        }
        return $counter == 0;
    }
    private function ucfirst_array_values($arr)
    {
        $final = array();
        foreach($arr as $val) {
            $val = ucfirst(strtolower($val));
            $final[] = $val;
        }
        return $final;
    }
    /* DwCA Reference
    identifier
    publicationType
    full_reference
    primaryTitle
    title
    pages
    pageStart
    pageEnd
    volume
    edition
    publisher
    authorList
    editorList
    created
    language
    uri
    doi
    localityName
    */
}
?>