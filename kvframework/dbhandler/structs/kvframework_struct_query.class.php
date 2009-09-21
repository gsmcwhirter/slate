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
 * Database base query struct
 *
 * @package KvFramework
 * @subpackage DBHandler
 */
abstract class kvframework_struct_query extends struct {

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
    "include" => array(),
	  "parameters" => array()
  );

  /**
   * Variables allowed on construct
   *
   * @var array
   */
  protected $VARS = array(
    "action",
    "table"
  );

  /**
   * Query action
   *
   * @var string
   */
  public $action;
  /**
   * Query tables
   *
   * @var array
   */
  public $tables = array();

  /**
   * Constructor
   *
   * @param array $tables Tables for the query
   * @param array $new_defaults New default variable values
   */
  function __construct(array $tables, array $new_defaults = array()){
    if(array_key_exists("action", $this->FIELDS)){unset($this->FIELDS["action"]);}
    if(array_key_exists("table", $this->FIELDS)){unset($this->FIELDS["table"]);}
    parent::__construct($new_defaults);
    $this->tables = $tables;
  }
}

?>
