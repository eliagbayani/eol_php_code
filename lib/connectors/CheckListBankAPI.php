<?php
namespace php_active_record;
/* ALL THIS FROM COPIED TEMPLATE: TraitDataImportAPI.php
real connector now: [CheckListBank_tool.php]
*/
class CheckListBankAPI extends CheckListBankRules
{
    function __construct($app)
    {
        $this->resource_id = ''; //will be initialized in start()
        $this->app = $app;
        // $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        // $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        
        $this->download_options = array('cache' => 1, 'resource_id' => 'CheckListBank', 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*1);
        // $this->download_options['expire_seconds'] = false; //debug only
        
        /* ============================= START for specimen_export ============================= */
        if($app == 'specimen_export') {}
        /* ============================= END for specimen_export ============================= */

        /* ============================= START for image_export ============================= */
        if($app == 'CheckListBank_tool') { //trait_data_import
            // $this->input['path'] = DOC_ROOT.'/applications/specimen_image_export/temp/'; //input.xlsx
            // $this->input['path'] = DOC_ROOT.'/applications/trait_data_import/temp/'; //input.xlsx
            $this->input['path'] = DOC_ROOT.'/applications/CheckListBank_tool/temp/'; //input.xlsx
            $dir = $this->input['path'];
            if(!is_dir($dir)) mkdir($dir);
            
            // $this->resources['path'] = CONTENT_RESOURCE_LOCAL_PATH."MarineGEO_sie/";
            // $this->resources['path'] = CONTENT_RESOURCE_LOCAL_PATH."Trait_Data_Import/";
            $this->resources['path'] = CONTENT_RESOURCE_LOCAL_PATH."CheckListBank_files/";

            $dir = $this->resources['path'];
            if(!is_dir($dir)) mkdir($dir);

            $this->map['taxonomicStatus']['valid_invalid'] = array('valid', 'invalid');
            $this->map['taxonomicStatus']['accepted_not_accepted'] = array('accepted', 'not accepted', 'unaccepted');
            $this->map['unacceptability_reason'] = array("junior synonym", "original name/combination", "subsequent name/combination", "junior homonym", "homonym & junior synonym", "unavailable, database artifact", "unavailable, literature misspelling", "unavailable, incorrect orig.[inal] spelling", "unavailable, suppressed by ruling", "unavailable, nomen nudum", "unavailable, other", "unjustified emendation", "unnecessary replacement", "nomen oblitum", "misapplied", "pro parte", "other, see comments", "nomen dubium");

            $this->map['taxonRank'] = array("", "Kingdom", "Subkingdom", "Infrakingdom", "Superdivision", "Superphylum", "Division", "Phylum", "Subdivision", "Subphylum", "Infradivision", "Infraphylum", "Parvdivision", "Parvphylum", "Superclass", "Class", "Subclass", "Infraclass", "Superorder", "Order", "Suborder", "Infraorder", "Section", "Subsection", "Superfamily", "Family", "Subfamily", "Tribe", "Subtribe", "Genus", "Subgenus", "Species", "Subspecies", "Variety", "Form", "Subvariety", "Race", "Stirp", "Morph", "Aberration", "Subform");
            $this->map['locality'] = array("", "Antarctica/Southern Ocean", "North America", "Middle America", "Caribbean", "South America", "Europe & Northern Asia (excluding China)", "Africa", "Southern Asia", "Australia", "Oceania", "Eastern Atlantic Ocean", "Western Atlantic Ocean", "Indo-West Pacific", "East Pacific");
            $this->map['occurrenceStatus'] = array("", "Native", "Introduced", "Native & Introduced", "Incidental", "Native & Extirpated", "Native & Extinct");
            // $this->opendata_dataset_api = 'https://opendata.eol.org/api/3/action/package_show?id='; //not used
        }
        /* ============================= END for image_export ============================= */
    }
    function start($filename = false, $form_url = false, $uuid = false, $json = false)
    {
        // echo "\nstart filename: [$filename]\n";
        $this->arr_json = json_decode($json, true);
        if($val = @$this->arr_json['timestart']) $timestart = $val;               //normal operation
        else                                     $timestart = time_elapsed();     //during dev only - command line
        if($GLOBALS['ENV_DEBUG']) print_r($this->arr_json);
        
        // /* for $form_url:
        if($form_url && $form_url != '_') $filename = self::process_form_url($form_url, $uuid); //this will download (wget) and save file in /specimen_export/temp/
        // */
        
        /* doesn't seem to pass here anymore
        if(pathinfo($filename, PATHINFO_EXTENSION) == "zip") { //e.g. taxon.tab.zip
            $filename = self::process_zip_file($filename);
            // for csv files - file format: Taxa File
            if(pathinfo($filename, PATHINFO_EXTENSION) == "csv") { // exit("\n<br>meron csv [$filename]<br>\n");
                $filename = $this->input['path'].$filename;             // added complete path
                $filename = self::convert_csv2tsv($filename);
                $filename = pathinfo($filename, PATHINFO_BASENAME);     // back to just basename, e.g. 1687492564.tsv
            }
            // else exit("\n<br>wala daw csv [$filename]<br>\n"); //no need to trap            
        }
        */
        
        if(!$filename) exit("\nNo filename: [$filename]. Will terminate.\n");
        // $input_file = $this->input['path'].$filename;
        // $this->temp_folder = str_replace($filename, "", $input_file);
        $this->temp_folder = $this->input['path'];

        // print_r($this->arr_json); exit("\n$this->temp_folder\n".$this->input['path']."\nstop 1\n"); //good debug
        /*Array( $this->arr_json
            [Filename_ID] => 
            [Short_Desc] => 
            [TransID]           => 1719638494
            [Taxon_file]        => 1719638494_Taxon.tsv
            [Distribution_file] => 1719638494_Distribution.tsv
            [timestart] => 0.007148
        )
        Array(
            [Filename_ID] => 
            [Short_Desc] => 
            [TransID] => 1721807000
            [orig_taxa] => 1721807000_orig_taxa.tsv
            [orig_reference] => 1721807000_orig_reference.tsv
            [timestart] => 0.007816
        )*/

        $this->resource_id = $this->arr_json['TransID']; // exit("\nEli is here...\n[".$this->resource_id."]\n");
        
        $file_1 = $this->temp_folder . @$this->arr_json['Taxon_file'];
        $file_2 = $this->temp_folder . @$this->arr_json['Distribution_file'];
        $file_3 = $this->temp_folder . @$this->arr_json['orig_taxa'];
        $file_4 = $this->temp_folder . @$this->arr_json['orig_reference'];

        echo "\nfile_1: [$file_1]\n";
        echo "\nfile_2: [$file_2]\n";
        echo "\nfile_3: [$file_3]\n";
        echo "\nfile_4: [$file_4]\n";

        if(is_file($file_1) && is_file($file_2)) {
            $this->start_CheckListBank_process();
        }
        elseif(is_file($file_3) && is_file($file_4)) {
            exit("\ndo 2nd interface\n");
        }
        else debug("\nInput files not found\n");
    }

