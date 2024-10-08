<?php
namespace php_active_record;
/* From this adjustment request by Jen:
https://eol-jira.bibalex.org/browse/DATA-1768?focusedCommentId=63624&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63624

from old 708.php
708	Saturday 2018-11-24 02:48:32 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193}
708	Sunday 2018-11-25 04:26:42 AM	{"measurement_or_fact.tab":742382,"occurrence.tab":645486,"reference.tab":198537,"taxon.tab":196193}

started using 708_new.php - Aug 2, 2019
708	Friday 2019-08-02 11:19:20 AM	{"measurement_or_fact.tab":742379,"occurrence.tab":645483,"reference.tab":198537,"taxon.tab":196192} - consistent OK
708	Friday 2019-08-02 11:38:34 AM	{"measurement_or_fact.tab":742379,"occurrence.tab":645483,"reference.tab":198537,"taxon.tab":196192} - 1st in eol->archive
708	Friday 2019-08-09 01:26:06 AM	{"measurement_or_fact.tab":742378,"occurrence.tab":645482,"reference.tab":198537,"taxon.tab":196191} - remove in taxon.tab a blank sciname
after DATA-1841: terms remapping -> nos. shouldn't be affected, so consistent OK
708	Monday 2019-11-25 04:03:57 AM	{"measurement_or_fact.tab":742378,"occurrence.tab":645482,"reference.tab":198537,"taxon.tab":196191,"time_elapsed":{"sec":341.02,"min":5.68,"hr":0.09}}
708	Thursday 2020-02-20 11:18:49 PM	{"measurement_or_fact.tab":742378,"occurrence.tab":645482,"reference.tab":198537,"taxon.tab":196191,"time_elapsed":{"sec":306.43,"min":5.11,"hr":0.09}}
Expected for MoF to be reduced - so consistent OK
708	Thursday 2020-03-19 03:00:34 AM	{"measurement_or_fact.tab":727108, "occurrence.tab":631535, "reference.tab":198537, "taxon.tab":196191, "time_elapsed":{"sec":580.76, "min":9.68, "hr":0.16}}
708	Wednesday 2020-05-13 05:42:17 AM{"measurement_or_fact.tab":720242, "occurrence.tab":625163, "reference.tab":198537, "taxon.tab":194207, "time_elapsed":{"sec":933.52, "min":15.56, "hr":0.26}} Mac Mini
708	Wednesday 2020-05-13 09:35:56 AM{"measurement_or_fact.tab":720242, "occurrence.tab":625163, "reference.tab":195531, "taxon.tab":194207, "time_elapsed":{"sec":571.25, "min":9.52, "hr":0.16}} back to eol-archive

saved at one point: 708_31Jul2020.tar.gz
    http://rs.tdwg.org/dwc/terms/taxon:             Total: 194207
    http://rs.tdwg.org/dwc/terms/measurementorfact: Total: 720242
    http://eol.org/schema/reference/reference:      Total: 195531

Below are start of the updated resutls based on a legacy filter:
708	Sunday 2020-08-02 11:20:43 AM	                    {         "measurement_or_fact.tab":808856,          "occurrence.tab":704252, "reference.tab":198288, "taxon.tab":199940, "time_elapsed":{"sec":605.09, "min":10.08, "hr":0.17}}
708_cleaned_habitat_values	Thu 2022-04-07 03:20:12 AM	{"measurement_or_fact_specific.tab":663740, "occurrence_specific.tab":588157, "reference.tab":198288, "taxon.tab":187515, "time_elapsed":{"sec":833.32, "min":13.89, "hr":0.23}} Mac Mini
708_cleaned_habitat_values	Thu 2022-04-07 09:46:52 AM	{"measurement_or_fact_specific.tab":663740, "occurrence_specific.tab":588157, "reference.tab":198288, "taxon.tab":187515, "time_elapsed":{"sec":536.23, "min":8.94, "hr":0.15}} eol-archive
Below references is also properly updated
708_cleaned_habitat_values	Thu 2022-04-07 10:45:34 AM	{"measurement_or_fact_specific.tab":663740, "occurrence_specific.tab":588157, "reference.tab":180268, "taxon.tab":187515, "time_elapsed":{"sec":569.97, "min":9.5, "hr":0.16}}
*/

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

$resource_id = 708;
// if(Functions::is_production())  $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/708_25Nov2018.tar.gz';
if(Functions::is_production())  $dwca_file = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/708_26Nov2018.tar.gz';
else                            $dwca_file = 'http://localhost/cp/Environments/legacy/708_26Nov2018.tar.gz';
process_resource_url($dwca_file, $resource_id, $timestart);

function process_resource_url($dwca_file, $resource_id, $timestart)
{
    require_library('connectors/DwCA_Utility');
    $func = new DwCA_Utility($resource_id, $dwca_file);

    /* Orig in meta.xml has capital letters. Just a note reminder.
    rowType="http://rs.tdwg.org/dwc/terms/Occurrence"
    rowType="http://rs.tdwg.org/dwc/terms/MeasurementOrFact"
    rowType="http://rs.tdwg.org/dwc/terms/Taxon"
    rowType="http://eol.org/schema/reference/Reference"
    */

    $preferred_rowtypes = array();
    /* These 4 will be processed in New_EnvironmentsEOLDataConnector.php which will be called from DwCA_Utility.php
    http://rs.tdwg.org/dwc/terms/occurrence
    http://rs.tdwg.org/dwc/terms/measurementorfact
    http://rs.tdwg.org/dwc/terms/taxon
    http://eol.org/schema/reference/reference
    */
    $func->convert_archive($preferred_rowtypes);
    Functions::finalize_dwca_resource($resource_id, false, true, $timestart);
}
?>