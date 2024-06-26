<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from 26.php */
class WoRMS_post_process
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        /* not used here...
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        */
        /*
        Pattern 1:
        Dichelyne subgen. Dichelyne Jägerskiöld 1902                Dichelyne Jägerskiöld, 1902
        Flustrellidra subgen. Flustrellidrella d'Hondt 1983         Flustrellidrella d'Hondt, 1983
        Aglaophenia subgen. Pachyrhynchia Kirchenpauer 1872         Pachyrhynchia Kirchenpauer, 1872
        Halliella subgen. Halliella Ulrich 1891                     Halliella Ulrich, 1891
        
        Pattern 2:
        Dicranomyia subgen. Idiopyga Savchenko 1987                 Dicranomyia (Idiopyga) Savchenko, 1987
        Nodobythere subgen. Cristobythere Schornikov 1987           Nodobythere (Cristobythere) Schornikov, 1987
        Parydra subgen. Chaetoapnaea Hendel 1930                    Parydra (Chaetoapnaea) Hendel, 1930
        Dicranomyia subgen. Glochina Meigen 1830                    Dicranomyia (Glochina) Meigen, 1830

        Pattern 3:
        Entomozoe subgen. Nandania Wang                             Entomozoe (Nandania) Wang (Shang-Qi), 1984
        Ophiosema subgen. Sinophiosema Zhang                        Ophiosema (Sinophiosema) Zhang (Xi-Guang), 1986
        */
        // /* these taxonIDs were generated by lookup_WoRMS_mismapped_subgenera(). https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=65930&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65930
        $this->mismapped_subgenera = array("22874", "1378432", "1464471", "827193", "768822", "770107", "771827", "771768", "742534", "714996", "580832", "580831", "503264", "472859", "472852", "472871", "472854", "475534", "475535", "416435", "325624", "177062", "176793", "1469910", "1484924", "149025", "151038", "151010", "1290930", "1457547", "1457542", "1443938", "1460976", "1378643", "1379063", "1378904", "1377061", "1393997", "1378544", "1376445", "1373195", "1373075", "1373079", "1358532", "1373077", "1335232", "1373076", "1287693", "1058961", "1348334", "1036212", "1026010");
        // */
    }
    /*================================================================= STARTS HERE ======================================================================*/
    private function get_undefined_parentMeasurementIDs()
    {
        $contents = file_get_contents(CONTENT_RESOURCE_LOCAL_PATH.'26_undefined_parentMeasurementIDs.txt');
        $arr = explode("\n", $contents);
        $arr = array_map('trim', $arr);
        foreach($arr as $a) if($a) $final[$a] = '';
        return $final;
    }
    function start($info)
    {
        $this->undefined_parentMeasurementIDs = self::get_undefined_parentMeasurementIDs();
        $tables = $info['harvester']->tables;
        
        // /* New May 11,2021: https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=65930&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65930
        // DATA-1853: remove all occurrences and association records for taxa with specified ranks
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'build info');
        // */
        
        self::process_measurementorfact($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0]);
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'create extension');
        unset($this->occurrenceID_bodyPart);
    }
    private function process_measurementorfact($meta)
    {   //print_r($meta);
        echo "\nprocess_measurementorfact...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } // print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => 286376_1054700
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0191a5b6bbee617be3f101758872e911_26
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://rs.tdwg.org/dwc/terms/habitat
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://purl.obolibrary.org/obo/ENVO_01000024
                [http://rs.tdwg.org/dwc/terms/measurementMethod] => inherited from urn:lsid:marinespecies.org:taxname:101, Gastropoda Cuvier, 1795
                [http://purl.org/dc/terms/source] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1054700
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementUnit] => 
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedDate] => 
                [http://rs.tdwg.org/dwc/terms/measurementDeterminedBy] => 
                [http://purl.org/dc/terms/bibliographicCitation] => 
                [http://purl.org/dc/terms/contributor] => 
                [http://eol.org/schema/reference/referenceID] => 
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            //===========================================================================================================================================================
            if($parent_id = $rec['http://eol.org/schema/parentMeasurementID']) {
                if(isset($this->undefined_parentMeasurementIDs[$parent_id])) {
                    $this->occurrence_2be_deleted[$occurrenceID] = '';
                    continue;
                }
            }
            if(isset($this->occurrence_2be_deleted_Jen[$occurrenceID])) continue;
            //===========================================================================================================================================================
            $o = new \eol_schema\MeasurementOrFact_specific();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            if(!isset($this->measurementIDs[$o->measurementID])) {
                $this->archive_builder->write_object_to_file($o);
                $this->measurementIDs[$o->measurementID] = '';
            }
            // if($i >= 10) break; //debug only
        }
    }
    private function process_occurrence($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_occurrence...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            } //print_r($rec); exit("\ndebug...\n");
            /*Array(
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 0191a5b6bbee617be3f101758872e911_26
                [http://rs.tdwg.org/dwc/terms/taxonID] => 1054700
            )*/
            $occurrenceID = $rec['http://rs.tdwg.org/dwc/terms/occurrenceID'];
            
            if($what == 'create extension') { //process_occurrence()
                $uris = array_keys($rec);
                //===========================================================================================================================================================
                if(isset($this->occurrence_2be_deleted[$occurrenceID])) continue;
                if(isset($this->occurrence_2be_deleted_Jen[$occurrenceID])) continue;
                //===========================================================================================================================================================
                $o = new \eol_schema\Occurrence_specific();
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                if(!isset($this->occurrenceIDs[$o->occurrenceID])) {
                    $this->archive_builder->write_object_to_file($o);
                    $this->occurrenceIDs[$o->occurrenceID] = '';
                }
                // if($i >= 10) break; //debug only
            }
            elseif($what == 'build info') { //process_occurrence()
                $taxonID = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
                if(in_array($taxonID, $this->mismapped_subgenera)) $this->occurrence_2be_deleted_Jen[$occurrenceID] = '';
            }
        }
        echo "\noccurrence_2be_deleted_Jen: ".count($this->occurrence_2be_deleted_Jen)."\n";
    }
    /*================================================================= ENDS HERE ======================================================================*/
    /*================== START WoRMS_mismapped_subgenera ========================*/
    // https://eol-jira.bibalex.org/browse/DATA-1827?focusedCommentId=65930&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65930
    function lookup_WoRMS_mismapped_subgenera() //utility
    {   
        /* Part 1 */
        $source = '/opt/homebrew/var/www/eol_php_code/tmp2/26/taxon.tab'; $i = 0;
        foreach(new FileIterator($source) as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [taxonID] => 1
                    [source] => http://www.marinespecies.org/aphia.php?p=taxdetails&id=1
                    [referenceID] => WoRMS:citation:1
                    [acceptedNameUsageID] => 
                    [scientificName] => Biota
                    [namePublishedIn] => 
                    [kingdom] => 
                    [phylum] => 
                    [class] => 
                    [order] => 
                    [family] => 
                    [genus] => 
                    [taxonRank] => kingdom
                    [taxonomicStatus] => accepted
                    [taxonRemarks] => 
                    [rightsHolder] => WoRMS Editorial Board
                )*/
                if($rec['taxonRank'] == 'subgenus') $scinames[$rec['scientificName']] = $rec;
                if($rec['taxonRank'] == 'family') $scinames[$rec['scientificName']] = $rec;
                if($rec['taxonRank'] == 'genus') $scinames[$rec['scientificName']] = $rec;
            }
        }
        // print_r($scinames); exit;
        /* Part 2 */
        $subgenus_names = array("Dichelyne subgen. Dichelyne Jägerskiöld 1902", "Flustrellidra subgen. Flustrellidrella d'Hondt 1983", "Aglaophenia subgen. Pachyrhynchia Kirchenpauer 1872", "Dicranomyia subgen. Idiopyga Savchenko 1987", "Entomozoe subgen. Nandania Wang", "Halliella subgen. Halliella Ulrich 1891", "Nodobythere subgen. Cristobythere Schornikov 1987", "Ophiosema subgen. Sinophiosema Zhang", "Parydra subgen. Chaetoapnaea Hendel 1930", "Dicranomyia subgen. Glochina Meigen 1830", "Paralvinella subgen. Nautalvinella Desbruyères & Laubier 1993", "Paralvinella subgen. Miralvinella Desbruyères & Laubier 1993", "Leptoconops subgen. Holoconops Kieffer 1918", "Chaperiopsis subgen. Clipeochaperia Uttley & Bullivant 1972", "Alcyonidium subgen. Paralcyonidium Okada 1925", "Melicerita subgen. Henrimilnella d'Hondt & Gordon 1999", "Amphiblestrum subgen. Aviculamphiblestrum Rosso 1999", "Clitellio subgen. Clitelloides Finogenova 1985", "Olavius subgen. Coralliodriloides Erséus 1984", "Schizoperopsis subgen. Psammoschizoperopsis Apostolov 1982", "Macellicephala subgen. Sinantenna Hartmann-Schröder 1974", "Amphiporus subgen. Naredopsis Verrill 1892", "Nemertes subgen. Nemertes Johnston 1837", "Gyrodes subgen. Sohlella Popenoe, Saul & Susuki 1987", "Grammatodon subgen. Cosmetodon Branson 1942", "Rhopalodiaceae subgen. Karsten Topachevs'kyj & Oksiyuk 1960", "Culicoides subgen. Monoculicoides Khalaf 1954", "Coelopa subgen. Fucomyia Haliday 1938", "Cunnolites subgen. Cunnolites Alloiteau 1952", "Cunnolites subgen. Plesiocunnolites Alloiteau 1957", "Cunnolites subgen. Paracunnolites Beauvais 1964", "Ceratotrochus subgen. Edwardsotrochus Chevalier 1961", "Caligus subgen. Subcaligus Heegaard 1943", "Castanopora subgen. Castanoporina Voigt 1993", "Urceolipora subgen. Cureolipora Gordon 2000", "Fenestella subgen. Loculiporina Elias & Condra 1957", "Lepidopleurus subgen. Xiphiozona Berry 1919", "Drepanochilus subgen. Tulochilus Finlay & Marwick 1937", "Puncturiella subgen. Puncturiellina Voigt 1987", "Celleporaria subgen. Sinuporaria Pouyet 1973", "Eridopora subgen. Discotrypella Elias 1957", "Hederella subgen. Basslederella Solle 1968", "Hederella subgen. Rhenanerella Solle 1952", "Glycymeris subgen. Manaia Finlay & Marwick 1937", "Hederella subgen. Paralhederella Solle 1952", "Webbinelloidea subgen. Apsiphora Langer 1991", "Hederella subgen. Magnederella Solle 1952", "Tylopathes subgen. Paratylopathes Roule 1905", "Aljutovella subgen. Elongatella Solovieva ex Rauzer-Chernousova et al. 1996", "Vitta subgen. Vitta Mörch 1852", "Xestoleberis subgen. Pontoleberis Krstic & Stancheva 1967", "Himantozoum subgen. Beanodendria d'Hondt & Gordon 1996");
        $i = 0;
        foreach($subgenus_names as $name) { $i++;
            $rek = self::find_subgenus_rek($name, $scinames); // print_r($rek);
            // echo "\n$i. $name -> ".$rek['scientificName']. " [".$rek['taxonID']."]"; //good debug
            echo "\n".$rek['taxonID'];
        }
    }
    private function find_subgenus_rek($name, $scinames)
    {   $orig = $name;
        $name = str_replace("ex Rauzer", "in Rauzer", $name);
        
        //Pattern 1:
        //$name = "Dichelyne subgen. Dichelyne Jägerskiöld 1902"; //Dichelyne Jägerskiöld, 1902
        $words = explode("subgen.", $name);
        $words = array_map('trim', $words);
        $sought = $words[1];
        $numbers = self::get_numbers_from_string($words[1]); // print_r($numbers);
        if(count($numbers) == 1) {
            $sought = str_replace(" ".$numbers[0], ", ".$numbers[0], $sought);
        }
        // echo "\nPattern 1: [$sought]\n"; //good debug
        if($rek = @$scinames[$sought]) return $rek;
        
        //Pattern 2:
        //$name = "Dicranomyia subgen. Idiopyga Savchenko 1987"; //Dicranomyia (Idiopyga) Savchenko, 1987
        $sought = self::pattern2($name);
        // echo "\nPattern 2: [$sought]\n"; //good debug
        if($rek = @$scinames[$sought]) return $rek;
        
        //Pattern 3:
        //$name = "Entomozoe subgen. Nandania Wang";  //Entomozoe (Nandania) Wang (Shang-Qi), 1984
        $sought = self::pattern2($name);
        // echo "\nPattern 3: [$sought]\n"; //good debug
        
        foreach($scinames as $sciname => $rec) {
            if($sought == substr($sciname, 0, strlen($sought))) return $rec;
        }
        exit("\nInvestigate: No match: [$orig]\n");
    }
    private function pattern2($name)
    {
        $name = str_replace('subgen.', '', $name);
        $name = Functions::remove_whitespace($name);
        $numbers = self::get_numbers_from_string($name);
        if(count($numbers) == 1) {
            $name = str_replace(" ".$numbers[0], ", ".$numbers[0], $name);
            $words = explode(" ", $name);
            $sought = array($words[0], "(".$words[1].")", $words[2], $words[3], @$words[4], @$words[5], @$words[6], @$words[7]);
            $sought = trim(implode(" ", $sought));
        }
        elseif(count($numbers) > 1) {
            print_r($numbers);
            exit("\nInvestigate: too many numbers: [$name]\n");
        }
        else {
            
            $words = explode(" ", $name);
            $sought = array($words[0], "(".$words[1].")", $words[2], @$words[3], @$words[4], @$words[5]);
            $sought = trim(implode(" ", $sought));
        }
        return $sought;
    }
    private function get_numbers_from_string($str)
    {
        if(preg_match_all('/\d+/', $str, $a)) return $a[0];
    }
    /*=================== END WoRMS_mismapped_subgenera =========================*/
}
?>