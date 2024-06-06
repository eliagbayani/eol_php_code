<?php
namespace php_active_record;
/*  datahub_ggbn.php 
https://content.eol.org/resources/1222
*/
class DataHub_GGBN
{
    function __construct($folder = false)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));    
        }
        $this->debug = array();
        $this->download_options_GGBN = array('resource_id' => 'GGBN', 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800*2, 'download_attempts' => 1);
        $this->download_options_GGBN['expire_seconds'] = false; //May 2024
        $this->taxon_page = 'https://www.ggbn.org/ggbn_portal/search/result?fullScientificName='; //e.g. Gadus+morhua
        $this->api_call = "https://www.ggbn.org/ggbn_portal/api/search?getClassification&sampleType="; //DNA or specimen (or tissue; notused)
        /* 
        You can use both data.ggbn.org and www.ggbn.org.
        source for list of sampleTypes: https://www.ggbn.org/ggbn_portal/api/search?getCounts
        */
        // /*
        if(Functions::is_production()) $save_path = "/extra/other_files/dumps_GGI/";
        else                           $save_path = "/Volumes/Crucial_2TB/other_files2/dumps_GGI/";
        if(!is_dir($save_path)) mkdir($save_path);
        $save_path = $save_path . "GGBN/";
        if(!is_dir($save_path)) mkdir($save_path);
        $this->json_dump['DNA']      = $save_path.'getClassification_sampleType_DNA.txt';
        $this->json_dump['specimen'] = $save_path.'getClassification_sampleType_specimen.txt';
        // */
    }
    function start()
    {
        $this->debug = array();
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        // --------------------------------------------------------------------------------- 
        $sampleTypes = array('DNA', 'specimen');
        // $sampleTypes = array('specimen'); //dev only

        // step 1: download the 2 dump files
        foreach($sampleTypes as $sampleType) {
            $url = $this->api_call . $sampleType;
            $target = $this->json_dump[$sampleType];
            self::save_dump_files($url, $target);
        }

        // step 2: 
        foreach($sampleTypes as $sampleType) {
            $this->sampleType = $sampleType;
            $json = file_get_contents($this->json_dump[$sampleType]);
            $obj = json_decode($json, true); //print_r($obj); exit;
            $groups = array_keys($obj); print_r($groups);
            /*Array(
                [0] => method       [1] => filters      [2] => familia      [3] => genus
                [4] => species      [5] => classis      [6] => ordo         [7] => phylum   [8] => regnum
            )*/
            self::process_taxa($obj);
        }
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    function save_dump_files($url, $target)
    {   //wget -nc http://www.boldsystems.org/pics/KANB/USNM_442211_photograph_KB17_037_155mmSL_LRP_17_07+1507842962.JPG -O /Volumes/AKiTiO4/other_files/xxx/file.ext
        $cmd = WGET_PATH . " '$url' -O "."'$target'"; //wget -nc --> means 'no overwrite'
        $cmd .= " 2>&1";
        echo "\nurl: [$url]";   echo "\ntarget: [$target]"; echo "\ncmd: [$cmd]\n";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) echo("\n<i>URL path does not exist.\n$url</i>\n\n"); //string is found
        echo "\n---\n".trim($shell_debug)."\n---\n";
    }
    private function process_taxa($obj)
    {
        $rank_label['familia'] = 'family';
        $rank_label['genus'] = 'genus';
        $rank_label['species'] = 'species';
        $rank_label['classis'] = 'class';
        $rank_label['ordo'] = 'order';
        $rank_label['phylum'] = 'phylum';
        $rank_label['regnum'] = 'kingdom';

        foreach($obj as $group => $recs) {
            if(in_array($group, array('familia', 'genus', 'species', 'classis', 'ordo', 'phylum', 'regnum'))) { echo "\nprocess [$group]\n";
                $taxonRank = $rank_label[$group];
                $i = 0;
                foreach($recs as $sciname => $count) { $i++; if(($i % 20000) == 0) echo "\n $i ";
                    // echo "\n[$sciname] [$count]\n"; exit;
                    if(!self::valid_name($sciname)) continue;
                    $save = array();
                    $save['taxonID'] = strtolower(str_replace(" ", "_", $sciname));
                    $save['scientificName'] = $sciname;
                    $save['taxonRank'] = $taxonRank;
                    self::write_taxon($save);

                    $rec = array();
                    $rec['tax id'] = $save['taxonID'];
                    $rec['count'] = $count;
                    $rec['sciname'] = $sciname;
                    self::write_MoF($rec);
                }
            }
        }
    }
    private function write_taxon($rec)
    {   //print_r($rec);
        $taxonID = $rec['taxonID'];
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $taxonID;
        $taxon->scientificName      = $rec['scientificName'];
        $taxon->taxonRank           = $rec['taxonRank'];
        if(!isset($this->taxonIDs[$taxonID])) {
            $this->taxonIDs[$taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function write_MoF($rec)
    {   //print_r($o); exit;
        $taxonID = $rec['tax id'];
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $this->taxon_page . urlencode($rec['sciname']);
        // $save['bibliographicCitation'] = '';
        // $save['measurementRemarks'] = ""; 
        if    ($this->sampleType == 'DNA')      $mType = 'http://eol.org/schema/terms/NumberDNARecordsInGGBN';
        elseif($this->sampleType == 'specimen') $mType = 'http://eol.org/schema/terms/NumberSpecimensInGGBN';
        $mValue = $rec['count'];
        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.        
        $this->func->add_string_types($save, $mValue, $mType, "true");    
    }
    private function valid_name($sciname)
    {
        if($sciname == 'N/A') return false;
        return true;
    }
    /* On Wed, May 29, 2024 at 4:14 PM Dröge, Gabriele <G.Droege@bo.berlin> wrote:
    Dear Eli,
    Thanks a lot for your request and interest in GGBN!
    You can use the method getClassification using sampleType as filter. E.g. https://www.ggbn.org/ggbn_portal/api/search?getClassification&sampleType=DNA
    This will give you all families, phyla, species etc. we have for sampletype = DNA. So it is not a full dump, but at least only one call per sampleType.
    However it might be, that species will cause a little problem because of the sheer amount. Please do let me know if that’s the case. You can use both data.ggbn.org and www.ggbn.org.
    Which kind of sampletypes we have you can find out through “samples” in the result of https://www.ggbn.org/ggbn_portal/api/search?getCounts
    Let me know if this is what you were looking for or if you’ll need any other calls. We are currently developing a new portal for GGBN and if we can improve our API for users like EOL we are happy to do that.
    We are also linking out to EOL from our individual sample pages using, e.g. http://eol.org/search/?q=Tamias+dorsalis This has been used for years now. I wonder if there is a better way to make this connection? It seems, as your page is very slow at the moment and it can’t resolve the url.
    Example GGBN page: https://www.ggbn.org/ggbn_portal/search/record?unitID=MSB%3AMamm%3A283479&collectioncode=Mamm&institutioncode=MSB&guid=http%3A%2F%2Farctos.database.museum%2Fguid%2FMSB%3AMamm%3A283479%3Fpid%3D27237793
    Best,
    Gabi */
}
?>