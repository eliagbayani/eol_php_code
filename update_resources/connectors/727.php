<?php
namespace php_active_record;
/* From this adjustment request by Jen: DATA-1819

from legacy https://editors.eol.org/eol_php_code/applications/content_server/resources/727_24Oct2017.tar.gz

727	Tuesday 2017-10-24 04:39:52 AM	{"agents.txt":1,"associations.txt":1,"common names.txt":6,"events.txt":2,"measurements or facts.txt":295147,"media.txt":5,"occurrences.txt":295147,"references.txt":2,"taxa.txt":48096}
    http://eol.org/schema/media/document:
            http://purl.org/dc/dcmitype/Text: 2
            http://purl.org/dc/dcmitype/StillImage: 3
        Total: 5
        Total by subtype:   Map: 1
    http://rs.tdwg.org/dwc/terms/taxon:             Total: 48096
    http://rs.gbif.org/terms/1.0/vernacularname:    Total: 6
    http://eol.org/schema/reference/reference:      Total: 2
    http://eol.org/schema/agent/agent:              Total: 1
    http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 295147
    http://eol.org/schema/association:              Total: 1

*The legacy 727.tar.gz was renamed to 727_24Oct2017.tar.gz. We're going to use the latter moving forward.
This legacy DwCA was lastly generated by: spreadsheet_2_dwca.php _ 727. That was back in 24Oct2017.
-----------------------------------------------------------------------------------------------------------------------
started using 727.php - Aug, 2019
727	Thursday 2019-08-29 10:39:30 AM	{"agent.tab":1,"MoF.tab":604080,"media.tab":5,"occur.tab":658770,"reference.tab":2,"taxon.tab":35605,"vernacular_name.tab":305965}
727	Tuesday 2019-09-03 10:13:00 PM	{"agent.tab":1,"MoF.tab":604080,"media.tab":5,"occur.tab":658770,"reference.tab":2,"taxon.tab":35605,"vernacular_name.tab":305965} - updated URIs for the 10 area strings
727	Thursday 2019-12-05 11:38:58 AM	{"agent.tab":1,"MoF.tab":604080,"media.tab":5,"occur.tab":658770,"reference.tab":2,"taxon.tab":35605,"vernacular_name.tab":305965} - DATA-1841 terms remapped - Consistent OK
727	Tuesday 2019-12-17 03:00:54 AM	{"agent.tab":1, "MoF.tab":604080, "media.tab":5, "occur.tab":658770, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
Latest:
No trial #2
727	Wednesday 2020-03-25 08:18:28 PM{"agent.tab":1, "MoF.tab":602198, "media.tab":5, "occur.tab":656888, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
With trial #2 - improves a bit - but I guess consistent now as latest adjustment is to remove some records. Consistent OK
727	Thursday 2020-03-26 12:19:25 AM	{"agent.tab":1, "MoF.tab":602217, "media.tab":5, "occur.tab":656907, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
727	Saturday 2020-06-20 09:52:32 AM	{"agent.tab":1, "MoF.tab":602221, "media.tab":5, "occur.tab":656911, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false} - Consistent OK
Below start where mtype == Uses are removed
727	            Tuesday 2020-07-14 03:31:37 AM	{"agent.tab":1, "MoF.tab":581781, "media.tab":5, "occur.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false} - expected decrease in MoF

727	                Wed 2021-01-27 08:35:54 PM	{"agent.tab":1, "MoF.tab":581781, "media.tab":5, "occur.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
727_meta_recoded	Wed 2021-01-27 08:45:30 PM	{"agent.tab":1, "MoF.tab":581757, "media.tab":5, "occur.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":576.47, "min":9.61, "hr":0.16}}

727	Wed 2021-03-10 09:38:36 AM	                {"agent.tab":1, "MoF.tab":581781, "media.tab":5, "occur.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":false}
727_meta_recoded	Wed 2021-03-10 09:48:35 AM	{"agent.tab":1, "MoF.tab":581757, "media.tab":5, "occur.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":597.21, "min":9.95, "hr":0.17}}
727_meta_recoded	Thu 2022-03-10 09:28:17 AM	{"agent.tab":1, "MoF.tab":581757, "media.tab":5, "occur.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":609.92, "min":10.17, "hr":0.17}}
727_meta_recoded	Tue 2022-05-10 08:32:21 AM	{"agent.tab":1, "MoF.tab":581757, "media.tab":5, "occur.tab":636471, "reference.tab":2, "taxon.tab":35605, "vernacular_name.tab":305965, "time_elapsed":{"sec":600.71, "min":10.01, "hr":0.17}}

Good stable run: after adapting to the latest service API:
727	Thu 2022-05-12 07:17:28 PM	                {"agent.tab":1, "MoF.tab":580185, "media.tab":5, "occur.tab":634875, "reference.tab":2, "taxon.tab":35186, "vernacular_name.tab":47481, "time_elapsed":false}
727_meta_recoded	Thu 2022-05-12 07:26:20 PM	{"agent.tab":1, "MoF.tab":580161, "media.tab":5, "occur.tab":634875, "reference.tab":2, "taxon.tab":35186, "vernacular_name.tab":47481, "time_elapsed":{"sec":531.07, "min":8.85, "hr":0.15}}

727	Mon 2022-05-16 06:02:43 AM	                {"agent.tab":1, "MoF.tab":580185, "media.tab":5, "occur.tab":634875, "reference.tab":2, "taxon.tab":35186, "vernacular_name.tab":47481, "time_elapsed":false}
727_meta_recoded    Mon 2022-05-16 06:11:35 AM	{"agent.tab":1, "MoF.tab":580161, "media.tab":5, "occur.tab":634875, "reference.tab":2, "taxon.tab":35186, "vernacular_name.tab":47481, "time_elapsed":{"sec":530.75, "min":8.85, "hr":0.15}}

727	Wed 2023-04-05 06:34:30 AM	                {"agent.tab":1, "MoF.tab":580185, "media.tab":5, "occur.tab":634875, "reference.tab":2, "taxon.tab":35186, "vernacular_name.tab":47481, "time_elapsed":false}
727_meta_recoded	Wed 2023-04-05 06:42:57 AM	{"agent.tab":1, "MoF.tab":580161, "media.tab":5, "occur.tab":634875, "reference.tab":2, "taxon.tab":35186, "vernacular_name.tab":47481, "time_elapsed":{"sec":506.92, "min":8.45, "hr":0.14}}

Below no more vernaculars. Vernaculars now in usda_plant_images.tar.gz
727	Tue 2023-05-23 06:51:15 AM	                {"agent.tab":1, "MoF.tab":580185, "media.tab":5, "occur.tab":634875, "reference.tab":2, "taxon.tab":35186, "time_elapsed":false}
727_meta_recoded	Tue 2023-05-23 06:59:37 AM	{"agent.tab":1, "MoF.tab":580161, "media.tab":5, "occur.tab":634875, "reference.tab":2, "taxon.tab":35186, "time_elapsed":{"sec":502.52, "min":8.38, "hr":0.14}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

// test(); exit;

$resource_id = 727;
$dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/727_24Oct2017.tar.gz';
process_resource_url($dwca_file, $resource_id);

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function test()
{
    require_library('connectors/USDAPlants2019');
    $func = new USDAPlants2019("", "");
    /* worked OK
    $url = 'https://plants.sc.egov.usda.gov/core/profile?symbol=ABBA';
    // $url = 'https://plants.sc.egov.usda.gov/core/profile?symbol=ABAL3';
    $func->parse_profile_page($url);
    */
    // $func->process_per_state(); //worked OK
    
    // /* just a test, won't continue processing... just for debug example of 'no data found'
    $id = 'US30'; //with data
    $id = 'CANFCALB'; //e.g. no data found
    if($local = Functions::save_remote_file_to_local('https://plants.sc.egov.usda.gov/java/stateDownload?statefips='.$id)) {
        $func->parse_state_list($local, $id);
        if(file_exists($local)) unlink($local);
    }
    // */
}

function process_resource_url($dwca_file, $resource_id)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    /* Orig in meta.xml has capital letters. Just a note reminder.
      rowType="http://eol.org/schema/media/Document"
      rowType="http://rs.tdwg.org/dwc/terms/Taxon"
      rowType="http://rs.gbif.org/terms/1.0/VernacularName"
      rowType="http://eol.org/schema/reference/Reference"
      rowType="http://eol.org/schema/agent/Agent"
      rowType="http://rs.tdwg.org/dwc/terms/Occurrence"
      rowType="http://rs.tdwg.org/dwc/terms/MeasurementOrFact"
      rowType="http://eol.org/schema/Association"                       --- seems to be not intended to be created
      rowType="http://rs.tdwg.org/dwc/terms/Event"                      --- seems not needed
    */

    $preferred_rowtypes = array('http://eol.org/schema/media/document', 'http://eol.org/schema/reference/reference', 'http://eol.org/schema/agent/agent');
    /* These 4 will be processed in USDAPlants2019.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/occurrence
    http://rs.tdwg.org/dwc/terms/measurementorfact
    http://rs.tdwg.org/dwc/terms/taxon
    http://rs.gbif.org/terms/1.0/vernacularname
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id);
}
?>