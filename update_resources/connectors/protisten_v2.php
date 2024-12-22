<?php
namespace php_active_record;
/* the new Protisten.de - https://github.com/EOL/ContentImport/issues/20
https://www.protisten.de
https://editors.eol.org/eol_php_code/update_resources/connectors/monitor_dwca_refresh.php?dwca_id=protisten

php update_resources/connectors/protisten_v2.php _ '{"expire_seconds": "1"}'       --- expires now, expires in 1 sec.
php update_resources/connectors/protisten_v2.php _ '{"expire_seconds": "false"}'   --- doesn't expire
php update_resources/connectors/protisten_v2.php _ '{"expire_seconds": "86400"}'   --- 60*60*24    = 1 day   = expires in 86400 seconds | 864000 10 days
php update_resources/connectors/protisten_v2.php _ '{"expire_seconds": "2592000"}' --- 60*60*24*30 = 30 days = expires in 2592000 seconds

php5.6 protisten_v2.php jenkins '{"expire_seconds": "1"}'       #--- expires now, expires in 1 sec.
php5.6 protisten_v2.php jenkins '{"expire_seconds": "false"}'   #--- doesn't expire
php5.6 protisten_v2.php jenkins '{"expire_seconds": "86400"}'   #--- 60*60*24    = 1 day   = expires in 86400 seconds
php5.6 protisten_v2.php jenkins '{"expire_seconds": "2592000"}' #--- 60*60*24*30 = 30 days = expires in 2592000 seconds

e.g. This taxon (Closterium incurvum) has 3 images in DwCA
f4686d1cb464e70572f0d58ec00364c3 d4103073fb6feb109949bc120a73997f https://www.protisten.de/gallery-ARCHIVE/pics/Closterium-incurvum-063-200-2-7054999-HHW_NEW.jpg 
9fb3519c03d86ca5ee6895ff18803596 d4103073fb6feb109949bc120a73997f https://www.protisten.de/gallery-ARCHIVE/pics/Closterium-incurvum-040-100-P9274176-HID-INET800_NEW.jpg 
ffa94aed4fcd6977064ab7280635cdb3 d4103073fb6feb109949bc120a73997f https://www.protisten.de/gallery-ARCHIVE/pics/Closterium-spec-063-200-P8173128-145-grau-RLW-INET800_NEW.jpg 
Correctly listed in its media page:
https://eol.org/pages/920788/media?resource_id=697
BUT only one image is showing.
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ end */
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = "protisten_v2"; //"protisten";

print_r($argv);
$params['jenkins_or_cron'] = @$argv[1]; //not needed here
$param                     = json_decode(@$argv[2], true);
// print_r($param); exit;
/* Array(
    [expire_seconds] => 86400
)*/

// /* un-comment in real operation
require_library('connectors/Protisten_deAPI_V2');
$func = new Protisten_deAPI_V2($resource_id, $param);
$func->start();
Functions::finalize_dwca_resource($resource_id, false, false, $timestart); //3rd param true means to delete working resource folder
// */

/* utility */
require_library('connectors/DWCADiagnoseAPI');
$func = new DWCADiagnoseAPI();
// $func->check_unique_ids($resource_id); //takes time
$undefined = $func->check_if_all_parents_have_entries($resource_id, true); //true means output will write to text file
if($undefined) echo "\nERROR: There is undefined parent(s): ".count($undefined)."\n";
else           echo "\nOK: All parents in taxon.tab have entries.\n";

recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id); // remove working dir

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n elapsed time = " . $elapsed_time_sec . " seconds";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing\n";
?>