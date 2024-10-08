<?php
namespace php_active_record;
// connector: [xeno_canto.php]
class XenoCantoAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array(
            'resource_id'        => $this->resource_id,  //resource_id here is just a folder name in cache
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->domain = 'https://www.xeno-canto.org';
        $this->species_list = $this->domain.'/collection/species/all';
    }
    function start()
    {   
        if($html = Functions::lookup_with_cache($this->species_list, $this->download_options)) {
            // echo $html;
            if(preg_match_all("/<tr class(.*?)<\/tr>/ims", $html, $arr)) {
                // print_r($arr[1]);
                $i = 0;
                foreach($arr[1] as $r) {
                    /*[0] => ='new-species'>
                        <td>
                        <span clas='common-name'>
                        <a href="/species/Struthio-camelus">Common Ostrich</a>
                        </span>
                        </td>
                        <td>Struthio camelus</td>
                        <td></td>
                        <td align='right' width='20'>3</td>
                        <td align='right' width='30'>0</td>
                    */
                    $rec = array();
                    if(preg_match("/<span clas='common-name'>(.*?)<\/span>/ims", $r, $arr)) $rec['comname'] = trim(strip_tags($arr[1]));
                    if(preg_match("/href=\"(.*?)\"/ims", $r, $arr)) $rec['url'] = strip_tags($arr[1]);
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $r, $arr)) {
                        $rec['sciname'] = $arr[1][1];
                    }
                    $rec = array_map('trim', $rec);
                    if($rec['sciname'] && $rec['url']) {
                        $ret = self::prepare_media_records($rec);
                        self::write_taxon($ret['orig_rec']);
                        if($val = $ret['media']) self::write_media($val);
                        else continue; //didn't get anything for media
                    }
                    $i++;
                    // if($i >= 10) break;
                }
            }
            else echo "\nnothing found...\n";
        }
        else echo "\nno HTML\n";
        // exit("\n111\n");
        $this->archive_builder->finalize(TRUE);
    }
    private function parse_order_family($html, $orig_rec)
    {
        // Order: <a href='/explore/taxonomy?o=STRUTHIONIFORMES'>STRUTHIONIFORMES</a>
        if(preg_match("/Order:(.*?)<\/a>/ims", $html, $arr)) {
            if(preg_match("/o=(.*?)\'/ims", $arr[1], $arr2)) {
                $orig_rec['order'] = ucfirst(strtolower($arr2[1]));
            }
        }
        // Family: <a href='/explore/taxonomy?f=Struthionidae'>Struthionidae</a> (Ostriches)
        if(preg_match("/Family:(.*?)<\/a>/ims", $html, $arr)) {
            if(preg_match("/\?f=(.*?)\'/ims", $arr[1], $arr2)) {
                $orig_rec['family'] = ucfirst($arr2[1]);
            }
        }
        $orig_rec['taxonID'] = strtolower(str_replace(" ", "-", $orig_rec['sciname']));
        // print_r($orig_rec); exit;
        return $orig_rec;
    }
    private function prepare_media_records($rec)
    {
        $orig_rec = $rec;
        $final = array();
        if($html = Functions::lookup_with_cache($this->domain.$rec['url'], $this->download_options)) {
            // echo $html;
            $orig_rec = self::parse_order_family($html, $orig_rec);
            if(preg_match("/<table class=\"results\">(.*?)<\/table>/ims", $html, $arr)) {
                // echo $arr[1]; exit;
                $str = $arr[1];
                if(preg_match("/<thead>(.*?)<\/thead>/ims", $str, $arr2)) {
                    if(preg_match_all("/<th>(.*?)<\/th>/ims", $arr2[1], $arr)) {
                        // print_r($arr[1]);
                        $fields = array_map('strip_tags', $arr[1]);
                        $fields = array_map('trim', $fields);
                        $fields[0] = 'download';
                        $fields[1] = 'sciname';
                        // print_r($fields);
                    }
                }
                
                if(preg_match_all("/<tr (.*?)<\/tr>/ims", $str, $arr)) {
                    // print_r($arr);
                    $final = array();
                    foreach($arr[1] as $r) {
                        if(preg_match_all("/<td(.*?)<\/td>/ims", $r, $arr)) {
                            $values = array_map('trim', $arr[1]);
                            // print_r($values); exit;

                            $rek = array();
                            $i = -1;
                            foreach($fields as $f) { $i++;
                                $rek[$f] = $values[$i];
                            }
                            // print_r($rek); exit;
                            $rek['taxonID'] = $orig_rec['taxonID'];
                            $rek['furtherInformationURL'] = $this->domain.$orig_rec['url'];
                            
                            $final[] = $rek;
                        }
                    }
                }
            }
        }
        // print_r($final); exit;
        return array('orig_rec' => $orig_rec, 'media' => $final);
    }
    private function write_media($records)
    {
        foreach($records as $rec) {
            // print_r($rec); exit;
            /*Array(
                [0] => download
                [1] => sciname
                [2] => Length
                [3] => Recordist
                [4] => Date
                [5] => Time
                [6] => Country
                [7] => Location
                [8] => Elev. (m)
                [9] => Type
                [10] => Remarks
                [11] => Actions
                [12] => Cat.nr.
            )*/
            $ret1 = self::parse_location_lat_long($rec['Location']);
            $agent_id = '';
            if($ret2 = self::parse_recordist($rec['Recordist'])) $agent_id = self::write_agent($ret2);
            
            
            if($UsageTerms = self::parse_usageTerms($rec['Cat.nr.'])) {}
            else continue;
            
            
            if($ret = self::parse_accessURI($rec['download'])) {
                if($val = $ret['accessURI']) $accessURI = $val;
                else continue;
                $furtherInformationURL = $ret['furtherInfoURL'];
            }
            else continue;
            
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $rec['taxonID'];
            $mr->identifier     = md5($accessURI);
            $mr->format         = Functions::get_mimetype($accessURI);
            $mr->type           = Functions::get_datatype_given_mimetype($mr->format);
            if(!$mr->type) {
                echo "\nMessage: DataType must be present\n";
                print_r($rec);
                continue;
            }
            
            $mr->furtherInformationURL = $furtherInformationURL;
            $mr->accessURI      = $accessURI;
            $mr->Owner          = $ret2['agent'];
            $mr->UsageTerms     = $UsageTerms;
            $mr->LocationCreated = @$ret1['location'];
            $mr->lat             = @$ret1['lat'];
            $mr->long            = @$ret1['long'];
            $mr->description    = self::parse_description($rec['Remarks']);
            $mr->CreateDate     = self::parse_CreateDate($rec);
            $mr->agentID        = $agent_id;
            $mr->bibliographicCitation = self::parse_citation($rec, $mr->Owner, $mr->accessURI, $mr->furtherInformationURL);

            /*
            // $mr->thumbnailURL   = ''
            // $mr->CVterm         = ''
            // $mr->rights         = ''
            // $mr->title          = ''
            // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
            */
            
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';
            }
        }
    }
    private function parse_citation($rec, $owner, $accessURI, $furtherInformationURL)
    {
        // print_r($rec); //exit;
        // citation e.g.: Ralf Wendt, XC356323. Accessible at www.xeno-canto.org/356323.
        $filename = pathinfo($accessURI, PATHINFO_FILENAME);
        //e.g. XC207312-Apteryx%20australis141122_T1460
        $arr = explode('-', $filename);
        return "$owner, $arr[0]. Accessible at " . str_replace('https://', '', $furtherInformationURL).".";
    }
    private function parse_CreateDate($rec)
    {
        // [Date] => >2010-02-09
        // [Time] => > 07:00
        $str = $rec['Date'].' '.$rec['Time'];
        $str = str_replace('>', '', $str);
        $str = Functions::remove_whitespace($str);
        return $str;
    }
    private function parse_description($str)
    {
        $str = Functions::remove_whitespace(strip_tags($str));
        $str = str_replace('[sono]', '', $str);
        $str = str_replace('[also]', '', $str);
        $str = trim(substr($str,1,strlen($str)));
        // echo "\n$str\n";
        return $str;
    }
    private function parse_usageTerms($str)
    {
        //[Cat.nr.] => style='white-space: nowrap;'><a href="/46725">XC46725 <span title="Creative Commons Attribution-NonCommercial-NoDerivs 2.5">
        // <a href="//creativecommons.org/licenses/by-nc-nd/2.5/"><img class='icon' width='14' height='14' src='/static/img/cc.png'></a></span>
        if(preg_match("/href=\"\/\/creativecommons.org(.*?)\"/ims", $str, $arr)) {
            if(stripos($arr[1], "by-nc-nd") !== false) return false; //invalid license
            return 'http://creativecommons.org'.$arr[1];
        }
    }
    private function parse_accessURI($str)
    {
        $ret = array();
        // data-xc-filepath='//www.xeno-canto.org/sounds/uploaded/DNKBTPCMSQ/Ostrich%20RV%202-10.mp3'>
        if(preg_match("/filepath='(.*?)'/ims", $str, $arr)) $ret['accessURI'] = 'https:'.$arr[1];
        // data-xc-id='46725'
        if(preg_match("/data-xc-id='(.*?)'/ims", $str, $arr)) $ret['furtherInfoURL'] = $this->domain.'/'.$arr[1];
        return $ret;
    }
    private function parse_recordist($str)
    {
        //<a href='/contributor/DNKBTPCMSQ'>Derek Solomon</a>
        $val = array();
        if(preg_match("/href='(.*?)\'/ims", $str, $arr)) $val['homepage'] = $arr[1];
        if(preg_match("/\'>(.*?)<\/a>/ims", $str, $arr)) $val['agent'] = $arr[1];
        // print_r($val); //exit;
        return $val;
    }
    private function parse_location_lat_long($str)
    {
        //<a href="/location/map?lat=-24.3834&long=30.9334&loc=Hoedspruit">Hoedspruit</a>
        $val = array();
        if(preg_match("/lat=(.*?)&/ims", $str, $arr)) $val['lat'] = $arr[1];
        if(preg_match("/long=(.*?)&/ims", $str, $arr)) $val['long'] = $arr[1];
        if(preg_match("/\">(.*?)<\/a>/ims", $str, $arr)) $val['location'] = $arr[1];
        // print_r($val); //exit;
        return $val;
    }
    private function write_taxon($rec)
    {
        // print_r($rec); exit;
        /*Array(
            [comname] => Common Ostrich
            [url] => /species/Struthio-camelus
            [sciname] => Struthio camelus
            [order] => Struthioniformes
            [family] => Struthionidae
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['taxonID'];
        $taxon->scientificName  = $rec['sciname'];
        $taxon->taxonRank       = 'species';
        $taxon->order           = $rec['order'];
        $taxon->family          = $rec['family'];
        // $taxon->source          = $this->domain.$rec['url'];
        $taxon->furtherInformationURL = $this->domain.$rec['url'];
        
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec['taxonID'];
        $v->vernacularName  = $rec['comname'];
        $v->language        = 'en';
        $unique = md5($v->taxonID.$v->vernacularName);
        if(!isset($this->common_names[$unique])) {
            $this->archive_builder->write_object_to_file($v);
            $this->common_names[$unique] = '';
        }
    }
    private function write_agent($a)
    {
        // print_r($a); exit;
        $r = new \eol_schema\Agent();
        $r->term_name       = $a['agent'];
        $r->agentRole       = 'recorder';
        $r->term_homepage   = $this->domain.$a['homepage'];
        $r->identifier      = md5("$r->term_name|$r->agentRole|$r->term_homepage");
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $r->identifier;
    }
}
?>