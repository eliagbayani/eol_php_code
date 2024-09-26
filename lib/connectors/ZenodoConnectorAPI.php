<?php
namespace php_active_record;
/* */
class ZenodoConnectorAPI
{
    function __construct($folder = null, $query = null)
    {}
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ start @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    function latest_katja_changes()
    {   // step 1: loop into all Zenodo records
        /*
        $final = array(); $page = 0;
        while(true) { $page++;
            $cmd = 'curl -X GET "https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN.'&size=25&page=PAGENUM" -H "Content-Type: application/json"';
            $cmd = str_replace('PAGENUM', $page, $cmd);
            // echo "\nlist depostions cmd: [$cmd]\n";
            $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
            $obj = json_decode(trim($json), true);  //echo "\n=====\n"; print_r($obj); echo "\n=====\n";
            if(!$obj) break;
            echo "\nBatch: $page | No. of records: ".count($obj)."\n";
            foreach($obj as $o)  { print_r($o); exit;
                $final[trim($o['id'])] = '';
                @$stats[$o['title']]++;
            }
        }
        */
        $id = "13795618"; //Metrics: GBIF data coverage
        // $id = "13795451"; //Flickr: USGS Bee Inventory and Monitoring Lab
        // $id = "13794884"; //Flickr: Flickr BHL (544) --- nothing happened
        // $id = "13789577"; //Flickr: Flickr Group (15) --- nothing happened
        // $id = 13317938; //National Checklists 2019: Réunion Species List

        self::update_zenodo_record_of_latest_requested_changes($id);
    }
    function update_zenodo_record_of_latest_requested_changes($zenodo_id)
    {
        $obj_1st = $this->retrieve_dataset($zenodo_id); print_r($obj_1st); //exit("\nstop muna\n");
        $id = $obj_1st['id'];
        if($zenodo_id != $id) exit("\nInvestigate not equal IDs: [$zenodo_id] != [$id]\n");

        $edit_obj = $this->edit_Zenodo_dataset($obj_1st); //exit("\nstop muna 1\n");

        if($this->if_error($edit_obj, 'edit_0924', $id)) {}
        else {
            $obj_latest = self::fill_in_katja_changes($edit_obj); //$obj_1st
            // $obj_temp = self::cut_down_object($obj_latest);
            // self::update_then_publish($id, $obj_temp);
            self::update_then_publish($id, $obj_latest);    
        }
    }
    private function update_then_publish($id, $obj_latest)
    {
        $update_obj = $this->update_Zenodo_record_latest($id, $obj_latest); //to fill-in the publication_date, title creators upload_type et al.
        if($this->if_error($update_obj, 'update_0924', $id)) {}
        else {
            $new_obj = $update_obj;
            // /* publishing block
            $publish_obj = $this->publish_Zenodo_dataset($new_obj); //worked OK but with cumulative files carry-over
            if($this->if_error($publish_obj, 'publish', $new_obj['id'])) {}
            else {
                echo "\nSuccessfully UPDATED then PUBLISHED to Zenodo\n-----u & p-----\n";
                // $this->log_error(array('updated then published', @$new_obj['id'], @$new_obj['metadata']['title'], @$new_obj['metadata']['related_identifiers'][0]['identifier']));
                $this->log_error(array('updated then published', @$new_obj['id'], @$new_obj['metadata']['title']));
            }
            // */            
        }
    }
    private function fill_in_katja_changes($o)
    {   // print_r($o); exit("\nstop muna 1\n");
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

        // /* ------------------ creators
        $final = array();
        foreach($o['metadata']['creators'] as $r) {
            if($r['name'] == 'script') $final[] = array('name' => 'Encyclopedia of Life', 'type' => 'HostingInstitution', 'affiliation' => '');
        }
        if(!$final) $final[] = array('name' => 'Encyclopedia of Life', 'type' => 'HostingInstitution', 'affiliation' => '');
        $o['metadata']['creators'] = $final;
        // */
        // /* ------------------ contributors
        $final = array();
        foreach($o['metadata']['contributors'] as $r) {
            if($r['type'] == 'HostingInstitution'     && $r['name'] == 'Anne Thessen') $final[] = array('name' => $r['name'], 'type' => 'DataManager',   'affiliation' => 'Encyclopedia of Life');
            elseif($r['type'] == 'HostingInstitution' && $r['name'] == 'Eli Agbayani') $final[] = array('name' => $r['name'], 'type' => 'DataCollector', 'affiliation' => 'Encyclopedia of Life');
            elseif($r['type'] == 'HostingInstitution')                                 $final[] = array('name' => $r['name'], 'type' => 'DataManager',   'affiliation' => 'Encyclopedia of Life');
            else $final[] = $r;
        }
        $o['metadata']['contributors'] = $final;
        // */

        /* Keywords & subjects
        1. For all data sets with keyword "EOL Content Partners: National Checklists 2019" or "EOL Content Partners: Water Body Checklists 2019" add keyword "deprecated"
        2. Remove all keywords with the prefix "format:", e.g., "format: ZIP", "format: TAR", "format: XML", etc.        
        
        [keywords] => Array(
                    [0] => EOL Content Partners: National Checklists 2019
                    [1] => format: Darwin Core Archive
                )        
        */
        // #1
        $keywords = $o['metadata']['keywords'];
        if(in_array('EOL Content Partners: National Checklists 2019', $keywords) || in_array('EOL Content Partners: National Checklists 2019', $keywords)) {
            if(!in_array('deprecated', $keywords)) $keywords[] = 'deprecated';
        }
        // #2
        $final = array();
        foreach($keywords as $kw) {
            if(substr($kw,0,8) != 'format: ') $final[] = $kw;
        }
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


        // print_r($o); exit("\nstop muna 1\n");
        return $o;
    }
    private function cut_down_object($obj)
    {
        // print_r($obj); exit("\nelix 4\n");

        if($val = @$o['metadata']['creators'][0]['affiliation']) {
            $o['metadata']['creators'][0]['affiliation'] = "";
        }

        $obj['metadata']['notes'] = "";
        $obj['metadata']['contributors'] = array();
        return $obj;
    }
    function update_Zenodo_record_latest($id, $obj_1st) //this updates the newversion object
    {
        $ret_obj = $this->retrieve_dataset($id);
        // $links_edit = $ret_obj['links']['edit']; //not used
        $links_publish = $ret_obj['links']['publish'];

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
        // if($val = $this->new_description_for_zenodo) $notes = $val; // this wasn't implemented
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
                                    "keywords" => @$obj_1st['metadata']['keywords'],
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

        if($val = @$obj_1st['metadata']['description']) $input['metadata']['description'] = $val; //impt. bec. metadata description must not be blank.

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
        else echo "\nERROR: newversion object not created!\n";
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
        // if($val = $this->new_description_for_zenodo) $notes = $val; // this wasn't implemented
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
                                    "keywords" => @$obj_1st['metadata']['keywords'],
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

        if($val = @$obj_1st['metadata']['description']) $input['metadata']['description'] = $val; //impt. bec. metadata description must not be blank.

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
        $filename = $this->EOL_resource_id_and_Zenodo_id_file;
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
        echo "\npassed:   [".$this->new_description_for_zenodo."]\n";
        $file = CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".tar.gz";
        if(file_exists($file)) {
            if($zenodo_id = self::get_zenodo_id_using_eol_resource_id($resource_id)) {
                self::update_zenodo_record_of_eol_resource($zenodo_id, $file); //https://zenodo.org/records/13240083 test record
            }
        }
        else echo "\nFile does not exist [$file]. No Zenodo record.\n";
    }
    private function get_zenodo_id_using_eol_resource_id($resource_id)
    {
        $file = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Zenodo/EOL_resource_id_and_Zenodo_id_file.tsv";
        $options = $this->download_options; 
        $options['expire_seconds'] = 60*60*24; //1 day cache
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
                    return $rec['Zenodo_id'];
                }
            }                
        }
        unlink($local_file);
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
        $options['expire_seconds'] = 0; //60*60*24;
        if($html = Functions::lookup_with_cache($url, $options)) { echo "\ngoes date 1 [$url]\n";
            if(preg_match("/>Dates<\/h3>(.*?)<\/dl>/ims", $html, $arr)) { echo "\ngoes date 2\n";
                if(preg_match_all("/<dt(.*?)<\/dt>/ims", $arr[1], $arr2)) { echo "\ngoes date 3\n";
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
                if(preg_match_all("/<dd>(.*?)<\/dd>/ims", $arr[1], $arr3)) { echo "\ngoes date 4\n";
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
        if($date_type && $date_actual && $date_desc) { echo "\ngoes date 5\n";
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

        $forced_date = date("m/d/Y H:i:s"); //date today
        $date = strtotime($forced_date);
        $date_format = date("M d, Y h:i A", $date);  //July 13, 2023 08:30 AM

        // $this->iso_date_str = self::iso_date_format()
        $add_str = "####--- __"."EOL DwCA resource last updated: ".$date_format."__ ---####";
        $add_str = "####--- __EOL DwCA resource last updated: Sep 24, 2024 12:49 PM__ ---####"; //for dev only debug only
        $desc .= $add_str;
        return $desc;
    }
}
?>