<?php
namespace php_active_record;
/* 1st client: zenodo.php
docs:   https://developers.zenodo.org/?shell#representation
        https://help.zenodo.org/docs/deposit/manage-files/
        https://help.zenodo.org/guides/search/

Use this when searching a title in Zenodo. Paste this in the search textbox:
title:("EOL Dynamic Hierarchy: DH223test.zip")
Zenodo total count: 2,236 (open)        as of Aug 28, 2024
                       10 (restricted)  as of Aug 28, 2024
{"Aggregate Datasets":38,"EOL Content Partners":1774,"EOL Dynamic Hierarchy Data Sets":67,"Legacy datasets":56}
{"Aggregate Datasets":38,"EOL Content Partners":1794,"EOL Dynamic Hierarchy Data Sets":67,"Legacy datasets":56}

Search in interface:
metadata.subjects.subject:"Aggregate Datasets: EOL computer vision pipelines"
    https://zenodo.org/communities/eol/records?q=metadata.subjects.subject%3A%22Aggregate%20Datasets%3A%20EOL%20computer%20vision%20pipelines%22&l=list&p=1&s=10&sort=bestmatch
metadata.subjects.subject:"EOL Content Partners: Wikipedia"
    https://zenodo.org/search?q=metadata.subjects.subject%3A%22EOL%20Content%20Partners%3A%20Wikipedia%22&l=list&p=1&s=10&sort=bestmatch

Below are the 9 weird Zenodo errors during bulk updates:
proceed with U and P	13384702	Eli temporary files: page_ids_ancestry.txt.zip	2024-09-28 01:31:50 AM
ERROR	update_0924	13384702	{"error_id":"0b83d84323ec42499eca87ecfe58809e","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:31:51 AM
proceed with U and P	13381012	WoRMS internal: World Register of Marine Species	2024-09-28 01:33:28 AM
ERROR	update_0924	13381012	{"error_id":"4ce62d2504d646c689bc3fa8c860fa44","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:33:28 AM

proceed with U and P	13382574	EOL computer vision pipelines: Object Detection for Image Cropping: Chiroptera	2024-09-28 01:32:03 AM
ERROR	update_0924	13382574	{"error_id":"d56496755f8943908e7c0085ec07fa08","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:32:04 AM
proceed with U and P	13382576	EOL computer vision pipelines: Object Detection for Image Cropping: Aves	2024-09-28 01:32:15 AM
ERROR	update_0924	13382576	{"error_id":"479c01bc182e41278bec1a32f2773426","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:32:16 AM
proceed with U and P	13382578	EOL computer vision pipelines: Object Detection for Image Cropping: Multi-taxa	2024-09-28 01:32:27 AM
ERROR	update_0924	13382578	{"error_id":"c1fb071e01864507b913500ba426c96a","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:32:28 AM
proceed with U and P	13382580	EOL computer vision pipelines: Classification for Image Tagging: Flower Fruit	2024-09-28 01:32:39 AM
ERROR	update_0924	13382580	{"error_id":"a63cbfe08a3b4b21b0ac832b4871189a","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:32:40 AM
proceed with U and P	13382584	EOL computer vision pipelines: Classification for Image Tagging: Anura	2024-09-28 01:32:51 AM
ERROR	update_0924	13382584	{"error_id":"a01f682049eb4ad68b8efc87ecedd4ee","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:32:52 AM
proceed with U and P	13382586	EOL computer vision pipelines: Image Rating: Chiroptera	2024-09-28 01:33:03 AM
ERROR	update_0924	13382586	{"error_id":"a46067fb8cc3443799ff8a0ff03f0f3c","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:33:04 AM
proceed with U and P	13382488	EOL computer vision pipelines: Object Detection for Image Cropping: Lepidoptera	2024-09-28 01:33:16 AM
ERROR	update_0924	13382488	{"error_id":"de4cd960650442a18c9e185ca90326e9","message":"The server encountered an internal error and was unable to complete your request. Either the server is overloaded or there is an error in the application.","status":500}	2024-09-28 01:33:17 AM
*/

