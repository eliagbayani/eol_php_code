<?php
namespace php_active_record;
/* TRAM-808: Map EOL IDs for Dynamic Hierarchy Version 1.1.
Statistics
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DH_v1_1_Mapping_EOL_IDs');
ini_set('memory_limit','7096M'); //this is imperative, unless you want to change strategy for a one-time-run script.
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main
$resource_id = "DH_v1_1_Map_EOL_IDs";
$func = new DH_v1_1_Mapping_EOL_IDs($resource_id);

/* tests only
$source_str = "trunk:26c58c2e-b902-4cd8-8596-9c7796338c98,ictv:ICTV:Viruses";
$arr = $func->get_all_source_identifiers($source_str); print_r($arr);
$arr = $func->get_all_sources($source_str); print_r($arr);
exit("\n-end test-\n");
*/

/* main operations
$func->create_append_text(); exit("\n-end create_append_text-\n");//done only once; worked OK
*/
/*
$func->step_1(); //1. Match EOLid based on source identifiers --> generates [new_DH_after_step1.txt] [old_DH_after_step1.txt]
$func->before_step_2_or_3("new_DH_after_step1", "step 1"); //fix prob. described in an email to Katja //--> uses [new_DH_after_step1.txt]
                                                                                                      //--> generates [new_DH_before_step2.txt]
*/
/* these two can run one after the other (2.42 hours)
$func->step_2(); //2. Match EOLid based on full scientificName strings & rank //--> uses [new_DH_before_step2.txt] [old_DH_after_step1.txt] 
                                                                              //--> generates [new_DH_after_step2.txt] [old_DH_after_step2.txt]
$func->before_step_2_or_3("new_DH_after_step2", "step 2"); //--> uses [new_DH_after_step2.txt]
                                                           //--> generates [new_DH_before_step3.txt]
*/

/*                                    //--> uses [new_DH_before_step3.txt]
                                         //--> generates [new_DH_multiple_match_fixed.txt]
$func->fix_multiple_matches_after_step2('new_DH_before_step3'); //https://eol-jira.bibalex.org/browse/TRAM-808?focusedCommentId=63460&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63460
                                        //--> generates [new_DH_multiple_match_fixed.txt]
*/

/* REMINDER: if there is memory issue, end with pre_step_3(). And resume with step_3(). WORKED OK */

/* these two can run one after the other (2.08 hours)
// $func->pre_step_3(); //fix 'multiple' match   //--> uses [old_DH_after_step2.txt]
                                              //--> generates [] [old_DH_gnparsed.txt]
$func->step_3(); //main step 3  //--> uses [new_DH_multiple_match_fixed.txt] [old_DH_gnparsed_tbl] [old_DH_gnparsed.txt]
                                //--> generates [new_DH_after_step3] [old_DH_after_step3]
                                // 3. Match EOLid based on canonical form strings & rank
*/
/*
$func->before_step_2_or_3("new_DH_after_step3", "step 3"); //--> uses [new_DH_after_step3.txt]
                                                           //--> generates [new_DH_before_step4.txt]
$taxa_file = "/Volumes/AKiTiO4/d_w_h/TRAM-808/new_DH_before_step4.txt";
run_diagnostics(false, $taxa_file);
*/


/* important utility: check ancestry integrity of old DH
$taxa_file = "/Volumes/AKiTiO4/d_w_h/TRAM-808/eoldynamichierarchywithlandmarks/taxa.txt";
run_diagnostics(false, $taxa_file);
*/

/* Here is the final clean-up for the EOLids. https://eol-jira.bibalex.org/browse/TRAM-808?focusedCommentId=63479&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63479
$func->final_clean_up_for_EOLids(1);
        //--> uses
        // $this->main_path."/new_DH_before_step4.txt"
        //--> generates
        // $this->main_path."/new_DH_cleaned_up.txt"
*/
/*
$taxa_file = "/Volumes/AKiTiO4/d_w_h/TRAM-808/new_DH_cleaned_up.txt";
run_diagnostics(false, $taxa_file);
as of May 25, 2019:
OK: All parents in taxon.tab have entries.
OK: All acceptedNameUsageID have entries.
*/

/* hopefully final clean-up for the EOLids. https://eol-jira.bibalex.org/browse/TRAM-808?focusedCommentId=63482&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63482
$func->final_clean_up_for_EOLids(2);
        //--> uses
        // $this->main_path."/new_DH_cleaned_up.txt"
        //--> generates
        // $this->main_path."/new_DH_cleaned_up_v2.txt"
*/
/*
$taxa_file = "/Volumes/AKiTiO4/d_w_h/TRAM-808/new_DH_cleaned_up_v2.txt";
run_diagnostics(false, $taxa_file); exit;
// as of May 27, 2019:
// OK: All parents in taxon.tab have entries. OK
// OK: All acceptedNameUsageID have entries. OK
*/

/*
// $func->step_4(); //4. Create a special report for known homonyms
// $func->step4_2();
$func->step4_3(); exit;
*/

// /* last step: as of May 28, 2019 there is no more duplicate EOLid's OKAY!!!
$func->last_report(); //check for duplicate EOLid's
// */

/* BELOW HERE WAS NEVER USED */
// exit("\n-end for now-\n");
// $func->generate_dwca($resource_id); //use taxonomy_4dwca.txt from Step 5.
// unset($func);
// Functions::finalize_dwca_resource($resource_id, true, false);
// run_diagnostics($resource_id);

//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
// /*
Function run_diagnostics($resource_id, $taxa_file = false) // utility - takes time for this resource but very helpful to catch if all parents have entries.
{
    if($taxa_file) echo "\nRunning diagnostics [$taxa_file]:\n";
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id); //takes time

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, $taxa_file); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, $taxa_file, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
// */
?>