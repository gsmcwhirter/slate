<?php
/**
 * KvScheduler - Helpdesk User Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for Helpdesk Users
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class helpdesker_admin_site_class extends admin_site_class{

  /**
   * Array of things for a select generator
   *
   * @var array
   */
  protected $all_things;

  /**
   * User account
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
  }

  /**
   * Addition form
   *
   */
  public function add_form(){
    $this->output_page("add_form", "inline");
  }

  /**
   * Process addition form
   *
   */
  public function process_add_form(){
    if(array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"])){
      $pass = $this->USER->random_password(10);
      $crypt = $this->USER->crypt_password($pass);
      $id = Helpdesker::create(array_merge(self::$PARAMS["form"], array("password" => $crypt, "force_pass_change" => 1)));
      if($id){
        self::throwMessage("Helpdesk user added successfully.");
        $this->output_page("index", "inline","admin");
      } else {
        $this->output_page("add_form", "inline");
      }
    } else {
      self::throwError("Invalid parameters passed.");
      $this->output_page("add_form", "inline");
    }
  }

  /**
   * Select for for editing
   *
   */
  public function edit_select(){
    $this->select_prep();
    $this->output_page("edit_select", "inline");
  }

  /**
   * Editing form
   *
   */
  public function edit_form(){
    if($this->form_prep()){
      $this->output_page("edit_form", "inline");
    } else {
      $this->edit_select();
    }
  }

  /**
   * Process editing form
   *
   */
  public function process_edit_form(){
    if($this->form_prep()){
      if(array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"])){
        if(Helpdesker::update_attributes($this->thisguy->helpdesker_id, array_merge(self::$PARAMS["form"], array("password" => $this->thisguy->password)))){
          self::throwMessage("Helpdesk User record modified successfully.");
          $this->output_page("index", "inline", "admin");
        } else {
          $this->edit_form();
        }
      } else {
        self::throwError("Invalid parameters passed.");
        $this->output_page("index", "inline","admin");
      }
    } else {
      $this->edit_select();
    }
  }

  /**
   * Select for deleting
   *
   */
  public function delete_select(){
    $this->select_prep();
    $this->output_page("delete_select", "inline");
  }

  /**
   * Confirm deleting
   *
   */
  public function delete_confirm(){
    if($this->form_prep()){
      $this->output_page("confirm_remove", "inline");
    } else {
      $this->delete_select();
    }
  }

  /**
   * Process deletion
   *
   */
  public function process_delete_confirm(){
    if($this->form_prep()){
      if(!array_key_exists("confirm", self::$PARAMS) || self::$PARAMS["confirm"] != "yes"){
        self::throwMessage("Confirmation for removal was denied.");
      } elseif(!Helpdesker::destroy($this->thisguy->helpdesker_id)){
        self::throwError("Helpdesk user was not able to be removed for an unknown reason.");
      } else {
        self::throwMessage("Helpdesk user was removed successfully.");
      }
      $this->output_page("index", "inline", "admin");
    } else {
      $this->delete_select();
    }
  }

  /**
   * Prepare the selectors by generating the select values array
   *
   * @return boolean true
   */
  protected function select_prep(){
    $this->all_things = array();
    $sql = "SELECT *, id as helpdesker_id FROM helpdeskers ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_things[] = array(Helpdesker::select_name($row), $row->helpdesker_id);
    }

    return true;
  }

  /**
   * Prep the edit and delete forms by making sure the selected person is in the database
   *
   * @return boolean
   */
  protected function form_prep(){
    if(!array_key_exists("hid", self::$PARAMS)){
      self::throwError("Malformed request.");
      return false;
    } else {
      $sql = "SELECT *, id as helpdesker_id FROM helpdeskers WHERE id = '".self::$PARAMS["hid"]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thisguy = self::$DB->fetch($q);
        return true;
      } else {
        self::throwError("Helpdesk User not found in the database.");
        return false;
      }
    }
  }
}
