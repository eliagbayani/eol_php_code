<?php
namespace php_active_record;
/* 
This connector will use COLLECTIONS and dataObject API to process EOL XML:
1. convert EOL XML to DwCA
2. if there are media objects, it will use COLLECTIONS media view to get data_object_id's then use dataObject API to get object metadata
3. it will save media objects to /other_files/media/01/ or /23/. The last 2 chars of a data_object_id.
4. this will also combine 2 DwCA (IF NEEDED).
e.g.
- EOL_afrotropicalbirds.tar.gz               ---> for text objects
- EOL_afrotropicalbirds_multimedia.tar.gz    ---> for media objects

http://services.eol.org/resources/40.xml.gz
shhh quiet... - a hack in services.eol.org
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = false;

/* $ collections_generic.php jenkins 729 */
$cmdline_params['jenkins_or_cron']      = @$argv[1]; //irrelevant here
$cmdline_params['resource_id_2process'] = @$argv[2]; //useful here
// print_r($cmdline_params);
$resource_id_2process = false;
if($val = @$cmdline_params['resource_id_2process']) $resource_id_2process = $val;
if($resource_id_2process) echo "\n with resource_id_2process";
else echo "\n without resource_id_2process";

require_library('connectors/LifeDeskToEOLAPI');
$func1 = new LifeDeskToEOLAPI();

require_library('connectors/ConvertEOLtoDWCaAPI');
require_library('connectors/CollectionsScrapeAPI');
require_library('connectors/DwCA_Utility');

$final = array();
if($resource_id_2process) {
    $lifedesks = array($resource_id_2process); $final = array_merge($final, $lifedesks);
}

// $lifedesks = array(185, 367); $final = array_merge($final, $lifedesks);

//============================================================================================================================== 717 has 218 images
/* template
$info[res_id] = array('id' => col_id, 'domain' => 'http', 'OpenData_title' => 'xxxx', 'resource_id' => res_id);
$info[res_id]['xml_path'] = ""; $info[res_id]['data_types'] = array('datatype'); //possible values array('images', 'video', 'sounds', 'text')

$info[res_id] = array('id' => col_id, 'data_types' => array('dtype'), 'xml_path' => ''); //resource_name
*/

$info[683] = array('id' => 111553, 'data_types' => array('images'), 'xml_path' => ''); //Diptera of Central America (683)

//static xml, offline media, has collection -- http://services.eol.org/resources/145.xml
$info[145] = array('id' => 264, 'data_types' => array('video'), 'xml_path' => ''); //Natural History Services Resource

//offline XML, with collection
$info[555] = array('id' => 118667, 'data_types' => array('text', 'images'), 'xml_path' => ''); //Subterranean Biology


// ========================================================================= new batch above =========================================================================

//has XML, but offline media. Used collections instead, backup media.
$info[554] = array('id' => 118666, 'data_types' => array('text', 'images'), 'xml_path' => ''); //Comparative Cytogenetics

//no XML nor DwCA, has collection - https://opendata.eol.org/dataset/edulifedesks-archive/resource/a3ff059a-2eaa-49fc-96c1-566fccf86e47
$info[350] = array('id' => 21244, 'data_types' => array('text', 'images'), 'xml_path' => ''); //From so simple a beginning: 2011

//has dwca but media is already offline, has collection.
$info[426] = array('id' => 34928, 'data_types' => array('video'), 'xml_path' => ''); //Spinus tristis

//has dwca, no XML, has collection
$info[815] = array('id' => 105853, 'data_types' => array('images'), 'xml_path' => ''); //Smithsonian Gardens Orchid Images

//has no more xml nor dwca, has collection. Ran it locally to save space in Archive
$info[18] = array('id' => 179, 'data_types' => array('text'), 'xml_path' => ''); //BioPedia

//has no more xml nor dwca, has collection. Has connector but scrape connecter is obsolete, site has changed a lot.
$info[79] = array('id' => 214, 'data_types' => array('images'), 'xml_path' => ''); //Public Health Image Library Resource

//has connector, new site, must re-create connector, so will backup media for now.
$info[185] = array('id' => 293, 'data_types' => array('images', 'text'), 'xml_path' => ''); //Turbellarian Taxonomic Database resource

