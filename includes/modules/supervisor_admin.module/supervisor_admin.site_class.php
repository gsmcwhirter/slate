<?php
/**
 * KvScheduler - Supervisor Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for supervisors
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class supervisor_admin_site_class extends admin_site_class{
  /**
   * Selector generator values for supervisors
   *
   * @var array
   */
  protected $all_things;
  /**
   * Selected supervisors
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
   * Processing of addition form
   *
   */
  public function process_add_form(){
    if(array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"])){
      $pass = $this->USER->random_password(10);
      $crypt = $this->USER->crypt_password($pass);
      $id = Supervisor::create(array_merge(self::$PARAMS["form"], array("password" => $crypt, "force_pass_change" => 1)));
      if($id){
        self::throwMessage("Supervisor added successfully.");
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
   * Select for editing
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
   * Processing of editing form
   *
   */
  public function process_edit_form(){
    if($this->form_prep()){
      if(array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"])){
        if(Supervisor::update_attributes($this->thisguy->supervisor_id, array_merge(self::$PARAMS["form"], array("password" => $this->thisguy->password)))){
          self::throwMessage("Supervisor record modified successfully.");
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
   * Select for deletion
   *
   */
  public function delete_select(){
    $this->select_prep();
    $this->output_page("delete_select", "inline");
  }

  /**
   * Confirmation of deletion
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
   * Processing of deletion
   */
  public function process_delete_confirm(){
    if($this->form_prep()){
      if(!array_key_exists("confirm", self::$PARAMS) || self::$PARAMS["confirm"] != "yes"){
        self::throwMessage("Confirmation for removal was denied.");
      } elseif(!Supervisor::destroy($this->thisguy->supervisor_id)){
        self::throwError("Supervisor was not able to be removed for an unknown reason.");
      } else {
        self::throwMessage("Supervisor was removed successfully.");
      }
      $this->output_page("index", "inline", "admin");
    } else {
      $this->delete_select();
    }
  }

  /**
   * Prepare selection pages
   *
   * @return boolean true
   */
  protected function select_prep(){
    $this->all_things = array();
    $sql = "SELECT *, id as supervisor_id FROM supervisors ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_things[] = array(Supervisor::select_name($row), $row->supervisor_id);
    }

    return true;
  }

  /**
   * Prepare the editing and deleting forms
   *
   * @return boolean
   */
  protected function form_prep(){
    if(!array_key_exists("sid", self::$PARAMS)){
      self::throwError("Malformed request.");
      return false;
    } else {
      $sql = "SELECT *, id as supervisor_id FROM supervisors WHERE id = '".self::$PARAMS["sid"]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thisguy = self::$DB->fetch($q);
        return true;
      } else {
        self::throwError("Supervisor not found in the database.");
        return false;
      }
    }
  }
}
