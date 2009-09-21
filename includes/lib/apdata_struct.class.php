<?php
/**
 * KvScheduler - Report PDF generator
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Struct of appointment data
 *
 * @package KvScheduler
 * @subpackage Lib
 */
class apdata_struct extends struct{
  /**
   * Allowed fields
   *
   * @var array
   */
  protected $FIELDS = array(
    "blocks" => array(),
    "things" => array()
  );
}

?>