//no xml nor dwca, has collection
$info[367] = array('id' => 24776, 'data_types' => array('video'), 'xml_path' => ''); //DCbirds video
$info[369] = array('id' => 24607, 'data_types' => array('images'), 'xml_path' => ''); //Birds of DC maps

//no xml nor dwca, has collection
$info[545] = array('id' => 53390, 'data_types' => array('video'), 'xml_path' => ''); //Creaturecast

//has static dwca, with offline media, has collection
$info[717] = array('id' => 105511, 'domain' => 'http://www.eol.org/content_partners/666/resources/717', 'OpenData_title' => 'Okeanos, Gulf of Mexico', 'resource_id' => 717);
$info[717]['xml_path'] = ""; $info[717]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

//has static dwca, with offline media, has collection
$info[882] = array('id' => 111809, 'domain' => 'http://www.eol.org/content_partners/394/resources/882', 'OpenData_title' => 'eMammal Camera Trap Photos', 'resource_id' => 882);
$info[882]['xml_path'] = ""; $info[882]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

// start above new items -------------------------------------------------------------- 02 reported Thurs Feb 22

// has no more xml nor dwca, without connector, but with collection
$info[742] = array('id' => 100795, 'domain' => 'http://www.eol.org/content_partners/679/resources/742', 'OpenData_title' => 'AquaParadox: The Diversity of Planktonic Microorganisms', 'resource_id' => 742);
$info[742]['xml_path'] = ""; $info[742]['data_types'] = array('video', 'images'); //possible values array('images', 'video', 'sounds', 'text')

// has no more xml nor dwca, with connector, but with collection
$info[729] = array('id' => 99799, 'domain' => 'http://www.eol.org/content_partners/669/resources/729', 'OpenData_title' => 'Marine Life in Koh Phangan resource', 'resource_id' => 729);
$info[729]['xml_path'] = ""; $info[729]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

// has no more xml nor dwca, with connector, but with collection
$info[679] = array('id' => 96205, 'domain' => 'http://www.eol.org/content_partners/185/resources/679', 'OpenData_title' => 'One-time import of archived NBII images', 'resource_id' => 679);
$info[679]['xml_path'] = ""; $info[679]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

// has no more xml nor dwca, but with collection
$info[676] = array('id' => 95012, 'domain' => 'http://www.eol.org/content_partners/616/resources/676', 'OpenData_title' => 'Wikipeixes', 'resource_id' => 676);
$info[676]['xml_path'] = ""; $info[676]['data_types'] = array('images', 'text'); //possible values array('images', 'video', 'sounds', 'text')

//has static dwca but media is already offline, has collections though
$info[520] = array('id' => 94950, 'domain' => 'http://www.eol.org/content_partners/533/resources/520', 'OpenData_title' => 'India Biodiversity Portal Species Data', 'resource_id' => 520);
$info[520]['xml_path'] = ""; $info[520]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

//has static dwca but audio is already offline, has collections though
$info[551] = array('id' => 59318, 'domain' => 'http://www.eol.org/content_partners/584/resources/551', 'OpenData_title' => 'Thomas J. Walker sound recordings from Macaulay Library of Natural Sounds', 'resource_id' => 551);
$info[551]['xml_path'] = ""; $info[551]['data_types'] = array('sounds'); //possible values array('images', 'video', 'sounds', 'text')

//all five xls submitted, used collections.
$info[420] = array('id' => 53328, 'domain' => 'http://www.eol.org/content_partners/513/resources/420', 'OpenData_title' => 'Latin Botanical Illustrations', 'resource_id' => 420);
$info[420]['xml_path'] = ""; $info[420]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

$info[428] = array('id' => 53329, 'domain' => 'http://www.eol.org/content_partners/513/resources/428', 'OpenData_title' => 'African Flora', 'resource_id' => 428);
$info[428]['xml_path'] = ""; $info[428]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

$info[431] = array('id' => 53331, 'domain' => 'http://www.eol.org/content_partners/513/resources/431', 'OpenData_title' => 'Addisonia volume 1, 1916', 'resource_id' => 431);
$info[431]['xml_path'] = ""; $info[431]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

