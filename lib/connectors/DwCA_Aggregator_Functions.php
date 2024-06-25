<?php
namespace php_active_record;
/* */
class DwCA_Aggregator_Functions
{
    function __construct() {}
    function shorten_bibliographicCitation($meta, $bibliographicCitation)
    {   // exit("\n$meta->file_uri\n");
        // "/Volumes/AKiTiO4/eol_php_code_tmp/dir_07285//media.txt"
        $eml_file = str_ireplace("media.txt", "eml.xml", $meta->file_uri);      //for extension: http://eol.org/schema/media/Document
        $eml_file = str_ireplace("description.txt", "eml.xml", $eml_file);      //for extension: http://rs.gbif.org/terms/1.0/Description
        // exit("\n[$eml_file]\n");
        if(is_file($eml_file)) {
            if($xml = simplexml_load_file($eml_file)) { // print_r($xml);
                if($t = $xml->additionalMetadata->metadata->plaziMods) {
                    $mods = $t->children("http://www.loc.gov/mods/v3"); // xmlns:mods="http://www.loc.gov/mods/v3"
                    // echo "\n[".$mods->mods->typeOfResource."]\n"; //prints e.g. "text"
                    $subset = trim((string) @$mods->mods->relatedItem->part->detail->title);
                    if($subset) {
                        // echo "\nmay subset:\n[".$subset."]\n"; //prints the subset of the bibliographicCitation --- good debug
                        $shortened = str_ireplace("($subset)", "", $bibliographicCitation);
                        $shortened = Functions::remove_whitespace($shortened);
                        if($shortened) return $shortened;
                    }
                }
            }    
        }
        // exit("\nstop muna\n");
        return $bibliographicCitation;
    }
    /* Used by our original text object.
    function remove_taxon_lines_from_desc($html) // created for TreatmentBank - https://eol-jira.bibalex.org/browse/DATA-1916
    {
        if(preg_match_all("/<p>(.*?)<\/p>/ims", $html, $arr)) {
            $rows = $arr[1];
            $final = array();
            foreach($rows as $row) {
                $row = strip_tags($row);
                if(stripos($row, "locality:") !== false) {  //string is found
                    $final[] = $row;
                    continue;
                }
                if(strlen($row) <= 50) continue;
                if(stripos($row, "[not") !== false) continue; //string is found
                if(stripos($row, "(in part)") !== false) continue; //string is found
                if(stripos($row, "[? Not") !== false) continue; //string is found
                if(stripos($row, "Nomenclature") !== false) continue; //string is found
                if(stripos($row, "discarded]") !== false) continue; //string is found
                if(stripos($row, "♂") !== false) continue; //string is found
                if(stripos($row, "♀") !== false) continue; //string is found
                $final[] = $row;
            }
            if($final) {
                // print_r($final); // echo "\ntotal: [".count($final)."]\n";
                $ret = implode("\n", $final);
                return $ret;
            }
        }
        return $html;
    } */
    function let_media_document_go_first_over_description($index)
    {   /* Array(
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.tdwg.org/dwc/terms/occurrence
            [2] => http://rs.gbif.org/terms/1.0/description
            [3] => http://rs.gbif.org/terms/1.0/distribution
            [4] => http://eol.org/schema/media/document
            [5] => http://rs.gbif.org/terms/1.0/multimedia
            [6] => http://eol.org/schema/reference/reference
            [7] => http://rs.gbif.org/terms/1.0/vernacularname
        ) */
        $media_document = "http://eol.org/schema/media/document";;
        $description = "http://rs.gbif.org/terms/1.0/description";
        if(in_array($media_document, $index) && in_array($description, $index)) {
            $arr = array_diff($index, array($description));
            $arr[] = $description;
            $arr = array_values($arr); //reindex key
            return $arr;
        }
        else return $index;
    }
    function TreatmentBank_stats($rec, $description_type)
    {
        @$this->debug[$this->resource_id]['text type'][$rec['http://purl.org/dc/terms/type']]++;
        // save examples for Jen's investigation:
        $sought = array("synonymic_list", "vernacular_names", "food_feeding", "breeding", "activity", "use", "ecology", "", "biology", "material");
        // $sought = array('distribution'); //debug only
        if(in_array($description_type, $sought)) {
            $count = count(@$this->debug[$this->resource_id]['type e.g.'][$description_type]);
            if($count <= 100) $this->debug[$this->resource_id]['type e.g.'][$description_type][$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = '';
        }
    }
    function format_field($rec)
    {
        /* furtherInformationURL
        File: media_resource.tab
        Line: 338149
        URI: http://rs.tdwg.org/ac/terms/furtherInformationURL
        Message: Invalid URL
        Line Value: |https://treatment.plazi.org/id/E97287E44C427A0C1FEAFB6CFADACDC2        

        File: media_resource.tab
        Line: 1782706
        URI: http://rs.tdwg.org/ac/terms/furtherInformationURL
        Message: Invalid URL
        Line Value: Jonsell, B., Karlsson (2005): Chenopodiaceae - Fumariaceae (Chenopodium). Flora Nordica 2: 4-31, URL: http://antbase.org/ants/publications/FlNordica_chenop/FlNordica_chenop.pdf
        */
        $furtherInformationURL = str_replace("|", "", $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL']);
        if(substr($furtherInformationURL,0,4) != "http") $furtherInformationURL = ""; //invalid data, maybe due to erroneous tab count.
        $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL'] = $furtherInformationURL;

        /* http://purl.org/dc/terms/type
        File: media_resource.tab
        Line: 1782706
        URI: http://purl.org/dc/terms/type
        Message: Invalid DataType
        Line Value: https://treatment.plazi.org/id/01660DF93D09DB09C986CB2380FAB116
        */
        $type = $rec['http://purl.org/dc/terms/type'];
        if($type != "http://purl.org/dc/dcmitype/Text") $rec['http://purl.org/dc/terms/type'] = false;

        // [description] if last char is "|", should delete it. It messes with tab separators during validation tool.
        $desc = $rec['http://purl.org/dc/terms/description'];
        if(substr($desc, -1) == "|") $rec['http://purl.org/dc/terms/description'] = substr($desc, 0, strlen($desc)-1);
        
        return $rec;
    }
    function process_table_TreatmentBank_document($rec, $row_type, $meta, $zip_file)
    {   // print_r($rec); print_r($row_type); print_r($meta); exit("\n[$zip_file]\n");
        $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
        if($row_type == 'http://eol.org/schema/media/document') { //not http://rs.gbif.org/terms/1.0/description
            if(!$rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms']) return false; //continue; //exclude with blank license
            // build-up an info list
            $this->info_taxonID_mediaRec[$taxon_id] = array('UsageTerms'    => $rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms'],
                                                            'rights'        => $rec['http://purl.org/dc/terms/rights'],
                                                            'Owner'         => $rec['http://ns.adobe.com/xap/1.0/rights/Owner'],
                                                            'contributor'   => $rec['http://purl.org/dc/terms/contributor'],
                                                            'creator'       => $rec['http://purl.org/dc/terms/creator'],
                                                            'bibliographicCitation' => $rec['http://purl.org/dc/terms/bibliographicCitation']);
            //new 23Jun2024
            $rec['http://rs.tdwg.org/ac/terms/additionalInformation'] = $zip_file;
        }
        elseif($row_type == 'http://rs.gbif.org/terms/1.0/description') { //not http://eol.org/schema/media/document
            /* Array( print_r($rec);
                [http://rs.tdwg.org/dwc/terms/taxonID] => 03C44153FFA9FFABFF77F9DFFADFFA97.taxon
                [http://purl.org/dc/terms/type] => description
                [http://purl.org/dc/terms/description] => Immature stages Egg. Eggs elongate oval to somewhat cylindrical, chorion with distinct microsculpture in Chilocorus (Figs 4 a, 5 a), Brumoides (Fig. 4 b), and Priscibrumus Kovář. Eggs laid singly or in small groups on or in the vicinity of prey. Chilocorus spp. have a characteristic and peculiar habit of laying eggs on sibling larvae, pupae, and exuviae besides the host colony (Fig. 4 c – e). Larva. Larvae of Chilocorini have a nearly cylindrical or broadly fusiform body with the dorsal and lateral surfaces covered with setose projections (“ senti ”) or prominent parascoli (Figs 4 f, g; 5 b – e). After completing their development, the mature larvae of Chilocorini, particularly armoured-scale feeders, pass 1 – 2 days in an immobile, prepupal stage (Fig. 5 f). Pupa. Pupae are exarate and enclosed in longitudinally and medially split open larval exuvium (Figs 4 h, i; 5 g). In many Chilocorus spp., larvae congregate in small or large clusters on the lower side of branches or on the tree trunk for pupation (Drea & Gordon 1990). It is common to see large congregations of pupae in Indian species such as Chilocorus circumdatus (Gyllenhal) (Fig. 6 a, b), C. nigrita (Fig. 6 c, d) and C. infernalis Mulsant on various host plants.
                [http://purl.org/dc/terms/language] => en
                [http://purl.org/dc/terms/source] => POORANI, J. (2023): An illustrated guide to the lady beetles (Coleoptera: Coccinellidae) of the Indian Subcontinent. Part II. Tribe Chilocorini. Zootaxa 5378 (1): 1-108, DOI: 10.11646/zootaxa.5378.1.1, URL: https://www.mapress.com/zt/article/download/zootaxa.5378.1.1/52353
            ) */
            if(!$rec['http://purl.org/dc/terms/description']) return false; //continue; //description cannot be blank
            $description_type = $rec['http://purl.org/dc/terms/type'];
            
            // exclude these types:
            if(in_array($description_type, array('etymology', 'discussion', 'type_taxon')))                                     return false; //continue;
            // Additional text types:
            elseif(in_array($description_type, array("synonymic_list", "vernacular_names", "ecology", "biology", "material")))  return false; //continue;
            elseif(in_array($description_type, array("food_feeding", "breeding", "activity", "use")))                           return false; //continue;
            elseif(!$description_type)                                                                                          return false; //continue;
            // if($description_type == 'type_taxon') { print_r($rec); exit; } //debug only good debug

            $this->TreatmentBank_stats($rec, $description_type); // stat only purposes, good report.

            $json = json_encode($rec);
            $rec['http://purl.org/dc/terms/identifier'] = md5($json);
            $addInfo = array($rec['http://purl.org/dc/terms/type'], $zip_file);
            $rec['http://rs.tdwg.org/ac/terms/additionalInformation'] = implode("|", $addInfo);
            $rec['http://purl.org/dc/terms/type'] = "http://purl.org/dc/dcmitype/Text";
            $rec['http://purl.org/dc/terms/format'] = "text/html";
            $rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses";
            $rec['http://purl.org/dc/terms/title'] = "Type: $description_type";
            $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL'] = "https://treatment.plazi.org/id/".str_replace(".taxon", "", $rec['http://rs.tdwg.org/dwc/terms/taxonID']);
            $rec['http://purl.org/dc/terms/bibliographicCitation'] = $rec['http://purl.org/dc/terms/source'];
            unset($rec['http://purl.org/dc/terms/source']);
            // /* supplement with data from media row_type
            if($val = $this->info_taxonID_mediaRec[$taxon_id]) {
                $rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms']   = $val['UsageTerms']; //Public Domain
                $rec['http://purl.org/dc/terms/rights']                 = $val['rights']; //No known copyright restrictions apply. See Agosti, D., Egloff, W., 2009. Taxonomic information exchange and copyright: the Plazi approach. BMC Research Notes 2009, 2:53 for further explanation.
                $rec['http://ns.adobe.com/xap/1.0/rights/Owner']        = $val['Owner'];
                $rec['http://purl.org/dc/terms/contributor']            = $val['contributor']; //MagnoliaPress via Plazi
                $rec['http://purl.org/dc/terms/creator']                = $val['creator']; //POORANI, J.
                $rec['http://purl.org/dc/terms/bibliographicCitation']  = $val['bibliographicCitation']; //POORANI, J. (2023): An illustrated guide to the lady beetles (Coleoptera: Coccinellidae) of the Indian Subcontinent. Part II. Tribe Chilocorini. Zootaxa 5378 (1): 1-108, DOI: 10.11646/zootaxa.5378.1.1, URL: https://www.mapress.com/zt/article/download/zootaxa.5378.1.1/52353
            }
            // */                        
        }
        // ===================== 2nd part TreatmentBank customization
        $rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses";
                    
        // /* New: exclude non-English text
        if($lang = @$rec['http://purl.org/dc/terms/language']) {
            if($lang && $lang != "en") return false; //continue;
        }
        // */
        
        /* Used by our original text object.
        // remove taxonomic/nomenclature line from description
        if($description = @$rec['http://purl.org/dc/terms/description']) {
            $rec['http://purl.org/dc/terms/description'] = $this->remove_taxon_lines_from_desc($description);
        } */

        $rec = $this->format_field($rec);
        if(!$rec['http://purl.org/dc/terms/type']) return false; //continue;

        // /* shorten the bibliographicCitation: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66418&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66418
        if($bibliographicCitation = @$rec['http://purl.org/dc/terms/bibliographicCitation']) {
            $rec['http://purl.org/dc/terms/bibliographicCitation'] = $this->shorten_bibliographicCitation($meta, $bibliographicCitation);
            /* good debug
            if(true) {
            //if(stripos($bibliographicCitation, "Hespenheide, Henry A. (2019): A Review of the Genus Laemosaccus Schönherr, 1826 (Coleoptera: Curculionidae: Mesoptiliinae) from Baja California and America North of Mexico: Diversity and Mimicry") !== false) { //string is found
            //if(stripos($bibliographicCitation, "Grismer, L. Lee, Wood, Perry L., Jr, Lim, Kelvin K. P. (2012): Cyrtodactylus Majulah") !== false) { //string is found
                echo "\n===============================start\n";
                // print_r($meta); echo "\nwhat: [$what]\n";
                print_r($rec); //echo "\nresource_id: [$this->resource_id_current]\n";
                echo "\n===============================end\n";
                // exit("\n");
            } */
        }
        // print_r($rec); exit("\nexit muna...\n");
        /* Array( --- as of Dec 4, 2023
            [http://purl.org/dc/terms/identifier] => 03C44153FFA9FFABFF77F9DFFADFFA97.text
            [http://rs.tdwg.org/dwc/terms/taxonID] => 03C44153FFA9FFABFF77F9DFFADFFA97.taxon
            [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
            [http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses
            [http://purl.org/dc/terms/format] => text/html
            [http://purl.org/dc/terms/language] => en
            [http://purl.org/dc/terms/title] => Chilocorini Mulsant 1846
            [http://purl.org/dc/terms/description] => Form circular, broadly oval, or distinctly elongate oval (Fig. 2); dorsum often dome-shaped and strongly convex or moderately convex, shiny and glabrous (at the most only head and anterolateral flanks of pronotum with hairs), or with sparse, short and suberect pubescence on elytral disc and more visibly on lateral margins, or with distinct dorsal pubescence (Fig. 2g, i). Head capsule with anterior clypeal margin laterally strongly expanded over eyes, medially emarginate, rounded or laterally truncate (Fig. 3a–c). Anterior margin of pronotum deeply and trapezoidally excavate, lateral margins strongly descending below; anterior angles usually strongly produced anteriorly. Elytra basally much broader than pronotum. Antennae short (7–10 segmented) (Fig. 3g –j), shorter than half the width of head; antennal insertions hidden and broadly separated. Terminal maxillary palpomere (Fig. 3d–f) parallel-sided and apically obliquely transverse or securiform or elongate, slender, subcylindrical to tapered with oblique apex, or somewhat swollen with subtruncate apex. Prosternal intercoxal process without carinae (Fig. 3k). Elytral epipleura broad, sometimes strongly descending externally with inner carina reaching elytral apex or not. Legs often with strongly angulate tibiae; tarsal formula 4–4–4 (Fig. 3o, p); tarsal claws simple (Fig. 3u) or appendiculate (Fig. 3v). Abdominal postcoxal line incomplete (Fig. 3l, n) or complete (Fig. 3m). Female genitalia with elongate triangular or transverse coxites (Fig. 3q, r); spermatheca with (Fig. 3t) or without (Fig. 3s, w) a membranous, beak-like projection at apex; sperm duct between bursa copulatrix and spermatheca most often composed of two or three parts of different diameters (Fig. 3w); infundibulum present (Fig. 3w) or absent...
            [http://rs.tdwg.org/ac/terms/furtherInformationURL] => https://treatment.plazi.org/id/03C44153FFA9FFABFF77F9DFFADFFA97
            [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => Public Domain
            [http://purl.org/dc/terms/rights] => No known copyright restrictions apply. See Agosti, D., Egloff, W., 2009. Taxonomic information exchange and copyright: the Plazi approach. BMC Research Notes 2009, 2:53 for further explanation.
            [http://ns.adobe.com/xap/1.0/rights/Owner] => 
            [http://purl.org/dc/terms/contributor] => MagnoliaPress via Plazi
            [http://purl.org/dc/terms/creator] => POORANI, J.
            [http://purl.org/dc/terms/bibliographicCitation] => POORANI, J. (2023): An illustrated guide to the lady beetles (Coleoptera: Coccinellidae) of the Indian Subcontinent. Part II. Tribe Chilocorini. Zootaxa 5378 (1): 1-108, DOI: 10.11646/zootaxa.5378.1.1, URL: https://www.mapress.com/zt/article/download/zootaxa.5378.1.1/52353
        ) */

        return $rec;
    }
    function process_table_TreatmentBank_taxon($rec)
    {   // ancestry fields must not have separators: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66656&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66656
        $ancestors = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
        foreach($ancestors as $ancestor) {
            if($val = trim(@$rec["http://rs.tdwg.org/dwc/terms/".$ancestor])) {
                if(stripos($val, ";") !== false) $rec["http://rs.tdwg.org/dwc/terms/".$ancestor] = ""; //string is found
                elseif(stripos($val, ",") !== false) $rec["http://rs.tdwg.org/dwc/terms/".$ancestor] = ""; //string is found
                elseif(stripos($val, " ") !== false) $rec["http://rs.tdwg.org/dwc/terms/".$ancestor] = ""; //string is found
            }
        }
        if($rec['http://rs.tdwg.org/dwc/terms/taxonID'] == "03A487F05711FB7CFECA8E029F9BA19D.taxon") return false; //continue;
        if(stripos($rec['http://rs.tdwg.org/dwc/terms/scientificName'], "Acrididae;") !== false) return false; //continue; //string is found
        if(stripos(@$rec['http://rs.gbif.org/terms/1.0/canonicalName'], "Acrididae;") !== false) return false; //continue; //string is found

        @$this->debug[$this->resource_id]['taxonRank'][$rec['http://rs.tdwg.org/dwc/terms/taxonRank']]++;

        // /* new: Nov 21, 2023:
        if($scientificName = @$rec["http://rs.tdwg.org/dwc/terms/scientificName"]) {
            if(!Functions::valid_sciname_for_traits($scientificName)) return false;
            if(in_array($rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'], array('synonym'))) return false;
            if(self::no_ancestry_fields($rec)) return false;
        }
        else return false;
        // */

        @$this->debug[$this->resource_id]['taxonomicStatus'][$rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus']]++;

        /*Array(
            [http://rs.tdwg.org/dwc/terms/taxonID] => 5ACD6D1A54AC5FBC87E765FCFE855D63.taxon
            [http://rs.tdwg.org/dwc/terms/namePublishedIn] => 
            [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
            [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 
            [http://rs.tdwg.org/dwc/terms/originalNameUsageID] => 
            [http://rs.tdwg.org/dwc/terms/kingdom] => Animalia
            [http://rs.tdwg.org/dwc/terms/phylum] => Arthropoda
            [http://rs.tdwg.org/dwc/terms/class] => Insecta
            [http://rs.tdwg.org/dwc/terms/order] => Heteroptera
            [http://rs.tdwg.org/dwc/terms/family] => Acanthosomatidae
            [http://rs.tdwg.org/dwc/terms/genus] => Acanthosoma
            [http://rs.tdwg.org/dwc/terms/taxonRank] => species
            [http://rs.tdwg.org/dwc/terms/scientificName] => Acanthosoma Denticaudum (Jakovlev 1880)
            [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => (Jakovlev 1880)
            [http://gbif.org/dwc/terms/1.0/canonicalName] => Acanthosoma Denticaudum
            [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => 
            [http://rs.tdwg.org/dwc/terms/nomenclaturalStatus] => 
            [http://purl.org/dc/terms/references] => http://treatment.plazi.org/id/5ACD6D1A54AC5FBC87E765FCFE855D63
        )*/

        // /* new: by Eli 24Jun2024
        if($val = @$rec['http://rs.gbif.org/terms/1.0/canonicalName']) {
            $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = $val;
            $scientificName = $val;
        }
        elseif($val = $rec['http://gbif.org/dwc/terms/1.0/canonicalName']) {
            $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = $val;
            $scientificName = $val;
        }
        else { print_r($rec); exit("\nNo canonical\n"); }
        // */            

        // more than 2 numeric strings: e.g. "Pseudosphingonotus savignyi (Saussure 1884) Schumakov 1963"
        $matches = array();
        preg_match_all('/([0-9]+)/', $scientificName, $matches);
        if(count($matches[1]) > 1) {
            $obj = self::run_gnfinder($scientificName); print_r($obj);
            if($val = @$obj->names[0]->bestResult->matchedCanonicalSimple) $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = $val;
        }

        // /* Some scientificName values have uppercase epithets although the epithets at the source have the appropriate lower case. e.g. "Coranus Aethiops Jakovlev 1893" https://github.com/EOL/ContentImport/issues/13
        $parts = explode(" ", $scientificName);
        if(ctype_upper(substr(@$parts[1], 0, 1))) { echo "\n[epithet uppercase: ".$scientificName."]\n";
            $rec = self::epithet_upper_case($rec);
        } //ctype_upper

        // print_r($rec); //exit("\n[$sciname]\n");
        // if("Insecta" == @$rec['http://gbif.org/dwc/terms/1.0/canonicalName']) exit("\nelix\n"); //debug only
        // */

        /* utility: gni test only
        $sciname = "Deuterodon aff. taeniatus (Jenyns, 1842)";
        // $sciname = "Cercyon (Acycreon) apiciflavus Hebauer 2002";                                       //-> canonical simple: Cercyon apiciflavus
        // $sciname = "Galesus (G.) foersteri var. nigricornis Kieffer 1911";                              //-> canonical simple: Galesus foersteri nigricornis
        // $sciname = "Spartina ×townsendii H. Groves & J. Groves, Bot. Exch. Club Rep. 1880. 37. 1881.";  //-> canonical simple: Spartina townsendii
        $sciname = "(Porch) Kolibáč, Bocakova, Liebherr, Ramage, and Porch 2021";
        $sciname = "Tenebroides (Polynesibroides) Kolibáč, Bocakova, Liebherr, Ramage, and Porch 2021";
        $sciname = "Pseudosphingonotus savignyi (Saussure 1884) Schumakov 1963";
        $obj = self::run_gnfinder($sciname); print_r($obj);
        echo "\ncanonical simple: [".@$obj->names[0]->bestResult->matchedCanonicalSimple."]\n";
        exit("\norig: [$sciname]\n"); //works OK but not needed here.
        */

        /* This should accommodate cases where the scientificName value includes things like a subgenus name, a var./ssp./f. abbreviation or a hybrid character. Examples:
        scientificName: Cercyon (Acycreon) apiciflavus Hebauer 2002 -> canonical simple: Cercyon apiciflavus
        scientificName: Galesus (G.) foersteri var. nigricornis Kieffer 1911 -> canonical simple: Galesus foersteri nigricornis
        scientificName: Spartina ×townsendii H. Groves & J. Groves, Bot. Exch. Club Rep. 1880. 37. 1881. -> canonical simple: Spartina townsendii */
        $rec = self::get_name_from_gnfinder($rec);

        if(!self::katja_valid_name($rec)) return false;

        return $rec;
    }
    private function epithet_upper_case($rec)
    {
        // print_r($rec); //exit;
        $taxonID = str_replace(".taxon", "", $rec['http://rs.tdwg.org/dwc/terms/taxonID']);
        $url = "http://tb.plazi.org/GgServer/xml/".$taxonID;

        $options = $this->download_TB_options;
        $options['expire_seconds'] = false; //doesn't expire
        $xml_string = Functions::lookup_with_cache($url, $options);

        // $xml_string = 'xxxdocTitle="Callichthys callichthys callichthys (Linnaeus 1758" yyy<taxonomicName id="0E052165DC2726A7AD530570A1A6D6C2" LSID="BC789CA9-3135-5E6F-8EC5-A6F40F9AE404" authority="callichthys (Linnaeus, 1758)" authorityName="callichthys (Linnaeus" authorityYear="1758" baseAuthorityName="Linnaeus" baseAuthorityYear="1758" class="Actinopterygii" family="Callichthyidae" genus="Callichthys" higherTaxonomySource="CoL" kingdom="Animalia" lsidName="Callichthys callichthys" order="Siluriformes" pageId="0" pageNumber="25" phylum="Chordata" rank="species" species="callichthys">Callichthys callichthys (Linnaeus, 1758)</taxonomicName>ddd';

        $xml_sciname1 = self::get_taxonomicName_from_xml($xml_string, 'taxonomicName');
        $xml_sciname2 = self::get_taxonomicName_from_xml($xml_string, 'docTitle');

        // echo "\nxml_sciname1: [$xml_sciname1]\n"; echo "\nxml_sciname2: [$xml_sciname2]\n"; exit; //debug only

        $ret1 = self::xml_and_dwca_same_names($xml_sciname1, $rec);
        /* debug only
        if(in_array($taxonID, array('BC789CA931355E6F8EC5A6F40F9AE404'))) {
            print_r($ret1);
            exit("\n[$xml_sciname1]\n[$xml_sciname2]\nditox xx"); //debug only
        }
        */        
        if($ret1['final']) {
            // if(in_array($taxonID, array('BC789CA931355E6F8EC5A6F40F9AE404'))) exit("\n[$xml_sciname1]\n[$xml_sciname2]\nditox 1"); //debug only
            $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = $xml_sciname1;
            $rec['http://gbif.org/dwc/terms/1.0/canonicalName'] = Functions::canonical_form($xml_sciname1);
        }
        else {
            $ret2 = self::xml_and_dwca_same_names($xml_sciname2, $rec);
            if($ret2['final']) {
                if(in_array($taxonID, array('BC789CA931355E6F8EC5A6F40F9AE404'))) {
                    exit("\n does not go here: [$xml_sciname1]\n[$xml_sciname2]\n");
                    // print_r($ret2); exit("\n[$xml_sciname1]\n[$xml_sciname2]\nditox 2"); //debug only
                    $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = $xml_sciname1;
                    $rec['http://gbif.org/dwc/terms/1.0/canonicalName'] = Functions::canonical_form($xml_sciname1);    
                }
                else {
                    $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = $xml_sciname2;
                    $rec['http://gbif.org/dwc/terms/1.0/canonicalName'] = Functions::canonical_form($xml_sciname2);    
                }
            }
            else {
                if(in_array($taxonID, array('BC789CA931355E6F8EC5A6F40F9AE404'))) {
                    // exit("\n does not go here: [$xml_sciname1]\n[$xml_sciname2]\n"); //went here
                    $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = $xml_sciname1; //will do 
                    $rec['http://gbif.org/dwc/terms/1.0/canonicalName'] = Functions::canonical_form($xml_sciname1);    
                }
                else {
                    echo "\n--------------------------------------------------Investigate:\n"; print_r($rec); 
                    echo "\nsciname from XML taxonomicName: [".self::get_taxonomicName_from_xml($xml_string, 'taxonomicName')."]\n";
                    echo "\nsciname from XML docTitle: [".self::get_taxonomicName_from_xml($xml_string, 'docTitle')."]\n";

                    echo "\nsciname from DwCA: [".$rec['http://rs.tdwg.org/dwc/terms/scientificName']."]\n";
        
                    echo "\nsciname from XML 1: [".$ret1['sciname']."]\n";
                    echo "\nsciname from DwCA 1: [".$ret1['rec_sciname']."]\n";

                    echo "\nsciname from XML 2: [".$ret2['sciname']."]\n";
                    echo "\nsciname from DwCA 2: [".$ret2['rec_sciname']."]\n";

                    exit("\nsciname from XML is different from sciname from DWCA\n");    
                }
            }
        }
        return $rec;
    }
    private function get_name_from_gnfinder($rec)
    {
        /* This should accommodate cases where the scientificName value includes things like a subgenus name, a var./ssp./f. abbreviation or a hybrid character. Examples:
        scientificName: Cercyon (Acycreon) apiciflavus Hebauer 2002 -> canonical simple: Cercyon apiciflavus
        scientificName: Galesus (G.) foersteri var. nigricornis Kieffer 1911 -> canonical simple: Galesus foersteri nigricornis
        scientificName: Spartina ×townsendii H. Groves & J. Groves, Bot. Exch. Club Rep. 1880. 37. 1881. -> canonical simple: Spartina townsendii */
        $scientificName = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
        // case 1
        $strings = array(" var.", " ssp.", " f.", "×", " x ");
        foreach($strings as $str) {
            if(stripos($scientificName, $str) !== false) { //string is found
                if($val = self::get_canonical_simple($scientificName)) $rec['http://rs.tdwg.org/dwc/terms/scientificName'] = $val;
            }
        }        
        return $rec;
    }
    private function xml_and_dwca_same_names($xml_sciname, $rec)
    {
        $sciname = $xml_sciname;
        $sciname = str_replace(" aff. ", " ", $sciname); //BFDABD75C1CF582D8A453276518C11D0.taxon

        $rec_sciname = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];

        $sciname     = str_replace(".", ". ", $sciname);
        $rec_sciname = str_replace(".", ". ", $rec_sciname);

        $sciname     = Functions::remove_whitespace(str_replace(array(",", ".", ";"), "", $sciname));
        $rec_sciname = Functions::remove_whitespace(str_replace(array(",", ".", ";"), "", $rec_sciname));

        $sciname = strtolower(self::replace_accents($sciname));
        $rec_sciname = strtolower(self::replace_accents($rec_sciname));

        $sciname = Functions::remove_whitespace(preg_replace('/\s*\([^)]*\)/', '', $sciname)); //remove parenthesis OK
        $rec_sciname = Functions::remove_whitespace(preg_replace('/\s*\([^)]*\)/', '', $rec_sciname)); //remove parenthesis OK
        
        $sciname = Functions::remove_whitespace(self::remove_numeric_from_string($sciname));
        $rec_sciname = Functions::remove_whitespace(self::remove_numeric_from_string($rec_sciname));

        if($sciname == $rec_sciname)    return array('final' => true, 'sciname' => $sciname, 'rec_sciname' => $rec_sciname);
        else {
            $sciname = Functions::remove_whitespace(str_replace("&amp", "", $sciname));
            if($sciname == $rec_sciname)    return array('final' => true, 'sciname' => $sciname, 'rec_sciname' => $rec_sciname);
            else {
                $rec_sciname = Functions::remove_whitespace(str_replace("and", "", $rec_sciname));
                if($sciname == $rec_sciname)    return array('final' => true, 'sciname' => $sciname, 'rec_sciname' => $rec_sciname);
                else                            return array('final' => false, 'sciname' => $sciname, 'rec_sciname' => $rec_sciname);
            }
        }
    }
    function run_gnfinder($str)
    {
        require_library('connectors/Functions_Memoirs');
        require_library('connectors/ParseListTypeAPI_Memoirs');
        require_library('connectors/ParseUnstructuredTextAPI_Memoirs'); 
        $func = new ParseUnstructuredTextAPI_Memoirs(false, false);
        $obj = $func->run_gnverifier($str); //print_r($obj); //exit;
        return $obj;
    }
    function get_taxonomicName_from_xml($xml, $what)
    {   if($what == 'taxonomicName') {
            if(preg_match("/<taxonomicName(.*?)<\/taxonomicName>/ims", $xml, $arr)) {
                $sciname = self::replace_accents($arr[1]);
                $sciname = trim(strip_tags("<taxonomicName".$sciname));
                $sciname = str_replace(array("\t", chr(10), chr(13)), " ", $sciname);
                $sciname = html_entity_decode($sciname); //important 523AFB0F879857DEA647B13C3BAB685A.taxon

                $parts = explode(" ", $sciname);
                if(count($parts) > 2) {
                    $obj = self::run_gnfinder($sciname); //print_r($obj);
                    if($val = @$obj->names[0]->bestResult->matchedCanonicalSimple) return Functions::remove_whitespace($val);
                }
                else return Functions::remove_whitespace($sciname); //to limit call to gnfinder
            }    
        }
        elseif($what == 'docTitle') {
            if(preg_match("/docTitle=\"(.*?)\"/ims", $xml, $arr)) {
                $sciname = $arr[1];

                $parts = explode(" ", $sciname);
                if(count($parts) > 2) {
                    $obj = self::run_gnfinder($sciname); //print_r($obj);
                    if($val = @$obj->names[0]->bestResult->matchedCanonicalSimple) return Functions::remove_whitespace($val);
                }
                else return Functions::remove_whitespace($sciname); //to limit call to gnfinder
            }    
        }
        exit("\nInvestigate: no <taxonomicName> nor docTitle found in XML.\n");
    }
    function katja_valid_name($rec)
    {   
        $scientificName = $rec['http://rs.tdwg.org/dwc/terms/scientificName'];
        $taxonRank      = strtolower($rec['http://rs.tdwg.org/dwc/terms/taxonRank']);
        // /* Misparsed or malformed names
        // Please remove all records for taxa that have one the following strings in their scientific name values (case insensitive): undefined|undetermined|incertae sedis
        $strings = array('undefined', 'undetermined', 'incertae sedis');
        foreach($strings as $str) {
            if(stripos($scientificName, $str) !== false) return false; //string is found
        }
        // */

        // /* Higher taxa
        // Please remove all records for taxa that are NOT of rank species|variety|subspecies|form. There are over 90,000 of these records. 
        // Most of them are mismapped, i.e., the trait record is attached to a genus or family or worse, 
        // but the matched value is actually providing information for a species that is not picked up by the parser. Examples:
        if(!in_array($taxonRank, array('species', 'variety', 'subspecies', 'form'))) return false;
        // */

        // [taxonRank] => Array(
        //     [species] => 211
        //     [genus] => 20
        //     [subGenus] => 4
        //     [class] => 1
        //     [subSpecies] => 28
        //     [variety] => 4 )
        //  ...many more...
        /* Please remove all records for:
        taxa of rank species where the canonical name (simple) does not match [A-Z][a-z-]+ [a-z-]+
        taxa of rank variety|subspecies|form where the canonical name (simple) does not match [A-Z][a-z-]+ [a-z-]+ [a-z-]+.
        */
        if($taxonRank == 'species') {
            // $sciname = "R. crataegifolius Bunge Mém. Acad. Imp. Sci. St. - Pétersbourg Divers Savans 2: 98. 1835."; //cannot be rescued
            // $sciname = "C. italicus (Linnaeus, 1758)"; //cannot be rescued    
            $pattern = "/[A-Z][a-z-]+ [a-z-]+/";
            $canonical_simple = self::get_canonical_simple($scientificName);
            if(!preg_match($pattern, $canonical_simple)) {
                echo "\ninvalid reg: [$scientificName] [$canonical_simple]\n";
                return false;
            }
            else echo "\nvalid reg: [$scientificName] [$canonical_simple]\n";
        }

        /* taxa of rank variety|subspecies|form where the canonical name (simple) does not match [A-Z][a-z-]+ [a-z-]+ [a-z-]+. */
        $ranks = array('variety', 'subspecies', 'form');
        if(in_array($taxonRank, $ranks)) {
            $pattern = "/[A-Z][a-z-]+ [a-z-]+ [a-z-]+./";
            if(!preg_match($pattern, $scientificName)) {
                echo "\ninvalid reg rank [$taxonRank]: [$scientificName]\n";
                return false;
            }
            else echo "\nvalid reg rank [$taxonRank]: [$scientificName]\n";
        }

        return true; //means it's a valid name
    }
    private function get_canonical_simple($scientificName)
    {
        if($scientificName == Functions::canonical_form($scientificName)) return $scientificName;

        $obj = self::run_gnfinder($scientificName);
        if($val = @$obj->names[0]->bestResult->matchedCanonicalSimple) return $val;

        return $scientificName; //fallback
    }
    function replace_accents($str)
    {
        // $string = 'Ë À Ì Â Í Ã Î Ä Ï Ç Ò È Ó É Ô Ê Õ Ö ê Ù ë Ú î Û ï Ü ô Ý õ â û ã ÿ ç';
        $normalizeChars = array(
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ń'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
            'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ń'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
            'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f',
            'ă'=>'a', 'î'=>'i', 'â'=>'a', 'ș'=>'s', 'ț'=>'t', 'Ă'=>'A', 'Î'=>'I', 'Â'=>'A', 'Ș'=>'S', 'Ț'=>'T',
        );
        //Output: E A I A I A I A I C O E O E O E O O e U e U i U i U o Y o a u a y c
        return strtr($str, $normalizeChars);        
    }
    private function remove_numeric_from_string($string)
    {
        $digits = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0");
        return str_ireplace($digits, '', $string);
    }
    private function no_ancestry_fields($rec)
    {
        $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
        foreach($ranks as $rank) {
            if(@$rec["http://rs.tdwg.org/dwc/terms/".$rank]) return false; //has value
        }
        return true; //all fields are blank
    }
}
?>