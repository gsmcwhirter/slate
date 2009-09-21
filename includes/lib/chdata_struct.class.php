<?php
/**
 * KvScheduler - ConsultantHour Data struct
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Consultant Hour data struct
 *
 * @package KvScheduler
 * @subpackage Lib
 */
class chdata_struct extends struct{
  /**
   * Allowed fields
   *
   * @var array
   */
  protected $FIELDS = array(
    "blocks" => array(),
    "things" => array(),
    "consultants" => array()
  );
}

?>
