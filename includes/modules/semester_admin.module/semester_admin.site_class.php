<?php
/**
 * KvScheduler - Semester Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for Semesters
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class semester_admin_site_class extends admin_site_class{
  /**
   * Selector generator values for semesters
   *
   * @var array
   */
  protected $all_semesters;

  /**
   * Selected semester record
   *
   * @var mixed
   */
  protected $thissem;

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
    $this->output_page("add_semester", "inline");
  }

  /**
   * Processing of addition form
   *
   */
  public function process_add_form(){
    if(array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"]) && array_key_exists("date", self::$PARAMS) && is_array(self::$PARAMS["date"]) && array_key_exists("startdate", self::$PARAMS["date"]) && array_key_exists("stopdate", self::$PARAMS["date"]) && is_array(self::$PARAMS["date"]["startdate"]) && is_array(self::$PARAMS["date"]["stopdate"])){
      $id = Semester::create(array_merge(self::$PARAMS["form"], array("startdate" => TOOLS::string_to_date(implode("-", self::$PARAMS["date"]["startdate"])), "stopdate" => TOOLS::string_to_date(implode("-", self::$PARAMS["date"]["stopdate"])))));
      if($id){
        self::throwMessage("Semester added successfully.");
        $this->output_page("index", "inline","admin");
      } else {
        $this->output_page("add_semester", "inline");
      }
    } else {
      self::throwError("Invalid parameters passed.");
      $this->output_page("add_semester", "inline");
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
    if($this->form_prep()){
      $this->output_page("edit_semester", "inline");
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
      if(array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"]) && array_key_exists("date", self::$PARAMS) && is_array(self::$PARAMS["date"]) && array_key_exists("startdate", self::$PARAMS["date"]) && array_key_exists("stopdate", self::$PARAMS["date"]) && is_array(self::$PARAMS["date"]["startdate"]) && is_array(self::$PARAMS["date"]["stopdate"])){
        if(Semester::update_attributes($this->thissem->semester_id, array_merge(self::$PARAMS["form"], array("startdate" => TOOLS::string_to_date(implode("-", self::$PARAMS["date"]["startdate"])), "stopdate" => TOOLS::string_to_date(implode("-", self::$PARAMS["date"]["stopdate"])))))){
          self::throwMessage("Semester record modified successfully.");
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
   * Confirmation for deleting
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
   * Process deleting
   *
   */
  public function process_delete_confirm(){
    if($this->form_prep()){
      if(!array_key_exists("confirm", self::$PARAMS) || self::$PARAMS["confirm"] != "yes"){
        self::throwMessage("Confirmation for removal was denied.");
      } elseif(!Semester::destroy($this->thissem->semester_id)){
        self::throwError("Semester was not able to be removed for an unknown reason.");
      } else {
        self::throwMessage("Semester was removed successfully.");
      }
      $this->output_page("index", "inline", "admin");
    } else {
      $this->delete_select();
    }
  }

  /**
   * Prepare the select pages
   *
   * @return boolean true
   */
  protected function select_prep(){
    $this->all_semesters = array();
    $sql = "SELECT *, id as semester_id FROM semesters ORDER BY startdate DESC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_semesters[] = array(Semester::select_name($row), $row->semester_id);
    }

    return true;
  }

  /**
   * Preparation of the editing and deleting forms
   *
   * @return boolean
   */
  protected function form_prep(){
    if(!array_key_exists("sid", self::$PARAMS)){
      self::throwError("Malformed request.");
      return false;
    } else {
      $sql = "SELECT *, id as semester_id FROM semesters WHERE id = '".self::$PARAMS["sid"]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thissem = self::$DB->fetch($q);
        return true;
      } else {
        self::throwError("Semester not found in the database.");
        return false;
      }
    }
  }
}
