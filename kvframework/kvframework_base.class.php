<?php
/**
 * Base class for the KvFramework.
 * @package KvFramework
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Base class for many of the KvFramework files.
 * @package KvFramework
 * @static
 */
abstract class kvframework_base {

  /**
   * @staticvar nakor_core
   */
  protected static $NAKOR_CORE;
  /**
   * @staticvar kvframework_dbtype
   */
  protected static $DB;
  /**
   * @staticvar array
   */
  protected static $MESSAGES = array("msgs" => array(), "errors" => array());
  /**
   * @staticvar boolean
   */
  private static $CONNECTED = false;
  /**
   * @staticvar array
   */
  protected static $validate_errors = array();
  /**
    * A cleaned copy of $_POST
    * @staticvar array
    */
  protected static $POST;
  /**
    * A cleaned copy of $_GET
    * @staticvar array
    */
  protected static $GET;
  /**
    * A copy of $_FILES
    * @staticvar array
    */
  protected static $FILES;
  /**
    * A copy of $_COOKIE
    * @staticvar array
    */
  protected static $COOKIES;
  /**
    * Copy of $_SERVER
    * @staticvar $SERVER
    */
  protected static $SERVER;
  /**
    * $_GET["url"]: the mod_rewrite of the URI request
    * @staticvar string
    */
  protected static $REQUEST;
  /**
    * An amalgamation of $_GET, $_POST, and parsing URI arguments
    * @staticvar array
    */
  protected static $PARAMS = array();

  /**
   * Connects the database to $DB and initializes $NAKOR_CORE
   * @param kvframework_dbtype $db This is the connection.
   * @return boolean true
   */
  final public static function startup_base(kvframework_dbtype &$db){
    self::$NAKOR_CORE = new nakor_core;
    if(!self::$CONNECTED){self::$DB = $db; self::$CONNECTED = true;}
    self::setvars();
    return true;
  }

  /**
    * Initialize the static variables
    * @return boolean true
    */
  private static function setvars(){
    self::$POST = self::$NAKOR_CORE->clean_input("POST");
    self::$GET = self::$NAKOR_CORE->clean_input("GET");
    self::$SERVER = $_SERVER;
    self::$FILES = $_FILES;
    self::$COOKIES = $_COOKIE;
    self::$REQUEST = self::$GET["url"];
    if(KVF_COMPAT){
      self::$SERVER["REQUEST_METHOD"] = "GET";
    }
    return true;
  }

  /**
   * Saves and error to the messages["errors"] array
   * @param string $msg Text to save to the errors
   * @return boolean true
   */
  final protected static function throwError($msg){
    self::$MESSAGES["errors"][] = $msg;
    self::$MESSAGES["errors"] = array_unique(self::$MESSAGES["errors"]);
    return true;
  }

  /**
   * Saves and error to the messages["msgs"] array
   * @param string $msg Text to save to the messages
   * @return boolean true
   */
  final protected static function throwMessage($msg){
    self::$MESSAGES["msgs"][] = $msg;
    self::$MESSAGES["msgs"] = array_unique(self::$MESSAGES["msgs"]);
    return true;
  }

  /**
   * Returns whether or not any errors have been logged
   * @return boolean
   */
  final protected static function is_errors(){
    return(count(self::$MESSAGES["errors"]) > 0);
  }

  /**
   * Determine whether any validations threw errors and throw an exception if so.
   * @return boolean true
   */
  final public static function validate_resolve(){
    if(count(self::$validate_errors) > 0){
      $validate_errors = self::$validate_errors;
      self::$validate_errors = array();
      throw new validation_exception("Validation Error", $validate_errors);
    } else {
      return true;
    }
  }

  /**
   * Valdiate the presence of keys and the significance of values in an array
   * @param array $fields Array of keys to check
   * @param array $in Array in which to check the keys
   * @param mixed $message Message to throw an error with, or null to use default
   * @return boolean true
   */
  final public static function validate_presence_of(array $fields, array $in, $message = null){
    foreach($fields as $f){
      if(!array_key_exists($f, $in) || is_null($in[$f])){
        self::$validate_errors[] = (is_null($message)) ? $f." can not be empty." : $f." ".$message;
      }
    }
    return true;
  }

  /**
   * Validates the confirmation of certain fields (passwords, emails, etc)
   * @param array $fields Array of fields to check the confirmation of
   * @param array $in Array in which to check
   * @param mixed $message Message to throw an error with, or null to use the default.
   * @return boolean true
   */
  final public static function validate_confirmation_of(array $fields, array $in, $message = null){
    foreach($fields as $f){
      if($in[$f] != $in[$f."_confirmation"]){
        self::$validate_errors[] = (is_null($message)) ? $f." did not match its confirmation." : $f." ".$message;
      }
    }

    return true;
  }

