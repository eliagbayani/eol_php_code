<?php
namespace php_active_record;
/* connector: 1st client: [gbif_download_request.php]
              2nd client: [gbif_download_request_for_NMNH.php]
              3rd client: the 6 GBIF country type records -> e.g. Germany, Sweden, etc.
              4th client: [gbif_download_request_for_iNat.php] 
              5th client: [gbif_download_request_for_DataCoverage.php]

THERE IS A CURL ISSUE: and the "--insecure" param as a sol'n actually works OK!
curl: (60) SSL certificate problem: certificate has expired
More details here: https://curl.haxx.se/docs/sslcerts.html

curl performs SSL certificate verification by default, using a "bundle"
of Certificate Authority (CA) public keys (CA certs). If the default
bundle file isn't adequate, you can specify an alternate file
using the --cacert option.
If this HTTPS server uses a certificate signed by a CA represented in
the bundle, the certificate verification probably failed due to a
problem with the certificate (it might be expired, or the name might
not match the domain name in the URL).
If you'd like to turn off curl's verification of the certificate, use
the -k (or --insecure) option.

                                               https://api.gbif.org/v1/occurrence/download/request/0389316-210914110416597.zip
curl --insecure -LsS -o 'NMNH_images_DwCA.zip' https://api.gbif.org/v1/occurrence/download/request/0389316-210914110416597.zip
*/
class GBIFdownloadRequestAPI
{
    function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        
        // /* for resource_id equals 'GBIF_map_harvest'
        $this->taxon['Gadus ogac'] = 2415827;
        $this->taxon['Animalia'] = 1;
        $this->taxon['Plantae'] = 6;
        $this->taxon['Fungi'] = 5;
        $this->taxon['Chromista'] = 4;
        $this->taxon['Bacteria'] = 3;
        $this->taxon['Protozoa'] = 7;
        $this->taxon['incertae sedis'] = 0;
        $this->taxon['Archaea'] = 2;
        $this->taxon['Viruses'] = 8;
        // */
        
        if($this->resource_id == 'GBIF_map_harvest') $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF';
        elseif($this->resource_id == 'NMNH_images')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/NMNH_images';
        elseif($this->resource_id == 'Country_checklists')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/Country_checklists';
        elseif($this->resource_id == 'WaterBody_checklists')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/WaterBody_checklists';
        elseif($this->resource_id == 'Continent_checklists')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/Continent_checklists';
        

        // Further division:
        elseif($this->resource_id == 'map_animalia_phylum_Chordata')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_animalia_phylum_Chordata';
        // Animalia map data: n = 8
        elseif($this->resource_id == 'map_animalia_phylum_Arthropoda')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_animalia_phylum_Arthropoda';        
        elseif($this->resource_id == 'map_animalia_not_phylum_Arthropoda_Chordata')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_animalia_not_phylum_Arthropoda_Chordata';
        elseif($this->resource_id == 'map_phylum_Chordata_not_class_Aves')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_phylum_Chordata_not_class_Aves';        
        elseif($this->resource_id == 'map_class_Aves_not_order_Passeriformes')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_not_order_Passeriformes';        
        elseif($this->resource_id == 'map_order_Passeriformes_with_3_families')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_order_Passeriformes_with_3_families';        
        elseif($this->resource_id == 'map_order_Passeriformes_with_4_families')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_order_Passeriformes_with_4_families';                
        elseif($this->resource_id == 'map_order_Passeriformes_with_6_families')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_order_Passeriformes_with_6_families';        
        elseif($this->resource_id == 'map_order_Passeriformes_but_not_13_families')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_order_Passeriformes_but_not_13_families';
        elseif($this->resource_id == 'map_class_Aves_but_not_6_orders')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_but_not_6_orders';        
        elseif($this->resource_id == 'map_class_Aves_order_Charadriiformes')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_order_Charadriiformes';
        elseif($this->resource_id == 'map_class_Aves_order_Accipitriformes')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_order_Accipitriformes';
        elseif($this->resource_id == 'map_class_Aves_with_4_orders')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_with_4_orders';
        elseif($this->resource_id == 'map_class_Aves_with_5_orders')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_with_5_orders';
        elseif($this->resource_id == 'map_class_Aves_with_7_orders')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_with_7_orders';
        elseif($this->resource_id == 'map_class_Aves_but_not_17_orders')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_but_not_17_orders';
        elseif($this->resource_id == 'map_class_Aves_with_6_orders')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_with_6_orders';
        elseif($this->resource_id == 'map_class_Aves_with_18_orders')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_with_18_orders';
        elseif($this->resource_id == 'map_class_Aves_but_not_all_orders')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_class_Aves_but_not_all_orders';

