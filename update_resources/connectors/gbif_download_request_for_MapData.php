<?php
namespace php_active_record;
/* This is a library that handles GBIF download requests using their API 
Copied template from original: gbif_download_request.php
*/
/* Workspaces for GBIF map tasks:
- GBIFMapDataAPI
- GBIF_map_harvest
- GBIF_SQL_DownloadsAPI
- GBIFTaxonomy */
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GBIFdownloadRequestAPI');
$timestart = time_elapsed();
/*
This will overwrite any current download request. Run this once ONLY every harvest per taxon group.
php update_resources/connectors/gbif_download_request_for_MapData.php _ '{"task":"send_download_request", "taxon":"map_data_animalia"}'
    GBIF.org (16 February 2025) GBIF Occurrence Download https://doi.org/10.15468/dl.5pgy7k
php update_resources/connectors/gbif_download_request_for_MapData.php _ '{"task":"send_download_request", "taxon":"map_kingdom_not_animalia_nor_plantae"}'
php update_resources/connectors/gbif_download_request_for_MapData.php _ '{"task":"send_download_request", "taxon":"map_plantae_not_phylum_Tracheophyta"}'
--------------------------------------------------------------------------------------------------------------- as of Feb 23, 2025
Animalia = 1    2,405,787,432 GEOREFERENCED RECORDS
Plantae = 6       437,438,303 GEOREFERENCED RECORDS

Archaea = 2           348,156 GEOREFERENCED RECORDS
Bacteria = 3       19,176,920 GEOREFERENCED RECORDS
Chromista = 4      13,544,248 GEOREFERENCED RECORDS
Fungi = 5          36,320,132 GEOREFERENCED RECORDS
Protozoa = 7        1,321,912 GEOREFERENCED RECORDS
Viruses = 8            18,765 GEOREFERENCED RECORDS
incertae sedis = 0  5,045,233 GEOREFERENCED RECORDS
--------------------------------------------------------------------------------------------------------------- Further division
    map_animalia_phylum_Chordata
    phylumkey = 44
---------------------------------------------------------------------------------------------------------------
1. Kingdom not Animalia (1) nor Plantae (6)
    map_kingdom_not_animalia_nor_plantae
    kingdomkey IN (2,3,4,5,7,8,0)
---------------------------------------------------------------------------------------------------------------
Plantae (6)                             437,340,042
    Phylyum Tracheophyta (7707728)          416,670,220
        Class Magnoliopsida (220)               308,858,342
            Order Asterales (414)                   45,678,729
            Order Caryophyllales (422)              25,144,085
            Order Ericales (1353)                   13,660,931

1. Plantae (6) but NOT Phylum Tracheophyta (7707728)
    map_plantae_not_phylum_Tracheophyta
    kingdomkey = 6 AND phylumkey <> 7707728
2. Plantae (6) with Phylum Tracheophyta (7707728) with Class Magnoliopsida (220) with order Asterales Caryophyllales Ericales
    map_phylum_Tracheophyta_class_Magnoliopsida_orders_3
    phylumkey = 7707728 AND classkey = 220 AND orderkey IN (414, 422, 1353)
3. Plantae (6) with Phylum Tracheophyta (7707728) with Class Magnoliopsida (220) but NOT order Asterales Caryophyllales Ericales
    map_phylum_Tracheophyta_class_Magnoliopsida_not_orders_3
    phylumkey = 7707728 AND classkey = 220 AND orderkey NOT IN (414, 422, 1353)
4. Plantae (6) with Phylum Tracheophyta (7707728) but not Class Magnoliopsida (220)
    map_phylum_Tracheophyta_not_class_Magnoliopsida
    phylumkey = 7707728 AND classkey <> 220
---------------------------------------------------------------------------------------------------------------
Animalia (1)                            2,405,714,505
    Phylum Arthropoda (54)                  276,229,066
    Phylum Chordata (44)                    2,097,009,265
        Class Amphibia (131)                    9,574,975
        Class Aves (212)                        1,984,676,566
            Order Passeriformes (729)               1,093,006,886
                Family Corvidae (5235)                  101,641,071
                Family Passerellidae (9410667)          90,413,679
                Family Fringillidae (5242)              86,379,609
                Family Parulidae (5263)                 70,769,533
                Family Turdidae (5290)                  70,521,825
                Family Paridae (9327)                   68,968,932
                Family Icteridae (6176)                 61,050,292
                Family Tyrannidae (5291)                48,796,928
                Family Cardinalidae (9285)              39,277,227 
                Family Troglodytidae (9355)             33,589,855
                Family Sturnidae (9350)                 31,884,219
                Family Passeridae (5264)                27,285,246
                Family Motacillidae (5257)              18,371,440
                Family Vireonidae (9358)                16,099,606
                Family Sylviidae (5285)                 13,280,665
                Family Phylloscopidae (6100963)         12,783,108
                Family Meliphagidae (9319)              9,785,279
                Family Pycnonotidae (5277)              6,228,319
                Family Cisticolidae (9293)              6,076,943
                Family Campephagidae (9284)             2,067,394
                Family Estrildidae (5709)               4,233,653

1. Animalia (1) with Phylum Arthropoda (54)
    map_animalia_phylum_Arthropoda
    phylumkey = 54
2. Animalia but not Phylum Arthropoda (54) nor Chordata (44)
    map_animalia_not_phylum_Arthropoda_Chordata
    kingdomkey = 1 AND phylumkey <> 54 AND phylumkey <> 44 
3. Chordata (44) but not Class Aves (212)
    map_phylum_Chordata_not_class_Aves
    phylumkey = 44 AND classkey <> 212
4. Class Aves (212) but not Order Passeriformes (729)
    map_class_Aves_not_order_Passeriformes
    classkey = 212 AND orderkey <> 729
5. Order Passeriformes (729)
                Family Corvidae (5235)                  101,641,071
                Family Passerellidae (9410667)          90,413,679
                Family Tyrannidae (5291)                48,796,928
    map_order_Passeriformes_with_3_families
    orderkey = 729 AND familykey IN (5235, 9410667, 5291)
6. Order Passeriformes (729)
                Family Fringillidae (5242)              86,379,609
                Family Parulidae (5263)                 70,769,533
                Family Turdidae (5290)                  70,521,825
                Family Motacillidae (5257)              18,371,440
    map_order_Passeriformes_with_4_families
    orderkey = 729 AND familykey IN (5242, 5263, 5290, 5257)
7. Order Passeriformes (729)
                Family Paridae (9327)                   68,968,932
                Family Icteridae (6176)                 61,050,292
                Family Cardinalidae (9285)              39,277,227 
                Family Troglodytidae (9355)             33,589,855
                Family Sturnidae (9350)                 31,884,219
                Family Passeridae (5264)                27,285,246
    map_order_Passeriformes_with_6_families
    orderkey = 729 AND familykey IN (9327, 6176, 9285, 9355, 9350, 5264)
8. Order Passeriformes (729) but not these families:
                Family Corvidae (5235)                  101,641,071
                Family Passerellidae (9410667)          90,413,679
                Family Tyrannidae (5291)                48,796,928
                Family Fringillidae (5242)              86,379,609
                Family Parulidae (5263)                 70,769,533
                Family Turdidae (5290)                  70,521,825
                Family Motacillidae (5257)              18,371,440
                Family Paridae (9327)                   68,968,932
                Family Icteridae (6176)                 61,050,292
                Family Cardinalidae (9285)              39,277,227 
                Family Troglodytidae (9355)             33,589,855
                Family Sturnidae (9350)                 31,884,219
                Family Passeridae (5264)                27,285,246
    map_order_Passeriformes_but_not_13_families
    orderkey = 729 AND familykey NOT IN (5235, 9410667, 5291, 5242, 5263, 5290, 5257, 9327, 6176, 9285, 9355, 9350, 5264)

==================================                
Order Passeriformes un-used:
                Family Vireonidae (9358)                16,099,606
                Family Sylviidae (5285)                 13,280,665
                Family Phylloscopidae (6100963)         12,783,108
                Family Meliphagidae (9319)              9,785,279
                Family Pycnonotidae (5277)              6,228,319
                Family Cisticolidae (9293)              6,076,943
                Family Campephagidae (9284)             2,067,394
                Family Estrildidae (5709)               4,233,653
---------------------------------------------------------------------------------------------------------------

This will generate the .sh file if download is ready. The .sh file is the curl command to download.
php update_resources/connectors/gbif_download_request_for_MapData.php _ '{"task":"generate_sh_file", "taxon":"map_data_others"}'

This will check if all downloads are ready
php update_resources/connectors/gbif_download_request_for_MapData.php _ '{"task":"check_if_all_downloads_are_ready_YN"}'

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
$resource_id = $taxon; //e.g. "map_data_animalia" or "map_data_others"
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