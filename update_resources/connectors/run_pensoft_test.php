<?php
namespace php_active_record;
/* this is a utility to test Pensoft annotation */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$GLOBALS['ENV_DEBUG'] = true;

$timestart = time_elapsed();

require_library('connectors/Functions_Pensoft');
require_library('connectors/Pensoft2EOLAPI');

$param = array("task" => "generate_eol_tags_pensoft", "resource" => "all_BHL", "resource_id" => "TreatmentBank", "subjects" => "Uses", "ontologies" => "envo,eol-geonames");

$func = new Pensoft2EOLAPI($param);

/* independent test: Nov 27, 2023 --- separate sections of Treatment text
$str = file_get_contents(DOC_ROOT."/tmp2/sample_treatment.txt");
$ret = $func->format_TreatmentBank_desc($str);
echo "\n[$ret]\n";
exit("\n--end test--\n");
*/

/* option 1 works, but it skips a lot of steps that is needed in real-world connector run.
$json = $func->run_partial($desc);
$arr = json_decode($json); print_r($arr);
*/

/* option 2 --- didn't get to work
$basename = "ile_-_173"."ice";
$desc = strip_tags($desc);
$desc = trim(Functions::remove_whitespace($desc));
// $func->results = array();
$arr = $func->retrieve_annotation($basename, $desc); //it is in this routine where the pensoft annotator is called/run
// $arr = json_decode($json); 
print_r($arr);
*/

/*
$sciname = "Gadur morhuaspp.";
if(Functions::valid_sciname_for_traits($sciname)) exit("\n[$sciname] valid\n");
else                                              exit("\n[$sciname] invalid\n");
*/

// /* option 3 from AntWebAPI.php --- worked OK!
// /* This is used for accessing Pensoft annotator to get ENVO URI given habitat string.
$descs = array();
$descs[] = "(crops: peanuts, rice, sugarcane); (littoral: dune); (nest/prey: mud dauber nest [f]); (orchard: grapefruit, orange); (plants: bluebonnets, Indian paintbrush, miscellaneous vegetation, vegetation, next to cotton field); (soil/woodland: saltcedar)";
$descs[] = "SAIAB 60874  , 19  (of 23) specimens, SL 6.6–9.8 cm, Mozambique: Zambezi System : Zambezi River: island bank off the Marromeu harbour, 18 ◦ 17 ′ 08.63 ′′ S, 35 ";

