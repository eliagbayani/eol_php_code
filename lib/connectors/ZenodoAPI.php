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
            'expire_seconds'     => 60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->debug = array();
        $this->api['domain'] = 'https://zenodo.org';
        if(Functions::is_production()) $this->path_2_file_dat = '/extra/other_files/Zenodo/';
        else                           $this->path_2_file_dat = '/Volumes/OWC_Express/other_files/Zenodo/';
        if(!is_dir($this->path_2_file_dat)) mkdir(path_2_file_dat);
    }

    function start()
    {
        echo "\n".ZENODO_TOKEN."\n";
        // self::create_dataset();
        $obj = self::retrieve_dataset('13136202'); self::upload_dataset($obj);
        // self::publish_dataset();
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
    private function publish_dataset()
    {
        
        $cmd = 'curl -i -H "Content-Type: application/json" -X POST https://zenodo.org/api/deposit/depositions/13136202/actions/publish?access_token='.ZENODO_TOKEN;
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
}
?>