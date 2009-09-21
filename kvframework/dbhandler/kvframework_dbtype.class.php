<?php
/**
 * Database base type for the KvFramework.
 * @package KvFramework
 * @subpackage DBHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

load_files(
  KVF_FRAMEWORK_DIR."/dbhandler/kvframework_db_object.class.php",
  KVF_FRAMEWORK_DIR."/dbhandler/kvframework_db_query.class.php",
  KVF_FRAMEWORK_DIR."/dbhandler/structs/kvframework_struct_query.class.php",
  KVF_FRAMEWORK_DIR."/dbhandler/structs/kvframework_struct_query_find.class.php",
  KVF_FRAMEWORK_DIR."/dbhandler/structs/kvframework_struct_query_delete.class.php",
  KVF_FRAMEWORK_DIR."/dbhandler/structs/kvframework_struct_query_insert.class.php",
  KVF_FRAMEWORK_DIR."/dbhandler/structs/kvframework_struct_query_update.class.php",
  KVF_FRAMEWORK_DIR."/dbhandler/exceptions/kvframework_db_exception.class.php"
);

/**
 * Base class for database types
 * @package KvFramework
 * @subpackage DBHandler
 */
abstract class kvframework_dbtype{

  /**
   * Constructor.  Logs initialization.
   *
   */
  function __construct(){
    kvframework_log::write_log(get_class($this)." initialized.", KVF_LOG_LDEBUG);
  }

  /**
   * Execute an SQL query
   *
   * @param string $sql
   */
  abstract public function query($sql);
  /**
   * Fetch a database record from a query as an object
   *
   * @param kvframework_db_query $qid
   */
  abstract public function fetch(kvframework_db_query $qid);
  /**
   * Fetch a database record from a query as an array
   *
   * @param kvframework_db_query $qid
   */
  abstract public function fetch_array(kvframework_db_query $qid);
  /**
   * Get the number of rows returned for a query
   *
   * @param kvframework_db_query $qid
   */
  abstract public function rows(kvframework_db_query $qid);
  /**
   * Get the error from a query
   *
   * @param string $ret
   */
  abstract public function error($ret);
  /**
   * Return the number of rows affected by the last query
   *
   */
  abstract public function affected();
  /**
   * Return the last inserted id
   *
   */
  abstract public function inserted();
  /**
   * Escape the parameter so it is safe to insert into a query
   *
   * @param mixed $val
   */
  abstract public function escape($val);
  /**
   * Process a query struct according to its type.
   *
   * @param kvframework_struct_query $query
   */
  abstract public function process(kvframework_struct_query $query);

  /**
   * Generate a query struct for a find query
   *
   * @param array $tables
   * @return kvframework_struct_query_find
   */
  public function find_query(array $tables){
    return new kvframework_struct_query_find($tables);
  }

  /**
   * Generate a query struct for a delete query
   *
   * @param array $tables
   * @return kvframework_query_struct_delete
   */
  public function delete_query(array $tables){
    return new kvframework_struct_query_delete($tables);
  }

  /**
   * Generate a query struct for an insert query
   *
   * @param array $tables
   * @return kvframework_struct_query_insert
   */
  public function insert_query(array $tables){
    return new kvframework_struct_query_insert($tables);
  }

  /**
   * Generate a query struct for an update query
   *
   * @param array $tables
   * @return kvframework_struct_query_update
   */
  public function update_query(array $tables){
    return new kvframework_struct_query_update($tables);
  }

}

?>
