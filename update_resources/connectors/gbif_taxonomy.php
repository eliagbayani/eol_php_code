<?php
namespace php_active_record;
/* This is about ways to access the GBIF taxonomy */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$GLOBALS['ENV_DEBUG'] = false; //true;

print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
// print_r($param); exit;

require_library('connectors/GBIFTaxonomyAPI');
$func = new GBIFTaxonomyAPI();

/* un-comment in real operation -- works OK
// $id = '11592253'; //Squamata --- invalid
// $id = 2433451; //Ursus maritimus -- valid
// $id = 2433406; //Ursus -- invalid
// $id = 3086525; //Rhizophora -- valid
$id = 2433737; //Lontra felina -- valid
// $id = 5307; //Mustelidae -- invalid
// $id = 2433726; //Lontra -- invalid
// $id = 8084280; //Gadus morhua -- valid
// $id = 4308837; //species Insecta -- invalid
// $id = 8474910; //Archaeozostera aungustifolia -- valid
if($func->is_id_valid_waterbody_taxon($id)) echo "\nValid\n";
else echo "\nInvalid\n";
*/

// /* a local-only utility that generates this report/stats: https://github.com/EOL/ContentImport/issues/24#issuecomment-2615627318
$func->long_list_vs_continent_checklists(); //can only be run locally
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>