<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */

/*
#### below copied from Jenkins eol.org
# NorthAmericanFlora_2025.tar.gz was created locally (Mac Studio) and uploaded to eol-archive.
## step 1:
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"NorthAmericanFlora_2025", "subjects":"Description|Uses"}'
#generates NorthAmericanFlora_2025_ENV.tar.gz
## step 2:
php dwca_remove_MoF_records.php jenkins '{"resource_id":"NorthAmericanFlora_All", "destination_id":"NorthAmericanFlora_All_subset"}'
# generates NorthAmericanFlora_All_subset.tar.gz
## step 3:
php update_resources/connectors/aggregate_NorthAF_2025.php
# generates NorthAmericanFlora_All_2025.tar.gz
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');
$resource_id = 'NorthAmericanFlora_All_2025';
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array("NorthAmericanFlora_2025_ENV", "NorthAmericanFlora_All_subset");
$func->combine_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
?>