<?php
namespace php_active_record; 
/* 
as of Sep 2024: when working on Katja's [TreatmentBank trait data adjustment] https://github.com/EOL/ContentImport/issues/13
php update_resources/connectors/treatment_bank.php _ '{"task": "generate_single_dwca"}'
php update_resources/connectors/environments_2_eol.php _ '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"TreatmentBank", "subjects":"Uses"}'
php update_resources/connectors/run_pensoft_test.php 

-----------------------------------------------

DATA-1896: TreatmentBank - http://content.eol.org/resources/562
as of Sep 1, 2021 = 611,618 items
STEP 1:
php update_resources/connectors/treatment_bank.php _ '{"range_from": "1", "range_to":"100000"}'
php update_resources/connectors/treatment_bank.php _ '{"range_from": "100000", "range_to":"200000"}'
php update_resources/connectors/treatment_bank.php _ '{"range_from": "700000", "range_to":"700050"}'
{total of 8 ranges, see jenkins}

as of Nov 30, 2023
1-50        all fr
100-150     all de
25000-25050 all en

8 ranges in jenkins:
php5.6 treatment_bank.php jenkins '{"range_from": "1", "range_to":"100000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "100000", "range_to":"200000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "200000", "range_to":"300000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "300000", "range_to":"400000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "400000", "range_to":"500000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "500000", "range_to":"600000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "600000", "range_to":"700000"}'
php5.6 treatment_bank.php jenkins '{"range_from": "700000", "range_to":"779000"}' //actual 778,468 as of Dec 4, 2023

STEP 2:
php update_resources/connectors/treatment_bank.php _ '{"task": "build_up_dwca_list"}'
-> generates /resources/reports/Plazi_DwCA_list.txt

STEP 2.1: Utility: report for Jen to get document type for all DwCAs

STEP 3:
php update_resources/connectors/treatment_bank.php _ '{"task": "generate_single_dwca"}'
-> generates TreatmentBank.tar.gz

STEP 4:
php update_resources/connectors/environments_2_eol.php _       '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"TreatmentBank", "subjects":"Uses"}'
                         php5.6 environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"TreatmentBank", "subjects":"Uses"}'
-> generates TreatmentBank_ENV.tar.gz

STEP 5: adjustments starting here: https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=66874&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66874
php update_resources/connectors/treatmentbank_adjust.php _
                         php5.6 treatmentbank_adjust.php jenkins
-> generates TreatmentBank_adjustment_01.tar.gz

STEP 6:
php5.6 clade_filters_4_habitats.php jenkins '{"resource_id":"TreatmentBank_adjustment_01"}'
#-> generates TreatmentBank_adjustment_02.tar.gz

STEP 7: NEW
php5.6 georgia_cntry_vs_state.php jenkins '{"resource_id": "TreatmentBank_adjustment_02"}'
-> generates TreatmentBank_adjustment_03.tar.gz

# === LAST STEP: copy TreatmentBank_adjustment_03.tar.gz to TreatmentBank_final.tar.gz OK
cd /html/eol_php_code/applications/content_server/resources
cp TreatmentBank_adjustment_03.tar.gz TreatmentBank_final.tar.gz
ls -lt TreatmentBank_adjustment_03.tar.gz
ls -lt TreatmentBank_final.tar.gz
# then delete TreatmentBank_adjustment_03.tar.gz
rm -f TreatmentBank_adjustment_03.tar.gz

================== STATS ==================
TreatmentBank	    Wed 2021-09-08 04:23:09 PM	{                                                   "media.tab":596261, "taxon.tab":597054, "time_elapsed":{"sec":66673.13, "min":1111.22, "hr":18.52}}
TreatmentBank_ENV	Tue 2021-09-28 02:37:49 PM	{"MoF.tab":1554957, "occur_specific.tab":1554957,                       "taxon.tab":597054, "time_elapsed":{"sec":317931.33, "min":5298.86, "hr":88.31, "day":3.68}}
TreatmentBank	    Tue 2021-10-05 04:07:26 AM	{                                                   "media.tab":596261, "taxon.tab":597054, "time_elapsed":{"sec":66569.05, "min":1109.48, "hr":18.49}}
TreatmentBank_ENV	Tue 2021-10-05 05:38:14 AM	{"MoF.tab":1554958, "occur_specific.tab":1554958,                       "taxon.tab":597054, "time_elapsed":{"sec":5437.94, "min":90.63, "hr":1.51}}
TreatmentBank_ENV	Thu 2021-10-28 03:16:53 AM	{"MoF.tab":1554958, "occur_specific.tab":1554958,                       "taxon.tab":597054, "time_elapsed":{"sec":12692.65, "min":211.54, "hr":3.53}}
after filter using EOL terms file:
TreatmentBank	    Thu 2022-01-27 03:02:02 AM	{                                                   "media.tab":596261, "taxon.tab":597054, "time_elapsed":{"sec":67599.45, "min":1126.66, "hr":18.78}}
TreatmentBank_ENV	Thu 2022-01-27 06:50:50 AM	{"MoF.tab":1818376, "occur_specific.tab":1818376,                       "taxon.tab":597054, "time_elapsed":{"sec":13718.37, "min":228.64, "hr":3.81}}
after cleaning scientificName e.g. https://zenodo.org/record/5688763#.YflRD_XMJ_Q
TreatmentBank	    Fri 2022-02-04 03:50:57 AM	{                                                   "media.tab":596261, "taxon.tab":597053, "time_elapsed":{"sec":66563.64, "min":1109.39, "hr":18.49}}

TreatmentBank_ENV	Fri 2022-02-04 05:42:20 AM	{"MoF.tab":1818376, "occur_specific.tab":1818376,                       "taxon.tab":597053, "time_elapsed":{"sec":6675.69, "min":111.26, "hr":1.85}}


Groups of 3 now:
Below also implemented the adding of another text object from <title> form eml.xml from DwCA. This as expected exactly doubles the media count.
TreatmentBank	Wed 2022-06-15 03:51:07 AM	            {"media.tab":1192522,                    "taxon.tab":597053, "time_elapsed":{"sec":76459.03, "min":1274.32, "hr":21.24}}

*Below are erroneous - not unique traits caused by <title> inclusion
TreatmentBank_ENV	Thu 2022-06-16 12:49:52 AM	        {"MoF.tab":2193559, "occur.tab":2193559, "taxon.tab":597053, "time_elapsed":{"sec":75516.92, "min":1258.62, "hr":20.98}}
Below implemented adjustments: removal of taxa, then MoF and occurrence accordingly
TreatmentBank_adjustment_01	Thu 2022-06-16 02:23:32 AM	{"MoF.tab":2193511, "occur.tab":2193511, "taxon.tab":597036, "time_elapsed":{"sec":1549.79, "min":25.83, "hr":0.43}}

*Below start of correct - no more duplicates:
TreatmentBank_ENV	Fri 2022-06-17 01:05:59 AM	        {"MoF.tab":2007609, "occur.tab":2007609, "taxon.tab":597053, "time_elapsed":{"sec":50351.17, "min":839.19, "hr":13.99}}
TreatmentBank_adjustment_01	Fri 2022-06-17 03:40:54 AM	{"MoF.tab":2007566, "occur.tab":2007566, "taxon.tab":597036, "time_elapsed":{"sec":1421.39, "min":23.69, "hr":0.39}}

TreatmentBank_ENV	        Tue 2022-09-20 06:52:12 PM	{"MoF.tab":1865825, "occur.tab":1865825, "taxon.tab":597053, "time_elapsed":{"sec":62465.11, "min":1041.09, "hr":17.35}}
TreatmentBank_adjustment_01	Tue 2022-09-20 08:24:35 PM	{"MoF.tab":1865787, "occur.tab":1865787, "taxon.tab":597036, "time_elapsed":{"sec":1381.3, "min":23.02, "hr":0.38}}

TreatmentBank_ENV	Wed 2022-10-26 07:10:14 PM	        {"MoF.tab":1865825, "occur.tab":1865825, "taxon.tab":597053, "time_elapsed":{"sec":33205.82, "min":553.43, "hr":9.22}}
TreatmentBank_adjustment_01	Wed 2022-10-26 08:07:19 PM	{"MoF.tab":1865787, "occur.tab":1865787, "taxon.tab":597036, "time_elapsed":{"sec":1527.32, "min":25.46, "hr":0.42}}

Below start of getting only 'en' English descriptions:
TreatmentBank	Thu 2022-11-03 07:00:50 AM	{"media_resource.tab":1135562, "taxon.tab":597053, "time_elapsed":{"sec":77206, "min":1286.77, "hr":21.45}}

Below start of strict URI; only those found in EOL Terms file are allowed:
TreatmentBank_ENV	Wed 2022-11-16 06:41:27 AM	        {"MoF.tab":1679030, "occur.tab":1679030, "taxon.tab":597053, "time_elapsed":{"sec":13429.07, "min":223.82, "hr":3.73}}
TreatmentBank_adjustment_01	Wed 2022-11-16 07:04:20 AM	{"MoF.tab":1678993, "occur.tab":1678993, "taxon.tab":597036, "time_elapsed":{"sec":1196.84, "min":19.95, "hr":0.33}}

*** Last step: TreatmentBank_adjustment_01.tar.gz is renamed to TreatmentBank_final.tar.gz

You uploaded: TreatmentBank.tar.gz
This archive is Valid
Statistics
http://rs.tdwg.org/dwc/terms/taxon:
    Total: 597053
http://eol.org/schema/media/document:
    Total by type:
        http://purl.org/dc/dcmitype/Text: 1192522
    Total by license:
        Public Domain: 1192522
    Total by subject:
        http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses: 1192522
    Total by language:
        en: 1135562
        de: 37070
        es: 794
        fr: 16482
        it: 1712
        pt: 892
        nl: 10
    Total by format:
        text/html: 1192522
    Total: 1192522

*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/TreatmentBankAPI');
$GLOBALS["ENV_DEBUG"] = true; //false;
$timestart = time_elapsed();

/* test only
// $citation = "Beirne BP. The male genitalia of the British Stigmellidae (Nepticulidae) (Lep.). Proceedings of the Royal Irish Academy Section B 50: 191-218. doi: http://www.jstor.org/pss/20490833. (1945).";
// $citation = "Borkowski A. Studien an Nepticuliden (Lepidoptera). Teil VI. Die Verbreitung der Nepticuliden in Polen. Polskie Pismo Entomologiczne 45: 487-535, 410 pls. (1975).";
// $citation = "Borkowski A. Studien an Stigmelliden (Lepidoptera). Teil II. Fedalmia thymi sp. n.: eine neue Art aus Mitteleuropa. Polskie Pismo Entomologiczne 40: 69-78. (1970).";
// $citation = "Borkowski A. Studien an Nepticuliden (Lepidoptera). Teil V. Die europäischen Arten der Gattung Nepticula Heyden von Eichen. Polskie Pismo Entomologiczne 42: 767-799. (1972).";
$citation = "Bourquin F. Microlepidopteros nuevos con sus biologias. Revista de la Sociedad Entomológica Argentina 23: 31-46. (1961).";
// $citation = "Frey H, Boll J. Tineen aus Texas. Stettiner Entomologische Zeitung 39: 249-280. (1878).";
// $citation = "Frey H. A new Nepticula. Entomologist's Weekly Intelligencer 4: 14. (1858).";
// $citation = "Chrétien P. Contribution à la connaissance des Lépidoptères du Nord de l'Afrique. Annales dela Société Entomologique de France 84: 289-374, figs. 281-211. (1915).";
// $citation = "Chrétien P. Lés Lépidoptères du Maroc. Galleriinae - Micropterygidae. In: Oberthür C (Ed) Études de Lépidoptérologie comparée. Oberthür, Rennes, 324-379. (1922).";
// $citation = "ABDALA C. S., R. V. SEMHAN, D. L. MORENO AZOCAR, M. BONINO, M. M. PAZ & F. CRUZ. (2012). Taxonomic study and morphology based phylogeny of the patagonic clade Liolaemus melanops group (Iguania: Liolaemidae), with the description of three new taxa. Zootaxa 3163: 1–32.";
// $citation = "ABDALA, CRISTIAN SIMÓN; DIEGO ESTEBAN PROCOPIO, OSCAR ANÍBAL STELLATELLI, ALEJANDRO TRAVAINI, ALEJANDRO RODRÍGUEZ & MARIO RICARDO RUIZ MONACHESI. (2014). New Patagonian species of Liolaemus (Iguania: Liolaemidae) and novelty in the lepidosis of the southernmost lizard of the world: Liolaemus magellanicus. Zootaxa 3866 (4): 526–542.";

$citation = str_ireplace("de la", "dela", $citation); //will undo below
$len = strlen($citation); $needles = array(); $str = "";
for ($i = 0; $i <= $len; $i++) {
    $char = substr($citation, $i, 1);
    if(is_numeric($char)) continue;
    if($char == "." || $char == "_" || $char == ",") {
        $str = trim($str);
        $count = str_word_count($str);
        $pattern = '/\pL+/u';
        $pattern = "/[\pL']+/u";

        if(stripos($str, "(Ed)") !== false || stripos($str, "eds.") !== false) { //string is found
            $needles[-2][] = $str;
            $str = ""; //works even without this line
            continue;
        }

        $count = preg_match_all($pattern, $str, $matches);
        if(ctype_upper(@$str[0])) $needles[$count][] = $str;
        else                      $needles[-1][] = $str;
        $str = "";
    }
    else $str .= $char;
}
krsort($needles); print_r($needles);
$tmp_arr = array_shift($needles);
$title = array(array_shift($tmp_arr)); //get the first element of array using array_shift()
$title = str_ireplace("dela", "de la", $title); //undo what was done above
$final['title'] = $title;
print_r($final); exit("\n[$citation]\nstop 173\n");
*/



