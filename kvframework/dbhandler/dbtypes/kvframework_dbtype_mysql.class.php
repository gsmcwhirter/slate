<?php
/**
 * Database type for the KvFramework.
 * @package KvFramework
 * @subpackage DBHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Database type - MySQL
 *
 * @package KvFramework
 * @subpackage DBHandler
 */
class kvframework_dbtype_mysql extends kvframework_dbtype {
  /**
   * Database connection
   *
   * @var mixed
   */
  private $dbc;
  /**
   * Got me...
   *
   * @var array
   */
  private $tables_fields = array();

  /**
   * Constructor - connect to the database
   *
   * @param array $dbinfo Database connection info
   */
  public function __construct(array $dbinfo){
    //$this->dbc = @mysql_connect($dbinfo['dbhost'], $dbinfo['dbuser'], $dbinfo['dbpass']);
    //@mysql_select_db($dbinfo['dbname'], $this->dbc);
    $this->dbc = new mysqli($dbinfo['dbhost'], $dbinfo['dbuser'], $dbinfo['dbpass'], $dbinfo['dbname']);
    parent::__construct();
  }

  /**
   * Process a query struct
   *
   * @param kvframework_struct_query $query
   * @return mixed
   */
  public function process(kvframework_struct_query $query){
    switch($query->action){
      case "find":
        $sql = "SELECT ".((count($query->fields) > 0) ? implode(", ", $query->fields) : "*")." FROM ".implode(", ", $query->tables).(($query->conditions != "") ? " WHERE ".$query->conditions : "").(($query->order != "") ? " ORDER BY ". $query->order : "").(($query->limit != "") ? " LIMIT ".$query->limit : "");
        $q = $this->query($sql);
        if($query->limit == "1"){
          $ret = array();
          while($row = $this->fetch($q)){
            $ret[] = $row;
          }
        } else {
          $ret = $this->fetch($q);
        }
        return $ret;
        break;
      case "delete":
        $sql = "DELETE FROM ".implode(", ",$query->tables).(($query->conditions != "") ? " WHERE ".$query->conditions : "").(($query->limit != "") ? " LIMIT ".$query->limit : "");
        return $this->query($sql);
        break;
      case "insert":
        $ks = "";
        $vs = "";
        foreach($query->fields as $k => $v){
          $ks .= "`".$k."`, ";
          $vs .= "'".$this->escape($v)."', ";
        }
        $ks = substr($ks, 0, -2);
        $vs = substr($vs, 0, -2);
        $sql = "INSERT INTO ".implode(", ",$query->tables)." (".$ks.") VALUES (".$vs.")";
        $q = $this->query($sql);
        if($this->affected() > 0){return $this->inserted();}
        else{return false;}
        break;
      case "update":
        $flds = "";
        foreach($query->fields as $k => $v){
          $flds .= $k." = '".$this->escape($v)."', ";
        }
        $flds = substr($flds, 0, -2);
        $sql = "UPDATE ".implode(", ",$query->tables)." SET ".$flds." WHERE ".$query->conditions.(($query->limit != "") ? " LIMIT ".$query->limit : "");
        $q = $this->query($sql);
        if($q){return true;}
        else{return false;}
        break;
    }
  }

  /**
   * Execute a SQL statement
   *
   * @param string $sql
   * @return mixed
   */
  public function query($sql){
    $tstart = microtime(true);
    $query = $this->dbc->query($sql);
    $tstop = microtime(true);

    kvframework_log::write_log($sql." (".($tstop - $tstart)."s)", KVF_LOG_LINFO, array("sql"), "SQL");

    if(!$query){throw new kvframework_db_exception($this->error("text"), $this->error("number"));}
    return ($query instanceOf mysqli_result) ? new kvframework_db_query($query) : $query;
  }

  /**
   * Fetch a result row as an object
   *
   * @param kvframework_db_query $qid
   * @return mixed
   */
  public function fetch(kvframework_db_query $qid){
    $row = (!$qid->is_closed()) ? $qid->fetch_object("kvframework_db_object") : null;
    if(!$qid->is_closed() && (!$row || $this->rows($qid) == 1)){$qid->close();}
    return $row;
  }

  /**
   * Fetch a result row as an associative array
   *
   * @param kvframework_db_query $qid
   * @return mixed
   */
  public function fetch_array(kvframework_db_query $qid){
    $row = (!$qid->is_closed()) ? $qid->fetch_assoc() : null;
    if(!$qid->is_closed() && (!$row || $this->rows($qid) == 1)){$qid->close();}
    return $row;
  }

  /**
   * Get the number of rows in a result set
   *
   * @param kvframework_db_query $qid
   * @return integer
   */
  public function rows(kvframework_db_query $qid){
    $number = $qid->num_rows;
    return $number;
  }

  /**
   * Return the last error
   *
   * @param string $ret
   * @return mixed
   */
  public function error($ret){
    if($ret == "text"){ return $this->dbc->error;}
    elseif($ret == "number"){ return $this->dbc->errno;}
    elseif($ret == "array"){ return array($this->dbc->error, $this->dbc->errno);}
    else{ return "";}
  }

  /**
   * Get the number of rows affected by the last query
   *
   * @return integer
   */
  public function affected(){
    $number = $this->dbc->affected_rows;
    return $number;
  }

  /**
   * Get the last ID inserted
   *
   * @return integer
   */
  public function inserted(){
    $number = $this->dbc->insert_id;
    return $number;
  }

  /**
   * Escape a value for inclusion in a SQL statement
   *
   * @param mixed $val
   * @return mixed
   */
  public function escape($val){
    return @mysqli_real_escape_string($this->dbc, $val);
  }
}
?>
