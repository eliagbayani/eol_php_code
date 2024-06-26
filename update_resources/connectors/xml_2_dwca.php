<?php
namespace php_active_record;
/* https://eol-jira.bibalex.org/browse/TRAM-703?focusedCommentId=63349&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63349
This is a generic script that will convert EOL XML to EOL DWC-A

20	Thursday 2019-12-26 10:52:17 AM	{"agent.tab":2031, "media_resource.tab":28979, "reference.tab":1420, "taxon.tab":8830, "time_elapsed":{"sec":32.97,"min":0.55,"hr":0.01}}
20	Thursday 2020-08-06 06:32:48 AM	{"agent.tab":2031, "media_resource.tab":28979, "reference.tab":1420, "taxon.tab":8830, "time_elapsed":{"sec":35.6, "min":0.59, "hr":0.01}}

327	Thursday 2019-12-26 11:20:32 AM	{"agent.tab":120,"media_resource.tab":31927,"reference.tab":5025,"taxon.tab":17539,"vernacular_name.tab":4678,"time_elapsed":{"sec":29.3,"min":0.49,"hr":0.01}}

TaiEOL	Thursday 2019-12-26 09:57:10 PM	{"agent.tab":15,"media_resource.tab":3823,"taxon.tab":1292,"vernacular_name.tab":807,"time_elapsed":{"sec":4.74,"min":0.08,"hr":0}}
TaiEOL	Friday 2019-12-27 10:22:27 AM	{"agent.tab":10,"media_resource.tab":3409,"taxon.tab":1292,"vernacular_name.tab":807,"time_elapsed":{"sec":2.65,"min":0.04,"hr":0}} images removed
547	Friday 2019-12-27 10:22:34 AM	{"agent.tab":96,"media_resource.tab":13754,"taxon.tab":2954,"vernacular_name.tab":2940,"time_elapsed":{"sec":6.86,"min":0.11,"hr":0}}
889	Friday 2019-12-27 10:22:38 AM	{"agent.tab":2,"media_resource.tab":1394,"taxon.tab":149,"vernacular_name.tab":49,"time_elapsed":{"sec":3.99,"min":0.07,"hr":0}}
890	Friday 2019-12-27 10:22:39 AM	{"agent.tab":1,"media_resource.tab":629,"taxon.tab":365,"vernacular_name.tab":364,"time_elapsed":{"sec":1.05,"min":0.02,"hr":0}}
888	Friday 2019-12-27 10:22:40 AM	{"agent.tab":10,"media_resource.tab":400,"taxon.tab":146,"vernacular_name.tab":146,"time_elapsed":{"sec":1,"min":0.02,"hr":0}}
339	Tuesday 2019-12-31 12:36:31 AM	{"agent.tab":2,"media_resource.tab":59,"taxon.tab":57,"time_elapsed":{"sec":3.7,"min":0.06,"hr":0}}
63	Friday 2020-01-17 02:18:28 AM	{"agent.tab":3,"media_resource.tab":2750,"reference.tab":343,"taxon.tab":871,"time_elapsed":{"sec":2.59,"min":0.04,"hr":0}}
116	Friday 2020-01-17 03:14:19 AM	{"agent.tab":1,"media_resource.tab":642,"reference.tab":64,"taxon.tab":228,"time_elapsed":{"sec":1.3,"min":0.02,"hr":0}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/ConvertEOLtoDWCaAPI');
$timestart = time_elapsed();

/*
$url = "https://editors.eol.org/eoearth/resources/assets/EOL_logo_simple_jpg.jpg";
if(!Functions::accessible_uri_url($url)) exit("\nnot accessible\n");
exit("\nok accessible\n");
*/

$cmdline_params['jenkins_or_cron']  = @$argv[1]; //irrelevant here
$cmdline_params['resource_id']      = @$argv[2]; //useful here
$cmdline_params['expire_seconds']   = @$argv[3]; //useful here


// http://eol.taibif.tw/data_objects/66390
// https://data.taieol.tw/files/eoldata/imagecache/data_object_image/images/39/calophya_mangiferae-2-001-007-g-2.jpg
// http://nchuentm.biodiv.tw/files/nchu/imagecache/w740/images/2/Calophya%20mangiferae-2-001-007-G-2.jpg
// http://nchuentm.biodiv.tw/files/nchu/imagecache/w740/images/2/calophya_mangiferae-2-001-007-g-2.jpg

if($val = @$cmdline_params['resource_id']) $resource_id = $val;
else exit("\nNo resource_id passed. Will terminate.\n");