/* Important function for Pensoft. Used as retrieve_annotation() in Pensoft2EOLAPI.php
$orig_batch_length = 5; //4;
$batch_length = $orig_batch_length;
$desc = "-12345- -678910- -1112131415- -====================================================================- -1617181920- -2122- -2324- -252627- -28- -2930-";
$len = strlen($desc);
$loops = $len/$batch_length; echo("\nloops: [$loops]\n");
$loops = ceil($loops);
$ctr = 0;
// sleep(0.5);
for($loop = 1; $loop <= $loops; $loop++) { //echo "\n[$loop of $loops]";
    // block check start
    $i = 100;
    $new_b_l = $batch_length;
    for($x = 1; $x <= $i; $x++) {
        $char_ahead = substr($desc, $ctr+$new_b_l, 1); //print("\nchar_ahead: [$char_ahead]");
        if($char_ahead == " " || $char_ahead == "") {
            $batch_length = $new_b_l;
            $str = substr($desc, $ctr, $batch_length);
            break;
        }
        $new_b_l++;
    }
    // block check end

    $str = utf8_encode($str);
    // self::retrieve_partial($id, $str, $loop);
    $ctr = $ctr + $batch_length;
    echo "\nbatch $loop: [$str][$ctr][$batch_length]\n";
    $batch_length = $orig_batch_length;
}
exit("\n-end test-\n");
*/

