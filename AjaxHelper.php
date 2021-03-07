<?php
define('BASE_PATH', dirname(__FILE__));
require_once BASE_PATH.  "/lib/session.php";
include_once BASE_PATH.  "/config/constants.php";
Session::init();
require_once BASE_PATH.  "/lib/util.php";
include_once BASE_PATH.  "/modules/auth.php";

class AjaxHelper{
  
    protected $db;
    function __construct() {
        $_db_info = getDBClass();
        include_once($_db_info[0]);
        $_db_class = $_db_info[1];
        $this->db = new $_db_class();
        unset($_db_info);
        unset($_db_class); 
    } 
    
    private function setCurrentUserConnection()
    {
        $userName = Session::get('auth', 'user', true);
        $password = Session::get('auth', 'pwd', true);
        $host = Session::get('auth', 'host', true);
        $dbname  = $_POST["dbName"];
        if($this->db->connect($host,  $userName, $password, $dbname, $db))
            return true;
        else
            return false;
    }
    
    public function getTables(){ 
        $dbname  = $_POST["dbName"]; 
        
        if($this->setCurrentUserConnection())
            return $this->db->getTables(false, $dbname);
        else
            return array();
    }
    public function getTableFields(){ 
        $dbname  = $_POST["dbName"];
        $tablename  = $_POST["tablename"]; 
        
        if($this->setCurrentUserConnection())
            return $this->db->getTableFields($dbname.'.'.$tablename, true);
        else
            return array();
    }
}
 $valueExists = new AjaxHelper();

if(isset($_POST['func'])){
   $func = $_POST['func'];
    
   if($func === 'getTables'){
        $result = $valueExists->getTables() ;
        header('Content-Type: application/json');
        echo(json_encode($result));
   }
    if($func === 'getTableFields'){
        $result = $valueExists->getTableFields() ;
        header('Content-Type: application/json');
        echo(json_encode($result));
   }
}

