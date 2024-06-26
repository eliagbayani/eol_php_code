<?php
namespace php_active_record;
/* TRAM-807: Dynamic Hierarchy Version 1.1. Postprocessing 

Statistics
http://rs.tdwg.org/dwc/terms/taxon: Total: 2329082  --> before post_step_4()
http://rs.tdwg.org/dwc/terms/taxon: Total: 2329015  -- with post_step_4()
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DH_v1_1_postProcessing');
// ini_set('memory_limit','6096M');
// $GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();

//############################################################ start main

$resource_id = "DH_v1_1_postproc";
$func = new DH_v1_1_postProcessing($resource_id);

/* tests only
$flag = "incertae_sedis_inherited";
// $flag = "incertae_sedis";
// $flag = "incertae_sedis,barren";
if($func->there_is_incertae_sedis_in_flag($flag)) echo "\nthere is IS\n";
else echo "\nwalang IS\n";
exit("\n-end test-\n");
*/
/* part of main operation, un-comment in real operation
$func->start_tram_807(); //this creates taxonomy1.txt
$func->step_4pt2_of_9(); //this uses and starts with taxonomy1.txt from prev. step. Creates taxonomy2.txt
*/
// exit("\n-end step4-\n");
$func->post_step_4(); //exit("\n-end post_step_4-\n"); //Uses taxonomy2.txt, generates taxonomy3.txt. To clean up empty containers. If we end up with a lot of containers with only one or a few descendants (<5), we may want to remove those containers too and attach their children directly to the grandparent.
$func->step_5_minting(); //exit("\n-end step5-\n"); //this starts with taxonomy3.txt from prev. step. Creates taxonomy_4dwca.txt
// $func->test2(); exit;

/*
$func->save_all_ids_from_all_hierarchies_2MySQL(); //used source hierarchies. Manually done alone. Generates write2mysql_v2.txt. Table ids_scinames is needed below by generate_dwca().

$ mysql -u root -p --local-infile DWH;
copy table structure only:
mysql> CREATE TABLE ids_scinames LIKE ids_scinames_v1;
to load from txt file:
mysql> load data local infile '/Volumes/AKiTiO4/d_w_h/2019_04/zFiles/write2mysql_v2.txt' into table ids_scinames;

To make a backup of minted_records table
mysql> CREATE TABLE minted_records_bak LIKE minted_records;
mysql> INSERT minted_records_bak SELECT * FROM minted_records;

*/

$func->generate_dwca($resource_id); //use taxonomy_4dwca.txt from Step 5.
unset($func);
Functions::finalize_dwca_resource($resource_id, true, false);
run_diagnostics($resource_id);

/* stats:
counting: [/opt/homebrew/var/www/eol_php_code/applications/content_server/resources/DH_v1_1_postproc/taxon.tab] total: [2338864]
*/


//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
// /*
Function run_diagnostics($resource_id) // utility - takes time for this resource but very helpful to catch if all parents have entries.
{
    require_library('connectors/DWCADiagnoseAPI');
    $func = new DWCADiagnoseAPI();
    // $func->check_unique_ids($resource_id); //takes time

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
    else           echo "\nOK: All parents in taxon.tab have entries.\n";

    $undefined = $func->check_if_all_parents_have_entries($resource_id, true, false, array(), "acceptedNameUsageID"); //true means output will write to text file
    if($undefined) echo "\nERROR: There is undefined acceptedNameUsageID(s): ".count($undefined)."\n";
    else           echo "\nOK: All acceptedNameUsageID have entries.\n";
}
// */
?>