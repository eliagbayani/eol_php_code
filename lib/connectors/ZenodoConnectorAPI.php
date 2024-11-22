<?php
namespace php_active_record;
/**/
class ZenodoConnectorAPI
{
    function __construct($folder = null, $query = null)
    {}
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ start @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    function jen_Deprecated_Works()
    {
        $this->log_error(array("==================== Log starts here ====================Deprecated tasks"));

        /* ---------- start: for Related Works - iSourceOf relationship
        self::build_EOL_resourceID_and_Zenodo_ID_info(); //exit("\nstop3\n");
        ---------- end: */

        // /* start: main operation
        /* ---------- start: normal
        $i = 0; $hits = 0;
        foreach($final as $zenodo_id => $url) { $i++;
            echo "\nprocessing $i ... [$zenodo_id]\n";

            // do batches
            if($i < 707) continue;
            elseif($i >= 707 && $i <= 962) {}
            elseif($i > 962) break;
            else continue;

            // echo "\nprocessing $i ... [$zenodo_id]\n"; exit; //debug only
            if($zenodo_id && $url) { $hits++; sleep(2);
                $this->record_in_question = array('identifier' => $url, 'relation' => 'isSourceOf', 'resource_type' => 'dataset', 'scheme' => 'url');
                self::update_zenodo_record_of_latest_requested_changes($zenodo_id);
                // break; //debug only - run only the 1st hit
                // if($hits >= 2) break; //debug only
            }
        }
        exit("\n- end Related Works -\n");
        ---------- end: normal */

        // /* ---------- start: dev only
        $id = 13313293; //[13313293] [National Checklists: Turkmenistan]...
        self::update_zenodo_record_of_latest_requested_changes($id);
        exit("\n-----end per taxon, during dev-----\n");
        // ---------- end: dev only */

        // ----- end: main operation */
    }
    function jen_Related_Works()
    {
        $this->log_error(array("==================== Log starts here ====================Related Works"));

        // /* ---------- start: for Related Works - iSourceOf relationship
        self::build_EOL_resourceID_and_Zenodo_ID_info(); //exit("\nstop3\n");
        // ---------- end: */

        // /* start: main operation
        // [87794797-6169-4935-908c-c304ed594875] => Array(
        //             [name] => Panama Species List
        //             [id] => 196
        //             [status] => published
        //             [content_id] => 285
        //             [opendata_id] => 87794797-6169-4935-908c-c304ed594875
        //         )
        // [87794797-6169-4935-908c-c304ed594875] => Array(
        //         [Zenodo_id] => 13316781
        //         [Resource_id] => SC_panama
        //         [Resource_name] => Panama Species List
        //         [Resource_URL] => https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_panama.tar.gz
        //         [OpenData_URL] => https://opendata.eol.org/dataset/5d99ead1-db10-40ad-9aac-b1b5611d979e/resource/87794797-6169-4935-908c-c304ed594875
        //     )
        // print_r($this->eol_resources); print_r($this->opendata_info);
        $final = array();
        foreach($this->eol_resources as $opendata_id => $eol_rec) {
            if($eol_rec['status'] == 'published') {
                if($zenodo_rec = @$this->opendata_info[$opendata_id]) {
                    $zenodo_id = $zenodo_rec['Zenodo_id'];
                    $final[$zenodo_id] = "https://eol.org/resources/" . $eol_rec['id'];
                }
            }
        }
        // print_r($final); 
        echo "\nTotal Published records: ".count($final)."\n"; //exit;

        /* ---------- start: normal
        $i = 0; $hits = 0;
        foreach($final as $zenodo_id => $url) { $i++;
            echo "\nprocessing $i ... [$zenodo_id]\n";

            // do batches
            if($i < 707) continue;
            elseif($i >= 707 && $i <= 962) {}
            elseif($i > 962) break;
            else continue;

            // echo "\nprocessing $i ... [$zenodo_id]\n"; exit; //debug only
            if($zenodo_id && $url) { $hits++; sleep(2);
                $this->record_in_question = array('identifier' => $url, 'relation' => 'isSourceOf', 'resource_type' => 'dataset', 'scheme' => 'url');
                self::update_zenodo_record_of_latest_requested_changes($zenodo_id);
                // break; //debug only - run only the 1st hit
                // if($hits >= 2) break; //debug only
            }
        }
        exit("\n- end Related Works -\n");
        ---------- end: normal */

        // /* ---------- start: dev only
        $id = 13761108; //FishBase $id = 13933415; //AntWeb $id = 13321654; //Zoosystematics and Evolution
        $id = 13320903; //Insect Wings - unchanged  $id = 13320567; //unchanged  $id = 13321623; //unchanged
        $id = 13320563; //Saproxylic Organisms
        $id = 13886436; //with ROR and ISNI: USDA NRCS PLANTS Database: USDA PLANTS images DwCA | isSourceOf = https://eol.org/resources/469
        $id = 13318018; //ver 1 of 13886436
        if($url = @$final[$id]) {
            $this->record_in_question = array('identifier' => $url, 'relation' => 'isSourceOf', 'resource_type' => 'dataset', 'scheme' => 'url');
            self::update_zenodo_record_of_latest_requested_changes($id);
        }
        else echo "\nTest record didn't proceed!\n".count($final)."\n";
        exit("\n-----end per taxon, during dev-----\n");
        // ---------- end: dev only */

        // ----- end: main operation */
    }
    function latest_katja_changes_2() //for removing tags "EOL Content Partners"
    {
        $this->log_error(array("==================== Log starts here ===================="));

        /* ------------------- start block
        // https://zenodo.org/search?q=metadata.subjects.subject:"EOL Content Partners"&f=subject:EOL Content Partners&l=list&p=1&s=10&sort=bestmatch
        // https://zenodo.org/search?q=metadata.subjects.subject:"EOL Content Partners"&l=list&p=1&s=10&sort=bestmatch
        // https://zenodo.org/search?q=metadata.subjects.subject:"taxonomy"&f=subject:taxonomy&l=list&p=1&s=10&sort=bestmatch

        $q = 'metadata.subjects.subject:"EOL Content Partners"';         //works OK - can be part of main operation
        // $q = 'metadata.subjects.subject:"taxonomy"';                     //works OK - can be part of main operation

        // didn't work, but no need
        // $f = 'subject:"EOL Content Partners"';
        // $f = 'subject:taxonomy';

        $page = 0; $stats2 = array();
        while(true) { $page++; $IDs = array(); $stats = array();
            // do batches
            // if($page < 31) continue;
            // elseif($page >= 31 && $page <= 90) {}
            // elseif($page > 90) break;
            // else continue;

            echo "\nProcessing page: [$page]...\n";
            if(isset($q)) $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&sort=bestmatch&size=25&page=PAGENUM&q="'.urlencode($q).' -H "Content-Type: application/json"';
            else          $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.               '&size=25&page=PAGENUM"                     -H "Content-Type: application/json"';

            // $cmd = str_replace('PAGENUM', $page, $cmd); //SHOULDN'T BE USED HERE
            $cmd = str_replace('PAGENUM', '1', $cmd); //USE THIS INSTEAD

            // echo "\nlist depostions cmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
            $obj = json_decode(trim($json), true);  echo "\n=====\n"; print_r($obj); echo "\n=====\n"; exit("\n".count($obj)."\n");
            if(!$obj) break;
            echo "\nStart Batch: $page | No. of records: ".count($obj)."\n";
            foreach($obj as $o)  { //print_r($o); exit;
                $id = trim($o['id']);
                if($id == '13381012') continue; //WoRMS
                $IDs[$id] = '';
                @$stats[$o['title']] .= $id . "|";
                @$stats2[$o['title']][] = $id;
            }
            print_r($stats); //exit;
            // ----- main operation
            // $IDs = array_keys($IDs); $i = 0;
            // foreach($IDs as $id)  { $i++; echo "\n [$page][$i]. "; sleep(3); self::update_zenodo_record_of_latest_requested_changes($id); }
            // print_r($stats); //exit;
            // echo "\nEnd Batch: $page | No. of records: ".count($obj)."\n"; sleep(60);
            // -----
            if(count($obj) < 25) break; //means it is the last batch.
            // break; //debug only dev only
        } //end while()        
        exit("\n-end bulk updates-\n");
        ------------------- end block */

        $id = 13761108; //FishBase
        $id = 13933415; //AntWeb
        self::update_zenodo_record_of_latest_requested_changes($id);
        exit("\n-----end per taxon, during dev-----\n");
    }
    function latest_katja_changes()
    {   
        $this->log_error(array("==================== Log starts here ===================="));
        // step 1: loop into all Zenodo records

        /* ------------------- start block
        $page = 0; $stats2 = array();
        while(true) { $page++; $IDs = array(); $stats = array();
            // do batches
            // if($page < 39) continue;
            // elseif($page >= 39 && $page <= 59) {}
            // elseif($page > 59) break;
            // else continue;

            if($page < 24) continue;
            elseif($page >= 24 && $page <= 28) {}
            elseif($page > 28) break;
            else continue;

            
            echo "\nProcessing page: [$page]...\n";
            $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&size=25&page=PAGENUM" -H "Content-Type: application/json"';
            $cmd = str_replace('PAGENUM', $page, $cmd);
            // echo "\nlist depostions cmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
            $obj = json_decode(trim($json), true);  //echo "\n=====\n"; print_r($obj); echo "\n=====\n";
            if(!$obj) break;
            echo "\nStart Batch: $page | No. of records: ".count($obj)."\n";
            foreach($obj as $o)  { //print_r($o); exit;
                $IDs[trim($o['id'])] = '';
                @$stats[$o['title']] .= $o['id'] . "|";
                @$stats2[$o['title']][] = $o['id'];
            }
            print_r($stats); //exit;
            // ----- main operation
            $IDs = array_keys($IDs); $i = 0;
            foreach($IDs as $id)  { $i++; echo "\n [$i]. "; sleep(3); self::update_zenodo_record_of_latest_requested_changes($id); }
            print_r($stats); //exit;
            echo "\nEnd Batch: $page | No. of records: ".count($obj)."\n"; sleep(60);
            // -----
            if(count($obj) < 25) break; //means it is the last batch.
            // break; //debug only dev only
        } //end while()

        // worked OK!        
        // echo "\nstart report: multiple IDs per title:\n";
        // foreach($stats2 as $title => $ids) {
        //     if(count($ids) > 1) {
        //         echo "\n$title\n"; print_r($ids);
        //         @$multiple_IDs++;
        //         $this->log_error(array('multiple IDs', $title, json_encode($ids)));                              //main operation
        //         foreach($ids as $id)  { sleep(3); self::update_zenodo_record_of_latest_requested_changes($id); } //main operation
        //         // exit("\nforce exit\n"); //dev only debug only
        //     }
        // }
        // echo "\$multiple_IDs: $multiple_IDs\n";
        // echo "\n-end report-\n";
        
        exit("\n-end bulk updates-\n");
        ------------------- end block */

        $id = "13795618"; //Metrics: GBIF data coverage
        // $id = "13795451"; //Flickr: USGS Bee Inventory and Monitoring Lab
        // $id = "13794884"; //Flickr: Flickr BHL (544)
        // $id = "13789577"; //Flickr: Flickr Group (15)
        // $id = 13317938; //National Checklists 2019: Réunion Species List
        // $id = 13515043;
        // $id = 13763554; //Museum of Comparative Zoology, Harvard] => 
        // $id = 13788325; //GBIF data summaries: GBIF national node type records: UK] => 
        // $id = 13763279; //Bioimages (Vanderbilt): Bioimages Vanderbilt (200) DwCA] => 
        $id = 13788207; //GBIF data summaries: GBIF national node type records: France
        $id = 13333250; //O'Brien et al, 2013 --- with quotes
        // $id = 13323180; //FALO Classification
        $id = 13315911; //Anne Thessen - Water Body Checklists: Alboran Sea Species List
        // $id = 13313138; //Anne Thessen - National Checklists: Namibia Species List
        $id = 13321983; //BİLECENOĞLU, et al, 204: BİLECENOĞLU et al, 2014
        $id = 13319945; //Water Body Checklists 2019: North Atlantic Species List
        $id = 13317726; //National Checklists 2019: Tajikistan Species List
        $id = 13382586; //EOL computer vision pipelines: Image Rating: Chiroptera;
        $id = 13769682; //EOL taxon identifier map --- Katja's record
        // $id = 13136202; //Images list: image list --- Jen's record
        $id = 13647046; //A record for doing tests
        // $id = 13761108; //FishBase - new record for testing
        // $id = 13879556; //BioImages - the Virtual Fieldguide (UK): BioImages, the virtual fieldguide, UK
        $id = 13315783; //alert by Jen: Arctic Biodiversity: Arctic Freshwater Fishes
        $id = 13315803; //Arctic Biodiversity: Arctic Algae
        $id = 13321333; //Eli Wikipedia : Wikipedia: wikipedia-kk (Kazakh)        
        $id = 13317092; // National Checklists 2019: United Arab Emirates Species List
        $id = 13321393; //Wikipedia: wikipedia_combined_languages_batch2
        $id = 13317273; //National Checklists 2019: Afghanistan Species List
        $id = 13879731;

        // metadata.subjects.subject:"EOL Content Partners: Water Body Checklists 2019"

        // excluded:
        // $id = 13743941; //USDA NRCS PLANTS Database: USDA PLANTS images DwCA | "identifiers": [{"identifier": "01na82s61", "scheme": "ror"}, {"identifier": "0000 0004 0478 6311", "scheme": "isni"}], 
        // $id = 13751009; //[EOL full taxon identifier map] => 

        self::update_zenodo_record_of_latest_requested_changes($id);
        /* To do:
        good example for general formatting of description field: https://zenodo.org/records/13795451
        */
    }
    function update_zenodo_record_of_latest_requested_changes($zenodo_id)
    {
        $this->html_contributors = array(); //initialize

        $excluded_ids = array(13743941, 13751009);
        if(in_array($zenodo_id, $excluded_ids)) return;

        $obj_1st = $this->retrieve_dataset($zenodo_id); print_r($obj_1st); exit("\nstop muna\n");

        /* NEW Oct_6: to filter per tag requirement */
        /* batch 66 - 67
        if(!in_array('EOL Content Partners: Arctic Biodiversity', $obj_1st['metadata']['keywords'])) return;
        */
        /* batch 70 - 80
        if(!in_array('EOL Content Partners: National Checklists', $obj_1st['metadata']['keywords'])) return;
        */
        /* batch 61 - 65
        if(!in_array('EOL Content Partners: Water Body Checklists', $obj_1st['metadata']['keywords'])) return;
        */
        /* batch 16 - 19
        if(!in_array('EOL Content Partners: Wikipedia', $obj_1st['metadata']['keywords'])) return;
        */
        /* batch 39 - 59
        if(!in_array('EOL Content Partners: National Checklists 2019', $obj_1st['metadata']['keywords'])) return;
        */
        /* batch 24 - 28
        if(!in_array('EOL Content Partners: Water Body Checklists 2019', $obj_1st['metadata']['keywords'])) return;
        */
        /* batch 24 - 28
        if(!in_array('EOL Content Partners', $obj_1st['metadata']['keywords'])) return;
        // https://zenodo.org/search?q=metadata.subjects.subject%3A%22EOL%20Content%20Partners%22&f=subject%3AEOL%20Content%20Partners&l=list&p=1&s=10&sort=bestmatch
        */

        $id = $obj_1st['id'];
        if($zenodo_id != $id) {
            if($zenodo_id && $id) {}
            else exit("\nInvestigate not equal IDs: [$zenodo_id] != [$id]\n");
        }

        $edit_obj = $this->edit_Zenodo_dataset($obj_1st); //request to edit a record //exit("\nstop muna 1\n");

        if($this->if_error($edit_obj, 'edit_22Nov2024', $id)) {}
        else {
            /* ran already - DONE
            $obj_latest = self::fill_in_Katja_changes($edit_obj);
            */
            $obj_latest = self::fill_in_Jen_changes($edit_obj); //for the 'deprecated' batch: https://github.com/EOL/ContentImport/issues/16#issuecomment-2488617061
            if($obj_latest) self::update_then_publish($id, $obj_latest);
        }
    }
    private function update_then_publish($id, $obj_latest)
    {   sleep(2);
        // /*
        $this->log_error(array('proceed with U and P', @$obj_latest['id'], @$obj_latest['metadata']['title']));
        // return; //dev only
        // */
        $update_obj = $this->update_Zenodo_record_latest($id, $obj_latest); //to fill-in the publication_date, title creators upload_type et al.
        if($this->if_error($update_obj, 'update_0924', $id)) {}
        else {
            $new_obj = $update_obj;
            // /* publishing block
            $publish_obj = $this->publish_Zenodo_dataset($new_obj); //worked OK but with cumulative files carry-over
            if($this->if_error($publish_obj, 'publish', $new_obj['id'])) {}
            else {
                echo "\nSuccessfully UPDATED then PUBLISHED to Zenodo\n-----u & p-----\n";
                $this->log_error(array('updated then published', @$new_obj['id'], @$new_obj['metadata']['title']));
            }
            // */            
        }
    }
    private function fill_in_Katja_changes($o)
    {   //print_r($o); exit("\nstop muna 1\n");
        // $o['metadata']['creators'][0]['affiliation'] = "Eli was here 5."; //dev only
        /* Agents
        - For records that have Hosting institution: Anne Thessen under Contributors, remove the Contributors record, 
            remove the "script (Zenodo API)" Creator 
            and add the following as the new Creator:            
                Person
                Name: Anne Thessen [important: do not link to any identifiers]
                Affiliations: Encyclopedia of Life
                Role: Data Manager
        - For all other records that have "script (Zenodo API)" as the Creator, remove this Creator and add the following as the new Creator:
                Organization
                Name: Encyclopedia of Life
                Role: Hosting Institution
        - Remove all remaining Contributors with Role: Hosting Institution.        
        */

        // /* ------------------------------------ impt block
        self::get_data_record_from_html($o, 'contributors', 0); //3rd param expire_seconds
        self::get_data_record_from_html($o, 'creators', false);
        self::get_data_record_from_html($o, 'creators2', false); //e.g. for Zenodo ID = 13647046
        if($val = $this->html_contributors) {
            echo "\nWITH captured Creators and Contributors with identifiers.\n";
            print_r($this->html_contributors); //good debug
            $this->log_error(array($o['id'], $o['title'], "Captured data" , json_encode($val)));
            // return false; //un-comment in real operation
        }
        else echo "\nNO captured Creators and Contributors with identifiers.\n";
        // exit("\nelix 1\n");
        // Array(
        //     [United States Department of Agriculture] => Array(
        //             [ror] => 01na82s61
        //             [orcid] => 0000 0004 0478 6311
        //         )
        // )
        // ------------------------------------ */ 

        // /* ------------------ START creators latest
        $final = array();
        foreach(@$o['metadata']['creators'] as $r) {
            if($r['name'] == 'script') $final[] = array('name' => 'Encyclopedia of Life', 'type' => 'HostingInstitution', 'affiliation' => ''); //orig
            else  {
                $name = $r['name'];
                if($name == 'Encyclopedia of Life') $r['type'] = 'HostingInstitution';
                if($val = @$this->html_contributors[$name]['orcid']) $r['orcid'] = $val;        //worked OK, with doc example gnd      - html ror 01na82s61
                if($val = @$this->html_contributors[$name]['gnd'])   $r['gnd']   = $val;        //worked OK, with doc example orcid    - html isni 0000 0004 0478 6311
                if($val = @$this->html_contributors[$name]['isni'])  $r['isni']  = "$val";      //no doc example, never worked    
                if($val = @$this->html_contributors[$name]['ror'])   $r['ror']   = "$val";      //was never proven      
                if($orcid = @$this->ORCIDs[$name]) $r['orcid'] = $orcid; //implement saved ORCIDs
                /* manual check: no choice until API catches us with site --> BUT all type of combinations didn't work; can't add a type e.g. DataManager
                    // [type] => DataManager
                    // [orcid] => 0000-0001-7134-3324 -> this is Schulz, Katja
                // if($r['orcid'] == '0000-0001-7134-3324') {
                //     $r['type']['role']['id'] = 'DataManager';
                //     $r['role']['type']['id'] = 'DataManager';
                // }
                */
                $final[] = $r;
            }
        }
        if(!$final) $final[] = array('name' => 'Encyclopedia of Life', 'type' => 'HostingInstitution', 'affiliation' => ''); //orig

        // both didn't work
        // if(!$final) $final[] = array('name' => 'Encyclopedia of Life', 'type' => array('id' => 'HostingInstitution'), 'affiliation' => ''); //didn't work
        // if(!$final) $final[] = array('organization' => array('name' => 'Encyclopedia of Life', 'type' => 'organizational'), 'role' => array('id' => 'HostingInstitution', 'title' => array('en' => 'Hosting institution'))); //didn't work

        $o['metadata']['creators'] = $final;
        echo "\nCreators to save:"; print_r($final);
        // ------------------ END creators latest */
        /*
        "creators": [{  "person_or_org": {"name": "Encyclopedia of Life", "type": "organizational"}, 
                        "role": {"id": "hostinginstitution", "title": {"de": "Bereitstellende Institution", "en": "Hosting institution"}}}]        
        */

        // /* ------------------ START contributors latest
        $final = array();
        if($val = @$o['metadata']['contributors']) {
            foreach($val as $r) {
                if(!@$r['name']) continue;
    
                // if($r['name'] == 'Eli Agbayani') continue; //debug only
    
                if($r['type'] == 'HostingInstitution' && $r['name'] == 'Anne Thessen')
                {   /*
                    and add the following as the new Creator:            
                        Person
                        Name: Anne Thessen [important: do not link to any identifiers]
                        Affiliations: Encyclopedia of Life
                        Role: Data Manager
                    */
                    $o['metadata']['creators'] = array();
                    $o['metadata']['creators'][] = array('name' => $r['name'], 'type' => 'DataManager',   'affiliation' => 'Encyclopedia of Life');
                                        $final[] = array('name' => $r['name'], 'type' => 'DataManager',   'affiliation' => 'Encyclopedia of Life');
                }
                elseif($r['type'] == 'HostingInstitution' && $r['name'] == 'Eli Agbayani') $final[] = array('name' => $r['name'], 'type' => 'DataCollector', 'affiliation' => 'Encyclopedia of Life');
                elseif($r['type'] == 'HostingInstitution') { //orcid: 0000-0002-1694-233X | gnd: 170118215
                    $r['type'] = 'ContactPerson';
                    $tmp = $r;
                    $name = $r['name'];
                    if($val = @$this->html_contributors[$name]['orcid']) $tmp['orcid'] = $val;      //worked OK, with doc example gnd      - html ror 01na82s61
                    if($val = @$this->html_contributors[$name]['gnd'])   $tmp['gnd'] = $val;        //worked OK, with doc example orcid    - html isni 0000 0004 0478 6311
                    if($val = @$this->html_contributors[$name]['isni'])  $tmp['isni'] = "$val";     //no doc example, never worked    
                    if($val = @$this->html_contributors[$name]['ror'])   $tmp['ror'] = "$val";      //was never proven      
                    if($orcid = @$this->ORCIDs[$name]) $tmp['orcid'] = $orcid; //implement saved ORCIDs
                    $final[] = $tmp;
                }
                else {
                    $tmp = $r;
                    $name = $r['name'];
                    if($val = @$this->html_contributors[$name]['orcid']) $tmp['orcid'] = $val;      //worked OK, with doc example gnd      - html ror 01na82s61
                    if($val = @$this->html_contributors[$name]['gnd'])   $tmp['gnd'] = $val;        //worked OK, with doc example orcid    - html isni 0000 0004 0478 6311
                    if($val = @$this->html_contributors[$name]['isni'])  $tmp['isni'] = "$val";     //no doc example, never worked    
                    if($val = @$this->html_contributors[$name]['ror'])   $tmp['ror'] = "$val";      //was never proven      
                    /* Contingency since isni and ror don't work: THIS PRODUCED A VALIDATION ERROR
                    if($val = @$this->html_contributors[$name]['isni'])  $tmp['gnd'] = "$val";     //contingency    
                    if($val = @$this->html_contributors[$name]['ror'])   $tmp['orcid'] = "$val";      //contingency
                    */
                    if($orcid = @$this->ORCIDs[$name]) $tmp['orcid'] = $orcid; //implement saved ORCIDs
                    $final[] = $tmp;
                }
            } //end foreach()    
        }
        // Oct_6
        if($val = @$o['metadata']['keywords']) {
            if(in_array('EOL Content Partners: Wikipedia', $val)) {
                if(!self::is_name_in_Contributors('Eli Agbayani', $final)) {
                    $final[] = array('name' => 'Eli Agbayani', 'type' => 'DataManager', 'affiliation' => 'Encyclopedia of Life', 'orcid' => @$this->ORCIDs['Eli Agbayani']);
                }    
            }    
        }

        // /* New:
        $final = self::add_or_notAdd_katja($o['metadata']['creators'], $final); //return value is: contributors
        // */

        $o['metadata']['contributors'] = $final; 
        echo "\nContributors to save:"; print_r($final);
        // ------------------ END contributors latest */

        /* Keywords & subjects
        1. For all data sets with keyword "EOL Content Partners: National Checklists 2019" or "EOL Content Partners: Water Body Checklists 2019" add keyword "deprecated"
        2. Remove all keywords with the prefix "format:", e.g., "format: ZIP", "format: TAR", "format: XML", etc.        
        [keywords] => Array(
                    [0] => EOL Content Partners: National Checklists 2019
                    [1] => format: Darwin Core Archive
                )        
        */
        // #1
        if($keywords = @$o['metadata']['keywords']) {
            if(in_array('EOL Content Partners: National Checklists 2019', $keywords) || in_array('EOL Content Partners: Water Body Checklists 2019', $keywords)) {
                if(!in_array('deprecated', $keywords)) $keywords[] = 'deprecated';
            }    
        }
        // #2
        $final = array();
        if($keywords) {
            foreach($keywords as $kw) {
                if(substr($kw,0,8) != 'format: ') $final[] = $kw;
            }    
        }
        // Oct_6 'geography'
        $tags = array('EOL Content Partners: Arctic Biodiversity', 'EOL Content Partners: National Checklists', 'EOL Content Partners: Water Body Checklists');
        foreach($tags as $tag) {
            if(in_array($tag, $final)) { if(!in_array('geography', $final)) $final[] = 'geography'; }
            if(($key = array_search($tag, $final)) !== false) { //value search in an array
                if(in_array('geography', $final)) $final[$key] = NULL;
            }
        }
        $final = self::remove_null_make_unique_reindex_key($final);
        echo "\nKeywords to save: "; print_r($final);
        $o['metadata']['keywords'] = $final;

        // Oct_6 'descriptions' Wikipedia
        $tags = array('EOL Content Partners: Wikipedia');
        foreach($tags as $tag) {
            if(in_array($tag, $final)) { if(!in_array('descriptions', $final)) $final[] = 'descriptions'; }
            if(($key = array_search($tag, $final)) !== false) { //value search in an array
                if(in_array('descriptions', $final)) $final[$key] = NULL;
            }
        }
        $final = self::remove_null_make_unique_reindex_key($final);
        echo "\nKeywords to save (descriptions): "; print_r($final);

        // Oct_6 just remove these tags if encountered
        $tags = array('EOL Content Partners: National Checklists 2019', 'EOL Content Partners: Water Body Checklists 2019', 'EOL Content Partners');
        foreach($tags as $tag) {
            if(in_array($tag, $final)) {
                if(($key = array_search($tag, $final)) !== false) $final[$key] = NULL;
            }
        }
        $final = self::remove_null_make_unique_reindex_key($final);
        echo "\nKeywords to save (just delete 3 tags): "; print_r($final);
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        $o['metadata']['keywords'] = $final;


        /* Notes: It looks like the Notes field in Zenodo currently contains a combination of the OpenData resource and organization description. 
        We would like to handle this in a different way:
        #1 Please move the content that's currently in the Zenodo Notes field to the Description field instead. 
            If there is already content in the Description field, append the content from the Notes field.
        #2 Please entirely remove this text from all Notes, 
            i.e., do not include it in the text appended to the Description: 
                "This is where EOL hosts source datasets (archives, dumps, etc.) from EOL content partners (especially partners without a web presence of their own).
                This organization will also include the content partner utility files EOL connectors use to generate a particular content partner__s resource EOL archive or XML.
                For questions or suggestions please visit the EOL Services forum at http://discuss.eol.org/c/eol-services ####--- __EOL DwCA resource last updated: .... ---####"
        */
        if($notes = trim(@$o['metadata']['notes'])) {
            // 1st step: format notes
            $left  = "This is where EOL hosts source datasets";
            $right = "__ ---####";
            $notes = self::remove_all_in_between_inclusive($left, $right, $notes, true);
            //2nd pass on it:
            $left  = "####--- __";
            $right = "__ ---####";
            $notes = self::remove_all_in_between_inclusive($left, $right, $notes, true);
            // 3rd pass
            $left  = "This is where EOL hosts source datasets";
            $right = "http://discuss.eol.org/c/eol-services";
            $notes = self::remove_all_in_between_inclusive($left, $right, $notes, true);
            // 4th pass
            $left  = "For questions or suggestions please visit the EOL Services";
            $right = "http://discuss.eol.org/c/eol-services";
            $notes = self::remove_all_in_between_inclusive($left, $right, $notes, true);

            // 2nd step: move notes to description
            $description = trim(@$o['metadata']['description']);
            if($description) $description .= "<p></p>" . $notes;
            else             $description = $notes;
            // 3rd step: assignment
            $o['metadata']['notes'] = "";
            $description = self::eli_formats_description($description);
            $o['metadata']['description'] = trim($description);
        }

        /* working but not used anymore
        // from separate path
        if($val = @$this->html_contributors) { //doesn't go here anymore sice these records won't be updated by API anymore. But manually.
            $notes = @$o['metadata']['notes'];
            if($notes) $notes .= "<p></p>" . "Captured data during API bulk updates: ".json_encode($val);
            else       $notes = "Captured data during API bulk updates: ".json_encode($val);
            $o['metadata']['notes'] = $notes;
        }
        */

        // print_r($o); exit("\nstop muna 1\n");
        return $o;
    }
    private function add_or_notAdd_katja($creators, $contributors)
    {
        // print_r($creators); print_r($contributors); exit("\nelix 1\n");
        /*Array(
            [0] => Array(
                    [name] => Schulz, Katja
                    [affiliation] => National Museum of Natural History, Smithsonian Institution
                    [orcid] => 0000-0001-7134-3324
                )
        )*/
        if($katja_exists_in_creators = self::if_exists_in_creatorsORcontributors($creators, 'Schulz, Katja', '0000-0001-7134-3324')) {
            //if yes then add katja as DataManager in Contributors, if not there yet
            if($katja_exists_in_contributors = self::if_exists_in_creatorsORcontributors($contributors, 'Schulz, Katja', '0000-0001-7134-3324')) {}
            else { //not there yet
                $r = $katja_exists_in_creators;
                $contributors[] = array('name' => $r['name'], 'type' => 'DataManager', 'orcid' => $r['orcid'], 'affiliation' => $r['affiliation']);
            }
        }
        return $contributors;
    }
    private function if_exists_in_creatorsORcontributors($arr, $name, $orcid)
    {
        foreach($arr as $r) {
            if(@$r['orcid'] == $orcid || @$r['name'] == $name) return $r;
        }
        return false;
    }
    function update_Zenodo_record_latest($id, $obj_1st) //this updates the newversion object
    {
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        $dates_final = self::get_dates_entries_from_html($obj_1st, false); //2nd param false is $updateDate_set2Current_YN
        // if(!self::has_type_equal2_Other($dates_final)) {
            // $dates_final[] = array("start" => date("Y-m-d"), "end" => date("Y-m-d"), "type" => 'Other', "description" => "metadata updated");
        // }
        // print_r($dates_final); exit;
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        if($val = @$obj_1st['metadata']['license']) $license_final = $val;
        else                                        $license_final = "notspecified";
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        $notes = @$obj_1st['metadata']['notes'];
        $notes = self::format_description($notes);
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        $keywords_final = array();
        if($val = @$obj_1st['metadata']['keywords']) {
            foreach($val as $kw) {
                $kw = str_replace("'", "__", $kw);
                $keywords_final[] = $kw;
            }
        }
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        /*[related_identifiers] => Array(
            [0] => Array(
                    [identifier] => https://editors.eol.org/eol_php_code/applications/content_server/resources/42_meta_recoded.tar.gz
                    [relation] => isSupplementTo
                    [resource_type] => dataset
                    [scheme] => url
                )
            [1] => Array(
                    [identifier] => https://eol.org/resources/395
                    [relation] => isSourceOf
                    [resource_type] => dataset
                    [scheme] => url
                )
        )*/

        // /* --------------------- start: Related Works ---------------------
        // $this->record_in_question = array('identifier' => 'https://eol.org/resources/547', 'relation' => 'isSourceOf', 'resource_type' => 'dataset', 'scheme' => 'url'); //force assign
        $sought = @$this->record_in_question;
        if($RI = @$obj_1st['metadata']['related_identifiers']) { print_r($RI);
            $add_isSourceOf_YN = true;
            foreach($RI as $r) {
                if($r['identifier'] == @$sought['identifier'] && $r['relation'] == @$sought['relation']) $add_isSourceOf_YN = false;
            }
            if($add_isSourceOf_YN) { $RI[] = $sought; echo "\nisSourceOf is added.\n"; print_r($RI); }
            else echo "\nisSourceOf was not added. Already exists.\n";
        }
        else $RI = array();
        $obj_1st['metadata']['related_identifiers'] = $RI;
        // --------------------- end: Related Works --------------------- */
        
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        array_shift($obj_1st['files']);
        $input['metadata'] = array(
                                    "title" => str_replace("'", "__", $obj_1st['metadata']['title']),
                                    "publication_date" => $obj_1st['metadata']['publication_date'], //date("Y-m-d"),
                                    "creators" => @$obj_1st['metadata']['creators'],
                                    "upload_type" => @$obj_1st['metadata']['upload_type'], //'dataset',
                                    // "files" => array() //$obj_1st['files']
                                    "access_right" => @$obj_1st['metadata']['access_right'],
                                    "contributors" => @$obj_1st['metadata']['contributors'],                                    
                                    "keywords" => $keywords_final,
                                    "related_identifiers" => @$obj_1st['metadata']['related_identifiers'],
                                    "imprint_publisher" => @$obj_1st['metadata']['imprint_publisher'],
                                    "communities" => @$obj_1st['metadata']['communities'],
                                    "notes" => str_replace("'", "__", $notes),
                                    "prereserve_doi" => @$obj_1st['metadata']['prereserve_doi'],
                                    "license" => $license_final,
                                    "dates" => $dates_final,
                                    // "dates" => $dates, // manual force assignment
                                    // "dates" => array(),

        ); //this is needed for publishing a newly uploaded file.

        if($val = @$obj_1st['metadata']['description']) $input['metadata']['description'] = str_replace("'", "__", $val); //impt. bec. metadata description is never blank.

        // Resource type: Missing data for required field.
        // Creators: Missing data for required field.
        // Title: Missing data for required field.

        $json = json_encode($input); //echo "\n$json\n";
        if($this->show_print_r) print_r($input); //exit;

        $cmd = 'curl -s -H "Content-Type: application/json" -X PUT --data '."'$json'".' https://zenodo.org/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN;
        // $cmd = 'curl -s -H "Content-Type: application/json" -X PUT --data '."'$json'".' '.$links_edit.'?access_token='.ZENODO_TOKEN;
        
        // $cmd .= " 2>&1";
        // echo "\n$cmd\n";
        $json = shell_exec($cmd);           //echo "\n$json\n";
        $obj = json_decode(trim($json), true);    
        echo "\n----------update pubdate latest----------\n"; 
        if($this->show_print_r) print_r($obj); 
        echo "\n----------update pubdate latest end----------\n";
        return $obj;
    }
    private function has_type_equal2_Other($dates)
    {   // print_r($dates); exit("\nelix 1\n");
        foreach($dates as $date) {
            if($date['type'] == 'Other' && $date['description'] == 'metadata updated') return true;
        }
        return false;
    }
    private function eli_formats_description($description)
    {
        if($description) { echo "\n----- goes here 1 ".strlen($description)."\n";
            $description = str_replace("Please contact", "<p></p>Please contact", $description);
            $description = str_replace("Follow us on", "<p></p>Follow us on", $description);
            $description = str_replace("http", "<p></p>http", $description);
            $description = str_replace("<p><p></p>", "<p>", $description);
            $description = trim($description);

            // echo "\n111[$description]\n";
            // if(substr($description,0,7) == "<p></p>") { echo "\n----- goes here 2 ".strlen($description)."\n";
            //     $description = trim(substr($description,7,strlen($description)));
            // }
            // echo "\n222[$description]\n";
        }
        // echo "\n----- goes here 3 ".strlen($description)."\n"; echo "\n[$description]\n";
        $description = str_replace("'", "__", $description);
        return $description;
    }
    private function build_EOL_resourceID_and_Zenodo_ID_info()
    {
        $options = $this->download_options; 
        $options['expire_seconds'] = 60*60*24; //1 day cache
        $options['cache'] = 1;
        // step 1
        $url = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/reports/EOL_harvest_list.html';
        if($html = Functions::lookup_with_cache($url, $options)) { 
            if(preg_match_all("/\{(.*?)\}/ims", $html, $arr)) { // print_r($arr[1]);
                foreach($arr[1] as $str) {
                    $json = "{".$str."}"; 
                    $rec = json_decode($json, true); // echo "\n$json\n"; print_r($rec); exit("\nelix\n");
                    /*Array(
                        [name] => 000_English Vernaculars for Landmark Taxa
                        [id] => 1001
                        [status] => unpublished
                        [content_id] => 550
                        [opendata_id] => 4b1ad94f-0d20-47f1-8a43-c2cb0d670da4
                    )*/
                    $this->eol_resources[$rec['opendata_id']] = $rec;
                }
            }
        }
        // print_r($this->eol_resources); exit;
        // step 2
        $file = $this->github_EOL_resource_id_and_Zenodo_id_file;
        if($local_file = Functions::save_remote_file_to_local($file, $options)) { $i = 0;
            foreach(new FileIterator($local_file) as $line_number => $line) { $i++;
                $row = explode("\t", $line); // print_r($row);
                if($i == 1) {
                    $fields = $row;
                    $fields = array_filter($fields); //print_r($fields);
                    continue;
                }
                else {
                    $k = 0; $rec = array();
                    foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit("\nstopx\n");
                /*Array(
                    [Zenodo_id] => 13318002
                    [Resource_id] => microscope
                    [Resource_name] => micro*scope
                    [Resource_URL] => https://editors.eol.org/uploaded_resources/55a/d62/microscope.xml.gz
                    [OpenData_URL] => https://opendata.eol.org/dataset/4a668cee-f1da-4e95-9ed1-cb755a9aca4f/resource/55ad629d-dd89-4bac-8fff-96f219f4b323
                )*/
                // if($val = @$rec['Zenodo_id']) $this->zenodo_2_eol_conn[$val] = $rec;
                if($OpenData_id = pathinfo(@$rec['OpenData_URL'], PATHINFO_BASENAME)) $this->opendata_info[$OpenData_id] = $rec;
            }
            unlink($local_file);
        }
        // unlink($local_file); //redundant
        /*
        $this->eol_resources[opendata id]
        [87794797-6169-4935-908c-c304ed594875] => Array(
                    [name] => Panama Species List
                    [id] => 196
                    [status] => published
                    [content_id] => 285
                    [opendata_id] => 87794797-6169-4935-908c-c304ed594875
                )
        $this->opendata_info[opendata id]
        [87794797-6169-4935-908c-c304ed594875] => Array(
                [Zenodo_id] => 13316781
                [Resource_id] => SC_panama
                [Resource_name] => Panama Species List
                [Resource_URL] => https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_panama.tar.gz
                [OpenData_URL] => https://opendata.eol.org/dataset/5d99ead1-db10-40ad-9aac-b1b5611d979e/resource/87794797-6169-4935-908c-c304ed594875
            )
        */
        // print_r($this->eol_resources); print_r($this->opendata_info);
        echo "\n eol_resources: ".count($this->eol_resources)."\n";
        echo "\n opendata_info: ".count($this->opendata_info)."\n";
        // exit;
    }
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ end @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    function update_zenodo_record_of_eol_resource($zenodo_id, $actual_file) //upload of actual file to a published Zenodo record
    {
        $obj_1st = $this->retrieve_dataset($zenodo_id); //exit("\nstop muna\n");
        // /*
        if($new_obj = $this->request_newversion($obj_1st)) { $id = $new_obj['id']; //13271534 --- this ID will be needed for the next retrieve-publish tasks below. //main operation
        // if(true) { //debug only dev only

            // exit("\nstop muna\n");

            // /* original
            if($upload_obj = $this->upload_Zenodo_dataset($new_obj, $actual_file)) {}
            else {
                if(isset($new_obj)) $this->request_discard($new_obj);
                echo "\nNewVersion discarded since upload failed.\n";
                return;
            }
            // */
    
            // return; //dev only debug only

            if($this->if_error($upload_obj, 'upload', $new_obj['id'])) {}
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

                // /* ========== retrieve and publish

                /* ----- special case: comment in real operation - works OK if used -> This did not get the latest ver. but the first ver. from the TSV file.
                $obj_orig = $this->retrieve_dataset($zenodo_id, false); //2nd param false means doesn't need the latest version.
                $update_obj = $this->update_Zenodo_record_v2($id, $obj_orig); //to fill-in the publication_date, title creators upload_type et al.
                ----- end */

                $update_obj = $this->update_Zenodo_record_v2($id, $obj_1st); //to fill-in the publication_date, title creators upload_type et al.
                if($this->if_error($update_obj, 'update1', $id)) {}
                else {
                    $obj = $this->retrieve_dataset($id); //works OK
                    if($this->if_error($obj, 'retrieve', $id)) {}    
                    else {
                        // /* publishing block
                        $publish_obj = $this->publish_Zenodo_dataset($new_obj); //worked OK but with cumulative files carry-over
                        if($this->if_error($publish_obj, 'publish', $new_obj['id'])) {}
                        else {
                            echo "\nSuccessfully uploaded then published to Zenodo\n-----u & p-----\n";
                            $this->log_error(array('uploaded then published', @$new_obj['id'], @$new_obj['metadata']['title'], @$new_obj['metadata']['related_identifiers'][0]['identifier']));
                        }
                        // */
                    }
                }
                // ========== end */
            }            
        }
        else {
            echo "\n----------\nERROR Zenodo: newversion object not created!\n";
            print_r($obj_1st); echo "\n----------\n";
        }
        // */
    }
    function update_Zenodo_record_v2($id, $obj_1st) //this updates the newversion object
    {
        $ret_obj = $this->retrieve_dataset($id);
        $links_edit = $ret_obj['links']['edit'];
        $links_publish = $ret_obj['links']['publish'];

        /*
        curl -i -H "Content-Type: application/json" -X PUT
        --data '{"metadata": {"title": "My first upload", "upload_type": "poster", 
                              "description": "This is my first upload", 
                              "creators": [{"name": "Doe, John", "affiliation": "Zenodo"}]}}' https://zenodo.org/api/deposit/depositions/1234?access_token=ACCESS_TOKEN
        */

        /* generate input first: 3 required fields
        Resource type: Missing data for required field.
        Creators: Missing data for required field.
        Title: Missing data for required field.        
        */

        /* manual force assignment
        $dates = array();
        $dates[] = array("start" => "2017-10-02", "end" => "2017-10-02", "type" => "Created");
        */

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        $dates_final = self::get_dates_entries_from_html($obj_1st);
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        if($val = @$obj_1st['metadata']['license']) $license_final = $val;
        else                                        $license_final = "notspecified";
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        $notes = @$obj_1st['metadata']['notes'];
        $notes = self::format_description($notes);
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Below is a new block:
        self::get_data_record_from_html($obj_1st, 'contributors', 0); //3rd param expire_seconds
        self::get_data_record_from_html($obj_1st, 'creators', false);
        self::get_data_record_from_html($obj_1st, 'creators2', false); //e.g. for Zenodo ID = 13647046
        // /* ------------------ creators v2
        $final = array();
        foreach(@$obj_1st['metadata']['creators'] as $r) {
            $name = $r['name'];
            if($val = @$this->html_contributors[$name]['orcid']) $r['orcid'] = $val;        //worked OK, with doc example gnd      - html ror 01na82s61
            if($val = @$this->html_contributors[$name]['gnd'])   $r['gnd']   = $val;        //worked OK, with doc example orcid    - html isni 0000 0004 0478 6311
            if($val = @$this->html_contributors[$name]['isni'])  $r['isni']  = "$val";      //no doc example, never worked    
            if($val = @$this->html_contributors[$name]['ror'])   $r['ror']   = "$val";      //was never proven      
            if($orcid = @$this->ORCIDs[$name]) $r['orcid'] = $orcid; //implement saved ORCIDs
            $final[] = $r;
        }
        $obj_1st['metadata']['creators'] = $final;
        echo "\nCreators to save:"; print_r($final);
        // */

        // /* ------------------ contributors v2
        $final = array();
        if($val = @$obj_1st['metadata']['contributors']) {
            foreach($val as $r) {
                if(!@$r['name']) continue;
                $tmp = $r;
                $name = $r['name'];
                if($val = @$this->html_contributors[$name]['orcid']) $tmp['orcid'] = $val;      //worked OK, with doc example gnd      - html ror 01na82s61
                if($val = @$this->html_contributors[$name]['gnd'])   $tmp['gnd'] = $val;        //worked OK, with doc example orcid    - html isni 0000 0004 0478 6311
                if($val = @$this->html_contributors[$name]['isni'])  $tmp['isni'] = "$val";     //no doc example, never worked    
                if($val = @$this->html_contributors[$name]['ror'])   $tmp['ror'] = "$val";      //was never proven      
                // /* Contingency since isni and ror don't work: THIS PRODUCED A VALIDATION ERROR
                if($val = @$this->html_contributors[$name]['isni'])  $tmp['gnd'] = "$val";     //contingency    
                if($val = @$this->html_contributors[$name]['ror'])   $tmp['orcid'] = "$val";      //contingency
                // */
                if($orcid = @$this->ORCIDs[$name]) $tmp['orcid'] = $orcid; //implement saved ORCIDs
                $final[] = $tmp;    
            } //end foreach()    
        }
        $obj_1st['metadata']['contributors'] = $final; 
        echo "\nContributors to save:"; print_r($final);
        // */
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        $keywords_final = array();
        if($val = @$obj_1st['metadata']['keywords']) {
            foreach($val as $kw) {
                $kw = str_replace("'", "__", $kw);
                $keywords_final[] = $kw;
            }
        }
        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        array_shift($obj_1st['files']);
        $input['metadata'] = array(
                                    "title" => str_replace("'", "__", $obj_1st['metadata']['title']),
                                    "publication_date" => date("Y-m-d"),
                                    "creators" => @$obj_1st['metadata']['creators'],
                                    "upload_type" => @$obj_1st['metadata']['upload_type'], //'dataset',
                                    // "files" => array() //$obj_1st['files']
                                    "access_right" => @$obj_1st['metadata']['access_right'],
                                    "contributors" => @$obj_1st['metadata']['contributors'],
                                    "keywords" => $keywords_final,
                                    "related_identifiers" => @$obj_1st['metadata']['related_identifiers'],
                                    "imprint_publisher" => @$obj_1st['metadata']['imprint_publisher'],
                                    "communities" => @$obj_1st['metadata']['communities'],
                                    "notes" => str_replace("'", "__", $notes),
                                    "prereserve_doi" => @$obj_1st['metadata']['prereserve_doi'],
                                    "license" => $license_final,
                                    "dates" => $dates_final,
                                    // "dates" => $dates, // manual force assignment
                                    // "dates" => array(),

        ); //this is needed for publishing a newly uploaded file.

        if($val = @$obj_1st['metadata']['description']) $input['metadata']['description'] = str_replace("'", "__", $val); //impt. bec. metadata description must not be blank.

        // Resource type: Missing data for required field.
        // Creators: Missing data for required field.
        // Title: Missing data for required field.

        $json = json_encode($input); echo "\n$json\n";
        if($this->show_print_r) print_r($input); //exit;

        $cmd = 'curl -s -H "Content-Type: application/json" -X PUT --data '."'$json'".' https://zenodo.org/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN;
        // $cmd = 'curl -s -H "Content-Type: application/json" -X PUT --data '."'$json'".' '.$links_edit.'?access_token='.ZENODO_TOKEN;
        
        // $cmd .= " 2>&1";
        // echo "\n$cmd\n";
        $json = shell_exec($cmd);           //echo "\n$json\n";
        $obj = json_decode(trim($json), true);    
        echo "\n----------update pubdate----------\n"; 
        if($this->show_print_r) print_r($obj); 
        echo "\n----------update pubdate end----------\n";
        return $obj;
    }
    function gen_EOL_resource_ID_and_Zenodo_ID_list($r, $id_sought)
    {
        $name = ($r['name']) ? ($r['name']) : ("Unnamed resource");
        $opendata_url = "https://opendata.eol.org/dataset/".$r['package_id']."/resource/".$r['id'];
        
        $filename = pathinfo($r['url'], PATHINFO_FILENAME); //exit;
        $arr = explode('.', $filename);
        $resource_id = $arr[0]; // print_r($r); echo "\n[$resource_id]\n";

        $save = array('Zenodo_id' => $id_sought, 'Resource_id' => $resource_id, 'Resource_name' => $name, 'Resource_URL' => $r['url'], 'OpenData_URL' => $opendata_url);
        // , 'id' => $r['id'], 'package_id' => $r['package_id']
        // print_r($save);
        $fields = array_keys($save); //print_r($fields); exit;
        $filename = $this->Write_EOL_resource_id_and_Zenodo_id_file;
        $WRITE = Functions::file_open($filename, "a");
        clearstatcache(); //important for filesize()
        if(filesize($filename) == 0) fwrite($WRITE, implode("\t", $fields) . "\n");
        $arr = array();
        foreach($fields as $f) $arr[] = $save[$f];
        fwrite($WRITE, implode("\t", $arr) . "\n");
        fclose($WRITE);
    }
    function update_Zenodo_record_using_EOL_resourceID($resource_id)
    {
        // echo "\npassed:   [".$this->new_description_for_zenodo."]\n"; //not implemented anymore
        $file = CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".tar.gz";
        if(file_exists($file)) {
            if($zenodo_id = self::get_zenodo_id_using_eol_resource_id($resource_id)) {
                self::update_zenodo_record_of_eol_resource($zenodo_id, $file); //https://zenodo.org/records/13240083 test record
            }
            else echo "\nCannot link EOL resource id to a Zenodo record [$resource_id].\n";
        }
        else echo "\nFile does not exist [$file]. No Zenodo record.\n";
    }
    private function get_zenodo_id_using_eol_resource_id($resource_id)
    {
        $file = $this->github_EOL_resource_id_and_Zenodo_id_file;
        $options = $this->download_options; 
        $options['expire_seconds'] = 60*60*24; //1 day cache
        // $options['expire_seconds'] = 0; //expires now
        $options['cache'] = 1;
        if($local_file = Functions::save_remote_file_to_local($file, $options)) {
            $i = 0;
            foreach(new FileIterator($local_file) as $line_number => $line) {
                $i++;
                $row = explode("\t", $line); // print_r($row);
                if($i == 1) {
                    $fields = $row;
                    $fields = array_filter($fields); //print_r($fields);
                    continue;
                }
                else {
                    $k = 0; $rec = array();
                    foreach($fields as $fld) { $rec[$fld] = @$row[$k]; $k++; }
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit("\nstopx\n");
                /*Array(
                    [Zenodo_id] => 13318002
                    [Resource_id] => microscope
                    [Resource_name] => micro*scope
                    [Resource_URL] => https://editors.eol.org/uploaded_resources/55a/d62/microscope.xml.gz
                    [OpenData_URL] => https://opendata.eol.org/dataset/4a668cee-f1da-4e95-9ed1-cb755a9aca4f/resource/55ad629d-dd89-4bac-8fff-96f219f4b323
                )*/
                $basename = pathinfo($rec['Resource_URL'], PATHINFO_BASENAME); //exit;
                $needle = $resource_id.".tar.gz";
                if($resource_id == $rec['Resource_id'] && $needle == $basename ) { // print_r($rec); exit("\nstopx\n");
                    unlink($local_file);
                    return $rec['Zenodo_id'];
                }
            }
            unlink($local_file);
        }
        // unlink($local_file); //redundant
    }
    function get_dates_entries_from_html($obj, $updateDate_set2Current_YN = true)
    {   /*
        <div class="ui grid">
            <div class="sixteen wide mobile four wide tablet three wide computer column">
                <h3 class="ui header">Dates</h3>
            </div>
            <div class="sixteen wide mobile twelve wide tablet thirteen wide computer column">
                <dl class="details-list">
                    <dt class="ui tiny header">Created</dt>
                    <dd>
                        <div>2017-10-02</div>
                        <div class="text-muted">1st</div>
                    </dd>
                    <dt class="ui tiny header">Updated</dt>
                    <dd>
                        <div>2017-10-03</div>
                        <div class="text-muted">2nd</div>
                    </dd>
                    <dt class="ui tiny header">Collected</dt>
                    <dd>
                        <div>2017-10-04</div>
                        <div class="text-muted">3rd</div>
                    </dd>
                </dl>
            </div>
        </div>
        */
        $date_type = array(); $date_actual = array(); $date_desc = array();
        $url = $obj['links']['html'];
        $url = str_replace("deposit", "records", $url);
        $options = $this->download_options;
        $options['expire_seconds'] = 0;
        $options['expire_seconds'] = 60*60*24; //can be not 0 bec. same URL was called above with already 0 expiry.
        if($html = Functions::lookup_with_cache($url, $options)) { echo "\ngoes date 1 [$url]\n";
            if(preg_match("/>Dates<\/h3>(.*?)<\/dl>/ims", $html, $arr)) { //echo "\ngoes date 2\n";
                if(preg_match_all("/<dt(.*?)<\/dt>/ims", $arr[1], $arr2)) { //echo "\ngoes date 3\n";
                    // print_r($arr2[1]);
                    /*Array(
                        [0] =>  class="ui tiny header">Created
                        [1] =>  class="ui tiny header">Updated
                        [2] =>  class="ui tiny header">Collected
                    )*/
                    foreach($arr2[1] as $tmp) {
                        $tmp = trim($tmp);
                        $tmp = "<".$tmp;
                        $tmp = trim(strip_tags($tmp));
                        $date_type[] = $tmp;
                    }
                }
                if(preg_match_all("/<dd>(.*?)<\/dd>/ims", $arr[1], $arr3)) { //echo "\ngoes date 4\n";
                    // print_r($arr3[1]);
                    /*Array(
                        [0] => 
                        <div>2017-10-02</div>
                        <div class="text-muted">1st</div>
                        [1] => 
                        <div>2017-10-03</div>
                        <div class="text-muted">2nd</div>
                        [2] => 
                        <div>2017-10-04</div>
                        <div class="text-muted">3rd</div>    
                    )*/
                    foreach($arr3[1] as $tmp) {
                        $tmp = trim($tmp);
                        if(preg_match("/<div>(.*?)<\/div>/ims", $tmp, $arr4)) $date_actual[] = $arr4[1];
                        if(preg_match("/\">(.*?)<\/div>/ims", $tmp, $arr4))   $date_desc[]   = $arr4[1];
                    }
                }
            }
        }
        $final = array();
        if($date_type && $date_actual && $date_desc) { //echo "\ngoes date 5\n";
            $i = -1;
            foreach($date_type as $type) { $i++;
                $desc = @$date_desc[$i] ? $date_desc[$i] : "";
                if($type == 'Updated') {
                    if($updateDate_set2Current_YN) { //orig
                        $start = date("Y-m-d");
                        $end   = date("Y-m-d");
                    }
                    else { //for katja's latest changes
                        $start = @$date_actual[$i];
                        $end   = @$date_actual[$i];    
                    }
                }
                elseif($type == 'Other' && $desc == "metadata updated") {
                    $start = date("Y-m-d");
                    $end   = date("Y-m-d");                
                }
                else {
                    $start = @$date_actual[$i];
                    $end   = @$date_actual[$i];
                }
                $final[] = array("start" => $start, "end" => $end, "type" => $type, "description" => $desc);
            }
        }
        // print_r($final); exit("\n-end date process-\n");
        return $final;
    }
    private function get_data_record_from_html($obj, $what, $expire_seconds)
    {   /*
        <div class="column"
         id="recordManagement"
         data-record='{"access": {"embargo": {"active": false, "reason": null}, "files": "public", "record": "public", "status": "open"},...
         data-permissions='{"can_edit": true, "can_manage": true, "can_media_read_files": true, "can_moderate": false, "can_new_version": true, "can_read_files": true, "can_review": true, "can_update_draft": true, "can_view": true}'
         data-is-draft="false"
         data-is-preview-submission-request="false"
         data-current-user-id="1158728"
         data-record-owner-username='eagbayani'
         data-groups-enabled='false'
        >        
        */
        /*
        [{ "person_or_org": {
                                "identifiers": [{"identifier": "01na82s61", "scheme": "ror"}, {"identifier": "0000 0004 0478 6311", "scheme": "isni"}], 
                                "name": "United States Department of Agriculture", 
                                "type": "organizational"
                            }, 
           "role": {"id": "hostinginstitution", "title": {"de": "Bereitstellende Institution", "en": "Hosting institution"}}
        }]
        */
        $url = $obj['links']['html'];
        $url = str_replace("deposit", "records", $url);
        $options = $this->download_options;
        // $options['expire_seconds'] = 0; //60*60*24;
        $options['expire_seconds'] = $expire_seconds;
        if($html = Functions::lookup_with_cache($url, $options)) { //exit("\n$html\n");
            if($what == 'contributors') {
                $left = '"contributors":';
                $right = '"creators":';    
            }
            elseif($what == 'creators') {
                $left = '"creators":';
                $right = '"dates":';    
            }
            elseif($what == 'creators2') {
                $left = '"creators":';
                $right = '"publication_date":';    
            }
            else return;

            // echo "\ndito 0\n"; //exit("\n$html\n");
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) { //echo "\ndito 1\n"; 
                $json = substr_replace(trim($arr[1]), '', -1); //remove last char
                if($arr = json_decode($json, true)) {
                    print_r($arr); //exit("\nvery gud!\n"); //very good debug 

                    // /* New:
                    if(in_array($what, array('creators', 'creators2'))) {
                        if(self::there_is_role($arr)) $this->log_error(array("There is role in Creator: ", $obj['id']));
                    }
                    // */

                    foreach(@$arr as $r) {
                        if($name = @$r['person_or_org']['name']) {
                            if($identifiers = @$r['person_or_org']['identifiers']) {
                                foreach($identifiers as $i) {
                                    if($scheme = @$i['scheme']) {
                                        /* can be refactored
                                        if($scheme == 'isni') $this->html_contributors[$name]['isni'] = $i['identifier'];  //orcid         html isni 0000 0004 0478 6311
                                        if($scheme == 'ror') $this->html_contributors[$name]['ror'] = $i['identifier'];    //important     html ror 01na82s61
                                        // only orcid and gnd are in doc examples
                                        if($scheme == 'gnd') $this->html_contributors[$name]['gnd'] = $i['identifier'];
                                        if($scheme == 'orcid') $this->html_contributors[$name]['orcid'] = $i['identifier'];
                                        */
                                        $this->html_contributors[$name][$scheme] = $i['identifier'];
                                    }
                                }
                            }
                        }
                    } //end foreach()    
                }
            }
        }
    }
    private function there_is_role($arr)
    {
        /*Array(
            [0] => Array(
                    [affiliations] => Array(
                            [0] => Array(
                                    [name] => National Museum of Natural History, Smithsonian Institution
                                )
                        )
                    [person_or_org] => Array(
                            [family_name] => Schulz
                            [given_name] => Katja
                            [identifiers] => Array(
                                    [0] => Array(
                                            [identifier] => 0000-0001-7134-3324
                                            [scheme] => orcid
                                        )
                                )
                            [name] => Schulz, Katja
                            [type] => personal
                        )
                    [role] => Array(
                            [id] => datamanager
                            [title] => Array(
                                    [de] => DatenmanagerIn
                                    [en] => Data manager
                                )
                        )
                )
        )
        */
        foreach($arr as $r) {
            if(@$r['role']) return true;
        }
    }
    private function remove_all_in_between_inclusive($left, $right, $html, $includeRight = true)
    {
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                if($includeRight) { //original
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, '', $html);
                }
                else { //meaning exclude right
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, $right, $html);
                }
            }
        }
        return $html;
    }
    function format_description($desc)
    {
        // ####--- __EOL DwCA resource last updated: Jul 17, 2023 07:41 AM__ ---####
        // "####--- __"."EOL DwCA resource last updated: ".$this->date_format."__ ---####";
        $left  = "####--- __";
        $right = "__ ---####";
        $desc = self::remove_all_in_between_inclusive($left, $right, $desc, true);

        $arr = explode("\n", $desc); //print_r($arr);
        // echo "\nlast element is: [".end($arr)."]\n";
        if(end($arr) == "") {} //echo "\nlast element is nothing\n";
        else $desc .= chr(13); //add a next line

        /* working OK but now obsolete
        $forced_date = date("m/d/Y H:i:s"); //date today
        $date = strtotime($forced_date);
        $date_format = date("M d, Y h:i A", $date);  //July 13, 2023 08:30 AM
        // $this->iso_date_str = self::iso_date_format()
        $add_str = "####--- __"."EOL DwCA resource last updated: ".$date_format."__ ---####";
        $desc .= $add_str;
        */
        return $desc;
    }
    private function remove_null_make_unique_reindex_key($arr)
    {
        $arr = array_filter($arr); //remove null arrays
        $arr = array_unique($arr); //make unique
        $arr = array_values($arr); //reindex key
        return $arr;
    }
    private function is_name_in_Contributors($name, $contributors)
    {
        foreach($contributors as $r) {
            if($r['name'] == 'Eli Agbayani') return true;
        }
        return false;
    }
}
?>