$info[434] = array('id' => 53332, 'domain' => 'http://www.eol.org/content_partners/513/resources/434', 'OpenData_title' => 'Addisonia volume 2, 1917', 'resource_id' => 434);
$info[434]['xml_path'] = ""; $info[434]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

$info[460] = array('id' => 53333, 'domain' => 'http://www.eol.org/content_partners/513/resources/460', 'OpenData_title' => 'Addisonia volume 3, 1918', 'resource_id' => 460);
$info[460]['xml_path'] = ""; $info[460]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

//has connector but partner site already down. no xml nor dwca, used collections instead.
$info[414] = array('id' => 49850, 'domain' => 'http://www.eol.org/content_partners/503/resources/414', 'OpenData_title' => 'Dutch Marine and Coastal Species Encyclopedia', 'resource_id' => 414);
$info[414]['xml_path'] = ""; $info[414]['data_types'] = array('images', 'text'); //possible values array('images', 'video', 'sounds', 'text')

//has static dwca, offline media, has collection
$info[388] = array('id' => 31834, 'domain' => 'http://www.eol.org/content_partners/487/resources/388', 'OpenData_title' => 'Freshwater and Marine Image Bank, University Libraries, U Washington', 'resource_id' => 388);
$info[388]['xml_path'] = ""; //http
$info[388]['data_types'] = array('images', 'text'); //possible values array('images', 'video', 'sounds', 'text')

//with connector, partner site now offline, cannot recreate resource, with collection
$info[221] = array('id' => 318, 'domain' => 'http://www.eol.org/content_partners/302/resources/221', 'OpenData_title' => 'Invertebrates of the Salish Sea', 'resource_id' => 221);
$info[221]['xml_path'] = ""; //http
$info[221]['data_types'] = array('images', 'text'); //possible values array('images', 'video', 'sounds', 'text')

// start above new items -------------------------------------------------------------- 01

//flickr (15) special case, no xml, with collection
// $info['Flickr_snapshot_2018_02_14'] = array('id' => 176, 'domain' => 'http://www.eol.org/content_partners/18/resources/15', 'OpenData_title' => 'Flickr snapshot 2018_Feb_14', 'resource_id' => 'Flickr_snapshot_2018_02_14');
// $info['Flickr_snapshot_2018_02_14']['xml_path'] = ""; //http
// $info['Flickr_snapshot_2018_02_14']['data_types'] = array('images', 'video'); //possible values array('images', 'video', 'sounds', 'text')

//todo: has a dwca with text objects but media_url is offline
$info[785] = array('id' => 103533, 'domain' => 'http://www.eol.org/content_partners/700/resources/785', 'OpenData_title' => 'Inventaire National du Patrimoine Naturel', 'resource_id' => 785);
$info[785]['xml_path'] = "";
$info[785]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

$info[12] = array('id' => 174, 'domain' => 'http://www.eol.org/content_partners/12/resources/12', 'OpenData_title' => 'Initial Biolib.de Import', 'resource_id' => 12);
$info[12]['xml_path'] = "";
$info[12]['data_types'] = array('images'); //possible values array('images', 'video', 'sounds', 'text')

//==============================================================================================================================

/* this works OK. but was decided not to add ancestry if original source doesn't have ancestry. Makes sense.
$ancestry[40] = array('kingdom' => 'Animalia', 'phylum' => 'Chordata', 'class' => 'Aves'); 
*/

/* un-comment if you want to RUN ALL
$final = array_merge($final, array_keys($info));
*/

if(!$resource_id_2process) $final = array_merge($final, array_keys($info));

$final = array_unique($final);
print_r($final); echo "\nTotal resource(s): ".count($final)."\n"; //exit;

// /* normal operation
foreach($final as $ld) {
    $params[$ld]["local"]["lifedesk"] = $info[$ld]['xml_path'];
    $params[$ld]["local"]["name"]     = $ld;
    $params[$ld]["local"]["ancestry"] = @$ancestry[$ld];
}

$cont_compile = false;

