<?php
/**
 * Logging information struct for the KvFramework.
 * @package KvFramework
 * @subpackage Logger
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Log Info Struct
 *
 * @package KvFramework
 * @subpackage Logger
 */
class kvframework_loginfo_struct extends struct{

  /**
   * Allowed fields
   *
   * @var array
   */
  protected $FIELDS = array(
    "types" => array(),
    "configs" => array(),
    "default" => ""
  );

}
?>
