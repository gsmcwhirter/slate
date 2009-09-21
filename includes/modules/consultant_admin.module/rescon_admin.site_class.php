<?php
/**
 * KvScheduler - Consultant Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for Consultants
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class consultant_admin_site_class extends admin_site_class{
  /**
   * Selector generator values array for consultants
   *
   * @var array
   */
  protected $all_things;

  /**
   * Selector generator values array for tags
   *
   * @var array
   */
  protected $all_tags;

  /**
   * Selected consultant record
   *
   * @var mixed
   */
  protected $thisguy;

  /**
   * Constructor
   */
  function __construct(){
    parent::__construct();
  }

  /**
   * Addition form
   */
  public function add_form(){
    $this->prep_tags();
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
      $id = Consultant::create(array_merge(self::$PARAMS["form"], array("password" => $crypt, "force_pass_change" => 1, "appt_perms" => ApHash::checkbox_input_to_string(self::$PARAMS["appt_perms"]))));
      if($id){
        self::throwMessage("Consultant added successfully.");
        $this->output_page("index", "inline","admin");
      } else {
        $this->add_form();
      }
    } else {
      self::throwError("Invalid parameters passed.");
      $this->add_form();
    }
  }

  /**
   * Status Form
   */
  public function status_form(){
    $this->select_prep(true);
    $this->output_page("stati","inline");
  }

  /**
   * Processing of status form
   */
  public function process_status_form(){
    if(array_key_exists("rc_status", self::$PARAMS) && is_array(self::$PARAMS["rc_status"])){
      foreach(self::$PARAMS["rc_status"] as $rcid => $status){
        if(!Consultant::update_attributes($rcid, array("status" => $status))){
          self::throwError("Updating failed for Consultant ID ".$rcid);
        }
      }

      if(!self::is_errors()){
        self::throwMessage("Consultant status updated successfully.");
      }

      $this->output_page("index","inline","admin");
    } else {
      self::throwError("Invalid parameters passed.");
      $this->status_form();
    }
  }

  /**
   * Select for editing
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
    $this->prep_tags();
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
        if(Consultant::update_attributes($this->thisguy->consultant_id, array_merge(self::$PARAMS["form"], array("password" => $this->thisguy->password, "appt_perms" => ApHash::checkbox_input_to_string(self::$PARAMS["appt_perms"]))))){
          self::throwMessage("Consultant record modified successfully.");
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
   * Select for removal
   *
   */
  public function delete_select(){
    $this->select_prep();
    $this->output_page("delete_select", "inline");
  }

  /**
   * Confirmation of removal
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
   * Processing of removal form
   *
   */
  public function process_delete_confirm(){
    if($this->form_prep()){
      if(!array_key_exists("confirm", self::$PARAMS) || self::$PARAMS["confirm"] != "yes"){
        self::throwMessage("Confirmation for removal was denied.");
      } elseif(!Consultant::destroy($this->thisguy->consultant_id)){
        self::throwError("Consultant was not able to be removed for an unknown reason.");
      } else {
        self::throwMessage("Consultant was removed successfully.");
      }
      $this->output_page("index", "inline", "admin");
    } else {
      $this->delete_select();
    }
  }

  /**
   * Prepare the editing and removal selector pages
   *
   * @return boolean true
   */
  protected function select_prep($incl_status = false){
    $this->all_things = array();
    $sql = "SELECT *, id as consultant_id FROM consultants WHERE status != 'deleted' ORDER BY SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_things[] = ($incl_status) ? array(Consultant::select_name($row), $row->consultant_id, $row->status) : array(Consultant::select_name($row), $row->consultant_id);
    }

    return true;
  }

  /**
   * Prepare the editing and removal forms
   *
   * @return boolean
   */
  protected function form_prep(){
    if(!array_key_exists("sid", self::$PARAMS)){
      self::throwError("Malformed request.");
      return false;
    } else {
      $sql = "SELECT *, id as consultant_id FROM consultants WHERE id = '".self::$PARAMS["sid"]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thisguy = self::$DB->fetch($q);
        return true;
      } else {
        self::throwError("Consultant not found in the database.");
        return false;
      }
    }
  }

  /**
   * Prep for the addition and editing forms
   *
   * @return boolean true
   */
  protected function prep_tags(){
    $sql = "SELECT *, id as tag_id FROM tags ORDER BY label";
    $q = self::$DB->query($sql);
    $this->all_tags = array();
    while($row = self::$DB->fetch($q)){
      $this->all_tags[] = array(Tag::select_name($row), $row->tag_id);
    }

    return true;
  }
}
