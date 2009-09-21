<?php
/**
 * Database data object for the KvFramework.
 * @package KvFramework
 * @subpackage DBHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.comgsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Database data object
 *
 * @package KvFramework
 * @subpackage DBHandler
 */
class kvframework_db_object {

  /**
   * Holds the actual data
   *
   * @var array
   */
  private $DATA = array();
  /**
   * Holds an array of appointments
   *
   * @var mixed
   */
  public $appointments;
  /**
   * Holds an array of appttypes
   *
   * @var  mixed
   */
  public $appttypes;
  /**
   * Holds an array of locations
   *
   * @var  mixed
   */
  public $locations;
  /**
   * Holds an array of metalocs
   *
   * @var  mixed
   */
  public $metalocs;
  /**
   * Holds an array of consultants
   *
   * @var  mixed
   */
  public $consultants;
  /**
   * Holds an array of consultantappts
   *
   * @var  mixed
   */
  public $consultantappts;
  /**
   * Holds an array of consultanthours
   *
   * @var  mixed
   */
  public $consultanthours;
  /**
   * Holds a loczone
   *
   * @var  mixed
   */
  public $loczone;
  /**
   * Holds a location
   *
   * @var  mixed
   */
  public $location;
  /**
   * Holds a tm
   *
   * @var  mixed
   */
  public $tm;
  /**
   * Holds a tag
   *
   * @var  mixed
   */
  public $tag;
  /**
   * Holds location tag array
   *
   * @var mixed
   */
  public $loctags;


  /**
   * Constructor.  Does nothing
   *
   */
  function __construct(){
  }

  /**
   * PHP Magic function
   *
   * @param mixed $nm Name of the value
   * @param mixed $val Value
   */
  public function __set($nm, $val){
    if(!in_array($nm, array_keys(get_object_vars($this)))){
      $this->DATA[$nm] = $val;
    } else {
      $this->$nm = $val;
    }
    kvframework_log::write_log("DB OBJECT: set called with (".serialize($nm).",".serialize($val).")", KVF_LOG_LDEBUG);
  }

  /**
   * PHP Magic function
   *
   * @param mixed $nm Name of the value
   * @return mixed
   */
  public function __get($nm){
    if(in_array($nm, array_keys(get_object_vars($this)))){
      return $this->$nm;
    } else {
      return (array_key_exists($nm, $this->DATA)) ? $this->DATA[$nm] : null;
    }
  }

  /**
   * PHP Magic function
   *
   * @param mixed $nm Name of the value
   * @return boolean
   */
  public function __isset($nm){
    return isset($this->DATA[$nm]);
  }

  /**
   * PHP Magic function
   *
   * @param mixed $nm Name of the value
   */
  public function __unset($nm){
    unset($this->DATA[$nm]);
  }
}
?>
