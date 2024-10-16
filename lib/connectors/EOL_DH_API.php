<?php
namespace php_active_record;
/* connector: No specific connector. But now used in EOLv2MetadataAPI.php 

What DH to use as of Oct 16, 2024. From Katja:
Hi Eli, 
It depends on what you are using it for. If you need the currently active DH, that's still 
[this one](https://opendata.eol.org/dataset/tram-807-808-809-810-dh-v1-1/resource/1c3b5f47-a3b9-40d3-a31e-13d91bbbed35). 
https://editors.eol.org/uploaded_resources/1c3/b5f/dhv21.zip

But we'll hopefully get a new one soon and the current version for that 
is [here](https://opendata.eol.org/dataset/tram-807-808-809-810-dh-v1-1/resource/157a00d8-489d-4993-8db7-67d2301cc43c).
https://editors.eol.org/other_files/DWH/DH223test2.zip
*/


class EOL_DH_API
{
    function __construct()
    {
        // for the longest time
        $this->EOL_DH = "http://localhost/cp/summary%20data%20resources/DH/eoldynamichierarchywithlandmarks.zip";

        // as of Oct 16, 2024 from Katja:
        // /Volumes/Crucial_2TB/other_files2/DH_working_2024/dhv21/taxon.tab
        $this->EOL_DH = "http://localhost/other_files2/DH_working_2024/dhv21.zip";
    }
    private function extract_DH($filename)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->EOL_DH, $filename, array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $tables['taxa'] = $filename;
        $paths['tables'] = $tables;
        return $paths;
    }
    private function prep_DH()
    {
        // $filename = 'taxa.txt'; //obsolete
        $filename = 'taxon.tab';
        // if(true) {
        if(Functions::is_production()) {
            if(!($info = self::extract_DH($filename))) return;
        }
        else { //local development only
            /*
            $info = Array('archive_path' => '/opt/homebrew/var/www/eol_php_code/tmp/dir_52635/EOL_dynamic_hierarchy/',  //for eoldynamichierarchyv1.zip
                          'temp_dir' => '/opt/homebrew/var/www/eol_php_code/tmp/dir_52635/',
                          'tables' => Array('taxa' => 'taxa.txt'));
            $info = Array('archive_path' => '/opt/homebrew/var/www/eol_php_code/tmp/dir_86040/',                        //for eoldynamichierarchywithlandmarks.zip
                          'temp_dir' => '/opt/homebrew/var/www/eol_php_code/tmp/dir_86040/',
                          'tables' => Array('taxa' => 'taxa.txt'));
            */
            $info = Array('archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_35497/',                             //for dhv21.zip
                          'temp_dir'     => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_35497/',
                          'tables'       => Array('taxa' => $filename));            
        }
        print_r($info);
        return $info;
    }
    public function parse_DH()
    {
        $info = self::prep_DH();
        $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )
                Array(
                    [taxonID] => 93302
                    [acceptedNameUsageID] => 93302
                    [parentNameUsageID] => -1
                    [scientificName] => Cellular Organisms
                    [taxonRank] => clade
                    [source] => trunk:b72c3e8e-100e-4e47-82f6-76c3fd4d9d5f
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 6061725
                    [EOLidAnnotations] => manual;
                    [Landmark] => 
                )
                */
                /* debugging
                // if($rec['EOLid'] == 3014446) {print_r($rec); exit;}
                // if($rec['taxonID'] == 93302) {print_r($rec); exit;}
                // if($rec['Landmark']) print_r($rec);
                if(in_array($rec['EOLid'], Array(7687,3014522,42399419,32005829,3014446,2908256))) print_r($rec);
                */

                $eolID = $rec['eolID'];

                $this->EOL_2_DH[$eolID] = $rec['taxonID'];
                $this->DH_2_EOL[$rec['taxonID']] = $eolID;
                $this->parent_of_taxonID[$rec['taxonID']] = $rec['parentNameUsageID'];
                $this->landmark_value_of[$eolID] = $rec['Landmark'];
                if($rec['taxonRank'] == 'family') $this->is_family[$eolID] = '';
            }
        }
        /* may not want to force assign this:
        $this->DH_2_EOL[93302] = 6061725; //Biota - Cellular Organisms
        */
        
        // remove temp dir
        // recursive_rmdir($info['temp_dir']);
        // echo ("\n temporary directory removed: " . $info['temp_dir']);
    }
    public function get_ancestry_via_DH($page_id, $landmark_only = true)
    {
        $final = array(); $final2 = array();
        $taxonID = @$this->EOL_2_DH[$page_id];
        if(!$taxonID) {
            echo "\nThis page_id [$page_id] is not found in DH.\n";
            return array();
        }
        while(true) {
            if($parent = @$this->parent_of_taxonID[$taxonID]) $final[] = $parent;
            else break;
            $taxonID = $parent;
        }
        $i = 0;
        foreach($final as $taxonID) {
            // echo "\n$i. [$taxonID] => ";
            if($EOLid = @$this->DH_2_EOL[$taxonID]) {
                /* new strategy: using Landmark value   ver 1
                if($this->landmark_value_of[$EOLid]) $final2[] = $EOLid; */

                if($landmark_only) { //default; new strategy: using Landmark value   ver 2
                    if($this->landmark_value_of[$EOLid] || isset($this->is_family[$EOLid])) $final2[] = $EOLid;
                }
                else { //orig strategy
                    $final2[] = $EOLid;
                }
            }
            $i++;
        }
        return $final2;
    }
}
?>