/* test only
$orig_str = "Beirne BP. The male genitalia of the British Stigmellidae (Nepticulidae) (Lep.). Proceedings of the Royal Irish Academy Section B 50: 191-218. doi: http://www.jstor.org/pss/20490833. (1945).";
$str = $orig_str;
if(preg_match_all("/\((.*?)\)/ims", $str, $arr)) {
    print_r($arr[1]);
    $i = -1;
    $words = array();
    foreach($arr[1] as $s) {
        if(stripos($s, ".") !== false) { //string is found
            $i++;
            $temp = str_replace(".", " _my_period_ ", $s);
            $str = str_replace("($s)", "($temp)", $str);
        }
    }
    echo "\norig: [$orig_str]\n";
    echo "\n new: [$str]\n"; //return $str
}
exit("\n-end test-\n");
*/

/* test only
$matches = array();
preg_match_all('/([0-9]+)/', "Pseudosphingonotus savignyi (Saussure 1884) Schumakov 1963", $matches);
print_r($matches[1]);
exit("\n-end test-\n");
*/

print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
// print_r($param); exit;
/*Array(
    [range_from] => 1
    [range_to] => 100000
)*/
$from = @$param['range_from'];
$to = @$param['range_to'];
$task = @$param['task'];

if($from && $to) {
    $func = new TreatmentBankAPI();
    $func->start($from, $to); //initial operation - downloads all Plazi DwCA's locally
}
elseif($task == "build_up_dwca_list") {
    $func = new TreatmentBankAPI();
    $func->build_up_dwca_list();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
elseif($task == "generate_single_dwca") {
    require_library('connectors/DwCA_Aggregator_Functions');
    require_library('connectors/DwCA_Aggregator');
    $resource_id = "TreatmentBank";
    $func = new DwCA_Aggregator($resource_id, false, 'regular');
    $func->combine_Plazi_Treatment_DwCAs();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>