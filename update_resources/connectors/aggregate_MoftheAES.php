<?php
namespace php_active_record;
/* This can be a generic connector that combines DwCA's. */
/* start Mar 17, 2025
Copied from Jenkins.eol.org

cd /var/www/html/eol_php_code/update_resources/connectors
## step 1: generate XXX_ENV.tar.gz for 20 DwCAs
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118935", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"120081", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"120082", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118986", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118920", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"120083", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118237", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118941", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118950", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118936", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118946", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"118978", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"119035", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"119187", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"119188", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"119520", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"120602", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"27822", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"30354", "subjects":"Description|Uses"}'
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"MoftheAES 1st", "resource_id":"30355", "subjects":"Description|Uses"}'
#generates 20 DwCAs XXX_ENV.tar.gz

## step 2:
php aggregate_MoftheAES.php
# this combines: 20 DwCAs XXX_ENV.tar.gz
# generates MoftheAES_resources.tar.gz
# since MoftheAES_resources.tar.gz already exists in Zenodo, the Zenodo record was updated with new DwCA.
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
require_library('connectors/DwCA_Aggregator_Functions');
require_library('connectors/DwCA_Aggregator');
$resource_id = 'MoftheAES_resources'; //Memoirs of the American Entomological Society : https://zenodo.org/records/13321700
$func = new DwCA_Aggregator($resource_id, false, 'regular');
$resource_ids = array("118935", "120081", "120082", "118986", "118920", "120083", "118237",
"118941", "118950", "118936", "118946", "118978", "119035", "119187", "119188", "119520", "120602", "27822", "30354", "30355");
/* 20 documents as of Jul 29, 2021 */

/* rowtypes
"http://rs.tdwg.org/dwc/terms/taxon", "http://eol.org/schema/media/document", 
"http://rs.tdwg.org/dwc/terms/occurrence", "http://rs.tdwg.org/dwc/terms/measurementorfact"
*/
$func->combine_MoftheAES_DwCAs($resource_ids);
Functions::finalize_dwca_resource($resource_id, false, true, $timestart);

/* Not part of any operation. To save legacy DwCAs:
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118935.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/120081.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/120082.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118986.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118920.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/120083.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118237.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118941.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118950.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118936.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118946.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118978.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/119035.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/119187.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/119188.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/119520.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/120602.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/27822.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/30354.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/30355.tar.gz

wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118935_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/120081_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/120082_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118986_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118920_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/120083_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118237_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118941_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118950_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118936_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118946_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/118978_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/119035_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/119187_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/119188_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/119520_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/120602_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/27822_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/30354_ENV.tar.gz
wget https://editors.eol.org/eol_php_code/applications/content_server/resources/30355_ENV.tar.gz
*/
?>