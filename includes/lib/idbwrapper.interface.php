<?php
/**
 * KvScheduler - Required Model Interface
 * @package KvScheduler
 * @subpackage Lib.Models
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Required Model interface
 *
 * @package KvScheduler
 * @subpackage Lib.Models
 */
interface iDbWrapper{
  /**
   * Create a new database record
   *
   * @param array $params
   */
  static function create(array $params);
  /**
   * Update an existing database record
   *
   * @param integer $id
   * @param array $attribs
   */
  static function update_attributes($id, array $attribs);
  /**
   * Destroy existing database record(s)
   *
   * @param mixed $ids
   */
  static function destroy($ids);
  /**
   * Generate a descriptive name for a database record
   *
   * @param kvframework_db_object $self
   */
  static function select_name(kvframework_db_object $self);
}
?>
