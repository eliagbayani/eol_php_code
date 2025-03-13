Files from: /Volumes/Crucial_2TB/eol_php_code_tmp2/Content_Import_26/North_American_Flora/
Steps:
1. manually created NorthAmericanFlora_2025.tar.gz from: NorthAmericanFlora_All.tar.gz
2. removed these extensions for NorthAmericanFlora_2025:
    - association.tab
    - measurement_or_fact_specific.tab
    - occurrence_specific.tab
    What remains:
    - taxon.tab
    - media_resource.tab

3. edited meta.xml accordingly. Removed entries for association, measurement_or_fact_specific and occurrence_specific tabs.
4. then created the .tar.gz file. Run in command-line:
    $ tar -czf NorthAmericanFlora_2025.tar.gz NorthAmericanFlora_2025/    

5. then run in eol-archive Jenkins:
php environments_2_eol.php jenkins '{"task": "generate_eol_tags_pensoft", "resource":"all_BHL", "resource_id":"NorthAmericanFlora_2025", "subjects":"Description|Uses"}'
to generate: NorthAmericanFlora_2025_ENV.tar.gz
This will have MoF with
    measurementType == http://purl.obolibrary.org/obo/RO_0002303
    measurementType == http://eol.org/schema/terms/Present

6. then run dwca_remove_MoF_records.php use NorthAmericanFlora_All.tar.gz as input.
This will generate NorthAmericanFlora_All_subset.tar.gz, which is identical to NorthAmericanFlora_All.tar.gz 
but without the MoF records with: 
    measurementType == http://purl.obolibrary.org/obo/RO_0002303
    measurementType == http://eol.org/schema/terms/Present

7. Now run aggregate script (aggregate_NorthAF_2025.php) to add 2 DwCAs: 
    1. NorthAmericanFlora_2025_ENV.tar.gz
    2. NorthAmericanFlora_All_subset.tar.gz
    Sum = NorthAmericanFlora_All_2025.tar.gz
8. -end-