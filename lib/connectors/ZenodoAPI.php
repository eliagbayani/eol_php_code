<?php
namespace php_active_record;
/* 1st client: zenodo.php
*/
class ZenodoAPI
{
    function __construct($folder = null, $query = null)
    {
        $this->download_options = array(
            'resource_id'        => 'zenodo',  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30, //maybe 1 day to expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);
        // $this->download_options['expire_seconds'] = 0;

        $this->debug = array();
        $this->api['domain'] = 'https://zenodo.org';
        if(Functions::is_production()) $this->path_2_file_dat = '/extra/other_files/Zenodo/';
        else                           $this->path_2_file_dat = '/Volumes/OWC_Express/other_files/Zenodo/';
        if(!is_dir($this->path_2_file_dat)) mkdir($this->path_2_file_dat);
        /*
        https://opendata.eol.org/api/3/action/package_list
        */
        $this->ckan['organization_list'] = 'https://opendata.eol.org/api/3/action/organization_list';
        $this->ckan['organization_show'] = 'https://opendata.eol.org/api/3/action/organization_show?id=ORGANIZATION_ID&include_datasets=true';
        // https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life&include_datasets=true
        // https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life

        $this->ckan['package_show'] = 'https://opendata.eol.org/api/3/action/package_show?id='; //e.g. images-list
        // https://opendata.eol.org/api/3/action/package_show?id=images-list


        /* very helpful
        https://www.whatismybrowser.com/detect/what-is-my-user-agent/
        */
        $this->ckan['user_show'] = 'https://opendata.eol.org/api/3/action/user_show?id='; //e.g. 47d700d6-0f4c-43e8-a0c5-a5e739bc390c

        // https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life&include_datasets=true
        // useful to get all datasets; even private ones

        $this->temp_count = 0;
    }

    function list_all_datasets($privateYN = 1)
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
                        if($privateYN) $label = 'private datasets';
                        else $label = 'public datasets';
                        if($p['private'] == $privateYN) {
                            $final[$org_title_name][$label][$package_title_name] = '';

                            // /* loop to each of the resources of a package
                            $package_obj = self::lookup_package_using_id($p['id']);
                            foreach(@$package_obj['result']['resources'] as $r) { //print_r($p); print_r($r); 
                                if($val = @$r['url']) { $e++; //"https://eol.org/data/media_manifest.tgz"
                                    $this->debug['urls'][$val] = '';
                                    $parse = parse_url($val);
                                    $this->debug['urls pathinfo'][$parse['host']] = '';
                                    self::deal_with_ckan_urls($val, $parse, $r);
                                }
                                // exit("\na resource object\n");
                            }
                            // */
                        }
                    }
                }

                // break; //debug only
                // if($e >= 10) break; //debug only
            }
        }
        echo "\n\n"; //print_r($final);
        // print_r($this->debug);
    }
    function start()
    {
        if($json = Functions::lookup_with_cache($this->ckan['organization_list'], $this->download_options)) {
            $o = json_decode($json, true); //print_r($o);
            foreach($o['result'] as $organization_id) {
                if($organization_id != 'encyclopedia_of_life') continue; //Aggregate Datasets //debug only dev only
                echo "\n" . $organization_id;
                self::process_organization($organization_id);
            }
        }
        print_r($this->debug);
    }
    private function process_organization($organization_id)
    {
        $url = str_replace('ORGANIZATION_ID', $organization_id, $this->ckan['organization_show']);
        // $url = 'https://opendata.eol.org/api/3/action/organization_show?id=encyclopedia_of_life&include_datasets=true';
        $url = 'http://localhost/other_files2/Zenodo_files/json/encyclopedia_of_life.json'; //an organization: Aggregate Datasets
        $url = "http://localhost/other_files2/Zenodo_files/json/".$organization_id.".json";
        $url = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/Zenodo/json/".$organization_id.".json";


        // if($json = Functions::get_remote_file_fake_browser($url, $this->download_options)) //worked but still doesn't include all datasets

        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $o = json_decode($json, true); //print_r($o);
            echo "\npackage_count: ".$o['result']['package_count'];
            foreach($o['result']['packages'] as $p) {
                // if($p['title'] != 'Images list') continue; //debug only dev only
                // if($p['title'] != 'EOL computer vision pipelines') continue; //debug only dev only

                // print_r($p); exit;
                if($p['private'] == 'true') continue;
                // if($p['private'] == 'false' || $p['private'] == '') continue;

                self::process_a_package($p);
            }
        }
    }
    private function process_a_package($p)
    {   // loop to each of the resources of a package
        $package_obj = self::lookup_package_using_id($p['id']);
        foreach(@$package_obj['result']['resources'] as $r) { print_r($p); print_r($r); 
            $input = self::generate_input_field($p, $r);
            // exit("\na resource object\n");
        }
    }
    private function generate_input_field($p, $r) //todo loop into resources and have $input for each resource...
    {
        $input = array();
        // -------------------------------------------------------------------
        $dates = array();
        // if($val = $p['metadata_created'])   $dates[] = array("start" => $val, "end" => $val, "type" => "Created");
        // if($val = $p['metadata_modified'])  $dates[] = array("start" => $val, "end" => $val, "type" => "Updated");
        if($val = @$r['created'])           $dates[] = array("start" => $val, "end" => $val, "type" => "Created");
        if($val = @$r['last_modified'])     $dates[] = array("start" => $val, "end" => $val, "type" => "Updated");
        // -------------------------------------------------------------------
        $creators = array();
        $creator = "";
        if($val = $p['creator_user_id']) {
            if($user_obj = self::lookup_user_using_id($val)) {
                if(@$user_obj['result']['sysadmin'] == 'true') $affiliation = "Encyclopedia of Life";
                else                                           $affiliation = "";
                if($creator = @$user_obj['result']['fullname']) {
                    $creators[] = array("name" => $creator, "affiliation" => $affiliation);
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
        $license = 'cc-by';
        if($val = @$p['license_id']) {
            if($val != 'notspecified') $license = $val;
        }
        // -------------------------------------------------------------------
        /*Controlled vocabulary:
            * open: Open Access
            * embargoed: Embargoed Access
            * restricted: Restricted Access
            * closed: Closed Access        
        */
        $access_conditions = '';
        if($p['private'] == 'false') $access_right = 'open';
        else {
            $access_right = 'restricted';
            $access_conditions = 'This is not available publicly. Only community members can see this record.';
        }
        // -------------------------------------------------------------------
        $notes = "";
        if($val = @$r['description']) $notes = $val;
        if($notes) $notes .= "\n".$p['organization']['description'];
        else       $notes = $p['organization']['description'];
        // -------------------------------------------------------------------
        $input['metadata'] = array( "title" => $p['title'].": ".$r['name'], //"Images list: image list",
                                    "upload_type" => "dataset", //controlled vocab.
                                    "description" => $p['notes'],
                                    "creators" => $creators, 
                                    //Example: [{'name':'Doe, John', 'affiliation': 'Zenodo'}, 
                                    //          {'name':'Smith, Jane', 'affiliation': 'Zenodo', 'orcid': '0000-0002-1694-233X'}, 
                                    //          {'name': 'Kowalski, Jack', 'affiliation': 'Zenodo', 'gnd': '170118215'}]
                                    "keywords" => array($p['organization']['title'].": ".$p['title']), //array("Aggregate Datasets: Images list"),
                                    // "publication_date" => "2020-02-04", //required. Date of publication in ISO8601 format (YYYY-MM-DD). Defaults to current date.                                                                        
                                    "notes" => $notes, //"For questions or use cases calling for large, multi-use aggregate data files, please visit the EOL Services forum at http://discuss.eol.org/c/eol-services",
                                    "communities" => array(array("identifier" => "eol")), //Example: [{'identifier':'eol'}]
                                    "dates" => $dates, //Example: [{"start": "2018-03-21", "end": "2018-03-25", "type": "Collected", "description": "Specimen A5 collection period."}]
                                    "related_identifiers" => $related_identifiers, 
                                    //Example: [{'relation': 'isSupplementTo', 'identifier':'10.1234/foo'}, {'relation': 'cites', 'identifier':'https://doi.org/10.1234/bar', 'resource_type': 'image-diagram'}]
                                    "access_right" => $access_right, //defaults to 'open'
                                    "license" => $license,
                            );        
        print_r($input); //exit("\nstop muna\n");
        return $input;
    }
    function test()
    {
        // echo "\n".ZENODO_TOKEN."\n";
        // self::create_dataset();
        // $obj = self::retrieve_dataset('13136202'); self::upload_dataset($obj); //worked OK
        // $obj = self::retrieve_dataset('13136202'); self::publish_dataset($obj); //worked OK

        // $obj = self::retrieve_dataset('13136202'); self::update_dataset($obj); //didn't work yet

    }
    private function update_dataset($obj)
    {
        /*
        curl -i -H "Content-Type: application/json" -X PUT
        --data '{"metadata": {"title": "My first upload", "upload_type": "poster", 
                    "description": "This is my first upload", 
                    "creators": [{"name": "Doe, John", "affiliation": "Zenodo"}]}}' https://zenodo.org/api/deposit/depositions/1234?access_token=ACCESS_TOKEN        
        */
        $input = array();
        $input['metadata'] = array("communities" => array(array("identifier" => "eol")), //Example: [{'identifier':'eol'}]
                            );        

        $input['metadata'] = array( "title" => "Images list",
        "upload_type" => "dataset", //controlled vocab.
        "description" => "
All the images in EOL, with minimal metadata:
EOL content ID (can construct eol media page url, eg: https://eol.org/media/15263823)
EOL page ID (can construct eol taxon page url, eg: https://eol.org/pages/45511218)
Medium Source URL (if available, eg: https://animaldiversity.org/collections/contributors/phil_myers/ADW_birds_3_4_03/Passeriformes/Emberizidae/lark_sparrow7789/large.jpg)
EOL Full-Size Copy URL (recommended for download, eg: https://content.eol.org/data/media/be/4a/08/30.d4f396d82dcceeb0615c194f99421c0c.jpg)
License (eg: cc-by-nc-sa-3.0, see https://creativecommons.org/licenses/ for details)
Copyright Owner (unless image is in the Public Domain)
                            ",
                            "creators" => array(array("name" => "EOL staff", "affiliation" => "Encyclopedia of Life")), 
                            //Example: [{'name':'Doe, John', 'affiliation': 'Zenodo'}, 
                            //          {'name':'Smith, Jane', 'affiliation': 'Zenodo', 'orcid': '0000-0002-1694-233X'}, 
                            //          {'name': 'Kowalski, Jack', 'affiliation': 'Zenodo', 'gnd': '170118215'}]
                            "keywords" => array("Aggregate Datasets"),
                            // "publication_date" => "2020-02-04", //required. Date of publication in ISO8601 format (YYYY-MM-DD). Defaults to current date.                                                                        
                            "notes" => "For questions or use cases calling for large, multi-use aggregate data files, please visit the EOL Services forum at http://discuss.eol.org/c/eol-services",
                            "communities" => array(array("identifier" => "eol")), //Example: [{'identifier':'eol'}]
                            "dates" => array(array("start" => "2020-02-04", "end" => "2020-02-04", "type" => "Created"), //CKAN's created
                                             array("start" => "2024-07-26", "end" => "2024-07-26", "type" => "Updated"), //CKAN's updated   
                                            ), //Example: [{"start": "2018-03-21", "end": "2018-03-25", "type": "Collected", "description": "Specimen A5 collection period."}]
                            "related_identifiers" => array(array("relation" => "isSupplementTo", 
                                                                 "identifier" => "https://eol.org/data/media_manifest.tgz",
                                                                 "resource_type" => "dataset")), 
                            //Example: [{'relation': 'isSupplementTo', 'identifier':'10.1234/foo'}, 
                            //          {'relation': 'cites', 'identifier':'https://doi.org/10.1234/bar', 'resource_type': 'image-diagram'}]
                            "access_right" => "open", //defaults to 'open'
                            "license" => "cc-by",
                    );        

        $json = json_encode($input); // echo "\n$json\n";
        print_r($input);

        $cmd = 'curl -i -H "Content-Type: application/json" -X PUT --data '."'$json'".' https://zenodo.org/api/deposit/depositions/'.$obj['id'].'?access_token='.ZENODO_TOKEN;
        // $cmd = 'curl -i -H "Content-Type: application/json" -X PUT --data '."'$json'".' https://zenodo.org/api/deposit/depositions/13136202?access_token='.ZENODO_TOKEN;

        // $cmd .= " 2>&1";
        echo "\n$cmd\n";
        $json = shell_exec($cmd);           echo "\n$json\n";
        $obj = json_decode(trim($json));    print_r($obj);
    }
    private function retrieve_dataset($id)
    {
        echo "\nRetrieving ".$id."...\n";
        // $cmd = 'curl -i '.$this->api['domain'].'/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN; //orig from Zenodo, -i more complete output. Not used.
        $cmd = 'curl -s '.$this->api['domain'].'/api/deposit/depositions/'.$id.'?access_token='.ZENODO_TOKEN; //better curl output, -s just the json output.
        $cmd .= " 2>&1";
        $json = shell_exec($cmd);               //echo "\n--------------------\n$json\n--------------------\n";
        $obj = json_decode(trim($json), true);  //echo "\n=====\n"; print_r($obj); echo "\n=====\n";
        return $obj;
    }
    private function upload_dataset($obj)
    {
        echo "\nUploading ".$obj['id']."...\n";
        self::initialize_file_dat($obj);
        $bucket = $obj['links']['bucket']; //e.g. https://zenodo.org/api/files/6c1d26b0-7b4a-41e3-a0e8-74cf75710946 // echo "\n[$bucket]\n";
        $cmd = 'curl --upload-file /path/to/your/file.dat https://zenodo.org/api/files/6c1d26b0-7b4a-41e3-a0e8-74cf75710946/file.dat?access_token='.ZENODO_TOKEN;
        $cmd = 'curl --upload-file '.self::generate_file_dat($obj).' '.$bucket.'/'.$obj['id'].'.dat?access_token='.ZENODO_TOKEN;
        echo "\n$cmd\n";
        $json = shell_exec($cmd);               echo "\n$json\n";
        $obj = json_decode(trim($json), true);  print_r($obj);
    }
    private function publish_dataset($obj)
    {
        $publish = $obj['links']['publish']; //https://zenodo.org/api/deposit/depositions/13136202/actions/publish
        $cmd = 'curl -i -H "Content-Type: application/json" -X POST https://zenodo.org/api/deposit/depositions/13136202/actions/publish?access_token='.ZENODO_TOKEN;
        $cmd = 'curl -i -H "Content-Type: application/json" -X POST '.$publish.'?access_token='.ZENODO_TOKEN;
        // $cmd .= " 2>&1";
        echo "\n$cmd\n";
        $json = shell_exec($cmd);           echo "\n$json\n";
        $obj = json_decode(trim($json));    print_r($obj);

    }
    private function create_dataset()
    {   /* sample: https://developers.zenodo.org/?shell#create
        curl -i -H "Content-Type: application/json" -X POST
        --data '{"metadata": {  "title": "My first upload", 
                                "upload_type": "poster", 
                                "description": "This is my first upload", 
                                "creators": [{"name": "Doe, John", "affiliation": "Zenodo"}]}}' /api/deposit/depositions/?access_token=ACCESS_TOKEN
        */
        $input = array();
        $input['metadata'] = array( "title" => "Images list",
                                    "upload_type" => "dataset", //controlled vocab.
                                    "description" => "
All the images in EOL, with minimal metadata:
EOL content ID (can construct eol media page url, eg: https://eol.org/media/15263823)
EOL page ID (can construct eol taxon page url, eg: https://eol.org/pages/45511218)
Medium Source URL (if available, eg: https://animaldiversity.org/collections/contributors/phil_myers/ADW_birds_3_4_03/Passeriformes/Emberizidae/lark_sparrow7789/large.jpg)
EOL Full-Size Copy URL (recommended for download, eg: https://content.eol.org/data/media/be/4a/08/30.d4f396d82dcceeb0615c194f99421c0c.jpg)
License (eg: cc-by-nc-sa-3.0, see https://creativecommons.org/licenses/ for details)
Copyright Owner (unless image is in the Public Domain)
                                    ",
                                    "creators" => array(array("name" => "EOL staff", "affiliation" => "Encyclopedia of Life")), 
                                    //Example: [{'name':'Doe, John', 'affiliation': 'Zenodo'}, 
                                    //          {'name':'Smith, Jane', 'affiliation': 'Zenodo', 'orcid': '0000-0002-1694-233X'}, 
                                    //          {'name': 'Kowalski, Jack', 'affiliation': 'Zenodo', 'gnd': '170118215'}]
                                    "keywords" => array("Aggregate Datasets"),
                                    // "publication_date" => "2020-02-04", //required. Date of publication in ISO8601 format (YYYY-MM-DD). Defaults to current date.                                                                        
                                    "notes" => "For questions or use cases calling for large, multi-use aggregate data files, please visit the EOL Services forum at http://discuss.eol.org/c/eol-services",
                                    "communities" => array(array("identifier" => "eol")), //Example: [{'identifier':'eol'}]
                                    "dates" => array(array("start" => "2020-02-04", "end" => "2020-02-04", "type" => "Created"), //CKAN's created
                                                     array("start" => "2024-07-26", "end" => "2024-07-26", "type" => "Updated"), //CKAN's updated   
                                                    ), //Example: [{"start": "2018-03-21", "end": "2018-03-25", "type": "Collected", "description": "Specimen A5 collection period."}]
                                    "related_identifiers" => array(array("relation" => "isSupplementTo", 
                                                                         "identifier" => "https://eol.org/data/media_manifest.tgz",
                                                                         "resource_type" => "dataset")), 
                                    //Example: [{'relation': 'isSupplementTo', 'identifier':'10.1234/foo'}, {'relation': 'cites', 'identifier':'https://doi.org/10.1234/bar', 'resource_type': 'image-diagram'}]
                                    "access_right" => "open", //defaults to 'open'
                                    "license" => "cc-by",
                            );        
        $json = json_encode($input);
        print_r($input);
        // echo "\n$json\n";

        $cmd = 'curl -i -H "Content-Type: application/json" -X POST --data '."'$json'".' https://zenodo.org/api/deposit/depositions?access_token='.ZENODO_TOKEN;
        // $cmd .= " 2>&1";
        echo "\n$cmd\n";
        $json = shell_exec($cmd);           echo "\n$json\n";
        $obj = json_decode(trim($json));    print_r($obj);

        // $cmd = 'curl -s "'.$url.'" -H "X-Authentication-Token:'.$this->service['token'].'"';
        // $cmd = 'curl --insecure --include --user '.$this->gbif_username.':'.$this->gbif_pw.' --header "Content-Type: application/json" --data @'.$filename.' -s https://api.gbif.org/v1/occurrence/download/request';
        // $output = shell_exec($cmd);
        // echo "\nRequest output:\n[$output]\n";
    
    }



    // ===================================== copied template below =====================================
    function parse_citation_using_anystyle_cli($citation, $input_file)
    {
        $WRITE = Functions::file_open($input_file, "w");
        fwrite($WRITE, $citation);
        fclose($WRITE);

        $cmd = "$this->anystyle_path --parser-model $this->anystyle_parse_model_core  --stdout -f json parse $input_file";
        // $cmd = "$this->anystyle_path --parser-model $this->anystyle_parse_model_gold  --stdout -f json parse $input_file";
        // $cmd = "$this->anystyle_path                                                  --stdout -f json parse $input_file";

        $json = shell_exec($cmd);
        // /* seems not needed here, only in Ruby below
        $json = substr(trim($json), 1, -1); # remove first and last char
        // $json = str_replace("\\", "", $json); # remove "\" from converted json from Ruby
        // */

        // $arr = json_decode($json, true); print_r($arr); //exit("\ncheck anystle output\n"); //debug only

        $obj = json_decode($json); //print_r($obj); //good debug
        return $obj;
    }

    function parse_citation_using_anystyle($citation, $what, $series = false) //$series is optional
    {
        // echo("\n----------\nthis runs ruby...[$what][$series]\n----------\n"); //comment in real operation
        $json = shell_exec($this->anystyle_parse_prog . ' "'.$citation.'"');
        $json = substr(trim($json), 1, -1); # remove first and last char
        $json = str_replace("\\", "", $json); # remove "\" from converted json from Ruby
        $obj = json_decode($json); //print_r($obj);
        if($what == 'all') return $obj;
        elseif($what == 'title') {
            if($val = @$obj[0]->title[0]) return $val;
            else {
                echo "\n---------- no title -------------\n";
                // print_r($obj); 
                echo "\ncitation:[$citation]\ntitle:[$what]\n";
                echo "\n---------- end -------------\n";
                return "-no title-";
            }
        }
        echo ("\n-end muna-\n");
    }
    private function initialize_file_dat($obj)
    {
        $file = self::generate_file_dat($obj);
        $WRITE = Functions::file_open($file, "w");
        fwrite($WRITE, json_encode($obj));
        fclose($WRITE);
    }
    private function generate_file_dat($obj)
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
        $options['expire_seconds'] = false; //doesn't expire
        if($json = Functions::lookup_with_cache($this->ckan['package_show'].$id, $options)) {
            $o = json_decode($json, true); //print_r($o); exit;
            return $o;
        }
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
            else echo "\n[$cmd COPIED already.]\n";
            print_r($r);
            echo "\nold url: $url";
            echo "\nnew_url: $new_url\n";
        }
        elseif(is_file($destination)) echo "\nCopied already.\n";
        else {
            print_r($r);
            exit("\nFile not found. Investigate.\n"); //main operation
        }

        if($this->temp_count >= 10) exit("\nstop muna 2\n");
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
        // $cmd = 'curl https://opendata.eol.org/api/3/action/resource_update'; // orig but not used here.
        $cmd = 'curl https://opendata.eol.org/api/3/action/resource_patch';     // those fields not updated will remain
        $cmd .= " -d '".$json."'";
        $cmd .= ' -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"';
        // */

        sleep(2); //may need to delay since there are many records involved
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

}
?>