        // Plantae map data: n = 4
        elseif($this->resource_id == 'map_plantae_not_phylum_Tracheophyta')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_plantae_not_phylum_Tracheophyta';        
        elseif($this->resource_id == 'map_phylum_Tracheophyta_class_Magnoliopsida_orders_3')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_phylum_Tracheophyta_class_Magnoliopsida_orders_3';
        elseif($this->resource_id == 'map_phylum_Tracheophyta_class_Magnoliopsida_not_orders_3')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_phylum_Tracheophyta_class_Magnoliopsida_not_orders_3';
        elseif($this->resource_id == 'map_phylum_Tracheophyta_not_class_Magnoliopsida')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_phylum_Tracheophyta_not_class_Magnoliopsida';
        // Not Animalia nor Plantae        
        elseif($this->resource_id == 'map_kingdom_not_animalia_nor_plantae')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_kingdom_not_animalia_nor_plantae';

        // for testing
        elseif($this->resource_id == 'map_Gadiformes')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/map_Gadiformes';        



        elseif($this->resource_id == 'iNat_images')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/iNat_images';
        elseif($this->resource_id == 'Data_coverage')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/Data_coverage';
        elseif($this->resource_id == 'GBIF_Netherlands')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_Netherlands';
        elseif($this->resource_id == 'GBIF_France')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_France';
        elseif($this->resource_id == 'GBIF_Germany')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_Germany';
        elseif($this->resource_id == 'GBIF_Brazil')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_Brazil';
        elseif($this->resource_id == 'GBIF_Sweden')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_Sweden';
        elseif($this->resource_id == 'GBIF_UnitedKingdom')  $this->destination_path = DOC_ROOT.'update_resources/connectors/files/GBIF_UnitedKingdom';
        else {
            echo("\nresource_id not yet initialized\n");
            exit(1); //jenkins fail
        }
        if(!is_dir($this->destination_path)) mkdir($this->destination_path);
        
