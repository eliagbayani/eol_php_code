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

$func->process_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);


?>