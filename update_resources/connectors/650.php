<?php
namespace php_active_record;
/* connector for Smithsonian Wild Flickr photostream
execution time: 61 hours
earliest upload: 2010-11-13
earliest datetaken: 1900-01-01 00:00:00

as of Feb 21, 2018
    http://rs.gbif.org/terms/1.0/vernacularname:        Total: 2998
    http://eol.org/schema/agent/agent:                  Total: 1
    http://purl.org/dc/dcmitype/StillImage:             3038
    http://creativecommons.org/licenses/by-nc-sa/2.0/:  3038
        Total by language:                              en: 3038
        Total by format:                                image/jpeg: 3038
    http://rs.tdwg.org/dwc/terms/taxon:                 Total: 238

650	Mon 2020-06-08 03:07:14 AM	{"agent.tab":1, "media_resource.tab":3068, "taxon.tab":236, "vernacular_name.tab":3030, "time_elapsed":{"sec":117.43, "min":1.96, "hr":0.03}}
650	Mon 2022-02-21 03:47:40 AM	{"agent.tab":1, "media_resource.tab":3068, "taxon.tab":236, "vernacular_name.tab":236, "time_elapsed":{"sec":4065.2, "min":67.75, "hr":1.13}}

650	Mon 2023-04-03 12:27:28 PM	{"agent.tab":1, "media_resource.tab":3068, "taxon.tab":238, "vernacular_name.tab":236, "time_elapsed":false}
650	Mon 2023-04-03 12:27:32 PM	{"agent.tab":1, "media_resource.tab":3068, "taxon.tab":236, "vernacular_name.tab":236, "time_elapsed":{"sec":2181.14, "min":36.35, "hr":0.61}}

650	Wed 2023-07-19 09:17:58 AM	{"agent.tab":1, "media_resource.tab":3068, "taxon.tab":238, "vernacular_name.tab":236, "time_elapsed":false}
650	Wed 2023-07-19 09:18:04 AM	{"agent.tab":1, "media_resource.tab":3068, "taxon.tab":236, "vernacular_name.tab":236, "time_elapsed":{"sec":2247.93, "min":37.47, "hr":0.62}}
*/

ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('FlickrAPI');
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;
$resource_id = 650;

/* will use generic_services.eol.org.ph for the meantime until extra hardisk is installed in Archive */
// return;

// /*
$user_id = "51045845@N08"; // Smithsonian Wild's photostream - http://www.flickr.com/photos/51045845@N08
$start_year = 2001;
$max_photos_per_taxon = 20;

$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_AUTH_TOKEN)) $auth_token = FLICKR_AUTH_TOKEN;

// create new _temp file
if(!($resource_file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+"))) return;

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

$months_to_be_broken_down = array(0 => array("year" => 2008, "month" => 4),
                                  1 => array("year" => 2008, "month" => 5),
                                  2 => array("year" => 2009, "month" => 5),
                                  3 => array("year" => 2009, "month" => 6),
                                  4 => array("year" => 2009, "month" => 7),
                                  5 => array("year" => 2009, "month" => 8),
                                  6 => array("year" => 2010, "month" => 1),
                                  7 => array("year" => 2010, "month" => 2),
                                  8 => array("year" => 2010, "month" => 3));

// query Flickr and write results to file
FlickrAPI::get_photostream_photos($auth_token, $resource_file, $user_id, $start_year, $months_to_be_broken_down, $max_photos_per_taxon, $resource_id);


$GLOBALS['ENV_DEBUG'] = true;
echo "\nStart: At this point:\n";
if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml")) echo "\nThere is: ".CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml"."\n";
if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml")) echo "\nThere is: ".CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml"."\n";
echo "\nEnd: At this point:\n";

// write the resource footer
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

echo "\nStartx [file_rename]...\n";
// cache the previous version and make this new version the current version
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");
// */

//--------------
echo "\nStartx [manual adjustment on some names]...\n";
/* set rating to 2 */
require_library('ResourceDataObjectElementsSetting');
$resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
$func = new ResourceDataObjectElementsSetting($resource_id, $resource_path, 'http://purl.org/dc/dcmitype/StillImage', 2);
$xml = $func->set_data_object_rating_on_xml_document();

echo "\nStartx set_data_object_rating_on_xml_document...\n";
/* manual adjustment on some names */
$xml = str_ireplace("<dwc:ScientificName>Lontra sp.or Lutra sp.</dwc:ScientificName>", "<dwc:ScientificName>Lontra sp.</dwc:ScientificName>", $xml);
$xml = str_ireplace("<dwc:ScientificName>Sciurus igniventris_or_spadiceus</dwc:ScientificName>", "<dwc:ScientificName>Sciurus igniventris</dwc:ScientificName>", $xml);
$func->save_resource_document($xml);
//--------------

////////////////// section below added in Jun 5, 2020 - convert XML to DwCA

    echo "\nStartx gzip_resource_xml...\n";
    Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate 650.gz.xml, otherwise remain commented

    echo "\nStartx call_xml_2_dwca...\n";
    //---------------------new start
    require_library('ResourceDataObjectElementsSetting');
    $nmnh = new ResourceDataObjectElementsSetting($resource_id);
    $nmnh->call_xml_2_dwca($resource_id, "Flickr files", false); //3rd param false means it is not NMNH resource.
    //---------------------new end

    //---------------------new start convert_archive_normalized() meaning remove taxa without objects, only leave taxa with objects in final dwca
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz");
    $func->convert_archive_normalized();
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
    //---------------------new end

    /* The End */
?>