class ZenodoAPI extends ZenodoConnectorAPI
{
    function __construct($folder = null, $query = null)
    {
        $this->download_options = array(
            'resource_id'        => 'zenodo',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*1, //maybe 1 day to expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);
        // $this->download_options['expire_seconds'] = 0;
        $this->download_options['expire_seconds'] = 60*60*24*30; //for eol content partners

        $this->api['domain'] = 'https://zenodo.org';
        if(Functions::is_production()) {
            $this->path_2_file_dat = '/extra/other_files/Zenodo/';
            $this->cache_path      = '/extra/other_files/Zenodo/cache/';
        }
        else {
            $this->path_2_file_dat = '/Volumes/OWC_Express/other_files/Zenodo/';
            $this->cache_path      = '/Volumes/OWC_Express/other_files/Zenodo/cache/';
        }
        if(!is_dir($this->path_2_file_dat)) mkdir($this->path_2_file_dat);
        if(!is_dir($this->cache_path)) mkdir($this->cache_path);
        $this->log_file = $this->path_2_file_dat . "Zenodo_logs.tsv";
        $this->html_report = $this->path_2_file_dat . "opendata_zenodo.html";
        $this->stats_file = $this->path_2_file_dat . "stats.tsv";

        $this->Write_EOL_resource_id_and_Zenodo_id_file = $this->path_2_file_dat . "EOL_resource_id_and_Zenodo_id_file.tsv";
        $WRITE = Functions::file_open($this->Write_EOL_resource_id_and_Zenodo_id_file, "c");
        fclose($WRITE);

        // /* main report
        // [0] => dynamic-hierarchy
        // [1] => encyclopedia_of_life
        // [2] => eol-content-partners
        // [3] => legacy-datasets
        // [4] => wikidata-trait-reports
        $this->report['EOL Dynamic Hierarchy Data Sets']    = $this->path_2_file_dat . "dynamic-hierarchy.json";
        $this->report['Aggregate Datasets']                 = $this->path_2_file_dat . "encyclopedia_of_life.json";
        $this->report['EOL Content Partners']               = $this->path_2_file_dat . "eol-content-partners.json";
        $this->report['Legacy datasets']                    = $this->path_2_file_dat . "legacy-datasets.json";
        $this->report['WikiData Trait Reports']             = $this->path_2_file_dat . "wikidata-trait-reports.json";

        $this->org_name['dynamic-hierarchy']        = 'EOL Dynamic Hierarchy Data Sets';
        $this->org_name['encyclopedia_of_life']     = 'Aggregate Datasets';
        $this->org_name['eol-content-partners']     = 'EOL Content Partners';
        $this->org_name['legacy-datasets']          = 'Legacy datasets';
        $this->org_name['wikidata-trait-reports']   = 'WikiData Trait Reports'; 

        // $this->organization = array("EOL Dynamic Hierarchy Data Sets", "Aggregate Datasets", "EOL Content Partners", "Legacy datasets", "WikiData Trait Reports");
        // */
        
        /*
        https://opendata.eol.org/api/3/action/package_list
        */
        $this->ckan['organization_list'] = 'https://opendata.eol.org/api/3/action/organization_list';
        $this->ckan['organization_show'] = 'https://opendata.eol.org/api/3/action/organization_show?id=ORGANIZATION_ID&include_datasets=true';
        // https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life&include_datasets=true
        // -> useful to get all datasets; even private ones
        // https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life
        // -> no datasets included

        $this->ckan['package_show'] = 'https://opendata.eol.org/api/3/action/package_show?id='; //e.g. images-list
        // https://opendata.eol.org/api/3/action/package_show?id=images-list

        $this->ckan['user_show'] = 'https://opendata.eol.org/api/3/action/user_show?id='; //e.g. 47d700d6-0f4c-43e8-a0c5-a5e739bc390c

        $this->temp_count = 0;
        $this->license_map = array(
            "cc-by"           => "cc-by-1.0",
            "cc-by-sa"        => "cc-by-sa-1.0",
            "cc-nc"           => "cc-by-nc-1.0",
            "cc-zero"         => "cc0-1.0",
            "notspecified"    => "notspecified",
            "odc-by"          => "odc-by-1.0",
            "odc-pddl"        => "pddl-1.0",
            "other-open"      => "other-open",
            "other-pd"        => "other-pd",            
        );
        $this->debug = array();
        $this->debug['total resources'] = 0;
        $this->divide_and_conquer = 0;

        /* not helpful since Authorization is required regardless of user-agent
        https://www.whatismybrowser.com/detect/what-is-my-user-agent/
        */
        $this->new_description_for_zenodo = "";
        $this->show_print_r = false;
        $this->html_contributors = array();
        $this->ORCIDs['Eli Agbayani'] = '0009-0007-6825-9034'; //https://orcid.org/0009-0007-6825-9034
        $this->ORCIDs['Agbayani, Eli'] = '0009-0007-6825-9034'; //https://orcid.org/0009-0007-6825-9034
        $this->ORCIDs['Jen Hammock'] = '0000-0002-9943-2342'; //https://orcid.org/0000-0002-9943-2342
        $this->ORCIDs['Hammock, Jennifer'] = '0000-0002-9943-2342'; //https://orcid.org/0000-0002-9943-2342
        $this->ORCIDs['Jennifer Hammock'] = '0000-0002-9943-2342'; //https://orcid.org/0000-0002-9943-2342
        $this->ORCIDs['Schulz, Katja'] = '0000-0001-7134-3324'; //https://orcid.org/0000-0001-7134-3324
        $this->ORCIDs['Katja Schulz'] = '0000-0001-7134-3324'; //https://orcid.org/0000-0001-7134-3324

        $this->github_EOL_resource_id_and_Zenodo_id_file = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Zenodo/EOL_resource_id_and_Zenodo_id_file.tsv';
        $this->api['record'] = 'https://zenodo.org/api/records/'; //e.g. https://zenodo.org/api/records/13322979
        $this->api['versions'] = "https://zenodo.org/api/records/ZENODO_ID/versions?page=PAGE_NUM&size=25&sort=version"; //e.g. "https://zenodo.org/api/records/14035881/versions?page=1&size=25&sort=version"
    }
    function start()
    {   self::log_error(array("==================== Log starts here ===================="));
        if($json = Functions::lookup_with_cache($this->ckan['organization_list'], $this->download_options)) {
            $o = json_decode($json, true); //print_r($o); exit;
            foreach($o['result'] as $organization_id) { $this->organization_id = $organization_id;
                /*Array(
                    [0] => dynamic-hierarchy
                    [1] => encyclopedia_of_life
                    [2] => eol-content-partners
                    [3] => legacy-datasets
                    [4] => wikidata-trait-reports
                )*/
                
                /* main operation
                if(in_array($organization_id, array('encyclopedia_of_life', 'dynamic-hierarchy', 'legacy-datasets'))) continue; //migrated public datasets already //main operation
                if(in_array($organization_id, array('encyclopedia_of_life', 'dynamic-hierarchy', 'legacy-datasets'))) continue; //uploaded actual files already
                */

                // if($organization_id != 'encyclopedia_of_life') continue;         //Aggregate Datasets    //debug only dev only
                // if($organization_id != 'dynamic-hierarchy') continue;            //xxx                   //debug only dev only
                // if($organization_id != 'legacy-datasets') continue;              //xxx                   //debug only dev only
                // if($organization_id != 'wikidata-trait-reports') continue;       //xxx                   //debug only dev only
                // if($organization_id != 'eol-content-partners') continue;            //xxx                   //debug only dev only

                /* main report: generate info list for title lookup
                $this->title_id_info = self::generate_title_id_info($organization_id);
                */

                echo "\norganization ID: [$organization_id]\n";
                self::process_organization($organization_id);
            }
        }
        print_r($this->debug); //very good debug

        $arr = array_keys($this->debug['urls pathinfo']);
        asort($arr); //print_r($arr);

        print_r(array_keys($this->debug));
        echo "\ntotal resources: ".$this->debug['total resources'];
        echo "\nurls: ".count($this->debug['urls']);
        echo "\nurls pathinfo: ".count($this->debug['urls pathinfo']);
        echo "\ntitles: ".count($this->debug['titles'])."\n\n";
        print_r($this->debug2);

        // /*
        self::check_license_values();
        $sum = 0; 
        if($val = @$this->debug['license_id']) {
            foreach($val as $n) $sum += $n;
        }
        echo "\nlicense_id: [$sum]\n";
        // */

        echo "\ntotal resources: [".$this->debug['total resources']."]\n";
        echo "\ntotal resources migrated: [".@$this->debug['total resources migrated']."]\n";
        // self::list_depositions(); //utility -- check if there are records in CKAN that are not in Zenodo yet.

        /* main report
        // print_r($this->report); exit;
        $json = json_encode($this->report);
        $file = $this->report[$this->organization_name];
        $WRITE = Functions::file_open($file, "w");
        fwrite($WRITE, $json); fclose($WRITE);
        */
    }
    private function process_organization($organization_id)
    {
        $url = str_replace('ORGANIZATION_ID', $organization_id, $this->ckan['organization_show']); //not getting correct total of packages

        // the 5 organizations - are saved as json files:
        // $url = 'https://opendata.eol.org/api/3/action/organization_show?id=dynamic-hierarchy&include_datasets=true';
        // $url = 'https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life&include_datasets=true';
        // $url = 'https://opendata.eol.org/api/3/action/organization_show?id=eol-content-partners&include_datasets=true';
        // $url = 'https://opendata.eol.org/api/3/action/organization_show?id=legacy-datasets&include_datasets=true';
        // $url = 'https://opendata.eol.org/api/3/action/organization_show?id=wikidata-trait-reports&include_datasets=true';

        // $url = 'http://localhost/other_files2/Zenodo_files/json/encyclopedia_of_life.json'; //an organization: Aggregate Datasets
        // $url = "http://localhost/other_files2/Zenodo_files/json/".$organization_id.".json";

        // $url = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/Zenodo/json/".$organization_id.".json"; //main operation

        $options = $this->download_options;
        // $options['expire_seconds'] = 0;

        if($json = Functions::lookup_with_cache($url, $options)) {
            $o = json_decode($json, true); //print_r($o); exit("\n111\n");
            $this->organization_name = $o['result']['display_name'];
            echo "\npackage_count: ".$o['result']['package_count']."\n";
            foreach($o['result']['packages'] as $p) { //print_r($p); exit("\n111\n");
                $this->dataset_title = $p['title'];
                // if(in_array($p['title'], array("Images list", "Vernacular names", "FishBase", "EOL Stats for species level pages"))) continue; //main operation

                // /* dev only --- force limit the loop
                // if($p['title'] != 'Images list') continue; //debug only dev only
                // if($p['title'] != 'Vernacular names') continue; //debug only dev only
                // if($p['title'] != 'EOL computer vision pipelines') continue; //debug only dev only
                // if($p['title'] != 'WoRMS internal') continue; //debug only dev only
                // */

                // /* UN-COMMENT for PUBLIC datasets.
                if(self::is_dataset_private_YN($p)) continue;   //private --- waiting for Jen to cherry-pick those to include to migrate to Zenodo
                else {}                                         //public --- the rest will be processed    
                // */

                /* UN-COMMENT for PRIVATE datasets.
                if(self::is_dataset_private_YN($p)) {}   //private
                else continue;                           //public    
                */

                // print_r($p); exit("\nditox1\n");
                self::process_a_package($p); //main operation
            }
        }
        else echo "\nInvestigate: Cannot lookup: [$url]\n";
    }
    private function process_a_package($p) //process a dataset
    {   // print_r($p);
        // echo "\nnum_resources: ".$p['num_resources']."\n";
        // loop to each of the resources of a package
        $package_obj = self::lookup_package_using_id($p['id']);
        if($resources = @$package_obj['result']['resources']) {
            $this->debug['total resources'] += count($resources);
            foreach($resources as $r) { //print_r($r);

                //name here is CKAN resource name 
                // if($r['name'] != 'EOL stats for species-level pages') continue;
                // if($r['name'] != 'EOL Dynamic Hierarchy Active Version') continue; //e.g. https://opendata.eol.org/dataset/tram-807-808-809-810-dh-v1-1/resource/00adb47b-57ed-4f6b-8f66-83bfdb5120e8    
                // if($r['name'] != 'Duplicate DH1.1 page mappings') continue;
                // if($r['name'] != 'EOL Tenebrionidae Patch') continue;
                // if($r['name'] != 'EOL Dynamic Hierarchy Lizards Patch') continue;
                // if($r['name'] != 'EOL Mammals Patch Version 1') continue;
                // if($r['name'] != 'current version') continue;                
                // if($r['name'] != 'All trait data') continue;
                // if($r['name'] != 'EOL Dynamic Hierarchy Erebidae Patch') continue;
                // if($r['name'] != 'EOL Dynamic Hierarchy Trunk Active Version') continue;
                // if($r['name'] != 'EOL Fossil Fishes Patch ') continue;                
                // if($r['name'] != 'vernacular names, May 2020') continue;
                // if($r['name'] != 'User Added Text, curated') continue; //"User Generated Content (EOL v2): User Added Text, curated"
                // if($r['name'] == 'Hierarchy Entries April 2017') continue;                      //done -- migrated completely*
                // if($r['name'] == '2019, August 22') continue; //early exports: 2019, August 22  //done -- migrated completely*
                // if($r['name'] != 'World Register of Marine Species') continue;

                $input = self::generate_input_field($p, $r, $resources); //main operation
                $title = $input['metadata']['title'];

                /* ----- start main report -----
                // x MainRep: Title not found    13322823	[EduLifeDesks Archive: From so simple a beginning: 2010 (357) DwCA]	2024-08-14 12:50:54 PM
                // x MainRep: Title not found    13333044 	[GBIF data summaries: GBIF nat'l node classification resource: Germany]	2024-08-14 03:59:45 PM
                // x MainRep: Title not found    13340241	[Thomas J. Walker Sound Recordings from Macaulay Library of Natural Sounds: Thomas J. Walker's insect recordings]	2024-08-14 06:41:48 PM
                if($title == "National Checklists: São Tomé and Príncipe Species List") $id_sought = 13313212;
                elseif($title == "National Checklists 2019: São Tomé and Príncipe Species List") $id_sought = 13317655;
                elseif($title == "National Checklists 2019: Réunion Species List") $id_sought = 13317938;
                else {
                    if($id_sought = @$this->title_id_info[$title]) { echo "\nTitle found: [$title]"; }
                    else {
                        sleep(2);
                        $obj = self::get_deposition_by_title($title); //print_r($obj); exit;
                        if(!$obj) {
                            self::log_error(array("MainRep2: Title not found", "[$title]"));
                            echo "\nMainRep2: Title not found [$title]\n";
                            continue;
                        }
                        if(self::if_error($obj, 'get_deposition_by_title', $title)) return;
                        $id_sought = $obj['id'];
                    }    
                }
                $this->report['main_report'][$this->organization_name][$this->dataset_title][$title] = $id_sought;

                // -------------------------------------- start EOL resource ID and Zenodo ID list
                $this->gen_EOL_resource_ID_and_Zenodo_ID_list($r, $id_sought);
                // -------------------------------------- end

                continue;
                ----- end main report ----- */

                // if(in_array($title, array("Vernacular names: vernacular names, May 2020", "Identifiers with Images (EOL v2): identifiers_with_images.csv.gz", "User Generated Content (EOL v2): User Added Text, curated"))) continue; //ckan file already uploaded
                // if(in_array($title, array("early exports: 2019, August 22"))) continue;                                 //done -- migrated completely* Legacy datasets
                // if(in_array($title, array("EOL Hierarchy Entries April 2017: Hierarchy Entries April 2017"))) continue; //done -- migrated completely* Legacy datasets
                // if(in_array($title, array("FishBase: FishBase"))) continue; //migrated already
                // if(in_array($title, array("FishBase"))) continue; //migrated already
                // if(in_array($title, array("Paleobiology Database (PBDB): PBDB (368) in DwCA"))) continue; //migrated already
                // if(in_array($title, array("DiscoverLife: Discoverlife Maps"))) continue; //migrated already
                // if(in_array($title, array("van Tienhoven, 2003: van Tienhoven, A. 2003"))) continue; //migrated already

                // ============ dev only
                /* only private datasets for migration:
                [EOL Content Partners: eol-content-partners]    => WoRMS internal: worms-internal -> "WoRMS internal: World Register of Marine Species"
                [Aggregate Datasets: encyclopedia_of_life]      => Dataset test 2019: dataset-test-2019 DONE
                [Aggregate Datasets: encyclopedia_of_life]      => EOL computer vision pipelines: eol-computer-vision-pipelines
                    [EOL computer vision pipelines: Object Detection for Image Cropping: Lepidoptera]
                    [EOL computer vision pipelines: Object Detection for Image Cropping: Chiroptera]
                    [EOL computer vision pipelines: Object Detection for Image Cropping: Aves]
                    [EOL computer vision pipelines: Object Detection for Image Cropping: Multi-taxa]
                    [EOL computer vision pipelines: Classification for Image Tagging: Flower Fruit]
                    [EOL computer vision pipelines: Classification for Image Tagging: Anura]
                    [EOL computer vision pipelines: Image Rating: Chiroptera]
                */

                // echo "\n[$title]\n"; //good debug
                // if(!in_array($title, array("Dataset test 2019: dataset-test-2019"))) continue;
                // if(!in_array($title, array("WoRMS internal: World Register of Marine Species"))) continue;
                // if(in_array($title, array("EOL computer vision pipelines: Object Detection for Image Cropping: Lepidoptera"))) continue;
                // if(!in_array($title, array("Eli temporary files: page_ids_ancestry.txt.zip"))) continue;

                /* add resources one by one:
                $title = str_replace("'", "__", $title); //ditoxAug17
                $new_title = "EduLifeDesks Archive: The Field Museum Member's Night EOL Photo Scavenger Hunt 2010 (137) DwCA";
                $new_title = "Moth Photographer's Group";
                $new_title = "Ori Fragman-Sapir's TrekNature Gallery";
                $new_title = "Ori Fragman-Sapir's TrekNature Gallery: Ori Fragman's TrekNature gallery";
                $new_title = "Royal Botanic Garden Edinburgh: Rhododendrons from Curtis' Botanical Magazine";
                $new_title = "Thomas J. Walker Sound Recordings from Macaulay Library of Natural Sounds: Thomas J. Walker's insect recordings";
                $new_title = str_replace("'", "__", $new_title); //ditoxAug17
                if(!in_array($title, array($new_title))) continue;
                */
                
                /* ---------- block of code --- only accept "http:" not "https:"
                if($url = @$input['metadata']['related_identifiers'][0]['identifier']) {
                    // $needle = "http://editors.eol.org/eol_php_code/applications/content_server/resources";
                    $needle = "http://editors.eol.org";
                    if(stripos($url, $needle) !== false) {} //string is found 
                    else continue;
                }
                else continue;
                ---------- */

                /* ======================================= divide and conquer
                @$this->divide_and_conquer++;
                $m = $this->divide_and_conquer;
                if(    $m >= 1 && $m <= 5)  continue; //done
                elseif($m > 5  && $m <= 20) continue; //done
                elseif($m > 20 && $m <= 25) continue; //done
                elseif($m > 25 && $m <= 60) continue; //done
                elseif($m > 60 && $m <= 100) continue; //done
                elseif($m > 100 && $m <= 200) continue; //done
                elseif($m > 200 && $m <= 400) continue; //done
                elseif($m > 400 && $m <= 600) continue; //done
                elseif($m > 600 && $m <= 800) continue; //done
                elseif($m > 800 && $m <= 1200) continue; //done
                elseif($m > 1200 && $m <= 1600) continue; //done
                elseif($m > 1600 && $m <= 2000) continue; //done
                elseif($m > 2000 && $m <= 2400) {} 
                else continue;
                if($m == 2100) { echo "\nPause 4 minutes ($m counter)\n"; sleep(60*4); }
                if($m == 2200) { echo "\nPause 4 minutes ($m counter)\n"; sleep(60*4); }
                if($m == 2300) { echo "\nPause 4 minutes ($m counter)\n"; sleep(60*4); }
                // error starts with: ERROR	create	Ori Fragman-Sapir's TrekNature Gallery	{"status":400,"message":"Unable to decode JSON data in request body."}	2024-08-13 11:45:03 AM
                ======================================= */

                // print_r($input); //exit("\nfirst occurrence\n");
                $this->input = $input;

                /*
                self::start_Zenodo_process($input); //main operation
                */

                /*
                self::start_Zenodo_upload_only($title); //main operation --- upload of actual file to a published Zenodo record
                */

                /*
                self::just_stats($input);
                */

                // exit("\n--a resource object--\n");
            }
        }
    }
    private function just_stats($input)
    {
        if($url = @$input['metadata']['related_identifiers'][0]['identifier']) {
            echo "\n[$url]\n"; //print_r(pathinfo($url));
            $needle1 = "https://editors.eol.org/";
            $needle2 = "http://editors.eol.org/";
            if(stripos($url, $needle1) !== false || stripos($url, $needle2) !== false) { //string is found
                $arr = explode("/", $url); // print_r($arr);
                if(stripos($url, "editors.eol.org/eol_php_code/applications/content_server/resources/") !== false) $this->debug['editors path']['eol resources'] = '';  //string is found
                else $this->debug['editors path'][$arr[3]] = '';
            }
        }
    }
    function Zenodo_upload_publish($id) //utility where the xxx.dat file was not uploaded during bulk migration
    {
        $obj = self::retrieve_dataset($id); //works OK
        if(self::if_error($obj, 'retrieve', $id)) {}
        else {
            $upload_obj = self::upload_Zenodo_dataset($obj); //worked OK
            if(self::if_error($upload_obj, 'upload', $obj['id'])) {}
            else {
                $obj = self::retrieve_dataset($id); //works OK
                if(self::if_error($obj, 'retrieve', $id)) {}
                else {
                    $publish_obj = self::publish_Zenodo_dataset($obj); //worked OK
                    if(self::if_error($publish_obj, 'publish', $obj['id'])) {}
                    else {
                        echo "\nSuccessfully migrated to Zenodo";
                        echo "\n----------\n";
                    }    
                }   
            }    
        }
    }
    private function start_Zenodo_upload_only($title) //upload of actual file to a published Zenodo record
    {
        echo "\n[$title]\n"; echo "\nPause 5 seconds...\n"; sleep(5);
        $obj = self::get_deposition_by_title($title); // print_r($obj); exit;
        if(!$obj) {
            self::log_error(array("Title not found", "needle:[$title]"));
            echo "\nTitle not found*. needle:[$title]\n";
            return;
        }
        if(self::if_error($obj, 'get_deposition_by_title', $title)) return;

        $retrieved_title = $obj['metadata']['title'];
        echo "\ntitle to search: [$title]";
        echo "\nretrieved_title: [$retrieved_title]\n";
        if($title != $retrieved_title) {
            echo "\nRetrieved title is not a match. Will not proceed.\n";
            self::log_error(array("Title not found", "needle:[$title]", "haystack:[$retrieved_title]"));
            return;
        }

        // return; //debug eonly

        // start here... include the 3 if-then-else for the diff url types
        // /*
        if($url = @$obj['metadata']['related_identifiers'][0]['identifier']) {}
        else { self::log_error(array("ERROR", "No URL, should not go here.", $obj['id'])); return; }

        if($new_obj = self::request_newversion($obj)) { $id = $new_obj['id']; //13271534 --- this ID will be needed for the next retrieve-publish tasks below. //main operation
        // if(true) { //debug only dev only

            // PICK 1 OF THE 3 ---    

            // /* original
            // if($actual_file = self::is_ckan_uploaded_file($url))        $this->debug['to process'][$title]=$url; //$upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            // elseif($actual_file = self::is_editors_other_files($url))   $this->debug['to process'][$title]=$url; //$upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            // elseif($actual_file = self::is_editors_eol_resources($url)) $this->debug['to process'][$title]=$url; //$upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            // else return;
            if($actual_file = self::is_ckan_uploaded_file($url))        $upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            elseif($actual_file = self::is_editors_other_files($url))   $upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            elseif($actual_file = self::is_editors_eol_resources($url)) $upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            else {
                if(isset($new_obj)) self::request_discard($new_obj);
                return;
            }
            // */

            /* for DH and aggregate datasets
            // if($actual_file = self::is_editors_other_files($url))       $this->debug['to process'][$title]=$url; //$upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            // elseif($actual_file = self::is_editors_eol_resources($url)) $this->debug['to process'][$title]=$url; //$upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            // else return;
            if($actual_file = self::is_editors_other_files($url))       $upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            elseif($actual_file = self::is_editors_eol_resources($url)) $upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            else {
                if(isset($new_obj)) self::request_discard($new_obj);
                return;
            }
            */
    
            /* for legacy datasets            
            // if($actual_file = self::is_editors_eol_resources($url)) $this->debug['to process'][$title]=$url; //$upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            // else return;
            if($actual_file = self::is_editors_eol_resources($url)) $upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);  //uploads actual file
            else {
                if(isset($new_obj)) self::request_discard($new_obj);
                return;
            }
            */
    
            // return; //dev only debug only

            if(self::if_error($upload_obj, 'upload', $new_obj['id'])) {}
            else {
                // it seems the $upload_obj will not be used atm.
                /*Array( $upload_obj
                    [created] => 2024-08-08T14:48:22.623440+00:00
                    [updated] => 2024-08-08T14:48:30.794780+00:00
                    [version_id] => e2866bf9-5abe-41c7-a50e-07b1ec17027c
                    [key] => vernacularnames.csv
                    [size] => 222967948
                    [mimetype] => text/csv
                    [checksum] => md5:8e847b0d4f4ab6267e1c23555b771ca8
                    [is_head] => 1
                    [delete_marker] => 
                    [links] => Array(
                            [self] => https://zenodo.org/api/files/cb841b0c-e915-4655-9c15-88a078529d03/vernacularnames.csv
                            [version] => https://zenodo.org/api/files/cb841b0c-e915-4655-9c15-88a078529d03/vernacularnames.csv?versionId=e2866bf9-5abe-41c7-a50e-07b1ec17027c
                            [uploads] => https://zenodo.org/api/files/cb841b0c-e915-4655-9c15-88a078529d03/vernacularnames.csv?uploads
                        )
                )*/
                // /* retrieve and publish
                $update_obj = self::update_Zenodo_record($id, $this->input); //to fill-in the publication_date
                if(self::if_error($update_obj, 'update1', $id)) {}
                else {
                    $obj = self::retrieve_dataset($id); //works OK
                    if(self::if_error($obj, 'retrieve', $id)) {}    
                    else {
                        // $input['metadata'] = array("publication_date" => date("Y-m-d")); //2010-12-30 --- this is needed for publishing a newly uploaded file.
                        $publish_obj = self::publish_Zenodo_dataset($obj); //worked OK
                        if(self::if_error($publish_obj, 'publish', $obj['id'])) {}
                        else {
                            echo "\nSuccessfully uploaded then published to Zenodo\n-----u & p-----\n";
                            self::log_error(array('uploaded then published', @$obj['id'], @$obj['metadata']['title'], @$obj['metadata']['related_identifiers'][0]['identifier']));
                        }
                    }
                }
                // */
            }            
        }
        else echo "\nERRORx: newversion object not created!\n";
        // */

        /* this block was eventually refactored:
        if($url = @$obj['metadata']['related_identifiers'][0]['identifier']) {
            $info = pathinfo($url); print_r($info);
            $needle = "https://editors.eol.org/uploaded_resources";
            if(stripos($url, $needle) !== false) { //string is found --- means this file is originally a ckan uploaded file.
                $subfolders = str_replace($needle, "", $info['dirname']);                   // e.g. /bf6/3dc
                $actual_file = "/extra/ckan_resources".$subfolders."/".$info['basename'];   // e.g. /extra/ckan_resources/bf6/3dc/vernacularnames.csv
                echo "\nsource: [$actual_file]\n";
                exit("\ncha2\n");
                if(file_exists($actual_file)) {
                // if(true) {
                    echo "\nfilesize: ".filesize($actual_file)."\n";
                    if($new_obj = self::request_newversion($obj)) {
                        $id = $new_obj['id']; //13271534 --- this ID will be needed for the next retrieve-publish tasks below.
                        // exit("\nexit muna after new version request\n");
                        $upload_obj = self::upload_Zenodo_dataset($new_obj, $actual_file);
                    }
                    else echo "\nERRORx: newversion object not created!\n";
                }
                else echo "\nNo file uploaded. File does not exist. [$actual_file]\n";
            }
            else echo "\nNot a CKAN URL [$url].\n";
        }*/
        // exit("\n--stop muna ditox--\n");
    }
    function start_Zenodo_process($input)
    {
        $title_x = $input['metadata']['title'];
        $notes_x = $input['metadata']['notes'];
 
        echo "\nPause 10 seconds...\n"; sleep(10);
        $create_obj = self::create_Zenodo_dataset($input);
        if(self::if_error($create_obj, 'create', $title_x)) {}
        else {
            if($id = $create_obj['id']) {
                $obj = self::retrieve_dataset($id); //works OK
                if(self::if_error($obj, 'retrieve', $id)) {}
                else {
                    // /* main operation
                    if($url = @$obj['metadata']['related_identifiers'][0]['identifier']) {}
                    else { self::log_error(array("ERROR", "No URL, should not go here.", $obj['id'])); return; }
                    if($actual_file = self::is_ckan_uploaded_file($url, $title_x))        $upload_obj = self::upload_Zenodo_dataset($obj, $actual_file);  //uploads actual file
                    elseif($actual_file = self::is_editors_other_files($url, $title_x))   $upload_obj = self::upload_Zenodo_dataset($obj, $actual_file);  //uploads actual file
                    elseif($actual_file = self::is_editors_eol_resources($url, $title_x)) $upload_obj = self::upload_Zenodo_dataset($obj, $actual_file);  //uploads actual file
                    else                                                                  $upload_obj = self::upload_Zenodo_dataset($obj);                //uploads .dat file
                    // */
                    /* dev only debug only
                    $upload_obj = self::upload_Zenodo_dataset($obj);
                    */
                    if(self::if_error($upload_obj, 'upload', $obj['id'])) {}
                    else {
                        $obj = self::retrieve_dataset($id); //works OK
                        print_r($obj);
                        /* main operation - publish -> commented for private "restricted" records migration
                        if(self::if_error($obj, 'retrieve', $id)) {}
                        else {
                            $publish_obj = self::publish_Zenodo_dataset($obj); //worked OK
                            if(self::if_error($publish_obj, 'publish', $obj['id'])) {}
                            else {
                                echo "\nSuccessfully migrated to Zenodo\n";
                                echo $title_x."\n";
                                echo $notes_x."\n----------\n";
                                @$this->debug['total resources migrated']++;
                                self::log_error(array('migrated', @$obj['id'], @$obj['metadata']['title'], @$obj['metadata']['related_identifiers'][0]['identifier']));
                            }    
                        }
                        */   
                    }    
                }    
            }
        }        
    }
    private function is_ckan_uploaded_file($url, $title = "") /* symlink: uploaded_resources -> /extra/ckan_resources/ */
    {   $info = pathinfo($url); //print_r($info);
        /*Array(
            [dirname] => https://editors.eol.org/uploaded_resources/bf6/3dc
            [basename] => vernacularnames.csv
            [extension] => csv
            [filename] => vernacularnames
        )*/
        $needle1 = "https://editors.eol.org/uploaded_resources";
        $needle2 = "http://editors.eol.org/uploaded_resources";
        if(stripos($url, $needle1) !== false || stripos($url, $needle2) !== false) { //string is found --- means this file is originally a ckan uploaded file.    
            $subfolders = str_replace($needle1, "", $info['dirname']);                   // e.g. /bf6/3dc
            $subfolders = str_replace($needle2, "", $subfolders);                        // e.g. /bf6/3dc
            $actual_file = "/extra/ckan_resources".$subfolders."/".$info['basename'];   // e.g. /extra/ckan_resources/bf6/3dc/vernacularnames.csv
            if(file_exists($actual_file)) {
                echo "\nsource: [$actual_file]\n";
                return $actual_file;    
            }
            else self::log_error(array("MISSING: actual_file not found. [$actual_file]", $title, "uploaded_resources"));
        }
        return false;
    }
    private function is_editors_other_files($url, $title = "") /* symlink: other_files -> /extra/other_files/ */
    {   // [https://editors.eol.org/other_files/SDR/traits-20191111/traits_all_201911.zip] => 
        $info = pathinfo($url); //print_r($info);
        /*Array(
            [dirname] => https://editors.eol.org/other_files/SDR/traits-20191111
            [basename] => traits_all_201911.zip
            [extension] => zip
            [filename] => traits_all_201911
        )*/
        $needle1 = "https://editors.eol.org/other_files";
        $needle2 = "http://editors.eol.org/other_files";
        if(stripos($url, $needle1) !== false || stripos($url, $needle2) !== false) { //string is found --- means this file is stored in eol-archive (editors.eol.org) [/other_files/].    
            $subfolders = str_replace($needle1, "", $info['dirname']);               // e.g. /SDR/traits-20191111
            $subfolders = str_replace($needle2, "", $subfolders);                    // e.g. /SDR/traits-20191111
            $actual_file = "/extra/other_files".$subfolders."/".$info['basename'];  // e.g. /extra/other_files/SDR/traits-20191111/traits_all_201911.zip
            if(file_exists($actual_file)) {
                echo "\nsource: [$actual_file]\n";
                return $actual_file;    
            }
            else self::log_error(array("MISSING: actual_file not found. [$actual_file]", $title, "other_files"));
        }
        return false;
    }
    private function is_editors_eol_resources($url, $title = "") /* symlink: resources -> /extra/eol_php_resources/ */
    {   // https://editors.eol.org/eol_php_code/applications/content_server/resources/eol_traits/bat-body-masses.txt.gz
        // https://editors.eol.org/eol_php_code/applications/content_server/resources/173.tar.gz
        $info = pathinfo($url); //print_r($info);
        /*Array(
            [dirname] => https://editors.eol.org/eol_php_code/applications/content_server/resources/eol_traits
                         https://editors.eol.org/eol_php_code/applications/content_server/resources
            [basename] => bat-body-masses.txt.gz OR 173.tar.gz
            [extension] => ???
            [filename] => bat-body-masses
        )*/
        $needle1 = "https://editors.eol.org/eol_php_code/applications/content_server/resources";
        $needle2 = "http://editors.eol.org/eol_php_code/applications/content_server/resources";
        if(stripos($url, $needle1) !== false || stripos($url, $needle2) !== false) { //string is found --- means this file is stored in eol-archive (editors.eol.org) as EOL resources. Produced by a general connector.    
            $subfolders = str_replace($needle1, "", $info['dirname']);               // e.g. "/eol_traits" OR ""
            $subfolders = str_replace($needle2, "", $subfolders);                    // e.g. "/eol_traits" OR ""
            $actual_file = "/extra/eol_php_resources".$subfolders."/".$info['basename'];  // e.g. /extra/eol_php_resources/eol_traits/bat-body-masses.txt.gz
                                                                                          //      /extra/eol_php_resources/173.tar.gz
            if(file_exists($actual_file)) {
                echo "\nsource: [$actual_file]\n";
                return $actual_file;    
            }
            else self::log_error(array("MISSING: actual_file not found. [$actual_file]", $title, "EOL resources"));
            // MISSING: actual_file not found. [/extra/eol_php_resourceshttps://editors.eol.org/eol_php_code/applications/content_server/resources/368_delta.tar.gz]
        }
        return false;
    }
    private function generate_input_field($p, $r, $resources) //todo loop into resources and have $input for each resource...
    {
        if($val = @$p['license_id']) {
            if($val2 = @$this->license_map[$val]) $p['license_id'] = $val2;
        }

        $input = array();        
        // -------------------------------------------------------------------
        $dates = array();
        // if($val = $p['metadata_created'])   $dates[] = array("start" => $val, "end" => $val, "type" => "Created");
        // if($val = $p['metadata_modified'])  $dates[] = array("start" => $val, "end" => $val, "type" => "Updated");
        if($val = @$r['created']) {
            $val = substr($val,0,10);
            $dates[] = array("start" => $val, "end" => $val, "type" => "Created");
        }
        if($val = @$r['last_modified']) {
            $val = substr($val,0,10);
            $dates[] = array("start" => $val, "end" => $val, "type" => "Updated");
        }
        // -------------------------------------------------------------------
        $creators = array();
        $creators[] = array("name" => "script", "affiliation" => "Zenodo API");
        // -------------------------------------------------------------------
        $contributors = array();
        $creator = "";
        if($val = $p['creator_user_id']) {
            if($user_obj = self::lookup_user_using_id($val)) { //print_r($user_obj);
                if(@$user_obj['result']['sysadmin'] == 'true') $affiliation = "Encyclopedia of Life";
                else                                           $affiliation = "";
                if($creator = @$user_obj['result']['fullname']) {
                    if($email = @$user_obj['result']['email']) $creator .= " ($email)"; //this will only work using curl with Authorization. Won't work with just plain lookup_with_cache().
                    $contributors[] = array("name" => $creator, "affiliation" => $affiliation, "type" => "HostingInstitution");
                }
            }
        }
        // -------------------------------------------------------------------
        $related_identifiers = array();
        if($val = @$r['url']) { //"https://eol.org/data/media_manifest.tgz"
            $this->debug['urls'][$val] = '';
            $parse = parse_url($val);
            $this->debug['urls pathinfo'][$parse['host']] = '';
            $related_identifiers[] = array("relation" => "isSupplementTo", "identifier" => $val, "resource_type" => "dataset");
        }
        // -------------------------------------------------------------------
        if($val = @$p['license_id']) {
            @$this->debug['license_id'][$val]++;
            $license = $val;
        }
        else $license = "notspecified";
        // -------------------------------------------------------------------
        /*Controlled vocabulary:
            * open: Open Access
            * embargoed: Embargoed Access
            * restricted: Restricted Access
            * closed: Closed Access        
        */
        $access_conditions = '';        
        if(self::is_dataset_private_YN($p)) {
            $access_right = 'restricted';
            $access_conditions = 'This is not available publicly. Only community members can see this record.';
        }
        else $access_right = 'open'; //public
        // -------------------------------------------------------------------
        $notes = "";
        if($val = @$r['description']) $notes = $val;
        if($notes) $notes .= "\n".$p['organization']['description'];
        else       $notes = $p['organization']['description'];
        // $notes = addcslashes($notes, "'");
        // $notes = addslashes($notes);
        $notes = str_replace("'", "__", $notes);
        // -------------------------------------------------------------------
        // $p['notes'] = addcslashes($p['notes'], "'");
        // $p['notes'] = addslashes($p['notes']);
        $p['notes'] = str_replace("'", "__", $p['notes']);
        // -------------------------------------------------------------------
        $keywords = array();
        if(count($resources) == 1)  $keywords[] = str_replace("'", "__", $p['organization']['title']); //ditoxAug17;
        else                        $keywords[] = str_replace("'", "__", $p['organization']['title'].": ".$p['title']); //ditoxAug17;
        if($val = @$r['format']) {
            $keywords[] = "format: $val";
            @$this->debug['format'][$val]++;
        }
        //e.g. array("Aggregate Datasets: Images list", "CSV")
        // -------------------------------------------------------------------
        $title = false;
        if($this->organization_id == "eol-content-partners") {
            if(trim($p['title']) == trim($r['name'])) $title = trim($p['title']);
            else { //print_r($p); print_r($r);
                if(trim($r['name'])) $title = trim($p['title'].": ".$r['name']);
                else                 $title = trim($p['title']);
            }
        }
        else $title = trim($p['title'].": ".$r['name']); //orig
        if(!$title) exit("\nwalang title\n");

        /* new
        $title = str_replace("'", "__", $title); //ditoxAug17
        */

        $this->debug['titles'][$title] = '';

        if($parse['host'] != 'editors.eol.org') {
            @$this->debug2['count']++;
            $obj = self::get_deposition_by_title($title); //print_r($obj); exit;
            $zenodo_url = '';
            if($id = @$obj['id']) $zenodo_url = "https://zenodo.org/records/".$id;
            $this->debug2[$parse['host']][] = array('title' => $title, 'URL' => @$r['url'], 'Zenodo' => $zenodo_url);
        }
        // -------------------------------------------------------------------
        $input['metadata'] = array( "title" => $title, //"Images list: image list",
                                    "upload_type" => "dataset", //controlled vocab.
                                    "publication_date" => date("Y-m-d"),
                                    "description" => $p['notes'],
                                    "creators" => $creators, 
                                    "contributors" => $contributors,
                                    //Example: [{'name':'Doe, John', 'affiliation': 'Zenodo'}, 
                                    //          {'name':'Smith, Jane', 'affiliation': 'Zenodo', 'orcid': '0000-0002-1694-233X'}, 
                                    //          {'name': 'Kowalski, Jack', 'affiliation': 'Zenodo', 'gnd': '170118215'}]
                                    "keywords" => $keywords,
                                    // "publication_date" => "2020-02-04", //required. Date of publication in ISO8601 format (YYYY-MM-DD). Defaults to current date.                                                                        
                                    "notes" => $notes, //"For questions or use cases calling for large, multi-use aggregate data files, please visit the EOL Services forum at http://discuss.eol.org/c/eol-services",
                                    "communities" => array(array("identifier" => "eol")), //Example: [{'identifier':'eol'}]
                                    "dates" => $dates, //Example: [{"start": "2018-03-21", "end": "2018-03-25", "type": "Collected", "description": "Specimen A5 collection period."}]
                                    "related_identifiers" => $related_identifiers, 
                                    //Example: [{'relation': 'isSupplementTo', 'identifier':'10.1234/foo'}, {'relation': 'cites', 'identifier':'https://doi.org/10.1234/bar', 'resource_type': 'image-diagram'}]
                                    "access_right" => $access_right, //defaults to 'open'
                                    "access_conditions" => $access_conditions,
                                    "license" => $license,
                            );        
        // print_r($input); //exit("\nstop muna\n");
        return $input;
    }
    private function create_Zenodo_dataset($input)
    {   /* sample: https://developers.zenodo.org/?shell#create
        curl -i -H "Content-Type: application/json" -X POST
        --data '{"metadata": {  "title": "My first upload", 
                                "upload_type": "poster", 
                                "description": "This is my first upload", 
                                "creators": [{"name": "Doe, John", "affiliation": "Zenodo"}]}}' /api/deposit/depositions/?access_token=ACCESS_TOKEN */
        $json = json_encode($input); // echo "\n$json\n";
        $cmd = 'curl -i -H "Content-Type: application/json" -X POST --data '."'$json'".' https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN;
        $cmd = 'curl -s -H "Content-Type: application/json" -X POST --data '."'$json'".' https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN;
        // $cmd .= " 2>&1";
        // echo "\ncreate cmd: [$cmd]\n";
        $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
        $obj = json_decode(trim($json), true);  echo "\n=====created=====\n"; print_r($obj); echo "\n=====created end=====\n";
        return $obj;
        // copied template:
        // $cmd = 'curl -s "'.$url.'" -H "X-Authentication-Token:'.$this->service['token'].'"';
        // $cmd = 'curl --insecure --include --user '.$this->gbif_username.':'.$this->gbif_pw.' --header "Content-Type: application/json" --data @'.$filename.' -s https://api.gbif.org/v1/occurrence/download/request';
        // $output = shell_exec($cmd);
        // echo "\nRequest output:\n[$output]\n";
    }

    function edit_Zenodo_dataset($obj, $data = false) //request to edit a record
    {
        if($edit = @$obj['links']['edit']) { 
            // curl -i -X POST https://zenodo.org/api/deposit/depositions/1234/actions/edit?access_token=ACCESS_TOKEN
            if(!$data) {
                // $cmd = 'curl -s -H -X POST '.$edit.'?access_token='.ZENODO_TOKEN;    //from Eli      - does not work
                // $cmd = 'curl -i -X POST '.$edit.'?access_token='.ZENODO_TOKEN;       //from Zenodo   - does not work
                $cmd = 'curl -s -X POST '.$edit.'?access_token='.ZENODO_TOKEN;          //from Zenodo   - works OK
            }
            else {
                exit("\nDoes not go here.\n");
                $json = json_encode($data); // echo "\n$json\n";
                $cmd = 'curl -s -H "Content-Type: application/json" -X POST  --data '."'$json'".' '.$publish.'?access_token='.ZENODO_TOKEN; //didn't work, param wasn't submitted.
            }
            // $cmd .= " 2>&1";
            // echo "\nedit cmd: [$cmd]\n";
            $json = shell_exec($cmd);                   //echo "\n$json\n";
            $obj = json_decode(trim($json), true);  
            echo "\n=====edit=====\n"; 
            if($this->show_print_r) print_r($obj); 
            echo "\n=====edit end=====\n";
            return $obj;    
        }
        else { print_r($obj); exit("\nNo [edit] link for this object.\n"); }
    }
    function publish_Zenodo_dataset($obj, $data = false)
    {
        if($publish = @$obj['links']['publish']) { //https://zenodo.org/api/deposit/depositions/13136202/actions/publish
            // $cmd = 'curl -i -H "Content-Type: application/json" -X POST https://zenodo.org/api/deposit/depositions/13136202/actions/publish?access_token='.ZENODO_TOKEN;
            //         curl -i                                     -X POST https://zenodo.org/api/deposit/depositions/13635445/actions/publish?access_token=ACCESS_TOKEN
            if(!$data) {
                $cmd = 'curl -s -H "Content-Type: application/json" -X POST '.$publish.'?access_token='.ZENODO_TOKEN; //works OK
                // $cmd = 'curl -i -X POST '.$publish.'?access_token='.ZENODO_TOKEN; //does not work?
                // $cmd = 'curl -s -H POST '.$publish.'?access_token='.ZENODO_TOKEN; //does not work?
            }
            else {
                exit("\nDoes not go here.\n");
                $json = json_encode($data); // echo "\n$json\n";
                $cmd = 'curl -s -H "Content-Type: application/json" -X POST  --data '."'$json'".' '.$publish.'?access_token='.ZENODO_TOKEN; //didn't work, param wasn't submitted.
            }
            // $cmd .= " 2>&1";
            // echo "\npublish cmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n$json\n";
            $obj = json_decode(trim($json), true);  
            echo "\n=====published=====\n"; 
            if($this->show_print_r) print_r($obj); 
            echo "\n=====published end=====\n";
            return $obj;    
        }
    }
    function get_depositions_by_part_title($q, $allVersions = false, $returnNow = false)
    {
        $final = array();
        echo "\nallVersions: [$allVersions]\n";
        // $q = "title:($title)"; //too accepting...
        // $q = "+title:checklists -title:2019 +title:water"; //works splendidly - OK!
        $page_num = 0;
        while(true) { $page_num++;
            if($allVersions) $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&allversions=true&sort=bestmatch&size=25&page='.$page_num.'&q="'.urlencode($q).' -H "Content-Type: application/json"';
            else             $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&sort=bestmatch&size=25&page='.$page_num.'&q="'.urlencode($q).' -H "Content-Type: application/json"';

            $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
            $obj = json_decode(trim($json), true);  //echo "\n=====by title=====\n"; print_r($obj); echo "\n=====by title=====\n"; exit;

            if(!$obj) { return $final;
                // if($allVersions) return;
                // else return self::get_depositions_by_part_title($q, true);
            }
            if(count($obj) == 0) { return $final;
                // if($allVersions) return;
                // else return self::get_depositions_by_part_title($q, true);
            }

            echo "\nneedle: [$q]\n";
            $i = 0;
            foreach($obj as $o) { $i++;
                $id = $o['id'];
                $result_title = $o['metadata']['title'];
                $publication_date = $o['metadata']['publication_date'];
                echo "\n- [$page_num] $i. [$id] [$result_title] [$publication_date]...";
                $final[] = $o;
            }
            if($returnNow) return $final;
            // return $final; //debug only, return the first 25 records only
            // if($page_num >= 3) return; //debug only
        } //end while()
        return $final;
    }
    function get_deposition_by_title($title, $allVersions = false)
    {   echo "\nallVersions: [$allVersions]\n";
        /* 1st option: not quite good        
        $q = "title:($title)";
        */

        // /* 2nd option: very good: from https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
        // curl -X GET "localhost:9200/_search?pretty" -H 'Content-Type: application/json' -d'
        // {
        //   "query": {
        //     "query_string": {
        //       "query": "(new york city) OR (big apple)",
        //       "default_field": "content"
        //     }
        //   }
        // }
        // '

        $arr["query"]["query_string"] = array("query" => "$title", "default_field" => "title"); //orig
        $arr["query"]["query_string"] = array("query" => 'title:("'.$title.'")', "default_field" => "title"); //test
        $q = json_encode($arr);        
        // */

        $page_num = 0;
        while(true) { $page_num++;
            // $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&size=1&page=1&q="'.urlencode($q).' -H "Content-Type: application/json"';

            if($allVersions) $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&allversions=true&sort=bestmatch&size=25&page='.$page_num.'&q="'.urlencode($q).' -H "Content-Type: application/json"';
            else             $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&sort=bestmatch&size=25&page='.$page_num.'&q="'.urlencode($q).' -H "Content-Type: application/json"';

            $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
            $obj = json_decode(trim($json), true);  //echo "\n=====by title=====\n"; print_r($obj); echo "\n=====by title=====\n";

            if(!$obj) {
                if($allVersions) return;
                else {
                    return self::get_deposition_by_title($title, true);
                }
            }
            if(count($obj) == 0) {
                if($allVersions) return;
                else {
                    return self::get_deposition_by_title($title, true);
                }
            }

            // /* loop the results and get the exact match
            echo "\nneedle: [$title]\n";
            $i = 0;
            foreach($obj as $o) { $i++;
                $result_title = $o['metadata']['title'];
                echo "\n- [$page_num] $i. Checking... [$result_title]...";
                if($title == $result_title) {
                    echo "\nFound match: [$result_title]\n"; return $o;
                }
            }
            // */
            // if($page_num >= 3) return;
        } //end while()
    }
    function list_depositions()
    {
        $final = array(); $page = 0;
        while(true) { $page++;
            $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&size=25&page=PAGENUM" -H "Content-Type: application/json"';
            $cmd = str_replace('PAGENUM', $page, $cmd);
            // echo "\nlist depostions cmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
            $obj = json_decode(trim($json), true);  //echo "\n=====\n"; print_r($obj); echo "\n=====\n";
            if(!$obj) break;
            echo "\nNo. of records: ".count($obj)."\n";
            foreach($obj as $o)  {
                $final[trim($o['title'])] = '';
                @$stats[$o['title']]++;
            }
        }
        // asort($stats); print_r($stats); exit("\n-end stats-\n");
        print_r($final);
        $titles = array_keys($this->debug['titles']);
        foreach($titles as $title) {
            $title = trim($title);
            if(!isset($final[$title])) echo "\nTitle not found in Zenodo: [$title]\n";
        }
        // ====================================================
        foreach(array_keys($final) as $zenodo_title) {
            $zenodo_title = trim($zenodo_title);
            if(!isset($this->debug['titles'][$zenodo_title])) echo "\nTitle not found in CKAN: [$zenodo_title]\n";
        }
    }
    function delete_dataset($id)
    {
        $cmd = 'curl -s https://zenodo.org/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN.' -X DELETE';
        echo "\ndelete cmd: [$cmd]\n";
        $json = shell_exec($cmd);               echo "\n--------------------\n$json\n--------------------\n";
        $obj = json_decode(trim($json), true);  echo "\n=====delete=====\n"; print_r($obj); echo "\n=====delete end=====\n";
        return $obj;
    }
    function retrieve_dataset($id, $versionLatestYN = true)
    {
        echo "\nRetrieving ".$id."...\n";
        // $cmd = 'curl -i '.$this->api['domain'].'/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN; //orig from Zenodo, -i more complete output. Not used.
        $cmd = 'curl -s '.$this->api['domain'].'/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN; //better curl output, -s just the json output.
        $cmd .= " 2>&1";
        // echo "\nretrieve cmd: [$cmd]\n";
        $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
        $obj = json_decode(trim($json), true);  
        echo "\n=====retrieve=====\n"; 
        if($this->show_print_r) print_r($obj); 
        echo "\n=====retrieve end=====\n"; 

        // /* ----- new block - works OK if used
        if($versionLatestYN) {} //just proceed below, get the latest version
        else return $obj;
        // ----- */

        /* debug only dev only
        $file = $this->path_2_file_dat . "z_13323232.dat";
        $WRITE = Functions::file_open($file, "w");
        fwrite($WRITE, $json); fclose($WRITE);
        */

        // /* added block to accomodate deposition with multiple versions
        $retrieved_id = $this->retrieve_latest($obj); //exit;
        if($id != $retrieved_id) {
            echo "\n[$id][$retrieved_id] NOT equal IDs. Get latest object...\n";
            $obj = $this->retrieve_dataset($retrieved_id); //exit;
        }
        else echo "\n[$id][$retrieved_id] equal IDs. Current is latest object.\n";
        // */

        return $obj;
    }
    function upload_Zenodo_dataset($obj, $actual_file = false)
    {
        echo "\nUploading ".$obj['id']."...\n";
        if(!$actual_file) {
            self::initialize_file_dat($obj);
            $actual_file = self::get_file_dat_path($obj);
            $basename = $obj['id'].".dat";
        }
        else $basename = pathinfo($actual_file, PATHINFO_BASENAME);

        echo "\nUploading actual file: [".$actual_file."]\n";
        if(file_exists($actual_file)) {
            if($bucket = @$obj['links']['bucket']) { //e.g. https://zenodo.org/api/files/6c1d26b0-7b4a-41e3-a0e8-74cf75710946 // echo "\n[$bucket]\n";
                // $cmd = 'curl --upload-file /path/to/your/file.dat https://zenodo.org/api/files/6c1d26b0-7b4a-41e3-a0e8-74cf75710946/file.dat?access_token='.ZENODO_TOKEN;
                $cmd = 'curl --upload-file '.$actual_file.' '.$bucket.'/'.$basename.'?access_token='.ZENODO_TOKEN;
                // echo "\nupload cmd: [$cmd]\n";
                $json = shell_exec($cmd);               //echo "\n$json\n";
                $obj = json_decode(trim($json), true);  
                echo "\n=====upload=====\n"; 
                if($this->show_print_r) print_r($obj); 
                echo "\n=====upload end=====\n";
                if(self::if_error($obj, 'upload bucket', @$obj['id'])) return false;
                return $obj;    
            }    
        }
    }
    function request_newversion($obj)
    {   echo "\nRequesting newversion ".$obj['id']."...\n";
        if($newversion = @$obj['links']['newversion']) { //e.g. https://zenodo.org/api/deposit/depositions/13268261/actions/newversion
            // curl -i -X POST https://zenodo.org/api/deposit/depositions/1234/actions/newversion?access_token=ACCESS_TOKEN
            $cmd = 'curl -s -X POST '.$newversion.'?access_token='.ZENODO_TOKEN; // echo "\ncmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n----x-----\n$json\n-----x----\n";
            $obj = json_decode(trim($json), true);  
            echo "\n=======newversion=======\n"; 
            if($this->show_print_r) print_r($obj); 
            echo "\n=======newversion end=======\n"; //exit("\nstop: newversion\n");
            if(self::if_error($obj, 'newversion', @$obj['id'])) return false;
            return $obj;    
        }
        else exit("\nERRORx: Cannot get newversion URL! [".$obj['id']."]\n");
    }
    function retrieve_latest($obj)
    {   echo "\nRequesting latest ".$obj['id']."...\n";
        if($latest = @$obj['links']['latest']) { //e.g. https://zenodo.org/api/deposit/depositions/13240083
            // curl -i -X POST https://zenodo.org/api/deposit/depositions/13240083?access_token=ACCESS_TOKEN
            $cmd = 'curl -s -X GET '.$latest.'?access_token='.ZENODO_TOKEN; // echo "\ncmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n----x-----\n$json\n-----x----\n";
            $obj = json_decode(trim($json), true);  
            echo "\n=======latest=======\n"; 
            if($this->show_print_r) print_r($obj); 
            echo "\n=======latest end=======\n"; //exit("\nstop: newversion\n");
            // return $obj;
            /*Array( $obj
                [status] => 301
                [message] => Redirecting...
                [location] => https://zenodo.org/api/records/13629642
            )*/
            // print_r(pathinfo($obj['location']));
            return pathinfo($obj['location'], PATHINFO_BASENAME); //return the id e.g. 13629642
        }
        // else exit("\nERRORx: Cannot get latest URL! [".$obj['id']."]\n");
        echo "\nThis is already the latest ID: [".$obj['id']."]\n";
        return $obj['id'];
    }
    function request_discard($obj)
    {   echo "\nRequesting discard ".$obj['id']."...\n";
        if($discard = @$obj['links']['discard']) { //e.g. https://zenodo.org/api/deposit/depositions/13306865/actions/discard
            // curl -i -X POST https://zenodo.org/api/deposit/depositions/13306865/actions/discard?access_token=ACCESS_TOKEN
            $cmd = 'curl -s -X POST '.$discard.'?access_token='.ZENODO_TOKEN; // echo "\ncmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n----x-----\n$json\n-----x----\n";
            $obj = json_decode(trim($json), true);  echo "\n=======discard=======\n"; print_r($obj); echo "\n=======discard end=======\n"; //exit("\nstop: newversion\n");
            return $obj;    
        }
        else exit("\nERRORx: Cannot discard draft! [".$obj['id']."]\n");
    }
    function update_Zenodo_record($id, $input) //maybe should use PATCH instead of PUT
    {   /*
        curl -i -H "Content-Type: application/json" -X PUT
        --data '{"metadata": {"title": "My first upload", "upload_type": "poster", 
                              "description": "This is my first upload", 
                              "creators": [{"name": "Doe, John", "affiliation": "Zenodo"}]}}' https://zenodo.org/api/deposit/depositions/1234?access_token=ACCESS_TOKEN        
        */

        // $input['metadata'] = array("publication_date" => date("Y-m-d")); //2010-12-30 --- this is needed for publishing a newly uploaded file.
        $json = json_encode($input); // echo "\n$json\n";
        // print_r($input);

        $cmd = 'curl -s -H "Content-Type: application/json" -X PUT --data '."'$json'".' https://zenodo.org/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN;
        // $cmd .= " 2>&1";
        // echo "\n$cmd\n";
        $json = shell_exec($cmd);           //echo "\n$json\n";
        $obj = json_decode(trim($json), true);    
        echo "\n----------update----------\n"; 
        if($this->show_print_r) print_r($obj); 
        echo "\n----------update end----------\n";
        return $obj;
    }
    private function initialize_file_dat($obj)
    {
        $file = self::get_file_dat_path($obj);
        $WRITE = Functions::file_open($file, "w");
        fwrite($WRITE, json_encode($obj));
        fclose($WRITE);
    }
    private function get_file_dat_path($obj)
    {
        return $this->path_2_file_dat . $obj['id'] . ".dat";        
    }
    private function lookup_user_using_id($id)
    {
        if($json = Functions::lookup_with_cache($this->ckan['user_show'].$id, $this->download_options)) {
            $user_obj = json_decode($json, true); //print_r($o); exit;
            /*Array(
                [help] => https://opendata.eol.org/api/3/action/help_show?name=user_show
                [success] => 1
                [result] => Array(
                        [openid] => 
                        [about] => 
                        [display_name] => Jen Hammock
                        [name] => jhammock
                        [created] => 2017-02-06T11:20:32.160756
                        [email_hash] => e9470a1777b5a8165a47b223b48593f9
                        [sysadmin] => 1
                        [activity_streams_email_notifications] => 
                        [state] => active
                        [number_of_edits] => 4753
                        [fullname] => Jen Hammock
                        [id] => 3330d6a7-b9fd-42a9-aad7-8d30b87817c3
                        [number_created_packages] => 483
                    )
            )*/
            return $user_obj;
        }
    }
    private function lookup_package_using_id($id)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //0; //doesn't expire

        // if($id == "0a023d9a-f8c3-4c80-a8d1-1702475cda18") $options['expire_seconds'] = 0;


        $url = $this->ckan['package_show'].$id; //main operation
        // $url = "https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life&include_datasets=true";
        
        // /* working OK but doesn't get private datasets --- private = 1
        if($json = Functions::lookup_with_cache($url, $options)) {
            $o = json_decode($json, true); //print_r($o); exit;
            return $o;
        }
        else {
            // /* this can return private datasets
            $cmd = 'curl '.$url;
            // $cmd .= " -d '".$json."'";
            $cmd .= ' -H "Authorization: '.CKAN_AUTHORIZATION_KEY.'"';
            $json = shell_exec($cmd);
            $o = json_decode($json, true); echo "\n[$cmd]\n"; //print_r($o); //exit;
            return $o;
            // */
        }
        // */
    }
    function test_curl()
    {
        $url = "https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life&include_datasets=true";
        $cmd = 'curl '.$url;
        // $cmd .= " -d '".$json."'";
        $cmd .= ' -s -H "Authorization: '.CKAN_AUTHORIZATION_KEY.'"';
        $json = shell_exec($cmd);
        $o = json_decode($json, true); print_r($o); exit;
        return $o;
    }
    private function deal_with_ckan_urls($url, $parse, $r)
    {   /* [https://opendata.eol.org/dataset/e62665f5-c992-4e18-894f-454a188af411/resource/19ba1335-d661-41bf-be4f-ce2f08f9eac1/download/archive.zip] => 
           [https://opendata.eol.org/dataset/fe8bddda-74e5-44bc-b541-dc197b347d31/resource/a6cd53d7-76ae-484a-9f98-0321072decd0/download/identifierswithimages.csv.gz] => 
        [urls pathinfo] => Array(
                [editors.eol.org] => 
                [opendata.eol.org] => 
                [www.dropbox.com] => 
                [eol.org] => 
        )*/
        // print_r($r);
        if($parse['host'] == 'opendata.eol.org') {
            // print_r($r); print_r($parse); exit("\n$url\n");
            self::duplicate_actual_file_from_url($r, $url);
        }
        else { //the rest
        }
    }
    private function duplicate_actual_file_from_url($r, $url)
    {   /*
        https://opendata.eol.org/dataset/fe8bddda-74e5-44bc-b541-dc197b347d31/resource/a6cd53d7-76ae-484a-9f98-0321072decd0/download/identifierswithimages.csv.gz
        generate actual file: identifierswithimages.csv.gz  
        $ cp /extra/ckan_resources/a6c/d53/d7-76ae-484a-9f98-0321072decd0 /extra/ckan_resources/a6c/d53/identifierswithimages.csv.gz
        OS path:    cd /extra/ckan_resources/a6c/d53/d7-76ae-484a-9f98-0321072decd0
        www path:   cd /var/www/html/uploaded_resources/a6c/d53/d7-76ae-484a-9f98-0321072decd0
                    https://editors.eol.org/uploaded_resources/a6c/d53/identifierswithimages.csv.gz
        */
        // print_r($r); exit("\n$url\n");
        $id = $r['id'];
        $basename = pathinfo($url, PATHINFO_BASENAME); //e.g. dh21.zip
        $folder_1 = substr($id, 0, 3);
        $folder_2 = substr($id, 3, 3);
        $file = trim(substr($id, 6, strlen($id)));
        $source = "/extra/ckan_resources/$folder_1/$folder_2/$file";
        $destination = "/extra/ckan_resources/$folder_1/$folder_2/$basename";
        $new_url = "https://editors.eol.org/uploaded_resources/$folder_1/$folder_2/$basename";

        echo "\nsource: [$source]\n";
        echo "\ndestination: [$destination]\n";

        // if(true) {
        if(is_file($source)) {
            if(!is_file($destination)) {
                $cmd = "cp $source $destination"; echo "\n[$cmd --- Copying...]\n";
                shell_exec($cmd); //main operation
                if(is_file($destination)) {
                    echo "\n[$cmd --- Copied OK]\n";
                    shell_exec("chmod 775 $destination"); //main operation
                    self::UPDATE_ckan_resource($r, $new_url);
                    $this->temp_count++;
                }
            }
            else { //seems the actual file already generated but the URL was not updated
                echo "\n[($destination) COPIED already.]\n";
                self::UPDATE_ckan_resource($r, $new_url);
                echo "\n xx old url: $url";
                echo "\n xx new_url: $new_url\n";    
                // exit("\neli muna\n");
            }
            echo "\nold url: $url";
            echo "\nnew_url: $new_url\n";
        }
        elseif(is_file($destination)) echo "\nCopied already.\n";
        else {
            print_r($r);
            echo "\nFile not found. Investigate.\n";
            @$this->debug['File not found. Investigate.']++;
            exit("\nFile not found. Investigate.\n"); //main operation
        }

        // if($this->temp_count >= 10) exit("\nstop muna 2\n"); //debug only
    }
    private function UPDATE_ckan_resource($r, $new_url) //https://docs.ckan.org/en/ckan-2.7.3/api/        COPIED TEMPLATE from TraitDataImportAPI.php
    {
        $ckan_resource_id = $r['id'];
        $rec = array();
        
        // /* ---------- for Zenodo CKAN resource update ---------- start
        $rec['id'] = $ckan_resource_id; //e.g. a4b749ea-1134-4351-9fee-ac1e3df91a4f
        $rec['clear_upload'] = "true"; //comment this line once new CKAN is installed.
        $rec['url_type'] = ""; //orig value here is 'upload', which will be replaced by just blank ""
        $rec['url'] = $new_url;
        // ---------- for Zenodo CKAN resource update ---------- end */

        /* not needed in Zenodo task
        $rec['state'] = 'active';
        $rec['package_id'] = $r['package_id']; //"trait-spreadsheet-repository"; // https://opendata.eol.org/dataset/trait-spreadsheet-repository
        if($val = @$this->arr_json['Short_Desc']) $rec['name'] = $val;
        $rec['description'] = "Updated: ".date("Y-m-d h:i:s A");
        $rec['format'] = "Darwin Core Archive";
        */
        $json = json_encode($rec);
        
        // /* for old CKAN
        // $cmd = 'curl https://opendata.eol.org/api/3/action/resource_update'; // orig but not used for Zenodo.
        $cmd = 'curl https://opendata.eol.org/api/3/action/resource_patch';     // those fields not updated will remain unchanged
        $cmd .= " -d '".$json."'";
        $cmd .= ' -H "Authorization: '.CKAN_AUTHORIZATION_KEY.'"';
        // */

        sleep(5); //may need to delay since there are many records involved
        $output1 = shell_exec($cmd);            echo "\n$output1\n";
        $output2 = json_decode($output1, true); print_r($output2);
        if($output2['success'] == 1) echo "\nOpenData resource UPDATE OK.\n";
        else{
            echo "\n-----------\n"; echo($output1); echo "\n-----------\n";        
            echo "\n-----------\n"; print_r($output2); echo "\n-----------\n";        
            echo "\nERRORx: OpenData resource UPDATE failed xxx.\n";
        }
        /*Array(
            [help] => https://opendata.eol.org/api/3/action/help_show?name=resource_update
            [success] => 1
            [result] => Array(
                    [cache_last_updated] => 
                    [cache_url] => 
                    [mimetype_inner] => 
                    [hash] => hash-cha_02
                    [description] => Updated: 2022-02-03 05:36
                    [format] => Darwin Core Archive
                    [url] => http://localhost/eol_php_code/applications/content_server/resources/Trait_Data_Import/cha_02.tar.gz
                    [created] => 2022-02-03T01:40:54.782481
                    [state] => active
                    [webstore_last_updated] => 
                    [webstore_url] => 
                    [package_id] => dab391f0-7ec0-4055-8ead-66b1dea55f28
                    [last_modified] => 
                    [mimetype] => 
                    [url_type] => 
                    [position] => 1
                    [revision_id] => 3c3f2587-c0b3-4fdd-bb5e-c6ae23d79afe
                    [size] => 
                    [id] => a4b749ea-1134-4351-9fee-ac1e3df91a4f
                    [resource_type] => 
                    [name] => Fishes of Philippines
                )
        )*/
        // echo "\n$output\n";
    }
    private function is_dataset_private_YN($p)
    {
        if($p['private'] == 'true' || $p['private'] == 1 || $p['private'] == '1') return true;  //private
        if($p['private'] == 'false' || $p['private'] == '') return false;                       //public
    }
    function get_all_Zenodo_licence_IDs()
    {   $url = "https://zenodo.org/api/vocabularies/licenses?page=1&size=500&sort=title";
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $o = json_decode($json, true); //print_r($o); exit;
            foreach($o['hits']['hits'] as $rec) {
                $final[$rec['id']] = '';
            }
            return $final;
            $final = array_keys($final);
            asort($final);
            print_r($final);
            echo "\ntotal licenses: ".count($final)."\n";
            exit;
            return $o;
        }
        else echo "\nInvestigate: Cannot lookup: [$url]\n";
    }
    private function check_license_values()
    {
        $zenodo_licenses = self::get_all_Zenodo_licence_IDs();
        if($val = @$this->debug['license_id']) {
            $opendata_licenses = array_keys($val);
            foreach($opendata_licenses as $ol) {
                if(isset($zenodo_licenses[$ol])) @$ret['license_found'][$ol] = '';
                else                             @$ret['license_not_found'][$ol] = '';
            }
            print_r($ret);
        }
    }
    function list_all_datasets($sought_privateYN = 1)
    {
        if($json = Functions::lookup_with_cache($this->ckan['organization_list'], $this->download_options)) {
            $o = json_decode($json, true); //print_r($o);
            $i = 0; $e = 0;
            foreach($o['result'] as $organization_id) { $i++;
                // if($organization_id != 'encyclopedia_of_life') continue; //Aggregate Datasets //debug only dev only
                // echo "\n" . $organization_id;

                $url = str_replace('ORGANIZATION_ID', $organization_id, $this->ckan['organization_show']);
                // $url = "http://localhost/other_files2/Zenodo_files/json/encyclopedia_of_life.json";
                $url = "http://localhost/other_files2/Zenodo_files/json/".$organization_id.".json";
                $url = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/Zenodo/json/".$organization_id.".json";

                if($json = Functions::lookup_with_cache($url, $this->download_options)) {
                    $o = json_decode($json, true); //print_r($o); exit;
                    if(!$o['success']) exit("\nWhy not successfull\n");
                    $org_title_name = $o['result']['title'].": (".$o['result']['name'].")";
                    echo "\n$i. $org_title_name";

                    $final[$org_title_name]['dataset_count'] = $o['result']['package_count'];
                    foreach($o['result']['packages'] as $p) {
                        $package_title_name = $p['title'].": ".$p['name'];
                        if($sought_privateYN)   $label = 'private datasets';
                        else                    $label = 'public datasets';
                        if($p['private'] == $sought_privateYN) {
                            $final[$org_title_name][$label][$package_title_name] = $p['id'];

                            // /* loop to each of the resources of a package
                            $package_obj = self::lookup_package_using_id($p['id']);
                            if($resources = @$package_obj['result']['resources']) {
                                foreach($resources as $r) { //print_r($p); print_r($r);
                                    echo "\n -------------- start resource -------------- \n";
                                    if($val = @$r['url']) { $e++; //"https://eol.org/data/media_manifest.tgz"
                                        $this->debug['urls'][$val] = '';
                                        $parse = parse_url($val);
                                        $this->debug['urls pathinfo'][$parse['host']] = '';
                                        // /* main operation
                                        self::deal_with_ckan_urls($val, $parse, $r);
                                        // */
                                    }
                                    // print_r($r); exit("\na resource object\n");
                                    echo "\n -------------- end resource -------------- \n";
                                }    
                            }
                            // */
                        }
                    }
                }
                // break; //debug only
                // if($e >= 10) break; //debug only
            }
        }
        echo "\n\n"; print_r($final); //stats report
        print_r($this->debug);
    }
    function if_error($o, $what, $what2)
    {
        if(@$o['status'] > 204) {
            $json_error = json_encode($o);
            $err_msg = "ERRORx: ($what) ($what2) [".$json_error."]";
            echo "\n--- $err_msg ---\n"; print_r($o);
            $this->debug['zenodo errors'][$err_msg] = '';
            self::log_error(array("ERROR", $what, $what2, $json_error));
            return true;
        }
        else {
            /* This is very wrong!!! Never un-comment this.
            if(@$o['id'] && @$o['metadata']['title']) {} //no error; will return false below
            else {
                echo "\nERROR Zenodo: [$what] [$what2] operation failed.\n";
                print_r($o);
                self::log_error(array("ERROR: [$what] [$what2] operation failed."));
                return true;
            }
            */
        }
        return false;
    }
    function log_error($arr)
    {
        if($this->divide_and_conquer) $arr[] = "[".$this->divide_and_conquer."]";
        $arr[] = date("Y-m-d h:i:s A");
        if(!($file = Functions::file_open($this->log_file, "a"))) return;
        fwrite($file, implode("\t", $arr)."\n");
        fclose($file);
    }
    function access_json_reports() //generates the HTML page
    {   /*
        $this->report['EOL Dynamic Hierarchy Data Sets']    = $this->path_2_file_dat . "dynamic-hierarchy.json";
        $this->report['Aggregate Datasets']                 = $this->path_2_file_dat . "encyclopedia_of_life.json";
        $this->report['EOL Content Partners']               = $this->path_2_file_dat . "eol-content-partners.json";
        $this->report['Legacy datasets']                    = $this->path_2_file_dat . "legacy-datasets.json";
        $this->report['WikiData Trait Reports']             = $this->path_2_file_dat . "wikidata-trait-reports.json";
        */
        if(!($file = Functions::file_open($this->html_report, "w"))) return;
        $organizations = array_keys($this->report); asort($organizations);

        // /* put here a Table of contents, showing organizations that will jump below...
        fwrite($file, "<a name='TOC'><br>Organizations:");
        fwrite($file, "<ul>");
        foreach($organizations as $org_name) {
            fwrite($file, "<li><a href='#$org_name'>$org_name</a></li>");
        }
        fwrite($file, "</ul>");
        // */

        foreach($organizations as $org_name) {
            $json = file_get_contents($this->report[$org_name]);
            $arr = json_decode($json, true); //print_r($arr); exit;
            if($arr = @$arr['main_report']) {}
            else continue;
            $orgs = array_keys($arr);
            $org_name = $orgs[0];

            $tmp = $arr[$org_name]; 
            $datasets = array_keys($tmp); asort($datasets); //print_r($datasets);

            fwrite($file, "<a name='$org_name'>");
            fwrite($file, "<hr>\n");
            fwrite($file, "Organization: <b>$org_name</b> 
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small>Datasets: ".count($datasets)."</small>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <small><a href='#TOC'>Back to top</a></small>\n");
            fwrite($file, "<hr>\n");
            $i = 0;
            foreach($datasets as $dataset) { $i++;
                fwrite($file, "<br>Dataset $i: <b>$dataset</b>\n");                
                $tmp2 = $tmp[$dataset];
                $resources = array_keys($tmp2); asort($resources);
                // print_r($tmp2); exit;

                fwrite($file, "<ul>");
                foreach($resources as $resource) {
                    fwrite($file, "<li><a target='".$resource."' href='https://zenodo.org/records/$tmp2[$resource]'>$resource</a></li>\n");
                    @$final[$org_name]++;
                }
                // fwrite($file, "<br>");
                fwrite($file, "</ul>");

            }
            // exit;
        }
        $json = json_encode($final);
        fwrite($file, "<hr>"); fwrite($file, $json); fwrite($file, "<hr>");
        fclose($file);
    }
    private function generate_title_id_info($organization_id)
    {
        $json = file_get_contents($this->report[$this->org_name[$organization_id]]);
        $arr = json_decode($json, true); //print_r($arr); exit;
        if($arr = @$arr['main_report']) {}
        else exit("\njson file not found [$organization_id]\n");
        // print_r($arr); exit("\nelix 2\n");
        foreach($arr as $org_name => $arr_datasets) {
            foreach($arr_datasets as $dataset_name => $arr_resources) {
                foreach($arr_resources as $resource_name => $obj_id) $final[$resource_name] = $obj_id;
            }
        }
        // print_r($final); exit;
        return $final;
    }
    /*
    $arr = array("EOL Dynamic Hierarchy: Dynamic Hierarchy Version 1.1",  
        "EOL Dynamic Hierarchy: EOL Dynamic Hierarchy Index",  
        "EOL Dynamic Hierarchy: DH223test.zip",  
        "Amphibian Species of the World (ASW) - obsolete: Frost, Darrel R. 2018. Amphibian Species of the World", 
        "IOC World Bird List (IOC) - active: IOC World Bird List",  
        "IOC World Bird List (IOC) - active: IOC World Bird List with higherClassification",
        "Catalogue of Life: Catalogue of Life 2018-03-28",  
        "Catalogue of Life: Catalog of Life Protists", 
        "Catalogue of Life: Catalog of Life Extract for DH (TRAM-797)", 
        "Catalogue of Life: Catalog of Life Protists (20 Feb 2019 dump)", 
        "Catalogue of Life: Catalog of Life for DH (20 Feb 2019 dump)", 
        "Catalogue of Life: Catalogue of Life extract for DH2", 
        "Catalogue of Life Collembola: CoL 2020-08-01 Collembola", 
        "NCBI Taxonomy Harvest (TRAM-795): NCBI_Taxonomy_Harvest.tar.gz", 
        "NCBI Taxonomy Harvest (TRAM-795): NCBI_Taxonomy_Harvest_no_vernaculars.tar.gz", 
        "NCBI Taxonomy for DH (TRAM-796): NCBI Taxonomy for DH (TRAM-796)",  
        "ICTV Virus Taxonomy (ICTV) - active: ICTV-virus_taxonomy-with-higherClassification.tar.gz",
        "User Generated Content (EOL v2): user-preferred comnames",
        "User Generated Content (EOL v2): user-added comnames", 
        "User Generated Content (EOL v2): user added text", 
        "User Generated Content (EOL v2): curation of media objects", 
        "User Generated Content (EOL v2): user comments", 
        "User Generated Content (EOL v2): user image cropping", 
        "User Generated Content (EOL v2): user image ratings",
        "User Generated Content (EOL v2): user exemplar images",
        "User Generated Content (EOL v2): user activity collections", 
        "User Generated Content (EOL v2): user activity collections (json format)", 
        "User Generated Content (EOL v2): taxonomic propagation - exemplar images", 
        "User Generated Content (EOL v2): taxonomic propagation - image ratings", 
        "WikiData: wikidata_hierarchy.tar.gz", 
        "All trait data: All trait data", 
        "Representative records: representative records", 
        "EOL Stats for species level pages: EOL stats for species-level pages");
    print_r($arr);
    echo "\n".count($arr)."\n";
    */
}
?>