        $this->abbreviation['GBIF_Netherlands'] = "NL";
        $this->abbreviation['GBIF_France'] = "FR";
        $this->abbreviation['GBIF_Germany'] = "DE";
        $this->abbreviation['GBIF_Brazil'] = "BR";
        $this->abbreviation['GBIF_Sweden'] = "SE";
        $this->abbreviation['GBIF_UnitedKingdom'] = "GB";   //United Kingdom of Great Britain and Northern Ireland
    }
    private function initialize($taxon_group)
    {
        if(in_array($this->resource_id, array('Country_checklists', 'WaterBody_checklists', 'Continent_checklists'))) {
            require_library('connectors/GBIFTaxonomyAPI');
            $this->GBIFTaxonomy = new GBIFTaxonomyAPI('GBIF_checklists');
            print_r($this->GBIFTaxonomy->dataset_filters); //exit("\nxxx\n"); //from GBIFTaxonomyAPI.php country_filters() func
            /*Array( as of Jan 23, 2025
                [0] => ebd01d3e-5e9a-4e80-8ae2-1dfe9a032bf7
                [1] => 6276fa08-f762-11e1-a439-00145eb45e9a
                [2] => c8fb4ced-0374-46f7-8c03-5eb5a6b70640
                [3] => 12464931-e8ea-4437-b6df-c280e063b107
                [4] => b7ef1d60-b0a0-11dd-aa14-b8a03c50a862
                [5] => 10fe7809-00b8-45ed-b743-963520ea7680
                [6] => 5bc157a5-d52f-4be1-918b-c2950c5e742c
                [7] => 00cbf1a4-5437-4902-84c8-17643eac3c8c
                [8] => 0360c673-20fc-420a-845a-05d20f185dcf
                [9] => 2ddded16-5565-45ee-8aa1-1b8118fa361f
            )*/
            // e.g. " LastEditedBy NOT IN (11,17,13) "
            $str = "";
            foreach($this->GBIFTaxonomy->dataset_filters as $key) $str .= " '$key', ";
            $str = substr(trim($str), 0, -1); //remove last char "," a comma
            $str = "AND datasetkey NOT IN ($str)";
            // exit("\n[$str]\n");
            $this->datasetKey_filters = $str;
        }
    }
    function send_download_request($taxon_group) //this will overwrite any current download request. Run this once ONLY every harvest per taxon group.
    {
        // /* new
        self::initialize($taxon_group);
        // */

        $json = self::generate_json_request($taxon_group);
        self::save_json_2file($json);
        $arr = json_decode($json, true); // print_r($arr);
        /* orig per https://www.gbif.org/developer/occurrence#download
        curl --include --user userName:PASSWORD --header "Content-Type: application/json" --data @query.json https://api.gbif.org/v1/occurrence/download/request
        */
        $filename = $this->destination_path.'/query.json';
        $cmd = 'curl --insecure --include --user '.GBIF_USERNAME.':'.GBIF_PW.' --header "Content-Type: application/json" --data @'.$filename.' -s https://api.gbif.org/v1/occurrence/download/request';
        echo "\ncmd:\n[$cmd]\n";
        $output = shell_exec($cmd);
        echo "\nRequest output:\n[$output]\n";
        $lines = explode("\n", trim($output));
        if($key = trim(array_pop($lines))) { //get last line
            echo "\nDownload Key:[$key]\n";
            self::save_key_per_taxon_group($taxon_group, $key);
            return $key;
        }
        exit("\nCannot generate download key. Investigate [$taxon_group].\n");
    }
    function generate_sh_file($taxon_group)
    {
        if($key = self::retrieve_key_for_taxon($taxon_group)) {
            echo "\nDownload key for [$taxon_group]: [$key]\n";
            if($arr = self::can_proceed_to_download_YN($key)) {
                echo "\nCan proceed to download [$taxon_group].\n";
                self::create_bash_file($taxon_group, $arr['downloadLink']);
                return true;
            }
            else echo "\nCannot download yet [$taxon_group]. Download not yet ready.\n";
        }
        return false;
    }
    function check_if_all_downloads_are_ready_YN()
    {
        if($this->resource_id == 'GBIF_map_harvest') {
            $groups = array('Animalia', 'Plantae', 'Other7Groups');
            foreach($groups as $taxon_group) {
                if(!self::generate_sh_file($taxon_group)) {
                    echo "\n[$taxon_group] NOT yet ready :-( \n";
                    return false;
                }
                else echo "\n[$taxon_group] now ready OK :-) \n";
            }
        }
        /* moved this below, together with the 6 GBIF countries
        elseif($this->resource_id == 'NMNH_images') {
            $taxon_group = 'NMNH_images';
            if(!self::generate_sh_file($taxon_group)) return false;
        }
        */
        else { //for NMNH_images and the 6 GBIF countries and iNat_images and Data_coverage and Country_checklists WaterBody_checklists Continent_checklists
            $taxon_group = $this->resource_id;
            if(!self::generate_sh_file($taxon_group)) return false;
        }
        return true;
    }
    private function can_proceed_to_download_YN($key)
    {   /* orig
        curl -Ss https://api.gbif.org/v1/occurrence/download/0000022-170829143010713 | jq .
        e.g. Gadus ogac
        curl -Ss https://api.gbif.org/v1/occurrence/download/0018153-200613084148143 | jq .
        */
        /* original entry but it seems jq is not needed or it is not essential. It just gives a pretty-print json output.
        And since it does not work in our Rhel Linux eol-archive, I just removed it.
        $cmd = 'curl -Ss https://api.gbif.org/v1/occurrence/download/'.$key.' | jq .';
        */
        $cmd = 'curl --insecure -Ss https://api.gbif.org/v1/occurrence/download/'.$key;
        
        $output = shell_exec($cmd);
        echo "\nRequest output:\n[$output]\n"; //good debug
        $arr = json_decode($output, true);
        // print_r($arr); exit;
        if($arr['status'] == 'SUCCEEDED') return $arr;
        else return false;
    }
    private function generate_json_request($taxon_group)
    {
        if($this->resource_id == 'GBIF_map_harvest') { //=====================================================================
            $taxon = $this->taxon;
            
            if($taxon_group == 'Other7Groups')  $taxon_array = Array("type" => "in", "key" => "TAXON_KEY", "values" => Array(0 => $taxon['Fungi'],
                1 => $taxon['Chromista'], 2 => $taxon['Bacteria'], 3 => $taxon['Protozoa'], 
                4 => $taxon['Archaea'], 5 => $taxon['Viruses']));
                /* as of Oct 5, 2023, removed 'incertae sedis'. It was included from the beginning though.
                4 => $taxon['incertae sedis']
                */

            else $taxon_array = Array("type" => "equals", "key" => "TAXON_KEY", "value" => $taxon[$taxon_group]);
            
            $param = Array( 'creator' => GBIF_USERNAME,
                            'notificationAddresses' => Array(0 => GBIF_EMAIL),
                            'sendNotification' => 1,
                            'format' => 'DWCA',
                            'predicate' => Array(
                                                    'type' => 'and',
                                                    'predicates' => Array(
                                                                            0 => Array(
                                                                                    'type' => 'equals',
                                                                                    'key' => 'HAS_COORDINATE',
                                                                                    'value' => 'true'
                                                                                 ),
                                                                            1 => Array(
                                                                                    'type' => 'equals',
                                                                                    'key' => 'HAS_GEOSPATIAL_ISSUE',
                                                                                    'value' => 'false'
                                                                                 ),
                                                                            2 => $taxon_array
                                                                        )
                                                )
                     );
            return json_encode($param);
        } //end GBIF_map_harvest

        /*Filter used:
        {
          "and" : [
            "HasCoordinate is true",
            "HasGeospatialIssue is false",
            "TaxonKey is Animalia"
          ]
        }*/

        $predicate = array();
        //==================================================================================================================================
        if($this->resource_id == 'NMNH_images') {
            $predicate = Array(
                'type' => 'and',
                'predicates' => Array(
                                        0 => Array(
                                                'type' => 'equals',
                                                'key' => 'DATASET_KEY',
                                                'value' => '821cc27a-e3bb-4bc5-ac34-89ada245069d',
                                                'matchCase' => ''
                                             ),
                                        1 => Array(
                                                'type' => 'or',
                                                'predicates' => Array(
                                                                    0 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'MEDIA_TYPE',
                                                                            'value' => 'StillImage',
                                                                            'matchCase' => ''
                                                                        ),
                                                                    1 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'MEDIA_TYPE',
                                                                            'value' => 'MovingImage',
                                                                            'matchCase' => ''
                                                                        ),
                                                                    2 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'MEDIA_TYPE',
                                                                            'value' => 'Sound',
                                                                            'matchCase' => ''
                                                                        )
                                                                )
                                             ),
                                        2 => Array(
                                                'type' => 'equals',
                                                'key' => 'OCCURRENCE_STATUS',
                                                'value' => 'present',
                                                'matchCase' => ''
                                            )
                )
            );
        } //end NMNH_images
        
        /* from its download DOI: https://doi.org/10.15468/dl.b5vdyg
        From the 2nd box. Click 'API' to get the json format of the request. Then in php run below, to get the array value.
        $arr = json_decode($json, true);
        */
        //==================================================================================================================================
        
        //==================================================================================================================================
        $gbif_countries = array("GBIF_Netherlands", "GBIF_France", "GBIF_Germany", "GBIF_Brazil", "GBIF_Sweden", "GBIF_UnitedKingdom");
        if(in_array($this->resource_id, $gbif_countries)) {
            $predicate = Array(
                            'type' => 'and',
                            'predicates' => Array(
                                                0 => Array(
                                                        'type' => 'isNotNull',
                                                        'parameter' => 'TYPE_STATUS'
                                                     ),
                                                1 => Array(
                                                        'type' => 'equals',
                                                        'key' => 'PUBLISHING_COUNTRY',
                                                        'value' => $this->abbreviation[$this->resource_id],
                                                        'matchCase' => ''
                                                     )
                                            )
                         );
        } //end GBIF countries
        //==================================================================================================================================
        if($this->resource_id == 'iNat_images') {
            $predicate = Array(
                'type' => 'and',
                'predicates' => Array(
                                        0 => Array(
                                                'type' => 'equals',
                                                'key' => 'DATASET_KEY',
                                                'value' => '50c9509d-22c7-4a22-a47d-8c48425ef4a7',
                                                'matchCase' => ''
                                             ),
                                        1 => Array(
                                                'type' => 'or',
                                                'predicates' => Array(
                                                                    0 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'LICENSE',
                                                                            'value' => 'CC_BY_NC_4_0',
                                                                            'matchCase' => ''
                                                                        ),
                                                                    1 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'LICENSE',
                                                                            'value' => 'CC_BY_4_0',
                                                                            'matchCase' => ''
                                                                        ),
                                                                    2 => Array(
                                                                            'type' => 'equals',
                                                                            'key' => 'LICENSE',
                                                                            'value' => 'CC0_1_0',
                                                                            'matchCase' => ''
                                                                        )
                                                                )
                                             ),
                                        2 => Array(
                                                'type' => 'equals',
                                                'key' => 'MEDIA_TYPE',
                                                'value' => 'StillImage',
                                                'matchCase' => ''
                                            )
                )
            );
        } //end iNat
        /* from download page: https://doi.org/10.15468/dl.xr247r (API) */
        //==================================================================================================================================
        if($this->resource_id == 'Data_coverage') {
            $predicate = Array(
                'type' => 'equals',
                'key' => 'OCCURRENCE_STATUS',
                'value' => 'present',
                'matchCase' => ''
            );
        } //end Data_coverage
        /* from its download DOI: https://doi.org/10.15468/dl.y5bevt
        From the 2nd box. Click 'API' to get the json format of the request. Then in php run below, to get the array value.
        $arr = json_decode($json, true);
        */
        //==================================================================================================================================

        /* For all except $this->resource_id == 'GBIF_map_harvest' */
        
        if($this->resource_id == 'Data_coverage')               $format = 'SPECIES_LIST'; //'SPECIESLIST';
        elseif($this->resource_id == 'Country_checklists')      $format = 'SQL_TSV_ZIP';
        
    
        elseif(in_array($this->resource_id, array('map_animalia_phylum_Chordata', 'map_animalia_phylum_Arthropoda', 'map_animalia_not_phylum_Arthropoda_Chordata', 
            'map_phylum_Chordata_not_class_Aves', 'map_class_Aves_not_order_Passeriformes', 'map_order_Passeriformes_with_3_families', 
            'map_order_Passeriformes_with_4_families', 'map_order_Passeriformes_but_not_13_families', 'map_order_Passeriformes_with_6_families', 
            'map_plantae_not_phylum_Tracheophyta', 'map_phylum_Tracheophyta_class_Magnoliopsida_orders_3', 
            'map_class_Aves_but_not_6_orders', 'map_class_Aves_order_Charadriiformes', 'map_class_Aves_order_Accipitriformes', 'map_class_Aves_with_4_orders', 
            'map_class_Aves_with_5_orders', 'map_class_Aves_with_7_orders', 'map_class_Aves_but_not_17_orders', 'map_class_Aves_with_6_orders', 'map_class_Aves_with_18_orders', 
            'map_class_Aves_but_not_all_orders', 
            'map_phylum_Tracheophyta_class_Magnoliopsida_not_orders_3', 'map_phylum_Tracheophyta_not_class_Magnoliopsida', 'map_kingdom_not_animalia_nor_plantae', 'map_Gadiformes'
            ))) $format = 'SQL_TSV_ZIP';
        

        elseif($this->resource_id == 'WaterBody_checklists')    $format = 'SQL_TSV_ZIP';
        elseif($this->resource_id == 'Continent_checklists')    $format = 'SQL_TSV_ZIP';
        else                                                    $format = 'DWCA';

        $param = Array( 'creator' => GBIF_USERNAME,
                        'notificationAddresses' => Array(0 => GBIF_EMAIL),
                        'sendNotification' => 1,
                        'format' => $format,
                        'predicate' => $predicate);

        if($this->resource_id == 'Country_checklists') {
            unset($param['predicate']);
            $param['sql'] = "SELECT specieskey, COUNT(specieskey), countrycode
            FROM occurrence
            WHERE
            specieskey IS NOT NULL
            AND countrycode IS NOT NULL
            -- AND countrycode = 'CA'
            AND occurrencestatus = 'PRESENT'
            AND (
                basisofrecord = 'HUMAN_OBSERVATION'
                OR basisofrecord = 'MACHINE_OBSERVATION'
                OR basisofrecord = 'OCCURRENCE'
                OR basisofrecord = 'LIVING_SPECIMEN'
                OR basisofrecord = 'MATERIAL_SAMPLE'
            )
            AND NOT ARRAY_CONTAINS(issue, 'ZERO_COORDINATE')
            AND NOT ARRAY_CONTAINS(issue, 'COORDINATE_OUT_OF_RANGE')
            AND NOT ARRAY_CONTAINS(issue, 'COUNTRY_COORDINATE_MISMATCH') " .$this->datasetKey_filters. " 
            GROUP BY specieskey, countrycode";
        }
        elseif(in_array($this->resource_id, array( 'map_animalia_phylum_Chordata', 
                    'map_animalia_phylum_Arthropoda', 'map_animalia_not_phylum_Arthropoda_Chordata', 'map_phylum_Chordata_not_class_Aves', 'map_class_Aves_not_order_Passeriformes', 
                    'map_order_Passeriformes_with_3_families', 'map_order_Passeriformes_with_4_families', 'map_order_Passeriformes_with_6_families',
                    'map_order_Passeriformes_but_not_13_families', 'map_plantae_not_phylum_Tracheophyta', 'map_phylum_Tracheophyta_class_Magnoliopsida_orders_3', 
                    'map_class_Aves_but_not_6_orders', 'map_class_Aves_order_Charadriiformes', 'map_class_Aves_order_Accipitriformes', 'map_class_Aves_with_4_orders', 
                    'map_class_Aves_with_5_orders', 'map_class_Aves_with_7_orders', 'map_class_Aves_but_not_17_orders', 'map_class_Aves_with_6_orders', 'map_class_Aves_with_18_orders', 
                    'map_class_Aves_but_not_all_orders', 
                    'map_phylum_Tracheophyta_class_Magnoliopsida_not_orders_3', 'map_phylum_Tracheophyta_not_class_Magnoliopsida', 'map_kingdom_not_animalia_nor_plantae', 'map_Gadiformes'))) {
            unset($param['predicate']);
            // /* Eli initiated dataset filters: BOLD: e.g. scientificname = "BOLD:AAB3717"
            $str = "";
            foreach(array('040c5662-da76-4782-a48e-cdea1892d14c') as $key) $str .= " '$key', ";
            $str = substr(trim($str), 0, -1); //remove last char "," a comma
            $str = "AND datasetkey NOT IN ($str)";
            $datasetKey_filters = $str;
            // */
            // -----------------------------
            if($this->resource_id == 'map_kingdom_not_animalia_nor_plantae') $sql_part = " kingdomkey IN (2,3,4,5,7,8,0) ";
            elseif($this->resource_id == 'map_Gadiformes') $sql_part = " orderkey = 549 ";
            // -----------------------------
            elseif($this->resource_id == 'map_animalia_phylum_Chordata') $sql_part = " phylumkey = 44 ";
            // -----------------------------
            elseif($this->resource_id == 'map_animalia_phylum_Arthropoda') $sql_part = " phylumkey = 54 ";
            elseif($this->resource_id == 'map_animalia_not_phylum_Arthropoda_Chordata') $sql_part = " kingdomkey = 1 AND phylumkey NOT IN (54, 44) ";
            elseif($this->resource_id == 'map_phylum_Chordata_not_class_Aves') $sql_part = " phylumkey = 44 AND classkey <> 212 ";
            elseif($this->resource_id == 'map_class_Aves_not_order_Passeriformes') $sql_part = " classkey = 212 AND orderkey <> 729 ";
            elseif($this->resource_id == 'map_order_Passeriformes_with_3_families') $sql_part = " orderkey = 729 AND (familykey = 5235 OR familykey = 9410667 OR familykey = 5291) ";            
            elseif($this->resource_id == 'map_order_Passeriformes_with_4_families') $sql_part = " orderkey = 729 AND (familykey = 5242 OR familykey = 5263 OR familykey = 5290 OR familykey = 5257) ";            
            elseif($this->resource_id == 'map_order_Passeriformes_with_6_families') $sql_part = " orderkey = 729 AND (familykey = 9327 OR familykey = 6176 OR familykey = 9285 OR familykey = 9355 OR familykey = 9350 OR familykey = 5264) ";
            elseif($this->resource_id == 'map_order_Passeriformes_but_not_13_families') $sql_part = " orderkey = 729 AND familykey NOT IN (5235, 9410667, 5291, 5242, 5263, 5290, 5257, 9327, 6176, 9285, 9355, 9350, 5264) ";
            elseif($this->resource_id == 'map_class_Aves_but_not_6_orders') $sql_part = " classkey = 212 AND orderkey NOT IN (7191147, 1108, 1448, 7192402, 1446, 724) ";
            elseif($this->resource_id == 'map_class_Aves_order_Charadriiformes') $sql_part = " classkey = 212 AND orderkey = 7192402 ";
            elseif($this->resource_id == 'map_class_Aves_order_Accipitriformes') $sql_part = " classkey = 212 AND orderkey = 7191147 ";
            elseif($this->resource_id == 'map_class_Aves_with_4_orders') $sql_part = " classkey = 212 AND orderkey IN (1108, 1448, 724) "; //originally included: 1446
            elseif($this->resource_id == 'map_class_Aves_with_5_orders') $sql_part = " classkey = 212 AND orderkey IN (1446, 1447, 839, 723, 1493) ";
            elseif($this->resource_id == 'map_class_Aves_with_7_orders') $sql_part = " classkey = 212 AND orderkey IN (1492, 7191407, 7190953, 7192755, 7192754, 7191588, 7192775) ";            
            elseif($this->resource_id == 'map_class_Aves_but_not_17_orders') $sql_part = " classkey = 212 AND orderkey NOT IN (7192402, 7191147, 1108, 1448, 724, 1446, 1447, 839, 723, 1493, 1492, 7191407, 7190953, 7192755, 7192754, 7191588, 7192775, 716, 8510645, 1445, 7190978, 1450, 1449) ";
            elseif($this->resource_id == 'map_class_Aves_with_6_orders') $sql_part = " classkey = 212 AND orderkey IN (716, 8510645, 1445, 7190978, 1450, 1449) ";
            
            elseif($this->resource_id == 'map_class_Aves_with_18_orders') $sql_part = " classkey = 212 AND orderkey IN (8454030, 8706725, 8602104, 721, 8481794, 8454707, 8617753, 1444, 10833565, 8705315, 8708973, 7190987, 7191426, 7192749, 8603836, 10726067, 725, 726) ";
            // elseif($this->resource_id == 'map_class_Aves_with_18_orders') $sql_part = " specieskey = 2477528 ";
            // No CSV data: [Berberis aquifolium][469325][3033868]
            // elseif($this->resource_id == 'map_class_Aves_with_18_orders') $sql_part = " specieskey = 3033868 ";
            // Will use API for: [Neogobius melanostomus][46575276][2379089]
            // elseif($this->resource_id == 'map_class_Aves_with_18_orders') $sql_part = " specieskey = 2379089 ";
            // No CSV data: [Ocyurus chrysurus][46580791][2385282]
            // No CSV data: [Neovison vison][922786][2433652]


            elseif($this->resource_id == 'map_class_Aves_but_not_all_orders') $sql_part = " classkey = 212 AND orderkey NOT IN (7192402, 7191147, 1108, 1448, 724, 1446, 1447, 839, 723, 1493, 1492, 7191407, 7190953, 7192755, 7192754, 7191588, 7192775, 716, 8510645, 1445, 7190978, 1450, 1449, 8454030, 8706725, 8602104, 721, 8481794, 8454707, 8617753, 1444, 10833565, 8705315, 8708973, 7190987, 7191426, 7192749, 8603836, 10726067, 725, 726) ";
            // -----------------------------            
            elseif($this->resource_id == 'map_plantae_not_phylum_Tracheophyta') $sql_part = " kingdomkey = 6 AND phylumkey <> 7707728 ";
            elseif($this->resource_id == 'map_phylum_Tracheophyta_class_Magnoliopsida_orders_3') $sql_part = " phylumkey = 7707728 AND classkey = 220 AND orderkey IN (414, 422, 1353) ";            
            elseif($this->resource_id == 'map_phylum_Tracheophyta_class_Magnoliopsida_not_orders_3') $sql_part = " phylumkey = 7707728 AND classkey = 220 AND orderkey NOT IN (414, 422, 1353) ";
            elseif($this->resource_id == 'map_phylum_Tracheophyta_not_class_Magnoliopsida') $sql_part = " phylumkey = 7707728 AND classkey <> 220 ";
            // -----------------------------
            /* From: https://techdocs.gbif.org/en/data-use/api-sql-downloads
            - mediatype = e.g. values: StillImage, MovingImage or Sound
            - v_associatedmedia
            */

            $param['sql'] = "SELECT catalognumber, scientificname, publishingorgkey, institutioncode, datasetkey, gbifid, decimallatitude, decimallongitude, 
            recordedby, identifiedby, eventdate, kingdomkey, phylumkey, classkey, orderkey, familykey, genuskey, subgenuskey, specieskey,
            mediatype, v_associatedmedia
            FROM occurrence WHERE
            $sql_part
            AND taxonomicstatus = 'ACCEPTED' //newly added 12Feb2025
            AND hascoordinate = 1
            AND hasgeospatialissues = 0
            AND specieskey IS NOT NULL
            AND occurrencestatus = 'PRESENT'
            -- AND (
            --     basisofrecord = 'HUMAN_OBSERVATION'
            --     OR basisofrecord = 'MACHINE_OBSERVATION'
            --     OR basisofrecord = 'OCCURRENCE'
            --     OR basisofrecord = 'LIVING_SPECIMEN'
            --     OR basisofrecord = 'MATERIAL_SAMPLE'
            -- )
            AND NOT ARRAY_CONTAINS(issue, 'ZERO_COORDINATE')
            AND NOT ARRAY_CONTAINS(issue, 'COORDINATE_OUT_OF_RANGE') $datasetKey_filters ";
            /*
            $rec = array();
            $rec['a']   = $rek['catalognumber'];
            $rec['b']   = $rek['scientificname'];
            $rec['c']   = self::get_org_name('publisher', @$rek['publishingorgkey']);
            $rec['d']   = @$rek['publishingorgkey'];
            if($val = @$rek['institutioncode']) $rec['c'] .= " ($val)";
            $rec['e']   = self::get_dataset_field(@$rek['datasetkey'], 'title'); //self::get_org_name('dataset', @$rek['datasetkey']);
            $rec['f']   = @$rek['datasetkey'];
            $rec['g']   = $rek['gbifid'];
            $rec['h']   = $rek['decimallatitude'];
            $rec['i']   = $rek['decimallongitude'];
            $rec['j']   = @$rek['recordedby'];
            $rec['k']   = @$rek['identifiedby'];
            $rec['l']   = self::get_media_by_gbifid($gbifid);
            $rec['m']   = @$rek['eventdate'];
            */
        }
        elseif($this->resource_id == 'WaterBody_checklists') {
            unset($param['predicate']);
            $param['sql'] = "SELECT specieskey, COUNT(specieskey), waterbody
            FROM occurrence
            WHERE
            specieskey IS NOT NULL
            AND waterbody IS NOT NULL
            AND occurrencestatus = 'PRESENT'
            AND (
                basisofrecord = 'HUMAN_OBSERVATION'
                OR basisofrecord = 'MACHINE_OBSERVATION'
                OR basisofrecord = 'OCCURRENCE'
                OR basisofrecord = 'LIVING_SPECIMEN'
                OR basisofrecord = 'MATERIAL_SAMPLE'
            )
            AND NOT ARRAY_CONTAINS(issue, 'ZERO_COORDINATE')
            AND NOT ARRAY_CONTAINS(issue, 'COORDINATE_OUT_OF_RANGE') " .$this->datasetKey_filters. " 
            GROUP BY specieskey, waterbody";
            /* removed: pertains to country not water body
            AND NOT ARRAY_CONTAINS(issue, 'COUNTRY_COORDINATE_MISMATCH')
            */
        }
        elseif($this->resource_id == 'Continent_checklists') {
            unset($param['predicate']);
            $param['sql'] = "SELECT specieskey, COUNT(specieskey), continent
            FROM occurrence
            WHERE
            continent IS NOT NULL
            AND continent IS NOT NULL
            AND occurrencestatus = 'PRESENT'
            AND (
                basisofrecord = 'HUMAN_OBSERVATION'
                OR basisofrecord = 'MACHINE_OBSERVATION'
                OR basisofrecord = 'OCCURRENCE'
                OR basisofrecord = 'LIVING_SPECIMEN'
                OR basisofrecord = 'MATERIAL_SAMPLE'
            )
            AND NOT ARRAY_CONTAINS(issue, 'ZERO_COORDINATE')
            AND NOT ARRAY_CONTAINS(issue, 'COORDINATE_OUT_OF_RANGE') " .$this->datasetKey_filters. " 
            GROUP BY specieskey, continent";
        }
        echo("\n".@$param['sql']."\n"); //exit;
        return json_encode($param);
        /* from GBIF API Downloads: Country_checklists or WaterBody_checklists Continent_checklists
            {
                "sendNotification": true,
                "notificationAddresses": [
                    "eagbayani@eol.org" 
                ],
                "format": "SQL_TSV_ZIP", 
                "sql": "SELECT..." 
            }        
        */
    }
    private function save_json_2file($json)
    {
        $file = $this->destination_path.'/query.json';
        $fhandle = Functions::file_open($file, "w");
        fwrite($fhandle, $json);
        fclose($fhandle);
    }
    private function save_key_per_taxon_group($taxon_group, $key)
    {
        $file = $this->destination_path.'/download_key_'.$taxon_group.'.txt';
        $fhandle = Functions::file_open($file, "w");
        fwrite($fhandle, $key);
        fclose($fhandle);
    }
    function retrieve_key_for_taxon($taxon_group)
    {
        $file = $this->destination_path.'/download_key_'.$taxon_group.'.txt';
        if(file_exists($file)) {
            if($key = trim(file_get_contents($file))) return $key;
            else exit("\nDownload key not found for [$taxon_group]\n");
        }
        else echo "\nNo download request for this taxon yet [$taxon_group].\n\n";
    }
    private function create_bash_file($taxon_group, $downloadLink)
    {
        $row1 = "#!/bin/sh";
        /* worked for the longest time. But started having this error msg: 
        "curl: (33) HTTP server doesn't seem to support byte ranges. Cannot resume."
        So I now removed the "-C"
        $row2 = "curl -L -o '".$taxon_group."_DwCA.zip' -C - $downloadLink";
        */
        $row2 = "curl --insecure -LsS -o '".$taxon_group."_DwCA.zip' -C - $downloadLink";   //this worked OK as of Oct 17, 2021 (as of Sep 28, 2023)
        /* for some reason this -sS is causing error. BETTER TO NOT USE IT.
        -s is "silent"
        -S is show errors when it is "silent"
        */
        /*
        " -C - "
        works OK!. Can now resume unfinished download
        */

        $file = $this->destination_path.'/run_'.$taxon_group.'.sh';
        $fhandle = Functions::file_open($file, "w");
        fwrite($fhandle, $row1."\n");
        fwrite($fhandle, $row2."\n");
        fclose($fhandle);
    }
}
?>