$descs[] = "female ( JRUC); same data, 1600 m, 20.ix.1971, 1 male, 2 females ( ZMAS); Nuristan province, Paprok [ca. 35°33'N 71°17'E], 2000 m, 25.ix.1971, O.N. Kabakov leg., 2 males ( ZMAS); Nuristan province, NWaygal [ca. 35°12'N 70°58'E], 3500 m, 2.vii.1972, O.N. Kabakov leg., 1 male, 2 females ( ZMAS), 1 male ( JRUC); NWaygal [ca. 35°12'N 70°59'E], 2700 m, 6.vii.1972, O.N. Kabakov leg., 2 males ( ZMAS); same data, 7.vii.1972, 2 females ( ZMAS); Pakistan: Azad Jammu and Kashmir, Muzaffarabad env., top of Leepa valley [ca. 34°20'N, 73°55'E], 3200–3300 m, 14.vi.1997, Heinz leg., 2 males, 3 females ( SMNS); Dir [= Khyber Pakhtunkhwa province], Gujar Levy Post env., Lawarai pass [ca. 35°21'N, 71°48'E], 2800–3100 m, 5.–7.vii.1997, Heinz leg., 1 female ( SMNS); Gilgit-Baltistan, Nanga Parbat Mt., Rama env. [ca. 35°20'N, 74°48'E], 3000–3500 m, 27.–30.vi.1997, Heinz leg., 1 male, 4 females ( SMNS); Northern Areas [= Gilgit-Baltistan], Gilgit district, Bagrot Valley, 36°02'32.6''N, 74°34'8.3''E, 2600 m, 250 m from Hinarki Glacier snout, pitfall trap, 25.x.–2.xi.2008, L. Latella leg., 2 males, 1 female ( MCSV); Northern Areas [= Gilgit-Baltistan], Gilgit district, Kargah Valley, 35°54'45.8''N, 74°15'26.9''E, 1611 m,  Myagdi Khola valley,  (river) Kali Gandaki valley, upper Lete [ca. 28°37'N, 83°38'E], 2900 m, 19.v.2002, J. Schmidt leg., 2 males ( SMNS); Annapurna Mts., South Himal, Dhasia Khola  ( SMNS); Mustang district, Dhaulagiri, SE slope, SW slope of Lete pass [ca. 28°24'N, 83°41'E], ], 4300–4500 m, 13.–16.vi.2000, Expedition I. Ghalé, S. Tamang, R. Santa & S. Gurung, 2 females ( SMNS);";
// $descs[] = "Myślenice distr. , Osieczany, Stobiecki, 1 ex ( ISEA) ; Nowy S cz distr., Rytro­Radziejowa, Stobiecki, 4 exx ( ISEA) ; Nowy S cz distr., Stobiecki, 12 exx ( ISEA) ; Przemyśl, Kotula , 5 exx ( ISEA) ; Przemyśl, Trella , 31 exx ( ISEA) ; Przeworsk distr. , Rocibórz, Stobiecki, 1 ex ( ISEA) ; Racibórz , 1903, H. Nowotny, 2 exx ( USMB) ; Rzeszów distr. , Czudec, Stobiecki, 2 exx ( ISEA) ; Tarnów, Stobiecki , 1 ex ( ISEA) ; Warszawa­ Bielany , 23.V.1900, 6 exx ( USMB) ; 18.V.1901, 2 exx ( USMB) ; 21.IV.1902, 5 exx ( USMB) ; Warszawa­ Saska Kępa , 13.IX.1891, 1 ex ( USMB) ; 2.X.1901, 1 ex ( USMB) ; Warszawa­ Ṡwider , 2.VI.1900, 1 ex ( USMB) ; 3.VI.1902, 3 exx ( USMB) ; 20.VI.1904, 1 ex ( USMB) ; 9.VII.1904, 1 ex ( USMB) ; Warszawa­Zegrze , 1.VI.1933, Tenenbaum, 1 ex ( FMNH) ; Wrocław, Letzner , 1 ex ( DEI) . Romania: Cornutel , 6 exx ( NMW) ; Galatz, Letzner , 2 exx ( DEI) ; Sinaia Prahova­Tal , 900–1600 m, 11–17.VIII.1982 ,M. Schülke, 5 exx ( ZMHB). Russia: Poltava , VIII.1957, 1 ex ( MNHN) . Slovakia: Košice, 1924, Schüvalley";

$descs[] = "Romania: Cornutel , 6 exx ( NMW) ; Galatz, Letzner , 2 exx ( DEI) ; Sinaia Prahova­Tal , 900–1600 m, 11–17.VIII.1982 , Schülke, 5 exx ( ZMHB). Russia: Poltava , VIII.1957, 1 ex ( MNHN) . Slovakia: Košice, 1924, Schüvalley";