foreach($final as $lifedesk) {
    echo "\n -------------------------------------------- Processing [$lifedesk] -------------------------------------------- \n";
    $taxa_from_orig_LifeDesk_XML = array();
    $path = false;
    if(Functions::url_exists($info[$lifedesk]['xml_path'])) {
        $infox = $func1->get_taxa_from_EOL_XML($info[$lifedesk]['xml_path']);
        $taxa_from_orig_LifeDesk_XML = $infox['taxa_from_EOL_XML'];
        $path                        = $infox['xml_path']; //e.g. '/opt/homebrew/var/www/eol_php_code/tmp/dir_50900/anagetext.xml'
        // print_r($infox); exit;
        convert_xml_2_dwca($path, "EOL_".$lifedesk); //convert XML to DwCA
        $cont_compile = true;
    }

    // start generate the 2nd DwCA -------------------------------
    $resource_id = "EOL_".$lifedesk."_multimedia";
    if($collection_id = @$info[$lifedesk]['id']) { //9528;
        $func2 = new CollectionsScrapeAPI($resource_id, $collection_id, $info[$lifedesk]['data_types']);
        $func2->start($taxa_from_orig_LifeDesk_XML);
        Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means resource folder will be deleted
        $cont_compile = true;
    }
    else echo "\nNo Collection for this resource.\n";
    // end generate the 2nd DwCA -------------------------------
    
    //  --------------------------------------------------- start compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 
    if($cont_compile) {
        $dwca_file = false;
        $resource_id = "EOL_".$lifedesk."_final";
        $func2 = new DwCA_Utility($resource_id, $dwca_file); //2nd param is false bec. it'll process multiple archives, see convert_archive_files() in library DwCA_Utility.php

        $archives = array();
        /* use this if we're getting taxa info (e.g. ancestry) from Collection
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."EOL_".$lifedesk."_multimedia.tar.gz")) $archives[] = "EOL_".$lifedesk."_multimedia";
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."EOL_".$lifedesk.".tar.gz"))            $archives[] = "EOL_".$lifedesk;
        */
        // Otherwise let the taxa from LifeDesk XML be prioritized
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."EOL_".$lifedesk.".tar.gz"))            $archives[] = "EOL_".$lifedesk;
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH."EOL_".$lifedesk."_multimedia.tar.gz")) $archives[] = "EOL_".$lifedesk."_multimedia";

        $func2->convert_archive_files($archives); //this is same as convert_archive(), only it processes multiple DwCA files not just one.
        unset($func2);
        Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means it will delete workind directory e.g. /40/ inside /resources/40/
        
        /* working but removed since sometimes a LifeDesk only provides names without objects at all
        //---------------------new start generic_normalize_dwca() meaning remove taxa without objects, only leave taxa with objects in final dwca
        $tar_gz = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".tar.gz";
        if(file_exists($tar_gz)) {
            $func = new DwCA_Utility($resource_id, $tar_gz);
            $func->convert_archive_normalized();
            Functions::finalize_dwca_resource($resource_id);
        }
        //---------------------new end
        */
    }
    //  --------------------------------------------------- end compiling the 2 DwCA files into 1 final DwCA --------------------------------------------------- 

    if($path) {
        // remove temp dir
        $parts = pathinfo($path);
        recursive_rmdir($parts["dirname"]); debug("\n temporary directory removed: " . $parts["dirname"]);
    }
    
} //end foreach()
// */


$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function convert_xml_2_dwca($path, $resource_id)
{
    $params["eol_xml_file"] = $path;
    $params["filename"]     = "no need to mention here.xml";
    $params["dataset"]      = "EOL XML files";
    $params["resource_id"]  = $resource_id;
    $func = new ConvertEOLtoDWCaAPI($resource_id);
    
    /* u need to set this to expire now = 0 ... if there is change in ancestry information... */
    // $func->export_xml_to_archive($params, true, 60*60*24*15); // true => means it is an XML file, not an archive file nor a zip file. Expires in 15 days.
    $func->export_xml_to_archive($params, true, 0); // true => means it is an XML file, not an archive file nor a zip file. Expires now.

    Functions::finalize_dwca_resource($resource_id, false, true); //3rd param true means resource folder will be deleted
    Functions::delete_if_exists(CONTENT_RESOURCE_LOCAL_PATH.$resource_id.".xml");
}

?>