<?php
/**
 * Database query object for the KvFramework.
 * @package KvFramework
 * @subpackage DBHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Database query object
 *
 * @package KvFramework
 * @subpackage DBHandler
 */
class kvframework_db_query{
  /**
   * Is the query closed or not?
   *
   * @var boolean
   */
  protected $closed = false;
  /**
   * The query object
   *
   * @var mixed
   */
  protected $query = null;

  /**
   * Constructor.  Saves the passed query to the instance
   *
   * @param mixed $query
   */
  function __construct($query){
    $this->query = $query;
  }

  /**
   * Is the query closed?
   *
   * @return boolean
   */
  public function is_closed(){
    return $this->closed;
  }

  /**
   * Close the query
   *
   */
  public function close(){
    if(is_callable(array($this->query, "close"), false)){
      $this->query->close();
    }
    $this->closed = true;
  }

  /**
   * PHP Magic Function
   *
   * @param mixed $m
   * @param mixed $p
   * @return mixed
   */
  public function __call($m, $p){
    if($m != "close" && $m != "is_closed"){
      if($this->closed){
        throw new kvframework_db_exception("Tried to call method on closed query.");
      } else {
        if(is_callable(array($this->query, $m), false)){
            return call_user_func_array(array($this->query, $m), $p);
        } else {
          throw new kvframework_db_exception("Unknown method called on a query.");
        }
      }
    }
  }

  /**
   * PHP Magic Function
   *
   * @param mixed $v
   * @return mixed
   */
  public function __get($v){
    return $this->query->$v;
  }

}
?>
