<?php
/**
 * KvScheduler - Thing abstaction
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * An object with a ->starttime, ->stoptime, ->begin, and ->end
 *
 * @package KvScheduler
 * @subpackage Lib
 */
class Thing{

  /**#@+
   * @var integer
   */
  /**
   * Start time
   */
  public $starttime;
  /**
   * Stop time
   */
  public $stoptime;
  /**
   * Begin time
   */
  public $begin;
  /**
   * End time
   */
  public $end;
  /**#@-*/

  /**
   * Constructor
   * @param integer $start
   * @param integer $stop
   */
  public function __construct($start, $stop){
   $this->starttime = $start;
   $this->stoptime = $stop;
   $this->begin = $start;
   $this->end = $stop;
  }
}
?>
