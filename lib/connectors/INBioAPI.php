<?php
namespace php_active_record;
/* connector: 276
We received a Darwincore archive file from the partner. It has a pliniancore extension.
Partner hasn't yet hosted the DWC-A file.
Connector downloads the archive file, extracts, reads the archive file, assembles the data and generates the EOL XML.
*/
class INBioAPI
{
    private static $MAPPINGS;
    const TAXON_SOURCE_URL = "http://darnis.inbio.ac.cr/ubis/FMPro?-DB=UBIPUB.fp3&-lay=WebAll&-error=norec.html&-Format=detail.html&-Op=eq&-Find=&id=";
    const SPM = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#";
    const EOL = "http://www.eol.org/voc/table_of_contents#";

    function get_all_taxa($dwca_file)
    {
        self::$MAPPINGS = self::assign_mappings();
        $all_taxa = array();
        $used_collection_ids = array();
        $paths = self::extract_archive_file($dwca_file, "meta.xml");
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($GLOBALS['fields'] = $tables["http://www.pliniancore.org/plic/pcfcore/pliniancore2.3"][0]->fields)) {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        $images = self::get_images($harvester->process_row_type('http://rs.gbif.org/terms/1.0/image'));
        $references = self::get_references($harvester->process_row_type('http://rs.gbif.org/terms/1.0/reference'));
        $vernacular_names = self::get_vernacular_names($harvester->process_row_type('http://rs.gbif.org/terms/1.0/vernacularname'));
        $taxon_media = array();
        $media = $harvester->process_row_type('http://www.pliniancore.org/plic/pcfcore/PlinianCore2.3');
        foreach($media as $m) @$taxon_media[$m['http://rs.tdwg.org/dwc/terms/taxonID']] = $m;
        $taxa = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');
        $i = 0;
        $total = sizeof($taxa);
        foreach($taxa as $taxon) {
            $i++;
            debug("$i of $total");
            $taxon_id = @$taxon['http://rs.tdwg.org/dwc/terms/taxonID'];
            $taxon["id"] = $taxon_id;
            $taxon["image"] = @$images[$taxon_id];
            $taxon["reference"] = @$references[$taxon_id];
            $taxon["vernacular_name"] = @$vernacular_names[$taxon_id];
            $taxon["media"] = $taxon_media[$taxon_id];
            $arr = self::get_inbio_taxa($taxon, $used_collection_ids);
            $page_taxa               = $arr[0];
            $used_collection_ids     = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);
        }
        // remove tmp dir
        if($temp_dir) shell_exec("rm -fr $temp_dir");
        return $all_taxa;
    }
    private function get_contents($file, $download_options)
    {
        if(substr($file,0,4) == 'http')  {
            $contents = Functions::lookup_with_cache($file, $download_options);
            return array("contents" => $contents);
        }
        elseif(substr($file,0,1) == '/') {
            return array("path" => $file); //means you return the path and not the contents of the file //new
                                           //return file_get_contents($file);                           //orig
        }
        else exit("\nInvestigate get_contents() in INBioAPI.php\n");
    }
    function extract_zip_file($dwca_file, $download_options = array('timeout' => 172800, 'expire_seconds' => 0))
    { //1st client is lib/connectors/SDR_Consolid8API.php
        debug("Please wait, extract_zip_file [$dwca_file]...");
        $path_parts = pathinfo($dwca_file); //print_r($path_parts); //exit;
        /*Array( [dirname] => https://editors.eol.org/other_files/SDR
                 [basename] => parent_basal_values_resource.txt.zip
                 [extension] => zip
                 [filename] => parent_basal_values_resource.txt)*/
        if(strtolower($path_parts['extension']) != 'zip') exit("\nERROR: Not a zip file.\n");
        $filename = $path_parts['basename'];
        $temp_dir = create_temp_dir() . "/";
        debug($temp_dir);

        $ret = self::get_contents($dwca_file, $download_options);
        if(@$ret['contents'] || @$ret['path']) {}
        else {
            debug("Connector terminated. Remote/local file is not ready.");
            return;
        }
        if($temp_file_path = @$ret['path']) {}
        if($file_contents = @$ret['contents']) {
            $temp_file_path = $temp_dir . "" . $filename;
            debug("temp_dir: $temp_dir");
            debug("Extracting... $temp_file_path");
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            sleep(5);
        }
        shell_exec("unzip -ad $temp_dir $temp_file_path");
        $extracted_file = str_ireplace(".zip", "", $temp_file_path);
        return array('extracted_file' => $extracted_file, 'temp_dir' => $temp_dir, 'temp_file_path' => $temp_file_path);
        /*Array(
            [extracted_file] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_68666/parent_basal_values_resource.txt
            [temp_dir] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_68666/
            [temp_file_path] => /Volumes/AKiTiO4/eol_php_code_tmp/dir_68666/parent_basal_values_resource.txt.zip
        )*/
    }
    function extract_archive_file($dwca_file, $check_file_or_folder_name, $download_options = array('timeout' => 172800, 'expire_seconds' => 0), $force_extension = false) //e.g. with force_extension is NMNHTypeRecordAPI_v2.php
    {
        // /* New May 12, 2021 - another option to detect $check_file_or_folder_name
        $tmp = pathinfo($dwca_file, PATHINFO_BASENAME);
        $tmpfolder = str_replace('.tar.gz', '', $tmp); //exit("\n[$tmpfolder]\n");
        // */
        
        debug("Please wait, downloading resource document...");
        $temp_dir = create_temp_dir() . "/";
        debug($temp_dir);
        $path_parts = pathinfo($dwca_file);
        $filename = $path_parts['basename'];
        if($force_extension) $filename = "elix.".$force_extension; //you can just make-up a filename (elix) here and add the forced extension.

        $ret = self::get_contents($dwca_file, $download_options);
        if(@$ret['contents'] || @$ret['path']) {}
        else {
            debug("Connector terminated. Remote/local file is not ready.");
            recursive_rmdir($temp_dir); echo "\ntemp. dir removed: [$temp_dir]\n";
            return;
        }
        if($temp_file_path = @$ret['path']) {}
        if($file_contents = @$ret['contents']) {    
            $temp_file_path = $temp_dir . "" . $filename;
            debug("temp_dir: $temp_dir");
            debug("Extracting... $temp_file_path");
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) {
                recursive_rmdir($temp_dir); echo "\ntemp. dir removed: [$temp_dir]\n";
                return;
            }
            fwrite($TMP, $file_contents);
            fclose($TMP);
            sleep(1); //orig 5 secs.
        }

        if($force_extension == 'zip') {
            shell_exec("unzip -ad $temp_dir $temp_file_path");
            $archive_path = str_ireplace(".zip", "", $temp_file_path);
        }
        else {
            if(preg_match("/^(.*)\.(tar.gz|tgz)$/", $dwca_file, $arr)) {
                $cur_dir = getcwd();
                chdir($temp_dir);
                shell_exec("tar -zxvf $temp_file_path");
                chdir($cur_dir);
                $archive_path = str_ireplace(".tar.gz", "", $temp_file_path);
                $archive_path = str_ireplace(".tgz", "", $temp_file_path);
            }
            elseif(preg_match("/^(.*)\.(gz|gzip)$/", $dwca_file, $arr)) {
                shell_exec("gunzip -f $temp_file_path");
                $archive_path = str_ireplace(".gz", "", $temp_file_path);
            }
            elseif(preg_match("/^(.*)\.(zip)$/", $dwca_file, $arr) || preg_match("/mcz_for_eol(.*?)/ims", $dwca_file, $arr)) {
                shell_exec("unzip -ad $temp_dir $temp_file_path");
                $archive_path = str_ireplace(".zip", "", $temp_file_path);
            } 
            else {
                debug("-- archive not gzip or zip. [$dwca_file]");
                recursive_rmdir($temp_dir); echo "\ntemp. dir removed: [$temp_dir]\n";
                return;
            }
        }
        debug("archive path: [" . $archive_path . "]");


        //TODO: make it automatic to detect .... the likes of dwca/ and EOL_dynamic_hierarchy/
        if    (file_exists($temp_dir . $check_file_or_folder_name))           return array('archive_path' => $temp_dir,     'temp_dir' => $temp_dir);
        elseif(file_exists($archive_path . "/" . $check_file_or_folder_name)) return array('archive_path' => $archive_path, 'temp_dir' => $temp_dir);
        elseif(file_exists($temp_dir ."dwca/". $check_file_or_folder_name))   return array('archive_path' => $temp_dir."dwca/", 'temp_dir' => $temp_dir); //for http://britishbryozoans.myspecies.info/eol-dwca.zip where it extracts to /dwca/ folder instead of usual /eol-dwca/.
        elseif(file_exists($temp_dir ."EOL_dynamic_hierarchy/". $check_file_or_folder_name)) return array('archive_path' => $temp_dir."EOL_dynamic_hierarchy/", 'temp_dir' => $temp_dir); //for https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/b534cd22-d904-45e4-b0e2-aaf06cc0e2d6/download/eoldynamichierarchyv1revised.zip
        elseif(file_exists($temp_dir ."$tmpfolder/". $check_file_or_folder_name))   return array('archive_path' => $temp_dir."$tmpfolder/", 'temp_dir' => $temp_dir);
        elseif(file_exists($temp_dir ."itisMySQL022519/". $check_file_or_folder_name)) return array('archive_path' => $temp_dir."itisMySQL022519/", 'temp_dir' => $temp_dir); //from ITIS downloads - TRAM-804
        elseif(file_exists($temp_dir ."itisMySQL033119/". $check_file_or_folder_name)) return array('archive_path' => $temp_dir."itisMySQL033119/", 'temp_dir' => $temp_dir); //from ITIS downloads - TRAM-806
        elseif(file_exists($temp_dir ."itisMySQL082819/". $check_file_or_folder_name)) return array('archive_path' => $temp_dir."itisMySQL082819/", 'temp_dir' => $temp_dir); //from ITIS downloads
        elseif(file_exists($temp_dir ."itisMySQL072820/". $check_file_or_folder_name)) return array('archive_path' => $temp_dir."itisMySQL072820/", 'temp_dir' => $temp_dir); //from ITIS downloads - TRAM-987
        elseif(file_exists($temp_dir ."itisMySQL120120/". $check_file_or_folder_name)) return array('archive_path' => $temp_dir."itisMySQL120120/", 'temp_dir' => $temp_dir); //from ITIS downloads - TRAM-987
        elseif(file_exists($temp_dir ."itisMySQL022822/". $check_file_or_folder_name)) return array('archive_path' => $temp_dir."itisMySQL022822/", 'temp_dir' => $temp_dir); //from ITIS downloads - while doing TRAM-806
        else {
            echo "\n1. ".$temp_dir . $check_file_or_folder_name."\n";
            echo "\n2. ".$archive_path . "/" . $check_file_or_folder_name."\n";
            echo "\n3. ".$temp_dir ."dwca/". $check_file_or_folder_name."\n";
            echo "\n4. ".$temp_dir ."EOL_dynamic_hierarchy/". $check_file_or_folder_name."\n";
            echo "\n5. ".$temp_dir ."$tmpfolder/". $check_file_or_folder_name."\n";
            debug("Can't find check_file_or_folder_name [$check_file_or_folder_name].");
            recursive_rmdir($temp_dir); echo "\ntemp. dir removed: [$temp_dir]\n";
            return false;
            // return array('archive_path' => $temp_dir, 'temp_dir' => $temp_dir);
        }
    }
    public static function assign_eol_subjects($xml_string)
    {
        if(!stripos($xml_string, "http://www.eol.org/voc/table_of_contents#")) return $xml_string;
        debug("this resource has http://www.eol.org/voc/table_of_contents# ");
        $xml = simplexml_load_string($xml_string);
        foreach($xml->taxon as $taxon) {
            foreach($taxon->dataObject as $dataObject) {
                $eol_subjects[] = self::EOL . "SystematicsOrPhylogenetics";
                $eol_subjects[] = self::EOL . "TypeInformation";
                $eol_subjects[] = self::EOL . "Notes";
                if(@$dataObject->subject) {
                    if(in_array($dataObject->subject, $eol_subjects)) {
                        $dataObject->addChild("additionalInformation", "");
                        $dataObject->additionalInformation->addChild("subject", $dataObject->subject);
                        if    ($dataObject->subject == self::EOL . "SystematicsOrPhylogenetics") $dataObject->subject = self::SPM . "Evolution";
                        elseif($dataObject->subject == self::EOL . "TypeInformation")            $dataObject->subject = self::SPM . "DiagnosticDescription";
                        elseif($dataObject->subject == self::EOL . "Notes")                      $dataObject->subject = self::SPM . "Description";
                    }
                }
            }
        }
        return $xml->asXML();
    }
    private function get_images($imagex)
    {
        $images = array();
        foreach($imagex as $image) {
            if($image['http://purl.org/dc/terms/identifier']) {
                $taxon_id = $image['http://rs.tdwg.org/dwc/terms/taxonID'];
                $images[$taxon_id]['url'][]           = $image['http://purl.org/dc/terms/identifier'];
                $images[$taxon_id]['caption'][]       = $image['http://purl.org/dc/terms/description'];
                $images[$taxon_id]['license'][]       = @$image['http://purl.org/dc/terms/license'];
                $images[$taxon_id]['publisher'][]     = @$image['http://purl.org/dc/terms/publisher'];
                $images[$taxon_id]['creator'][]       = @$image['http://purl.org/dc/terms/creator'];
                $images[$taxon_id]['created'][]       = @$image['http://purl.org/dc/terms/created'];
                $images[$taxon_id]['rightsHolder'][]  = @$image['http://purl.org/dc/terms/rightsHolder'];
            }
        }
        return $images;
    }
    private function get_references($refs)
    {
        $references = array();
        foreach($refs as $ref) {
            $taxon_id = $ref['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($ref['http://purl.org/dc/terms/bibliographicCitation']) $references[$taxon_id] = self::parse_references($ref['http://purl.org/dc/terms/bibliographicCitation']);
        }
        return $references;
    }
    private function get_vernacular_names($names)
    {
        $vernacular_names = array();
        foreach($names as $name) {
            $taxon_id = $name['http://rs.tdwg.org/dwc/terms/taxonID'];
            if($name['http://rs.tdwg.org/dwc/terms/vernacularName']) {
                $vernacular_names[$taxon_id][] = array("name" => $name['http://rs.tdwg.org/dwc/terms/vernacularName'], "language" => self::get_language(@$name['http://purl.org/dc/terms/language']));
            }
        }
        return $vernacular_names;
    }
    public static function get_inbio_taxa($taxon, $used_collection_ids)
    {
        $response = self::parse_xml($taxon);
        $page_taxa = array();
        foreach($response as $rec) {
            if(@$used_collection_ids[$rec["identifier"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["identifier"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }
    private function parse_xml($taxon)
    {
        $taxon_id = $taxon["id"];
        $arr_data = array();
        $arr_objects = array();
        if($taxon["media"]) {
            foreach($GLOBALS['fields'] as $field) {
                $term = $field["term"];
                $mappings = self::$MAPPINGS;
                if(@$mappings[$term] && @$taxon["media"][$term]) $arr_objects[] = self::prepare_text_objects($taxon, $term);
            }
            $arr_objects = self::prepare_image_objects($taxon, $arr_objects);
            $refs = array();
            if($taxon["reference"]) $refs = $taxon["reference"];
            if(sizeof($arr_objects)) {
                $sciname = @$taxon["http://rs.tdwg.org/dwc/terms/scientificName"];
                if(@$taxon["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"]) $sciname .= " " . $taxon["http://rs.tdwg.org/dwc/terms/scientificNameAuthorship"];
                $arr_data[]=array(  "identifier"   => $taxon_id,
                                    "source"       => self::TAXON_SOURCE_URL . $taxon_id,
                                    "kingdom"      => @$taxon["http://rs.tdwg.org/dwc/terms/kingdom"],
                                    "phylum"       => @$taxon["http://rs.tdwg.org/dwc/terms/phylum"],
                                    "class"        => @$taxon["http://rs.tdwg.org/dwc/terms/class"],
                                    "order"        => @$taxon["http://rs.tdwg.org/dwc/terms/order"],
                                    "family"       => @$taxon["http://rs.tdwg.org/dwc/terms/family"],
                                    "genus"        => @$taxon["http://rs.tdwg.org/dwc/terms/genus"],
                                    "sciname"      => $sciname,
                                    "reference"    => $refs,
                                    "synonyms"     => array(),
                                    "commonNames"  => $taxon["vernacular_name"],
                                    "data_objects" => $arr_objects
                                 );
            }
        }
        return $arr_data;
    }
    private function parse_references($refs)
    {
        if    (is_numeric(stripos($refs, "<p>")))  $refs = explode("<p>", $refs);
        elseif(is_numeric(stripos($refs, "</p>"))) $refs = explode("</p>", $refs);
        else $refs = explode("<p>", $refs);
        $references = array();
        foreach($refs as $ref) $references[] = array("fullReference" => $ref);
        return $references;
    }
    private function prepare_image_objects($taxon, $arr_objects)
    {
        $image_urls = @$taxon["image"]['url'];
        $i = 0;
        if($image_urls) {
          foreach($image_urls as $image_url) {
            if($image_url) {
                $identifier     = @$taxon["image"]['url'][$i];
                $description    = @$taxon["image"]['caption'][$i];
                $mimeType       = "image/jpeg";
                $dataType       = "http://purl.org/dc/dcmitype/StillImage";
                $title          = "";
                $subject        = "";
                $mediaURL       = @$taxon["image"]['url'][$i]; 
                $location       = "";
                $license_index  = @$taxon["image"]['license'][$i];
                $license_info["CC-Attribution-NonCommercial-ShareAlike"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                $license        = @$license_info[$license_index];
                $rightsHolder   = @$taxon["image"]['rightsHolder'][$i];
                $created        = @$taxon["image"]['created'][$i];
                $source         = self::TAXON_SOURCE_URL . $taxon["id"];
                $agent          = array();
                if(@$taxon["image"]['creator'][$i]) $agent[] = array("role" => "photographer", "homepage" => "", "fullName" => @$taxon["image"]['creator'][$i]);
                if(@$taxon["image"]['publisher'][$i]) $agent[] = array("role" => "publisher", "homepage" => "", "fullName" => @$taxon["image"]['publisher'][$i]);
                $refs           = array();
                $modified       = "";
                $created        = "";
                $language       = "";
                $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language);
            }
            $i++;
          }
        }
        return $arr_objects;
    }
    private function get_language($lang)
    {
        if($lang == "Ingles") return "en";
        elseif($lang == "Español") return "es";
        else return "es";
    }
    private function prepare_text_objects($taxon, $term)
    {
        $temp = parse_url($term);
        $description   = $taxon["media"][$term];
        $identifier    = $taxon["id"] . str_replace("/", "_", $temp["path"]);
        $mimeType      = "text/html";
        $dataType      = "http://purl.org/dc/dcmitype/Text";
        $title         = "";
        $subject       = self::$MAPPINGS[$term];
        $mediaURL      = "";
        $location      = "";
        $license_index = @$taxon["http://purl.org/dc/terms/license"];
        $license_info["CC-Attribution-NonCommercial-ShareAlike"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $license       = @$license_info[$license_index];
        $rightsHolder  = @$taxon["http://purl.org/dc/terms/rightsHolder"];
        $source        = self::TAXON_SOURCE_URL . $taxon["id"];
        $refs          = array();
        $agent         = self::get_agents($taxon);
        $created       = $taxon["media"]["http://purl.org/dc/terms/created"];
        $modified      = "";
        $language      = self::get_language($taxon["http://purl.org/dc/terms/language"]);
        return self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language);
    }
    private function get_agents($taxon)
    {
        $agent = array();
        if($taxon["media"]["http://purl.org/dc/terms/creator"]) {
            $creators = explode(",", $taxon["media"]["http://purl.org/dc/terms/creator"]);
            foreach($creators as $creator) $agent[] = array("role" => "author", "homepage" => "", "fullName" => trim(strip_tags($creator)));
        }
        if($taxon["media"]["http://purl.org/dc/elements/1.1/contributor"]) {
            $contributors = explode(",", $taxon["media"]["http://purl.org/dc/elements/1.1/contributor"]);
            foreach($contributors as $contributor) {
                $contributor = trim(strip_tags(str_replace("\\", "", $contributor)));
                if($contributor) $agent[] = array("role" => "editor", "homepage" => "", "fullName" => $contributor);
            }
        }
        return $agent;
    }
    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language)
    {
        return array( "identifier"   => $identifier,
                      "dataType"     => $dataType,
                      "mimeType"     => $mimeType,
                      "title"        => $title,
                      "source"       => $source,
                      "description"  => $description,
                      "mediaURL"     => $mediaURL,
                      "agent"        => $agent,
                      "license"      => $license,
                      "location"     => $location,
                      "rightsHolder" => $rightsHolder,
                      "reference"    => $refs,
                      "subject"      => $subject,
                      "modified"     => $modified,
                      "created"      => $created,
                      "language"     => $language
                    );
    }
    private function assign_mappings()
    {
        return array(  "http://www.pliniancore.org/plic/pcfcore/scientificDescription"        => self::SPM . "DiagnosticDescription",
                       "http://www.pliniancore.org/plic/pcfcore/distribution"                 => self::SPM . "Distribution",
                       "http://www.pliniancore.org/plic/pcfcore/feeding"                      => self::SPM . "TrophicStrategy",
                       "http://www.pliniancore.org/plic/pcfcore/identificationKeys"           => self::SPM . "Key",
                       "http://www.pliniancore.org/plic/pcfcore/invasivenessData"             => self::SPM . "RiskStatement",
                       "http://www.pliniancore.org/plic/pcfcore/theUses"                      => self::SPM . "Uses",
                       "http://www.pliniancore.org/plic/pcfcore/migratoryData"                => self::SPM . "Migration",
                       "http://www.pliniancore.org/plic/pcfcore/ecologicalSignificance"       => self::SPM . "Ecology",
                       "http://www.pliniancore.org/plic/pcfcore/annualCycle"                  => self::SPM . "Cyclicity",
                       "http://www.pliniancore.org/plic/pcfcore/folklore"                     => self::SPM . "TaxonBiology",
                       "http://www.pliniancore.org/plic/pcfcore/populationBiology"            => self::SPM . "PopulationBiology",
                       "http://www.pliniancore.org/plic/pcfcore/threatStatus"                 => self::SPM . "ConservationStatus",
                       "http://www.pliniancore.org/plic/pcfcore/abstract"                     => self::SPM . "Description",
                       "http://www.pliniancore.org/plic/pcfcore/interactions"                 => self::SPM . "Associations",
                       "http://www.pliniancore.org/plic/pcfcore/territory"                    => self::SPM . "Behaviour",
                       "http://www.pliniancore.org/plic/pcfcore/behavior"                     => self::SPM . "Behaviour",
                       "http://www.pliniancore.org/plic/pcfcore/chromosomicNumberN"           => self::SPM . "Cytology",
                       "http://www.pliniancore.org/plic/pcfcore/reproduction"                 => self::SPM . "Reproduction",
                       "http://www.pliniancore.org/plic/pcfcore/theManagement"                => self::SPM . "Management",
                       "http://www.pliniancore.org/plic/pcfcore/endemicity"                   => self::SPM . "Distribution",
                       "http://www.pliniancore.org/plic/pcfcore/briefDescription"             => self::SPM . "TaxonBiology",
                       "http://www.pliniancore.org/plic/pcfcore/habit"                        => self::SPM . "Morphology",
                       "http://www.pliniancore.org/plic/pcfcore/legislation"                  => self::SPM . "Legislation",
                       "http://www.pliniancore.org/plic/pcfcore/habitat"                      => self::SPM . "Habitat",
                       "http://www.pliniancore.org/plic/pcfcore/lifeCycle"                    => self::SPM . "LifeCycle",
                       "http://iucn.org/terms/threatStatus"                                   => self::SPM . "ConservationStatus",
                       "http://rs.tdwg.org/dwc/terms/habitat"                                 => self::SPM . "Habitat",
                       "http://rs.tdwg.org/dwc/terms/establishmentMeans"                      => self::SPM . "Distribution",
                       "http://purl.org/dc/terms/abstract"                                    => self::SPM . "TaxonBiology",
                       "http://www.pliniancore.org/plic/pcfcore/molecularData"                => self::EOL . "SystematicsOrPhylogenetics", 
                       "http://www.pliniancore.org/plic/pcfcore/typification"                 => self::EOL . "TypeInformation", 
                       "http://www.pliniancore.org/plic/pcfcore/unstructuredNaturalHistory"   => self::EOL . "Notes",
                       "http://www.pliniancore.org/plic/pcfcore/unstructedDocumentation"      => self::EOL . "Notes",
                       "http://www.pliniancore.org/plic/pcfcore/unstructuredDocumentation"    => self::EOL . "Notes"
                   );
    }
    function download_extract_zip_file($remote_zip_file, $destination_folder)
    {   /*
        remote zip file:        http://eol.org/folder/filename.zip
        destination folder:     /extra/dumps/folder/
        */
        $output = shell_exec("unzip -oj $remote_zip_file -d $destination_folder"); //-o overwrites; -j removes upper directory; -d destination
        return $output;
    }
    function download_general_dump($source_remote_url, $destination_file, $download_path, $check_file_or_folder_name) //now a public function
    {   
        // /* un-comment in real operation
        //1. download remote file
        self::save_dump_files($source_remote_url, $destination_file);
        //2. extract downloaded local file
        $paths = self::extract_local_file($destination_file, $download_path, $check_file_or_folder_name); //'creatoridentifier.txt'
        print_r($paths); //exit;
        // */

        /* dev only - datahub_bhl.php
        $paths = Array(
            "archive_path" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_97485/Data/", 
            "temp_dir" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_97485/"
        );
        */

        /* dev only - datahub_ncbi.php
        $paths = Array(
            "archive_path" => "/Volumes/Crucial_2TB/other_files2/dumps_GGI/NCBI/downloaded_1", //this is a file not a folder.
            "temp_dir" => "/Volumes/AKiTiO4/eol_php_code_tmp/dir_42492/"
        );
        */
        return $paths;
    }
    function save_dump_files($url, $target)
    {   //wget -nc http://www.boldsystems.org/pics/KANB/USNM_442211_photograph_KB17_037_155mmSL_LRP_17_07+1507842962.JPG -O /Volumes/AKiTiO4/other_files/xxx/file.ext
        $cmd = WGET_PATH . " '$url' -nv -O "."'$target'"; //wget -nc --no-clobber --> means 'no overwrite' | -nv   --no-verbose | -q --quiet (no output) | -v --verbose (default)
        $cmd .= " 2>&1";
        echo "\nurl: [$url]";   echo "\ntarget: [$target]"; echo "\ncmd: [$cmd]\n";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) echo("\nURL path does not exist.\n$url\n\n"); //string is found
        echo "\n---\n".trim($shell_debug)."\n---\n";
    }
    function extract_local_file($dwca_file, $temp_dir, $check_file_or_folder_name)
    {   //1st client: extracting a local downloaded file from: https://www.biodiversitylibrary.org/data/hosted/data.zip (DataHub_BHL_API.php)
        debug("Please wait, extract_local_file ...");

        // /* New May 12, 2021 - another option to detect $check_file_or_folder_name
        $tmp = pathinfo($dwca_file, PATHINFO_BASENAME);
        $tmpfolder = str_replace('.tar.gz', '', $tmp); //exit("\n[$tmpfolder]\n");
        // */

        $temp_dir = create_temp_dir() . "/";
        debug($temp_dir);
        if(true) {
            $temp_file_path = $dwca_file;
            debug("temp_dir: $temp_dir");
            debug("Extracting... $temp_file_path");
            if(preg_match("/^(.*)\.(tar.gz|tgz)$/", $dwca_file, $arr)) {
                $cur_dir = getcwd();
                chdir($temp_dir);
                shell_exec("tar -zxvf $temp_file_path");
                chdir($cur_dir);
                $archive_path = str_ireplace(".tar.gz", "", $temp_file_path);
                $archive_path = str_ireplace(".tgz", "", $temp_file_path);
            }
            elseif(preg_match("/^(.*)\.(gz|gzip)$/", $dwca_file, $arr)) {
                shell_exec("gunzip -f $temp_file_path");
                $archive_path = str_ireplace(".gz", "", $temp_file_path);
            }
            elseif(preg_match("/^(.*)\.(zip)$/", $dwca_file, $arr) || preg_match("/mcz_for_eol(.*?)/ims", $dwca_file, $arr)) {
                shell_exec("unzip -ad $temp_dir $temp_file_path");
                $archive_path = str_ireplace(".zip", "", $temp_file_path);
            } 
            else {
                debug("-- archive not gzip or zip. [$dwca_file]");
                return;
            }
            debug("archive path: [" . $archive_path . "]");
        }
        else {
            debug("Connector terminated. Files is not ready.");
            return;
        }
        //TODO: make it automatic to detect .... the likes of dwca/ and Data/
        if    (file_exists($temp_dir . $check_file_or_folder_name))                 return array('archive_path' => $temp_dir,               'temp_dir' => $temp_dir);
        elseif(file_exists($archive_path . "/" . $check_file_or_folder_name))       return array('archive_path' => $archive_path,           'temp_dir' => $temp_dir);
        elseif(file_exists($temp_dir ."dwca/". $check_file_or_folder_name))         return array('archive_path' => $temp_dir."dwca/",       'temp_dir' => $temp_dir);
        elseif(file_exists($temp_dir ."Data/". $check_file_or_folder_name))         return array('archive_path' => $temp_dir."Data/",       'temp_dir' => $temp_dir); //e.g. https://www.biodiversitylibrary.org/data/hosted/data.zip
        elseif(file_exists($temp_dir ."$tmpfolder/". $check_file_or_folder_name))   return array('archive_path' => $temp_dir."$tmpfolder/", 'temp_dir' => $temp_dir);
        elseif(file_exists($archive_path))                                          return array('archive_path' => $archive_path,           'temp_dir' => $temp_dir);
        else {
            echo "\n1. ".$temp_dir . $check_file_or_folder_name."\n";
            echo "\n2. ".$archive_path . "/" . $check_file_or_folder_name."\n";
            echo "\n3. ".$temp_dir ."dwca/". $check_file_or_folder_name."\n";
            echo "\n4. ".$temp_dir ."Data/". $check_file_or_folder_name."\n";
            echo "\n5. ".$temp_dir ."$tmpfolder/". $check_file_or_folder_name."\n";
            echo "\n6. ".$archive_path."\n";
            debug("Can't find check_file_or_folder_name [$check_file_or_folder_name].");
            recursive_rmdir($temp_dir); //un-comment in real operation
            echo "\ntemp. dir removed: [$temp_dir]\n";
            return false;
        }
    }
}
?>