// $resource_id = 20; //debug
$xml[20]['url'] = 'https://opendata.eol.org/dataset/e09787e8-1428-401a-a10d-c28872f2dc93/resource/f2c6d809-abd9-4b98-9b00-39546dcb4eac/download/20.xml.zip'; //ZooKeys
$xml[327]['url'] = 'https://opendata.eol.org/dataset/1220f735-a568-47e2-adee-f1bbf65c4ffe/resource/fd17f8dd-74f7-43eb-a547-b3f65deec976/download/327.xml.zip'; //Flora of Zimbabwe
$xml['TaiEOL']['url'] = 'http://eoldata.taibif.tw/files/eoldata/eol/taieol_export_taxonpage_44903.xml'; //Taiwan Encyclopedia of Life (TaiEOL) --- mediaURL false
$xml['547']['url'] = 'http://eoldata.taibif.tw/files/eoldata/eol/taieol_export_taxonpage_44902.xml'; //Fish Database of Taiwan --- mediaURL OK
$xml['889']['url'] = 'http://eoldata.taibif.tw/files/eoldata/eol/taieol_export_taxonpage_69268.xml'; //TaiEOL- NCHU Museum of Entomology --- mediaURL scraped
$xml['890']['url'] = 'http://eoldata.taibif.tw/files/eoldata/eol/taieol_export_taxonpage_69297.xml'; //Butterflies of Taiwan --- mediaURL OK
$xml['888']['url'] = 'http://eoldata.taibif.tw/files/eoldata/eol/taieol_export_taxonpage_69267.xml'; //Dragonflies of Taiwan --- mediaURL OK 
$xml['6']['url'] = 'https://opendata.eol.org/dataset/7fa7309c-52e5-4071-a10b-e1f3ed444477/resource/e03c421b-6d75-4586-97cd-b607907bbe65/download/6.xml'; //Arkive (6) XML
$xml['339']['url'] = 'http://data.rbge.org.uk/service/static/Rhododendron_curtis_images_eol_transfer.xml'; //Rhododendron Images from Curtis Botanical
$xml['63']['url'] = 'https://opendata.eol.org/dataset/b526f101-ea5d-4d28-a5ab-19ea5aac7c73/resource/a38bb047-3bdb-4caa-a45d-57fef8cfaee5/download/63.xml'; //INOTAXA
$xml['116']['url'] = 'https://opendata.eol.org/dataset/11dff9b4-0779-45cd-ade6-0ab223ddd5aa/resource/ffe53549-0eb1-4ed8-9e13-bb88b1d2ba93/download/116.xml'; //The Dutch Ascidians Homepage
$xml['100']['url'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/100.xml.gz';


/* Not used but values are correct
$xml['TaiEOL']['images'] = false;
$xml['547']['images'] = true;
$xml['889']['images'] = true; //mediaURL scraped
$xml['890']['images'] = true;
$xml['888']['images'] = true;
*/

$xml[20]['xmlYN'] = false;
$xml[327]['xmlYN'] = false;
$xml['TaiEOL']['xmlYN'] = true;
$xml['547']['xmlYN'] = true;
$xml['889']['xmlYN'] = true;
$xml['890']['xmlYN'] = true;
$xml['888']['xmlYN'] = true;
$xml['6']['xmlYN'] = true;
$xml['339']['xmlYN'] = true;
$xml['63']['xmlYN'] = true;
$xml['116']['xmlYN'] = true;
$xml[100]['xmlYN'] = false;

$xml[20]['expire_seconds'] = false; //no expire
$xml[327]['expire_seconds'] = false;
$xml['TaiEOL']['expire_seconds'] = 60*60*24*30; //expires in a month
$xml['547']['expire_seconds'] = false;
$xml['889']['expire_seconds'] = false;
$xml['890']['expire_seconds'] = false;
$xml['888']['expire_seconds'] = false;
$xml['6']['expire_seconds'] = false;
$xml['339']['expire_seconds'] = 60*60*24*30;
$xml['63']['expire_seconds'] = false;
$xml['116']['expire_seconds'] = false;
$xml['100']['expire_seconds'] = 60*60*24; //expires in a day

if($val = @$cmdline_params['expire_seconds']) $xml[$resource_id]['expire_seconds'] = $val;

if(!@$xml[$resource_id]) exit("\nResource ID [$resource_id] not yet initialized. Will terminate.\n\n");

// $params["eol_xml_file"] = Functions::get_accesspoint_url_if_available($resource_id, "http://...");
$params["eol_xml_file"] = $xml[$resource_id]['url'];
$params["filename"]     = $resource_id.".xml";
$params["dataset"]      = "";
$params["resource_id"]  = $resource_id;
$params["with_imagesYN"]  = @$xml[$resource_id]['images'];

$func = new ConvertEOLtoDWCaAPI($resource_id);
$func->export_xml_to_archive($params, $xml[$resource_id]['xmlYN'], $xml[$resource_id]['expire_seconds']); // 2nd param true => means it is an XML file, not an archive file nor a zip file. Third param false, NO expire.
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>