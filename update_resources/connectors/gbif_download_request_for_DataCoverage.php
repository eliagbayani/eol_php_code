<?php
namespace php_active_record;
/* This is a library that handles GBIF download requests using their API 
Copied template from original: gbif_download_request_for_NMNH.php
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFdownloadRequestAPI');
$timestart = time_elapsed();
/*

This will overwrite any current download request. Run this once ONLY every harvest per taxon group.
php update_resources/connectors/gbif_download_request_for_iNat.php _ '{"task":"send_download_request", "taxon":"Data_coverage"}'

This will generate the .sh file if download is ready. The .sh file is the curl command to download.
php update_resources/connectors/gbif_download_request_for_iNat.php _ '{"task":"generate_sh_file", "taxon":"Data_coverage"}'

                                This comes from NMNH_images template but does nothing, so I won't apply it here in Data_coverage
                                gbif_download_request_for_iNat.php _ '{"task":"start_download", "taxon":"Data_coverage"}'

This will check if all downloads are ready
php update_resources/connectors/gbif_download_request_for_iNat.php _ '{"task":"check_if_all_downloads_are_ready_YN"}'

Sample of run_Data_coverage.sh:
#!/bin/sh
curl -L -o 'Data_coverage_DwCA.zip' https://api.gbif.org/v1/occurrence/download/request/0164955-210914110416597.zip

.sh files are run in Jenkins eol-archive:
bash /var/www/html/eol_php_code/update_resources/connectors/files/GBIF/run_Data_coverage.sh
*/
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$fields = json_decode($params['json'], true);
$task = $fields['task'];
$taxon = @$fields['taxon'];
$download_key = @$fields['download_key'];

//############################################################ start main
$resource_id = "Data_coverage";
$func = new GBIFdownloadRequestAPI($resource_id);
if($task == 'send_download_request') $func->send_download_request($taxon);
if($task == 'generate_sh_file') $func->generate_sh_file($taxon);
if($task == 'check_if_all_downloads_are_ready_YN') {
    if($func->check_if_all_downloads_are_ready_YN($download_key)) {
        echo "\nAll downloads are now ready. OK to proceed.\n";
        exit(0); //jenkins success
    }
    else {
        echo "\nNOT all downloads are ready yet. Cannot proceed!\n\n";
        exit(1); //jenkins fail
    }
}
//############################################################ end main

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";
?>