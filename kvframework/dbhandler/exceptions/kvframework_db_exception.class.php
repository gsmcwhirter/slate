<?php
/**
 * Database query exception for the KvFramework.
 * @package KvFramework
 * @subpackage DBHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Database query exception
 *
 * @package KvFramework
 * @subpackage DBHandler
 */
class kvframework_db_exception extends Exception {
  /**
   * Constructor
   *
   * @param mixed $message Error message
   * @param mixed $code Error code
   */
  function __construct($message, $code = 0){
    parent::__construct($message, $code);
  }
}
?>
