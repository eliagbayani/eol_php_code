<?php
namespace php_active_record;
/* Global Register of Introduced and Invasive Species : DATA-1838
e.g. Belgium
https://www.gbif.org/dataset/6d9e952f-948c-4483-9807-575348147c7e
https://api.gbif.org/v1/dataset/6d9e952f-948c-4483-9807-575348147c7e/document

e.g.
Belgium -- https://ipt.inbo.be/archive.do?r=unified-checklist
South Africa -- http://ipt.ala.org.au/archive.do?r=south-africa-griis-gbif

Friday 2019-10-18 08:42:41 AM	{"measurement_or_fact_specific.tab":91634,"occurrence_specific.tab":91634,"taxon.tab":51907,"time_elapsed":{"sec":756.03,"min":12.6,"hr":0.21}}
Monday 2019-10-21 09:53:40 AM	{"measurement_or_fact_specific.tab":91634,"occurrence_specific.tab":91634,"taxon.tab":51907,"time_elapsed":{"sec":759.39,"min":12.66,"hr":0.21}}
Thursday 2019-10-31 12:17:33 PM	{"measurement_or_fact_specific.tab":89450,"occurrence_specific.tab":62123,"taxon.tab":16994,"time_elapsed":{"sec":742.57,"min":12.38,"hr":0.21}}
Friday 2019-11-01 08:23:30 AM	{"measurement_or_fact_specific.tab":89450,"occurrence_specific.tab":62123,"taxon.tab":16994,"time_elapsed":{"sec":745.43,"min":12.42,"hr":0.21}}
Sunday 2019-12-01 09:46:47 PM	{"measurement_or_fact_specific.tab":92282,  "occurrence_specific.tab":63604, "taxon.tab":17089, "time_elapsed":{"sec":963.22,"min":16.05,"hr":0.27}} Consistent OK
Wednesday 2020-04-29 05:00:51 AM{"measurement_or_fact_specific.tab":107294, "occurrence_specific.tab":68821, "taxon.tab":16296, "time_elapsed":{"sec":1390.78, "min":23.18, "hr":0.39}}
Wednesday 2020-04-29 08:06:12 AM{"measurement_or_fact_specific.tab":107294, "occurrence_specific.tab":68817, "taxon.tab":16296, "time_elapsed":{"sec":1283.24, "min":21.39, "hr":0.36}}
Wednesday 2020-04-29 10:06:57 AM{"measurement_or_fact_specific.tab":107294, "occurrence_specific.tab":68817, "taxon.tab":16296, "time_elapsed":{"sec":1267.99, "min":21.13, "hr":0.35}}
Monday 2020-05-11 10:07:24 PM	{"measurement_or_fact_specific.tab":107294, "occurrence_specific.tab":68817, "taxon.tab":16296, "time_elapsed":{"sec":1318.97, "min":21.98, "hr":0.37}}
Tuesday 2020-05-12 10:29:00 AM	{"measurement_or_fact_specific.tab":108348, "occurrence_specific.tab":69871, "taxon.tab":16296, "time_elapsed":{"sec":1312.01, "min":21.87, "hr":0.36}}
Tuesday 2020-07-28 02:11:04 AM	{"measurement_or_fact_specific.tab":84575, "occurrence_specific.tab":57077, "taxon.tab":14873, "time_elapsed":{"sec":1082.89, "min":18.05, "hr":0.3}}
Sun 2020-09-06 10:26:31 AM	    {"measurement_or_fact_specific.tab":84575, "occurrence_specific.tab":57077, "taxon.tab":14873, "time_elapsed":{"sec":960.28, "min":16, "hr":0.27}}
Mon 2020-09-07 11:51:45 PM	    {"measurement_or_fact_specific.tab":84800, "occurrence_specific.tab":57302, "taxon.tab":14873, "time_elapsed":{"sec":982.71, "min":16.38, "hr":0.27}}
Tue 2020-09-08 08:30:03 AM	    {"measurement_or_fact_specific.tab":85503, "occurrence_specific.tab":57659, "taxon.tab":14891, "time_elapsed":{"sec":1008.26, "min":16.8, "hr":0.28}}
*/
include_once(dirname(__FILE__) . "/../../config/environment.php");
require_library('connectors/GlobalRegister_IntroducedInvasiveSpecies');
$timestart = time_elapsed();
$cmdline_params['jenkins_or_cron'] = @$argv[1]; //irrelevant here

/* test
$str = 'Medicago sativa subsp. varia (Martyn) Arcang.';
$str = 'Rumex sanguineus var. sanguineus';
$str = 'Lespedeza juncea var. sericea (Thunb.) Lace & Hauech';
$str = 'Abelmoschus esculentus (L.) Moench';
// $str = 'Abelmoschus esculentus Moench';
exit("\n".Functions::canonical_form($str)."\n");
*/

/* test
$south = array('a','b','c');
$belgium = array('a','b','c','d','e');
$diff = array_diff($belgium, $south); //proper
// $diff = array_diff($south, $belgium); //can be used to check what is in others but South Africa doesn't have
print_r($diff); exit;
*/

$resource_id = 'griis'; //Global Register of Introduced and Invasive Species
$func = new GlobalRegister_IntroducedInvasiveSpecies($resource_id);

/* worked OK
$func->compare_meta_between_datasets(); //a utility to generate report for Jen
$func->start('utility_report'); //utility, generate report for Jen. Used once only.
$func->start('synonym_report'); //utility, generate synonym report for Katja. Used once only.
*/

// /*
$func->start(); //main operation - generate DwCA
// */

/* Some debug findings: as of Sep 6, 2020
download_extract_dwca: [https://cloud.gbif.org/griis/archive.do?r=griis-turkey]...
-> does not have [http://rs.tdwg.org/dwc/terms/occurrenceStatus] in distribution.txt
*/

Functions::finalize_dwca_resource($resource_id, false, false, $timestart);

/* as of Oct 18, 2019
wc -l GRIIS_synonym_report.txt 
5233 GRIIS_synonym_report.txt

Array
(
    [http://rs.tdwg.org/dwc/terms/taxonID] => 
    [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
    [http://rs.tdwg.org/dwc/terms/scientificName] => 
    [http://rs.tdwg.org/dwc/terms/acceptedNameUsage] => 
    [http://rs.tdwg.org/dwc/terms/kingdom] => 
    [http://rs.tdwg.org/dwc/terms/phylum] => 
    [http://rs.tdwg.org/dwc/terms/class] => 
    [http://rs.tdwg.org/dwc/terms/order] => 
    [http://rs.tdwg.org/dwc/terms/family] => 
    [http://rs.tdwg.org/dwc/terms/genus] => 
    [http://rs.tdwg.org/dwc/terms/specificEpithet] => 
    [http://rs.tdwg.org/dwc/terms/infraspecificEpithet] => 
    [http://rs.tdwg.org/dwc/terms/taxonRank] => 
    [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => 
    [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => 
    [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
    [http://purl.org/dc/terms/language] => 
    [http://purl.org/dc/terms/license] => 
    [http://purl.org/dc/terms/rightsHolder] => 
    [http://purl.org/dc/terms/bibliographicCitation] => 
    [http://rs.tdwg.org/dwc/terms/datasetID] => 
    [http://rs.tdwg.org/dwc/terms/datasetName] => 
    [http://purl.org/dc/terms/references] => 
)
*/
?>