$descs[] = "sоmetimes аррeаring соmрletelу brоwn if lightсоlоured with соmрlete mediаn саrinа аnd unifоrmlу meshlike соriасeоus tо аlutасeоus.";
$descs[] = " ; Mutìnice, Myślenice distr.  Slovakia: Košice, 1924";
$descs[] = "usuаllу with соmрlete mediаn саrinа hаving а соmрletelу sсulрtured sсrоbаl а соmрlete mediаn саrinа";
$descs[] = "Male genitalia: DISTINCTLY SHApED ( FIgS. 2E, f  ). TEgmEN ( FIg. 2E ) RATHER WIDE, WIDEST AT mIDDLE, mEDIAL DISTAL EXCISION DEEp, V-SHApED (RATIO DTIN/LETE = 0.32–0.33), INNER mARgINS WITHOUT ANY pROJECTION; RATIO LETE/WITE = 1.19–1.20. RATIO THLE/LETE = 0.21. MEDIAN LObE Of AEDEAgUS mODERATELY ELONgATE, RATIO LEAE/WIAE = 1.90– 1.95, EXHIbITINg mAXImUm WIDTH NEARLY AT DISTAL THIRD, WITH NARROWLY AND ObTUSELY SpATULATE ApEX ( FIg. 2f  ). MAIN SCLERITES Of INTERNAL SAC (ENDOpHALLUS) LONg AND ROD-SHApED IN bOTH DORSAL AND LATERAL vIEW.";
$descs[] = "We wish to thank A.I. Golykov and B.I. Sirenko of the Institute of Zoology, Russian Academy of Science, for the planktonic material upon which this study was partially based and for the environmental data presented in station data herein. Also, we appreciate the comments and criticism of the manuscript by reviewers, especially by M.V. Angel and R. Matzke-Karasz, which helped us a lot. Beside, we wish to thank G.G. Stovbun (A.V. Zhirmunsky Institute of Marine Biology, Far East Branch of Russian Academy of Science, Vladivostok, Russia) for technical preparation of manuscript.";
$descs[] = "Spain, Santander, Santillana del Mar, Cueva de Altamira. in the valley of the dead found in Philippines.";
$descs[] = 'scientificNameAuthorship: Théel, 1882; ';
$descs[] = "linn city house cliff pass ice mud transportation railroad cline biofilm sediment";
$descs[] = "mesa laguna rapids ocean sea organ field well adhesive quarry reservoir umbrella plantation bar planktonic material";
$descs[] = "Almost all of these are incorrect: e.g., (1) ‘‘fen. ov.’’ (fenestra ovalis, = f. vestibuli)";
$descs[] = "Atlantic blanket bogs and fen";
$descs[] = "I live in the mountains over the nunatak valley.";
$descs[] = "I live in a sandy soil";
$descs[] = "Distribution. Sri Lanka.";
// $descs[] = "Notes. In Poorani’s (2002) checklist of the Indian Subcontinent, Brumus ceylonicus was listed with a note that ‘ it might be a Brumoides ’. 
// Images of the two syntypes of ‘ Brumus ceylonicus ’ deposited at SDEI (obtained through the courtesy of Kevin Weissing, SDEI) below the valley show that this species 
// indeed is a Brumoides and it is transferred here to Brumoides (comb. n.). The male syntype (abdomen and genitalia dissected and glued to a card) is 
// hereby designated as a lectotype to ensure stability of nomenclature (lectotype designation). 
// This is likely to be a synonym of either B. suturalis or B. lineatus, both of which are found in South India. 
// The male genitalia could not be examined in detail for confirmation. yz";

$descs[] = "below the valley show that this species indeed is a Brumoides and it is transferred here to Brumoides (comb. n.). This is likely to be a synonym of either B. Suturalis or B. Lineatus, both of which are found in south India";


$descs[] = "the quick References: valley in the north.";
$descs[] = "I drive to the forest, just around the woodland trees.";
$descs[] = "I drive to the savanna, just around the grassland.";
$descs[] = "I work in the Marine Institue of Technology. This is a marine species.";
$descs[] = "I work in arete ria belong to the organic material inside moor around harbor";
$descs[] = "(Fenďa and Lukáš 2014). I visited the United States National Museum last year.";
$descs[] = "I like to live in a plateau."; //test for WoRMS
$descs[] = "Description. See Crosnier and Forest (1966) for description and figures of eastern Atlantic specimens."; 
$descs[] = "Lives in the forest of Brazil"; 
$descs[] = "Zhang C, Stadler T, Klopfstein S, Heath TA, Ronquist F. 2016.";
$descs[] = "Zhang, Stadler, Klopfstein, Heath, Ronquist 2016.";
$descs[] = "Zhang, Stadler, Klopfstein, uncultivated heath, Ronquist 2016."; //[heath-ENVO_00000107]

