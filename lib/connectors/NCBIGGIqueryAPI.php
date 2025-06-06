<?php
namespace php_active_record;
/* connector: [723] NCBI, GGBN, GBIF, BHL, BOLDS data coverage (DATA-1369 and others)
http://content.eol.org/resources/555

#==== 5 AM, every 4th day of the month -- [Number of sequences in GenBank (DATA-1369)]
00 05 4 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/723.php > /dev/null

#==== 5 AM, every 5th day of the month -- [Number of DNA and specimen records in GGBN (DATA-1372)]
00 05 5 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/730.php > /dev/null

#==== 5 AM, every 6th day of the month -- [Number of records in GBIF (DATA-1370)]
00 05 6 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/731.php > /dev/null

#==== 5 AM, every 7th day of the month -- [Number of pages in BHL (DATA-1417)]
00 05 7 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/743.php > /dev/null

#==== 5 AM, every 8th day of the month -- [Number of specimens with sequence in BOLDS (DATA-1417)]
00 05 8 * * /usr/bin/php /opt/eol_php_code/update_resources/connectors/747.php > /dev/null

----- Start 2024 -----
Background: 
Resource DwCA was last generated Aug 4, 2018
Here are the current measurementTypes we're getting from respective databases.
We either used an API service or a webpage service (scraped) whichever was available.

BOLDS
"http://eol.org/schema/terms/NumberRecordsInBOLD"           - removed non-public -
"http://eol.org/schema/terms/RecordInBOLD" (boolean)        - removed
"http://eol.org/schema/terms/NumberPublicRecordsInBOLD"

BHL
"http://eol.org/schema/terms/NumberReferencesInBHL"
"http://eol.org/schema/terms/ReferenceInBHL" (boolean)      - removed

GBIF
"http://eol.org/schema/terms/NumberRecordsInGBIF"
"http://eol.org/schema/terms/RecordInGBIF" (boolean)        - removed

GGBN
"http://eol.org/schema/terms/NumberDNARecordsInGGBN"
"http://eol.org/schema/terms/NumberSpecimensInGGBN"
"http://eol.org/schema/terms/SpecimensInGGBN" (boolean)     - removed

https://www.ggbn.org/ggbn_portal/search/browse?sampletype=DNA&page=23&per-page=150


NCBI
"http://eol.org/schema/terms/NumberOfSequencesInGenBank"
"http://eol.org/schema/terms/SequenceInGenBank" (boolean)   - removed

EOL
"http://eol.org/schema/terms/NumberRichSpeciesPagesInEOL"

INAT (excluded here, it has its own connector)
"http://eol.org/schema/terms/NumberOfiNaturalistObservations"
*/
class NCBIGGIqueryAPI
{
    function __construct($folder = null, $process_level = null)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->process_level = $process_level;
            $this->taxa = array();
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
            $this->occurrence_ids = array();
            $this->measurement_ids = array();
        }
        $this->download_options = array('resource_id' => 723, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); //3 months to expire
        // $this->download_options['expire_seconds'] = false; //debug - false -> wont expire; 0 -> expires now

        /* obsolete, no longer used
        // local
        $this->families_list = "http://localhost/cp/NCBIGGI/falo2.in";
        $this->families_list = "https://dl.dropboxusercontent.com/u/7597512/NCBI_GGI/falo2.in";
        */

        // NCBI service
        /*
        Guidelines for Scripting Calls to NCBI Servers
        Do not overload NCBI's systems. Users intending to send numerous queries and/or retrieve large numbers of records should comply with the following:

- Run retrieval scripts on weekends or between 9 pm and 5 am Eastern Time weekdays for any series of more than 100 requests.
- Send E-utilities requests to https://eutils.ncbi.nlm.nih.gov, not the standard NCBI Web address.
- Make no more than 3 requests every 1 second.
- Use the URL parameter email, and tool for distributed software, so that we can track your project and contact you if there is a problem. 
For more information, please see the Usage Guidelines and Requirements section in the Entrez Programming Utilities Help Manual.
- NCBI's Disclaimer and Copyright notice must be evident to users of your service. NLM does not claim the copyright on the abstracts in PubMed; 
however, journal publishers or authors may. NLM provides no legal advice concerning distribution of copyrighted materials, consult your legal counsel.
        */
        
        // $this->family_service_ncbi =    "http://www.ncbi.nlm.nih.gov   /entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        $this->family_service_ncbi =    "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term="; //always use this not above
        /* to be used if u want to get all Id's, that is u will loop to get all Id's so server won't be overwhelmed: &retmax=10&retstart=0 */

        // GGBN data portal:
        $this->family_service_ggbn = "http://www.dnabank-network.org/Query.php?family=";                // original
        $pre = "http://data.ggbn.org"; //legacy still works
        // $pre = "http://www.ggbn.org"; //2024 also works
        $this->family_service_ggbn = $pre."/Query.php?family=";                          // "Dröge, Gabriele" <g.droege@bgbm.org> advised to use this instead, Apr 17, 2014
        $this->family_service_ggbn = $pre."/ggbn_portal/api/search?getSampletype&name="; // "Dröge, Gabriele" <g.droege@bgbm.org> advised to use this API instead, May 3, 2016
        /* 2024
        Documentation for the GGBN API is work in progress. Some examples in advance:
        API URL: http://www.ggbn.org/ggbn_portal/api/search?
            getCounts	total counts of different taxon levels and sample types (used for the start page)
            getSampletype&name=Arthropoda	total counts of sample types for a certain name (any level)
            getClassification&name=Chordata	total counts of samples for all children of a name
        Feel free to test it. If you have any questions please contact support@ggbn.org. More information will follow soon.
        ============================================================================ GitHub link here: https://github.com/EOL/ContentImport/issues/6#issuecomment-2074392011
        Hi Jen,
        For GGBN we're only getting "DNA" and "specimen" counts.
        But there is also now "tissue". e.g. ...
        ============================================================================
        */

        //GBIF services
        $this->gbif_taxon_info = "http://api.gbif.org/v1/species/match?name="; //http://api.gbif.org/v1/species/match?name=felidae&kingdom=Animalia
        $this->gbif_record_count = "http://api.gbif.org/v1/occurrence/count?taxonKey=";
        $this->download_options_GBIF = $this->download_options;
        $this->download_options_GBIF['resource_id'] = "";
        // https://www.gbif.org/dataset/d7dddbf4-2cf0-4f39-9b2a-bb099caae36c --- GBIF Backbone Taxonomy
        if(Functions::is_production()) $this->download_options_GBIF['cache_path'] = "/extra/eol_cache_gbif/";
        else                           $this->download_options_GBIF['cache_path'] = '/Volumes/Thunderbolt4/eol_cache_gbif/';

        // BHL services
        $this->bhl_taxon_page = "http://www.biodiversitylibrary.org/name/";
        $this->bhl_taxon_in_csv = "http://www.biodiversitylibrary.org/namelistdownload/?type=c&name=";
        $this->bhl_taxon_in_xml = "http://www.biodiversitylibrary.org/api2/httpquery.ashx?op=NameGetDetail&apikey=deabdd14-65fb-4cde-8c36-93dc2a5de1d8&name=";

        // BOLDS portal
        $this->bolds_taxon_page_by_name = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?searchTax=&taxon=";
        $this->bolds_taxon_page_by_id = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=";
        $this->bolds["TaxonSearch"] = "http://www.boldsystems.org/index.php/API_Tax/TaxonSearch?taxName=";
        $this->bolds["TaxonData_orig"] = "http://www.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=basic,stats&taxId="; //orig call; enough for 723
        /* new below
        REMINDER: the new call sometimes gives out a wrong json bec. of some maybe un-escaped chars, thus a fallback of the orig call is needed.
        The new call is just to uniform the BOLDS API calls.
        */
        $this->bolds["TaxonData"] = "http://www.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=all&includeTree=true&taxId="; //new call, will be used in BOLDS new connector
        $this->download_options_BOLDS = array('resource_id' => 'BOLDS', 'expire_seconds' => 60*60*24*30*9, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); //9 months to expire

        // INAT api
        $this->inat['taxa_search'] = "https://api.inaturalist.org/v1/taxa?q="; //q=Gadidae
        $this->inat['observation_search'] = "https://api.inaturalist.org/v1/observations/histogram?taxon_is_active=true&verifiable=true&date_field=observed&interval=month_of_year&taxon_id="; //taxon_id=44185 Muridae
        $this->download_options_INAT = array('resource_id' => 723, 'expire_seconds' => 60*60*24*30*3, 'download_wait_time' => 3000000, 'timeout' => 10800, 'download_attempts' => 1); //3 months to expire

        $this->inat['taxon_page'] = "https://www.inaturalist.org/taxa/"; // e.g. taxa/47390

        
        // stats
        $this->TEMP_DIR = create_temp_dir() . "/";
        echo "\nTEMP_DIR: [".$this->TEMP_DIR."]\n";
        $this->names_no_entry_from_partner_dump_file = $this->TEMP_DIR . "names_no_entry_from_partner.txt";
        $this->name_from_eol_api_dump_file = $this->TEMP_DIR . "name_from_eol_api.txt";
        $this->names_dae_to_nae_dump_file = $this->TEMP_DIR . "names_dae_to_nae.txt";

        /* // FALO report
        $this->names_in_falo_but_not_in_irmng = $this->TEMP_DIR . "families_in_falo_but_not_in_irmng.txt";
        $this->names_in_irmng_but_not_in_falo = $this->TEMP_DIR . "families_in_irmng_but_not_in_falo.txt";
        */

        $this->ggi_databases = array("ggbn", "bhl"); //for family-level only | removed 'inat', 'gbif' here, they have their own connector.
                                                                                     //removed 'bolds', exceeded your allowed request quota.
                                                                                     //removed 'ncbi' as well
        
        // $this->ggi_databases = array("ncbi"); //debug - use to process 1 database - OK Apr 2024
        // $this->ggi_databases = array("ggbn"); //debug - use to process 1 database - OK Apr 2024
        // $this->ggi_databases = array("gbif"); //debug - use to process 1 database - OK Apr 2024
        // $this->ggi_databases = array("bhl"); //debug - use to process 1 database - OK Apr 2024
        // $this->ggi_databases = array("bolds"); //debug - use to process 1 database - OK Apr 2024
        $this->ggi_databases = array("inat"); //debug - use to process 1 database - OK Apr 2024 NEW

        $this->ggi_path = DOC_ROOT . "temp/GGI/";
        $this->blacklist_bhl_csv_call = $this->ggi_path."blacklist_bhl_csv_call.txt";

        $this->eol_api["search"]    = "http://eol.org/api/search/1.0.json?page=1&exact=true&filter_by_taxon_concept_id=&filter_by_hierarchy_entry_id=&filter_by_string=&cache_ttl=&q=";
        $this->eol_api["page"][0]   = "http://eol.org/api/pages/1.0/";
        $this->eol_api["page"][1]   = ".json?images=0&videos=0&sounds=0&maps=0&text=0&iucn=false&subjects=overview&licenses=all&details=true&common_names=false&synonyms=false&references=false&vetted=1&cache_ttl=";
        $this->databases_to_check_eol_api["ncbi"] = "NCBI Taxonomy";
        $this->databases_to_check_eol_api["gbif"] = "GBIF Nub Taxonomy";
        $this->databases_to_check_eol_api["ggbn"] = "ITIS Catalogue of Life";
        $this->databases_to_check_eol_api["bolds"] = "-BOLDS-";

        $this->temp_family_table_file = DOC_ROOT . "tmp/family_table.txt";
    }
    /* To do:
    - get all family and genus for iNat
            https://api.inaturalist.org/v1/taxa?rank=family&page=1
            https://api.inaturalist.org/v1/taxa?rank=genus&page=2&per_page=50

    - get all family and genus for ggbn: Animalia, Fungi, *Archaebacteria, Plantae, *Monera, Chromista, *Protista, Archaea, Bacteria, Protozoa, *Chrysophytes
            https://data.ggbn.org/ggbn_portal/api/search?getClassification&name=Animalia
            https://data.ggbn.org/ggbn_portal/api/search?getClassification&name=Fungi
            https://data.ggbn.org/ggbn_portal/api/search?getClassification&name=Plantae
            https://data.ggbn.org/ggbn_portal/api/search?getClassification&name=Chromista
            https://data.ggbn.org/ggbn_portal/api/search?getClassification&name=Archaea
            https://data.ggbn.org/ggbn_portal/api/search?getClassification&name=Bacteria
            https://data.ggbn.org/ggbn_portal/api/search?getClassification&name=Protozoa
    */
    function start()
    {
        require_library('connectors/DataHub_INAT_API');
        $this->func = new DataHub_INAT_API();

        $this->taxa_blacklist_bhl_csv_call = array();
        if(file_exists($this->blacklist_bhl_csv_call)) $this->taxa_blacklist_bhl_csv_call = file($this->blacklist_bhl_csv_call, FILE_IGNORE_NEW_LINES);
        echo "\nBlacklist: "; print_r($this->taxa_blacklist_bhl_csv_call); //exit;

        self::initialize_files();
        if($this->process_level == "family") self::get_all_taxa_family();
        if($this->process_level == "genus") self::get_all_taxa_genus();

        $this->archive_builder->finalize(TRUE); //moved here
    }
    function get_all_taxa_genus()
    {
        $this->ggi_databases = array("ggbn", "bhl"); //for genus-level we remove "inat" and "gbif"; they have separate connectors
                                                                                         //removed 'bolds', exceeded your allowed request quota.
                                                                                         //removed 'ncbi' as well
        $genus_taxa = self::get_DH_taxa_per_rank("genus"); // print_r($genus_taxa); exit;
        /* force assign | debug only
        $genus_taxa = array();
        $genus_taxa[] = "Gadus";
        $genus_taxa[] = "Panthera";
        // $genus_taxa = array("Quercus");
        */
        echo "\nGenus count: [".count($genus_taxa)."]\n"; //exit; //Genus count: [187774] as of Apr 27, 2024

        // /* working, a round-robin option of server load - per 100 calls each server
        $k = 0; $m = count($genus_taxa)/6; // before 9646/6
        $calls = 10; //orig is 100
        for ($i = $k; $i <= count($genus_taxa)+$calls; $i=$i+$calls) { //orig value of i is 0
            echo "\n[$i] - ";
            /* breakdown when caching
            $cont = false;
            // if($i >= 1    && $i < $m)    $cont = true;
            // if($i >= $m   && $i < $m*2)  $cont = true;
            // if($i >= $m*2 && $i < $m*3)  $cont = true;
            // if($i >= $m*3 && $i < $m*4)  $cont = true;
            // if($i >= $m*4 && $i < $m*5)  $cont = true;
            if($i >= $m*5 && $i < $m*6)  $cont = true;
            if(!$cont) continue;
            */
            
            $min = $i; $max = $min+$calls;
            foreach($this->ggi_databases as $database) {
                $this->families_with_no_data = array(); //moved here
                self::create_instances_from_taxon_object($genus_taxa, false, $database, $min, $max);
            }
            // break;              //debug only - process just a subset, just the 1st cycle
            // if($i >= 30) break; //debug only - just the first 20 cycles
        }
        self::compare_previuos_and_current_dumps_then_process();
        $this->create_taxa_archive();

        echo "\n temp dir: " . $this->TEMP_DIR . "\n";
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR); // debug - comment to check "name_from_eol_api.txt"
    }
    function get_all_taxa_family()
    {   /* obsolete
        $families = self::get_families_from_google_spreadsheet(); Google spreadsheets are very slow, it is better to use Dropbox for our online spreadsheets
        $families = self::get_families(); use to read a plain text file
        $families = self::get_families_with_missing_data_xlsx(); - utility
        $families = self::get_families_from_JonCoddington(); //working OK... for Jonathan Coddington - from email May 15-16, 2018
        */
        $families = self::get_families_xlsx(); //normal operation for resource 723

        /* families force-assign | during dev only
        $families = array("Caudinidae", "Eupyrgidae", "Gephyrothuriidae", "Molpadiidae"); //BHL
        $families[] = "Ophiuridae"; //GGBN
        $families[] = "Holothuriidae"; //BOLDS
        */
        
        echo "\nFamilies count: [".count($families)."]\n";
        if($families) {
            /* working but not round-robin, rather each database is processed one after the other.
            foreach($this->ggi_databases as $database) {
                self::create_instances_from_taxon_object($families, false, $database);
                $this->families_with_no_data = array_keys($this->families_with_no_data);
                if($this->families_with_no_data) self::create_instances_from_taxon_object($this->families_with_no_data, true, $database);
            }
            */

            // /* working, a round-robin option of server load - per 100 calls each server
            $k = 0; $m = count($families)/6; // before 9646/6
            // $calls = 10; //orig is 100
            $calls = 2;
            for ($i = $k; $i <= count($families)+$calls; $i=$i+$calls) { //orig value of i is 0
                echo "\n[$i] - ";
                /* breakdown when caching
                $cont = false;
                // if($i >= 1    && $i < $m)    $cont = true;
                // if($i >= $m   && $i < $m*2)  $cont = true;
                // if($i >= $m*2 && $i < $m*3)  $cont = true;
                // if($i >= $m*3 && $i < $m*4)  $cont = true;
                // if($i >= $m*4 && $i < $m*5)  $cont = true;
                if($i >= $m*5 && $i < $m*6)  $cont = true;
                if(!$cont) continue;
                */
                
                $min = $i; $max = $min+$calls;
                foreach($this->ggi_databases as $database) { //echo "\nProcess dbase: [$database]\n";
                    $this->families_with_no_data = array(); //moved here
                    self::create_instances_from_taxon_object($families, false, $database, $min, $max);
                    $this->families_with_no_data = array_keys($this->families_with_no_data);
                    if($this->families_with_no_data) self::create_instances_from_taxon_object($this->families_with_no_data, true, $database);
                }
                break;              //debug only - process just a subset, just the 1st cycle
                // if($i >= 2) break; //debug only - just the first 20 cycles
            }
            // */

            self::compare_previuos_and_current_dumps_then_process();
            $this->create_taxa_archive();
        }
        echo "\n temp dir: " . $this->TEMP_DIR . "\n";
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR); // debug - comment to check "name_from_eol_api.txt"
    }
    private function initialize_files()
    {
        if(!file_exists($this->ggi_path)) mkdir($this->ggi_path);
        foreach($this->ggi_databases as $database) {
            $this->ggi_text_file[$database]["previous"] = $this->ggi_path . $database  . "_$this->process_level" . ".txt";
            if(!file_exists($this->ggi_text_file[$database]["previous"])) self::initialize_dump_file($this->ggi_text_file[$database]["previous"]);
            //initialize current batch
            $this->ggi_text_file[$database]["current"]  = $this->ggi_path . $database . "_$this->process_level"  . "_working.txt";
            self::initialize_dump_file($this->ggi_text_file[$database]["current"]);
        }
    }
    private function initialize_dump_file($file)
    {
        if(!($WRITE = Functions::file_open($file, "w"))) return;
        fclose($WRITE);
        echo "\n initialize file:[$file]\n";
    }
    private function compare_previuos_and_current_dumps_then_process()
    {
        /* should be working but better to always process the 'current'.
        foreach($this->ggi_databases as $database) {
            $previous = $this->ggi_text_file[$database]["previous"];
            $current = $this->ggi_text_file[$database]["current"];
            if(Functions::count_rows_from_text_file($current) >= Functions::count_rows_from_text_file($previous)) {
                self::process_text_file($current, $database);
                unlink($previous);
                if(copy($current, $previous)) unlink($current);
            }
            else {
                self::process_text_file($previous, $database);
                unlink($current);                
            }
        }
        */

        // /* always process the current
        foreach($this->ggi_databases as $database) {
            $current = $this->ggi_text_file[$database]["current"];
            self::process_text_file($current, $database);
        }
        // */
    }
    private function process_text_file($filename, $database)
    {
        foreach(new FileIterator($filename) as $line_number => $line) {
            if($line) {
                $line = trim($line);
                $values = explode("\t", $line);
                if(count($values) != 7) {
                    echo "\n investigate: wrong no. of tabs";
                    print_r($values);
                }
                else {
                    $family             = $values[0];
                    $count              = $values[1];
                    $rec["taxon_id"]    = $values[2];
                    $rec["object_id"]   = $values[3];
                    $rec["source"]      = $values[4];
                    $label              = $values[5];
                    $measurement        = $values[6];
                    self::add_string_types($rec, $label, $count, $measurement, $family);
                }
            }
        }
    }
    private function create_instances_from_taxon_object($families, $is_subfamily = false, $database, $min=false, $max=false)
    {
        // $this->families_with_no_data = array(); //moved up
        $i = 0;
        $total = count($families);
        foreach($families as $family) { //echo "\nProcess family: [$family][".count($families)."]\n";
            $i++;
            if($min || $max) {
                // /* breakdown when caching
                $cont = false;
                if($i >= $min && $i < $max) $cont = true;
                if(!$cont) continue;
                // */
            }

            if(($i % 100) == 0) echo "\n $i of $total - [$family]\n";
            if    ($database == "ncbi")  $with_data = self::query_family_NCBI_info($family, $is_subfamily, $database);
            elseif($database == "ggbn")  $with_data = self::query_family_GGBN_info($family, $is_subfamily, $database);
            elseif($database == "gbif")  $with_data = self::query_family_GBIF_info($family, $is_subfamily, $database);
            elseif($database == "bhl")   $with_data = self::query_family_BHL_info($family, $is_subfamily, $database);
            elseif($database == "bolds") $with_data = self::query_family_BOLDS_info($family, $is_subfamily, $database);
            elseif($database == "inat")  $with_data = self::query_family_INAT_info($family, $is_subfamily, $database);

            if(($is_subfamily && $with_data) || !$is_subfamily) {
                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID         = str_replace(" ", "_", $family);
                $taxon->scientificName  = $family;
                if(!$is_subfamily) $taxon->taxonRank = $this->process_level;
                $this->taxa[$taxon->taxonID] = $taxon;
            }
        }
    }
    private function bolds_API_result_still_validYN($str)
    {
        // You have exceeded your allowed request quota. If you wish to download large volume of data, please contact support@boldsystems.org for instruction on the process. 
        if(stripos($str, 'have exceeded') !== false) { //string is found
            echo "\n[$str]\n";
            // echo "\nBOLDS special error\n"; exit("\nexit muna, remove BOLDS from the list of dbases.\n");
            sleep(60*10); //10 mins
            @$this->BOLDS_TooManyRequests++;
            if($this->BOLDS_TooManyRequests >= 3) exit("\nBOLDS should stop now.\n");
        }
    }
    private function query_family_BOLDS_info($family, $is_subfamily, $database)
    {
        $rec[$this->process_level] = $family;
        $rec["taxon_id"] = str_replace(" ", "_", $family);
        $rec["source"] = $this->bolds_taxon_page_by_name . $family;
        $options = $this->download_options_BOLDS;
        $options['download_wait_time'] = 3000000; //3 secs interval

        if($json = Functions::lookup_with_cache($this->bolds["TaxonSearch"] . $family, $options)) {

            @$this->bolds_calls++;
            if(($this->bolds_calls % 1000) == 0) echo "\n BOLDS calls made: [$this->bolds_calls] | Latest: [". substr($json,0,200) ."]\n";
            
            self::bolds_API_result_still_validYN($json); //special filter

            if($info = self::parse_bolds_taxon_search($json)) {
                $rec["source"] = $this->bolds_taxon_page_by_id . $info["taxid"];
                if(@$info["specimens"] > 0) {
                    /* removed non-public
                    $rec["object_id"]   = "_no_of_rec_in_bolds";
                    $rec["count"]       = $info["specimens"];
                    $rec["label"]       = "Number records in BOLDS";
                    $rec["measurement"] = "http://eol.org/schema/terms/NumberRecordsInBOLD";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    */
                    /* removed boolean
                    $rec["object_id"]   = "_rec_in_bolds";
                    $rec["count"]       = "http://eol.org/schema/terms/yes";
                    $rec["label"]       = "Records in BOLDS";
                    $rec["measurement"] = "http://eol.org/schema/terms/RecordInBOLD";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    */
                }
                else {
                    if(!$is_subfamily) {
                        /* removed non-public
                        $rec["object_id"] = "_no_of_rec_in_bolds";
                        self::add_string_types($rec, "Number records in BOLDS", 0, "http://eol.org/schema/terms/NumberRecordsInBOLD", $family);
                        */
                        /* removed boolean
                        $rec["object_id"] = "_rec_in_bolds";
                        self::add_string_types($rec, "Records in BOLDS", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInBOLD", $family);
                        */
                    }
                }
                if(@$info["public records"] > 0) {
                    $rec["object_id"]   = "_no_of_public_rec_in_bolds";
                    $rec["count"]       = $info["public records"];
                    $rec["label"]       = "Number public records in BOLDS";
                    $rec["measurement"] = "http://eol.org/schema/terms/NumberPublicRecordsInBOLD";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                }
                else {
                    if(!$is_subfamily) {
                        $rec["object_id"] = "_no_of_public_rec_in_bolds";
                        self::add_string_types($rec, "Number public records in BOLDS", 0, "http://eol.org/schema/terms/NumberPublicRecordsInBOLD", $family);
                    }
                }
                if(@$info["specimens"] > 0 || @$info["public records"] > 0) return true;
            }
            else self::save_to_dump($family."\t".$database, $this->names_no_entry_from_partner_dump_file);
        }
        else self::save_to_dump($family."\t".$database, $this->names_no_entry_from_partner_dump_file);

        if(!$is_subfamily) {
            /* removed non-public
            $rec["object_id"] = "_no_of_rec_in_bolds";
            self::add_string_types($rec, "Number records in BOLDS", 0, "http://eol.org/schema/terms/NumberRecordsInBOLD", $family);
            */
            /* removed boolean
            $rec["object_id"] = "_rec_in_bolds";
            self::add_string_types($rec, "Records in BOLDS", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInBOLD", $family);
            */
            $rec["object_id"] = "_no_of_public_rec_in_bolds";
            self::add_string_types($rec, "Number public records in BOLDS", 0, "http://eol.org/schema/terms/NumberPublicRecordsInBOLD", $family);
            self::has_diff_family_name_in_eol_api($family, $database); //BOLDS
        }
        self::check_for_sub_family($family); //BOLDS
        return false;
    }
    private function parse_bolds_taxon_search($json)
    {
        if($taxid = self::get_best_bolds_taxid($json)) {
            if($json = Functions::lookup_with_cache($this->bolds["TaxonData"] . $taxid, $this->download_options_BOLDS)) {
                self::bolds_API_result_still_validYN($json); //special filter
                $arr = json_decode($json);
                // print_r($arr); //good debug
                if(isset($arr->stats)) return array("taxid" => $taxid, "public records" => $arr->stats->publicrecords, "specimens" => $arr->stats->sequencedspecimens);
                else { //use orig API call
                    if($json = Functions::lookup_with_cache($this->bolds["TaxonData_orig"] . $taxid, $this->download_options_BOLDS)) {
                        self::bolds_API_result_still_validYN($json); //special filter
                        $arr = json_decode($json);
                        if(@$arr->stats) return array("taxid" => $taxid, "public records" => @$arr->stats->publicrecords, "specimens" => @$arr->stats->sequencedspecimens);
                    }
                }
            }
        }
        else return false;
    }
    private function get_best_bolds_taxid($json)
    {
        // $a = json_decode($json); print_r($a);
        /* stdClass Object(
            [top_matched_names] => Array(
                    [0] => stdClass Object
                        (
                            [taxid] => 701984
                            [taxon] => Thermoproteaceae
                            [tax_rank] => family
                            [tax_division] => Bacteria
                            [parentid] => 701983
                            [parentname] => Thermoproteales
                            [specimenrecords] => 1
                        )
                )
            [total_matched_names] => 1
        )
        */
        $ranks = array("family", "subfamily", "genus", "order"); // best rank for FALO family, in this order
        if($arr = json_decode($json)) {
            foreach($ranks as $rank) {
                foreach(@$arr->top_matched_names as $rec) {
                    if(!$rec) return false;
                    if($rec->tax_rank == $rank) return $rec->taxid;
                }
            }
            foreach(@$arr->top_matched_names as $rec) return $rec->taxid;
        }
        
        /* old ways
        $ranks = array("family", "subfamily", "genus", "order"); // best rank for FALO family, in this order
        if($arr = json_decode($json)) {
            foreach($ranks as $rank) {
                foreach($arr as $taxid => $rec) {
                    if(!$rec) return false;
                    if($rec->tax_rank == $rank) return $taxid;
                }
            }
            foreach($arr as $taxid => $rec) return $taxid;
        }
        */
        return false;
    }
    private function query_family_BHL_info($family, $is_subfamily, $database)
    {
        $rec[$this->process_level] = $family;
        $rec["taxon_id"] = str_replace(" ", "_", $family);
        $rec["source"] = $this->bhl_taxon_page . $family;
        $options = $this->download_options;
        $options['download_wait_time'] = 10000000; //10 secs interval
        if($contents = Functions::lookup_with_cache($this->bhl_taxon_in_xml . $family, $options)) {

            @$this->bhl_calls++;
            if(($this->bhl_calls % 1000) == 0) echo "\n BHL calls made: [$this->bhl_calls] | Latest: [". substr($contents,0,200) ."]\n";

            if($count = self::get_page_count_from_BHL_xml($contents)) {
                if($count > 0) {
                    $rec["object_id"]   = "_no_of_page_in_bhl";
                    $rec["count"]       = $count;
                    $rec["label"]       = "Number pages in BHL";
                    $rec["measurement"] = "http://eol.org/schema/terms/NumberReferencesInBHL";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    /* removed boolean
                    $rec["object_id"]   = "_page_in_bhl";
                    $rec["count"]       = "http://eol.org/schema/terms/yes";
                    $rec["label"]       = "Pages in BHL";
                    $rec["measurement"] = "http://eol.org/schema/terms/ReferenceInBHL";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    */
                    return true;
                }
            }
            elseif(in_array($family, $this->taxa_blacklist_bhl_csv_call)){}
            else {
                if($contents = Functions::lookup_with_cache($this->bhl_taxon_in_csv . $family, $this->download_options)) {
                    if($count = self::get_page_count_from_BHL_csv($contents)) {
                        if($count > 0) {
                            $rec["object_id"] = "_no_of_page_in_bhl";
                            $rec["count"]       = $count;
                            $rec["label"]       = "Number pages in BHL";
                            $rec["measurement"] = "http://eol.org/schema/terms/NumberReferencesInBHL";
                            self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                            /* removed boolean
                            $rec["object_id"]   = "_page_in_bhl";
                            $rec["count"]       = "http://eol.org/schema/terms/yes";
                            $rec["label"]       = "Pages in BHL";
                            $rec["measurement"] = "http://eol.org/schema/terms/ReferenceInBHL";
                            self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                            */
                            return true;
                        }
                    }
                    else self::save_to_dump($family."\t".$database, $this->names_no_entry_from_partner_dump_file);
                }
                else {
                    echo "\nBlacklist: $family\n";
                    self::save_to_dump($family, $this->blacklist_bhl_csv_call);
                }
            }
        }
        else self::save_to_dump($family."\t".$database, $this->names_no_entry_from_partner_dump_file);

        if(!$is_subfamily) {
            $rec["object_id"] = "_no_of_page_in_bhl";
            self::add_string_types($rec, "Number pages in BHL", 0, "http://eol.org/schema/terms/NumberReferencesInBHL", $family);
            /* removed boolean     
            $rec["object_id"] = "_page_in_bhl";
            self::add_string_types($rec, "Pages in BHL", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/ReferenceInBHL", $family);
            */
        }
        self::check_for_sub_family($family); //BHL
        return false;
    }
    private function get_page_count_from_BHL_xml($contents)
    {
        if(preg_match_all("/<ErrorMessage>(.*?)<\/ErrorMessage>/ims", $contents, $arr)) {
            exit("\nBHL Error found, will terminate. Investigate:\n$contents\n");
        }

        if(preg_match_all("/<PageID>(.*?)<\/PageID>/ims", $contents, $arr)) return count(array_unique($arr[1]));
        return false;
    }
    private function get_page_count_from_BHL_csv($contents)
    {
        $temp_path = temp_filepath();
        if($contents) {
            if(!($file = Functions::file_open($temp_path, "w"))) return;
            fwrite($file, $contents);
            fclose($file);
        }
        $page_ids = array();
        $i = 0;
        if(!($file = Functions::file_open($temp_path, "r"))) return;
        while(!feof($file)) {
            $i++;
            if($i == 1) $fields = fgetcsv($file);
            else {
                $rec = array();
                $temp = fgetcsv($file);
                $k = 0;
                if(!$temp) continue;
                foreach($temp as $t) {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                $parts = pathinfo($rec["Url"]);
                $page_ids[$parts["filename"]] = '';
            }
        }
        fclose($file);
        unlink($temp_path);
        return count(array_keys($page_ids));
    }
    private function has_diff_family_name_in_eol_api($family, $database)
    {
        return false; //debug - remove in normal operation
        $canonical = "";
        $d_options = $this->download_options;
        $d_options['resource_id'] = "eol_api";
        $d_options['expire_seconds'] = false; //15552000; //6 months to expire

        if($json = Functions::lookup_with_cache($this->eol_api["search"] . $family, $d_options)) {
            $json = json_decode($json, true);
            if($json["results"]) {
                if($id = $json["results"][0]["id"]) {
                    if($database == "bolds") {
                        /* service (resources/partner_links) no longer exists in eol.org | commented Apr 24, 2024
                        if($html = Functions::lookup_with_cache("http://eol.org/pages/$id/resources/partner_links", $d_options)) {
                            if(preg_match("/boldsystems\.org\/index.php\/Taxbrowser_Taxonpage\?taxid=(.*?)\"/ims", $html, $arr)) {
                                echo "\n bolds id: " . $arr[1] . "\n";
                                if($json = Functions::lookup_with_cache($this->bolds["TaxonData"] . $arr[1], $this->download_options_BOLDS)) {
                                    if($arr = json_decode($json)) {
                                        $canonical = trim(@$arr->taxon);
                                        echo "\n Got from bolds.org: [" . $canonical . "]\n";
                                    }
                                }
                                elseif($html = Functions::lookup_with_cache($this->bolds_taxon_page_by_id . $arr[1], $this->download_options_BOLDS)) // original means, more or less it won't go here anymore
                                {
                                   // <h3>TAXONOMY BROWSER: Gadidae</h3>
                                    if(preg_match("/<h3>TAXONOMY BROWSER\: (.*?)<\/h3>/ims", $html, $arr)) {
                                        $canonical = trim($arr[1]);
                                        echo "\n Got from bolds.org 2: [" . $canonical . "]\n";
                                    }
                                }
                            }
                        }
                        */
                    }
                    elseif(in_array($database, array("ncbi", "gbif", "ggbn"))) { // ncbi, gbif, ggbn
                        $u = $this->eol_api["page"][0] . $id . $this->eol_api["page"][1];
                        // echo "\ninvestigate: [$u]\n";
                        if($json = Functions::lookup_with_cache($u, $d_options)) {
                            $json = json_decode($json, true);
                            if(@$json["taxonConcepts"]) {
                                foreach(@$json["taxonConcepts"] as $tc) {
                                    if(in_array($database, array("ncbi", "gbif"))) {
                                        if($this->databases_to_check_eol_api[$database] == $tc["nameAccordingTo"]) {
                                            if($family != $tc["canonicalForm"]) $canonical = $tc["canonicalForm"];
                                        }
                                    }
                                    elseif($database == "ggbn") {
                                        if(is_numeric(stripos($tc["nameAccordingTo"], $this->databases_to_check_eol_api[$database]))) $canonical = $tc["canonicalForm"];
                                    }
                                }    
                            }
                        }
                    }
                }
            }
        }
        // echo "\n [$database] taxonomy:[" . $this->databases_to_check_eol_api[$database] . "]\n";
        if($canonical) $canonical = ucfirst(strtolower($canonical));
        if($canonical && $canonical != $family) {
            echo "\n has diff name in eol api:[$canonical]\n";
            $this->families_with_no_data[$canonical] = '';
            self::save_to_dump($family . "\t" . $canonical . "\t" . $database, $this->name_from_eol_api_dump_file);
            return true;
        }
        // elseif($canonical == $family) echo "\n Result: Same name in FALO. \n";
        // else echo "\n Result: No name found in EOL API or Partner Links tab. \n";
        return false;
    }
    private function query_family_INAT_info($family, $is_subfamily, $database)
    {   // $family = "Muridae"; //force assign
        $rec[$this->process_level] = $family;
        $rec["taxon_id"] = str_replace(" ", "_", $family);
        $options = $this->download_options_INAT;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($this->inat['taxa_search'] . $family, $options)) {
            $taxon_id = self::parse_inat_taxa_search_object($family, $this->process_level, $json); //exit("\n[$taxon_id]\n");
            if($taxon_id) {
                
                // /* 1st ver - using the histogram
                $json = Functions::lookup_with_cache($this->inat['observation_search'] . $taxon_id, $this->download_options_INAT);
                $count = self::parse_inat_observ_search_object($json); //exit("\ncount: [$count]\n");
                // */

                /* 2nd ver --- designed for genus level but caused "Too Many Requests" error.
                $count = $this->func->get_total_observations($taxon_id); //from DataHub_INAT_API.php
                if($count === false) {
                    return false;
                }
                */

                if($count || strval($count) == "0") {
                    $rec["source"] = $this->inat['taxon_page'] . $taxon_id;
                    $rec["object_id"]   = "_no_of_inat_observ";
                    $rec["count"]       = $count;
                    $rec["label"]       = "Number of iNaturalist Observations";
                    $rec["measurement"] = "http://eol.org/schema/terms/NumberOfiNaturalistObservations";
                    self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                    return true;                    
                }
                else self::save_to_dump($family."\t".$database, $this->names_no_entry_from_partner_dump_file);
            }
        }
        else self::save_to_dump($family."\t".$database, $this->names_no_entry_from_partner_dump_file);
        return false;
    }
    public function parse_inat_taxa_search_object($sciname, $rank, $json)
    {   $obj = json_decode($json);
        foreach($obj->results as $r) {
            if($r->name == $sciname && strtolower($r->rank) == strtolower($rank)) return $r->id;
        }
    }
    public function parse_inat_observ_search_object($json)
    {   $obj = json_decode($json); //print_r($obj); //exit;
        $sum = 0;
        foreach($obj->results->month_of_year as $r) $sum += $r;
        return $sum;
    }
    public function get_gbif_taxon_record_count($usageKey)
    {
        @$this->gbif_calls++;
        $options = $this->download_options_GBIF;
        if($this->gbif_calls % 2 == 0) $options['expire_seconds'] = false; //print "It's even";
        else {} //it's odd no. 
        /* ---------- force assign, dev only debug only
        $options['expire_seconds'] = false;
        ---------- */
        $ret = Functions::lookup_with_cache($this->gbif_record_count . $usageKey, $options);
        if(($this->gbif_calls % 1000) == 0) echo "\n GBIF calls made: [$this->gbif_calls] | Latest: [$ret]\n";
        return $ret;
    }
    private function query_family_GBIF_info($family, $is_subfamily, $database)
    {
        $rec[$this->process_level] = $family;
        $rec["taxon_id"] = str_replace(" ", "_", $family);
        $rec["source"] = $this->gbif_taxon_info . $family;
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $family, $this->download_options_GBIF)) {
            $json = json_decode($json);
            $usageKey = false;
            if(!isset($json->usageKey)) {
                if(isset($json->note)) $usageKey = self::get_GBIF_usage_key($family);
                else {} // e.g. Fervidicoccaceae
            }
            else $usageKey = trim((string) $json->usageKey);
            if($usageKey) {
                $count = self::get_gbif_taxon_record_count($usageKey);
                if($count || strval($count) == "0") {
                    $rec["source"] = $this->gbif_record_count . $usageKey;
                    if($count > 0) {
                        $rec["object_id"]   = "_no_of_rec_in_gbif";
                        $rec["count"]       = $count;
                        $rec["label"]       = "Number records in GBIF";
                        $rec["measurement"] = "http://eol.org/schema/terms/NumberRecordsInGBIF";
                        self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                        /* removed boolean
                        $rec["object_id"]   = "_rec_in_gbif";
                        $rec["count"]       = "http://eol.org/schema/terms/yes";
                        $rec["label"]       = "Records in GBIF";
                        $rec["measurement"] = "http://eol.org/schema/terms/RecordInGBIF";
                        self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                        */
                        return true;
                    }
                }
                else self::save_to_dump($family."\t".$database, $this->names_no_entry_from_partner_dump_file);
            }
        }
        else self::save_to_dump($family."\t".$database, $this->names_no_entry_from_partner_dump_file);

        if(!$is_subfamily) {
            $rec["object_id"] = "_no_of_rec_in_gbif";
            self::add_string_types($rec, "Number records in GBIF", 0, "http://eol.org/schema/terms/NumberRecordsInGBIF", $family);
            /* removed boolean
            $rec["object_id"] = "_rec_in_gbif";
            self::add_string_types($rec, "Records in GBIF", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/RecordInGBIF", $family);
            */
            self::has_diff_family_name_in_eol_api($family, $database); //GBIF
        }
        self::check_for_sub_family($family); //GBIF
        return false;
    }
    private function get_GBIF_usage_key($family)
    {
        if($json = Functions::lookup_with_cache($this->gbif_taxon_info . $family . "&verbose=true", $this->download_options_GBIF)) {
            $usagekeys = array();
            $options = array();
            $json = json_decode($json);
            if(!isset($json->alternatives)) return false;
            foreach($json->alternatives as $rec) {
                if($rec->canonicalName == $family) {
                    $options[$rec->rank][] = $rec->usageKey;
                    $usagekeys[] = $rec->usageKey;
                }
            }
            if($options) {
                /* orig
                if(isset($options["FAMILY"])) return min($options["FAMILY"]);
                else return min($usagekeys);
                */
                if(isset($options[strtoupper($this->process_level)])) return min($options[strtoupper($this->process_level)]);
                else return min($usagekeys);                
            }
        }
        return false;
    }
    private function get_names_no_entry_from_partner()
    {
        $names = array();
        $dump_file = DOC_ROOT . "/public/tmp/gbif/names_no_entry_from_partner.txt";
        foreach(new FileIterator($dump_file) as $line_number => $line) {
            if($line) $names[$line] = "";
        }
        return array_keys($names);
    }
    private function save_to_dump($rec, $filename)
    {
        if(isset($rec["measurement"]) && is_array($rec)) {
            $fields = array($this->process_level, "count", "taxon_id", "object_id", "source", "label", "measurement");
            $data = "";
            foreach($fields as $field) $data .= $rec[$field] . "\t";
            if(!($WRITE = Functions::file_open($filename, "a"))) return;
            fwrite($WRITE, $data . "\n");
            fclose($WRITE);
        }
        else {
            if(!($WRITE = Functions::file_open($filename, "a"))) return;
            if($rec && is_array($rec)) fwrite($WRITE, json_encode($rec) . "\n");
            else                       fwrite($WRITE, $rec . "\n");
            fclose($WRITE);
        }
    }
    private function query_family_GGBN_info($family, $is_subfamily, $database)
    {
        $records = array();
        $rec[$this->process_level] = $family;
        $rec["source"] = $this->family_service_ggbn . $family;
        $rec["taxon_id"] = str_replace(" ", "_", $family);
        if($html = Functions::lookup_with_cache($rec["source"], $this->download_options)) {

            @$this->ggbn_calls++;
            if(($this->ggbn_calls % 1000) == 0) echo "\n GGBN calls made: [$this->ggbn_calls] | Latest: [". substr($html,0,200) ."]\n";
    
            $obj = json_decode($html);
            $has_data = false;
            if(@$obj->sampletype->DNA > 0) {
                $rec["object_id"]   = "NumberDNAInGGBN";
                $rec["count"]       = (string) $obj->sampletype->DNA;
                $rec["label"]       = "Number of DNA records in GGBN";
                $rec["measurement"] = "http://eol.org/schema/terms/NumberDNARecordsInGGBN";
                self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                $has_data = true;
            }
            if(!$has_data) {
                if(!$is_subfamily) {
                    $rec["object_id"] = "NumberDNAInGGBN";
                    self::add_string_types($rec, "Number of DNA records in GGBN", 0, "http://eol.org/schema/terms/NumberDNARecordsInGGBN", $family);
                }
            }

            if(@$obj->sampletype->specimen > 0) {
                $rec["object_id"] = "NumberSpecimensInGGBN";
                $rec["count"] = (string) $obj->sampletype->specimen;
                $rec["label"] = "NumberSpecimensInGGBN";
                $rec["measurement"] = "http://eol.org/schema/terms/NumberSpecimensInGGBN";
                self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                /* removed boolean
                $rec["object_id"] = "SpecimensInGGBN";
                $rec["count"] = "http://eol.org/schema/terms/yes";
                $rec["label"] = "SpecimensInGGBN";
                $rec["measurement"] = "http://eol.org/schema/terms/SpecimensInGGBN";
                self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                */
            }
            else {
                if(!$is_subfamily) {
                    $rec["object_id"] = "NumberSpecimensInGGBN";
                    self::add_string_types($rec, "NumberSpecimensInGGBN", 0, "http://eol.org/schema/terms/NumberSpecimensInGGBN", $family);
                    /* removed boolean
                    $rec["object_id"] = "SpecimensInGGBN";
                    self::add_string_types($rec, "SpecimensInGGBN", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/SpecimensInGGBN", $family);
                    */
                }
            }
            if(@$obj->sampletype->DNA || @$obj->sampletype->specimen) return true;
        }
        if(!$is_subfamily) {
            $rec["object_id"] = "NumberSpecimensInGGBN";
            self::add_string_types($rec, "NumberSpecimensInGGBN", 0, "http://eol.org/schema/terms/NumberSpecimensInGGBN", $family);
            /* removed boolean
            $rec["object_id"] = "SpecimensInGGBN";
            self::add_string_types($rec, "SpecimensInGGBN", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/SpecimensInGGBN", $family);
            */
            $rec["object_id"] = "NumberDNAInGGBN";
            self::add_string_types($rec, "Number of DNA records in GGBN", 0, "http://eol.org/schema/terms/NumberDNARecordsInGGBN", $family);
            self::has_diff_family_name_in_eol_api($family, $database); //GGBN
        }
        self::check_for_sub_family($family); //GGBN
        return false;
    }
    private function get_number_of_pages($html, $num)
    {
        if($num) return ceil($num/50);
        return 1;
    }
    private function process_html($html)
    {
        $temp = array();
        $html = str_ireplace("<tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'>", "<tr style='elix'>", $html);
        if(preg_match_all("/<tr style=\'elix\'>(.*?)<\/tr>/ims", $html, $arr)) {
            foreach($arr[1] as $r) {
                $r = strip_tags($r, "<td>");
                if(preg_match_all("/<td valign=\'top\'>(.*?)<\/td>/ims", $r, $arr2)) $temp[] = $arr2[1][2]; //get last coloumn (specimen no.)
            }
        }
        return array_unique($temp);
    }
    private function query_family_NCBI_info($family, $is_subfamily, $database)
    {
        $rec[$this->process_level] = $family;
        $rec["source"] = $this->family_service_ncbi . $family;
        $rec["taxon_id"] = str_replace(" ", "_", $family);
        $contents = Functions::lookup_with_cache($rec["source"], $this->download_options);

        @$this->ncbi_calls++;
        if(($this->ncbi_calls % 1000) == 0) echo "\n NCBI calls made: [$this->ncbi_calls] | Latest: [". substr($contents,0,200) ."]\n";

        if($xml = simplexml_load_string($contents)) {
            if($xml->Count > 0) {
                $rec["object_id"] = "_no_of_seq_in_genbank";
                $rec["count"]       = $xml->Count;
                $rec["label"]       = "Number Of Sequences In GenBank";
                $rec["measurement"] = "http://eol.org/schema/terms/NumberOfSequencesInGenBank";
                self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                /* removed boolean
                $rec["object_id"] = "SequenceInGenBank";
                $rec["count"] = "http://eol.org/schema/terms/yes";
                $rec["label"] = "SequenceInGenBank";
                $rec["measurement"] = "http://eol.org/schema/terms/SequenceInGenBank";
                self::save_to_dump($rec, $this->ggi_text_file[$database]["current"]);
                */
                return true;
            }
        }
        if(!$is_subfamily) {
            $rec["object_id"] = "_no_of_seq_in_genbank";
            self::add_string_types($rec, "Number Of Sequences In GenBank", 0, "http://eol.org/schema/terms/NumberOfSequencesInGenBank", $family);
            /* removed boolean
            $rec["object_id"] = "SequenceInGenBank";
            self::add_string_types($rec, "SequenceInGenBank", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/SequenceInGenBank", $family);
            */
            self::has_diff_family_name_in_eol_api($family, $database); //NCBI
        }
        self::check_for_sub_family($family); //NCBI
        return false;
    }
    private function add_string_types($rec, $label, $value, $measurementType, $family)
    {
        $taxon_id = (string) $rec["taxon_id"];
        $object_id = (string) $rec["object_id"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $object_id);
        $m->occurrenceID        = $occurrence_id;
        $m->measurementOfTaxon  = 'true';
        $m->source              = @$rec["source"];
        if($val = $measurementType) $m->measurementType = $val;
        else                        $m->measurementType = "http://ggbn.org/". SparqlClient::to_underscore($label);
        $m->measurementValue = (string) $value;

        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }
    private function add_occurrence($taxon_id, $object_id)
    {
        $occurrence_id = $taxon_id . 'O' . $object_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;

        /* old ways
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
        */
    }
    private function create_taxa_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        // $this->archive_builder->finalize(TRUE); //moved on top
    }
    private function get_families_with_missing_data_xlsx() // utility
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        $dropbox_xlsx[] = "http://localhost/cp/NCBIGGI/missing from GBIF.xlsx";
        foreach($dropbox_xlsx as $doc) {
            echo "\n processing [$doc]...\n";
            if($path = Functions::save_remote_file_to_local($doc, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2))) {
                $arr = $parser->convert_sheet_to_array($path);
                foreach($arr as $key => $fams) {
                    $fams[] = "Cenarchaeaceae";
                    foreach($fams as $family) {
                        if($family) $families[$family] = '';
                    }
                }
                unlink($path);
                break;
            }
            else echo "\n [$doc] unavailable! \n";
        }
        return array_keys($families);
    }
    /* obsolete
    private function get_families_from_google_spreadsheet()
    {
        $google_spreadsheets[] = array("title" => "FALO",                                            "column_number_to_return" => 16);
        $google_spreadsheets[] = array("title" => "falo_version 2.0.a.11_03-01-14 minus unassigned", "column_number_to_return" => 16);
        $google_spreadsheets[] = array("title" => "FALO_Version 2.0.a.1 minus unassigned",           "column_number_to_return" => 14);
        $sheet = array();
        foreach($google_spreadsheets as $doc) {
            echo "\n processing spreadsheet: " . $doc["title"] . "\n";
            if($sheet = Functions::get_google_spreadsheet(array("spreadsheet_title" => $doc["title"], "column_number_to_return" => $doc["column_number_to_return"], "timeout" => 999999))) {
                echo "\n successful process: " . $doc["title"] . "\n";
                break;
            }
            else echo "\n un-successful process: " . $doc["title"] . "\n";
        }
        if(!$sheet) return array();
        $families = array();
        foreach($sheet as $family) {
            $family = trim(str_ireplace(array("Family ", '"', "FAMILY"), "", $family));
            if(is_numeric($family)) continue;
            if($family) $families[$family] = '';
        }
        return array_keys($families);
    } */
    /* works but seems obsolete - commented Mar 29, 2018
    private function get_families()
    {
        $families = array();
        if(!$temp_path_filename = Functions::save_remote_file_to_local($this->families_list, $this->download_options)) return;
        foreach(new FileIterator($temp_path_filename) as $line_number => $line) {
            if($line) {
                $line = trim($line);
                $temp = explode("[", $line);
                $family = trim($temp[0]);
                $families[$family] = '';
            }
        }
        unlink($temp_path_filename);
        return array_keys($families);
    }
    */
    function falo_gbif_report()
    {
        require_library('connectors/IrmngAPI');
        $func = new IrmngAPI();
        $irmng_families = $func->get_irmng_families();
        $falo_families = self::get_families_xlsx();
        $names_in_falo_but_not_in_irmng = array_diff($falo_families, $irmng_families);
        $names_in_irmng_but_not_in_falo = array_diff($irmng_families, $falo_families);
        echo "\n falo_families:" . count($falo_families);
        echo "\n names_in_falo_but_not_in_irmng:" . count($names_in_falo_but_not_in_irmng);
        echo "\n irmng_families:" . count($irmng_families);
        echo "\n names_in_irmng_but_not_in_falo:" . count($names_in_irmng_but_not_in_falo);
        $names_in_falo_but_not_in_irmng = array_values($names_in_falo_but_not_in_irmng);
        $names_in_irmng_but_not_in_falo = array_values($names_in_irmng_but_not_in_falo);
        self::save_as_tab_delimited($names_in_falo_but_not_in_irmng, $this->names_in_falo_but_not_in_irmng);
        self::save_as_tab_delimited($names_in_irmng_but_not_in_falo, $this->names_in_irmng_but_not_in_falo);
        /*
            falo_families:9672
            names_in_falo_but_not_in_irmng:510
            irmng_families:19998
            names_in_irmng_but_not_in_falo:10836
        */
        // recursive_rmdir($this->TEMP_DIR);
    }
    private function save_as_tab_delimited($names, $file)
    {
        foreach($names as $name) self::save_to_dump($name, $file);
    }
    private function check_for_sub_family($family)
    {
        if(substr($family, -3) == "dae") {
            $orig = $family;
            $family = str_replace("dae" . "xxx", "nae", $family . "xxx");
            $this->families_with_no_data[$family] = '';
            self::save_to_dump($orig . "\t" . $family, $this->names_dae_to_nae_dump_file);
        }
        /* commented for now bec it is not improving the no. of records
        elseif(substr($family, -4) == "ceae")
        {
            $family = str_replace("ceae" . "xxx", "deae", $family . "xxx");
            $this->families_with_no_data[$family] = '';
        }*/
    }
    private function get_families_from_JonCoddington()
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();
        $excel = "http://localhost/cp/GGI/FamNamesForEli.xlsx";
        echo "\n processing [$excel]...\n";
        if($path = Functions::save_remote_file_to_local($excel, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2, 'cache' => 1))) {
            $arr = $parser->convert_sheet_to_array($path);
            foreach($arr['Name'] as $family) {
                if($family) $families[$family] = '';
            }
            unlink($path);
        }
        else echo "\n [$excel] unavailable! \n";
        return array_keys($families);
    }
    private function get_families_xlsx()
    {   require_library('XLSParser');
        $parser = new XLSParser();
        $families = array();

        // for family table
        $family_table = array();
        $fields = array("SpK", "K", "SbK", "IK", "SpP", "P", "SbP", "IP", "PvP", "SpC", "C", "SbC", "IC", "SpO", "O");

        // $dropbox_xlsx[] = "http://tiny.cc/FALO"; // from Cyndy's Dropbox
        // $dropbox_xlsx[] = "http://localhost/cp/NCBIGGI/FALO.xlsx"; // local
        // $dropbox_xlsx[] = "http://localhost/cp/NCBIGGI/ALF2015.xlsx"; // local
        $dropbox_xlsx[] = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/NCBIGGI/ALF2015.xlsx"; //used in normal operation

        foreach($dropbox_xlsx as $doc) {
            echo "\n processing [$doc]...\n";
            if($path = Functions::save_remote_file_to_local($doc, array("timeout" => 3600, "file_extension" => "xlsx", 'download_attempts' => 2, 'delay_in_minutes' => 2, 'cache' => 1))) {
                $arr = $parser->convert_sheet_to_array($path);
                $i = 0;
                foreach($arr["FAMILY"] as $family) {
                    $family = trim(str_ireplace(array("Family", '"'), "", $family));
                    if(is_numeric($family)) continue;
                    if($family) {
                        $families[$family] = '';
                        foreach($fields as $field) $family_table[$family][$field] = $arr[$field][$i]; // for family table
                    }
                    $i++;
                }
                unlink($path);
                break;
            }
            else echo "\n [$doc] unavailable! \n";
        }

        //save $family_table as json to text file, to be accessed later when generating the spreadsheet
        self::initialize_dump_file($this->temp_family_table_file);
        self::save_to_dump($family_table, $this->temp_family_table_file);
        echo "\n count family rows: ". count($family_table) . "\n"; unset($family_table);

        return array_keys($families);
    }
    function count_subfamily_per_database($file, $database)
    {
        $subfamilies = array();
        foreach(new FileIterator($file) as $line_number => $line) {
            if($line) {
                $line = trim($line);
                $temp = explode("\t", $line);
                $str = explode("naeO", $temp[0]);
                if(count($str) > 1) $subfamilies[$str[0] . "nae"] = '';
            }
        }
        print "\n $database: " . count($subfamilies) . "\n";
    }
    private function generate_spreadsheet($resource_id)
    {
        /*[Xenoturbellidae] => Array
             [SpK] => Superkingdom Eukaryota
             [K] => Kingdom Animalia
             [SbK] => Subkingdom Bilateria
             [IK] => Infrakingdom Deuterostomia
             [SpP] =>
             [P] => Phylum Xenacoelomorpha
             [SbP] => Subphylum Xenoturbellida
             [IP] =>
             [PvP] =>
             [SpC] =>
             [C] =>
             [SbC] =>
             [IC] =>
             [SpO] =>
             [O] => */
        $family_counts = self::convert_measurement_or_fact_to_array($resource_id);
        $xls = self::access_dump_file($this->temp_family_table_file); // this will access the array, that is the main spreadsheet source for this connector
        $uris = array("http://eol.org/schema/terms/NumberReferencesInBHL",          "http://eol.org/schema/terms/NumberPublicRecordsInBOLD",
                      "http://eol.org/schema/terms/NumberRichSpeciesPagesInEOL",    "http://eol.org/schema/terms/NumberRecordsInGBIF",
                      "http://eol.org/schema/terms/NumberOfSequencesInGenBank",     "http://eol.org/schema/terms/NumberSpecimensInGGBN");
        foreach($xls as $family => $rec) {
            if($fam_rec = @$family_counts[$family]) {
                if(self::family_has_totals($fam_rec, $uris)) {
                    echo "\n $family";
                    print_r($family_counts[$family]);
                }
            }
        }
        // if(file_exists($this->temp_family_table_file)) unlink($this->temp_family_table_file);
    }
    private function family_has_totals($fam_rec, $uris)
    {
        foreach($uris as $uri) {
            if(@$fam_rec[$uri] > 0) return true;
        }
        return false;
    }
    private function convert_measurement_or_fact_to_array($resource_id)
    {
        $fields = array("http://eol.org/schema/terms/NumberReferencesInBHL", "http://eol.org/schema/terms/NumberPublicRecordsInBOLD",
                        "http://eol.org/schema/terms/NumberRichSpeciesPagesInEOL", "http://eol.org/schema/terms/NumberRecordsInGBIF",
                        "http://eol.org/schema/terms/NumberOfSequencesInGenBank", "http://eol.org/schema/terms/NumberSpecimensInGGBN");
        $file = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "/measurement_or_fact.tab";
        $records = array();
        foreach(new FileIterator($file) as $line_number => $line) {
            $rec = array();
            /*  [0] => CharaciosiphonaceaeO_no_of_public_rec_in_bolds
                [1] => true
                [2] => http://eol.org/schema/terms/NumberPublicRecordsInBOLD
                [3] => 2
                [4] => http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=414014 */
            if($line) {
                $line = trim($line);
                $row = explode("\t", $line);
                if(in_array($row[2], $fields)) {
                    $temp = explode("O_", $row[0]);
                    $sciname = $temp[0];
                    $records[$sciname][$row[2]] = $row[3];
                }
            }
        }
        return $records;
    }
    private function access_dump_file($file_path, $is_array = true)
    {
        if(!($file = Functions::file_open($file_path, "r"))) return;
        if($is_array) $contents = json_decode(fread($file,filesize($file_path)), true);
        else          $contents = fread($file,filesize($file_path));
        fclose($file);
        return $contents;
    }
    private function get_DH_taxa_per_rank($sought_rank)
    {   if(Functions::is_production()) $file = "/extra/other_files/DWH/dh21eolid/taxon.tab";
        else                           $file = "/Volumes/Crucial_2TB/other_files2/dh21eolid/DH21taxaWeolIDs.txt";
        echo "\nReading DH file $file...\n";
        $i = 0; $final = array();
        foreach(new FileIterator($file) as $line => $row) { $i++; // $row = Functions::conv_to_utf8($row);
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = $tmp[$k]; $k++; }
                $rec = array_map('trim', $rec); //print_r($rec); exit("\nstop muna\n");
                /*Array
                    (
                        [taxonID] => EOL-000000000001
                        [source] => trunk:4038af35-41da-469e-8806-40e60241bb58
                        [furtherInformationURL] => 
                        [acceptedNameUsageID] => 
                        [parentNameUsageID] => 
                        [scientificName] => Life
                        [taxonRank] => 
                        [taxonomicStatus] => accepted
                        [datasetID] => trunk
                        [canonicalName] => Life
                        [eolID] => 2913056
                        [Landmark] => 3
                        [higherClassification] => 
                )
                */
                $status['taxonomicStatus'][$rec['taxonomicStatus']] = ''; //for stats only
                $status['datasetID'][$rec['datasetID']] = ''; //for stats only

                if($rec['taxonRank'] == $sought_rank && $rec['taxonomicStatus'] == "accepted") $final[$rec['canonicalName']] = '';
            }
        } //end foreach()
        // print_r($status); exit;
        // print_r($final); exit;
        return array_keys($final);
    }
}
?>