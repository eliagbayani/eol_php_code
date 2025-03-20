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

// step 1:
$func->build_resources_list(); //generates a json file of resources to be used in next step. Run once only every month.
// step 2:
$func->process_DwCAs_using_json_list_of_resources(); //using a json file from step 1. Run each resource and write textmined traits to tsv.

// $func->process_DwCAs($resource_ids); //during dev

/* copied template
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
*/

?>