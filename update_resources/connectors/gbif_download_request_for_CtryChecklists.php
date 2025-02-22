<?php
namespace php_active_record;
/* This is a library that handles GBIF download requests using their API 
Copied template from original: gbif_download_request.php

Works with: NationalChecklist_2024.code-workspace
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFdownloadRequestAPI');
$timestart = time_elapsed();
/*
This will overwrite any current download request. Run this once ONLY every harvest per taxon group.
php update_resources/connectors/gbif_download_request_for_CtryChecklists.php _ '{"task":"send_download_request", "taxon":"Country_checklists"}'

This will generate the .sh file if download is ready. The .sh file is the curl command to download.
php update_resources/connectors/gbif_download_request_for_CtryChecklists.php _ '{"task":"generate_sh_file", "taxon":"Country_checklists"}'

This will check if all downloads are ready
php update_resources/connectors/gbif_download_request_for_CtryChecklists.php _ '{"task":"check_if_all_downloads_are_ready_YN"}'

Sample of .sh files:
#!/bin/sh
curl -L -o 'Country_checklists_DwCA.zip' -C - http://api.gbif.org/v1/occurrence/download/request/xxxxxxx-123456789012345.zip                                    

.sh files are run in Jenkins eol-archive:
bash /var/www/html/eol_php_code/update_resources/connectors/files/GBIF/run_Country_checklists.sh
*/
/* Not copied template:
validate sql:
curl --include --header "Content-Type: application/json" --data @query.json https://api.gbif.org/v1/occurrence/download/request/validate

run request:
curl --include --user eli_agbayani:ile173 --header "Content-Type: application/json" --data @query.json https://api.gbif.org/v1/occurrence/download/request
0030585-241126133413365

check status:
curl -Ss https://api.gbif.org/v1/occurrence/download/0030585-241126133413365

Once status == 'SUCCEEDED': you can proceed to download...
curl --location --remote-name https://api.gbif.org/v1/occurrence/download/request/0030585-241126133413365.zip
*/
// print_r($argv);
$params['jenkins_or_cron']   = @$argv[1]; //irrelevant here
$params['json']              = @$argv[2]; //useful here
$fields = json_decode($params['json'], true);
$task = $fields['task'];
$taxon = @$fields['taxon'];
$download_key = @$fields['download_key'];

//############################################################ start main
$resource_id = $taxon; //e.g. "Country_checklists" or "WaterBody_checklists"
$func = new GBIFdownloadRequestAPI($resource_id);
// exit("\nstop muna...\n");
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