  /**
   * Validate some keys by a callback function
   * @param array $fields Array of keys to check
   * @param array $in Array in which to check the keys
   * @param callback $callback Functional check to apply
   * @param mixed $message Message with which to throw error, or null for default.
   * @param array $extraparams Extra parameters to pass the callback
   * @return boolean true;
   */
  final public static function validate_callback_on(array $fields, array $in, $callback, $message = null, array $extraparams = array()){
    foreach($fields as $f){
      if(!call_user_func_array($callback, array_merge(array($in[$f]), $extraparams))){
        self::$validate_errors[] = (is_null($message)) ? $f ." failed callback by ".$callback : $f." ".$message;
      }
    }

    return true;
  }

	/**
   * Validate some keys by a negated callback function
   * @param array $fields Array of keys to check
   * @param array $in Array in which to check the keys
   * @param callback $callback Functional check to apply
   * @param mixed $message Message with which to throw error, or null for default.
   * @param array $extraparams Extra parameters to pass the callback
   * @return boolean true;
   */
  final public static function validate_callback_neg_on(array $fields, array $in, $callback, $message = null, array $extraparams = array()){
    foreach($fields as $f){
      if(call_user_func_array($callback, array_merge(array($in[$f]), $extraparams))){
        self::$validate_errors[] = (is_null($message)) ? $f ." failed callback by ".$callback : $f." ".$message;
      }
    }

    return true;
  }

  /**
   * Validates existence of some row in the database
   * @param string $name Description of the fields
   * @param array $conds Array of conditions to build the query from
   * @param string $table Table to query
   * @param mixed $message Message with which to throw error, or null for default
   */
  final public static function validate_exists_in_db($name, array $conds, $table, $message = null){
    $where = "";
    foreach($conds as $fld => $val){
      $where .= $fld." = '".$val."' AND ";
    }
    $sql = "SELECT * FROM ".$table." WHERE ".substr($where, 0, -4)." LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) != 1){
      self::$validate_errors[] = (is_null($message)) ? $name." not found in ". $table."." : $name." ".$message;
    }

    return true;
  }

  /**
   * Validates uniqueness of a value in the database
   * @param string $name Description of the field
   * @param mixed $val Value of the field
   * @param string $table Name of the table
   * @param string $field Name of the field
   * @param mixed $message Message with which to throw an error, or null for default
   * @param mixed $id ID field value for checking except a certain row
   * @param string $id_field ID field name for checking except a certain row
   * @return boolean true
   */
  final public static function validate_db_uniqueness_of($name, $val, $table, $field, $message = null, $id = null, $id_field = "id"){
    $sql = "SELECT * FROM ".$table." WHERE ".$field." = '".$val."' ".((!is_null($id)) ? "AND ".$id_field." != '".$id."'" : "")." LIMIT 1";
    kvframework_log::write_log("VAL_DB_!: ".$sql, KVF_LOG_LINFO);
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) >= 1){
      kvframework_log::write_log("VAL_DB_!: Got here", KVF_LOG_LINFO);
      $msg = (is_null($message)) ? $name." is not unique in ".$table."." : $name." ".$message;
      kvframework_log::write_log("VAL_DB_!: ".$msg, KVF_LOG_LINFO);
      self::$validate_errors[] = $msg;
    }

    return true;
  }

  /**
   * Validate the inclusion of a value in an array
   * @param string $name Description of the value to be checked.
   * @param mixed $val Value to be checked
   * @param array $in Array in which to look for the value
   * @param mixed $message Message with which to throw an error, or null for default
   * @return boolean true
   */
  final public static function validate_inclusion_of($name, $val, array $in, $message = null){
    if(!in_array($val, $in)){
      self::$validate_errors[] = (is_null($message)) ? $name." is not in the array." : $name." ".$message;
    }
  }

  final public static function validate_length_of($name, $val, array $conds, $message = null){
    if(array_key_exists("min", $conds) && strlen($val) < $conds["min"]){
      self::$validate_errors[] = (is_null($message)) ? $name." is too short." : $name." ".$message;
    }
    if(array_key_exists("max", $conds) && strlen($val) > $conds["max"]){
      self::$validate_errors[] = (is_null($message)) ? $name." is too long." : $name." ".$message;
    }

    return true;
  }

  /**
   * Serializes an array so its information can be stored as a string.
   * @param array $array Array to serialize
   * @return string Serialized version of $array
   */
  final public static function array_to_string(array $array){
    return serialize($array);
  }

}

/**
 * Exception thrown by the validation functions
 * @package KvFramework
 */
class validation_exception extends Exception{
  /**
   * @var array
   */
  public $errors = array();

  /**
   * Constructor
   * @param string $message Error text
   * @param array $errors Array of error details as strings
   * @param integer $code Error code
   */
  function __construct($message, array $errors, $code = 0){
    parent::__construct($message, $code);
    $this->errors = $errors;
  }
}

?>