$descs[] = "This is likely to be a synonym of either B. suturalis or B. lineatus, both of which are found in south India";
$descs[] = "This is likely to be a synonym of either B Suturalis or B Lineatus, both of which are found in south India";

$descs[] = "Type: materials_examined	Holotype. AMS I. 19426 - 001, 414 mm, female, off Malabar "; //[malabar-Malabar]
$descs[] = "It differs significantly from all but A. malabar in having more total vertebrae (160 vs. 131 – 145)"; //[]
$descs[] = "(1836: 83) and on disturbed ground such as roadsides. 0 – 620 m elevation. Conservation Status: — C. antarctica is classed as Least Concern as it is widespread with stable populations";
$descs[] = "A very cold place is Antarctica in the North Pole";
$descs[] = "It is found in Ukraine in western marsh harrier Circus aeruginosus 1";

$descs[] = "This is just the tip of the iceberg.";
$descs[] = "This is just the tip x of the Iceberg.";
$descs[] = "This is just the tip x of the iceberg.";

$descs[] = "around the wall of the alimentary canal in segment chambers of the head region";
$descs[] = "that Canal stretches a mile long.";
$descs[] = "that canal stretches a mile long."; //canal-ENVO_00000014

$descs[] = "transferred from USNM, used for SEM), Magic Mountain";
$descs[] = "transferred from USNM, used for SEM), Magic mountain";

/*
$descs = array();
// $descs[] = file_get_contents(DOC_ROOT."/tmp2/sample_treatment.txt");
*/

$final = array();
$IDs = array('24', '617_ENV', 'TreatmentBank_ENV', '26_ENV'); //normal operation --- 617_ENV -> Wikipedia EN //24 -> AntWeb resource ID
// $IDs = array('24');                                      //dev only
// $IDs = array('TreatmentBank_ENV'); //or TreatmentBank    //dev only
// $IDs = array('617_ENV'); //or Wikipedia EN               //dev only

