<?php
namespace php_active_record;
/* connector: [jamstec.php] https://eol-jira.bibalex.org/browse/DATA-1828
*/
class JamstecAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //orig expires quarterly
        // $this->download_options['expire_seconds'] = false; //debug only

        $this->image_path = "https://editors.eol.org/other_files/JAMSTEC/";
        $this->meta['Species']      = $this->image_path . "Species/EOLmetadata_Species_20190624.xlsx";
        $this->meta['Genus']        = $this->image_path . "Genus/EOLmetadata_Genus_20190624.xlsx";
        $this->meta['Family']       = $this->image_path . "Family/EOLmetadata_Family_20190624.xlsx";
        $this->meta['Subfamily']    = $this->image_path . "Subfamily/EOLmetadata_Subfamily_20190624.xlsx";
        $this->licenses['CC-BY-NC'] = 'http://creativecommons.org/licenses/by-nc/4.0/';
        
        if(Functions::is_production()) {
            $this->zip['source file'] = '/extra/other_files/JAMSTEC/Downloads/Family.zip';
            $this->zip['extract to']  = '/extra/other_files/JAMSTEC/';
        }
        else {
            $this->zip['source file'] = '/Volumes/AKiTiO4/other_files/JAMSTEC/Downloads/Family.zip';
            $this->zip['extract to']  = '/Volumes/AKiTiO4/other_files/JAMSTEC/';
        }
        /*
        unzip -o /path/to/Family.zip -d /path/to/extract/to/
        */
    }
    private function extract_files()
    {
        $cmd = "unzip -o ".$this->zip['source file']." -d ".$this->zip['extract to'];
        echo "\n$cmd\n";
        $output = shell_exec($cmd);
        if(is_dir($this->zip['extract to'].'Species')) debug("\nExtracted OK\n");
    }
    function start()
    {   self::extract_files();
        self::build_Name_taxonID_list();
        self::main();
        self::create_rest_of_hierarchy();
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    private function build_Name_taxonID_list()
    {   $groups = array('Species', 'Genus', 'Family', 'Subfamily');
        foreach($groups as $group) {
            $recs = self::convert_sheet2array($group, 'build_Name_taxonID_list');
            foreach($recs as $rec) {
                if($val = @$rec['scientificName']) $this->Name_taxonID[$val] = $rec['BISMaLTaxonID'];
            }
        }
    }
    private function main()
    {
        $groups = array('Species', 'Genus', 'Family', 'Subfamily');
        foreach($groups as $group) {
            $recs = self::convert_sheet2array($group, 'main'); // print_r($recs);
            self::write_dwca($recs, $group);
        }
    }
    private function write_dwca($recs, $group)
    {
        foreach($recs as $rec) {
            $rec = array_map('trim', $rec);
            self::create_taxon($rec, $group);
            self::create_media($rec, $group);
        }
    }
    private function create_taxon($rec, $group)
    {   /*Array(
            [BISMaLTaxonID] => 9000379
            [scientificName] => Bothrocara hollandi
            [license] => CC-BY-NC
            [imageID] => 2K1280IN0048
            [url] => http://www.godac.jamstec.go.jp/jedi/player/e/2K1280IN0048
            [owner] => JAMSTEC
            [the pilot・operator・image editor] => 
            [Taxonomy] => Eukarya - Opisthokonta - Animalia - Chordata - Vertebrata - Gnathostomata - Pisciformes - Actinopterygii - Perciformes - Zoarcoidei - Zoarcidae - Bothrocara
            [a short text title] => 
            [a longer text caption] => 
            [date] => 37075
            [verbal location name] => Toyama Bay
            [latitude] => 
            [longitude] => 
            [water depth] => 
            [partner site] => BISMaL http://www.godac.jamstec.go.jp/bismal/e/view/9000379
            [visit source] => J-EDI http://www.godac.jamstec.go.jp/jedi/player/e/2K1280IN0048
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['BISMaLTaxonID'];
        if($val = @$rec['Scientific Name'])    $taxon->scientificName = $val;
        elseif($val = @$rec['scientificName']) $taxon->scientificName = $val;
        else exit("\nNo sciname\n");
        $ret = self::get_kingdom_phylum($rec['Taxonomy']);
        $taxon->kingdom                 = $ret['kingdom'];
        $taxon->phylum                  = $ret['phylum'];
        $taxon->parentNameUsageID       = $ret['parent_id'];
        $taxon->higherClassification    = trim(str_replace(" - ", "|", $rec['Taxonomy']));
        $taxon->taxonRank               = strtolower($group);
        $taxon->furtherInformationURL   = self::format_url($rec['partner site']);
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function get_kingdom_phylum($str)
    {   //Eukarya - Opisthokonta - Animalia - Chordata - Vertebrata - Gnathostomata - Pisciformes - Actinopterygii - Lophiiformes - Chaunacidae - Chaunax
        $arr = explode(" - ", $str); //print_r($arr);
        $index_of_Animalia = array_search("Animalia", $arr);
        $parent = array_pop($arr);
        if($parent_id = @$this->Name_taxonID[$parent]) {}
        else $parent_id = strtolower($parent);
        return array('kingdom' => 'Animalia', 'phylum' => $arr[$index_of_Animalia+1], 'parent_id' => $parent_id);
    }
    private function create_rest_of_hierarchy()
    {   $groups = array('Species', 'Genus', 'Family', 'Subfamily');
        foreach($groups as $group) {
            $recs = self::convert_sheet2array($group, 'create_rest_of_hierarchy');
            foreach($recs as $rec) self::write_hierarchy($rec['Taxonomy']);
        }
    }
    private function write_hierarchy($taxonomy)
    {   //[Taxonomy] => Eukarya - Opisthokonta - Animalia - Chordata - Vertebrata - Gnathostomata - Pisciformes - Actinopterygii - Perciformes - Zoarcoidei - Zoarcidae - Bothrocara
        $names = explode(" - ", $taxonomy); //print_r($arr);
        $names = array_map('trim', $names);
        $i = -1;
        foreach($names as $name) { $i++;
            if(@$this->Name_taxonID[$name]) {}
            else {
                $taxon_id = strtolower($name);
                if($i > 0)  $parent_id = strtolower($names[$i-1]);
                else        $parent_id = '';
                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID                 = $taxon_id;
                $taxon->scientificName          = $name;
                $taxon->parentNameUsageID       = $parent_id;
                $this->archive_builder->write_object_to_file($taxon);
                $this->Name_taxonID[$name] = $taxon_id;
            }
        }
    }
    private function create_media($rec, $group)
    {   /*JAMSTEC,DwC-A,notes
        BISMaLTaxonID,http://rs.tdwg.org/dwc/terms/taxonID,
        scientificName,http://rs.tdwg.org/dwc/terms/scientificName,
        license,http://ns.adobe.com/xap/1.0/rights/UsageTerms,
        imageID,http://purl.org/dc/terms/identifier,
        url,http://rs.tdwg.org/ac/terms/furtherInformationURL,
        owner,http://ns.adobe.com/xap/1.0/rights/Owner,
        the pilot・operator・image editor,http://purl.org/dc/terms/contributor,
        Taxonomy,,"I think a parent-child hierarchy would be nice, if you can construct one fairly safely. If that's painful, it should suffice to grab Animalia and the one element that follows it and use just Kingdom and Phylum columns"
        a short text title,http://purl.org/dc/terms/title,
        a longer text caption,http://purl.org/dc/terms/description,
         date,http://ns.adobe.com/xap/1.0/CreateDate,
        verbal location name,http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated,
        latitude,http://www.w3.org/2003/01/geo/wgs84_pos#lat,
        longitude,http://www.w3.org/2003/01/geo/wgs84_pos#long,
        water depth,http://www.w3.org/2003/01/geo/wgs84_pos#alt,"please insert a ""-"" in front of the string"
        partner site,http://purl.org/dc/terms/bibliographicCitation,
        ,,
        CC-BY-NC,http://creativecommons.org/licenses/by-nc/4.0/,
        */
        /*Array(
            [BISMaLTaxonID] => 9000379
            [scientificName] => Bothrocara hollandi
            [license] => CC-BY-NC
            [imageID] => 2K1280IN0048
            [url] => http://www.godac.jamstec.go.jp/jedi/player/e/2K1280IN0048
            [owner] => JAMSTEC
            [the pilot・operator・image editor] => 
            [Taxonomy] => Eukarya - Opisthokonta - Animalia - Chordata - Vertebrata - Gnathostomata - Pisciformes - Actinopterygii - Perciformes - Zoarcoidei - Zoarcidae - Bothrocara
            [a short text title] => 
            [a longer text caption] => 
            [date] => 37075
            [verbal location name] => Toyama Bay
            [latitude] => 
            [longitude] => 
            [water depth] => 
            [partner site] => BISMaL http://www.godac.jamstec.go.jp/bismal/e/view/9000379
            [visit source] => J-EDI http://www.godac.jamstec.go.jp/jedi/player/e/2K1280IN0048
        )*/
        
        /*JAMSTEC,DwC-A,notes
            water depth,http://www.w3.org/2003/01/geo/wgs84_pos#alt,"please insert a ""-"" in front of the string"
        */
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $rec['BISMaLTaxonID'];
        $mr->identifier     = $rec['imageID'];
        $mr->format         = 'image/jpeg'; //mimetype
        $mr->type           = Functions::get_datatype_given_mimetype($mr->format);
        $mr->language       = 'en';
        $mr->furtherInformationURL = $rec['url'];
        $mr->accessURI      = $this->image_path.$group."/".$rec['imageID'].".jpg";
        $mr->title          = $rec['a short text title'];
        $mr->description    = $rec['a longer text caption'];
        $mr->CreateDate         = $rec['date'];
        $mr->LocationCreated    = $rec['verbal location name'];
        $mr->lat                = $rec['latitude'];
        $mr->long               = $rec['longitude'];
        $mr->alt                = $rec['water depth'];
        $mr->Owner              = $rec['owner'];
        $mr->contributor        = $rec['the pilot・operator・image editor'];
        $mr->UsageTerms         = $this->licenses[$rec['license']];
        $mr->bibliographicCitation = $rec['partner site'];
        $mr->publisher = "Global Oceanographic Data Center, http://www.godac.jp/en";
        // $mr->rights         = '';
        // $mr->audience       = 'Everyone';
        // $mr->thumbnailURL   = '';
        // $mr->CVterm         = ''; subject
        // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
        // if($agent_ids     =     @$this->object_agent_ids[$o['int_do_id']])  $mr->agentID = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
        else echo "\nImage already exists\n";
    }
    private function convert_sheet2array($group, $what)
    {   echo "\nConverting sheet [$what][$group]";
        $final = array();
        $options = $this->download_options;
        $options['file_extension'] = 'xlsx';
        if($local_xls = Functions::save_remote_file_to_local($this->meta[$group], $options)) {
            require_library('XLSParser');
            $parser = new XLSParser();
            debug("\n reading: " . $local_xls . "\n");
            $temp = $parser->convert_sheet_to_array($local_xls); // print_r($temp);
            $fields = array_keys($temp); // print_r($fields);
            $i = -1;
            foreach($temp['BISMaLTaxonID'] as $taxon_id) {
                $i++;
                $rec = array();
                foreach($fields as $field) $rec[$field] = $temp[$field][$i];
                // print_r($rec);
                $final[] = $rec;
            }
        }
        unlink($local_xls);
        return $final;
    }
    private function format_url($str)
    {   //[partner site] => BISMaL http://www.godac.jamstec.go.jp/bismal/e/view/9000418
        $arr = explode(" ", $str);
        $arr = array_map('trim', $arr);
        if($val = @$arr[1]) return $val;
        return $arr[0];
    }
    /*
    function load_zip_contents($zip_path, $download_options, $files, $extension)
    {   $text_path = array();
        $temp_path = create_temp_dir();
        if($file_contents = Functions::lookup_with_cache($zip_path, $download_options)) // resource is set to harvest quarterly and the cache expires by default in a month
        {
            $parts = pathinfo($zip_path);
            $temp_file_path = $temp_path . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip -o $temp_file_path -d $temp_path");
            if(file_exists($temp_path . "/" . $files[0] . $extension)) {
                foreach($files as $file) {
                    $text_path[$file] = $temp_path . "/" . $file . $extension;
                }
            }
        }
        else echo "\n\n Connector terminated. Remote files are not ready.\n\n";
        return $text_path;
    }
    */
}
?>
