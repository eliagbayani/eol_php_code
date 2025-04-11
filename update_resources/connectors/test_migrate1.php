<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/Migrate1_API');

$timestart = time_elapsed();
$resource_id = 'migrate1';

$func = new Migrate1_API($resource_id);
$func->generate_archive();
Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means to delete the dwca folder.

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n";
echo "elapsed time = $elapsed_time_sec seconds             \n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes  \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\n\n Done processing.";
?>