foreach($IDs as $resource_id) {
    $param['resource_id'] = $resource_id;
    require_library('connectors/Functions_Pensoft');
    require_library('connectors/Pensoft2EOLAPI');
    $pensoft = new Pensoft2EOLAPI($param);
    $pensoft->initialize_remaps_deletions_adjustments();
    // /* to test if these 4 variables are populated.
    // echo "\n From Pensoft Annotator:";
    // echo("\n remapped_terms: "              .count($pensoft->remapped_terms)."");
    // echo("\n mRemarks: "                    .count($pensoft->mRemarks)."");
    // echo("\n delete_MoF_with_these_labels: ".count($pensoft->delete_MoF_with_these_labels)."");
    // echo("\n delete_MoF_with_these_uris: "  .count($pensoft->delete_MoF_with_these_uris)."\n");
    // ************************************
    $i = 0; $errors = 0;
    foreach($descs as $desc) { $i++;
        $ret = run_desc($desc, $pensoft);
        echo "\n[$resource_id $i] - "; echo("[$desc] [$ret]");
        // $i = 9; //force-assign
        if($resource_id == '24') {            
            if($i == 1) {$s = "woodland-ENVO_01000175|orchard-ENVO_00000115|dune-ENVO_00000170"; if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 2) {$s = "mozambique-1036973|zambezi-Zambezi";     if($ret == $s)                          echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 3) {$s = "pakistan-1168579|valley-ENVO_00000100"; if($ret == $s)             echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 4) {$s = "slovakia-3057568|romania-798549|russia-2017370"; if($ret == $s)                  echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 5) {$s = "";                                       if($ret == $s)                          echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 6) {$s = "slovakia-3057568";                       if($ret == $s)                          echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 7) {$s = "";                                       if($ret == $s)                          echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 8) {$s = "";                   if($ret == $s)                                              echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 9) {$s = "russia-2017370";     if($ret == $s)                                              echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 10) {$s = "philippines-1694008|valley-ENVO_00000100|spain-2510769"; if($ret == $s)         echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 11) {$s = "";                  if($ret == $s)                                              echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 12) {$s = "";                  if($ret == $s)  echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 13) {$s = "";                  if($ret == $s)  echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 14) {$s = "";                  if($ret == $s)  echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 15) {$s = "fen-ENVO_00000232"; if($ret == $s)  echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 16) {$s = "mountains-ENVO_00000081|nunatak-ENVO_00000181|valley-ENVO_00000100"; if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 17) {$s = "sandy soil-ENVO_00002229";  if($ret == $s)                      echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 18) {$s = "Sri Lanka-1227603";         if($ret == $s)                      echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 19) {$s = "valley-ENVO_00000100"; if($ret == $s)             echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 20) {$s = "valley-ENVO_00000100";      if($ret == $s)                      echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 21) {$s = "woodland-ENVO_01000175|forest-ENVO_01000174"; if($ret == $s)    echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 22) {$s = "grassland-ENVO_01000177|savanna-ENVO_01000178"; if($ret == $s)  echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }            
            if($i == 23) {$s = "";                      if($ret == $s)                          echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 24) {$s = "";                      if($ret == $s)                          echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 25) {$s = "";                      if($ret == $s)                          echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
            if($i == 27) {$s = "Eastern Atlantic-9237"; if($ret == $s)                          echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} } //exclude 'Forest'
        }
        if(in_array($resource_id, array('TreatmentBank_ENV', '617_ENV'))) {
            $q = array();
            $q[1] = array('s' => "woodland-ENVO_01000175|orchard-ENVO_00000115|dune-ENVO_00000170");
            $q[2] = array('s' => "mozambique-1036973|zambezi-Zambezi");
            $q[3] = array('s' => "pakistan-1168579|valley-ENVO_00000100");
            $q[4] = array('s' => "slovakia-3057568|romania-798549|russia-2017370");
            $q[5] = array('s' => "");
            $q[6] = array('s' => "slovakia-3057568");
            $q[7] = array('s' => "");
            $q[8] = array('s' => "");
            $q[9] = array('s' => "russia-2017370");
            $q[10] = array('s' => "philippines-1694008|valley-ENVO_00000100|spain-2510769");
            $q[11] = array('s' => "");
            $q[12] = array('s' => "");
            $q[13] = array('s' => "");
            $q[14] = array('s' => "");
            $q[15] = array('s' => "fen-ENVO_00000232");
            $q[16] = array('s' => "mountains-ENVO_00000081|nunatak-ENVO_00000181|valley-ENVO_00000100");
            $q[17] = array('s' => "sandy soil-ENVO_00002229-ENVO_09200008");
            $q[21] = array('s' => "woodland-ENVO_01000175|forest-ENVO_01000174");
            $q[22] = array('s' => "grassland-ENVO_01000177|savanna-ENVO_01000178");
            $q[23] = array('s' => "");
            $q[24] = array('s' => "");
            $q[25] = array('s' => "");
            if($arr = @$q[$i]) {
                if($ret == $arr['s']) echo " -OK-"; 
                else { echo " -ERROR- [$arr[s]]"; $errors++; }
            }
        }
        if($resource_id == '617_ENV') {
            $q = array();
            $q[18] = array('s' => "");
            $q[19] = array('s' => "valley-ENVO_00000100");
            $q[20] = array('s' => "valley-ENVO_00000100");
            $q[27] = array('s' => "");
            if($arr = @$q[$i]) {
                if($ret == $arr['s']) echo " -OK-"; 
                else { echo " -ERROR- [$arr[s]]"; $errors++; }
            }
        }
        if($resource_id == 'TreatmentBank_ENV') {
            $q = array();
            $q[18] = array('s' => "Sri Lanka-1227603");
            $q[19] = array('s' => "valley-ENVO_00000100");
            $q[20] = array('s' => "");
            $q[27] = array('s' => "Eastern Atlantic-9237");
            if($arr = @$q[$i]) {
                if($ret == $arr['s']) echo " -OK-"; 
                else { echo " -ERROR- [$arr[s]]"; $errors++; }
            }
        }
        if($i == 26) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} } //exclude 'plateau' across the board
        if($i == 28) {$s = "forest-ENVO_01000174|brazil-3469034";   if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} } //include 'forest'
        if($i == 29) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 30) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 31) {$s = "heath-ENVO_00000107";                   if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 32) {$s = "india-1269750";                         if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 33) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 34) {$s = "malabar-Malabar";                       if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 35) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 36) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 37) {$s = "antarctica-6255152";                    if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 38) {$s = "ukraine-690791";                        if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }

        if($i == 39) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 40) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 41) {$s = "iceberg-ENVO_00000298";                 if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }

        if($i == 42) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 43) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 44) {$s = "canal-ENVO_00000014";                   if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        
        if($i == 45) {$s = "";                                      if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }
        if($i == 46) {$s = "mountain-ENVO_00000081";                if($ret == $s) echo " -OK-"; else {echo " -ERROR- [$s]"; $errors++;} }

    }
    echo "\nerrors: [$resource_id][$errors errors]";
    $final[] =     "[$resource_id][$errors errors]";
    // ************************************
} //end foreach()
echo "\n"; print_r($final);
echo "\n-end tests-\n";
// */
function run_desc($desc, $pensoft) {
    $basename = md5($desc);
    $desc = strip_tags($desc);
    $desc = trim(Functions::remove_whitespace($desc));
    $pensoft->results = array();
    $final = array();
    if($arr = $pensoft->retrieve_annotation($basename, $desc)) {
        // echo "\n---start---\n";
        // print_r($arr); //--- search ***** in Pensoft2EOLAPI.php
        // echo "\n---end---\n";
        foreach($arr as $uri => $rek) {
            $filename = pathinfo($uri, PATHINFO_FILENAME);
            $tmp = $rek['lbl']."-$filename";
            if($mtype = @$rek['mtype']) $tmp .= "-".pathinfo($mtype, PATHINFO_FILENAME);
            $final[] = $tmp;
        }
    }
    // else echo "\n-No Results-\n";
    return implode("|", $final);    
}
/*
@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Hi Jen,
Regarding this:
https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67731&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67731

1. The annotator correctly picks-up "planktonic material" and correctly assigns the URI "http://purl.obolibrary.org/obo/ENVO_01000063". And MoF captures this correctly.
The weird part is in our EOL Terms file, this URI is assigned to the name "marine upwelling". Please advise what adjustment to do.
2. This doesn't exist anymore in our DwCA MoF.
3. Weird that the annotator picks-up the string "Cueva de Altamira" and assigns the URI http://purl.obolibrary.org/obo/ENVO_00000102. I had to hard-code removal.

@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Hi Jen,
Regarding this one:
https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67732&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67732

forest, woodland: annotator picks it up correctly, and assigns the ontology to "eol-geonames".
http://purl.obolibrary.org/obo/ENVO_01000174
http://purl.obolibrary.org/obo/ENVO_01000175
With URIs respectively.
But we have a rule here that removes any terms from the geographic ontology that include the string /ENVO_
https://eol-jira.bibalex.org/browse/DATA-1877?focusedCommentId=65861&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65861

grassland: has both ontologies (envo and eol-geonames).
http://purl.obolibrary.org/obo/ENVO_01001206
http://purl.obolibrary.org/obo/ENVO_01000177
URIs respectively. That's why also removed.

savanna: has both ontologies (envo and eol-geonames).
http://purl.obolibrary.org/obo/ENVO_00000261
http://purl.obolibrary.org/obo/ENVO_01000178
URIs respectively. That's why also removed.

rainforests, littoral, abyssal, bog: annotator didn't pick it up.

fen: we are getting it correctly in MoF
e.g. http://purl.obolibrary.org/obo/RO_0002303	http://purl.obolibrary.org/obo/ENVO_00000232	source text: "fen"	http://treatment.plazi.org/id/03DC9141FF89F95C520D57A5EC7AF82D
@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
*/
?>