<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/ReadMultipleDwCA_API');
$resource_id = '-none-';
$func = new ReadMultipleDwCA_API($resource_id, false, 'regular');
$resource_ids = array('119035_ENV', 'MoftheAES_resources');
$resource_ids = array('TreatmentBank_final');

$func->process_DwCAs($resource_ids);
/* copied template
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
*/

function get_all_textmining_resources()
{
    $a = array();
    $a['Wikipedia: Wikipedia English - traits (inferred records)'] = 'https://zenodo.org/records/14437247';
    $a['TreatmentBank'] = 'https://zenodo.org/records/13321535';
    $a['Smithsonian Contributions Series: Smithsonian Contributions to Botany'] = 'https://zenodo.org/records/13321713';
    $a['Memoirs of the American Entomological Society'] = 'https://zenodo.org/records/15039847';
    $a['North American Flora: North American Flora - ALL'] = 'https://zenodo.org/records/15020541';
    $a['Nota Lepidopterologica: Nota Lepidopterologica (798)'] = 'https://zenodo.org/records/13321662';
    $a['Zoosystematics and Evolution: Zoosystematics and Evolution (834)'] = 'https://zenodo.org/records/13321654';
    $a['Deutsche Entomologische Zeitschrift: Deutsche Entomologische Zeitschrift (792)'] = 'https://zenodo.org/records/13321642';
    $a['Zookeys: ZooKeys (20) DwCA'] = 'https://zenodo.org/records/13316129';
    $a['AmphibiaWeb: AmphibiaWeb text w/traits based on Pensoft Annotator'] = 'https://zenodo.org/records/13318110';
    $a['Zookeys: Zookeys (829)'] = 'https://zenodo.org/records/14889995';
    $a['Mycokeys: Mycokeys (830)'] = 'https://zenodo.org/records/14890008';
    $a['Phytokeys: Phytokeys (826)'] = 'https://zenodo.org/records/14890085';
    $a['Journal of Hymenoptera Research: Journal of Hymenoptera Research (831)'] = 'https://zenodo.org/records/14890097';
}
?>