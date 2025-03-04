<?php
namespace php_active_record;
/* execution time (Jenkins) - initial run:      took 3 days 6 hours
                            - suceeding runs:   ???
15	Thursday 2018-04-05 05:02:24 AM	{"agent.tab":1826,"media_resource.tab":224686,"taxon.tab":63145,"vernacular_name.tab":48718}
15	Saturday 2018-05-05 04:52:17 AM	{"agent.tab":1819,"media_resource.tab":225425,"taxon.tab":63428,"vernacular_name.tab":48632}
15	Tuesday 2018-06-05 05:28:10 AM	{"agent.tab":1812,"media_resource.tab":226307,"taxon.tab":63668,"vernacular_name.tab":48725}
15	Thursday 2019-10-17 06:49:21 AM	{"agent.tab":1785,"media_resource.tab":236651,"taxon.tab":65679,"vernacular_name.tab":50276,"time_elapsed":false}
15	Wednesday 2019-12-18 02:06:17 PM{"agent.tab":1782,"media_resource.tab":237585,"taxon.tab":65976,"vernacular_name.tab":50304,"time_elapsed":false} Consistent OK
15	Wednesday 2019-12-25 06:10:40 AM{"agent.tab":1856,"media_resource.tab":260368,"taxon.tab":70486,"vernacular_name.tab":52550,"time_elapsed":false} in between connector updates...
15	Wednesday 2019-12-25 08:53:24 PM{"agent.tab":1856, "media_resource.tab":260368, "taxon.tab":70486, "time_elapsed":false} expected, vernaculars removed. Consistent OK
15	Thursday 2020-06-04 06:52:37 AM	{"agent.tab":1796, "media_resource.tab":238120, "taxon.tab":71147, "time_elapsed":false} -- start new updated connector, no auth_token anymore.
15_delta	Tue 2022-02-15 03:43:02 {"agent.tab":1796, "media_resource.tab":238120, "taxon.tab":71147, "time_elapsed":{"sec":357.45, "min":5.96, "hr":0.1}} - Mac Mini
15_delta	Tue 2022-02-15 10:50:01 {"agent.tab":1796, "media_resource.tab":238120, "taxon.tab":71147, "time_elapsed":{"sec":156.75, "min":2.61, "hr":0.04}} - eol->archive

15	Sat 2022-02-19 10:46:51 PM	    {"agent.tab":1839, "media_resource.tab":270523, "taxon.tab":79081, "time_elapsed":false}
15_delta	Sat 2022-02-19 10:49:39 {"agent.tab":1839, "media_resource.tab":270523, "taxon.tab":79081, "time_elapsed":{"sec":167.07, "min":2.78, "hr":0.05}}
15	    Mon 2022-02-21 01:24:32 AM	{"agent.tab":1845, "media_resource.tab":270523, "taxon.tab":79081, "time_elapsed":false}
15_delta	Mon 2022-02-21 01:27:19 {"agent.tab":1845, "media_resource.tab":270523, "taxon.tab":79081, "time_elapsed":{"sec":166.59, "min":2.78, "hr":0.05}}

15	    Mon 2023-04-03 08:05:27 AM	{"agent.tab":1834, "media_resource.tab":275767, "taxon.tab":80993, "time_elapsed":false}
15_delta	Mon 2023-04-03 09:44:05 {"agent.tab":1834, "media_resource.tab":275767, "taxon.tab":80993, "time_elapsed":{"sec":167.4, "min":2.79, "hr":0.05}}

Jenkins sched: 5,7,9,11,1,3 *
May Jul Sep Nov Jan Mar

Jun 6, 2024 ---> Remove in Flickr Group:
ea0a7840e37dd7d1c1c021a891039723	Wolfgang Bettighofer	photographer	http://www.flickr.com/photos/152958044@N03
*/
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
define('DOWNLOAD_WAIT_TIME', '300000'); // .3 seconds wait time
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
$resource_id = 15;
if(!Functions::can_this_connector_run($resource_id)) return;
require_library('FlickrAPI');
$GLOBALS['ENV_DEBUG'] = false;

$auth_token = NULL;
if(FlickrAPI::valid_auth_token(FLICKR_AUTH_TOKEN)) $auth_token = FLICKR_AUTH_TOKEN;

// create new _temp file
if(!($resource_file = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", "w+"))) return;

// start the resource file with the XML header
fwrite($resource_file, \SchemaDocument::xml_header());

log_step("STEP 1: query Flickr and write results to file");
// FlickrAPI::get_all_eol_photos($auth_token, $resource_file); //orig
FlickrAPI::get_all_eol_photos($auth_token, $resource_file, NULL, NULL, NULL, $resource_id);

log_step("STEP 2: write the resource footer");
fwrite($resource_file, \SchemaDocument::xml_footer());
fclose($resource_file);

log_step("STEP 3: cache the previous version and make this new version the current version");
@unlink(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_previous.xml");
Functions::file_rename(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");

log_step("STEP 4: set Flickr to Harvest Requested");
/* not needed anymore
if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml") > 600) Functions::set_resource_status_to_harvest_requested($resource_id);
*/
log_step("STEP 5: fix bad characters");
$xml_string = file_get_contents(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml");
$xml_string = Functions::remove_invalid_bytes_in_XML($xml_string);
if(($fhandle = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml", "w")))
{
    fwrite($fhandle, $xml_string);
    fclose($fhandle);
}

log_step("STEP 6: compress resource xml");
Functions::gzip_resource_xml($resource_id); //un-comment if you want to investigate the XML file

log_step("STEP 7: call_xml_2_dwca");
require_library('ResourceDataObjectElementsSetting');
$nmnh = new ResourceDataObjectElementsSetting($resource_id);
$nmnh->call_xml_2_dwca($resource_id, "Flickr files", false, $timestart); //3rd param false means it is not NMNH resource.

function log_step($str)
{
    echo "\n-----------------------------------";
    echo "\n".$str;
    echo "\n-----------------------------------\n";
}
?>