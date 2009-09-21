<?php
/**
 * KvScheduler - SchedulerBlock
 * @package KvScheduler
 * @subpackage Modules.Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * A struct for a block on the graphical schedule
 *
 * @package KvScheduler
 * @subpackage Modules.Lib
 */
class SchedulerBlock{

  /**
   * Block information
   *
   * @var array
   */
  protected $INFO = array(
    "status" => "",
    "hstatus" => "",
    "content" => "&nbsp;",
    "span" => 1,
    "hmodified" => 0,
    "amodified" => 0,
    "meta" => null,
    "h24" => ""
  );

  /**
   * PHP magic function
   *
   * @param string $n
   * @return mixed
   */
  public function __get($n){
    return $this->INFO[$n];
  }

  /**
   * PHP Magic function
   *
   * @param string $n
   * @param mixed $v
   * @return boolean false
   */
  function __set($n, $v){
    return false;
  }

  /**
   * Constructor
   *
   * @param integer $type
   */
  function __construct($type = 1){
    if($type == 0){
      $this->INFO["status"] = "L";
    } else {
      $this->INFO["status"] = "K";
    }
  }

  /**
   * Set content for the block
   *
   * @param mixed $value
   */
  public function set_content($value){
    $this->INFO["content"] = $value;
  }

  /**
   * Set time of the block
   *
   * @param mixed $value
   */
  public function set_h24($value){
    $this->INFO["h24"] = $value;
  }

  /**
   * Set the status of a block
   *
   * @param string $status
   */
  public function set_status($status){
    if(preg_match('/^[ABCIKLO]{1}$/', $status)){
      $this->INFO["status"] = $status;
      if($status == "A" || $status == "C" || $status == "K"){
        $this->INFO["hstatus"] = $status;
      }
    } else {
      throw new Exception("Bad SchedulerBlock status value $status");
    }
  }

  /**
   * Set the hmodified of a block
   *
   * @param mixed $value
   */
  public function set_hmodified($value){
    if(is_int($value)){
      $this->INFO["hmodified"] = $value;
    } else {
      throw new Exception("Bad SchedulerBlock hmodified value $value");
    }
  }

  /**
   * Set the amodified of a block
   *
   * @param mixed $value
   */
  public function set_amodified($value){
    if(is_int($value)){
      $this->INFO["amodified"] = $value;
    } else {
      throw new Exception("Bad SchedulerBlock amodified value $value");
    }
  }

  /**
   * Set the span of a block
   *
   * @param mixed $value
   */
  public function set_span($value){
    if(is_int($value) && $value > 0){
      $this->INFO["span"] = $value;
    } else {
      throw new Exception("Bad SchedulerBlock span value $value");
    }
  }

  /**
   * Set the meta of a block
   *
   * @param mixed $data
   */
  public function set_meta($data){
    $this->INFO["meta"] = $data;
  }

}
?>
