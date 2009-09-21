<?php
/**
 * KvScheduler - ApHash class
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Class representing an abstact data type (checkboxes <-> assoc array <-> string of 1/0)
 *
 * @package KvScheduler
 * @subpackage Lib
 */
class ApHash{

  /**
   * Default value
   *
   */
  const DEFAULT_STR = "111111111100";
  /**
   * the assoc array
   *
   * @var array
   */
  protected $hash = array();
  /**
   * Has the hash been loaded?
   *
   * @var boolean
   */
  protected $loaded = false;

  /**
   * Constructor - load a default value
   *
   * @param mixed $str Default value to load instead of class constant
   */
  function __construct($str = null){
    if(is_null($str)){
      $str = self::DEFAULT_STR;
    }
    $this->load($str);
  }

  /**
   * Get the assoc array for this aphash
   *
   * @return array
   */
  public function hash(){
    return $this->hash;
  }

  /**
   * Load data into the aphash
   *
   * @param mixed $pass Array of 1/0 to turn into a string
   * @return boolean Whether the load was successful
   */
  public function load($pass){
    if(is_array($pass)){
      $str = "";
      for($i=0; $i <= 11; $i++){
        $str .= $pass[(string)($i."")];
      }
    } else {
      $str = $pass;
    }

    if(preg_match("/[^0-1]{1}/", $str)){
      return false;
    } elseif (strlen($str) != 12){
      return false;
    }

    $this->hash["sysop"] = array();
    $this->hash["supervisor"] = array();
    $this->hash["helpdesk"] = array();

    $this->hash["sysop"]["generic"] = (substr($str, 0, 1) == "1") ? true : false;
    $this->hash["sysop"]["comcon"] = (substr($str, 1, 1) == "1") ? true : false;
    $this->hash["sysop"]["wireless"] = (substr($str, 2, 1) == "1") ? true : false;
    $this->hash["sysop"]["other"] = (substr($str, 3, 1) == "1") ? true : false;

    $this->hash["supervisor"]["generic"] = (substr($str, 4, 1) == "1") ? true : false;
    $this->hash["supervisor"]["comcon"] = (substr($str, 5, 1) == "1") ? true : false;
    $this->hash["supervisor"]["wireless"] = (substr($str, 6, 1) == "1") ? true : false;
    $this->hash["supervisor"]["other"] = (substr($str, 7, 1) == "1") ? true : false;

    $this->hash["helpdesk"]["generic"] = (substr($str, 8, 1) == "1") ? true : false;
    $this->hash["helpdesk"]["comcon"] = (substr($str, 9, 1) == "1") ? true : false;
    $this->hash["helpdesk"]["wireless"] = (substr($str, 10, 1) == "1") ? true : false;
    $this->hash["helpdesk"]["other"] = (substr($str, 11, 1) == "1") ? true : false;

    $this->loaded = true;

    return true;
  }

  /**
   * Turn the aphash data into a string
   *
   * @return string
   */
  public function save(){
    if($this->loaded){
      $str = "";
      $str .= ($this->hash["sysop"]["generic"]) ? "1" : "0";
      $str .= ($this->hash["sysop"]["comcon"]) ? "1" : "0";
      $str .= ($this->hash["sysop"]["wireless"]) ? "1" : "0";
      $str .= ($this->hash["sysop"]["other"]) ? "1" : "0";

      $str .= ($this->hash["supervisor"]["generic"]) ? "1" : "0";
      $str .= ($this->hash["supervisor"]["comcon"]) ? "1" : "0";
      $str .= ($this->hash["supervisor"]["wireless"]) ? "1" : "0";
      $str .= ($this->hash["supervisor"]["other"]) ? "1" : "0";

      $str .= ($this->hash["helpdesk"]["generic"]) ? "1" : "0";
      $str .= ($this->hash["helpdesk"]["comcon"]) ? "1" : "0";
      $str .= ($this->hash["helpdesk"]["wireless"]) ? "1" : "0";
      $str .= ($this->hash["helpdesk"]["other"]) ? "1" : "0";

      return $str;
    } else {
      return self::DEFAULT_STR;
    }
  }

  /**
   * Convert checkbox inputs to a string
   *
   * @param array $cb_array Array passed by the checkbox inputs
   * @return string
   */
  public static function checkbox_input_to_string(array $cb_array){
    $ret = "";
    for($i = 1; $i < 13; $i++){
      $ret .= $cb_array[$i];
    }

    return $ret;
  }
}

?>
