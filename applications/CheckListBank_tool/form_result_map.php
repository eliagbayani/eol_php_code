<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();

// /* normal operation
ini_set('error_reporting', false);
ini_set('display_errors', false);
$GLOBALS['ENV_DEBUG'] = false; //set to false in production
// */

// /* during development
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
$GLOBALS['ENV_DEBUG'] = true; //set to true during development
// */
$time_var = time();

$form = $_POST;
echo "<pre>"; print_r($form); echo "</pre>"; //exit("\neli 200\n");
/*Array(
    [taxonRank] => Array(
            [0] => Kingdom
            [1] => Phylum
            [2] => Class
            [3] => Order
            [4] => Superfamily
            [5] => Family
            [6] => Genus
            [7] => Species
        )
    [taxonomicStatus] => Array(
            [0] => accepted
            [1] => 
            [2] => misapplied
            [3] => 
        )
    [locality] => Array(
            [0] => 
            [1] => Australia
            [2] => 
            [19] => Western Atlantic Ocean
            [20] => Western Atlantic Ocean
        )
    [occurrenceStatus] => Array(
            [0] => Native
        )
)*/

if($resource_id = @get_val_var('resource_id')) {
    echo "\nid: $resource_id\n";
}
else echo "\nNo resource_id\n";

function get_val_var($v)
{
    if     (isset($_GET["$v"])) $var = $_GET["$v"];
    elseif (isset($_POST["$v"])) $var = $_POST["$v"];
    if(isset($var)) return $var;
    else return NULL;
}
?>