    /*=======================================================================================================*/ //COPIED TEMPLATE BELOW
    /*=======================================================================================================*/
    private function process_form_url($form_url, $uuid)
    {   //wget -nc https://content.eol.org/data/media/91/b9/c7/740.027116-1.jpg -O /Volumes/AKiTiO4/other_files/bundle_images/xxx/740.027116-1.jpg
        $ext = pathinfo($form_url, PATHINFO_EXTENSION);
        $target = $this->input['path'].$uuid.".".$ext;
        $cmd = WGET_PATH . " $form_url -O ".$target; //wget -nc --> means 'no overwrite'
        $cmd .= " 2>&1";
        $shell_debug = shell_exec($cmd);
        if(stripos($shell_debug, "ERROR 404: Not Found") !== false) exit("\n<i>URL path does not exist.\n$form_url</i>\n\n"); //string is found
        echo "\n---\n".trim($shell_debug)."\n---\n"; //exit;
        return pathinfo($target, PATHINFO_BASENAME);
    }
    function process_zip_file($filename)
    {
        $test_temp_dir = create_temp_dir();
        $local = Functions::save_remote_file_to_local($this->input['path'].$filename);
        $output = shell_exec("unzip -o $local -d $test_temp_dir");
        if($GLOBALS['ENV_DEBUG']) echo "<hr> [$output] <hr>";
        // $ext = "tab"; //not used anymore
        $new_local = self::get_file_inside_dir_with_this_extension($test_temp_dir."/*.{txt,tsv,tab,csv}");
        $new_local_ext = pathinfo($new_local, PATHINFO_EXTENSION);
        $destination = $this->input['path'].pathinfo($filename, PATHINFO_FILENAME).".$new_local_ext";
        /* debug only
        echo "\n\nlocal file = [$local]";
        echo "\nlocal dir = [$test_temp_dir]";
        echo "\nnew local file = [$new_local]";
        echo "\nnew_local_ext = [$new_local_ext]\n\n";
        echo "\ndestination = [$destination]\n\n";
        */
        if($GLOBALS['ENV_DEBUG']) print_r(pathinfo($destination));
        if(Functions::file_rename($new_local, $destination)) {}
        else echo "\nERRORx: file_rename failed.\n";
        // exit("\nditox 100\n");
        //remove these 2 that were used above if file is a zip file
        unlink($local);                     //echo "\nFile deleted: [$local]\n";
        recursive_rmdir($test_temp_dir);    //echo "\nFolder deleted: [$test_temp_dir]\n";
        /*
        File deleted: [/Volumes/AKiTiO4/eol_php_code_tmp/tmp_18704.file] 
        Folder deleted: [/Volumes/AKiTiO4/eol_php_code_tmp/dir_17044]
        */
        return pathinfo($destination, PATHINFO_BASENAME);
    }
    function convert_csv2tsv($csv_file) // temp/1687855441.csv
    {   // exit("<br>source csv: [$csv_file]<br>");
        $tsv_file = str_replace(".csv", ".tsv", $csv_file);
        $WRITE = Functions::file_open($tsv_file, "w");
        $fp = fopen($csv_file, 'r');
        $data = array();
        while (($row = fgetcsv($fp))) { // echo "<pre>"; print_r($row); exit;
            /* Array(
                [0] => taxonID
                [1] => furtherInformationURL
                [2] => scientificName
            ) */
            $tab_separated = implode("\t", $row); 
            fwrite($WRITE, $tab_separated . "\n");
        }
        fclose($fp);
        fclose($WRITE);
        return $tsv_file;
    }
    private function get_file_inside_dir_with_this_extension($files)
    {
        $arr = glob($files, GLOB_BRACE);
        // echo "\nglob() "; print_r($arr); //good debug
        if($val = $arr[0]) return $val;
        else exit("\nERROR: File to process does not exist.\n");
        // foreach (glob($files) as $filename) echo "\n- $filename\n";
    }
}
?>