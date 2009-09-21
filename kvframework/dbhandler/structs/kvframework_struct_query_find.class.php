<?php
/**
 * Database query struct for the KvFramework.
 * @package KvFramework
 * @subpackage DBHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Database select query struct
 *
 * @package KvFramework
 * @subpackage DBHandler
 */
class kvframework_struct_query_find extends kvframework_struct_query {

  /**
   * Allowed fields
   *
   * @var array
   */
  protected $FIELDS = array(
    "conditions" => "",
    "order" => "",
    "limit" => "",
    "fields" => array(),
	  "parameters" => array()
  );

  /**
   * Query action
   *
   * @var string
   */
  public $action = "find";
}

?>
