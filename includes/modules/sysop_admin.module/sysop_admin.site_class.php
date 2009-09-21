<?php
/**
 * KvScheduler - Sysop Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for sysops
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class sysop_admin_site_class extends admin_site_class{
  /**
   * Selector generator values for sysops
   *
   * @var array
   */
  protected $all_sysops;

  /**
   * Record of selected sysop
   *
   * @var mixed
   */
  protected $thisguy;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::sysop;
  }

  /**
   * Addition form
   *
   */
  public function add_form(){
    $this->output_page("add_form", "inline");
  }

  /**
   * Processing of addition form
   *
   */
  public function process_add_form(){
    if(array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"]) && array_key_exists("name", self::$PARAMS["form"])){
      $id = Sysop::create(array("username" => self::$PARAMS["form"]["name"]));
      if($id === 0){
        self::throwMessage("Sysop added successfully.");
        $this->output_page("index", "inline", "admin");
      } else {
        self::throwError("Sysop not added successfully.");
        $this->output_page("add_form", "inline");
      }
    } else {
      self::throwError("Invalid parameters passed.");
      $this->output_page("index", "inline", "admin");
    }
  }

  /**
   * Removal selector
   *
   */
  public function remove_form(){
    $this->all_sysops = array();
    $sql = "SELECT * FROM sysops ORDER BY username";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_sysops[] = array($row->username, $row->username);
    }

    $this->output_page("remove_select", "inline");
  }

  /**
   * Confirmation of removal
   *
   */
  public function remove_confirm(){
    if(array_key_exists("sid", self::$PARAMS)){
      $sql = "SELECT * FROM sysops WHERE username = '".self::$PARAMS["sid"]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thisguy = self::$DB->fetch($q);
        $this->output_page("remove_confirm", "inline");
      } else {
        self::throwError("The desired Sysop's record was not found in the system");
        $this->remove_form();
      }
    } else {
      self::throwError("Invalid parameters passed.");
      $this->remove_form();
    }
  }

  /**
   * Processing of removal
   *
   */
  public function process_remove_confirm(){
    if(array_key_exists("sid", self::$PARAMS)){
      $sql = "SELECT * FROM sysops WHERE username = '".self::$PARAMS["sid"]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thisguy = self::$DB->fetch($q);
        if(!array_key_exists("confirm", self::$PARAMS) || self::$PARAMS["confirm"] != "yes"){
          self::throwMessage("Confirmation for removal was denied.");
        } elseif(!Sysop::destroy($this->thisguy->username)){
          self::throwError("Sysop was not able to be removed for an unknown reason.");
        } else {
          self::throwMessage("Sysop was removed successfully.");
        }
      } else {
        self::throwError("The desired Sysop's record was not found in the system");
      }
    } else {
      self::throwError("Invalid parameters passed.");
    }

    $this->output_page("index","inline","admin");
  }
}
