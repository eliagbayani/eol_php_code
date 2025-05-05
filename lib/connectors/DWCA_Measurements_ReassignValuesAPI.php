<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from first client: dwca_MoF_reassign_values.php] 

Purpose of this API is described in this ticket: https://github.com/EOL/ContentImport/issues/28
First client for MADtraits:
    for records where measurementValue=
        http://eol.org/schema/terms/lecithotrophic
        OR
        http://eol.org/schema/terms/planktotrophic

    Please change measurementType   from:   http://eol.org/schema/terms/TrophicGuild
                                    TO:     http://eol.org/schema/terms/MarineLarvalDevelopmentStrategy
*/
class DWCA_Measurements_ReassignValuesAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        $this->download_options = array('cache' => 1, 'resource_id' => $resource_id, 'expire_seconds' => 60*60*24*30*4, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        $this->debug = array();
    }
    function start($info)
    {   echo "\nDWCA_Measurements_ReassignValuesAPI...\n";
        $tables = $info['harvester']->tables;
        if($this->resource_id == 'natdb_temp_1') { //MADtraits
            self::process_extension($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF', 'write_MADtraits');
        }
        else exit("\nResource ID not initialized [DWCA_Measurements_ReassignValuesAPI]\n");
    }
    private function process_extension($meta, $class, $what)
    {   //print_r($meta);
        echo "\nprocess_extension [$class][$what]...DWCA_Measurements_ReassignValuesAPI...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                // /* some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field['term']);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];
                // */
                if(!$field) continue;
                $rec[$field] = $tmp[$k];
                $k++;
            } //print_r($rec); exit;
            /*Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => M315930
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => CT100000
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://eol.org/schema/parentMeasurementID] => 
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.geonames.org/6252001
                [http://purl.org/dc/terms/source] => https://www.gbif.org/occurrence/map?taxon_key=9576216&geometry=POLYGON((-90.706%2029.151%2C%20-122.761%2047.269%2C%20-75.09%2038.321%2C%20-81.461%2030.757%2C%20-90.706%2029.151%2C%20-90.706%2029.151))
                [http://purl.org/dc/terms/contributor] => Compiler: Anne E Thessen
                [http://eol.org/schema/reference/referenceID] => R01|R02
            )*/
            //===========================================================================================================================================================
            //===========================================================================================================================================================
            if($what == 'build-up') {
                if($class == 'MoF') {
                    $this->measurementIDs[$rec['http://rs.tdwg.org/dwc/terms/measurementID']] = '';
                }
            }
            elseif($what == 'write_MADtraits') {
                if($class == 'MoF') {
                    $measurementValue = $rec['http://rs.tdwg.org/dwc/terms/measurementValue'];
                    $measurementType = $rec['http://rs.tdwg.org/dwc/terms/measurementType'];
                    if(in_array($measurementValue, array('http://eol.org/schema/terms/lecithotrophic', 'http://eol.org/schema/terms/planktotrophic'))) {
                        if($measurementType == 'http://eol.org/schema/terms/TrophicGuild') $rec['http://rs.tdwg.org/dwc/terms/measurementType'] = 'http://eol.org/schema/terms/MarineLarvalDevelopmentStrategy';
                        else                                                               $rec['http://rs.tdwg.org/dwc/terms/measurementType'] = 'http://eol.org/schema/terms/MarineLarvalDevelopmentStrategy'; //assign it anyway
                    }                    
                }
                
                if($class == 'MoF')             $o = new \eol_schema\MeasurementOrFact_specific();
                elseif($class == 'occurrence')  $o = new \eol_schema\Occurrence_specific();
                elseif($class == 'reference')   $o = new \eol_schema\Reference();
                $uris = array_keys($rec); //print_r($uris); exit("\ndito eli\n");
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $o->$field = $rec[$uri];
                }
                $this->archive_builder->write_object_to_file($o);
            }
        }
    }
}
?>