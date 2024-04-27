<?php
namespace php_active_record;
/*
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/DataHub_INAT_API');
$GLOBALS['ENV_DEBUG'] = true;
$timestart = time_elapsed();
$func = new DataHub_INAT_API();

$func->get_iNat_taxa('genus');

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "\n elapsed time = " . $elapsed_time_sec/60 . " minutes";
echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hours";
echo "\n Done processing.\n";
?>