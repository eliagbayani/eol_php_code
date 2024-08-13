<?php
namespace php_active_record;
/* 1st client: zenodo.php
docs:   https://developers.zenodo.org/?shell#representation
        https://help.zenodo.org/docs/deposit/manage-files/

Use this when searching a title in Zenodo. Paste this in the search textbox:
title:("EOL Dynamic Hierarchy: DH223test.zip")
*/
class ZenodoAPI
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
        if(Functions::is_production()) $this->path_2_file_dat = '/extra/other_files/Zenodo/';
        else                           $this->path_2_file_dat = '/Volumes/OWC_Express/other_files/Zenodo/';
        if(!is_dir($this->path_2_file_dat)) mkdir($this->path_2_file_dat);
        $this->log_file = $this->path_2_file_dat . "error_log.tsv";

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

        /* not helpful since Authorization is required regardless of user-agent
        https://www.whatismybrowser.com/detect/what-is-my-user-agent/
        */        
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
                
                // /* main operation
                if(in_array($organization_id, array('encyclopedia_of_life', 'dynamic-hierarchy', 'legacy-datasets'))) continue; //migrated public datasets already //main operation
                if(in_array($organization_id, array('encyclopedia_of_life', 'dynamic-hierarchy', 'legacy-datasets'))) continue; //uploaded actual files already
                // */

                // if(!in_array($organization_id, array('encyclopedia_of_life', 'dynamic-hierarchy'))) continue; //dev only
                // if($organization_id != 'encyclopedia_of_life') continue; //Aggregate Datasets //debug only dev only
                // if($organization_id != 'dynamic-hierarchy') continue; //xxx //debug only dev only
                // if($organization_id != 'legacy-datasets') continue; //xxx //debug only dev only
                // if($organization_id != 'wikidata-trait-reports') continue; //xxx //debug only dev only
                if($organization_id != 'eol-content-partners') continue; //xxx //debug only dev only

                echo "\norganization ID: [$organization_id]\n";
                self::process_organization($organization_id);
            }
        }
        print_r($this->debug);
        self::check_license_values();
        $sum = 0; foreach($this->debug['license_id'] as $n) $sum += $n; echo "\nlicense_id: [$sum]\n";
        echo "\ntotal resources: [".$this->debug['total resources']."]\n";
        echo "\ntotal resources migrated: [".@$this->debug['total resources migrated']."]\n";
        // self::list_depositions(); //utility -- check if there are records in CKAN that are not in Zenodo yet.
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

        $url = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/Zenodo/json/".$organization_id.".json"; //main operation

        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $o = json_decode($json, true); //print_r($o);
            echo "\npackage_count: ".$o['result']['package_count']."\n";
            foreach($o['result']['packages'] as $p) {

                // if(in_array($p['title'], array("Images list", "Vernacular names", "FishBase", "EOL Stats for species level pages"))) continue; //main operation

                // /* dev only --- force limit the loop
                // if($p['title'] != 'Images list') continue; //debug only dev only
                // if($p['title'] != 'Vernacular names') continue; //debug only dev only
                // if($p['title'] != 'EOL computer vision pipelines') continue; //debug only dev only
                // if($p['title'] != 'EOL Stats for species level pages') continue; //debug only dev only
                // */

                // /* UN-COMMENT for PUBLIC datasets.
                if(self::is_dataset_private_YN($p)) continue;   //private --- waiting for Jen to cherry-pick those to include to migrate to Zenodo
                else {}                                         //public --- the rest will be processed    
                // */

                /* UN-COMMENT for PRIVATE datasets.
                if(self::is_dataset_private_YN($p)) {}   //private
                else continue;                           //public    
                */

                // print_r($p); //exit;
                self::process_a_package($p); //main operation
            }
        }
    }
    private function process_a_package($p) //process a dataset
    {   // print_r($p);
        echo "\nnum_resources: ".$p['num_resources']."\n";
        // loop to each of the resources of a package
        $package_obj = self::lookup_package_using_id($p['id']);
        if($resources = @$package_obj['result']['resources']) {
            $this->debug['total resources'] += count($resources);
            foreach($resources as $r) { // print_r($r);
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

                $input = self::generate_input_field($p, $r, $resources); //main operation
                $title = $input['metadata']['title'];

                // if(in_array($title, array("Vernacular names: vernacular names, May 2020", "Identifiers with Images (EOL v2): identifiers_with_images.csv.gz", "User Generated Content (EOL v2): User Added Text, curated"))) continue; //ckan file already uploaded
                // if(in_array($title, array("early exports: 2019, August 22"))) continue;                                 //done -- migrated completely* Legacy datasets
                // if(in_array($title, array("EOL Hierarchy Entries April 2017: Hierarchy Entries April 2017"))) continue; //done -- migrated completely* Legacy datasets
                if(in_array($title, array("FishBase: FishBase"))) continue; //migrated already
                if(in_array($title, array("FishBase"))) continue; //migrated already
                if(in_array($title, array("Paleobiology Database (PBDB): PBDB (368) in DwCA"))) continue; //migrated already
                if(in_array($title, array("DiscoverLife: Discoverlife Maps"))) continue; //migrated already
                if(in_array($title, array("van Tienhoven, 2003: van Tienhoven, A. 2003"))) continue; //migrated already

                // ============ dev only
                // if(!in_array($title, array("Publications using EOL structured data: 2015-2017"))) continue;
                // if(!in_array($title, array("Publications using EOL structured data: 2020"))) continue;
                // if(!in_array($title, array("Publications using EOL structured data: 2019"))) continue;
                // if(!in_array($title, array("Publications using EOL structured data: 2018"))) continue;
                // if(!in_array($title, array("xxx"))) continue;
                
                /* ---------- block of code --- only accept "http:" not "https:"
                if($url = @$input['metadata']['related_identifiers'][0]['identifier']) {
                    // $needle = "http://editors.eol.org/eol_php_code/applications/content_server/resources";
                    $needle = "http://editors.eol.org";
                    if(stripos($url, $needle) !== false) {} //string is found 
                    else continue;
                }
                else continue;
                ---------- */

                // /* ===== divide and conquer
                @$this->divide_and_conquer++;
                $m = $this->divide_and_conquer;
                if(    $m >= 1 && $m <= 5)  continue; //done
                elseif($m > 5  && $m <= 20) continue; //done
                elseif($m > 20 && $m <= 60) continue;
                else continue;
                // ===== */

                print_r($input); //exit("\nfirst occurrence\n");
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
            echo "\n[$url]\n"; print_r(pathinfo($url));
            $needle1 = "https://editors.eol.org/";
            $needle2 = "http://editors.eol.org/";
            if(stripos($url, $needle1) !== false || stripos($url, $needle2) !== false) { //string is found
                $arr = explode("/", $url);
                // print_r($arr);
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
        else echo "\nERROR: newversion object not created!\n";
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
                    else echo "\nERROR: newversion object not created!\n";
                }
                else echo "\nNo file uploaded. File does not exist. [$actual_file]\n";
            }
            else echo "\nNot a CKAN URL [$url].\n";
        }
        */
        // exit("\n--stop muna ditox--\n");
    }
    function start_Zenodo_process($input)
    {
        $title_x = $input['metadata']['title'];
        $notes_x = $input['metadata']['notes'];
 
        echo "\nPause 7 seconds...\n"; sleep(7);
        $create_obj = self::create_Zenodo_dataset($input);
        if(self::if_error($create_obj, 'create', $title_x)) {}
        else {
            if($id = $create_obj['id']) {
                $obj = self::retrieve_dataset($id); //works OK
                if(self::if_error($obj, 'retrieve', $id)) {}
                else {

                    // /*
                    if($url = @$obj['metadata']['related_identifiers'][0]['identifier']) {}
                    else { self::log_error(array("ERROR", "No URL, should not go here.", $obj['id'])); return; }
                    if($actual_file = self::is_ckan_uploaded_file($url, $title_x))        $upload_obj = self::upload_Zenodo_dataset($obj, $actual_file);  //uploads actual file
                    elseif($actual_file = self::is_editors_other_files($url, $title_x))   $upload_obj = self::upload_Zenodo_dataset($obj, $actual_file);  //uploads actual file
                    elseif($actual_file = self::is_editors_eol_resources($url, $title_x)) $upload_obj = self::upload_Zenodo_dataset($obj, $actual_file);  //uploads actual file
                    else                                                        $upload_obj = self::upload_Zenodo_dataset($obj);                //uploads .dat file
                    // */

                    if(self::if_error($upload_obj, 'upload', $obj['id'])) {}
                    else {
                        $obj = self::retrieve_dataset($id); //works OK
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
        else { //public
            $access_right = 'open';
        }
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
        if(count($resources) == 1)  $keywords[] = $p['organization']['title'];
        else                        $keywords[] = $p['organization']['title'].": ".$p['title'];
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
        $this->debug['titles'][$title] = '';
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
        $obj = json_decode(trim($json), true);  echo "\n=====c=====\n"; print_r($obj); echo "\n=====c=====\n";
        return $obj;
        // copied template:
        // $cmd = 'curl -s "'.$url.'" -H "X-Authentication-Token:'.$this->service['token'].'"';
        // $cmd = 'curl --insecure --include --user '.$this->gbif_username.':'.$this->gbif_pw.' --header "Content-Type: application/json" --data @'.$filename.' -s https://api.gbif.org/v1/occurrence/download/request';
        // $output = shell_exec($cmd);
        // echo "\nRequest output:\n[$output]\n";
    }
    private function publish_Zenodo_dataset($obj, $data = false)
    {
        if($publish = @$obj['links']['publish']) { //https://zenodo.org/api/deposit/depositions/13136202/actions/publish
            // $cmd = 'curl -i -H "Content-Type: application/json" -X POST https://zenodo.org/api/deposit/depositions/13136202/actions/publish?access_token='.ZENODO_TOKEN;
            // $cmd = 'curl -i -H "Content-Type: application/json" -X POST '.$publish.'?access_token='.ZENODO_TOKEN;
            if(!$data) $cmd = 'curl -s -H "Content-Type: application/json" -X POST '.$publish.'?access_token='.ZENODO_TOKEN;
            else {
                $json = json_encode($data); // echo "\n$json\n";
                $cmd = 'curl -s -H "Content-Type: application/json" -X POST  --data '."'$json'".' '.$publish.'?access_token='.ZENODO_TOKEN; //didn't work, param wasn't submitted.
            }
            // $cmd .= " 2>&1";
            // echo "\npublish cmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n$json\n";
            $obj = json_decode(trim($json), true);  print_r($obj);
            return $obj;    
        }
    }
    function get_deposition_by_title($title)
    {
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
        $arr["query"]["query_string"] = array("query" => $title, "default_field" => "title");
        $q = json_encode($arr);        
        // */

        $page_num = 0;
        while(true) { $page_num++;
            // $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&size=1&page=1&q="'.urlencode($q).' -H "Content-Type: application/json"';
            $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&sort=bestmatch&size=25&page='.$page_num.'&q="'.urlencode($q).' -H "Content-Type: application/json"';
            $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
            $obj = json_decode(trim($json), true);  //echo "\n=====by title=====\n"; print_r($obj); echo "\n=====by title=====\n";

            if(!$obj) return;
            if(count($obj) == 0) return;

            // /* loop the results and get the exact match
            echo "\nneedle: [$title]\n";
            $i = 0;
            foreach($obj as $o) { $i++;
                $result_title = $o['metadata']['title'];
                echo "\n- [$page_num] $i. testing title results: [$result_title]...";
                if($title == $result_title) {
                    echo "\nFound match: [$result_title]\n"; return $o;
                }
            }
            // */
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
            echo "\n".count($obj)."\n";
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
        $obj = json_decode(trim($json), true);  echo "\n=====delete=====\n"; print_r($obj); echo "\n=====delete=====\n";
        return $obj;
    }
    function retrieve_dataset($id)
    {
        echo "\nRetrieving ".$id."...\n";
        // $cmd = 'curl -i '.$this->api['domain'].'/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN; //orig from Zenodo, -i more complete output. Not used.
        $cmd = 'curl -s '.$this->api['domain'].'/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN; //better curl output, -s just the json output.
        $cmd .= " 2>&1";
        // echo "\nretrieve cmd: [$cmd]\n";
        $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
        $obj = json_decode(trim($json), true);  //echo "\n=====retrieve=====\n"; print_r($obj); echo "\n=====retrieve=====\n";
        return $obj;
    }
    private function upload_Zenodo_dataset($obj, $actual_file = false)
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
                $obj = json_decode(trim($json), true);  //echo "\n=====upload=====\n"; print_r($obj); echo "\n=====upload=====\n";
                return $obj;    
            }    
        }
    }
    private function request_newversion($obj)
    {   echo "\nRequesting newversion ".$obj['id']."...\n";
        if($newversion = @$obj['links']['newversion']) { //e.g. https://zenodo.org/api/deposit/depositions/13268261/actions/newversion
            // curl -i -X POST https://zenodo.org/api/deposit/depositions/1234/actions/newversion?access_token=ACCESS_TOKEN
            $cmd = 'curl -s -X POST '.$newversion.'?access_token='.ZENODO_TOKEN; // echo "\ncmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n----x-----\n$json\n-----x----\n";
            $obj = json_decode(trim($json), true);  echo "\n=======newversion=======\n"; print_r($obj); echo "\n=======newversion=======\n"; //exit("\nstop: newversion\n");
            return $obj;    
        }
        else exit("\nERROR: Cannot get newversion URL! [".$obj['id']."]\n");
    }
    private function request_discard($obj)
    {   echo "\nRequesting discard ".$obj['id']."...\n";
        if($discard = @$obj['links']['discard']) { //e.g. https://zenodo.org/api/deposit/depositions/13306865/actions/discard
            // curl -i -X POST https://zenodo.org/api/deposit/depositions/13306865/actions/discard?access_token=ACCESS_TOKEN
            $cmd = 'curl -s -X POST '.$discard.'?access_token='.ZENODO_TOKEN; // echo "\ncmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n----x-----\n$json\n-----x----\n";
            $obj = json_decode(trim($json), true);  echo "\n=======discard=======\n"; print_r($obj); echo "\n=======discard=======\n"; //exit("\nstop: newversion\n");
            return $obj;    
        }
        else exit("\nERROR: Cannot discard draft! [".$obj['id']."]\n");
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
        $obj = json_decode(trim($json), true);    echo "\n----------update----------\n"; print_r($obj); echo "\n----------update----------\n";
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
        // $options['expire_seconds'] = 0; //doesn't expire

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
            $cmd .= ' -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"';
            $json = shell_exec($cmd);
            $o = json_decode($json, true); //print_r($o); exit;
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
        $cmd .= ' -s -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"';
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
        $cmd .= ' -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"';
        // */

        sleep(5); //may need to delay since there are many records involved
        $output1 = shell_exec($cmd);            echo "\n$output1\n";
        $output2 = json_decode($output1, true); print_r($output2);
        if($output2['success'] == 1) echo "\nOpenData resource UPDATE OK.\n";
        else{
            echo "\n-----------\n"; echo($output1); echo "\n-----------\n";        
            echo "\n-----------\n"; print_r($output2); echo "\n-----------\n";        
            echo "\nERROR: OpenData resource UPDATE failed xxx.\n";
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
    private function if_error($o, $what, $what2)
    {
        if(@$o['status'] > 204) {
            $json_error = json_encode($o);
            $err_msg = "ERROR: ($what) ($what2) [".$json_error."]";
            echo "\n--- $err_msg ---\n"; print_r($o);
            $this->debug['zenodo errors'][$err_msg] = '';
            self::log_error(array("ERROR", $what, $what2, $json_error));
            return true;
        }
        else return false;
    }
    private function log_error($arr)
    {
        $arr[] = date("Y-m-d h:i:s A");
        if(!($file = Functions::file_open($this->log_file, "a"))) return;
        fwrite($file, implode("\t", $arr)."\n");
        fclose($file);
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