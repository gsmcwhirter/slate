<?php
/**
 * KvScheduler - Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Admin Site Class
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class admin_site_class extends application_site_class {

  /**
   * Constructor - sets access to ACCESS::user
   *
   * @see ACCESS
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::modify;
  }

  /**
   * Display the admin index page
   *
   */
  public function index(){
    $this->output_page("index", "inline");
  }

}
?>
