<?php
namespace php_active_record;
class OpenData
{
    private $mysqli;
    private $mysqli_slave;
    private $content_archive_builder;
    
    public function __construct()
    {   /*
        # config.ini 
        # PHP error reporting. supported values are given below. 
        # 0 - Turn off all error reporting 
        # 1 - Running errors 
        # 2 - Running errors + notices 
        # 3 - All errors except notices and warnings 
        # 4 - All errors except notices 
        # 5 - All errors
        */
        if($GLOBALS['ENV_DEBUG'] == false) error_reporting(0);
        $this->mysqli =& $GLOBALS['db_connection'];
        /*
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => DOC_ROOT . "/temp/eol_names_archive/"));
        */
        /* diff means to access mysql
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        
        $query = "SELECT he.taxon_concept_id, he.rank_id";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row) {}
        
        $result = $this->mysqli_slave->query("SELECT r.id, tr.label FROM ranks r JOIN translated_ranks tr ON (r.id=tr.rank_id) WHERE tr.language_id=". Language::english()->id);
        while($result && $row=$result->fetch_assoc()) {}
        */
    }
    private function write_2text($arr, $WRITE)
    {
        fwrite($WRITE, implode("\t", $arr)."\n");
    }
    function get_id_from_REQUEST_URI($uri)
    {
        $uri = Functions::remove_this_last_char_from_str($uri, '/');
        $arr = explode("/", $uri);
        $possible_id = array_pop($arr);
        if($possible_id == 'resources') {
            // echo "<pre>"; print_r($arr); echo "</pre>";
            return array('id' => array_pop($arr), 'task' => 'get resources');
        }
        else return array('id' => $possible_id, 'task' => false);
    }
    function get_organization_by_id($org_id)
    {
        $sql = "SELECT g.* FROM v259_ckan_V2.group_list g WHERE g.id = '$org_id'";
        self::run_query($sql, 'print json');
    }
    function get_dataset_by_id($dataset_id)
    {
        $sql = "SELECT p.* FROM v259_ckan_V2.package p WHERE p.id = '$dataset_id' AND p.type = 'dataset'";
        if($row = self::run_query($sql)) {
            self::print_json($row);
        }
        else {
            $sql = "SELECT p.* FROM v259_ckan_V2.package p WHERE p.name = '$dataset_id' AND p.type = 'dataset'";
            self::run_query($sql, 'print json');
        }
    }
    function get_resources_from_dataset($info)
    {
        // echo "<pre>"; print_r($info); echo "</pre>";
        if(self::is_this_dataset_id($info['id'])) {
            // $sql = "SELECT r.* FROM v259_ckan_V2.resource r WHERE r.package_id = '".$info['id']."'";
            $sql = "SELECT if(u.url is null, r.url, u.url) as active_url, r.* from v259_ckan_V2.resource r 
            LEFT JOIN v259_ckan_V2.resource_url u ON r.id = u.id WHERE r.package_id = '".$info['id']."'";
            self::run_query_return_all($sql, 'print json', array('id', 'name', 'active_url', 'url', 'description', 'format'));
        }
        else {
            if($val = self::get_dataset_id_using_name($info['id'])) {
                // $sql = "SELECT r.* FROM v259_ckan_V2.resource r WHERE r.package_id = '".$val."'";
                $sql = "SELECT if(u.url is null, r.url, u.url) as active_url, r.* from v259_ckan_V2.resource r 
                LEFT JOIN v259_ckan_V2.resource_url u ON r.id = u.id WHERE r.package_id = '".$val."'";
                
                self::run_query_return_all($sql, 'print json', array('id', 'name', 'active_url', 'url', 'description', 'format'));
            }
            else exit("\nInvestigate no dataset id.\n");
        }
    }
    private function get_dataset_id_using_name($name)
    {
        $sql = "SELECT p.id FROM v259_ckan_V2.package p WHERE p.name = '$name' AND p.type = 'dataset'";
        if($row = self::run_query($sql)) {
            return $row['id'];
        }
    }
    private function is_this_dataset_id($dataset_id)
    {
        $sql = "SELECT p.id FROM v259_ckan_V2.package p WHERE p.id = '$dataset_id' AND p.type = 'dataset'";
        if(self::run_query($sql)) return true;
        else                      return false;
    }
    function get_resource_by_id($resource_id)
    {
        // $sql = "SELECT r.* FROM v259_ckan_V2.resource r WHERE r.id = '$resource_id'";
        $sql = "SELECT if(u.url is null, r.url, u.url) as active_url, r.* from v259_ckan_V2.resource r LEFT JOIN v259_ckan_V2.resource_url u ON r.id = u.id WHERE r.id = '$resource_id'";
        self::run_query($sql, 'print json');
    }
    function get_resource_by_id_v2($resource_id)
    {
        $sql = "SELECT if(u.url is null, r.url, u.url) as active_url, r.* from v259_ckan_V2.resource r LEFT JOIN v259_ckan_V2.resource_url u ON r.id = u.id WHERE r.id = ?";
        echo "<hr>$resource_id<hr>";

        $mysqli = $GLOBALS['db_connection'];
        

        // $db = $this->getConnection()->getDbConnection();
        // $preparedQuery = $db->stmt_init();
        // $preparedQuery->prepare ($query);

        /* check connection */
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit("\n error 01 \n");
        }
        else echo "<hr>good 01<hr>";

