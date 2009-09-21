<?php
/**
 * A Struct type abstract class
 * @package KvFramework
 * @subpackage Extensions
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

 /**
  * A struct type abstract class
  * @package KvFramework
  * @subpackage Extensions
  */
abstract class struct {

  /**
   * @var array Array of allowed properties of the struct
   */
  protected $FIELDS = array();
  /**
   * @var array Array of allowed variable of the struct
   */
  protected $VARS = array();
  /**
   * @var array Array of current values of the Fields
   */
  protected $DATA = array();

  /**
   * Constructor
   * @param array $new_defaults Override the allowed fields
   */
  public function __construct(array $new_defaults = array()){
    if(!is_array($this->FIELDS)){$this->FIELDS = array();}
    $this->FIELDS = array_merge($this->FIELDS, array_intersect_key($new_defaults, $this->FIELDS));
    foreach($this->FIELDS as $f => $d){
      $this->DATA[$f] = $d;
    }
  }

  /**
   * PHP magic function for getting a property value
   */
  public function __get($m){
    return(($this->var_allowed($m)) ? $this->DATA[$m] : null);
  }

  /**
   * PHP magic function for setting a property value
   */
  public function __set($m, $v){
    if($this->var_allowed($m) && gettype($v) == gettype($this->$m)){
      $this->DATA[$m] = $v;
    }
  }

  /**
   * PHP magic function for calling a method
   */
  public function __call($m, $a){
    return null;
  }

  /**
   * PHP magic function for unsetting a property
   */
  public function __unset($m){

  }

  /**
   * Determine whether a property is allowed to be get / set
   */
  protected function var_allowed($m){
    return((in_array($m, array_keys($this->FIELDS)) || in_array($m, $this->VARS)) ? true : false );
  }

}
?>
