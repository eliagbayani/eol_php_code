<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');
$resource_id = '-none-';
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array('MoftheAES_resources', '119035_ENV');

$func->combine_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);

/* IMPORTANT:
After above, next in line is to open the workspace: Remove_MoF_Records.code-workspace
*/

?>