        /* create a prepared statement */
        echo "<hr>aaa<hr>";
        // $stmt = $mysqli->prepare();
        // $preparedQuery = $db->stmt_init();
        $stmt =  $this->mysqli->stmt_init();
        // $stmt =  $mysqli->mysqli_stmt_prepare();
        echo "<hr>bbb<hr>";

        // if ($stmt = $mysqli->prepare($sql)) {

        if ($stmt->prepare($sql)) {
            echo "<hr>good 03<hr>";
            // /* bind parameters for markers */
            // $stmt->bind_param("s", $resource_id);
            // /* execute query */
            // $stmt->execute();
            // /* bind result variables */
            // $stmt->bind_result($district);
            // /* fetch value */
            // $stmt->fetch();
            // printf("%s is in \n", $resource_id);
            /* close statement */
            // $stmt->close();
        }
        else echo "<hr>error 03<hr>";
        // 
        // /* close connection */
        // $mysqli->close();



        //===================================

        // echo "<pre>";
        // print_r($this->mysqli);
        // echo "</pre>";

        // $stmt = $this->mysqli->prepare($sql);
        // $stmt->bind_param('s', $resource_id); // 's' specifies the variable type => 'string'
        // $stmt->execute();
        // $result = $stmt->get_result();
        // while ($row = $result->fetch_assoc()) {
        //     print_r($row);
        // }

        // $stmt = $dbConnection->prepare('SELECT * FROM employees WHERE name = ?');
        // $stmt->bind_param('s', $name); // 's' specifies the variable type => 'string'

        // self::run_query($sql, 'print json', $resource_id);
    }
    private function run_query($sql, $next = '')
    {
        $result = $this->mysqli->query($sql);
        if($result && $row=$result->fetch_assoc()) {
            if($GLOBALS['ENV_DEBUG']) {
                echo "<pre>"; print_r($row); echo "</pre>";
            }
            if($next == 'print json') {
                self::print_json($row);
            }
            else return $row;
        }
    }
    private function print_json($row)
    {
        $json = Functions::remove_whitespace(json_encode($row));
        echo $json;
    }
    private function run_query_return_all($sql, $next = '', $sought_fields = false)
    {
        $result = $this->mysqli->query($sql);
        // echo "\n$sql\n";
        while($result && $row=$result->fetch_assoc()) {
            if($sought_fields) {
                $rec = array();
                foreach($sought_fields as $fld) $rec[$fld] = $row[$fld];
                $final[] = $rec;
            }
            else $final[] = $row;
        }
        if($next == 'print json') self::print_json($final);
        else return $final;
    }
    function get_organizations($order_by)
    {
        $sql = "SELECT g.* FROM v259_ckan_V2.group_list g WHERE g.type = 'organization' ORDER BY $order_by";
        self::run_query_return_all($sql, 'print json');
    }
    function get_datasets($order_by)
    {
        $sql = "SELECT p.* FROM v259_ckan_V2.package p WHERE p.type = 'dataset' ORDER BY $order_by";
        self::run_query_return_all($sql, 'print json');
    }
    function get_resources($order_by)
    {
        // $sql = "SELECT r.* FROM v259_ckan_V2.resource r WHERE r.state = 'active' ORDER BY $order_by";
        $sql = "SELECT if(u.url is null, r.url, u.url) as active_url, r.* from v259_ckan_V2.resource r 
        LEFT JOIN v259_ckan_V2.resource_url u ON r.id = u.id WHERE r.state = 'active' ORDER BY $order_by";
        
        self::run_query_return_all($sql, 'print json');
    }
}
?>
