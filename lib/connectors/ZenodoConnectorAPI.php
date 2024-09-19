<?php
namespace php_active_record;
/* */
class ZenodoConnectorAPI
{
    function __construct($folder = null, $query = null)
    {}
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
        echo "\n$cmd\n";
        $json = shell_exec($cmd);           echo "\n$json\n";
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
    function get_dates_entries_from_html($obj)
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
        $options['expire_seconds'] = 60*60*24;
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
                if($type == 'Updated') {
                    $start = date("Y-m-d");
                    $end   = date("Y-m-d");
                }
                else {
                    $start = @$date_actual[$i];
                    $end   = @$date_actual[$i];
                }
                $final[] = array("start" => $start, "end" => $end, "type" => $type, "description" => $date_desc[$i]);
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
        $desc .= $add_str;
        return $desc;
    }
}
?>